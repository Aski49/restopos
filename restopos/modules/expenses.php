<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'Expenses'; $activePage = 'expenses';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    if ($action === 'add') {
        $db->prepare("INSERT INTO expenses(expense_date,category_id,description,amount,payment_method,supplier,notes,created_by) VALUES(?,?,?,?,?,?,?,?)")
           ->execute([$_POST['expense_date'],$_POST['category_id'],$_POST['description'],$_POST['amount'],$_POST['payment_method'],$_POST['supplier']??'',$_POST['notes']??'',$_SESSION['user_id']]);
        $msg = ['type'=>'success','text'=>'Expense recorded.'];
    } elseif ($action === 'edit') {
        $db->prepare("UPDATE expenses SET expense_date=?,category_id=?,description=?,amount=?,payment_method=?,supplier=?,notes=? WHERE id=?")
           ->execute([$_POST['expense_date'],$_POST['category_id'],$_POST['description'],$_POST['amount'],$_POST['payment_method'],$_POST['supplier']??'',$_POST['notes']??'',$_POST['expense_id']]);
        $msg = ['type'=>'success','text'=>'Expense updated.'];
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM expenses WHERE id=?")->execute([$_POST['expense_id']]);
        $msg = ['type'=>'success','text'=>'Expense deleted.'];
    }
}

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$cats     = $db->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll();
$expenses = $db->prepare("SELECT e.*, ec.name as cat FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN :f AND :t ORDER BY e.expense_date DESC");
$expenses->execute([':f'=>$from,':t'=>$to]); $expenses = $expenses->fetchAll();

$total = array_sum(array_column($expenses,'amount'));
$byCat = [];
foreach ($expenses as $e) $byCat[$e['cat']] = ($byCat[$e['cat']]??0)+$e['amount'];
arsort($byCat);

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Expense Management</div>
  <button class="btn" onclick="openModal('modalExpense')">+ Record Expense</button>
</div>

<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<form method="GET" class="report-filter">
  <div class="form-group"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?=$from?>"></div>
  <div class="form-group"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?=$to?>"></div>
  <button type="submit" class="btn">Filter</button>
  <a href="?from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">Today</a>
  <a href="?from=<?=date('Y-m-01')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">This Month</a>
</form>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">💸</span><div class="stat-label">Total Expenses</div><div class="stat-value text-red"><?=fmt($total)?></div></div>
  <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-label">Transactions</div><div class="stat-value text-blue"><?=count($expenses)?></div></div>
  <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-label">Period</div><div class="stat-value mono" style="font-size:13px"><?=$from?> → <?=$to?></div></div>
</div>

<div class="grid-2 mb-16">
  <div class="card">
    <div class="card-title">By Category</div>
    <?php foreach ($byCat as $cat=>$amt): $pct=$total>0?round($amt/$total*100,1):0; ?>
    <div style="margin-bottom:14px">
      <div class="flex-between mb-8 fs-13"><span><?=htmlspecialchars($cat)?></span><span class="mono text-red"><?=fmt($amt)?></span></div>
      <div class="progress"><div class="progress-bar" style="width:<?=$pct?>%;background:var(--red)"></div></div>
      <div class="fs-12 text-muted" style="margin-top:4px"><?=$pct?>%</div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($byCat)): ?><div class="text-muted fs-13">No expenses for this period.</div><?php endif; ?>
  </div>
  <div class="card">
    <div class="card-title">By Payment Method</div>
    <?php $byM=[]; foreach($expenses as $e) $byM[$e['payment_method']]=($byM[$e['payment_method']]??0)+$e['amount'];
    foreach($byM as $m=>$amt): ?>
    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border)">
      <span class="fs-13"><?=$m?></span><span class="mono text-red"><?=fmt($amt)?></span>
    </div>
    <?php endforeach; ?>
    <?php if (empty($byM)): ?><div class="text-muted fs-13">No data.</div><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-title">Expense Transactions</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Category</th><th>Description</th><th>Supplier</th><th>Method</th><th class="text-right">Amount</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($expenses as $e): ?>
        <tr>
          <td class="mono fs-12"><?=date('d/m/Y',strtotime($e['expense_date']))?></td>
          <td><span class="badge badge-muted"><?=htmlspecialchars($e['cat'])?></span></td>
          <td><?=htmlspecialchars($e['description'])?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($e['supplier']??'—')?></td>
          <td><?=$e['payment_method']?></td>
          <td class="text-right mono text-red fw-700"><?=fmt($e['amount'])?></td>
          <td>
            <div style="display:flex;gap:5px">
              <?php $eedit=["id"=>$e["id"],"expense_date"=>$e["expense_date"],"category_id"=>$e["category_id"],"description"=>$e["description"],"amount"=>$e["amount"],"payment_method"=>$e["payment_method"],"supplier"=>$e["supplier"]??"","notes"=>$e["notes"]??""]; ?>
              <button class="btn btn-sm btn-outline" onclick='openExpEdit(<?=json_encode($eedit)?>)'>
                ✏
              </button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this expense?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="expense_id" value="<?=$e['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($expenses)): ?><tr><td colspan="7" class="text-center text-muted">No expenses for this period.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><tr><td colspan="5"><strong>Total</strong></td><td class="text-right mono text-red"><strong><?=fmt($total)?></strong></td><td></td></tr></tfoot>
    </table>
  </div>
</div>

<!-- Add Expense Modal -->
<div class="modal-overlay" id="modalExpense">
  <div class="modal-box">
    <div class="modal-title">Record Expense</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="<?=date('Y-m-d')?>" required></div>
        <div class="form-group"><label class="form-label">Category</label>
          <select name="category_id" class="form-control">
            <?php foreach ($cats as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Description</label><input name="description" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Amount (Rs.)</label><input type="number" name="amount" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Payment Method</label>
          <select name="payment_method" class="form-control"><option>Cash</option><option>Card</option><option>Bank Transfer</option></select>
        </div>
        <div class="form-group"><label class="form-label">Supplier</label><input name="supplier" class="form-control"></div>
        <div class="form-group"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Save Expense</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalExpense')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal-overlay" id="modalExpEdit">
  <div class="modal-box">
    <div class="modal-title">Edit Expense</div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="expense_id" id="editExpId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Date</label><input type="date" name="expense_date" id="editExpDate" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Category</label>
          <select name="category_id" id="editExpCat" class="form-control">
            <?php foreach ($cats as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Description</label><input name="description" id="editExpDesc" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Amount (Rs.)</label><input type="number" name="amount" id="editExpAmt" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Payment Method</label>
          <select name="payment_method" id="editExpMethod" class="form-control">
            <option>Cash</option><option>Card</option><option>Bank Transfer</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Supplier</label><input name="supplier" id="editExpSupplier" class="form-control"></div>
        <div class="form-group"><label class="form-label">Notes</label><input name="notes" id="editExpNotes" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Save Changes</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalExpEdit')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openExpEdit(e) {
  document.getElementById('editExpId').value       = e.id;
  document.getElementById('editExpDate').value     = e.expense_date;
  document.getElementById('editExpCat').value      = e.category_id;
  document.getElementById('editExpDesc').value     = e.description;
  document.getElementById('editExpAmt').value      = e.amount;
  document.getElementById('editExpMethod').value   = e.payment_method;
  document.getElementById('editExpSupplier').value = e.supplier || '';
  document.getElementById('editExpNotes').value    = e.notes || '';
  openModal('modalExpEdit');
}
</script>
<?php include '../includes/footer.php'; ?>
