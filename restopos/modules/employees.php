<?php
require_once '../includes/config.php';
requireAccess('employees');
$db = getDB();
$pageTitle = 'Employees'; $activePage = 'employees';

define('OT_RATE_PER_HOUR', 100); // Flat Rs. 100 per OT hour
define('STANDARD_WORK_MINUTES', 9*60); // 9-hour standard shift from Time In

// ── CSV EXPORTS ───────────────────────────────────────────────
if (isset($_GET['export_att'])) {
    $expFrom = $_GET['from'] ?? date('Y-m-d');
    $expTo   = $_GET['to']   ?? date('Y-m-d');
    $rows = $db->prepare("SELECT e.name,e.position,a.att_date,a.status,a.time_in,a.time_out,a.overtime_hours,a.notes
        FROM attendance a JOIN employees e ON a.employee_id=e.id
        WHERE a.att_date BETWEEN ? AND ? AND e.name NOT LIKE '[DEL]%' ORDER BY a.att_date,e.name");
    $rows->execute([$expFrom,$expTo]); $rows=$rows->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_'.$expFrom.'_'.$expTo.'.csv');
    $f=fopen('php://output','w'); fprintf($f,chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($f,['Employee','Position','Date','Status','Time In','Time Out','OT Hours','Notes']);
    foreach($rows as $r) fputcsv($f,[$r['name'],$r['position'],$r['att_date'],$r['status'],$r['time_in']??'',$r['time_out']??'',$r['overtime_hours'],$r['notes']??'']);
    fclose($f); exit;
}
if (isset($_GET['export_ot'])) {
    $m=$_GET['month']??date('Y-m'); $ms=$m.'-01'; $me=date('Y-m-t',strtotime($ms));
    $rows=$db->prepare("SELECT e.name,e.position,e.basic_salary,
        COUNT(CASE WHEN a.status='Present' THEN 1 END) as present_days,
        COUNT(CASE WHEN a.status='Absent' THEN 1 END) as absent_days,
        COALESCE(SUM(a.overtime_hours),0) as total_ot
        FROM employees e LEFT JOIN attendance a ON e.id=a.employee_id AND a.att_date BETWEEN ? AND ?
        WHERE e.active=1 AND e.name NOT LIKE '[DEL]%' GROUP BY e.id ORDER BY e.name");
    $rows->execute([$ms,$me]); $rows=$rows->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ot_'.$m.'.csv');
    $f=fopen('php://output','w'); fprintf($f,chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($f,['Employee','Position','Basic Salary','Present Days','Absent Days','OT Hours','OT Pay (Rs.)']);
    foreach($rows as $r){
        $otPay=round(OT_RATE_PER_HOUR*$r['total_ot'],2);
        fputcsv($f,[$r['name'],$r['position'],$r['basic_salary'],$r['present_days'],$r['absent_days'],$r['total_ot'],$otPay]);
    }
    fclose($f); exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $db->prepare("INSERT INTO employees(name,nic,phone,position,basic_salary,allowances,epf_applicable,joined_date) VALUES(?,?,?,?,?,?,?,?)")
           ->execute([$_POST['name'],$_POST['nic']??'',$_POST['phone']??'',$_POST['position']??'',$_POST['basic_salary']??0,$_POST['allowances']??0,$_POST['epf']??0,$_POST['joined_date']??date('Y-m-d')]);
        $msg=['type'=>'success','text'=>'Employee added.'];

    } elseif ($action === 'edit_emp') {
        $db->prepare("UPDATE employees SET name=?,nic=?,phone=?,position=?,basic_salary=?,allowances=?,epf_applicable=?,joined_date=? WHERE id=?")
           ->execute([$_POST['name'],$_POST['nic']??'',$_POST['phone']??'',$_POST['position']??'',$_POST['basic_salary']??0,$_POST['allowances']??0,$_POST['epf']??0,$_POST['joined_date']??date('Y-m-d'),$_POST['emp_id']]);
        $msg=['type'=>'success','text'=>'Employee updated.'];

    } elseif ($action === 'delete_emp') {
        $db->prepare("UPDATE employees SET active=0, name=CONCAT('[DEL] ',name) WHERE id=?")->execute([$_POST['emp_id']]);
        $msg=['type'=>'success','text'=>'Employee removed.'];

    } elseif ($action === 'toggle') {
        $db->prepare("UPDATE employees SET active=NOT active WHERE id=?")->execute([$_POST['emp_id']]);
        $msg=['type'=>'success','text'=>'Status updated.'];

    } elseif ($action === 'attendance') {
        $rawIn   = $_POST['time_in']  ?: null;
        $rawOut  = $_POST['time_out'] ?: null;
        $timeIn  = $rawIn  ? (strlen($rawIn) ===5 ? $rawIn.':00'  : $rawIn)  : null;
        $timeOut = $rawOut ? (strlen($rawOut)===5 ? $rawOut.':00' : $rawOut) : null;
        $attDate = $_POST['att_date'] ?? date('Y-m-d');
        $status  = $_POST['att_status'] ?? 'Present';

        // Auto-calc OT: standard shift = 9 hours from Time In. Manual override takes priority.
        $otHours = 0;
        if (isset($_POST['ot_manual']) && $_POST['ot_manual'] !== '') {
            $otHours = (float)$_POST['ot_manual'];
        } elseif ($timeOut && in_array($status,['Present','Half Day'])) {
            $otHours = calcOTFromTimes($timeIn, $timeOut);
        }

        $db->prepare("INSERT INTO attendance(employee_id,att_date,status,time_in,time_out,overtime_hours,notes)
                      VALUES(?,?,?,?,?,?,?)
                      ON DUPLICATE KEY UPDATE status=VALUES(status),time_in=VALUES(time_in),
                      time_out=VALUES(time_out),overtime_hours=VALUES(overtime_hours),notes=VALUES(notes)")
           ->execute([$_POST['emp_id'],$attDate,$status,$timeIn,$timeOut,$otHours,$_POST['att_notes']??'']);
        $msg=['type'=>'success','text'=>'Attendance saved.' . ($otHours>0 ? ' OT: '.number_format($otHours,2).' hrs auto-calculated.' : '')];

    } elseif ($action === 'bulk_attendance') {
        $attDate = $_POST['bulk_date'] ?? date('Y-m-d');
        $empIds  = $_POST['bulk_emp_id'] ?? [];
        foreach ($empIds as $eid) {
            $rawTin  = $_POST['bulk_time_in'][$eid]  ?? null;
            $rawTout = $_POST['bulk_time_out'][$eid] ?? null;
            $tin     = $rawTin  ? (strlen($rawTin) ===5 ? $rawTin.':00'  : $rawTin)  : null;
            $tout    = $rawTout ? (strlen($rawTout)===5 ? $rawTout.':00' : $rawTout) : null;
            $stat    = $_POST['bulk_status'][$eid] ?? 'Present';
            // Auto OT: standard shift = 9 hours from this employee's Time In
            $otHrs = 0;
            if (isset($_POST['bulk_ot'][$eid]) && $_POST['bulk_ot'][$eid]!=='') {
                $otHrs = (float)$_POST['bulk_ot'][$eid];
            } elseif ($tout && in_array($stat,['Present','Half Day'])) {
                $otHrs = calcOTFromTimes($tin, $tout);
            }
            if ($tin || $stat !== 'Present') {
                $db->prepare("INSERT INTO attendance(employee_id,att_date,status,time_in,time_out,overtime_hours)
                              VALUES(?,?,?,?,?,?)
                              ON DUPLICATE KEY UPDATE status=VALUES(status),time_in=VALUES(time_in),
                              time_out=VALUES(time_out),overtime_hours=VALUES(overtime_hours)")
                   ->execute([$eid,$attDate,$stat,$tin,$tout,$otHrs]);
            }
        }
        $msg=['type'=>'success','text'=>'Bulk attendance saved for '.$attDate.'.'];

    } elseif ($action === 'save_ot') {
        $empId   = (int)$_POST['ot_emp_id'];
        $otDate  = $_POST['ot_date'];
        $otHours = (float)$_POST['ot_hours'];
        $notes   = $_POST['ot_notes'] ?? '';
        $db->prepare("INSERT INTO attendance(employee_id,att_date,status,overtime_hours,notes)
                      VALUES(?,?,'Present',?,?)
                      ON DUPLICATE KEY UPDATE overtime_hours=VALUES(overtime_hours),notes=VALUES(notes)")
           ->execute([$empId,$otDate,$otHours,$notes]);
        $msg=['type'=>'success','text'=>'OT saved. Will auto-appear in payroll.'];

    } elseif ($action === 'delete_ot') {
        $db->prepare("UPDATE attendance SET overtime_hours=0, notes='' WHERE employee_id=? AND att_date=?")
           ->execute([$_POST['ot_emp_id'],$_POST['ot_date']]);
        $msg=['type'=>'success','text'=>'OT removed.'];
    }
}

// ── FETCH ─────────────────────────────────────────────────────
$employees  = $db->query("SELECT * FROM employees WHERE name NOT LIKE '[DEL]%' ORDER BY active DESC,name")->fetchAll();
$activeEmps = array_filter($employees, fn($e)=>$e['active']);

$todayAtt   = $db->query("SELECT a.* FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.att_date=CURDATE() AND e.name NOT LIKE '[DEL]%'")->fetchAll();
$attByEmp   = array_column($todayAtt, null, 'employee_id');

// Attendance history — support date range filter
$filterType = $_GET['filter_type'] ?? 'date';   // date | week | month | range
$attFrom    = date('Y-m-d');
$attTo      = date('Y-m-d');

switch ($filterType) {
    case 'week':
        $attFrom = date('Y-m-d', strtotime('monday this week'));
        $attTo   = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $attFrom = date('Y-m-01');
        $attTo   = date('Y-m-t');
        break;
    case 'range':
        $attFrom = $_GET['att_from'] ?? date('Y-m-01');
        $attTo   = $_GET['att_to']   ?? date('Y-m-d');
        break;
    default: // date
        $attFrom = $_GET['att_date'] ?? date('Y-m-d');
        $attTo   = $attFrom;
}

$histAtt = $db->prepare("SELECT a.*,e.name as emp_name,e.position,e.basic_salary FROM attendance a
    JOIN employees e ON a.employee_id=e.id
    WHERE a.att_date BETWEEN ? AND ? AND e.name NOT LIKE '[DEL]%'
    ORDER BY a.att_date,e.name");
$histAtt->execute([$attFrom,$attTo]); $histAtt=$histAtt->fetchAll();

// OT records
$month      = $_GET['month'] ?? date('Y-m');
$monthStart = $month.'-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

$otRecords = $db->prepare("SELECT a.*,e.name as emp_name,e.position,e.basic_salary FROM attendance a
    JOIN employees e ON a.employee_id=e.id
    WHERE a.att_date BETWEEN ? AND ? AND a.overtime_hours>0 AND e.name NOT LIKE '[DEL]%'
    ORDER BY a.att_date,e.name");
$otRecords->execute([$monthStart,$monthEnd]); $otRecords=$otRecords->fetchAll();

$otSummary = $db->prepare("SELECT e.id,e.name,e.position,e.basic_salary,
    COUNT(CASE WHEN a.status='Present' THEN 1 END) as present_days,
    COUNT(CASE WHEN a.status='Absent' THEN 1 END) as absent_days,
    COUNT(CASE WHEN a.status='Half Day' THEN 1 END) as half_days,
    COUNT(CASE WHEN a.status='Leave' THEN 1 END) as leave_days,
    COALESCE(SUM(a.overtime_hours),0) as total_ot
    FROM employees e LEFT JOIN attendance a ON e.id=a.employee_id AND a.att_date BETWEEN ? AND ?
    WHERE e.active=1 AND e.name NOT LIKE '[DEL]%' GROUP BY e.id ORDER BY e.name");
$otSummary->execute([$monthStart,$monthEnd]); $otSummary=$otSummary->fetchAll();

function calcOTPay(float $basic, float $otH): float { return round(OT_RATE_PER_HOUR * $otH, 2); }

/**
 * Calculates OT hours dynamically: standard shift is 9 hours from Time In.
 * If no Time In is given, falls back to a default 8:00 AM start.
 * Returns OT hours (rounded to 2dp) worked beyond the 9-hour mark.
 */
function calcOTFromTimes(?string $timeIn, ?string $timeOut): float {
    if (!$timeOut) return 0;
    $inMins = 8*60; // default 8:00 AM if no time-in recorded
    if ($timeIn) {
        list($ih,$im) = array_map('intval', explode(':', substr($timeIn,0,5)));
        $inMins = $ih*60 + $im;
    }
    list($oh,$om) = array_map('intval', explode(':', substr($timeOut,0,5)));
    $outMins = $oh*60 + $om;
    $cutoff  = $inMins + STANDARD_WORK_MINUTES;
    if ($outMins > $cutoff) {
        return round(($outMins - $cutoff) / 60, 2);
    }
    return 0;
}

$activeTab = $_GET['tab'] ?? 'today';
include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-title">Employee Management</div>
  <button class="btn" onclick="openEmpModal()">+ Add Employee</button>
</div>

<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-label">Total Employees</div><div class="stat-value text-blue"><?=count($activeEmps)?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Present Today</div><div class="stat-value text-green"><?=count(array_filter($attByEmp,fn($a)=>$a['status']==='Present'))?></div></div>
  <div class="stat-card"><span class="stat-icon">❌</span><div class="stat-label">Absent Today</div><div class="stat-value text-red"><?=count(array_filter($attByEmp,fn($a)=>$a['status']==='Absent'))?></div></div>
  <div class="stat-card"><span class="stat-icon">⏰</span><div class="stat-label">OT This Month</div><div class="stat-value text-accent"><?=number_format(array_sum(array_column($otRecords,'overtime_hours')),2)?> hrs</div></div>
</div>

<div class="tab-bar">
  <button class="tab-btn <?=$activeTab==='today'?'active':''?>"    onclick="switchTab('emp','today');    setUrlTab('today')">📅 Today's Attendance</button>
  <button class="tab-btn <?=$activeTab==='bulk'?'active':''?>"     onclick="switchTab('emp','bulk');     setUrlTab('bulk')">📋 Bulk Mark</button>
  <button class="tab-btn <?=$activeTab==='history'?'active':''?>"  onclick="switchTab('emp','history');  setUrlTab('history')">🗂 Attendance History</button>
  <button class="tab-btn <?=$activeTab==='ot'?'active':''?>"       onclick="switchTab('emp','ot');       setUrlTab('ot')">⏰ OT Management</button>
  <button class="tab-btn <?=$activeTab==='register'?'active':''?>" onclick="switchTab('emp','register'); setUrlTab('register')">🧑‍💼 Register</button>
</div>

<!-- ══ TODAY ═══════════════════════════════════════════════════ -->
<div data-tabcontent="emp" data-content="today" <?=$activeTab!=='today'?'style="display:none"':''?>>
<div class="card">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">Today — <?=date('d F Y')?></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <span class="badge badge-accent fs-12">⏰ OT auto-calc after 9 hrs from Time In</span>
      <a href="?export_att=1&from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">📥 CSV</a>
      <button onclick="window.print()" class="btn btn-sm btn-outline">🖨 Print</button>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Employee</th><th>Position</th><th>Status</th><th>Time In</th><th>Time Out</th><th class="text-right">OT Hrs</th><th class="text-right">OT Pay</th><th>Notes</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($activeEmps as $e):
        $att   = $attByEmp[$e['id']] ?? null;
        $otPay = $att ? calcOTPay($e['basic_salary'],$att['overtime_hours']) : 0;
      ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($e['name'])?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($e['position']??'—')?></td>
          <td><?php if($att): ?><span class="badge <?=$att['status']==='Present'?'badge-green':($att['status']==='Absent'?'badge-red':'badge-accent')?>"><?=$att['status']?></span><?php else: ?><span class="badge badge-muted">Not Marked</span><?php endif; ?></td>
          <td class="mono fs-12"><?=$att&&$att['time_in']?$att['time_in']:'—'?></td>
          <td class="mono fs-12 <?=$att&&$att['time_out']>='17:00:00'?'text-accent':''?>"><?=$att&&$att['time_out']?$att['time_out']:'—'?></td>
          <td class="text-right mono <?=$att&&$att['overtime_hours']>0?'text-accent fw-700':'text-muted'?>"><?=$att?number_format($att['overtime_hours'],2):'—'?></td>
          <td class="text-right mono text-green"><?=$att&&$att['overtime_hours']>0?fmt($otPay):'—'?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($att['notes']??'—')?></td>
          <td>
            <button class="btn btn-sm btn-outline" onclick="openAttModal(
              <?=$e['id']?>, '<?=addslashes($e['name'])?>',
              '<?=$att?$att['att_date']:date('Y-m-d')?>',
              '<?=$att?$att['status']:'Present'?>',
              '<?=$att&&$att['time_in']?substr($att['time_in'],0,5):''?>',
              '<?=$att&&$att['time_out']?substr($att['time_out'],0,5):''?>',
              <?=$att?$att['overtime_hours']:0?>,
              '<?=addslashes($att['notes']??'')?>'
            )"><?=$att?'✏ Edit':'+ Mark'?></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- ══ BULK MARK ═══════════════════════════════════════════════ -->
<div data-tabcontent="emp" data-content="bulk" <?=$activeTab!=='bulk'?'style="display:none"':''?>>
<div class="card">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">Bulk Attendance Mark</div>
    <div style="display:flex;gap:8px">
      <button type="button" class="btn btn-sm btn-outline" onclick="setAllStatus('Present')">✅ All Present</button>
      <button type="button" class="btn btn-sm btn-outline-red" onclick="setAllStatus('Absent')">❌ All Absent</button>
    </div>
  </div>
  <form method="POST">
    <input type="hidden" name="action" value="bulk_attendance">
    <div class="form-group mb-16" style="max-width:200px">
      <label class="form-label">Date</label>
      <input type="date" name="bulk_date" class="form-control" value="<?=date('Y-m-d')?>">
    </div>
    <div class="alert alert-info fs-12 mb-12">⏰ OT is auto-calculated once Time Out passes 9 hours from Time In (Rs. 100/hr). You can also enter manually.</div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Employee</th><th>Position</th><th style="width:110px">Status</th><th style="width:110px">Time In</th><th style="width:110px">Time Out</th><th style="width:95px">OT Hrs <span class="badge badge-muted" style="font-size:9px">Auto/Manual</span></th><th style="width:80px">Auto OT</th></tr></thead>
        <tbody>
        <?php foreach ($activeEmps as $e):
          $att = $attByEmp[$e['id']] ?? null;
        ?>
          <input type="hidden" name="bulk_emp_id[]" value="<?=$e['id']?>">
          <tr>
            <td class="fw-700"><?=htmlspecialchars($e['name'])?></td>
            <td class="text-muted fs-12"><?=htmlspecialchars($e['position']??'—')?></td>
            <td>
              <select name="bulk_status[<?=$e['id']?>]" class="form-control bulk-status" style="padding:5px 8px;font-size:12px">
                <?php foreach(['Present','Absent','Half Day','Leave'] as $s): ?>
                  <option <?=$att&&$att['status']===$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="time" name="bulk_time_in[<?=$e['id']?>]" id="tin_<?=$e['id']?>" class="form-control" value="<?=$att&&$att['time_in']?substr($att['time_in'],0,5):''?>" oninput="calcBulkOT(<?=$e['id']?>)" style="padding:5px 8px;font-size:12px"></td>
            <td><input type="time" name="bulk_time_out[<?=$e['id']?>]" id="tout_<?=$e['id']?>" class="form-control" value="<?=$att&&$att['time_out']?substr($att['time_out'],0,5):''?>" oninput="calcBulkOT(<?=$e['id']?>)" style="padding:5px 8px;font-size:12px"></td>
            <td><input type="number" name="bulk_ot[<?=$e['id']?>]" id="otin_<?=$e['id']?>" step="0.25" min="0" placeholder="0" value="<?=$att&&$att['overtime_hours']>0?$att['overtime_hours']:''?>" class="form-control" style="padding:5px 8px;font-size:12px"></td>
            <td class="text-right mono text-accent fw-700" id="autoOT_<?=$e['id']?>">—</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="btn btn-green btn-lg" style="margin-top:16px">💾 Save All Attendance</button>
  </form>
</div>
</div>

<!-- ══ ATTENDANCE HISTORY ══════════════════════════════════════ -->
<div data-tabcontent="emp" data-content="history" <?=$activeTab!=='history'?'style="display:none"':''?>>
<div class="card">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">Attendance History</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="?tab=history&filter_type=<?=$filterType?>&att_from=<?=$attFrom?>&att_to=<?=$attTo?>&export_att=1&from=<?=$attFrom?>&to=<?=$attTo?>" class="btn btn-sm btn-outline">📥 Export CSV</a>
      <button onclick="window.print()" class="btn btn-sm btn-outline">🖨 Print</button>
    </div>
  </div>

  <!-- Filter Bar -->
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:16px">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
      <!-- Quick filters -->
      <div>
        <div class="fs-12 text-muted mb-8">Quick Filter</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <a href="?tab=history&filter_type=date&att_date=<?=date('Y-m-d')?>" class="btn btn-sm <?=$filterType==='date'&&$attFrom===date('Y-m-d')?'':'btn-outline'?>">Today</a>
          <a href="?tab=history&filter_type=week" class="btn btn-sm <?=$filterType==='week'?'':'btn-outline'?>">This Week</a>
          <a href="?tab=history&filter_type=month" class="btn btn-sm <?=$filterType==='month'?'':'btn-outline'?>">This Month</a>
        </div>
      </div>

      <!-- Single date -->
      <form method="GET" style="display:flex;gap:6px;align-items:flex-end">
        <input type="hidden" name="tab" value="history">
        <input type="hidden" name="filter_type" value="date">
        <div class="form-group">
          <label class="form-label fs-12">Single Date</label>
          <input type="date" name="att_date" value="<?=$filterType==='date'?$attFrom:date('Y-m-d')?>" class="form-control" style="width:150px">
        </div>
        <button type="submit" class="btn btn-sm">View</button>
      </form>

      <!-- Date range -->
      <form method="GET" style="display:flex;gap:6px;align-items:flex-end">
        <input type="hidden" name="tab" value="history">
        <input type="hidden" name="filter_type" value="range">
        <div class="form-group">
          <label class="form-label fs-12">From</label>
          <input type="date" name="att_from" value="<?=$filterType==='range'?$attFrom:date('Y-m-01')?>" class="form-control" style="width:145px">
        </div>
        <div class="form-group">
          <label class="form-label fs-12">To</label>
          <input type="date" name="att_to" value="<?=$filterType==='range'?$attTo:date('Y-m-d')?>" class="form-control" style="width:145px">
        </div>
        <button type="submit" class="btn btn-sm">Apply Range</button>
      </form>
    </div>

    <div style="margin-top:10px;font-size:13px;color:var(--muted)">
      Showing: <strong style="color:var(--text)"><?=$attFrom === $attTo ? date('d F Y',strtotime($attFrom)) : date('d M Y',strtotime($attFrom)).' → '.date('d M Y',strtotime($attTo))?></strong>
      — <?=count($histAtt)?> records
    </div>
  </div>

  <!-- Summary by employee for the period -->
  <?php if ($attFrom !== $attTo && !empty($histAtt)):
    $empSummary = [];
    foreach ($histAtt as $a) {
      $k = $a['employee_id'];
      if (!isset($empSummary[$k])) $empSummary[$k] = ['name'=>$a['emp_name'],'position'=>$a['position']??'','present'=>0,'absent'=>0,'half'=>0,'leave'=>0,'ot'=>0,'basic'=>$a['basic_salary']];
      if ($a['status']==='Present') $empSummary[$k]['present']++;
      elseif ($a['status']==='Absent') $empSummary[$k]['absent']++;
      elseif ($a['status']==='Half Day') $empSummary[$k]['half']++;
      elseif ($a['status']==='Leave') $empSummary[$k]['leave']++;
      $empSummary[$k]['ot'] += $a['overtime_hours'];
    }
  ?>
  <div class="card mb-16" style="padding:14px;background:rgba(59,130,246,.06);border-color:rgba(59,130,246,.2)">
    <div style="font-weight:700;font-size:13px;margin-bottom:10px;color:var(--blue)">📊 Period Summary</div>
    <div class="table-wrap">
      <table class="data-table" style="font-size:13px">
        <thead><tr><th>Employee</th><th class="text-right">Present</th><th class="text-right">Absent</th><th class="text-right">Half Day</th><th class="text-right">Leave</th><th class="text-right">Total OT Hrs</th><th class="text-right">OT Pay</th></tr></thead>
        <tbody>
        <?php foreach ($empSummary as $s):
          $otPay = calcOTPay($s['basic'],$s['ot']);
        ?>
          <tr>
            <td class="fw-700"><?=htmlspecialchars($s['name'])?></td>
            <td class="text-right text-green fw-700"><?=$s['present']?></td>
            <td class="text-right text-red"><?=$s['absent']?></td>
            <td class="text-right text-accent"><?=$s['half']?></td>
            <td class="text-right text-muted"><?=$s['leave']?></td>
            <td class="text-right mono text-accent fw-700"><?=number_format($s['ot'],2)?></td>
            <td class="text-right mono text-green"><?=$s['ot']>0?fmt($otPay):'—'?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Detail table -->
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Employee</th><th>Position</th><th>Status</th><th>Time In</th><th>Time Out</th><th class="text-right">OT Hrs</th><th class="text-right">OT Pay</th><th>Notes</th><th>Edit</th></tr></thead>
      <tbody>
      <?php foreach ($histAtt as $a):
        $otPay = calcOTPay($a['basic_salary'],$a['overtime_hours']);
      ?>
        <tr>
          <td class="mono fs-12 fw-700"><?=date('d/m/Y',strtotime($a['att_date']))?></td>
          <td class="fw-700"><?=htmlspecialchars($a['emp_name'])?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($a['position']??'—')?></td>
          <td><span class="badge <?=$a['status']==='Present'?'badge-green':($a['status']==='Absent'?'badge-red':'badge-accent')?>"><?=$a['status']?></span></td>
          <td class="mono fs-12"><?=$a['time_in']??'—'?></td>
          <td class="mono fs-12 <?=$a['time_out']>='17:00:00'?'text-accent':''?>"><?=$a['time_out']??'—'?></td>
          <td class="text-right mono text-accent fw-700"><?=number_format($a['overtime_hours'],2)?></td>
          <td class="text-right mono text-green"><?=$a['overtime_hours']>0?fmt($otPay):'—'?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($a['notes']??'—')?></td>
          <td>
            <button class="btn btn-sm btn-outline" onclick="openAttModal(
              <?=$a['employee_id']?>, '<?=addslashes($a['emp_name'])?>',
              '<?=$a['att_date']?>',
              '<?=$a['status']?>',
              '<?=$a['time_in']?substr($a['time_in'],0,5):''?>',
              '<?=$a['time_out']?substr($a['time_out'],0,5):''?>',
              <?=$a['overtime_hours']?>,
              '<?=addslashes($a['notes']??'')?>'
            )">✏ Edit</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($histAtt)): ?><tr><td colspan="10" class="text-center text-muted">No attendance records for this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- ══ OT MANAGEMENT ═══════════════════════════════════════════ -->
<div data-tabcontent="emp" data-content="ot" <?=$activeTab!=='ot'?'style="display:none"':''?>>

<div class="card mb-16">
  <div class="card-title">Add / Edit OT Entry</div>
  <div class="alert alert-info fs-12 mb-16">
    ⏰ OT added here automatically appears in the Payroll module when you generate payslips.
    Formula: Rs. 100 per OT hour (flat rate).
    <br>💡 OT is also auto-calculated from attendance — once an employee works past 9 hours from their Time In, OT accrues at Rs. 100 per hour.
  </div>
  <form method="POST">
    <input type="hidden" name="action" value="save_ot">
    <div class="form-row form-row-4 mb-12">
      <div class="form-group">
        <label class="form-label">Employee *</label>
        <select name="ot_emp_id" class="form-control" required>
          <option value="">Select...</option>
          <?php foreach ($activeEmps as $e): ?>
            <option value="<?=$e['id']?>"><?=htmlspecialchars($e['name'])?> (<?=htmlspecialchars($e['position']??'')?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Date *</label>
        <input type="date" name="ot_date" class="form-control" value="<?=date('Y-m-d')?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">OT Hours *</label>
        <input type="number" name="ot_hours" class="form-control" step="0.25" min="0.25" placeholder="e.g. 2.5" required>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <input type="text" name="ot_notes" class="form-control" placeholder="Reason">
      </div>
    </div>
    <button type="submit" class="btn">💾 Save OT Entry</button>
  </form>
</div>

<div class="card mb-16">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">OT Records — <?=date('F Y',strtotime($monthStart))?></div>
    <div style="display:flex;gap:8px;align-items:center">
      <form method="GET" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="tab" value="ot">
        <input type="month" name="month" value="<?=$month?>" class="form-control" style="width:160px">
        <button type="submit" class="btn btn-sm">View</button>
      </form>
      <a href="?export_ot=1&month=<?=$month?>" class="btn btn-sm btn-outline">📥 CSV</a>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Employee</th><th>Position</th><th class="text-right">OT Hours</th><th class="text-right">Rate</th><th class="text-right">OT Pay</th><th>Notes</th><th>Delete</th></tr></thead>
      <tbody>
      <?php foreach ($otRecords as $r):
        $otPay  = calcOTPay($r['basic_salary'],$r['overtime_hours']);
      ?>
        <tr>
          <td class="mono fs-12 fw-700"><?=date('d/m/Y',strtotime($r['att_date']))?></td>
          <td class="fw-700"><?=htmlspecialchars($r['emp_name'])?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($r['position']??'—')?></td>
          <td class="text-right mono text-accent fw-700"><?=number_format($r['overtime_hours'],2)?> hrs</td>
          <td class="text-right mono text-muted">Rs. 100.00/hr</td>
          <td class="text-right mono text-green fw-700"><?=fmt($otPay)?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($r['notes']??'—')?></td>
          <td>
            <form method="POST" style="display:inline" onsubmit="return confirm('Remove OT?')">
              <input type="hidden" name="action" value="delete_ot">
              <input type="hidden" name="ot_emp_id" value="<?=$r['employee_id']?>">
              <input type="hidden" name="ot_date" value="<?=$r['att_date']?>">
              <button class="btn btn-sm btn-red">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($otRecords)): ?><tr><td colspan="8" class="text-center text-muted">No OT records this month.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-title">Monthly OT Summary — <?=date('F Y',strtotime($monthStart))?></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Employee</th><th>Position</th><th class="text-right">Present</th><th class="text-right">Absent</th><th class="text-right">Half</th><th class="text-right">Leave</th><th class="text-right">Total OT Hrs</th><th class="text-right">Total OT Pay</th></tr></thead>
      <tbody>
      <?php $totOT=0; $totOTPay=0;
      foreach ($otSummary as $row):
        $op=calcOTPay($row['basic_salary'],$row['total_ot']);
        $totOT+=$row['total_ot']; $totOTPay+=$op;
      ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($row['name'])?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($row['position']??'—')?></td>
          <td class="text-right text-green"><?=$row['present_days']?></td>
          <td class="text-right text-red"><?=$row['absent_days']?></td>
          <td class="text-right text-accent"><?=$row['half_days']?></td>
          <td class="text-right text-muted"><?=$row['leave_days']?></td>
          <td class="text-right mono text-accent fw-700"><?=number_format($row['total_ot'],2)?> hrs</td>
          <td class="text-right mono text-green fw-700"><?=$row['total_ot']>0?fmt($op):'—'?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr>
        <td colspan="6"><strong>TOTALS</strong></td>
        <td class="text-right mono text-accent"><strong><?=number_format($totOT,2)?> hrs</strong></td>
        <td class="text-right mono text-green"><strong><?=fmt($totOTPay)?></strong></td>
      </tr></tfoot>
    </table>
  </div>
  <div class="alert alert-info fs-12" style="margin-top:12px">
    💡 These totals auto-appear in <a href="payroll.php" class="text-accent">Payroll</a> when you generate payslips. Select an employee → OT Pay is pre-filled automatically.
  </div>
</div>
</div>

<!-- ══ REGISTER ════════════════════════════════════════════════ -->
<div data-tabcontent="emp" data-content="register" <?=$activeTab!=='register'?'style="display:none"':''?>>
<div class="card">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">Employee Register</div>
    <button class="btn" onclick="openEmpModal()">+ Add Employee</button>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>NIC</th><th>Phone</th><th>Position</th><th class="text-right">Basic</th><th class="text-right">Allowances</th><th>EPF</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($employees as $e): ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($e['name'])?></td>
          <td class="mono fs-12"><?=htmlspecialchars($e['nic']??'—')?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($e['phone']??'—')?></td>
          <td><?=htmlspecialchars($e['position']??'—')?></td>
          <td class="text-right mono"><?=fmt($e['basic_salary'])?></td>
          <td class="text-right mono"><?=fmt($e['allowances'])?></td>
          <td><span class="badge <?=$e['epf_applicable']?'badge-green':'badge-muted'?>"><?=$e['epf_applicable']?'Yes':'No'?></span></td>
          <td class="fs-12 text-muted"><?=$e['joined_date']?date('d/m/Y',strtotime($e['joined_date'])):'—'?></td>
          <td><span class="badge <?=$e['active']?'badge-green':'badge-red'?>"><?=$e['active']?'Active':'Inactive'?></span></td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="btn btn-sm btn-outline"
                data-id="<?=$e['id']?>"
                data-name="<?=htmlspecialchars($e['name'],ENT_QUOTES)?>"
                data-nic="<?=htmlspecialchars($e['nic']??'',ENT_QUOTES)?>"
                data-phone="<?=htmlspecialchars($e['phone']??'',ENT_QUOTES)?>"
                data-position="<?=htmlspecialchars($e['position']??'',ENT_QUOTES)?>"
                data-basic="<?=$e['basic_salary']?>"
                data-allowances="<?=$e['allowances']?>"
                data-epf="<?=$e['epf_applicable']?>"
                data-joined="<?=$e['joined_date']??''?>"
                onclick="openEmpModal(this)">✏ Edit</button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="emp_id" value="<?=$e['id']?>">
                <button class="btn btn-sm <?=$e['active']?'btn-outline-red':'btn-outline-green'?>"><?=$e['active']?'Deactivate':'Activate'?></button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Remove this employee?')">
                <input type="hidden" name="action" value="delete_emp">
                <input type="hidden" name="emp_id" value="<?=$e['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- ══ ATTENDANCE MODAL ════════════════════════════════════════ -->
<div class="modal-overlay" id="modalAttendance">
  <div class="modal-box">
    <div class="modal-title" id="attModalTitle">Mark Attendance</div>
    <form method="POST">
      <input type="hidden" name="action" value="attendance">
      <input type="hidden" name="emp_id" id="attEmpId">
      <input type="hidden" name="att_date" id="attDate">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group">
          <label class="form-label">Date</label>
          <input type="date" id="attDateDisplay" class="form-control" readonly style="opacity:.7">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="att_status" id="attStatus" class="form-control">
            <option>Present</option><option>Absent</option><option>Half Day</option><option>Leave</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Time In</label>
          <input type="time" name="time_in" id="attTimeIn" class="form-control" oninput="calcModalOT()">
        </div>
        <div class="form-group">
          <label class="form-label">Time Out <span class="badge badge-accent" style="font-size:10px">OT auto after 9 hrs</span></label>
          <input type="time" name="time_out" id="attTimeOut" class="form-control" oninput="calcModalOT()">
        </div>
      </div>
      <div class="card mb-12" style="padding:12px;background:rgba(245,158,11,.06);border-color:rgba(245,158,11,.25)">
        <div class="flex-between mb-6">
          <span class="fs-13 fw-700">⏰ OT Hours</span>
          <span id="autoOTBadge" class="badge badge-muted">Auto: 0.00 hrs</span>
        </div>
        <div class="fs-12 text-muted mb-8">Auto-calculated from Time Out. Enter manually to override.</div>
        <input type="number" name="ot_manual" id="attOTManual" step="0.25" min="0" placeholder="Leave blank to use auto-calc" class="form-control">
      </div>
      <div class="form-group mb-12">
        <label class="form-label">Notes</label>
        <input type="text" name="att_notes" id="attNotes" class="form-control">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-green">💾 Save</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAttendance')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EMPLOYEE MODAL ══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalEmp">
  <div class="modal-box">
    <div class="modal-title" id="empModalTitle">Add Employee</div>
    <form method="POST">
      <input type="hidden" name="action" id="empAction" value="add">
      <input type="hidden" name="emp_id" id="empId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Full Name *</label><input name="name" id="empName" class="form-control" required></div>
        <div class="form-group"><label class="form-label">NIC Number</label><input name="nic" id="empNic" class="form-control"></div>
        <div class="form-group"><label class="form-label">Phone</label><input name="phone" id="empPhone" class="form-control"></div>
        <div class="form-group"><label class="form-label">Position</label><input name="position" id="empPosition" class="form-control"></div>
        <div class="form-group"><label class="form-label">Basic Salary (Rs.) *</label><input type="number" name="basic_salary" id="empBasic" class="form-control" step="0.01" required></div>
        <div class="form-group"><label class="form-label">Allowances (Rs.)</label><input type="number" name="allowances" id="empAllow" class="form-control" step="0.01" value="0"></div>
        <div class="form-group"><label class="form-label">Joined Date</label><input type="date" name="joined_date" id="empJoined" class="form-control" value="<?=date('Y-m-d')?>"></div>
        <div class="form-group"><label class="form-label">EPF Applicable</label>
          <select name="epf" id="empEpf" class="form-control"><option value="1">Yes</option><option value="0">No</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn" id="empSubmitBtn">Add Employee</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalEmp');resetEmpModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function setUrlTab(tab) {
  const url = new URL(window.location);
  url.searchParams.set('tab', tab);
  window.history.replaceState({}, '', url);
}

// ── ATTENDANCE MODAL ──────────────────────────────────────────
function openAttModal(empId, name, date, status, timeIn, timeOut, otHours, notes) {
  document.getElementById('attModalTitle').textContent = (status ? 'Edit' : 'Mark') + ' — ' + name;
  document.getElementById('attEmpId').value       = empId;
  document.getElementById('attDate').value         = date;
  document.getElementById('attDateDisplay').value  = date;
  document.getElementById('attStatus').value       = status || 'Present';
  document.getElementById('attTimeIn').value       = timeIn  || '';
  document.getElementById('attTimeOut').value      = timeOut || '';
  document.getElementById('attOTManual').value     = otHours > 0 ? otHours : '';
  document.getElementById('attNotes').value        = notes   || '';
  calcModalOT();
  openModal('modalAttendance');
}

function calcModalOT() {
  const tin  = document.getElementById('attTimeIn').value;
  const tout = document.getElementById('attTimeOut').value;
  let otHours = 0;
  if (tout) {
    const [oh, om] = tout.split(':').map(Number);
    const outMins = oh * 60 + om;
    let inMins = 8 * 60; // default 8:00 AM if Time In not set
    if (tin) {
      const [ih, im] = tin.split(':').map(Number);
      inMins = ih * 60 + im;
    }
    const cutoff = inMins + 9 * 60; // standard 9-hour shift
    if (outMins > cutoff) otHours = Math.round((outMins - cutoff) / 60 * 100) / 100;
  }
  document.getElementById('autoOTBadge').textContent = 'Auto: ' + otHours.toFixed(2) + ' hrs (Rs. ' + (otHours*100).toFixed(2) + ')';
  document.getElementById('autoOTBadge').className   = otHours > 0 ? 'badge badge-accent' : 'badge badge-muted';
}

// ── BULK OT CALC ──────────────────────────────────────────────
function calcBulkOT(empId) {
  const tin  = document.getElementById('tin_' + empId)?.value;
  const tout = document.getElementById('tout_' + empId)?.value;
  const otInput = document.getElementById('otin_' + empId);
  if (!otInput) return;

  let otH = 0;
  if (tout) {
    const [oh, om] = tout.split(':').map(Number);
    const outMins = oh*60+om;
    let inMins = 8*60; // default 8:00 AM if Time In not set
    if (tin) {
      const [ih, im] = tin.split(':').map(Number);
      inMins = ih*60+im;
    }
    const cutoff = inMins + 9*60; // standard 9-hour shift
    if (outMins > cutoff) otH = Math.round((outMins-cutoff)/60*100)/100;
  }

  // Only auto-fill if the user hasn't manually typed something already
  if (otH > 0) {
    otInput.value = otH.toFixed(2);
    otInput.style.color = 'var(--accent)';
  } else {
    otInput.value = '';
    otInput.style.color = '';
  }
}

// ── EMPLOYEE MODAL ────────────────────────────────────────────
function openEmpModal(btn) {
  if (btn) {
    document.getElementById('empModalTitle').textContent = 'Edit Employee';
    document.getElementById('empAction').value    = 'edit_emp';
    document.getElementById('empId').value        = btn.dataset.id;
    document.getElementById('empName').value      = btn.dataset.name;
    document.getElementById('empNic').value       = btn.dataset.nic    || '';
    document.getElementById('empPhone').value     = btn.dataset.phone  || '';
    document.getElementById('empPosition').value  = btn.dataset.position || '';
    document.getElementById('empBasic').value     = btn.dataset.basic;
    document.getElementById('empAllow').value     = btn.dataset.allowances;
    document.getElementById('empJoined').value    = btn.dataset.joined || '';
    document.getElementById('empEpf').value       = btn.dataset.epf;
    document.getElementById('empSubmitBtn').textContent = 'Save Changes';
  } else {
    resetEmpModal();
  }
  openModal('modalEmp');
}

function resetEmpModal() {
  document.getElementById('empModalTitle').textContent = 'Add Employee';
  document.getElementById('empAction').value   = 'add';
  document.getElementById('empId').value       = '';
  document.getElementById('empSubmitBtn').textContent = 'Add Employee';
  ['empName','empNic','empPhone','empPosition'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
  document.getElementById('empBasic').value  = '';
  document.getElementById('empAllow').value  = '0';
  document.getElementById('empEpf').value    = '1';
  document.getElementById('empJoined').value = new Date().toISOString().slice(0,10);
}

function setAllStatus(s) { document.querySelectorAll('.bulk-status').forEach(el=>el.value=s); }

// Activate tab from URL
const urlTab = new URLSearchParams(window.location.search).get('tab');
if (urlTab) switchTab('emp', urlTab);
</script>

<?php include '../includes/footer.php'; ?>
