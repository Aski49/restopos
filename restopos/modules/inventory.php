<?php
require_once '../includes/config.php';
requireAccess('inventory');
$db = getDB();
$pageTitle = 'Inventory'; $activePage = 'inventory';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_item') {
        $db->prepare("INSERT INTO inventory_items(category_id,name,unit,qty,min_qty,unit_cost) VALUES(?,?,?,?,?,?)")
           ->execute([$_POST['category_id'],$_POST['name'],$_POST['unit'],$_POST['qty'],$_POST['min_qty'],$_POST['unit_cost']]);
        $msg = ['type'=>'success','text'=>'Item added.'];
    } elseif ($action === 'edit_item') {
        $db->prepare("UPDATE inventory_items SET category_id=?,name=?,unit=?,qty=?,min_qty=?,unit_cost=? WHERE id=?")
           ->execute([$_POST['category_id'],$_POST['name'],$_POST['unit'],$_POST['qty'],$_POST['min_qty'],$_POST['unit_cost'],$_POST['item_id']]);
        $msg = ['type'=>'success','text'=>'Item updated.'];
    } elseif ($action === 'delete_item') {
        $db->prepare("DELETE FROM inventory_items WHERE id=?")->execute([$_POST['item_id']]);
        $msg = ['type'=>'success','text'=>'Item deleted.'];
    } elseif ($action === 'adjust') {
        $db->prepare("UPDATE inventory_items SET qty=GREATEST(0,qty+?) WHERE id=?")->execute([$_POST['adjust_qty'],$_POST['item_id']]);
        $msg = ['type'=>'success','text'=>'Stock adjusted.'];
    } elseif ($action === 'purchase') {
        $db->beginTransaction();
        $cost = $_POST['qty'] * $_POST['unit_cost'];
        $db->prepare("INSERT INTO stock_purchases(purchase_date,item_id,qty,unit_cost,total_cost,supplier,invoice_no,payment_method,created_by) VALUES(?,?,?,?,?,?,?,?,?)")
           ->execute([date('Y-m-d'),$_POST['item_id'],$_POST['qty'],$_POST['unit_cost'],$cost,$_POST['supplier']??'',$_POST['invoice_no']??'',$_POST['payment_method'],$_SESSION['user_id']]);
        $db->prepare("UPDATE inventory_items SET qty=qty+?, unit_cost=? WHERE id=?")->execute([$_POST['qty'],$_POST['unit_cost'],$_POST['item_id']]);
        $db->commit();
        $msg = ['type'=>'success','text'=>'Purchase recorded. Stock updated.'];
    }
}

$cats  = $db->query("SELECT * FROM inventory_categories ORDER BY name")->fetchAll();
$items = $db->query("SELECT ii.*, ic.name as cat FROM inventory_items ii JOIN inventory_categories ic ON ii.category_id=ic.id ORDER BY ic.name,ii.name")->fetchAll();
$low   = array_filter($items, fn($i)=>$i['qty']<=$i['min_qty']);
$totalValue = array_sum(array_map(fn($i)=>$i['qty']*$i['unit_cost'], $items));

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Inventory Management</div>
  <div style="display:flex;gap:8px">
    <button class="btn btn-outline" onclick="openModal('modalPurchase')">📥 Record Purchase</button>
    <button class="btn" onclick="openModal('modalAddItem')">+ Add Item</button>
  </div>
</div>
<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">📦</span><div class="stat-label">Total Items</div><div class="stat-value text-blue"><?=count($items)?></div></div>
  <div class="stat-card"><span class="stat-icon">⚠</span><div class="stat-label">Low Stock</div><div class="stat-value text-red"><?=count($low)?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Stock Value</div><div class="stat-value text-green"><?=fmt($totalValue)?></div></div>
</div>

<div class="card">
  <div class="card-title">Stock Register</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Item</th><th>Category</th><th>Unit</th><th class="text-right">Qty</th><th class="text-right">Min Qty</th><th class="text-right">Unit Cost</th><th class="text-right">Total Value</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $i): $isLow=($i['qty']<=$i['min_qty']); ?>
        <tr style="<?=$isLow?'background:rgba(239,68,68,.04)':''?>">
          <td class="fw-700"><?=htmlspecialchars($i['name'])?></td>
          <td><span class="badge badge-blue"><?=htmlspecialchars($i['cat'])?></span></td>
          <td><?=$i['unit']?></td>
          <td class="text-right mono <?=$isLow?'text-red fw-700':'text-green'?>"><?=number_format($i['qty'],2)?></td>
          <td class="text-right mono text-muted"><?=number_format($i['min_qty'],2)?></td>
          <td class="text-right mono"><?=fmt($i['unit_cost'])?></td>
          <td class="text-right mono text-accent fw-700"><?=fmt($i['qty']*$i['unit_cost'])?></td>
          <td><span class="badge <?=$isLow?'badge-red':'badge-green'?>"><?=$isLow?'LOW':'OK'?></span></td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <!-- Quick adjust -->
              <form method="POST" style="display:inline-flex;gap:4px;align-items:center">
                <input type="hidden" name="action" value="adjust">
                <input type="hidden" name="item_id" value="<?=$i['id']?>">
                <input type="number" name="adjust_qty" step="0.01" placeholder="+/-" class="form-control" style="width:65px;padding:4px 6px;font-size:12px">
                <button class="btn btn-sm btn-outline">±</button>
              </form>
              <button class="btn btn-sm btn-outline"
                data-id="<?=$i['id']?>"
                data-name="<?=htmlspecialchars($i['name'],ENT_QUOTES)?>"
                data-cat="<?=$i['category_id']?>"
                data-unit="<?=htmlspecialchars($i['unit'],ENT_QUOTES)?>"
                data-qty="<?=$i['qty']?>"
                data-min="<?=$i['min_qty']?>"
                data-cost="<?=$i['unit_cost']?>"
                onclick="openEditItem(this)">✏</button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?=htmlspecialchars($i['name'])?>?')">
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="item_id" value="<?=$i['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?><tr><td colspan="9" class="text-center text-muted">No inventory items.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Item Modal -->
<div class="modal-overlay" id="modalAddItem">
  <div class="modal-box">
    <div class="modal-title" id="itemModalTitle">Add Stock Item</div>
    <form method="POST">
      <input type="hidden" name="action" id="itemAction" value="add_item">
      <input type="hidden" name="item_id" id="itemId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Item Name *</label><input name="name" id="itemName" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Category</label>
          <select name="category_id" id="itemCat" class="form-control">
            <?php foreach($cats as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Unit</label>
          <select name="unit" id="itemUnit" class="form-control"><option>kg</option><option>L</option><option>packs</option><option>units</option><option>cylinders</option><option>bags</option></select>
        </div>
        <div class="form-group"><label class="form-label">Current Qty</label><input type="number" name="qty" id="itemQty" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Min Qty (alert level)</label><input type="number" name="min_qty" id="itemMinQty" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Unit Cost (Rs.)</label><input type="number" name="unit_cost" id="itemCost" step="0.01" class="form-control" required></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn" id="itemSubmitBtn">Save Item</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAddItem')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Purchase Modal -->
<div class="modal-overlay" id="modalPurchase">
  <div class="modal-box">
    <div class="modal-title">Record Stock Purchase</div>
    <form method="POST">
      <input type="hidden" name="action" value="purchase">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Item</label>
          <select name="item_id" class="form-control">
            <?php foreach($items as $i): ?><option value="<?=$i['id']?>"><?=htmlspecialchars($i['name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Qty</label><input type="number" name="qty" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Unit Cost (Rs.)</label><input type="number" name="unit_cost" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Supplier</label><input name="supplier" class="form-control"></div>
        <div class="form-group"><label class="form-label">Invoice No</label><input name="invoice_no" class="form-control"></div>
        <div class="form-group"><label class="form-label">Payment</label>
          <select name="payment_method" class="form-control"><option>Cash</option><option>Card</option><option>Bank Transfer</option><option>Credit</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-green">Record Purchase</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalPurchase')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditItem(btn) {
  document.getElementById('itemModalTitle').textContent = 'Edit Item';
  document.getElementById('itemAction').value    = 'edit_item';
  document.getElementById('itemId').value        = btn.dataset.id;
  document.getElementById('itemName').value      = btn.dataset.name;
  document.getElementById('itemCat').value       = btn.dataset.cat;
  document.getElementById('itemUnit').value      = btn.dataset.unit;
  document.getElementById('itemQty').value       = btn.dataset.qty;
  document.getElementById('itemMinQty').value    = btn.dataset.min;
  document.getElementById('itemCost').value      = btn.dataset.cost;
  document.getElementById('itemSubmitBtn').textContent = 'Save Changes';
  openModal('modalAddItem');
}
</script>
<?php include '../includes/footer.php'; ?>
