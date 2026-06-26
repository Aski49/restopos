<?php
require_once '../includes/config.php';
requireAccess('inventory');
$db = getDB();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$usageStmt = $db->prepare("SELECT su.*, ii.name as item_name, ii.unit as item_unit, ii.unit_cost
    FROM stock_usage su
    JOIN inventory_items ii ON su.item_id=ii.id
    WHERE su.usage_date BETWEEN ? AND ?
    ORDER BY su.usage_date DESC, su.id DESC");
$usageStmt->execute([$from, $to]);
$usageRows = $usageStmt->fetchAll();

$usageByItem = [];
foreach ($usageRows as $u) {
    $key = $u['item_id'];
    if (!isset($usageByItem[$key])) {
        $usageByItem[$key] = ['name'=>$u['item_name'],'unit'=>$u['item_unit'],'qty'=>0,'cost'=>0,'count'=>0];
    }
    $usageByItem[$key]['qty']  += $u['qty'];
    $usageByItem[$key]['cost'] += $u['qty'] * $u['unit_cost'];
    $usageByItem[$key]['count']++;
}
uasort($usageByItem, fn($a,$b) => $b['qty'] <=> $a['qty']);
$totalUsageCost = array_sum(array_column($usageByItem, 'cost'));

$bizName  = getSetting('business_name', 'RestoPOS');
$bizAddr  = getSetting('address', '');
$bizPhone = getSetting('phone', '');

$periodLabel = ($from === $to)
    ? date('l, d F Y', strtotime($from))
    : date('d M Y', strtotime($from)) . '  →  ' . date('d M Y', strtotime($to));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Inventory Usage Report — <?= htmlspecialchars($periodLabel) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Arial,sans-serif; font-size:12.5px; background:#fff; color:#000; padding:24px; }
  .header { text-align:center; border-bottom:2px solid #000; padding-bottom:12px; margin-bottom:16px; }
  .biz-name { font-size:19px; font-weight:bold; }
  .title { font-size:15px; font-weight:bold; margin:8px 0 2px; text-transform:uppercase; letter-spacing:1px; }
  .period-label { font-size:12.5px; color:#333; font-weight:bold; margin-top:4px; }
  .meta-row { font-size:11px; color:#666; margin-top:6px; }
  .summary { display:flex; justify-content:center; gap:30px; margin:14px 0; }
  .summary-box { text-align:center; }
  .summary-num { font-size:20px; font-weight:bold; }
  .summary-lbl { font-size:10px; color:#666; text-transform:uppercase; letter-spacing:.5px; }
  .section-title { font-size:13px; font-weight:bold; margin:18px 0 6px; padding-bottom:4px; border-bottom:1px solid #ccc; }
  table { width:100%; border-collapse:collapse; margin-top:4px; }
  th { background:#f0f0f0; text-align:left; padding:7px 8px; font-size:11px; text-transform:uppercase; border:1px solid #ccc; }
  td { padding:6px 8px; border:1px solid #ddd; font-size:11.5px; }
  .text-right { text-align:right; }
  .no-data { text-align:center; padding:30px; color:#888; }
  .footer { margin-top:24px; text-align:center; font-size:10.5px; color:#888; border-top:1px solid #ccc; padding-top:10px; }
  .no-print { margin-top:24px; text-align:center; }
  @media print { .no-print { display:none; } body{ padding:10px; } }
</style>
</head>
<body>

<div class="header">
  <div class="biz-name"><?= htmlspecialchars($bizName) ?></div>
  <div style="font-size:11px;color:#555"><?= htmlspecialchars($bizAddr) ?><?= $bizPhone ? ' | '.htmlspecialchars($bizPhone) : '' ?></div>
  <div class="title">Inventory Usage Report</div>
  <div class="period-label">Period: <?= htmlspecialchars($periodLabel) ?></div>
  <div class="meta-row">Generated: <?= date('d/m/Y H:i') ?></div>
</div>

<div class="summary">
  <div class="summary-box"><div class="summary-num"><?= count($usageRows) ?></div><div class="summary-lbl">Usage Events</div></div>
  <div class="summary-box"><div class="summary-num"><?= count($usageByItem) ?></div><div class="summary-lbl">Items Used</div></div>
  <div class="summary-box"><div class="summary-num">Rs. <?= number_format($totalUsageCost,2) ?></div><div class="summary-lbl">Total Cost</div></div>
</div>

<?php if (empty($usageRows)): ?>
  <div class="no-data">No inventory usage recorded for this period.</div>
<?php else: ?>

<div class="section-title">Summary by Item</div>
<table>
  <tr><th>Item</th><th class="text-right">Total Qty Used</th><th class="text-right">Times Used</th><th class="text-right">Est. Cost (Rs.)</th></tr>
  <?php foreach ($usageByItem as $u): ?>
  <tr>
    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
    <td class="text-right"><?= number_format($u['qty'],3) ?> <?= htmlspecialchars($u['unit']) ?></td>
    <td class="text-right"><?= $u['count'] ?></td>
    <td class="text-right"><?= number_format($u['cost'],2) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr style="font-weight:bold;background:#f7f7f7">
    <td colspan="3">TOTAL</td>
    <td class="text-right">Rs. <?= number_format($totalUsageCost,2) ?></td>
  </tr>
</table>

<div class="section-title">Usage — Event by Event</div>
<table>
  <tr><th>Date</th><th>Item</th><th class="text-right">Qty Used</th><th>Source</th><th>Detail</th></tr>
  <?php foreach ($usageRows as $u): ?>
  <tr>
    <td><?= date('d/m/Y', strtotime($u['usage_date'])) ?></td>
    <td><?= htmlspecialchars($u['item_name']) ?></td>
    <td class="text-right"><?= number_format($u['qty'],3) ?> <?= htmlspecialchars($u['item_unit']) ?></td>
    <td><?= $u['source']==='bill' ? 'Sale' : 'Manual' ?></td>
    <td><?= htmlspecialchars($u['menu_item_name'] ?: ($u['notes'] ?: '—')) ?></td>
  </tr>
  <?php endforeach; ?>
</table>

<?php endif; ?>

<div class="footer">RestoPOS Sri Lanka — Inventory Usage Report</div>

<div class="no-print">
  <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;font-size:14px">🖨 Print / Save as PDF</button>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
