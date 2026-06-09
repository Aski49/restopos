<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'Dashboard'; $activePage = 'dashboard';

// Today stats
$todayBills = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM bills WHERE DATE(created_at)=CURDATE() AND status='settled'")->fetch();
$todayCash  = $db->query("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at)=CURDATE() AND status='settled' AND payment_method='Cash'")->fetch();
$todayCard  = $db->query("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at)=CURDATE() AND status='settled' AND payment_method='Card'")->fetch();
$todayPlat  = $db->query("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at)=CURDATE() AND status='settled' AND payment_method IN('Uber Eats','PickMe')")->fetch();
$todayExp   = $db->query("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE expense_date=CURDATE()")->fetch();

// Monthly
$monthBills = $db->query("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status='settled'")->fetch();
$monthExp   = $db->query("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE MONTH(expense_date)=MONTH(NOW()) AND YEAR(expense_date)=YEAR(NOW())")->fetch();

// Debtors
$debtorTotal = $db->query("SELECT COALESCE(SUM(outstanding),0) as t FROM debtors")->fetch();

// Low stock
$lowStock = $db->query("SELECT * FROM inventory_items WHERE qty <= min_qty ORDER BY qty ASC LIMIT 8")->fetchAll();

// Recent bills
$recentBills = $db->query("SELECT b.*, u.name as cashier FROM bills b LEFT JOIN users u ON b.created_by=u.id ORDER BY b.created_at DESC LIMIT 8")->fetchAll();

$todayProfit = $todayBills['total'] - $todayExp['t'];
$monthProfit = $monthBills['t'] - $monthExp['t'];

include '../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-sub"><?= date('l, d F Y') ?> — Real-time Overview</div>
  </div>
</div>

<!-- TODAY STATS -->
<div class="stats-grid">
  <div class="stat-card"><span class="stat-icon">🧾</span><div class="stat-label">Today's Bills</div><div class="stat-value text-blue"><?= $todayBills['cnt'] ?></div><div class="stat-sub">Settled orders</div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Today's Sales</div><div class="stat-value text-green"><?= fmt($todayBills['total']) ?></div><div class="stat-sub">Gross revenue</div></div>
  <div class="stat-card"><span class="stat-icon">💵</span><div class="stat-label">Cash Collected</div><div class="stat-value text-accent"><?= fmt($todayCash['t']) ?></div><div class="stat-sub">Today</div></div>
  <div class="stat-card"><span class="stat-icon">💳</span><div class="stat-label">Card Payments</div><div class="stat-value text-blue"><?= fmt($todayCard['t']) ?></div><div class="stat-sub">Today</div></div>
  <div class="stat-card"><span class="stat-icon">🛵</span><div class="stat-label">Platform Sales</div><div class="stat-value" style="color:var(--purple)"><?= fmt($todayPlat['t']) ?></div><div class="stat-sub">Uber Eats + PickMe</div></div>
  <div class="stat-card"><span class="stat-icon">💸</span><div class="stat-label">Today's Expenses</div><div class="stat-value text-red"><?= fmt($todayExp['t']) ?></div><div class="stat-sub">All categories</div></div>
  <div class="stat-card"><span class="stat-icon">📈</span><div class="stat-label">Today's Profit</div><div class="stat-value <?= $todayProfit>=0?'text-green':'text-red' ?>"><?= fmt(abs($todayProfit)) ?></div><div class="stat-sub">Est. gross profit</div></div>
  <div class="stat-card"><span class="stat-icon">🏦</span><div class="stat-label">Debtors Total</div><div class="stat-value text-red"><?= fmt($debtorTotal['t']) ?></div><div class="stat-sub">Outstanding</div></div>
</div>

<!-- MONTHLY -->
<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-label">Monthly Revenue</div><div class="stat-value text-green"><?= fmt($monthBills['t']) ?></div><div class="stat-sub"><?= date('F Y') ?></div></div>
  <div class="stat-card"><span class="stat-icon">📉</span><div class="stat-label">Monthly Expenses</div><div class="stat-value text-red"><?= fmt($monthExp['t']) ?></div><div class="stat-sub"><?= date('F Y') ?></div></div>
  <div class="stat-card"><span class="stat-icon">💎</span><div class="stat-label">Monthly Profit</div><div class="stat-value <?= $monthProfit>=0?'text-green':'text-red' ?>"><?= fmt(abs($monthProfit)) ?></div><div class="stat-sub"><?= $monthBills['t']>0?round($monthProfit/$monthBills['t']*100,1).'% margin':'—' ?></div></div>
  <div class="stat-card"><span class="stat-icon">📦</span><div class="stat-label">Low Stock Items</div><div class="stat-value text-red"><?= count($lowStock) ?></div><div class="stat-sub">Below minimum</div></div>
</div>

<div class="grid-2">
  <!-- Low Stock -->
  <div class="card">
    <div class="card-title" style="color:var(--red)">⚠ Low Stock Alerts</div>
    <?php if (empty($lowStock)): ?>
      <div class="text-muted fs-13">All inventory items are adequately stocked.</div>
    <?php else: foreach ($lowStock as $i): ?>
      <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)">
        <div>
          <div style="font-weight:500;font-size:14px"><?= htmlspecialchars($i['name']) ?></div>
          <div class="fs-12 text-muted">Min: <?= $i['min_qty'] ?> <?= $i['unit'] ?></div>
        </div>
        <span class="badge badge-red"><?= $i['qty'] ?> <?= $i['unit'] ?></span>
      </div>
    <?php endforeach; endif; ?>
    <a href="inventory.php" class="btn btn-sm btn-outline" style="margin-top:14px">View Inventory →</a>
  </div>

  <!-- Recent Bills -->
  <div class="card">
    <div class="card-title">Recent Bills</div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Bill No</th><th>Type</th><th>Total</th><th>Method</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recentBills as $b): ?>
          <tr>
            <td class="mono text-accent"><?= htmlspecialchars($b['bill_no']) ?></td>
            <td><?= $b['order_type'] ?></td>
            <td class="mono text-green"><?= fmt($b['total']) ?></td>
            <td><?= $b['payment_method'] ?></td>
            <td><span class="badge <?= $b['status']==='settled'?'badge-green':($b['status']==='voided'?'badge-red':'badge-accent') ?>"><?= ucfirst($b['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <a href="sales.php" class="btn btn-sm btn-outline" style="margin-top:14px">View All Sales →</a>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
