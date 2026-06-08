<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'Payroll'; $activePage = 'payroll';

$msg = '';
$month      = $_GET['month'] ?? date('Y-m');
$monthStart = $month . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

// ── GENERATE PAYROLL ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $empId  = (int)$_POST['emp_id'];
    $basic  = (float)$_POST['basic'];
    $allow  = (float)$_POST['allowances'];
    $ot     = (float)$_POST['overtime'];
    $bonus  = (float)$_POST['bonus'];
    $advance= (float)$_POST['advance'];
    $ded    = (float)$_POST['deductions'];
    $gross  = $basic + $allow + $ot + $bonus;

    // ── KEY FIX: check employee's EPF flag ──────────────────
    $empRow = $db->prepare("SELECT epf_applicable FROM employees WHERE id=?");
    $empRow->execute([$empId]);
    $empRow = $empRow->fetch();
    $hasEPF = (bool)($empRow['epf_applicable'] ?? false);

    $epfBase = $basic + $allow;
    $epfEmp  = $hasEPF ? round($epfBase * 0.08, 2) : 0;
    $epfEr   = $hasEPF ? round($epfBase * 0.12, 2) : 0;
    $etf     = $hasEPF ? round($epfBase * 0.03, 2) : 0;
    $net     = $gross - $epfEmp - $advance - $ded;

    $ex = $db->prepare("SELECT id FROM payroll WHERE employee_id=? AND pay_month=?");
    $ex->execute([$empId, $monthStart]); $ex = $ex->fetch();

    if ($ex) {
        $db->prepare("UPDATE payroll SET basic_salary=?,allowances=?,overtime_pay=?,bonus=?,
                      gross_salary=?,salary_advance=?,other_deductions=?,
                      epf_employee=?,epf_employer=?,etf_employer=?,net_salary=?,status='draft'
                      WHERE id=?")
           ->execute([$basic,$allow,$ot,$bonus,$gross,$advance,$ded,$epfEmp,$epfEr,$etf,$net,$ex['id']]);
    } else {
        $db->prepare("INSERT INTO payroll(employee_id,pay_month,basic_salary,allowances,overtime_pay,
                      bonus,gross_salary,salary_advance,other_deductions,
                      epf_employee,epf_employer,etf_employer,net_salary)
                      VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([$empId,$monthStart,$basic,$allow,$ot,$bonus,$gross,$advance,$ded,$epfEmp,$epfEr,$etf,$net]);
    }
    $epfNote = $hasEPF ? "EPF/ETF applied." : "EPF/ETF skipped (not applicable).";
    $msg = ['type'=>'success','text'=>"Payroll generated. $epfNote Net: ".fmt($net)];
}

// ── MARK PAID ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_paid') {
    $db->prepare("UPDATE payroll SET status='paid', paid_date=CURDATE() WHERE id=?")
       ->execute([$_POST['pr_id']]);
    $msg = ['type'=>'success','text'=>'Marked as paid.'];
}

// ── DELETE PAYROLL ENTRY ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_payroll') {
    $db->prepare("DELETE FROM payroll WHERE id=?")->execute([$_POST['pr_id']]);
    $msg = ['type'=>'success','text'=>'Payroll entry deleted.'];
}

// ── FETCH ─────────────────────────────────────────────────────
// Join employees to get epf_applicable flag for display
$employees = $db->query("SELECT * FROM employees WHERE active=1 AND name NOT LIKE '[DEL]%' ORDER BY name")->fetchAll();
$empMap    = array_column($employees, null, 'id');

$payrolls  = $db->prepare("SELECT pr.*, e.name as emp_name, e.position, e.epf_applicable
                            FROM payroll pr
                            JOIN employees e ON pr.employee_id=e.id
                            WHERE pr.pay_month=?
                            ORDER BY e.name");
$payrolls->execute([$monthStart]); $payrolls = $payrolls->fetchAll();

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
  <div class="page-title">Payroll — <?=date('F Y',strtotime($monthStart))?></div>
  <div style="display:flex;gap:10px;align-items:center">
    <input type="month" value="<?=$month?>" onchange="location.href='?month='+this.value"
           class="form-control" style="width:160px">
    <a href="export/index.php?report=payroll&from=<?=$monthStart?>&to=<?=$monthEnd?>&extra=<?=$month?>&format=print"
       target="_blank" class="btn btn-outline">🖨 Print</a>
    <a href="export/index.php?report=payroll&from=<?=$monthStart?>&to=<?=$monthEnd?>&extra=<?=$month?>&format=excel"
       class="btn btn-outline">📊 Excel</a>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div>
<?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Total Net Payable</div><div class="stat-value text-green"><?=fmt($totals['net'])?></div></div>
  <div class="stat-card"><span class="stat-icon">🏛</span><div class="stat-label">EPF Employer (12%)</div><div class="stat-value text-blue"><?=fmt($totals['epfer'])?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">ETF Employer (3%)</div><div class="stat-value" style="color:var(--purple)"><?=fmt($totals['etf'])?></div></div>
  <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-label">Employees</div><div class="stat-value text-accent"><?=count($employees)?></div></div>
</div>

<!-- ── PAYROLL REGISTER TABLE ───────────────────────────────── -->
<div class="card mb-16">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">Payroll Register — <?=date('F Y',strtotime($monthStart))?></div>
    <a href="reports.php?rpt=payroll&from=<?=$monthStart?>&to=<?=$monthEnd?>&month=<?=$month?>"
       class="btn btn-sm btn-outline">View Report →</a>
  </div>

  <?php if (empty($payrolls)): ?>
    <div class="alert alert-info">
      No payroll generated yet for <?=date('F Y',strtotime($monthStart))?>.
      Use the form below to generate payslips.
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Position</th>
          <th>EPF</th>
          <th class="text-right">Basic</th>
          <th class="text-right">Allow.</th>
          <th class="text-right">OT</th>
          <th class="text-right">Gross</th>
          <th class="text-right">EPF Emp (8%)</th>
          <th class="text-right">EPF Er (12%)</th>
          <th class="text-right">ETF (3%)</th>
          <th class="text-right">Advance</th>
          <th class="text-right">Net</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($payrolls as $p): ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($p['emp_name'])?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($p['position']??'—')?></td>
          <td>
            <span class="badge <?=$p['epf_applicable']?'badge-green':'badge-muted'?>">
              <?=$p['epf_applicable']?'Yes':'No'?>
            </span>
          </td>
          <td class="text-right mono"><?=fmt($p['basic_salary'])?></td>
          <td class="text-right mono"><?=fmt($p['allowances'])?></td>
          <td class="text-right mono"><?=fmt($p['overtime_pay'])?></td>
          <td class="text-right mono fw-700"><?=fmt($p['gross_salary'])?></td>

          <!-- EPF/ETF — show 0 with muted style if not applicable -->
          <td class="text-right mono <?=$p['epf_applicable']?'text-red':'text-muted'?>">
            <?=$p['epf_applicable']?fmt($p['epf_employee']):'Rs. 0.00'?>
          </td>
          <td class="text-right mono <?=$p['epf_applicable']?'text-accent':'text-muted'?>">
            <?=$p['epf_applicable']?fmt($p['epf_employer']):'Rs. 0.00'?>
          </td>
          <td class="text-right mono <?=$p['epf_applicable']?'':'text-muted'?>" style="<?=$p['epf_applicable']?'color:var(--purple)':''?>">
            <?=$p['epf_applicable']?fmt($p['etf_employer']):'Rs. 0.00'?>
          </td>

          <td class="text-right mono text-red"><?=fmt($p['salary_advance'])?></td>
          <td class="text-right mono text-green fw-700"><?=fmt($p['net_salary'])?></td>
          <td>
            <span class="badge <?=$p['status']==='paid'?'badge-green':($p['status']==='approved'?'badge-accent':'badge-muted')?>">
              <?=ucfirst($p['status'])?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <a href="print_payslip.php?id=<?=$p['id']?>" target="_blank" class="btn btn-sm btn-outline">🖨</a>
              <?php if ($p['status'] !== 'paid'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="pr_id" value="<?=$p['id']?>">
                <button class="btn btn-sm btn-green">✓ Paid</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete this payroll entry?')">
                <input type="hidden" name="action" value="delete_payroll">
                <input type="hidden" name="pr_id" value="<?=$p['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="6"><strong>TOTALS</strong></td>
          <td class="text-right mono"><strong><?=fmt($totals['gross'])?></strong></td>
          <td class="text-right mono text-red"><strong><?=fmt($totals['epfe'])?></strong></td>
          <td class="text-right mono text-accent"><strong><?=fmt($totals['epfer'])?></strong></td>
          <td class="text-right mono" style="color:var(--purple)"><strong><?=fmt($totals['etf'])?></strong></td>
          <td class="text-right mono text-red"><strong><?=fmt($totals['advance'])?></strong></td>
          <td class="text-right mono text-green"><strong><?=fmt($totals['net'])?></strong></td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ── GENERATE PAYSLIP FORM ─────────────────────────────────── -->
<div class="card">
  <div class="card-title">Generate / Edit Payslip</div>

  <div class="alert alert-info fs-12 mb-16">
    💡 Select an employee to auto-fill salary.
    EPF (8% emp, 12% er) and ETF (3%) are calculated automatically <strong>only if EPF is applicable</strong> for that employee.
  </div>

  <form method="POST">
    <input type="hidden" name="action" value="generate">
    <div class="form-row form-row-3 mb-12">
      <div class="form-group">
        <label class="form-label">Employee</label>
        <select name="emp_id" id="empSelect" class="form-control" onchange="fillSalary(this)" required>
          <option value="">Select Employee...</option>
          <?php foreach ($employees as $e):
            // Get OT hours for this month for this employee
            $otStmt = $db->prepare("SELECT COALESCE(SUM(overtime_hours),0) as total_ot FROM attendance WHERE employee_id=? AND att_date BETWEEN ? AND ?");
            $otStmt->execute([$e['id'],$monthStart,$monthEnd]);
            $empOT = $otStmt->fetch()['total_ot'];
            $otPay = round(($e['basic_salary']/26/9)*1.5*$empOT,2);
          ?>
            <option value="<?=$e['id']?>"
                    data-basic="<?=$e['basic_salary']?>"
                    data-allow="<?=$e['allowances']?>"
                    data-epf="<?=$e['epf_applicable']?>"
                    data-ot="<?=$otPay?>"
                    data-othrs="<?=$empOT?>">
              <?=htmlspecialchars($e['name'])?>
              (<?=htmlspecialchars($e['position']??'')?>)
              <?=$e['epf_applicable']?'':'— No EPF'?>
              <?=$empOT>0?' | OT: '.number_format($empOT,2).' hrs':''?>
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
        <label class="form-label">Overtime Pay (Rs.)</label>
        <input type="number" name="overtime" step="0.01" class="form-control" value="0">
      </div>
      <div class="form-group">
        <label class="form-label">Bonus (Rs.)</label>
        <input type="number" name="bonus" step="0.01" class="form-control" value="0">
      </div>
      <div class="form-group">
        <label class="form-label">Salary Advance (Rs.)</label>
        <input type="number" name="advance" step="0.01" class="form-control" value="0">
      </div>
      <div class="form-group">
        <label class="form-label">Other Deductions (Rs.)</label>
        <input type="number" name="deductions" step="0.01" class="form-control" value="0">
      </div>
    </div>

    <!-- EPF status indicator -->
    <div id="epfNotice" class="alert alert-info fs-12" style="display:none"></div>

    <button type="submit" class="btn btn-lg" style="margin-top:12px">Generate Payslip</button>
  </form>
</div>

<script>
function fillSalary(sel) {
  const opt    = sel.options[sel.selectedIndex];
  const hasEPF = opt.dataset.epf === '1';
  const otPay  = parseFloat(opt.dataset.ot) || 0;
  const otHrs  = parseFloat(opt.dataset.othrs) || 0;

  document.getElementById('fBasic').value   = opt.dataset.basic || '';
  document.getElementById('fAllow').value   = opt.dataset.allow || '';

  // Auto-fill OT pay from attendance records
  const otField = document.querySelector('input[name="overtime"]');
  if (otField) otField.value = otPay > 0 ? otPay.toFixed(2) : '0';

  const notice = document.getElementById('epfNotice');
  if (opt.value) {
    notice.style.display = '';
    let msg = hasEPF
      ? '✅ EPF applicable — EPF 8% emp, 12% employer, ETF 3% will be calculated.'
      : '⚠ EPF NOT applicable — EPF/ETF will be Rs. 0.00.';
    if (otHrs > 0) {
      msg += ` | ⏰ OT auto-filled: ${otHrs.toFixed(2)} hrs = Rs. ${otPay.toFixed(2)}`;
    } else {
      msg += ' | No OT recorded this month.';
    }
    notice.className = hasEPF ? 'alert alert-info fs-12' : 'alert alert-warning fs-12';
    notice.innerHTML = msg;
  } else {
    notice.style.display = 'none';
  }
}
</script>

<?php include '../includes/footer.php'; ?>
