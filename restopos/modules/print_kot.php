<?php
require_once '../includes/config.php';
requireAccess('kds');
$db = getDB();

$billId = (int)($_GET['bill_id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM bills WHERE id=?");
$stmt->execute([$billId]);
$bill = $stmt->fetch();
if (!$bill) { echo "Order not found."; exit; }

$itemsStmt = $db->prepare("SELECT * FROM bill_items WHERE bill_id=? ORDER BY id ASC");
$itemsStmt->execute([$billId]);
$items = $itemsStmt->fetchAll();

$bizName = getSetting('business_name', 'RestoPOS');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>KOT — <?= htmlspecialchars($bill['bill_no']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Courier New',monospace; font-size:14px; background:#fff; color:#000; padding:14px; width:280px; margin:0 auto; }
  .center { text-align:center; }
  .big { font-size:20px; font-weight:bold; }
  .dash { border-top:1px dashed #000; margin:8px 0; }
  .row { display:flex; justify-content:space-between; padding:2px 0; }
  .item-line { padding:6px 0; border-bottom:1px dotted #999; }
  .item-name { font-weight:bold; font-size:15px; }
  .item-qty { font-size:18px; font-weight:bold; }
  .meta { font-size:12px; color:#333; }
  .no-print { margin-top:20px; text-align:center; }
  @media print { .no-print { display:none; } body{ width:100%; } }
</style>
</head>
<body>
  <div class="center big">🔥 KITCHEN ORDER</div>
  <div class="center meta"><?= htmlspecialchars($bizName) ?></div>
  <div class="dash"></div>
  <div class="row"><strong>Bill No</strong><span><?= htmlspecialchars($bill['bill_no']) ?></span></div>
  <div class="row"><strong>Type</strong><span><?= htmlspecialchars($bill['order_type']) ?><?= $bill['table_no'] ? ' ('.htmlspecialchars($bill['table_no']).')' : '' ?></span></div>
  <div class="row"><strong>Time</strong><span><?= date('h:i A', strtotime($bill['created_at'])) ?></span></div>
  <div class="dash"></div>
  <?php foreach ($items as $it): ?>
    <div class="item-line">
      <span class="item-qty"><?= (int)$it['qty'] ?>×</span> <span class="item-name"><?= htmlspecialchars($it['item_name']) ?></span>
    </div>
  <?php endforeach; ?>
  <div class="dash"></div>
  <div class="center meta">Printed: <?= date('d/m/Y h:i A') ?></div>

  <div class="no-print">
    <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;font-size:14px">🖨 Print Order</button>
  </div>

<script>window.onload = () => window.print();</script>
</body>
</html>
