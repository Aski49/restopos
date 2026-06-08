<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'Debtors & Credit'; $activePage = 'debtors';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_debtor') {
        $db->prepare("INSERT INTO debtors(name,phone,email,credit_limit) VALUES(?,?,?,?)")
           ->execute([$_POST['name'],$_POST['phone']??'',$_POST['email']??'',$_POST['credit_limit']??0]);
        $msg = ['type'=>'success','text'=>'Debtor added.'];
    } elseif ($action === 'edit_debtor') {
        $db->prepare("UPDATE debtors SET name=?,phone=?,email=?,credit_limit=? WHERE id=?")
           ->execute([$_POST['name'],$_POST['phone']??'',$_POST['email']??'',$_POST['credit_limit']??0,$_POST['debtor_id']]);
        $msg = ['type'=>'success','text'=>'Debtor updated.'];
    } elseif ($action === 'delete_debtor') {
        $db->prepare("DELETE FROM debtor_payments WHERE debtor_id=?")->execute([$_POST['debtor_id']]);
        $db->prepare("DELETE FROM debtors WHERE id=?")->execute([$_POST['debtor_id']]);
        $msg = ['type'=>'success','text'=>'Debtor deleted.'];
    } elseif ($action === 'payment') {
        $amt = (float)$_POST['amount'];
        $db->prepare("UPDATE debtors SET outstanding=GREATEST(0,outstanding-?) WHERE id=?")->execute([$amt,$_POST['debtor_id']]);
        $db->prepare("INSERT INTO debtor_payments(debtor_id,txn_date,amount,type,notes) VALUES(?,CURDATE(),?,'payment',?)")->execute([$_POST['debtor_id'],$amt,$_POST['notes']??'']);
        $msg = ['type'=>'success','text'=>'Payment of '.fmt($amt).' recorded.'];
    } elseif ($action === 'charge') {
        $amt = (float)$_POST['amount'];
        $db->prepare("UPDATE debtors SET outstanding=outstanding+? WHERE id=?")->execute([$amt,$_POST['debtor_id']]);
        $db->prepare("INSERT INTO debtor_payments(debtor_id,txn_date,amount,type,notes) VALUES(?,CURDATE(),?,'charge',?)")->execute([$_POST['debtor_id'],$amt,$_POST['notes']??'']);
        $msg = ['type'=>'success','text'=>'Charge of '.fmt($amt).' added.'];
    }
}

$debtors = $db->query("SELECT * FROM debtors ORDER BY outstanding DESC")->fetchAll();
$totalOutstanding = array_sum(array_column($debtors,'outstanding'));

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Debtors & Credit</div>
  <button class="btn" onclick="openModal('modalAddDebtor')">+ Add Debtor</button>
</div>
<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🏦</span><div class="stat-label">Total Outstanding</div><div class="stat-value text-red"><?=fmt($totalOutstanding)?></div></div>
  <div class="stat-card"><span class="stat-icon">👤</span><div class="stat-label">Total Debtors</div><div class="stat-value text-blue"><?=count($debtors)?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Cleared</div><div class="stat-value text-green"><?=count(array_filter($debtors,fn($d)=>$d['outstanding']==0))?></div></div>
</div>

<div class="card">
  <div class="card-title">Debtor Ledger</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Name</th><th>Phone</th><th>Email</th><th>Credit Limit</th><th class="text-right">Outstanding</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($debtors as $d): ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($d['name'])?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($d['phone']??'—')?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($d['email']??'—')?></td>
          <td class="mono"><?=$d['credit_limit']>0?fmt($d['credit_limit']):'—'?></td>
          <td class="text-right mono <?=$d['outstanding']>0?'text-red fw-700':'text-green'?>"><?=fmt($d['outstanding'])?></td>
          <td><span class="badge <?=$d['outstanding']>0?'badge-red':'badge-green'?>"><?=$d['outstanding']>0?'Outstanding':'Cleared'?></span></td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="btn btn-sm btn-green" onclick="setDebtorAction(<?=$d['id']?>,'payment','<?=addslashes($d['name'])?>')">💰 Payment</button>
              <button class="btn btn-sm btn-outline-red" onclick="setDebtorAction(<?=$d['id']?>,'charge','<?=addslashes($d['name'])?>')">+ Charge</button>
              <?php $dedit=["id"=>$d["id"],"name"=>$d["name"],"phone"=>$d["phone"]??"","email"=>$d["email"]??"","credit_limit"=>$d["credit_limit"]]; ?>
              <button class="btn btn-sm btn-outline" onclick='openEditDebtor(<?=json_encode($dedit)?>)'>✏</button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete debtor <?=htmlspecialchars($d['name'])?>? All payment history will also be deleted.')">
                <input type="hidden" name="action" value="delete_debtor">
                <input type="hidden" name="debtor_id" value="<?=$d['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($debtors)): ?><tr><td colspan="7" class="text-center text-muted">No debtors added yet.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><tr><td colspan="4"><strong>Total Outstanding</strong></td><td class="text-right mono text-red"><strong><?=fmt($totalOutstanding)?></strong></td><td colspan="2"></td></tr></tfoot>
    </table>
  </div>
</div>

<!-- Payment / Charge Modal -->
<div class="modal-overlay" id="modalAction">
  <div class="modal-box">
    <div class="modal-title" id="actionTitle">Record Payment</div>
    <form method="POST">
      <input type="hidden" name="action" id="actionType" value="payment">
      <input type="hidden" name="debtor_id" id="actionDebtorId">
      <div class="form-group mb-12"><label class="form-label">Debtor</label><input id="actionDebtorName" class="form-control" readonly></div>
      <div class="form-group mb-12"><label class="form-label">Amount (Rs.)</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
      <div class="form-group mb-12"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-green">Confirm</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAction')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Debtor Modal -->
<div class="modal-overlay" id="modalAddDebtor">
  <div class="modal-box">
    <div class="modal-title" id="debtorModalTitle">Add Debtor</div>
    <form method="POST">
      <input type="hidden" name="action" id="debtorAction" value="add_debtor">
      <input type="hidden" name="debtor_id" id="editDebtorId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Name *</label><input name="name" id="debtorName" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Phone</label><input name="phone" id="debtorPhone" class="form-control"></div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="debtorEmail" class="form-control"></div>
        <div class="form-group"><label class="form-label">Credit Limit (Rs.)</label><input type="number" name="credit_limit" id="debtorLimit" class="form-control" value="0"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn" id="debtorSubmitBtn">Add Debtor</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAddDebtor')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function setDebtorAction(id, type, name) {
  document.getElementById('actionType').value = type;
  document.getElementById('actionDebtorId').value = id;
  document.getElementById('actionDebtorName').value = name;
  document.getElementById('actionTitle').textContent = (type==='payment'?'Record Payment from ':'Add Charge for ') + name;
  openModal('modalAction');
}
function openEditDebtor(d) {
  document.getElementById('debtorModalTitle').textContent = 'Edit Debtor';
  document.getElementById('debtorAction').value   = 'edit_debtor';
  document.getElementById('editDebtorId').value   = d.id;
  document.getElementById('debtorName').value     = d.name;
  document.getElementById('debtorPhone').value    = d.phone || '';
  document.getElementById('debtorEmail').value    = d.email || '';
  document.getElementById('debtorLimit').value    = d.credit_limit || 0;
  document.getElementById('debtorSubmitBtn').textContent = 'Save Changes';
  openModal('modalAddDebtor');
}
</script>
<?php include '../includes/footer.php'; ?>
