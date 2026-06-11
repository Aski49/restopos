<?php
require_once '../includes/config.php';
requireAccess('pos');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$bill = $db->prepare("SELECT b.*, u.name as cashier FROM bills b LEFT JOIN users u ON b.created_by=u.id WHERE b.id=?");
$bill->execute([$id]); $bill = $bill->fetch();
if (!$bill) { echo "Bill not found."; exit; }
$items = $db->prepare("SELECT * FROM bill_items WHERE bill_id=?");
$items->execute([$id]); $items=$items->fetchAll();

// Fetch promotions applied
$promoApplied = [];
try {
    $ps = $db->prepare("SELECT * FROM bill_promotions WHERE bill_id=?");
    $ps->execute([$id]); $promoApplied=$ps->fetchAll();
} catch (Exception $e) {}

$bizName  = getSetting('business_name','RestoPOS');
$bizAddr  = getSetting('address','');
$bizPhone = getSetting('phone','');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt — <?= $bill['bill_no'] ?></title>
<style>
body{font-family:monospace;font-size:13px;background:#fff;color:#000;padding:10px;max-width:300px;margin:auto}
.center{text-align:center}.divider{border-top:1px dashed #000;margin:8px 0}
.row{display:flex;justify-content:space-between}
.bold{font-weight:bold}.big{font-size:16px;font-weight:bold}
.promo-line{color:#006600;font-weight:bold}
@media print{body{max-width:80mm}.no-print{display:none}}
</style>
</head>
<body>
<div class="center bold" style="font-size:16px"><?= htmlspecialchars($bizName) ?></div>
<div class="center"><?= htmlspecialchars($bizAddr) ?></div>
<div class="center"><?= htmlspecialchars($bizPhone) ?></div>
<div class="divider"></div>
<div class="row"><span>Bill No:</span><span class="bold"><?= $bill['bill_no'] ?></span></div>
<div class="row"><span>Date:</span><span><?= date('d/m/Y H:i',strtotime($bill['created_at'])) ?></span></div>
<div class="row"><span>Type:</span><span><?= $bill['order_type'] ?><?= $bill['table_no']?' ('.$bill['table_no'].')':'' ?></span></div>
<div class="row"><span>Cashier:</span><span><?= htmlspecialchars($bill['cashier']??'N/A') ?></span></div>
<div class="divider"></div>
<table style="width:100%;font-size:12px">
<tr><th style="text-align:left">Item</th><th>Qty</th><th style="text-align:right">Total</th></tr>
<?php foreach ($items as $it): ?>
<tr>
  <td><?= htmlspecialchars($it['item_name']) ?><br><span style="font-size:11px;color:#555">Rs.<?= number_format($it['price'],2) ?> x <?= $it['qty'] ?></span></td>
  <td style="text-align:center"><?= $it['qty'] ?></td>
  <td style="text-align:right">Rs. <?= number_format($it['line_total'],2) ?></td>
</tr>
<?php endforeach; ?>
</table>
<div class="divider"></div>
<div class="row"><span>Subtotal</span><span>Rs. <?= number_format($bill['subtotal'],2) ?></span></div>
<?php if ($bill['service_charge']>0): ?><div class="row"><span>Service Charge</span><span>Rs. <?= number_format($bill['service_charge'],2) ?></span></div><?php endif; ?>
<?php if ($bill['discount_amt']>0): ?><div class="row"><span>Discount (<?= $bill['discount_pct'] ?>%)</span><span>- Rs. <?= number_format($bill['discount_amt'],2) ?></span></div><?php endif; ?>

<!-- PROMOTION LINES ON RECEIPT -->
<?php foreach ($promoApplied as $p): ?>
<div class="row promo-line">
  <span>🎉 <?= htmlspecialchars($p['promo_name']) ?></span>
  <span>- Rs. <?= number_format($p['discount_amt'],2) ?></span>
</div>
<?php endforeach; ?>
<?php if (!empty($promoApplied)): ?>
<div class="center" style="font-size:11px;color:#006600;margin:2px 0">*** Promotion Applied! ***</div>
<?php endif; ?>

<?php if ($bill['tax_amt']>0): ?><div class="row"><span>Tax</span><span>Rs. <?= number_format($bill['tax_amt'],2) ?></span></div><?php endif; ?>
<div class="divider"></div>
<div class="row big"><span>TOTAL</span><span>Rs. <?= number_format($bill['total'],2) ?></span></div>
<div class="row"><span>Paid By</span><span><?= $bill['payment_method'] ?></span></div>
<?php if ($bill['cash_given']>0): ?>
<div class="row"><span>Cash Given</span><span>Rs. <?= number_format($bill['cash_given'],2) ?></span></div>
<div class="row"><span>Change</span><span>Rs. <?= number_format($bill['change_amt'],2) ?></span></div>
<?php endif; ?>
<div class="divider"></div>
<div class="center" style="margin-top:8px">Thank You! Come Again 🙏</div>
<div class="center" style="font-size:11px;margin-top:4px">Powered by RestoPOS Sri Lanka</div>
<br>
<div class="center no-print">
  <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;font-size:14px">🖨 Print</button>
  <button onclick="window.close()" style="padding:8px 20px;cursor:pointer;font-size:14px;margin-left:8px">Close</button>
</div>
<script>window.onload=()=>window.print();</script>
</body>
</html>
