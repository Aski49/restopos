<?php
require_once '../includes/config.php';
requireAccess('reservations');
$db = getDB();
$pageTitle = 'Reservations'; $activePage = 'reservations';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // AJAX poll for new reservations (used by popup notification system)
    if ($action === 'poll_new') {
        header('Content-Type: application/json');
        // New = created in last 60 seconds and status is Confirmed (just booked online)
        // We use a session-stored timestamp to only send truly new ones
        $since = $_SESSION['res_poll_since'] ?? date('Y-m-d H:i:s', strtotime('-60 seconds'));
        $rows = $db->prepare("SELECT id, customer_name, pax, res_date, res_time FROM reservations WHERE created_at >= ? ORDER BY created_at DESC LIMIT 10");
        $rows->execute([$since]);
        $rows = $rows->fetchAll();
        $_SESSION['res_poll_since'] = date('Y-m-d H:i:s');
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'            => $r['id'],
                'customer_name' => $r['customer_name'],
                'pax'           => $r['pax'],
                'date'          => date('d M Y', strtotime($r['res_date'])),
                'time'          => date('h:i A', strtotime($r['res_time'])),
            ];
        }
        echo json_encode(['new_reservations'=>$out]); exit;
    }

    if ($action === 'add_res') {
        $endTime = !empty($_POST['res_end_time']) ? $_POST['res_end_time'] : null;
        $db->prepare("INSERT INTO reservations(customer_name,contact,res_date,res_time,res_end_time,pax,location,notes,status,created_by)
                      VALUES(?,?,?,?,?,?,?,?,?,?)")
           ->execute([
               trim($_POST['customer_name']),
               trim($_POST['contact']),
               $_POST['res_date'],
               $_POST['res_time'],
               $endTime,
               (int)$_POST['pax'],
               trim($_POST['location'] ?? ''),
               trim($_POST['notes'] ?? ''),
               $_POST['status'] ?? 'Confirmed',
               $_SESSION['user_id'] ?? null,
           ]);
        logActivity('Created Reservation', 'reservations', 'Reservation for '.trim($_POST['customer_name']).' on '.$_POST['res_date'].' '.$_POST['res_time'].($endTime ? ' – '.$endTime : ''));
        $msg = ['type'=>'success','text'=>'Reservation added.'];

    } elseif ($action === 'edit_res') {
        $endTime = !empty($_POST['res_end_time']) ? $_POST['res_end_time'] : null;
        $db->prepare("UPDATE reservations SET customer_name=?,contact=?,res_date=?,res_time=?,res_end_time=?,pax=?,location=?,notes=?,status=? WHERE id=?")
           ->execute([
               trim($_POST['customer_name']),
               trim($_POST['contact']),
               $_POST['res_date'],
               $_POST['res_time'],
               $endTime,
               (int)$_POST['pax'],
               trim($_POST['location'] ?? ''),
               trim($_POST['notes'] ?? ''),
               $_POST['status'] ?? 'Confirmed',
               $_POST['res_id'],
           ]);
        logActivity('Updated Reservation', 'reservations', 'Reservation #'.$_POST['res_id'].' for '.trim($_POST['customer_name']));
        $msg = ['type'=>'success','text'=>'Reservation updated.'];

    } elseif ($action === 'delete_res') {
        $stmt = $db->prepare("SELECT customer_name FROM reservations WHERE id=?");
        $stmt->execute([$_POST['res_id']]);
        $resName = $stmt->fetchColumn();
        $db->prepare("DELETE FROM reservations WHERE id=?")->execute([$_POST['res_id']]);
        logActivity('Deleted Reservation', 'reservations', 'Reservation for '.$resName);
        $msg = ['type'=>'success','text'=>'Reservation deleted.'];

    } elseif ($action === 'status_change') {
        $db->prepare("UPDATE reservations SET status=? WHERE id=?")
           ->execute([$_POST['new_status'], $_POST['res_id']]);
        $msg = ['type'=>'success','text'=>'Status updated.'];
    }
}

// Filters
$filterDate = $_GET['date'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT r.*, u.name as created_by_name FROM reservations r LEFT JOIN users u ON r.created_by=u.id WHERE 1=1";
$params = [];
if ($filterDate !== '') { $sql .= " AND r.res_date = ?"; $params[] = $filterDate; }
if ($filterStatus !== '') { $sql .= " AND r.status = ?"; $params[] = $filterStatus; }
$sql .= " ORDER BY r.res_date DESC, r.res_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$today = date('Y-m-d');
$todayCount = $db->prepare("SELECT COUNT(*) FROM reservations WHERE res_date=? AND status!='Cancelled'");
$todayCount->execute([$today]); $todayCount = $todayCount->fetchColumn();

$upcomingCount = $db->prepare("SELECT COUNT(*) FROM reservations WHERE res_date>=? AND status IN('Confirmed','Pending')");
$upcomingCount->execute([$today]); $upcomingCount = $upcomingCount->fetchColumn();

$totalPaxToday = $db->prepare("SELECT COALESCE(SUM(pax),0) FROM reservations WHERE res_date=? AND status!='Cancelled'");
$totalPaxToday->execute([$today]); $totalPaxToday = $totalPaxToday->fetchColumn();

$pendingCount = $db->query("SELECT COUNT(*) FROM reservations WHERE status='Pending'")->fetchColumn();

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Reservations</div>
  <button class="btn" onclick="openAddRes()">+ New Reservation</button>
</div>
<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-label">Today's Reservations</div><div class="stat-value text-accent"><?=$todayCount?></div></div>
  <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-label">Total Pax Today</div><div class="stat-value text-blue"><?=$totalPaxToday?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">Upcoming (All)</div><div class="stat-value text-green"><?=$upcomingCount?></div></div>
  <div class="stat-card"><span class="stat-icon">⏳</span><div class="stat-label">Pending Confirmation</div><div class="stat-value text-red"><?=$pendingCount?></div></div>
</div>

<div class="card mb-16" style="padding:14px 16px">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="text" id="resSearch" class="form-control" style="max-width:280px" placeholder="🔍 Search name, contact, location..." oninput="liveSearchReservations(this.value)">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="date" name="date" class="form-control" style="max-width:160px" value="<?=htmlspecialchars($filterDate)?>">
      <select name="status" class="form-control" style="max-width:160px">
        <option value="">All Statuses</option>
        <option value="Confirmed" <?=$filterStatus==='Confirmed'?'selected':''?>>Confirmed</option>
        <option value="Pending" <?=$filterStatus==='Pending'?'selected':''?>>Pending</option>
        <option value="Cancelled" <?=$filterStatus==='Cancelled'?'selected':''?>>Cancelled</option>
        <option value="Completed" <?=$filterStatus==='Completed'?'selected':''?>>Completed</option>
      </select>
      <button type="submit" class="btn btn-sm">Filter</button>
      <a href="reservations.php" class="btn btn-sm btn-outline">Clear</a>
      <a href="reservations.php?date=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">Today</a>
      <a href="print_reservations_list.php?date=<?=urlencode($filterDate)?>&status=<?=urlencode($filterStatus)?>" target="_blank" class="btn btn-sm btn-green">🖨 Print List (PDF)</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-title">All Reservations <span class="badge badge-blue" id="resCountBadge"><?=count($reservations)?> shown</span></div>
  <div class="table-wrap">
    <table class="data-table" id="resTable">
      <thead>
        <tr><th>Name</th><th>Contact</th><th>Date</th><th>Time</th><th>Duration</th><th>Pax</th><th>Location</th><th>Notes</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($reservations as $r):
        // Calculate duration between start and end time
        $durLabel = '—';
        if (!empty($r['res_end_time'])) {
            $start = strtotime($r['res_time']);
            $end   = strtotime($r['res_end_time']);
            $diff  = $end - $start;
            if ($diff > 0) {
                $hrs  = floor($diff / 3600);
                $mins = floor(($diff % 3600) / 60);
                $durLabel = $hrs > 0 ? "{$hrs}h" . ($mins > 0 ? " {$mins}m" : '') : "{$mins}m";
            }
        }
      ?>
        <tr class="res-row" data-search="<?=strtolower(htmlspecialchars($r['customer_name'].' '.$r['contact'].' '.$r['location']))?>">
          <td class="fw-700"><?=htmlspecialchars($r['customer_name'])?></td>
          <td class="text-muted fs-12 mono"><?=htmlspecialchars($r['contact'])?></td>
          <td class="mono"><?=date('d/m/Y',strtotime($r['res_date']))?></td>
          <td class="mono text-accent fw-700">
            <?=date('h:i A',strtotime($r['res_time']))?>
            <?php if (!empty($r['res_end_time'])): ?>
              <span class="text-muted" style="font-weight:400"> – <?=date('h:i A',strtotime($r['res_end_time']))?></span>
            <?php endif; ?>
          </td>
          <td class="mono text-blue fw-700"><?=$durLabel?></td>
          <td class="text-right mono"><?=$r['pax']?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($r['location']?:'—')?></td>
          <td class="text-muted fs-12" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($r['notes'])?>"><?=htmlspecialchars($r['notes']?:'—')?></td>
          <td>
            <?php $stColors=['Confirmed'=>'badge-green','Pending'=>'badge-accent','Cancelled'=>'badge-red','Completed'=>'badge-blue']; ?>
            <select class="form-control" style="padding:4px 8px;font-size:11px;width:auto" onchange="quickStatusChange(<?=$r['id']?>,this.value)">
              <?php foreach(['Confirmed','Pending','Cancelled','Completed'] as $st): ?>
                <option value="<?=$st?>" <?=$r['status']===$st?'selected':''?>><?=$st?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <a href="print_reservation.php?id=<?=$r['id']?>" target="_blank" class="btn btn-sm btn-outline" title="Print PDF">🖨</a>
              <?php
                // Build WhatsApp message for this reservation
                $waMsg  = "📅 *RESERVATION CONFIRMATION*\n";
                $waMsg .= "🏨 " . getSetting('business_name','RestoPOS') . "\n\n";
                $waMsg .= "👤 *Customer:* " . $r['customer_name'] . "\n";
                $waMsg .= "📞 *Contact:* " . $r['contact'] . "\n";
                $waMsg .= "📆 *Date:* " . date('l, d F Y', strtotime($r['res_date'])) . "\n";
                $timeStr = date('h:i A', strtotime($r['res_time']));
                if (!empty($r['res_end_time'])) {
                    $timeStr .= ' – ' . date('h:i A', strtotime($r['res_end_time']));
                    $diff = strtotime($r['res_end_time']) - strtotime($r['res_time']);
                    if ($diff > 0) {
                        $hrs  = floor($diff / 3600);
                        $mins = floor(($diff % 3600) / 60);
                        $dur  = ($hrs > 0 ? $hrs.'h' : '') . ($mins > 0 ? ' '.$mins.'m' : '');
                        $timeStr .= ' (' . trim($dur) . ')';
                    }
                }
                $waMsg .= "⏰ *Time:* " . $timeStr . "\n";
                $waMsg .= "👥 *Pax:* " . $r['pax'] . " guest" . ($r['pax'] != 1 ? 's' : '') . "\n";
                if ($r['location']) $waMsg .= "📍 *Location:* " . $r['location'] . "\n";
                if ($r['notes'])    $waMsg .= "📝 *Notes:* " . $r['notes'] . "\n";
                $waMsg .= "\n✅ *Status: " . $r['status'] . "*";
                $waUrl = 'https://wa.me/?text=' . rawurlencode($waMsg);
              ?>
              <a href="<?=htmlspecialchars($waUrl)?>" target="_blank" class="btn btn-sm btn-wa" title="Share via WhatsApp">📲 WA</a>
              <button class="btn btn-sm btn-outline"
                data-id="<?=$r['id']?>"
                data-name="<?=htmlspecialchars($r['customer_name'],ENT_QUOTES)?>"
                data-contact="<?=htmlspecialchars($r['contact'],ENT_QUOTES)?>"
                data-date="<?=$r['res_date']?>"
                data-time="<?=substr($r['res_time'],0,5)?>"
                data-endtime="<?=substr($r['res_end_time']??'',0,5)?>"
                data-pax="<?=$r['pax']?>"
                data-location="<?=htmlspecialchars($r['location'],ENT_QUOTES)?>"
                data-notes="<?=htmlspecialchars($r['notes'],ENT_QUOTES)?>"
                data-status="<?=$r['status']?>"
                onclick="openEditRes(this)">✏</button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete reservation for <?=htmlspecialchars($r['customer_name'],ENT_QUOTES)?>?')">
                <input type="hidden" name="action" value="delete_res">
                <input type="hidden" name="res_id" value="<?=$r['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($reservations)): ?><tr id="resEmptyRow"><td colspan="9" class="text-center text-muted">No reservations found.</td></tr><?php endif; ?>
      </tbody>
    </table>
    <div id="resNoMatch" class="text-center text-muted" style="display:none;padding:20px">No reservations match your search.</div>
  </div>
</div>

<!-- Quick status change form (hidden, submitted via JS) -->
<form method="POST" id="statusForm" style="display:none">
  <input type="hidden" name="action" value="status_change">
  <input type="hidden" name="res_id" id="statusResId">
  <input type="hidden" name="new_status" id="statusNewVal">
</form>

<!-- Add / Edit Reservation Modal -->
<div class="modal-overlay" id="modalRes">
  <div class="modal-box">
    <div class="modal-title" id="resModalTitle">New Reservation</div>
    <form method="POST">
      <input type="hidden" name="action" id="resAction" value="add_res">
      <input type="hidden" name="res_id" id="resId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Customer Name *</label><input name="customer_name" id="resName" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Contact Number *</label><input name="contact" id="resContact" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Date *</label><input type="date" name="res_date" id="resDate" class="form-control" required></div>
        <div class="form-group"><label class="form-label">No. of Pax *</label><input type="number" name="pax" id="resPax" class="form-control" min="1" value="2" required></div>

        <!-- Time row: Start → End with live duration -->
        <div class="form-group">
          <label class="form-label">Start Time *</label>
          <input type="time" name="res_time" id="resTime" class="form-control" required oninput="calcDuration()">
        </div>
        <div class="form-group">
          <label class="form-label">End Time <span class="text-muted fs-12">(optional)</span></label>
          <input type="time" name="res_end_time" id="resEndTime" class="form-control" oninput="calcDuration()">
        </div>
        <!-- Live duration display -->
        <div class="form-group" style="grid-column:1/-1">
          <div id="durationDisplay" style="display:none;background:var(--card2);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;color:var(--blue)">
            ⏱ Duration: <span id="durationLabel">—</span>
          </div>
        </div>

        <div class="form-group"><label class="form-label">Location / Table</label><input name="location" id="resLocation" class="form-control" placeholder="e.g. Karaoke Room, Table T5, Garden Area"></div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" id="resStatus" class="form-control">
            <option value="Confirmed">Confirmed</option>
            <option value="Pending">Pending</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Completed">Completed</option>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Notes</label><textarea name="notes" id="resNotes" class="form-control" rows="2" placeholder="Special requests, occasion, equipment needed..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn" id="resSubmitBtn">Add Reservation</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalRes')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── LIVE SEARCH (instant client-side filter) ─────────────────
function liveSearchReservations(query) {
  const q = query.trim().toLowerCase();
  const rows = document.querySelectorAll('#resTable .res-row');
  let visibleCount = 0;
  rows.forEach(row => {
    const haystack = row.dataset.search || '';
    const match = haystack.includes(q);
    row.style.display = match ? '' : 'none';
    if (match) visibleCount++;
  });
  document.getElementById('resCountBadge').textContent = visibleCount + ' shown';
  document.getElementById('resNoMatch').style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
}

// ── ADD / EDIT MODAL ───────────────────────────────────────────
function openAddRes() {
  document.getElementById('resModalTitle').textContent = 'New Reservation';
  document.getElementById('resAction').value = 'add_res';
  document.getElementById('resId').value = '';
  document.getElementById('resName').value = '';
  document.getElementById('resContact').value = '';
  document.getElementById('resDate').value = '<?=date('Y-m-d')?>';
  document.getElementById('resTime').value = '19:00';
  document.getElementById('resEndTime').value = '';
  document.getElementById('resPax').value = 2;
  document.getElementById('resLocation').value = '';
  document.getElementById('resNotes').value = '';
  document.getElementById('resStatus').value = 'Confirmed';
  document.getElementById('resSubmitBtn').textContent = 'Add Reservation';
  document.getElementById('durationDisplay').style.display = 'none';
  openModal('modalRes');
}

function openEditRes(btn) {
  document.getElementById('resModalTitle').textContent = 'Edit Reservation';
  document.getElementById('resAction').value = 'edit_res';
  document.getElementById('resId').value = btn.dataset.id;
  document.getElementById('resName').value = btn.dataset.name;
  document.getElementById('resContact').value = btn.dataset.contact;
  document.getElementById('resDate').value = btn.dataset.date;
  document.getElementById('resTime').value = btn.dataset.time;
  document.getElementById('resEndTime').value = btn.dataset.endtime || '';
  document.getElementById('resPax').value = btn.dataset.pax;
  document.getElementById('resLocation').value = btn.dataset.location;
  document.getElementById('resNotes').value = btn.dataset.notes;
  document.getElementById('resStatus').value = btn.dataset.status;
  document.getElementById('resSubmitBtn').textContent = 'Save Changes';
  calcDuration();
  openModal('modalRes');
}

// ── LIVE DURATION CALCULATOR ──────────────────────────────────
function calcDuration() {
  const start = document.getElementById('resTime').value;
  const end   = document.getElementById('resEndTime').value;
  const box   = document.getElementById('durationDisplay');
  const lbl   = document.getElementById('durationLabel');

  if (!start || !end) { box.style.display = 'none'; return; }

  const [sh, sm] = start.split(':').map(Number);
  const [eh, em] = end.split(':').map(Number);
  const startMins = sh * 60 + sm;
  const endMins   = eh * 60 + em;
  const diff = endMins - startMins;

  if (diff <= 0) {
    lbl.textContent = '⚠ End time must be after start time';
    lbl.style.color = 'var(--red)';
    box.style.display = '';
    return;
  }

  const hrs  = Math.floor(diff / 60);
  const mins = diff % 60;
  const txt  = (hrs > 0 ? hrs + ' hour' + (hrs > 1 ? 's' : '') : '')
             + (hrs > 0 && mins > 0 ? ' ' : '')
             + (mins > 0 ? mins + ' min' : '');
  lbl.textContent = txt;
  lbl.style.color = 'var(--blue)';
  box.style.display = '';
}

// ── QUICK STATUS CHANGE (dropdown in table row) ────────────────
function quickStatusChange(id, newStatus) {
  document.getElementById('statusResId').value = id;
  document.getElementById('statusNewVal').value = newStatus;
  document.getElementById('statusForm').submit();
}
</script>
<?php include '../includes/footer.php'; ?>
