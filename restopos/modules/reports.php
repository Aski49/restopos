<?php
require_once '../includes/config.php';
requireAccess('reports');
$db = getDB();
$pageTitle = 'Reports'; $activePage = 'reports';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$rpt  = $_GET['rpt']  ?? 'pl';
$month = $_GET['month'] ?? date('Y-m');

// ── P&L ───────────────────────────────────────────────────────
$sales = $db->prepare("SELECT COALESCE(SUM(total),0) as t, COALESCE(SUM(subtotal),0) as sub, COALESCE(SUM(service_charge),0) as svc, COALESCE(SUM(discount_amt),0) as disc, COALESCE(SUM(tax_amt),0) as tax FROM bills WHERE DATE(created_at) BETWEEN :f AND :t AND status='settled'");
$sales->execute([':f'=>$from,':t'=>$to]); $salesData = $sales->fetch();

$expTot = $db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE expense_date BETWEEN :f AND :t");
$expTot->execute([':f'=>$from,':t'=>$to]); $expTot = $expTot->fetch()['t'];

$expByCat = $db->prepare("SELECT ec.name, COALESCE(SUM(e.amount),0) as total FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN :f AND :t GROUP BY ec.name ORDER BY total DESC");
$expByCat->execute([':f'=>$from,':t'=>$to]); $expByCat = $expByCat->fetchAll();

$grossProfit = $salesData['t'] - $expTot;
$margin = $salesData['t']>0 ? round($grossProfit/$salesData['t']*100,1) : 0;

// ── Expense list ──────────────────────────────────────────────
$expList = $db->prepare("SELECT e.*, ec.name as cat FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN :f AND :t ORDER BY e.expense_date DESC");
$expList->execute([':f'=>$from,':t'=>$to]); $expList=$expList->fetchAll();

// ── Payroll ───────────────────────────────────────────────────
$monthStart = $month . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));
$payroll = $db->prepare("SELECT pr.*, e.name as emp_name, e.position FROM payroll pr JOIN employees e ON pr.employee_id=e.id WHERE pr.pay_month=? ORDER BY e.name");
$payroll->execute([$monthStart]); $payroll=$payroll->fetchAll();

// ── Cash flow ─────────────────────────────────────────────────
$cashIn  = $db->prepare("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at) BETWEEN :f AND :t AND status='settled' AND payment_method='Cash'");
$cashIn->execute([':f'=>$from,':t'=>$to]); $cashIn=$cashIn->fetch()['t'];
$cashOut = $db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE expense_date BETWEEN :f AND :t AND payment_method='Cash'");
$cashOut->execute([':f'=>$from,':t'=>$to]); $cashOut=$cashOut->fetch()['t'];
$cardIn  = $db->prepare("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at) BETWEEN :f AND :t AND status='settled' AND payment_method='Card'");
$cardIn->execute([':f'=>$from,':t'=>$to]); $cardIn=$cardIn->fetch()['t'];
$platIn  = $db->prepare("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at) BETWEEN :f AND :t AND status='settled' AND payment_method IN('Uber Eats','PickMe')");
$platIn->execute([':f'=>$from,':t'=>$to]); $platIn=$platIn->fetch()['t'];
$bankTxn = $db->query("SELECT bt.*, ba.bank_name FROM bank_transactions bt JOIN bank_accounts ba ON bt.account_id=ba.id ORDER BY bt.txn_date DESC LIMIT 50")->fetchAll();

// ── Inventory ─────────────────────────────────────────────────
$invValue = $db->query("SELECT COALESCE(SUM(qty*unit_cost),0) as t FROM inventory_items")->fetch()['t'];
$inv = $db->query("SELECT ii.*, ic.name as cat FROM inventory_items ii JOIN inventory_categories ic ON ii.category_id=ic.id ORDER BY ic.name, ii.name")->fetchAll();

// ── Debtors ───────────────────────────────────────────────────
$debtors = $db->query("SELECT * FROM debtors ORDER BY outstanding DESC")->fetchAll();
$totalDebt = array_sum(array_column($debtors,'outstanding'));

// ── Promotions ───────────────────────────────────────────────
$promoUsage = $db->prepare("SELECT bp.*, b.bill_no, b.created_at, b.total as bill_total, b.order_type, b.payment_method
    FROM bill_promotions bp
    JOIN bills b ON bp.bill_id=b.id
    WHERE DATE(b.created_at) BETWEEN :f AND :t
    ORDER BY b.created_at DESC");
$promoUsage->execute([':f'=>$from,':t'=>$to]); $promoUsage=$promoUsage->fetchAll();

$promoSummary = $db->prepare("SELECT bp.promo_name, COUNT(*) as uses, SUM(bp.discount_amt) as total_disc
    FROM bill_promotions bp
    JOIN bills b ON bp.bill_id=b.id
    WHERE DATE(b.created_at) BETWEEN :f AND :t
    GROUP BY bp.promo_name ORDER BY total_disc DESC");
$promoSummary->execute([':f'=>$from,':t'=>$to]); $promoSummary=$promoSummary->fetchAll();

$totalPromoDisc = array_sum(array_column($promoUsage,'discount_amt'));

// Helper: export URL builder
function exportUrl($report,$from,$to,$format,$extra='All'){
    return "export/index.php?report=$report&from=$from&to=$to&format=$format&extra=".urlencode($extra);
}

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-title">Financial Reports</div>
</div>

<!-- Filter -->
<form method="GET" class="report-filter">
  <div class="form-group"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?=$from?>"></div>
  <div class="form-group"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?=$to?>"></div>
  <input type="hidden" name="rpt" value="<?=$rpt?>">
  <button type="submit" class="btn">Apply</button>
  <a href="?from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>&rpt=<?=$rpt?>" class="btn btn-sm btn-outline">Today</a>
  <a href="?from=<?=date('Y-m-01')?>&to=<?=date('Y-m-d')?>&rpt=<?=$rpt?>" class="btn btn-sm btn-outline">This Month</a>
  <a href="?from=<?=date('Y-01-01')?>&to=<?=date('Y-m-d')?>&rpt=<?=$rpt?>" class="btn btn-sm btn-outline">This Year</a>
</form>

<!-- Tabs -->
<div class="tab-bar">
  <?php $tabs=['pl'=>'📊 P&L','expenses'=>'💸 Expenses','cashflow'=>'💵 Cash Flow','debtors'=>'🏦 Debtors','inventory'=>'📦 Inventory','payroll'=>'👥 Payroll','attendance'=>'📅 Attendance','promotions'=>'🎉 Promotions'];
  foreach ($tabs as $key=>$label): ?>
    <a href="?from=<?=$from?>&to=<?=$to?>&rpt=<?=$key?>&month=<?=$month?>" class="tab-btn <?=$rpt===$key?'active':''?>"><?=$label?></a>
  <?php endforeach; ?>
</div>

<?php
// ── Export buttons bar ────────────────────────────────────────
$exportRpt = ($rpt==='payroll'||$rpt==='attendance') ? $rpt : $rpt;
$extraVal  = ($rpt==='payroll') ? $month : 'All';
?>
<div class="card mb-16" style="padding:12px 16px">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <span style="font-weight:600;font-size:13px">Export / Print:</span>
    <a href="<?=exportUrl($rpt,$from,$to,'print',$extraVal)?>" target="_blank" class="btn btn-sm btn-outline">🖨 Print / PDF</a>
    <a href="<?=exportUrl($rpt,$from,$to,'excel',$extraVal)?>" class="btn btn-sm btn-green">📊 Download Excel (CSV)</a>
    <?php if ($rpt==='pl'): ?>
    <a href="<?=exportUrl('expenses',$from,$to,'excel')?>" class="btn btn-sm btn-outline">📥 Export Expenses CSV</a>
    <?php endif; ?>
    <span class="fs-12 text-muted">| PDF: Open Print → Choose "Save as PDF"</span>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- P&L                                                        -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php if ($rpt === 'pl'): ?>
<div class="grid-2 mb-16">
  <div class="card">
    <div class="card-title text-green">Revenue</div>
    <?php foreach([['Gross Sales (Subtotal)',$salesData['sub']],['Service Charges',$salesData['svc']],['Less: Discounts',-$salesData['disc']],['Tax Collected',$salesData['tax']]] as [$lbl,$amt]): ?>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)">
      <span class="fs-13"><?=$lbl?></span><span class="mono <?=$amt<0?'text-red':'text-green'?>"><?=$amt<0?'- '.fmt(abs($amt)):fmt($amt)?></span>
    </div>
    <?php endforeach; ?>
    <div class="flex-between" style="padding:12px 0;font-weight:700;font-size:15px"><span>Net Revenue</span><span class="mono text-green"><?=fmt($salesData['t'])?></span></div>
  </div>
  <div class="card">
    <div class="card-title text-red">Expenses by Category</div>
    <?php foreach($expByCat as $cat): ?>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)">
      <span class="fs-13"><?=htmlspecialchars($cat['name'])?></span><span class="mono text-red"><?=fmt($cat['total'])?></span>
    </div>
    <?php endforeach; ?>
    <div class="flex-between" style="padding:12px 0;font-weight:700;font-size:15px"><span>Total Expenses</span><span class="mono text-red"><?=fmt($expTot)?></span></div>
  </div>
</div>
<div class="card">
  <div class="card-title">Profit & Loss Summary</div>
  <div class="grid-3">
    <div class="stat-card"><span class="stat-icon">📈</span><div class="stat-label">Total Revenue</div><div class="stat-value text-green"><?=fmt($salesData['t'])?></div></div>
    <div class="stat-card"><span class="stat-icon">📉</span><div class="stat-label">Total Expenses</div><div class="stat-value text-red"><?=fmt($expTot)?></div></div>
    <div class="stat-card"><span class="stat-icon">💎</span><div class="stat-label">Net Profit</div><div class="stat-value <?=$grossProfit>=0?'text-green':'text-red'?>"><?=fmt(abs($grossProfit))?></div><div class="stat-sub"><?=$margin?>% margin</div></div>
  </div>
</div>

<?php elseif ($rpt === 'expenses'): ?>
<div class="stats-grid mb-16">
  <div class="stat-card"><span class="stat-icon">💸</span><div class="stat-label">Total Expenses</div><div class="stat-value text-red"><?=fmt($expTot)?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">Transactions</div><div class="stat-value text-blue"><?=count($expList)?></div></div>
</div>
<div class="grid-2 mb-16">
  <div class="card">
    <div class="card-title">By Category</div>
    <?php foreach($expByCat as $cat): $pct=$expTot>0?round($cat['total']/$expTot*100,1):0; ?>
    <div style="margin-bottom:14px">
      <div class="flex-between mb-8 fs-13"><span><?=htmlspecialchars($cat['name'])?></span><span class="mono text-red"><?=fmt($cat['total'])?></span></div>
      <div class="progress"><div class="progress-bar" style="width:<?=$pct?>%;background:var(--red)"></div></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-title">By Payment Method</div>
    <?php $byM=[]; foreach($expList as $e) $byM[$e['payment_method']]=($byM[$e['payment_method']]??0)+$e['amount'];
    foreach($byM as $m=>$amt): ?>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)"><span class="fs-13"><?=$m?></span><span class="mono text-red"><?=fmt($amt)?></span></div>
    <?php endforeach; ?>
  </div>
</div>
<div class="card">
  <div class="card-title">Expense Transactions</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Method</th><th>Supplier</th><th class="text-right">Amount</th></tr></thead>
      <tbody>
      <?php foreach($expList as $e): ?>
        <tr><td class="mono fs-12"><?=date('d/m/Y',strtotime($e['expense_date']))?></td><td><span class="badge badge-muted"><?=htmlspecialchars($e['cat'])?></span></td><td><?=htmlspecialchars($e['description'])?></td><td><?=$e['payment_method']?></td><td class="text-muted fs-12"><?=htmlspecialchars($e['supplier']??'—')?></td><td class="text-right mono text-red fw-700"><?=fmt($e['amount'])?></td></tr>
      <?php endforeach; ?>
      <?php if(empty($expList)):?><tr><td colspan="6" class="text-center text-muted">No expenses for this period.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><tr><td colspan="5"><strong>Total</strong></td><td class="text-right mono text-red"><strong><?=fmt($expTot)?></strong></td></tr></tfoot>
    </table>
  </div>
</div>

<?php elseif ($rpt === 'cashflow'): ?>
<div class="stats-grid mb-16">
  <div class="stat-card"><span class="stat-icon">💵</span><div class="stat-label">Cash Inflow</div><div class="stat-value text-green"><?=fmt($cashIn)?></div></div>
  <div class="stat-card"><span class="stat-icon">💳</span><div class="stat-label">Card Inflow</div><div class="stat-value text-blue"><?=fmt($cardIn)?></div></div>
  <div class="stat-card"><span class="stat-icon">🛵</span><div class="stat-label">Platform</div><div class="stat-value" style="color:var(--purple)"><?=fmt($platIn)?></div></div>
  <div class="stat-card"><span class="stat-icon">💸</span><div class="stat-label">Cash Outflow</div><div class="stat-value text-red"><?=fmt($cashOut)?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Net Cash</div><div class="stat-value <?=($cashIn-$cashOut)>=0?'text-green':'text-red'?>"><?=fmt(abs($cashIn-$cashOut))?></div></div>
  <div class="stat-card"><span class="stat-icon">📊</span><div class="stat-label">Total Inflow</div><div class="stat-value text-green"><?=fmt($cashIn+$cardIn+$platIn)?></div></div>
</div>
<div class="card">
  <div class="card-title">Bank Transactions</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Bank</th><th>Type</th><th>Description</th><th>Reference</th><th class="text-right">Amount</th></tr></thead>
      <tbody>
      <?php foreach($bankTxn as $t): ?><tr><td class="mono fs-12"><?=date('d/m/Y',strtotime($t['txn_date']))?></td><td><?=htmlspecialchars($t['bank_name'])?></td><td><span class="badge <?=$t['type']==='deposit'?'badge-green':'badge-red'?>"><?=ucfirst($t['type'])?></span></td><td><?=htmlspecialchars($t['description']??'—')?></td><td class="mono text-muted fs-12"><?=htmlspecialchars($t['reference']??'—')?></td><td class="text-right mono <?=$t['type']==='deposit'?'text-green':'text-red'?> fw-700"><?=fmt($t['amount'])?></td></tr><?php endforeach; ?>
      <?php if(empty($bankTxn)):?><tr><td colspan="6" class="text-center text-muted">No bank transactions recorded.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($rpt === 'debtors'): ?>
<div class="stats-grid mb-16">
  <div class="stat-card"><span class="stat-icon">🏦</span><div class="stat-label">Total Outstanding</div><div class="stat-value text-red"><?=fmt($totalDebt)?></div></div>
  <div class="stat-card"><span class="stat-icon">👤</span><div class="stat-label">Active Debtors</div><div class="stat-value text-blue"><?=count($debtors)?></div></div>
</div>
<div class="card">
  <div class="card-title">Debtor Aging Report</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Debtor</th><th>Phone</th><th>Credit Limit</th><th class="text-right">Outstanding</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach($debtors as $d): ?><tr><td class="fw-700"><?=htmlspecialchars($d['name'])?></td><td class="text-muted fs-12"><?=htmlspecialchars($d['phone']??'—')?></td><td class="mono"><?=$d['credit_limit']>0?fmt($d['credit_limit']):'No limit'?></td><td class="text-right mono text-red fw-700"><?=fmt($d['outstanding'])?></td><td><span class="badge <?=$d['outstanding']>0?'badge-red':'badge-green'?>"><?=$d['outstanding']>0?'Outstanding':'Cleared'?></span></td></tr><?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="3"><strong>Total Outstanding</strong></td><td class="text-right mono text-red"><strong><?=fmt($totalDebt)?></strong></td><td></td></tr></tfoot>
    </table>
  </div>
</div>

<?php elseif ($rpt === 'inventory'): ?>
<?php $lowCount=count(array_filter($inv,fn($i)=>$i['qty']<=$i['min_qty'])); ?>
<div class="stats-grid mb-16">
  <div class="stat-card"><span class="stat-icon">📦</span><div class="stat-label">Total Items</div><div class="stat-value text-blue"><?=count($inv)?></div></div>
  <div class="stat-card"><span class="stat-icon">⚠</span><div class="stat-label">Low Stock</div><div class="stat-value text-red"><?=$lowCount?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Stock Value</div><div class="stat-value text-green"><?=fmt($invValue)?></div></div>
</div>
<div class="card">
  <div class="card-title">Inventory Report</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Item</th><th>Category</th><th>Unit</th><th class="text-right">Qty</th><th class="text-right">Min Qty</th><th class="text-right">Unit Cost</th><th class="text-right">Stock Value</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach($inv as $i): $low=$i['qty']<=$i['min_qty']; ?><tr style="<?=$low?'background:rgba(239,68,68,.04)':''?>"><td class="fw-700"><?=htmlspecialchars($i['name'])?></td><td class="text-muted fs-12"><?=htmlspecialchars($i['cat'])?></td><td><?=$i['unit']?></td><td class="text-right mono <?=$low?'text-red fw-700':'text-green'?>"><?=number_format($i['qty'],2)?></td><td class="text-right mono text-muted"><?=number_format($i['min_qty'],2)?></td><td class="text-right mono"><?=fmt($i['unit_cost'])?></td><td class="text-right mono text-accent fw-700"><?=fmt($i['qty']*$i['unit_cost'])?></td><td><span class="badge <?=$low?'badge-red':'badge-green'?>"><?=$low?'LOW STOCK':'OK'?></span></td></tr><?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="6"><strong>Total Stock Value</strong></td><td class="text-right mono text-green"><strong><?=fmt($invValue)?></strong></td><td></td></tr></tfoot>
    </table>
  </div>
</div>

<?php elseif ($rpt === 'payroll'): ?>
<div class="flex-between mb-16">
  <div style="display:flex;gap:8px;align-items:center">
    <input type="month" value="<?=$month?>" onchange="location.href='?rpt=payroll&from=<?=$from?>&to=<?=$to?>&month='+this.value" class="form-control" style="width:160px">
  </div>
</div>
<?php $totals=['gross'=>0,'epfe'=>0,'epfer'=>0,'etf'=>0,'net'=>0];
foreach($payroll as $p){$totals['gross']+=$p['gross_salary'];$totals['epfe']+=$p['epf_employee'];$totals['epfer']+=$p['epf_employer'];$totals['etf']+=$p['etf_employer'];$totals['net']+=$p['net_salary'];} ?>
<div class="stats-grid mb-16">
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Net Payable</div><div class="stat-value text-green"><?=fmt($totals['net'])?></div></div>
  <div class="stat-card"><span class="stat-icon">🏛</span><div class="stat-label">EPF Employer</div><div class="stat-value text-blue"><?=fmt($totals['epfer'])?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">ETF Employer</div><div class="stat-value" style="color:var(--purple)"><?=fmt($totals['etf'])?></div></div>
  <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-label">Employees</div><div class="stat-value text-accent"><?=count($payroll)?></div></div>
</div>
<div class="card">
  <div class="card-title">Payroll — <?=date('F Y',strtotime($monthStart))?></div>
  <?php if(empty($payroll)):?><div class="alert alert-info">No payroll generated for this month. <a href="payroll.php" class="text-accent">Go to Payroll →</a></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Employee</th><th>Position</th><th class="text-right">Basic</th><th class="text-right">Allow.</th><th class="text-right">OT</th><th class="text-right">Gross</th><th class="text-right">EPF(8%)</th><th class="text-right">EPF Er(12%)</th><th class="text-right">ETF(3%)</th><th class="text-right">Advance</th><th class="text-right">Net</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach($payroll as $p): ?><tr><td class="fw-700"><?=htmlspecialchars($p['emp_name'])?></td><td class="text-muted fs-12"><?=htmlspecialchars($p['position']??'—')?></td><td class="text-right mono"><?=fmt($p['basic_salary'])?></td><td class="text-right mono"><?=fmt($p['allowances'])?></td><td class="text-right mono"><?=fmt($p['overtime_pay'])?></td><td class="text-right mono fw-700"><?=fmt($p['gross_salary'])?></td><td class="text-right mono text-red"><?=fmt($p['epf_employee'])?></td><td class="text-right mono text-accent"><?=fmt($p['epf_employer'])?></td><td class="text-right mono" style="color:var(--purple)"><?=fmt($p['etf_employer'])?></td><td class="text-right mono text-red"><?=fmt($p['salary_advance'])?></td><td class="text-right mono text-green fw-700"><?=fmt($p['net_salary'])?></td><td><span class="badge <?=$p['status']==='paid'?'badge-green':'badge-muted'?>"><?=ucfirst($p['status'])?></span></td></tr><?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="5"><strong>TOTALS</strong></td><td class="text-right mono"><strong><?=fmt($totals['gross'])?></strong></td><td class="text-right mono text-red"><strong><?=fmt($totals['epfe'])?></strong></td><td class="text-right mono text-accent"><strong><?=fmt($totals['epfer'])?></strong></td><td class="text-right mono"><strong><?=fmt($totals['etf'])?></strong></td><td></td><td class="text-right mono text-green"><strong><?=fmt($totals['net'])?></strong></td><td></td></tr></tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($rpt === 'attendance'): ?>
<div class="flex-between mb-16" style="flex-wrap:wrap;gap:10px">
  <div style="display:flex;gap:8px;align-items:center">
    <label class="form-label">Period:</label>
    <a href="?rpt=attendance&from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">Today</a>
    <a href="?rpt=attendance&from=<?=date('Y-m-01')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">This Month</a>
  </div>
</div>
<?php
$attRows = $db->prepare("SELECT e.name,e.position,e.basic_salary,a.att_date,a.status,a.time_in,a.time_out,a.overtime_hours,a.notes FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.att_date BETWEEN ? AND ? AND e.name NOT LIKE '[DEL]%' ORDER BY a.att_date,e.name");
$attRows->execute([$from,$to]); $attRows=$attRows->fetchAll();
$totalOTHrs = array_sum(array_column($attRows,'overtime_hours'));
?>
<div class="stats-grid mb-16">
  <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-label">Records</div><div class="stat-value text-blue"><?=count($attRows)?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Present</div><div class="stat-value text-green"><?=count(array_filter($attRows,fn($a)=>$a['status']==='Present'))?></div></div>
  <div class="stat-card"><span class="stat-icon">❌</span><div class="stat-label">Absent</div><div class="stat-value text-red"><?=count(array_filter($attRows,fn($a)=>$a['status']==='Absent'))?></div></div>
  <div class="stat-card"><span class="stat-icon">⏰</span><div class="stat-label">Total OT Hours</div><div class="stat-value text-accent"><?=number_format($totalOTHrs,2)?></div></div>
</div>
<div class="card">
  <div class="card-title">Attendance Register</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Employee</th><th>Position</th><th>Date</th><th>Status</th><th>Time In</th><th>Time Out</th><th class="text-right">OT Hours</th><th class="text-right">OT Pay</th><th>Notes</th></tr></thead>
      <tbody>
      <?php foreach($attRows as $a):
        $otPay=round(($a['basic_salary']/26/9)*1.5*$a['overtime_hours'],2);
      ?><tr>
        <td class="fw-700"><?=htmlspecialchars($a['name'])?></td>
        <td class="text-muted fs-12"><?=htmlspecialchars($a['position']??'—')?></td>
        <td class="mono fs-12"><?=date('d/m/Y',strtotime($a['att_date']))?></td>
        <td><span class="badge <?=$a['status']==='Present'?'badge-green':($a['status']==='Absent'?'badge-red':'badge-accent')?>"><?=$a['status']?></span></td>
        <td class="mono fs-12"><?=$a['time_in']??'—'?></td>
        <td class="mono fs-12 <?=$a['time_out']>='17:00'?'text-accent':''?>"><?=$a['time_out']??'—'?></td>
        <td class="text-right mono text-accent fw-700"><?=number_format($a['overtime_hours'],2)?></td>
        <td class="text-right mono text-green"><?=$a['overtime_hours']>0?fmt($otPay):'—'?></td>
        <td class="text-muted fs-12"><?=htmlspecialchars($a['notes']??'')?></td>
      </tr><?php endforeach; ?>
      <?php if(empty($attRows)):?><tr><td colspan="9" class="text-center text-muted">No attendance records for this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ($rpt === 'promotions'): ?>
<div class="stats-grid mb-16">
  <div class="stat-card"><span class="stat-icon">🎉</span><div class="stat-label">Promotions Used</div><div class="stat-value text-blue"><?=count($promoUsage)?></div></div>
  <div class="stat-card"><span class="stat-icon">💸</span><div class="stat-label">Total Discount Given</div><div class="stat-value text-red"><?=fmt($totalPromoDisc)?></div></div>
  <div class="stat-card"><span class="stat-icon">🏷</span><div class="stat-label">Distinct Promotions</div><div class="stat-value text-accent"><?=count($promoSummary)?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">Manage Promotions</div><div class="stat-value"><a href="promotions.php" class="text-accent" style="font-size:13px">Go to Promotions →</a></div></div>
</div>

<div class="card mb-16">
  <div class="card-title">Promotion Usage Summary</div>
  <?php if (empty($promoSummary)): ?>
    <div class="alert alert-info">No promotions were applied to any bills in this period.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Promotion Name</th><th class="text-right">Times Used</th><th class="text-right">Total Discount Given</th></tr></thead>
      <tbody>
      <?php foreach($promoSummary as $ps): ?>
        <tr>
          <td class="fw-700">🎉 <?=htmlspecialchars($ps['promo_name'])?></td>
          <td class="text-right mono text-blue fw-700"><?=$ps['uses']?></td>
          <td class="text-right mono text-red fw-700">- <?=fmt($ps['total_disc'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td><strong>TOTAL</strong></td><td class="text-right mono"><strong><?=count($promoUsage)?></strong></td><td class="text-right mono text-red"><strong>- <?=fmt($totalPromoDisc)?></strong></td></tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-title">Promotion Usage — Bill by Bill</div>
  <?php if (empty($promoUsage)): ?>
    <div class="alert alert-info">No promotion usage records for this period.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Bill No</th><th>Date/Time</th><th>Order Type</th><th>Payment</th><th>Promotion</th><th class="text-right">Discount</th><th class="text-right">Bill Total</th></tr></thead>
      <tbody>
      <?php foreach($promoUsage as $pu): ?>
        <tr>
          <td class="mono text-accent"><?=htmlspecialchars($pu['bill_no'])?></td>
          <td class="fs-12 text-muted"><?=date('d/m/Y H:i',strtotime($pu['created_at']))?></td>
          <td><?=htmlspecialchars($pu['order_type'])?></td>
          <td><?=htmlspecialchars($pu['payment_method'])?></td>
          <td class="fw-700">🎉 <?=htmlspecialchars($pu['promo_name'])?></td>
          <td class="text-right mono text-red">- <?=fmt($pu['discount_amt'])?></td>
          <td class="text-right mono text-green fw-700"><?=fmt($pu['bill_total'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>
