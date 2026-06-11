<?php
require_once '../includes/config.php';
requireAccess('bills');
$db = getDB();
$pageTitle = 'Bill History'; $activePage = 'pos';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'void') {
        $db->prepare("UPDATE bills SET status='voided' WHERE id=?")->execute([$_POST['bill_id']]);
        $msg = ['type'=>'success','text'=>'Bill voided.'];
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM bill_items WHERE bill_id=?")->execute([$_POST['bill_id']]);
        $db->prepare("DELETE FROM bills WHERE id=?")->execute([$_POST['bill_id']]);
        $msg = ['type'=>'success','text'=>'Bill deleted permanently.'];
    } elseif ($action === 'edit') {
        $db->prepare("UPDATE bills SET order_type=?,table_no=?,payment_method=?,notes=? WHERE id=?")
           ->execute([$_POST['order_type'],$_POST['table_no'],$_POST['payment_method'],$_POST['notes']??'',$_POST['bill_id']]);
        $msg = ['type'=>'success','text'=>'Bill updated.'];
    }
}

$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');

$bills = $db->prepare("SELECT b.*, u.name as cashier FROM bills b LEFT JOIN users u ON b.created_by=u.id WHERE DATE(b.created_at) BETWEEN :f AND :t ORDER BY b.created_at DESC");
$bills->execute([':f'=>$from,':t'=>$to]);
$bills = $bills->fetchAll();
$settled = array_filter($bills, fn($b)=>$b['status']==='settled');
$total   = array_sum(array_column($settled,'total'));

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Bill History</div>
  <a href="pos.php" class="btn btn-outline">← Back to POS</a>
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
  <div class="stat-card"><span class="stat-icon">🧾</span><div class="stat-label">Total Bills</div><div class="stat-value text-blue"><?=count($bills)?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Settled Revenue</div><div class="stat-value text-green"><?=fmt($total)?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Settled</div><div class="stat-value text-green"><?=count($settled)?></div></div>
  <div class="stat-card"><span class="stat-icon">❌</span><div class="stat-label">Voided</div><div class="stat-value text-red"><?=count(array_filter($bills,fn($b)=>$b['status']==='voided'))?></div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Bill No</th><th>Date/Time</th><th>Type</th><th>Table</th><th>Method</th><th class="text-right">Total</th><th>Cashier</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($bills as $b): ?>
        <tr>
          <td class="mono text-accent"><?=htmlspecialchars($b['bill_no'])?></td>
          <td class="fs-12 text-muted"><?=date('d/m/Y H:i',strtotime($b['created_at']))?></td>
          <td><?=$b['order_type']?></td>
          <td><?=$b['table_no']??'—'?></td>
          <td><?=$b['payment_method']?></td>
          <td class="text-right mono <?=$b['status']==='voided'?'text-muted':'text-green fw-700'?>"><?=fmt($b['total'])?></td>
          <td class="fs-12 text-muted"><?=htmlspecialchars($b['cashier']??'—')?></td>
          <td><span class="badge <?=$b['status']==='settled'?'badge-green':($b['status']==='voided'?'badge-red':'badge-accent')?>"><?=ucfirst($b['status'])?></span></td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <a href="print_bill.php?id=<?=$b['id']?>" target="_blank" class="btn btn-sm btn-outline">🖨</a>
              <!-- EDIT BUTTON -->
              <button class="btn btn-sm btn-outline"
                data-id="<?=$b['id']?>"
                data-otype="<?=htmlspecialchars($b['order_type'],ENT_QUOTES)?>"
                data-table="<?=htmlspecialchars($b['table_no']??'',ENT_QUOTES)?>"
                data-method="<?=htmlspecialchars($b['payment_method'],ENT_QUOTES)?>"
                data-notes="<?=htmlspecialchars($b['notes']??'',ENT_QUOTES)?>"
                onclick="openBillEdit(this)">✏ Edit</button>
              <?php if ($b['status']!=='voided'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Void this bill?')">
                <input type="hidden" name="action" value="void">
                <input type="hidden" name="bill_id" value="<?=$b['id']?>">
                <button class="btn btn-sm btn-outline-red">⊘ Void</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Permanently DELETE bill <?=addslashes($b['bill_no'])?>?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="bill_id" value="<?=$b['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($bills)): ?><tr><td colspan="9" class="text-center text-muted">No bills for this period.</td></tr><?php endif; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="5"><strong>Total (Settled)</strong></td><td class="text-right mono text-green"><strong><?=fmt($total)?></strong></td><td colspan="3"></td></tr>
      </tfoot>
    </table>
  </div>
</div>

<!-- Edit Bill Modal -->
<div class="modal-overlay" id="modalBillEdit">
  <div class="modal-box">
    <div class="modal-title">Edit Bill</div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="bill_id" id="editBillId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group">
          <label class="form-label">Order Type</label>
          <select name="order_type" id="editOrderType" class="form-control">
            <?php foreach(['Dine-In','Takeaway','Uber Eats','PickMe','Delivery'] as $t): ?>
              <option><?=$t?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Table No</label>
          <input name="table_no" id="editTableNo" class="form-control" placeholder="e.g. T1">
        </div>
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select name="payment_method" id="editPayMethod" class="form-control">
            <?php foreach(['Cash','Card','QR','Bank Transfer','Credit','Uber Eats','PickMe'] as $m): ?>
              <option><?=$m?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <input name="notes" id="editBillNotes" class="form-control">
        </div>
      </div>
      <div class="alert alert-warning fs-12">Note: Only order type, table, payment method and notes can be edited. Amounts are fixed after billing.</div>
      <div class="modal-footer">
        <button type="submit" class="btn">Save Changes</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalBillEdit')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openBillEdit(btn) {
  document.getElementById('editBillId').value      = btn.dataset.id;
  document.getElementById('editOrderType').value   = btn.dataset.otype;
  document.getElementById('editTableNo').value     = btn.dataset.table;
  document.getElementById('editPayMethod').value   = btn.dataset.method;
  document.getElementById('editBillNotes').value   = btn.dataset.notes;
  openModal('modalBillEdit');
}
</script>
<?php include '../includes/footer.php'; ?>
