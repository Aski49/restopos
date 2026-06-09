<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'Settings'; $activePage = 'settings';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Save business settings ────────────────────────────────
    if ($action === 'save_settings') {
        $keys = ['business_name','address','phone','email','service_charge_pct','tax_pct','ubereats_commission','pickme_commission'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $db->prepare("INSERT INTO settings(setting_key,setting_value) VALUES(?,?)
                              ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
                   ->execute([$key, trim($_POST[$key])]);
            }
        }
        $msg = ['type'=>'success','text'=>'Settings saved successfully.'];

    // ── Add user ──────────────────────────────────────────────
    } elseif ($action === 'add_user') {
        $uname = trim($_POST['username']);
        $exists = $db->prepare("SELECT id FROM users WHERE username=?");
        $exists->execute([$uname]); 
        if ($exists->fetch()) {
            $msg = ['type'=>'danger','text'=>'Username already exists.'];
        } else {
            $db->prepare("INSERT INTO users(name,username,password,role) VALUES(?,?,?,?)")
               ->execute([trim($_POST['name']),$uname,trim($_POST['password']),$_POST['role']]);
            $msg = ['type'=>'success','text'=>'User added successfully.'];
        }

    // ── Edit user ─────────────────────────────────────────────
    } elseif ($action === 'edit_user') {
        $uid  = (int)$_POST['user_id'];
        $pass = trim($_POST['password']);
        if ($pass) {
            $db->prepare("UPDATE users SET name=?,username=?,password=?,role=? WHERE id=?")
               ->execute([trim($_POST['name']),trim($_POST['username']),$pass,$_POST['role'],$uid]);
        } else {
            $db->prepare("UPDATE users SET name=?,username=?,role=? WHERE id=?")
               ->execute([trim($_POST['name']),trim($_POST['username']),$_POST['role'],$uid]);
        }
        $msg = ['type'=>'success','text'=>'User updated.'];

    // ── Delete user ───────────────────────────────────────────
    } elseif ($action === 'delete_user') {
        $uid = (int)$_POST['user_id'];
        if ($uid === (int)$_SESSION['user_id']) {
            $msg = ['type'=>'danger','text'=>'Cannot delete your own account.'];
        } else {
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            $msg = ['type'=>'success','text'=>'User deleted.'];
        }

    // ── Toggle user active ────────────────────────────────────
    } elseif ($action === 'toggle_user') {
        $uid = (int)$_POST['user_id'];
        if ($uid !== (int)$_SESSION['user_id']) {
            $db->prepare("UPDATE users SET active=NOT active WHERE id=?")->execute([$uid]);
            $msg = ['type'=>'success','text'=>'User status changed.'];
        }
    }
}

// ── Fetch ─────────────────────────────────────────────────────
$users = $db->query("SELECT * FROM users ORDER BY role, name")->fetchAll();
$settingsArr = [];
foreach ($db->query("SELECT * FROM settings")->fetchAll() as $r) {
    $settingsArr[$r['setting_key']] = $r['setting_value'];
}

include '../includes/header.php';
?>

<div class="page-header"><div class="page-title">Settings</div></div>

<?php if ($msg): ?>
  <div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div>
<?php endif; ?>

<div class="grid-2">

  <!-- Business Info -->
  <div class="card">
    <div class="card-title">Business Information</div>
    <form method="POST">
      <input type="hidden" name="action" value="save_settings">
      <div class="form-row mb-12">
        <div class="form-group"><label class="form-label">Business Name</label>
          <input name="business_name" class="form-control" value="<?=htmlspecialchars($settingsArr['business_name']??'')?>"></div>
        <div class="form-group"><label class="form-label">Address</label>
          <input name="address" class="form-control" value="<?=htmlspecialchars($settingsArr['address']??'')?>"></div>
        <div class="form-group"><label class="form-label">Phone</label>
          <input name="phone" class="form-control" value="<?=htmlspecialchars($settingsArr['phone']??'')?>"></div>
        <div class="form-group"><label class="form-label">Email</label>
          <input name="email" class="form-control" value="<?=htmlspecialchars($settingsArr['email']??'')?>"></div>
      </div>
      <button type="submit" class="btn">💾 Save</button>
    </form>
  </div>

  <!-- Tax & Charges -->
  <div class="card">
    <div class="card-title">Tax & Commission</div>
    <form method="POST">
      <input type="hidden" name="action" value="save_settings">
      <div class="form-row mb-12">
        <div class="form-group"><label class="form-label">Service Charge (%)</label>
          <input type="number" name="service_charge_pct" step="0.1" class="form-control" value="<?=htmlspecialchars($settingsArr['service_charge_pct']??10)?>"></div>
        <div class="form-group"><label class="form-label">Tax / VAT (%)</label>
          <input type="number" name="tax_pct" step="0.1" class="form-control" value="<?=htmlspecialchars($settingsArr['tax_pct']??8)?>"></div>
        <div class="form-group"><label class="form-label">Uber Eats Commission (%)</label>
          <input type="number" name="ubereats_commission" step="0.1" class="form-control" value="<?=htmlspecialchars($settingsArr['ubereats_commission']??30)?>"></div>
        <div class="form-group"><label class="form-label">PickMe Commission (%)</label>
          <input type="number" name="pickme_commission" step="0.1" class="form-control" value="<?=htmlspecialchars($settingsArr['pickme_commission']??28)?>"></div>
      </div>
      <button type="submit" class="btn">💾 Save</button>
    </form>
  </div>

  <!-- System Users — full span -->
  <div class="card" style="grid-column:1/-1">
    <div class="flex-between mb-16">
      <div class="card-title" style="margin-bottom:0">System Users</div>
      <button class="btn" onclick="openAddUser()">+ Add User</button>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th><th>Username</th><th>Password</th><th>Role</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u):
          $isSelf = ($u['id'] == $_SESSION['user_id']);
        ?>
          <tr>
            <td class="fw-700"><?=htmlspecialchars($u['name'])?></td>
            <td class="mono text-accent"><?=htmlspecialchars($u['username'])?></td>
            <td class="mono text-muted"><?=htmlspecialchars($u['password'])?></td>
            <td>
              <span class="badge <?=$u['role']==='admin'?'badge-red':($u['role']==='manager'?'badge-accent':'badge-muted')?>">
                <?=ucfirst($u['role'])?>
              </span>
            </td>
            <td>
              <span class="badge <?=$u['active']?'badge-green':'badge-red'?>">
                <?=$u['active']?'Active':'Inactive'?>
              </span>
              <?php if ($isSelf): ?><span class="badge badge-blue" style="margin-left:4px">You</span><?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <!-- Edit -->
                <button class="btn btn-sm btn-outline"
                  onclick='openEditUser(<?=json_encode(["id"=>$u["id"],"name"=>$u["name"],"username"=>$u["username"],"role"=>$u["role"],"password"=>$u["password"]])?>)'>
                  ✏ Edit
                </button>

                <!-- Toggle active -->
                <?php if (!$isSelf): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="toggle_user">
                  <input type="hidden" name="user_id" value="<?=$u['id']?>">
                  <button class="btn btn-sm <?=$u['active']?'btn-outline-red':'btn-outline-green'?>">
                    <?=$u['active']?'Deactivate':'Activate'?>
                  </button>
                </form>

                <!-- Delete -->
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Delete user <?=htmlspecialchars($u['username'])?>? This cannot be undone.')">
                  <input type="hidden" name="action" value="delete_user">
                  <input type="hidden" name="user_id" value="<?=$u['id']?>">
                  <button class="btn btn-sm btn-red">🗑 Delete</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- About -->
  <div class="card">
    <div class="card-title">About System</div>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)"><span class="text-muted">System</span><span class="fw-700">RestoPOS Sri Lanka</span></div>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)"><span class="text-muted">Version</span><span class="mono">1.0.0</span></div>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)"><span class="text-muted">Currency</span><span class="mono">Rs. (LKR)</span></div>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)"><span class="text-muted">PHP Version</span><span class="mono"><?=PHP_VERSION?></span></div>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)"><span class="text-muted">Logged In As</span><span class="mono text-accent"><?=htmlspecialchars($_SESSION['user']['username']??'')?></span></div>
    <div style="margin-top:16px">
      <a href="../database.sql" download class="btn btn-outline btn-sm">⬇ Download SQL Schema</a>
    </div>
  </div>

</div>

<!-- ══ ADD / EDIT USER MODAL ══════════════════════════════════ -->
<div class="modal-overlay" id="modalUser">
  <div class="modal-box">
    <div class="modal-title" id="userModalTitle">Add User</div>
    <form method="POST" id="userForm">
      <input type="hidden" name="action" id="userAction" value="add_user">
      <input type="hidden" name="user_id" id="userId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input name="name" id="uName" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input name="username" id="uUsername" class="form-control" required autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label">Password <span id="passNote" class="text-muted fs-12">(required)</span></label>
          <input type="text" name="password" id="uPassword" class="form-control" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select name="role" id="uRole" class="form-control">
            <option value="cashier">Cashier</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="alert alert-info fs-12 mb-12">
        <strong>Roles:</strong>
        Admin = full access &nbsp;|&nbsp;
        Manager = reports, POS, staff &nbsp;|&nbsp;
        Cashier = POS only
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn" id="userSubmitBtn">Add User</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalUser')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddUser() {
  document.getElementById('userModalTitle').textContent = 'Add User';
  document.getElementById('userAction').value   = 'add_user';
  document.getElementById('userId').value       = '';
  document.getElementById('uName').value        = '';
  document.getElementById('uUsername').value    = '';
  document.getElementById('uPassword').value    = '';
  document.getElementById('uRole').value        = 'cashier';
  document.getElementById('passNote').textContent = '(required)';
  document.getElementById('uPassword').required  = true;
  document.getElementById('userSubmitBtn').textContent = 'Add User';
  openModal('modalUser');
}

function openEditUser(u) {
  document.getElementById('userModalTitle').textContent = 'Edit User — ' + u.username;
  document.getElementById('userAction').value   = 'edit_user';
  document.getElementById('userId').value       = u.id;
  document.getElementById('uName').value        = u.name;
  document.getElementById('uUsername').value    = u.username;
  document.getElementById('uPassword').value    = u.password;
  document.getElementById('uRole').value        = u.role;
  document.getElementById('passNote').textContent = '(leave blank to keep current)';
  document.getElementById('uPassword').required  = false;
  document.getElementById('userSubmitBtn').textContent = 'Save Changes';
  openModal('modalUser');
}
</script>

<?php include '../includes/footer.php'; ?>
