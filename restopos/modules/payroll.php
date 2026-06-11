<?php
require_once '../includes/config.php';
requireAccess('payroll');
$db = getDB();
$pageTitle = 'Payroll'; $activePage = 'payroll';

$msg = '';
$month      = $_GET['month'] ?? date('Y-m');
$monthStart = $month . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

// ── GENERATE ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'generate') {
    $empId   = (int)$_POST['emp_id'];
    $basic   = (float)$_POST['basic'];
    $allow   = (float)$_POST['allowances'];
    $ot      = (float)$_POST['overtime'];
    $bonus   = (float)$_POST['bonus'];
    $advance = (float)$_POST['advance'];
    $ded     = (float)$_POST['deductions'];
    $gross   = $basic + $allow + $ot + $bonus;

    $empRow  = $db->prepare("SELECT epf_applicable FROM employees WHERE id=?");
    $empRow->execute([$empId]); $empRow=$empRow->fetch();
    $hasEPF  = (bool)($empRow['epf_applicable']??false);

    $epfBase = $basic + $allow;
    $epfEmp  = $hasEPF ? round($epfBase*0.08,2) : 0;
    $epfEr   = $hasEPF ? round($epfBase*0.12,2) : 0;
    $etf     = $hasEPF ? round($epfBase*0.03,2) : 0;
    $net     = $gross - $epfEmp - $advance - $ded;

    $ex = $db->prepare("SELECT id FROM payroll WHERE employee_id=? AND pay_month=?");
    $ex->execute([$empId,$monthStart]); $ex=$ex->fetch();
    if ($ex) {
        $db->prepare("UPDATE payroll SET basic_salary=?,allowances=?,overtime_pay=?,bonus=?,gross_salary=?,salary_advance=?,other_deductions=?,epf_employee=?,epf_employer=?,etf_employer=?,net_salary=?,status='draft' WHERE id=?")
           ->execute([$basic,$allow,$ot,$bonus,$gross,$advance,$ded,$epfEmp,$epfEr,$etf,$net,$ex['id']]);
    } else {
        $db->prepare("INSERT INTO payroll(employee_id,pay_month,basic_salary,allowances,overtime_pay,bonus,gross_salary,salary_advance,other_deductions,epf_employee,epf_employer,etf_employer,net_salary) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([$empId,$monthStart,$basic,$allow,$ot,$bonus,$gross,$advance,$ded,$epfEmp,$epfEr,$etf,$net]);
    }
    $epfNote = $hasEPF ? "EPF/ETF applied." : "No EPF/ETF.";
    $msg = ['type'=>'success','text'=>"Payroll generated. $epfNote Net: ".fmt($net)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'mark_paid') {
    $db->prepare("UPDATE payroll SET status='paid',paid_date=CURDATE() WHERE id=?")->execute([$_POST['pr_id']]);
    $msg = ['type'=>'success','text'=>'Marked as paid.'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete_payroll') {
    $db->prepare("DELETE FROM payroll WHERE id=?")->execute([$_POST['pr_id']]);
    $msg = ['type'=>'success','text'=>'Entry deleted.'];
}

// ── FETCH ─────────────────────────────────────────────────────
$employees = $db->query("SELECT * FROM employees WHERE active=1 AND name NOT LIKE '[DEL]%' ORDER BY name")->fetchAll();

// Pre-load OT for all employees this month in one query
$otMap = [];
$otRows = $db->prepare("SELECT employee_id, COALESCE(SUM(overtime_hours),0) as total_ot FROM attendance WHERE att_date BETWEEN ? AND ? GROUP BY employee_id");
$otRows->execute([$monthStart,$monthEnd]); 
foreach ($otRows->fetchAll() as $r) $otMap[$r['employee_id']] = (float)$r['total_ot'];

$payrolls = $db->prepare("SELECT pr.*,e.name as emp_name,e.position,e.epf_applicable FROM payroll pr JOIN employees e ON pr.employee_id=e.id WHERE pr.pay_month=? ORDER BY e.name");
$payrolls->execute([$monthStart]); $payrolls=$payrolls->fetchAll();

$totals = ['gross'=>0,'epfe'=>0,'epfer'=>0,'etf'=>0,'net'=>0,'advance'=>0];
foreach ($payrolls as $p) {
    $totals['gross']   += $p['gross_salary'];
    $totals['epfe']    += $p['epf_employee'];
    $totals['epfer']   += $p['epf_employer'];
    $totals['etf']     += $p['etf_employer'];
    $totals['net']     += $p['net_salary'];
    $totals['advance'] += $p['salary_advance'];
}

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-title">Payroll — <?= date('F Y',strtotime($monthStart)) ?></div>
  <div style="display:flex;gap:10px;align-items:center">
    <input type="month" value="<?= $month ?>" onchange="location.href='?month='+this.value" class="form-control" style="width:160px">
    <a href="export/index.php?report=payroll&from=<?=$monthStart?>&to=<?=$monthEnd?>&extra=<?=$month?>&format=print" target="_blank" class="btn btn-outline">🖨 Print</a>
    <a href="export/index.php?report=payroll&from=<?=$monthStart?>&to=<?=$monthEnd?>&extra=<?=$month?>&format=excel" class="btn btn-outline">📊 Excel</a>
  </div>
</div>

<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?= htmlspecialchars($msg['text']) ?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Total Net Payable</div><div class="stat-value text-green"><?= fmt($totals['net']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">🏛</span><div class="stat-label">EPF Employer (12%)</div><div class="stat-value text-blue"><?= fmt($totals['epfer']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">ETF Employer (3%)</div><div class="stat-value" style="color:var(--purple)"><?= fmt($totals['etf']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-label">Employees</div><div class="stat-value text-accent"><?= count($employees) ?></div></div>
</div>

<!-- Payroll Register -->
<div class="card mb-16">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">Payroll Register — <?= date('F Y',strtotime($monthStart)) ?></div>
    <a href="reports.php?rpt=payroll&from=<?=$monthStart?>&to=<?=$monthEnd?>&month=<?=$month?>" class="btn btn-sm btn-outline">View Report →</a>
  </div>
  <?php if (empty($payrolls)): ?>
    <div class="alert alert-info">No payroll generated for <?= date('F Y',strtotime($monthStart)) ?>. Use the form below.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Employee</th><th>Position</th><th>EPF</th><th class="text-right">Basic</th><th class="text-right">Allow.</th><th class="text-right">OT</th><th class="text-right">Bonus</th><th class="text-right">Gross</th><th class="text-right">EPF Emp</th><th class="text-right">EPF Er</th><th class="text-right">ETF</th><th class="text-right">Advance</th><th class="text-right">Net</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($payrolls as $p): ?>
        <tr>
          <td class="fw-700"><?= htmlspecialchars($p['emp_name']) ?></td>
          <td class="text-muted fs-12"><?= htmlspecialchars($p['position']??'—') ?></td>
          <td><span class="badge <?= $p['epf_applicable']?'badge-green':'badge-muted' ?>"><?= $p['epf_applicable']?'Yes':'No' ?></span></td>
          <td class="text-right mono"><?= fmt($p['basic_salary']) ?></td>
          <td class="text-right mono"><?= fmt($p['allowances']) ?></td>
          <td class="text-right mono text-accent"><?= fmt($p['overtime_pay']) ?></td>
          <td class="text-right mono"><?= fmt($p['bonus']) ?></td>
          <td class="text-right mono fw-700"><?= fmt($p['gross_salary']) ?></td>
          <td class="text-right mono <?= $p['epf_applicable']?'text-red':'text-muted' ?>"><?= fmt($p['epf_employee']) ?></td>
          <td class="text-right mono <?= $p['epf_applicable']?'text-accent':'text-muted' ?>"><?= fmt($p['epf_employer']) ?></td>
          <td class="text-right mono" style="color:<?= $p['epf_applicable']?'var(--purple)':'var(--muted)' ?>"><?= fmt($p['etf_employer']) ?></td>
          <td class="text-right mono text-red"><?= fmt($p['salary_advance']) ?></td>
          <td class="text-right mono text-green fw-700"><?= fmt($p['net_salary']) ?></td>
          <td><span class="badge <?= $p['status']==='paid'?'badge-green':($p['status']==='approved'?'badge-accent':'badge-muted') ?>"><?= ucfirst($p['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <a href="print_payslip.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline">🖨</a>
              <?php if ($p['status']!=='paid'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="pr_id" value="<?= $p['id'] ?>">
                <button class="btn btn-sm btn-green">✓ Paid</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this payroll entry?')">
                <input type="hidden" name="action" value="delete_payroll">
                <input type="hidden" name="pr_id" value="<?= $p['id'] ?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="7"><strong>TOTALS</strong></td>
          <td class="text-right mono"><strong><?= fmt($totals['gross']) ?></strong></td>
          <td class="text-right mono text-red"><strong><?= fmt($totals['epfe']) ?></strong></td>
          <td class="text-right mono text-accent"><strong><?= fmt($totals['epfer']) ?></strong></td>
          <td class="text-right mono" style="color:var(--purple)"><strong><?= fmt($totals['etf']) ?></strong></td>
          <td class="text-right mono text-red"><strong><?= fmt($totals['advance']) ?></strong></td>
          <td class="text-right mono text-green"><strong><?= fmt($totals['net']) ?></strong></td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Generate Payslip Form -->
<div class="card">
  <div class="card-title">Generate / Edit Payslip</div>

  <!-- OT Summary for this month -->
  <?php if (!empty($otMap)): ?>
  <div class="card mb-16" style="background:rgba(245,158,11,.06);border-color:rgba(245,158,11,.25);padding:14px">
    <div style="font-weight:700;font-size:13px;margin-bottom:10px;color:var(--accent)">⏰ OT Recorded This Month (<?= date('F Y',strtotime($monthStart)) ?>)</div>
    <div class="table-wrap">
      <table class="data-table" style="font-size:13px">
        <thead><tr><th>Employee</th><th class="text-right">OT Hours</th><th class="text-right">Hourly Rate</th><th class="text-right">OT Pay (1.5×)</th></tr></thead>
        <tbody>
        <?php foreach ($employees as $e):
          $empOTHrs = $otMap[$e['id']] ?? 0;
          if ($empOTHrs <= 0) continue;
          $hourly = $e['basic_salary']/26/9;
          $otPay  = round($hourly*1.5*$empOTHrs,2);
        ?>
          <tr>
            <td class="fw-700"><?= htmlspecialchars($e['name']) ?></td>
            <td class="text-right mono text-accent fw-700"><?= number_format($empOTHrs,2) ?> hrs</td>
            <td class="text-right mono text-muted"><?= fmt($hourly) ?>/hr</td>
            <td class="text-right mono text-green fw-700"><?= fmt($otPay) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty(array_filter($otMap))): ?>
          <tr><td colspan="4" class="text-center text-muted">No OT recorded this month. Add OT in Employees → OT Management tab.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="alert alert-info fs-12 mb-16">
    💡 Select an employee — salary and OT pay auto-fill from attendance records.
    EPF (8%+12%) and ETF (3%) calculated only if EPF is applicable.
  </div>

  <form method="POST">
    <input type="hidden" name="action" value="generate">
    <div class="form-row form-row-3 mb-12">
      <div class="form-group">
        <label class="form-label">Employee *</label>
        <select name="emp_id" id="empSelect" class="form-control" onchange="fillSalary(this)" required>
          <option value="">Select Employee...</option>
          <?php foreach ($employees as $e):
            $empOTHrs = $otMap[$e['id']] ?? 0;
            $hourly   = $e['basic_salary']/26/9;
            $otPay    = round($hourly*1.5*$empOTHrs,2);
          ?>
            <option value="<?= $e['id'] ?>"
                    data-basic="<?= $e['basic_salary'] ?>"
                    data-allow="<?= $e['allowances'] ?>"
                    data-epf="<?= $e['epf_applicable'] ?>"
                    data-ot="<?= $otPay ?>"
                    data-othrs="<?= $empOTHrs ?>">
              <?= htmlspecialchars($e['name']) ?>
              (<?= htmlspecialchars($e['position']??'') ?>)
              <?= $e['epf_applicable']?'':'— No EPF' ?>
              <?= $empOTHrs>0?' ⏰ '.number_format($empOTHrs,2).' OT hrs':'' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Basic Salary (Rs.)</label>
        <input type="number" name="basic" id="fBasic" step="0.01" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Allowances (Rs.)</label>
        <input type="number" name="allowances" id="fAllow" step="0.01" class="form-control" value="0">
      </div>
      <div class="form-group">
        <label class="form-label">
          Overtime Pay (Rs.)
          <span id="otBadge" class="badge badge-accent fs-12" style="display:none"></span>
        </label>
        <input type="number" name="overtime" id="fOT" step="0.01" class="form-control" value="0">
      </div>
      <div class="form-group">
        <label class="form-label">Bonus (Rs.)</label>
        <input type="number" name="bonus" id="fBonus" step="0.01" class="form-control" value="0">
      </div>
      <div class="form-group">
        <label class="form-label">Salary Advance (Rs.)</label>
        <input type="number" name="advance" id="fAdvance" step="0.01" class="form-control" value="0">
      </div>
      <div class="form-group">
        <label class="form-label">Other Deductions (Rs.)</label>
        <input type="number" name="deductions" id="fDed" step="0.01" class="form-control" value="0">
      </div>
    </div>

    <div id="epfNotice" class="alert fs-12" style="display:none"></div>
    <button type="submit" class="btn btn-lg" style="margin-top:12px">⚡ Generate Payslip</button>
  </form>
</div>

<script>
function fillSalary(sel) {
  const opt    = sel.options[sel.selectedIndex];
  if (!opt.value) {
    document.getElementById('epfNotice').style.display = 'none';
    return;
  }

  const hasEPF = opt.dataset.epf === '1';
  const otPay  = parseFloat(opt.dataset.ot)    || 0;
  const otHrs  = parseFloat(opt.dataset.othrs) || 0;

  // Fill salary fields
  document.getElementById('fBasic').value   = opt.dataset.basic  || '';
  document.getElementById('fAllow').value   = opt.dataset.allow  || '0';
  document.getElementById('fOT').value      = otPay > 0 ? otPay.toFixed(2) : '0';
  document.getElementById('fBonus').value   = '0';
  document.getElementById('fAdvance').value = '0';
  document.getElementById('fDed').value     = '0';

  // OT badge
  const badge = document.getElementById('otBadge');
  if (otHrs > 0) {
    badge.textContent   = '⏰ ' + otHrs.toFixed(2) + ' hrs auto-filled';
    badge.style.display = 'inline';
  } else {
    badge.style.display = 'none';
  }

  // EPF notice
  const notice = document.getElementById('epfNotice');
  notice.style.display = '';
  let lines = [];
  if (hasEPF) {
    notice.className = 'alert alert-info fs-12';
    lines.push('✅ EPF applicable — EPF Employee 8%, EPF Employer 12%, ETF Employer 3% will be calculated.');
  } else {
    notice.className = 'alert alert-warning fs-12';
    lines.push('⚠ EPF NOT applicable — EPF/ETF will be Rs. 0.00 for this employee.');
  }
  if (otHrs > 0) {
    lines.push('⏰ OT auto-filled from attendance records: <strong>' + otHrs.toFixed(2) + ' hrs = Rs. ' + otPay.toFixed(2) + '</strong>. You can adjust manually if needed.');
  } else {
    lines.push('No OT recorded this month. You can enter OT manually above, or add it in <a href="employees.php?tab=ot" style="color:var(--accent)">Employees → OT Management</a>.');
  }
  notice.innerHTML = lines.join('<br>');
}
</script>

<?php include '../includes/footer.php'; ?>
