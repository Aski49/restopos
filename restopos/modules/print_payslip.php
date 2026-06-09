<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$pr = $db->prepare("SELECT pr.*, e.name as emp_name, e.nic, e.position, e.phone FROM payroll pr JOIN employees e ON pr.employee_id=e.id WHERE pr.id=?");
$pr->execute([$id]);
$pr = $pr->fetch();
if (!$pr) { echo "Payslip not found."; exit; }
$bizName  = getSetting('business_name','RestoPOS');
$bizAddr  = getSetting('address','');
$bizPhone = getSetting('phone','');
$monthLabel = date('F Y', strtotime($pr['pay_month']));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Payslip — <?= htmlspecialchars($pr['emp_name']) ?> — <?= $monthLabel ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Arial,sans-serif; font-size:13px; background:#fff; color:#000; padding:30px; }
  .header { text-align:center; border-bottom:2px solid #000; padding-bottom:12px; margin-bottom:16px; }
  .biz-name { font-size:20px; font-weight:bold; }
  .title { font-size:16px; font-weight:bold; margin:10px 0 4px; text-transform:uppercase; letter-spacing:1px; }
  .subtitle { font-size:12px; color:#555; }
  .section { margin-bottom:14px; }
  .section-title { font-weight:bold; font-size:12px; text-transform:uppercase; letter-spacing:.5px; background:#f0f0f0; padding:5px 10px; border:1px solid #ccc; }
  table { width:100%; border-collapse:collapse; }
  td, th { padding:6px 10px; border:1px solid #ccc; font-size:12px; }
  th { background:#f5f5f5; font-weight:bold; text-align:left; }
  .amount { text-align:right; font-weight:bold; }
  .total-row td { font-weight:bold; background:#f0f0f0; font-size:13px; }
  .net-row td { font-weight:bold; background:#000; color:#fff; font-size:15px; }
  .footer { margin-top:30px; display:flex; justify-content:space-between; font-size:12px; }
  .sig-line { border-top:1px solid #000; padding-top:6px; margin-top:30px; width:180px; text-align:center; }
  @media print { .no-print { display:none; } }
</style>
</head>
<body>
<div class="header">
  <div class="biz-name"><?= htmlspecialchars($bizName) ?></div>
  <div style="font-size:12px;color:#555"><?= htmlspecialchars($bizAddr) ?> | <?= htmlspecialchars($bizPhone) ?></div>
  <div class="title">Salary Payslip</div>
  <div class="subtitle">Pay Period: <?= $monthLabel ?> | Generated: <?= date('d/m/Y') ?></div>
</div>

<!-- Employee Info -->
<div class="section">
  <div class="section-title">Employee Information</div>
  <table>
    <tr><th style="width:25%">Employee Name</th><td><?= htmlspecialchars($pr['emp_name']) ?></td><th style="width:25%">NIC</th><td><?= htmlspecialchars($pr['nic'] ?? '—') ?></td></tr>
    <tr><th>Position</th><td><?= htmlspecialchars($pr['position'] ?? '—') ?></td><th>Phone</th><td><?= htmlspecialchars($pr['phone'] ?? '—') ?></td></tr>
    <tr><th>Pay Month</th><td><?= $monthLabel ?></td><th>Status</th><td><?= ucfirst($pr['status']) ?></td></tr>
  </table>
</div>

<!-- Earnings & Deductions -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
  <div class="section">
    <div class="section-title">Earnings</div>
    <table>
      <tr><td>Basic Salary</td><td class="amount">Rs. <?= number_format($pr['basic_salary'],2) ?></td></tr>
      <tr><td>Allowances</td><td class="amount">Rs. <?= number_format($pr['allowances'],2) ?></td></tr>
      <tr><td>Overtime Pay</td><td class="amount">Rs. <?= number_format($pr['overtime_pay'],2) ?></td></tr>
      <tr><td>Bonus</td><td class="amount">Rs. <?= number_format($pr['bonus'],2) ?></td></tr>
      <tr class="total-row"><td><strong>Gross Salary</strong></td><td class="amount">Rs. <?= number_format($pr['gross_salary'],2) ?></td></tr>
    </table>
  </div>
  <div class="section">
    <div class="section-title">Deductions</div>
    <table>
      <tr><td>EPF Employee (8%)</td><td class="amount">Rs. <?= number_format($pr['epf_employee'],2) ?></td></tr>
      <tr><td>Salary Advance</td><td class="amount">Rs. <?= number_format($pr['salary_advance'],2) ?></td></tr>
      <tr><td>Other Deductions</td><td class="amount">Rs. <?= number_format($pr['other_deductions'],2) ?></td></tr>
      <tr class="total-row"><td><strong>Total Deductions</strong></td><td class="amount">Rs. <?= number_format($pr['epf_employee']+$pr['salary_advance']+$pr['other_deductions'],2) ?></td></tr>
    </table>
  </div>
</div>

<!-- Statutory -->
<div class="section">
  <div class="section-title">Statutory Contributions (Employer)</div>
  <table>
    <tr><th>EPF Employer Contribution (12%)</th><td class="amount">Rs. <?= number_format($pr['epf_employer'],2) ?></td><th>ETF Employer Contribution (3%)</th><td class="amount">Rs. <?= number_format($pr['etf_employer'],2) ?></td></tr>
  </table>
</div>

<!-- Net -->
<table style="margin-bottom:20px">
  <tr class="net-row"><td style="font-size:16px">NET SALARY PAYABLE</td><td class="amount" style="font-size:16px">Rs. <?= number_format($pr['net_salary'],2) ?></td></tr>
</table>

<?php if ($pr['paid_date']): ?>
<div style="padding:8px 12px;background:#e6f9ee;border:1px solid #0a0;font-size:12px">
  ✅ Paid on <?= date('d/m/Y', strtotime($pr['paid_date'])) ?>
</div>
<?php endif; ?>

<div class="footer">
  <div class="sig-line">Employee Signature</div>
  <div class="sig-line">Authorised By</div>
  <div class="sig-line">Accounts</div>
</div>

<div class="no-print" style="margin-top:24px;text-align:center">
  <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;font-size:14px">🖨 Print Payslip</button>
  <button onclick="window.close()" style="padding:8px 20px;cursor:pointer;font-size:14px;margin-left:8px">Close</button>
</div>
<script>window.onload = () => window.print();</script>
</body>
</html>
