<?php
require_once '../includes/config.php';
requireAccess('kds');
$db = getDB();
$pageTitle = 'Kitchen Display'; $activePage = 'kds';

// AJAX endpoint for status updates (called via fetch, no full page reload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_status'])) {
    header('Content-Type: application/json');
    $itemId = (int)$_POST['item_id'];
    $newStatus = $_POST['new_status'];
    $allowed = ['pending','preparing','ready','served'];
    if (!in_array($newStatus, $allowed)) {
        echo json_encode(['ok'=>false,'error'=>'Invalid status']); exit;
    }
    $db->prepare("UPDATE bill_items SET kitchen_status=? WHERE id=?")->execute([$newStatus, $itemId]);
    echo json_encode(['ok'=>true]);
    exit;
}

// Fetch active orders (today, not fully served) grouped by bill
$orders = $db->query("
    SELECT bi.id as item_id, bi.item_name, bi.qty, bi.kitchen_status,
           b.id as bill_id, b.bill_no, b.order_type, b.table_no, b.created_at
    FROM bill_items bi
    JOIN bills b ON bi.bill_id = b.id
    WHERE DATE(b.created_at) = CURDATE()
      AND b.status = 'settled'
      AND bi.kitchen_status != 'served'
    ORDER BY b.created_at ASC, bi.id ASC
")->fetchAll();

// Group by bill
$grouped = [];
foreach ($orders as $o) {
    $grouped[$o['bill_id']]['bill_no'] = $o['bill_no'];
    $grouped[$o['bill_id']]['order_type'] = $o['order_type'];
    $grouped[$o['bill_id']]['table_no'] = $o['table_no'];
    $grouped[$o['bill_id']]['created_at'] = $o['created_at'];
    $grouped[$o['bill_id']]['items'][] = $o;
}

$counts = ['pending'=>0,'preparing'=>0,'ready'=>0];
foreach ($orders as $o) {
    if (isset($counts[$o['kitchen_status']])) $counts[$o['kitchen_status']]++;
}

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">🔥 Kitchen Display System</div>
  <span class="badge badge-green" id="kdsLive">● Live — auto-refreshing</span>
</div>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">⏳</span><div class="stat-label">Pending</div><div class="stat-value text-red"><?=$counts['pending']?></div></div>
  <div class="stat-card"><span class="stat-icon">🔥</span><div class="stat-label">Preparing</div><div class="stat-value text-accent"><?=$counts['preparing']?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Ready to Serve</div><div class="stat-value text-green"><?=$counts['ready']?></div></div>
</div>

<?php if (empty($grouped)): ?>
  <div class="alert alert-info">🍽 No active kitchen orders right now. New settled bills will appear here automatically.</div>
<?php else: ?>
<div class="kds-grid" id="kdsGrid">
  <?php foreach ($grouped as $billId => $g):
    $allPending  = !array_filter($g['items'], fn($i)=>$i['kitchen_status']!=='pending');
    $allReady    = !array_filter($g['items'], fn($i)=>$i['kitchen_status']!=='ready');
    $cardClass = $allReady ? 'kds-card-ready' : ($allPending ? 'kds-card-pending' : 'kds-card-mixed');
    $elapsed = floor((time() - strtotime($g['created_at'])) / 60);
  ?>
  <div class="kds-card <?=$cardClass?>" data-bill="<?=$billId?>">
    <div class="kds-card-head">
      <div>
        <div class="kds-bill-no"><?=htmlspecialchars($g['bill_no'])?></div>
        <div class="kds-meta"><?=htmlspecialchars($g['order_type'])?><?= $g['table_no'] ? ' · '.htmlspecialchars($g['table_no']) : '' ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <a href="print_kot.php?bill_id=<?=$billId?>" target="_blank" class="kds-btn kds-btn-print" title="Print Kitchen Order Ticket">🖨 Print</a>
        <div class="kds-timer" data-started="<?=strtotime($g['created_at'])?>"><?=$elapsed?>m</div>
      </div>
    </div>
    <div class="kds-items">
      <?php foreach ($g['items'] as $it): ?>
        <div class="kds-item kds-status-<?=$it['kitchen_status']?>" data-item="<?=$it['item_id']?>">
          <div class="kds-item-name"><span class="kds-qty"><?=$it['qty']?>×</span> <?=htmlspecialchars($it['item_name'])?></div>
          <div class="kds-item-actions">
            <?php if ($it['kitchen_status']==='pending'): ?>
              <button class="kds-btn kds-btn-accent" onclick="updateStatus(<?=$it['item_id']?>,'preparing',this)">▶ Start</button>
            <?php elseif ($it['kitchen_status']==='preparing'): ?>
              <button class="kds-btn kds-btn-green" onclick="updateStatus(<?=$it['item_id']?>,'ready',this)">✅ Ready</button>
            <?php elseif ($it['kitchen_status']==='ready'): ?>
              <button class="kds-btn kds-btn-blue" onclick="updateStatus(<?=$it['item_id']?>,'served',this)">🍽 Served</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.kds-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.kds-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:14px;border-top:4px solid var(--muted)}
.kds-card-pending{border-top-color:var(--red)}
.kds-card-mixed{border-top-color:var(--accent)}
.kds-card-ready{border-top-color:var(--green)}
.kds-card-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.kds-bill-no{font-weight:800;font-family:'JetBrains Mono',monospace;color:var(--accent);font-size:14px}
.kds-meta{font-size:11px;color:var(--muted);margin-top:2px}
.kds-timer{font-family:'JetBrains Mono',monospace;font-weight:700;font-size:13px;padding:3px 9px;border-radius:6px;background:var(--card2);color:var(--muted)}
.kds-items{display:flex;flex-direction:column;gap:8px}
.kds-item{display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-radius:8px;background:var(--card2)}
.kds-status-pending{border-left:3px solid var(--red)}
.kds-status-preparing{border-left:3px solid var(--accent)}
.kds-status-ready{border-left:3px solid var(--green);opacity:.85}
.kds-item-name{font-size:13px;font-weight:600}
.kds-qty{color:var(--accent);font-weight:800;margin-right:4px}
.kds-btn{border:none;border-radius:7px;padding:6px 12px;font-size:11px;font-weight:700;cursor:pointer}
.kds-btn-accent{background:var(--accent);color:#1a1206}
.kds-btn-green{background:var(--green);color:#04241a}
.kds-btn-blue{background:var(--blue, #3b82f6);color:#04162a}
.kds-btn-print{background:var(--card2);color:var(--text);border:1px solid var(--border);text-decoration:none;display:inline-flex;align-items:center}
</style>

<script>
function updateStatus(itemId, newStatus, btn) {
  btn.disabled = true;
  btn.textContent = '...';
  const fd = new FormData();
  fd.append('ajax_status', '1');
  fd.append('item_id', itemId);
  fd.append('new_status', newStatus);
  fetch('kds.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        location.reload();
      } else {
        alert('Failed to update status: ' + (data.error || 'Unknown error'));
        btn.disabled = false;
      }
    })
    .catch(() => { alert('Network error updating status.'); btn.disabled = false; });
}

// Auto-refresh every 20 seconds to pick up new orders from POS
setInterval(() => { location.reload(); }, 20000);

// Live elapsed timer update (cosmetic, between reloads)
setInterval(() => {
  document.querySelectorAll('.kds-timer').forEach(el => {
    const started = parseInt(el.dataset.started, 10);
    const mins = Math.floor((Date.now()/1000 - started) / 60);
    el.textContent = mins + 'm';
  });
}, 15000);
</script>
<?php include '../includes/footer.php'; ?>
