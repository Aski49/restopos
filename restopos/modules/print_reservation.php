<?php
require_once '../includes/config.php';
requireAccess('reservations');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT r.*, u.name as created_by_name FROM reservations r LEFT JOIN users u ON r.created_by=u.id WHERE r.id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { echo "Reservation not found."; exit; }

$bizName  = getSetting('business_name','RestoPOS');
$bizAddr  = getSetting('address','');
$bizPhone = getSetting('phone','');

$statusColors = [
    'Confirmed' => '#0a8a5c',
    'Pending'   => '#b8860b',
    'Cancelled' => '#c0392b',
    'Completed' => '#2563eb',
];
$stColor = $statusColors[$r['status']] ?? '#555';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Reservation — <?= htmlspecialchars($r['customer_name']) ?> — <?= date('d/m/Y', strtotime($r['res_date'])) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Arial,sans-serif; font-size:13px; background:#fff; color:#000; padding:30px; }
  .header { text-align:center; border-bottom:2px solid #000; padding-bottom:12px; margin-bottom:18px; }
  .biz-name { font-size:20px; font-weight:bold; }
  .title { font-size:16px; font-weight:bold; margin:10px 0 4px; text-transform:uppercase; letter-spacing:1px; }
  .subtitle { font-size:12px; color:#555; }
  .status-badge { display:inline-block; margin-top:10px; padding:5px 16px; border-radius:14px; font-weight:bold; font-size:12px; color:#fff; background:<?= $stColor ?>; }
  .section { margin-bottom:16px; }
  .section-title { font-weight:bold; font-size:12px; text-transform:uppercase; letter-spacing:.5px; background:#f0f0f0; padding:6px 10px; border:1px solid #ccc; }
  table { width:100%; border-collapse:collapse; }
  td, th { padding:8px 10px; border:1px solid #ccc; font-size:13px; }
  th { background:#f5f5f5; font-weight:bold; text-align:left; width:35%; }
  .notes-box { border:1px solid #ccc; padding:12px; min-height:50px; font-size:12.5px; background:#fafafa; }
  .footer { margin-top:40px; display:flex; justify-content:space-between; font-size:12px; }
  .sig-line { border-top:1px solid #000; padding-top:6px; margin-top:40px; width:180px; text-align:center; }
  .ref { text-align:right; font-size:11px; color:#777; margin-top:4px; }
  @media print { .no-print { display:none; } }
</style>
</head>
<body>
<div class="header">
  <div class="biz-name"><?= htmlspecialchars($bizName) ?></div>
  <div style="font-size:12px;color:#555"><?= htmlspecialchars($bizAddr) ?> <?= $bizPhone ? '| '.htmlspecialchars($bizPhone) : '' ?></div>
  <div class="title">Reservation Confirmation</div>
  <div class="subtitle">Generated: <?= date('d/m/Y H:i') ?></div>
  <div class="status-badge"><?= htmlspecialchars($r['status']) ?></div>
</div>

<div class="ref">Reservation #<?= str_pad($r['id'],5,'0',STR_PAD_LEFT) ?></div>

<div class="section">
  <div class="section-title">Reservation Details</div>
  <table>
    <tr><th>Customer Name</th><td><?= htmlspecialchars($r['customer_name']) ?></td></tr>
    <tr><th>Contact Number</th><td><?= htmlspecialchars($r['contact']) ?></td></tr>
    <tr><th>Date</th><td><?= date('l, d F Y', strtotime($r['res_date'])) ?></td></tr>
    <tr>
      <th>Time</th>
      <td>
        <strong><?= date('h:i A', strtotime($r['res_time'])) ?></strong>
        <?php if (!empty($r['res_end_time'])): ?>
          &nbsp;—&nbsp;<strong><?= date('h:i A', strtotime($r['res_end_time'])) ?></strong>
          <?php
            $diff = strtotime($r['res_end_time']) - strtotime($r['res_time']);
            if ($diff > 0) {
                $hrs  = floor($diff / 3600);
                $mins = floor(($diff % 3600) / 60);
                $dur  = ($hrs > 0 ? $hrs.'h' : '') . ($mins > 0 ? ' '.$mins.'m' : '');
                echo '<span style="color:#555;font-size:12px"> (' . trim($dur) . ')</span>';
            }
          ?>
        <?php endif; ?>
      </td>
    </tr>
    <tr><th>Number of Pax</th><td><?= (int)$r['pax'] ?> guest<?= $r['pax']!=1?'s':'' ?></td></tr>
    <tr><th>Location / Table</th><td><?= htmlspecialchars($r['location'] ?: '—') ?></td></tr>
  </table>
</div>

<div class="section">
  <div class="section-title">Notes / Special Requests</div>
  <div class="notes-box"><?= nl2br(htmlspecialchars($r['notes'] ?: 'No additional notes.')) ?></div>
</div>

<div class="footer">
  <div>
    <div style="font-size:11px;color:#777">Booked by: <?= htmlspecialchars($r['created_by_name'] ?? 'Staff') ?></div>
    <div style="font-size:11px;color:#777">Created: <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
  </div>
  <div class="sig-line">Authorised By</div>
</div>

<div class="no-print" style="margin-top:30px;text-align:center;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
  <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;font-size:14px">🖨 Print Reservation</button>
  <?php
    $waMsg  = "📅 *RESERVATION CONFIRMATION*\n";
    $waMsg .= "🏨 " . $bizName . "\n\n";
    $waMsg .= "👤 *Customer:* " . $r['customer_name'] . "\n";
    $waMsg .= "📞 *Contact:* " . $r['contact'] . "\n";
    $waMsg .= "📆 *Date:* " . date('l, d F Y', strtotime($r['res_date'])) . "\n";
    $tStr = date('h:i A', strtotime($r['res_time']));
    if (!empty($r['res_end_time'])) {
        $tStr .= ' – ' . date('h:i A', strtotime($r['res_end_time']));
        $d = strtotime($r['res_end_time']) - strtotime($r['res_time']);
        if ($d > 0) {
            $h = floor($d/3600); $m = floor(($d%3600)/60);
            $tStr .= ' (' . trim(($h>0?$h.'h':'') . ($m>0?' '.$m.'m':'')) . ')';
        }
    }
    $waMsg .= "⏰ *Time:* " . $tStr . "\n";
    $waMsg .= "👥 *Pax:* " . $r['pax'] . " guest" . ($r['pax']!=1?'s':'') . "\n";
    if ($r['location']) $waMsg .= "📍 *Location:* " . $r['location'] . "\n";
    if ($r['notes'])    $waMsg .= "📝 *Notes:* " . $r['notes'] . "\n";
    $waMsg .= "\n✅ *Status: " . $r['status'] . "*";
    $waUrl = 'https://wa.me/?text=' . rawurlencode($waMsg);
  ?>
  <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank"
     style="padding:8px 20px;background:#25D366;color:#fff;border-radius:6px;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
    📲 Share via WhatsApp
  </a>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
