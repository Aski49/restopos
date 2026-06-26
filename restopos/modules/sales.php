<?php
require_once '../includes/config.php';
requireAccess('sales');
$db = getDB();
$pageTitle = 'Sales Reports'; $activePage = 'sales';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$type = $_GET['type'] ?? 'All';

$where = "WHERE DATE(b.created_at) BETWEEN :from AND :to AND b.status='settled'";
$params = [':from'=>$from, ':to'=>$to];
if ($type !== 'All') { $where .= " AND b.order_type=:otype"; $params[':otype'] = $type; }

// Summary
$summary = $db->prepare("SELECT COUNT(*) as bills, COALESCE(SUM(subtotal),0) as subtotal, COALESCE(SUM(service_charge),0) as svc, COALESCE(SUM(discount_amt),0) as disc, COALESCE(SUM(tax_amt),0) as tax, COALESCE(SUM(total),0) as total FROM bills b $where");
$summary->execute($params); $summary = $summary->fetch();

// By order type
$byType = $db->prepare("SELECT order_type, COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM bills b $where GROUP BY order_type ORDER BY total DESC");
$byType->execute($params); $byType = $byType->fetchAll();

// By payment method
$byPay = $db->prepare("SELECT payment_method, COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM bills b $where GROUP BY payment_method ORDER BY total DESC");
$byPay->execute($params); $byPay = $byPay->fetchAll();

// Daily sales (for chart)
$daily = $db->prepare("SELECT DATE(created_at) as dy, COALESCE(SUM(total),0) as total, COUNT(*) as cnt FROM bills b $where GROUP BY DATE(created_at) ORDER BY dy ASC");
$daily->execute($params); $daily = $daily->fetchAll();

// Top items
$topItems = $db->prepare("SELECT bi.item_name, SUM(bi.qty) as qty_sold, SUM(bi.line_total) as revenue FROM bill_items bi JOIN bills b ON bi.bill_id=b.id $where GROUP BY bi.item_name ORDER BY qty_sold DESC LIMIT 10");
$topItems->execute($params); $topItems = $topItems->fetchAll();

// All bills
$bills = $db->prepare("SELECT b.*, u.name as cashier FROM bills b LEFT JOIN users u ON b.created_by=u.id $where ORDER BY b.created_at DESC LIMIT 200");
$bills->execute($params); $bills = $bills->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-title">Sales Reports</div>
  <div style="display:flex;gap:10px">
    <a href="?from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">Today</a>
    <a href="?from=<?=date('Y-m-d',strtotime('monday this week'))?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">This Week</a>
    <a href="?from=<?=date('Y-m-01')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">This Month</a>
    <a href="?from=<?=date('Y-01-01')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">This Year</a>
  </div>
</div>

<!-- Filter Bar -->
<form method="GET" class="report-filter">
  <div class="form-group">
    <label class="form-label">From</label>
    <input type="date" name="from" class="form-control" value="<?= $from ?>">
  </div>
  <div class="form-group">
    <label class="form-label">To</label>
    <input type="date" name="to" class="form-control" value="<?= $to ?>">
  </div>
  <div class="form-group">
    <label class="form-label">Order Type</label>
    <select name="type" class="form-control">
      <option value="All">All Types</option>
      <?php foreach (['Dine-In','Takeaway','Uber Eats','PickMe','Delivery'] as $t): ?>
        <option <?= $type===$t?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn">Generate Report</button>
  <a href="export/index.php?report=sales&from=<?=$from?>&to=<?=$to?>&extra=<?=$type?>&format=excel" class="btn btn-outline">📊 Excel</a>
    <a href="export/index.php?report=sales&from=<?=$from?>&to=<?=$to?>&extra=<?=$type?>&format=print" target="_blank" class="btn btn-outline">🖨 PDF/Print</a>
</form>

<!-- Summary Stats -->
<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🧾</span><div class="stat-label">Total Bills</div><div class="stat-value text-blue"><?= number_format($summary['bills']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Gross Sales</div><div class="stat-value text-green"><?= fmt($summary['subtotal']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">🏷</span><div class="stat-label">Discounts Given</div><div class="stat-value text-red"><?= fmt($summary['disc']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">🧾</span><div class="stat-label">Service Charge</div><div class="stat-value text-accent"><?= fmt($summary['svc']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">Tax Collected</div><div class="stat-value" style="color:var(--purple)"><?= fmt($summary['tax']) ?></div></div>
  <div class="stat-card"><span class="stat-icon">📈</span><div class="stat-label">Net Revenue</div><div class="stat-value text-green"><?= fmt($summary['total']) ?></div></div>
</div>

<div class="grid-2 mb-16">
  <!-- Sales by order type -->
  <div class="card">
    <div class="card-title">Sales by Order Type</div>
    <?php $grandTotal = array_sum(array_column($byType,'total')); ?>
    <?php foreach ($byType as $row): $pct = $grandTotal>0?round($row['total']/$grandTotal*100,1):0; ?>
    <div style="margin-bottom:14px">
      <div class="flex-between mb-8 fs-13"><span><?= $row['order_type'] ?> — <?= $row['cnt'] ?> bills</span><span class="mono text-green"><?= fmt($row['total']) ?></span></div>
      <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%;background:var(--green)"></div></div>
      <div class="fs-12 text-muted" style="margin-top:4px"><?= $pct ?>% of total</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- By payment method -->
  <div class="card">
    <div class="card-title">Payment Breakdown</div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Method</th><th>Bills</th><th class="text-right">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($byPay as $row): ?>
          <tr>
            <td><?= $row['payment_method'] ?></td>
            <td><?= $row['cnt'] ?></td>
            <td class="text-right mono text-accent"><?= fmt($row['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="2"><strong>Total</strong></td><td class="text-right mono text-green"><strong><?= fmt($summary['total']) ?></strong></td></tr></tfoot>
      </table>
    </div>
  </div>
</div>

<!-- Daily Chart -->
<?php if (!empty($daily)): ?>
<div class="card mb-16">
  <div class="card-title">Daily Sales — <?= $from ?> to <?= $to ?></div>
  <?php $maxD = max(array_column($daily,'total'))?:1; ?>
  <div class="bar-chart" style="height:120px;align-items:flex-end;gap:4px">
    <?php foreach ($daily as $d): ?>
      <div class="bar-col" title="<?= $d['dy'] ?>: <?= fmt($d['total']) ?>">
        <div class="bar" style="height:<?= round($d['total']/$maxD*100) ?>px;background:var(--accent);width:100%"></div>
        <div class="bar-label"><?= date('d',strtotime($d['dy'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Top Items -->
<div class="grid-2 mb-16">
  <div class="card">
    <div class="card-title">Top Selling Items</div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Item</th><th class="text-right">Qty</th><th class="text-right">Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($topItems as $i=>$item): ?>
          <tr>
            <td class="text-muted"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($item['item_name']) ?></td>
            <td class="text-right mono"><?= number_format($item['qty_sold']) ?></td>
            <td class="text-right mono text-green"><?= fmt($item['revenue']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Avg per bill -->
  <div class="card">
    <div class="card-title">Summary Statistics</div>
    <?php
    $avgBill = $summary['bills']>0 ? $summary['total']/$summary['bills'] : 0;
    $days = max(1, (strtotime($to)-strtotime($from))/86400+1);
    $avgDaily = $summary['total']/$days;
    ?>
    <div class="flex-between" style="padding:12px 0;border-bottom:1px solid var(--border)"><span class="fs-13">Avg Bill Value</span><span class="mono text-accent"><?= fmt($avgBill) ?></span></div>
    <div class="flex-between" style="padding:12px 0;border-bottom:1px solid var(--border)"><span class="fs-13">Avg Daily Revenue</span><span class="mono text-green"><?= fmt($avgDaily) ?></span></div>
    <div class="flex-between" style="padding:12px 0;border-bottom:1px solid var(--border)"><span class="fs-13">Period (days)</span><span class="mono"><?= (int)$days ?></span></div>
    <div class="flex-between" style="padding:12px 0"><span class="fs-13">Bills Per Day (avg)</span><span class="mono"><?= round($summary['bills']/$days,1) ?></span></div>
  </div>
</div>

<!-- All Bills Table -->
<div class="card">
  <div class="flex-between mb-16">
    <div class="card-title" style="margin-bottom:0">Bill Details (<?= count($bills) ?> records)</div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Bill No</th><th>Date / Time</th><th>Type</th><th>Table</th><th>Method</th><th class="text-right">Subtotal</th><th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right">Total</th><th>Cashier</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($bills as $b): ?>
        <tr>
          <td><a href="print_bill.php?id=<?= $b['id'] ?>" target="_blank" class="text-accent mono"><?= htmlspecialchars($b['bill_no']) ?></a></td>
          <td class="fs-12 text-muted"><?= date('d/m/Y H:i',strtotime($b['created_at'])) ?></td>
          <td><?= $b['order_type'] ?></td>
          <td><?= $b['table_no'] ?? '—' ?></td>
          <td><?= $b['payment_method'] ?></td>
          <td class="text-right mono"><?= fmt($b['subtotal']) ?></td>
          <td class="text-right mono text-red"><?= $b['discount_amt']>0?fmt($b['discount_amt']):'—' ?></td>
          <td class="text-right mono"><?= fmt($b['tax_amt']) ?></td>
          <td class="text-right mono text-green fw-700"><?= fmt($b['total']) ?></td>
          <td class="fs-12 text-muted"><?= htmlspecialchars($b['cashier']??'—') ?></td>
          <td><span class="badge <?= $b['status']==='settled'?'badge-green':'badge-red' ?>"><?= ucfirst($b['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5"><strong>TOTALS</strong></td>
          <td class="text-right mono"><strong><?= fmt($summary['subtotal']) ?></strong></td>
          <td class="text-right mono text-red"><strong><?= fmt($summary['disc']) ?></strong></td>
          <td class="text-right mono"><strong><?= fmt($summary['tax']) ?></strong></td>
          <td class="text-right mono text-green"><strong><?= fmt($summary['total']) ?></strong></td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
