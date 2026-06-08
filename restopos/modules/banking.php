<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'Cash & Banking'; $activePage = 'banking';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_txn') {
        $amt = (float)$_POST['amount'];
        $db->prepare("INSERT INTO bank_transactions(account_id,txn_date,type,amount,description,reference) VALUES(?,?,?,?,?,?)")
           ->execute([$_POST['account_id'],$_POST['txn_date'],$_POST['type'],$amt,$_POST['description']??'',$_POST['reference']??'']);
        $delta = ($_POST['type']==='deposit') ? $amt : -$amt;
        $db->prepare("UPDATE bank_accounts SET balance=balance+? WHERE id=?")->execute([$delta,$_POST['account_id']]);
        $msg = ['type'=>'success','text'=>'Transaction recorded.'];
    } elseif ($action === 'edit_txn') {
        // Reverse old amount, apply new
        $old = $db->prepare("SELECT * FROM bank_transactions WHERE id=?");
        $old->execute([$_POST['txn_id']]); $old=$old->fetch();
        if ($old) {
            $reversal = ($old['type']==='deposit') ? -$old['amount'] : $old['amount'];
            $db->prepare("UPDATE bank_accounts SET balance=balance+? WHERE id=?")->execute([$reversal,$old['account_id']]);
            $newAmt = (float)$_POST['amount'];
            $newDelta = ($_POST['type']==='deposit') ? $newAmt : -$newAmt;
            $db->prepare("UPDATE bank_transactions SET account_id=?,txn_date=?,type=?,amount=?,description=?,reference=? WHERE id=?")
               ->execute([$_POST['account_id'],$_POST['txn_date'],$_POST['type'],$newAmt,$_POST['description']??'',$_POST['reference']??'',$_POST['txn_id']]);
            $db->prepare("UPDATE bank_accounts SET balance=balance+? WHERE id=?")->execute([$newDelta,$_POST['account_id']]);
        }
        $msg = ['type'=>'success','text'=>'Transaction updated.'];
    } elseif ($action === 'delete_txn') {
        $old = $db->prepare("SELECT * FROM bank_transactions WHERE id=?");
        $old->execute([$_POST['txn_id']]); $old=$old->fetch();
        if ($old) {
            $reversal = ($old['type']==='deposit') ? -$old['amount'] : $old['amount'];
            $db->prepare("UPDATE bank_accounts SET balance=balance+? WHERE id=?")->execute([$reversal,$old['account_id']]);
            $db->prepare("DELETE FROM bank_transactions WHERE id=?")->execute([$_POST['txn_id']]);
        }
        $msg = ['type'=>'success','text'=>'Transaction deleted and balance reversed.'];
    } elseif ($action === 'add_account') {
        $db->prepare("INSERT INTO bank_accounts(bank_name,account_no,balance) VALUES(?,?,?)")
           ->execute([$_POST['bank_name'],$_POST['account_no'],$_POST['balance']??0]);
        $msg = ['type'=>'success','text'=>'Bank account added.'];
    } elseif ($action === 'delete_account') {
        $db->prepare("DELETE FROM bank_transactions WHERE account_id=?")->execute([$_POST['account_id']]);
        $db->prepare("DELETE FROM bank_accounts WHERE id=?")->execute([$_POST['account_id']]);
        $msg = ['type'=>'success','text'=>'Account deleted.'];
    }
}

$accounts = $db->query("SELECT * FROM bank_accounts WHERE active=1")->fetchAll();
$txns     = $db->query("SELECT bt.*, ba.bank_name FROM bank_transactions bt JOIN bank_accounts ba ON bt.account_id=ba.id ORDER BY bt.txn_date DESC, bt.id DESC LIMIT 100")->fetchAll();
$totalBank = array_sum(array_column($accounts,'balance'));

$todayCashSales = $db->query("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at)=CURDATE() AND status='settled' AND payment_method='Cash'")->fetch()['t'];
$todayCashExp   = $db->query("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE expense_date=CURDATE() AND payment_method='Cash'")->fetch()['t'];
$todayCard      = $db->query("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at)=CURDATE() AND status='settled' AND payment_method='Card'")->fetch()['t'];
$todayPlat      = $db->query("SELECT COALESCE(SUM(total),0) as t FROM bills WHERE DATE(created_at)=CURDATE() AND status='settled' AND payment_method IN('Uber Eats','PickMe')")->fetch()['t'];

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Cash & Banking</div>
  <div style="display:flex;gap:8px">
    <button class="btn btn-outline" onclick="openModal('modalAddAccount')">+ Add Account</button>
    <button class="btn" onclick="openModal('modalBankTxn')">+ Transaction</button>
  </div>
</div>
<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">💵</span><div class="stat-label">Today Cash Sales</div><div class="stat-value text-green"><?=fmt($todayCashSales)?></div></div>
  <div class="stat-card"><span class="stat-icon">💳</span><div class="stat-label">Today Card</div><div class="stat-value text-blue"><?=fmt($todayCard)?></div></div>
  <div class="stat-card"><span class="stat-icon">🛵</span><div class="stat-label">Today Platform</div><div class="stat-value" style="color:var(--purple)"><?=fmt($todayPlat)?></div></div>
  <div class="stat-card"><span class="stat-icon">💸</span><div class="stat-label">Today Cash Expenses</div><div class="stat-value text-red"><?=fmt($todayCashExp)?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Net Cash Today</div><div class="stat-value <?=($todayCashSales-$todayCashExp)>=0?'text-green':'text-red'?>"><?=fmt(abs($todayCashSales-$todayCashExp))?></div></div>
  <div class="stat-card"><span class="stat-icon">🏦</span><div class="stat-label">Total Bank Balance</div><div class="stat-value text-accent"><?=fmt($totalBank)?></div></div>
</div>

<!-- Bank Accounts -->
<div class="card mb-16">
  <div class="card-title">Bank Accounts</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Bank</th><th>Account No</th><th class="text-right">Balance</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($accounts as $acc): ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($acc['bank_name'])?></td>
          <td class="mono text-muted"><?=htmlspecialchars($acc['account_no']??'—')?></td>
          <td class="text-right mono text-green fw-700" style="font-size:16px"><?=fmt($acc['balance'])?></td>
          <td>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this bank account and ALL its transactions?')">
              <input type="hidden" name="action" value="delete_account">
              <input type="hidden" name="account_id" value="<?=$acc['id']?>">
              <button class="btn btn-sm btn-red">🗑 Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="2"><strong>Total</strong></td><td class="text-right mono text-accent"><strong><?=fmt($totalBank)?></strong></td><td></td></tr></tfoot>
    </table>
  </div>
</div>

<!-- Transactions -->
<div class="card">
  <div class="card-title">Bank Transaction History</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Bank</th><th>Type</th><th>Description</th><th>Reference</th><th class="text-right">Amount</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($txns as $t): ?>
        <tr>
          <td class="mono fs-12"><?=date('d/m/Y',strtotime($t['txn_date']))?></td>
          <td><?=htmlspecialchars($t['bank_name'])?></td>
          <td><span class="badge <?=$t['type']==='deposit'?'badge-green':'badge-red'?>"><?=ucfirst($t['type'])?></span></td>
          <td><?=htmlspecialchars($t['description']??'—')?></td>
          <td class="mono text-muted fs-12"><?=htmlspecialchars($t['reference']??'—')?></td>
          <td class="text-right mono fw-700 <?=$t['type']==='deposit'?'text-green':'text-red'?>"><?=fmt($t['amount'])?></td>
          <td>
            <div style="display:flex;gap:5px">
              <?php $tedit=["id"=>$t["id"],"account_id"=>$t["account_id"],"txn_date"=>$t["txn_date"],"type"=>$t["type"],"amount"=>$t["amount"],"description"=>$t["description"]??"","reference"=>$t["reference"]??""]; ?>
              <button class="btn btn-sm btn-outline" onclick='openEditTxn(<?=json_encode($tedit)?>)'>✏</button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this transaction? Balance will be reversed.')">
                <input type="hidden" name="action" value="delete_txn">
                <input type="hidden" name="txn_id" value="<?=$t['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($txns)): ?><tr><td colspan="7" class="text-center text-muted">No transactions yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal-overlay" id="modalBankTxn">
  <div class="modal-box">
    <div class="modal-title" id="txnModalTitle">Add Bank Transaction</div>
    <form method="POST">
      <input type="hidden" name="action" id="txnAction" value="add_txn">
      <input type="hidden" name="txn_id" id="txnId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Account</label>
          <select name="account_id" id="txnAccount" class="form-control">
            <?php foreach($accounts as $a): ?><option value="<?=$a['id']?>"><?=htmlspecialchars($a['bank_name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Date</label><input type="date" name="txn_date" id="txnDate" class="form-control" value="<?=date('Y-m-d')?>"></div>
        <div class="form-group"><label class="form-label">Type</label>
          <select name="type" id="txnType" class="form-control"><option value="deposit">Deposit</option><option value="withdrawal">Withdrawal</option><option value="transfer">Transfer</option></select>
        </div>
        <div class="form-group"><label class="form-label">Amount (Rs.)</label><input type="number" name="amount" id="txnAmount" step="0.01" class="form-control" required></div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Description</label><input name="description" id="txnDesc" class="form-control"></div>
        <div class="form-group"><label class="form-label">Reference</label><input name="reference" id="txnRef" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn" id="txnSubmitBtn">Save Transaction</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalBankTxn');resetTxnForm()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Account Modal -->
<div class="modal-overlay" id="modalAddAccount">
  <div class="modal-box">
    <div class="modal-title">Add Bank Account</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_account">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Bank Name *</label><input name="bank_name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Account No</label><input name="account_no" class="form-control" placeholder="e.g. ****1234"></div>
        <div class="form-group"><label class="form-label">Opening Balance (Rs.)</label><input type="number" name="balance" class="form-control" step="0.01" value="0"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Add Account</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAddAccount')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditTxn(t) {
  document.getElementById('txnModalTitle').textContent = 'Edit Transaction';
  document.getElementById('txnAction').value   = 'edit_txn';
  document.getElementById('txnId').value       = t.id;
  document.getElementById('txnAccount').value  = t.account_id;
  document.getElementById('txnDate').value     = t.txn_date;
  document.getElementById('txnType').value     = t.type;
  document.getElementById('txnAmount').value   = t.amount;
  document.getElementById('txnDesc').value     = t.description || '';
  document.getElementById('txnRef').value      = t.reference || '';
  document.getElementById('txnSubmitBtn').textContent = 'Save Changes';
  openModal('modalBankTxn');
}
function resetTxnForm() {
  document.getElementById('txnModalTitle').textContent = 'Add Bank Transaction';
  document.getElementById('txnAction').value = 'add_txn';
  document.getElementById('txnId').value     = '';
  document.getElementById('txnSubmitBtn').textContent = 'Save Transaction';
}
</script>
<?php include '../includes/footer.php'; ?>
