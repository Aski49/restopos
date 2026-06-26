<?php
require_once '../includes/config.php';
requireAccess('activity_log');
$db = getDB();
$pageTitle = 'Activity Log'; $activePage = 'activity_log';

$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');
$moduleFilter = $_GET['module'] ?? '';
$userFilter   = $_GET['user'] ?? '';

$sql = "SELECT * FROM activity_log WHERE DATE(created_at) BETWEEN ? AND ?";
$params = [$from, $to];
if ($moduleFilter !== '') { $sql .= " AND module = ?"; $params[] = $moduleFilter; }
if ($userFilter !== '')   { $sql .= " AND user_name = ?"; $params[] = $userFilter; }
$sql .= " ORDER BY created_at DESC LIMIT 500";

$logs = [];
$modules = [];
$users = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    $modules = $db->query("SELECT DISTINCT module FROM activity_log ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);
    $users   = $db->query("SELECT DISTINCT user_name FROM activity_log WHERE user_name IS NOT NULL ORDER BY user_name")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // activity_log table doesn't exist yet — show empty state with migration hint
}

$todayCount = 0;
try {
    $tc = $db->prepare("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at)=?");
    $tc->execute([date('Y-m-d')]);
    $todayCount = $tc->fetchColumn();
} catch (Exception $e) {}

$moduleIcons = [
    'auth'=>'🔑','pos'=>'🧾','bills'=>'📄','reservations'=>'📅','inventory'=>'📦',
    'expenses'=>'💸','debtors'=>'🏦','payroll'=>'👥','promotions'=>'🎉','employees'=>'🧑‍💼',
    'banking'=>'🏛','menu'=>'🍽','settings'=>'⚙',
];
$actionColors = [
    'Logged In'=>'badge-green','Logged Out'=>'badge-muted',
    'Deleted Bill'=>'badge-red','Voided Bill'=>'badge-red',
    'Settled Bill'=>'badge-green','Created Reservation'=>'badge-blue',
    'Updated Reservation'=>'badge-accent','Deleted Reservation'=>'badge-red',
];

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Activity Log</div>
</div>

<?php if (!$logs && empty($modules)): ?>
<div class="alert alert-info">
  📋 No activity recorded yet, or the Activity Log table hasn't been created on this database. Run <code>upgrade_migration.sql</code> in phpMyAdmin to enable this feature, then activity will start appearing here automatically as users log in, settle bills, manage reservations, etc.
</div>
<?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🕓</span><div class="stat-label">Events Today</div><div class="stat-value text-accent"><?=$todayCount?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">Showing</div><div class="stat-value text-blue"><?=count($logs)?> events</div></div>
  <div class="stat-card"><span class="stat-icon">👤</span><div class="stat-label">Active Users</div><div class="stat-value text-green"><?=count($users)?></div></div>
</div>

<div class="card mb-16" style="padding:14px 16px">
  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <div class="form-group"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?=$from?>"></div>
    <div class="form-group"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?=$to?>"></div>
    <div class="form-group"><label class="form-label">Module</label>
      <select name="module" class="form-control">
        <option value="">All Modules</option>
        <?php foreach ($modules as $m): ?>
          <option value="<?=htmlspecialchars($m)?>" <?=$moduleFilter===$m?'selected':''?>><?=htmlspecialchars(ucfirst($m))?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label class="form-label">User</label>
      <select name="user" class="form-control">
        <option value="">All Users</option>
        <?php foreach ($users as $u): ?>
          <option value="<?=htmlspecialchars($u)?>" <?=$userFilter===$u?'selected':''?>><?=htmlspecialchars($u)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn">Filter</button>
    <a href="?from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" class="btn btn-outline">Today</a>
    <a href="activity_log.php" class="btn btn-outline">Clear</a>
  </form>
</div>

<div class="card">
  <div class="card-title">Event Log <span class="badge badge-blue"><?=count($logs)?> events</span></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $log):
        $icon = $moduleIcons[$log['module']] ?? '📌';
        $actionBadge = $actionColors[$log['action']] ?? 'badge-muted';
      ?>
        <tr>
          <td class="mono fs-12 text-muted"><?=date('d/m/Y H:i:s', strtotime($log['created_at']))?></td>
          <td class="fw-700"><?=htmlspecialchars($log['user_name'] ?: '—')?></td>
          <td><span class="badge <?=$actionBadge?>"><?=htmlspecialchars($log['action'])?></span></td>
          <td><?=$icon?> <?=htmlspecialchars(ucfirst($log['module']))?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($log['details'] ?: '—')?></td>
          <td class="mono fs-12 text-muted"><?=htmlspecialchars($log['ip_address'] ?: '—')?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?><tr><td colspan="6" class="text-center text-muted">No activity recorded for this filter.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
