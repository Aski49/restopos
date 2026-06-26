<?php
require_once '../includes/config.php';
requireAccess('reservations');
$db = getDB();

$filterDate   = $_GET['date'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT r.*, u.name as created_by_name FROM reservations r LEFT JOIN users u ON r.created_by=u.id WHERE 1=1";
$params = [];
if ($filterDate !== '')   { $sql .= " AND r.res_date = ?"; $params[] = $filterDate; }
if ($filterStatus !== '') { $sql .= " AND r.status = ?";   $params[] = $filterStatus; }
$sql .= " ORDER BY r.res_date ASC, r.res_time ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$totalPax = array_sum(array_column($reservations, 'pax'));

// Build a human-readable description of the applied filter for the report title
$filterParts = [];
if ($filterDate !== '') {
    $filterParts[] = 'Date: ' . date('l, d F Y', strtotime($filterDate));
}
if ($filterStatus !== '') {
    $filterParts[] = 'Status: ' . $filterStatus;
}
$filterLabel = $filterParts ? implode('  ·  ', $filterParts) : 'All Reservations — No Filter Applied';

$bizName  = getSetting('business_name', 'RestoPOS');
$bizAddr  = getSetting('address', '');
$bizPhone = getSetting('phone', '');

$statusColors = [
    'Confirmed' => '#0a8a5c',
    'Pending'   => '#b8860b',
    'Cancelled' => '#c0392b',
    'Completed' => '#2563eb',
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Reservations — <?= htmlspecialchars($filterLabel) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Arial,sans-serif; font-size:12.5px; background:#fff; color:#000; padding:24px; }
  .header { text-align:center; border-bottom:2px solid #000; padding-bottom:12px; margin-bottom:16px; }
  .biz-name { font-size:19px; font-weight:bold; }
  .title { font-size:15px; font-weight:bold; margin:8px 0 2px; text-transform:uppercase; letter-spacing:1px; }
  .filter-label { font-size:12.5px; color:#333; font-weight:bold; margin-top:4px; }
  .meta-row { font-size:11px; color:#666; margin-top:6px; }
  .summary { display:flex; justify-content:center; gap:30px; margin:14px 0; }
  .summary-box { text-align:center; }
  .summary-num { font-size:20px; font-weight:bold; }
  .summary-lbl { font-size:10px; color:#666; text-transform:uppercase; letter-spacing:.5px; }
  table { width:100%; border-collapse:collapse; margin-top:10px; }
  th { background:#f0f0f0; text-align:left; padding:7px 8px; font-size:11px; text-transform:uppercase; border:1px solid #ccc; }
  td { padding:7px 8px; border:1px solid #ddd; font-size:12px; vertical-align:top; }
  .status-pill { display:inline-block; padding:2px 9px; border-radius:10px; font-size:10px; font-weight:bold; color:#fff; }
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
  <div class="title">Reservations Report</div>
  <div class="filter-label"><?= htmlspecialchars($filterLabel) ?></div>
  <div class="meta-row">Generated: <?= date('d/m/Y H:i') ?></div>
</div>

<div class="summary">
  <div class="summary-box"><div class="summary-num"><?= count($reservations) ?></div><div class="summary-lbl">Reservations</div></div>
  <div class="summary-box"><div class="summary-num"><?= $totalPax ?></div><div class="summary-lbl">Total Pax</div></div>
</div>

<?php if (empty($reservations)): ?>
  <div class="no-data">No reservations found for this filter.</div>
<?php else: ?>
<table>
  <tr>
    <th>Name</th>
    <th>Contact</th>
    <th>Date</th>
    <th>Time</th>
    <th>Duration</th>
    <th>Pax</th>
    <th>Location</th>
    <th>Status</th>
    <th>Notes</th>
  </tr>
  <?php foreach ($reservations as $r):
    $durLabel = '—';
    if (!empty($r['res_end_time'])) {
        $diff = strtotime($r['res_end_time']) - strtotime($r['res_time']);
        if ($diff > 0) {
            $hrs  = floor($diff / 3600);
            $mins = floor(($diff % 3600) / 60);
            $durLabel = ($hrs > 0 ? $hrs.'h' : '') . ($mins > 0 ? ' '.$mins.'m' : '');
            $durLabel = trim($durLabel);
        }
    }
    $stColor = $statusColors[$r['status']] ?? '#555';
  ?>
  <tr>
    <td><strong><?= htmlspecialchars($r['customer_name']) ?></strong></td>
    <td><?= htmlspecialchars($r['contact']) ?></td>
    <td><?= date('d/m/Y', strtotime($r['res_date'])) ?></td>
    <td>
      <?= date('h:i A', strtotime($r['res_time'])) ?>
      <?php if (!empty($r['res_end_time'])): ?>
        – <?= date('h:i A', strtotime($r['res_end_time'])) ?>
      <?php endif; ?>
    </td>
    <td><?= $durLabel ?></td>
    <td style="text-align:center"><?= (int)$r['pax'] ?></td>
    <td><?= htmlspecialchars($r['location'] ?: '—') ?></td>
    <td><span class="status-pill" style="background:<?= $stColor ?>"><?= htmlspecialchars($r['status']) ?></span></td>
    <td><?= htmlspecialchars($r['notes'] ?: '—') ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<div class="footer">RestoPOS Sri Lanka — Reservations Report</div>

<div class="no-print">
  <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;font-size:14px">🖨 Print / Save as PDF</button>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
