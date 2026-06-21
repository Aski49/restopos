<?php
require_once '../includes/config.php';
requireAccess('menu');
$db = getDB();
$pageTitle = 'Menu Manager'; $activePage = 'menu';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO menu_items(category_id,name,price,description) VALUES(?,?,?,?)")
           ->execute([$_POST['category_id'],$_POST['name'],$_POST['price'],$_POST['description']??'']);
        $msg = ['type'=>'success','text'=>'Menu item added.'];
    } elseif ($action === 'edit') {
        $db->prepare("UPDATE menu_items SET category_id=?,name=?,price=?,description=? WHERE id=?")
           ->execute([$_POST['category_id'],$_POST['name'],$_POST['price'],$_POST['description']??'',$_POST['item_id']]);
        $msg = ['type'=>'success','text'=>'Item updated successfully.'];
    } elseif ($action === 'delete_item') {
        $db->prepare("DELETE FROM menu_items WHERE id=?")->execute([$_POST['item_id']]);
        $msg = ['type'=>'success','text'=>'Item deleted.'];
    } elseif ($action === 'toggle') {
        $db->prepare("UPDATE menu_items SET active=NOT active WHERE id=?")->execute([$_POST['item_id']]);
        $msg = ['type'=>'success','text'=>'Status changed.'];
    } elseif ($action === 'add_cat') {
        $db->prepare("INSERT INTO menu_categories(name) VALUES(?)")->execute([$_POST['cat_name']]);
        $msg = ['type'=>'success','text'=>'Category added.'];
    }
}

$cats  = $db->query("SELECT * FROM menu_categories ORDER BY sort_order,name")->fetchAll();
$items = $db->query("SELECT mi.*,mc.name as cat_name FROM menu_items mi JOIN menu_categories mc ON mi.category_id=mc.id ORDER BY mc.sort_order,mi.name")->fetchAll();

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">Menu Manager</div>
  <div style="display:flex;gap:10px">
    <a href="../online_menu.php" target="_blank" class="btn btn-outline">🌐 View Online Menu</a>
    <button class="btn btn-outline" onclick="openModal('modalAddCat')">+ Category</button>
    <button class="btn" onclick="openModal('modalAddItem')">+ Menu Item</button>
  </div>
</div>
<div class="alert alert-info fs-12 mb-12">🌐 Share your public menu with customers: <strong><?= htmlspecialchars(getSetting('business_name','RestoPOS')) ?></strong> menu is live at <code>/online_menu.php</code> — print this as a QR code for tables.</div>
<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🍽</span><div class="stat-label">Total Items</div><div class="stat-value text-blue"><?=count($items)?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Active</div><div class="stat-value text-green"><?=count(array_filter($items,fn($i)=>$i['active']))?></div></div>
  <div class="stat-card"><span class="stat-icon">📂</span><div class="stat-label">Categories</div><div class="stat-value text-accent"><?=count($cats)?></div></div>
</div>

<div class="card">
  <div class="card-title">Menu Items</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Item Name</th><th>Category</th><th class="text-right">Price</th><th>Description</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $i): ?>
        <tr style="opacity:<?=$i['active']?1:.55?>">
          <td class="fw-700"><?=htmlspecialchars($i['name'])?></td>
          <td><span class="badge badge-blue"><?=htmlspecialchars($i['cat_name'])?></span></td>
          <td class="text-right mono text-accent fw-700">Rs. <?=number_format($i['price'],2)?></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($i['description']??'—')?></td>
          <td><span class="badge <?=$i['active']?'badge-green':'badge-red'?>"><?=$i['active']?'Active':'Inactive'?></span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <!-- FIX: use data-* attributes instead of json_encode in onclick -->
              <button class="btn btn-sm btn-outline"
                data-id="<?=$i['id']?>"
                data-name="<?=htmlspecialchars($i['name'],ENT_QUOTES)?>"
                data-cat="<?=$i['category_id']?>"
                data-price="<?=$i['price']?>"
                data-desc="<?=htmlspecialchars($i['description']??'',ENT_QUOTES)?>"
                onclick="editItem(this)">✏ Edit</button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="item_id" value="<?=$i['id']?>">
                <button class="btn btn-sm <?=$i['active']?'btn-outline-red':'btn-outline-green'?>"><?=$i['active']?'Deactivate':'Activate'?></button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this menu item?')">
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="item_id" value="<?=$i['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── ADD ITEM MODAL ── -->
<div class="modal-overlay" id="modalAddItem">
  <div class="modal-box">
    <div class="modal-title">Add Menu Item</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Item Name *</label><input name="name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Category *</label>
          <select name="category_id" class="form-control">
            <?php foreach ($cats as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Price (Rs.) *</label><input type="number" name="price" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><input name="description" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Add Item</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAddItem')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT ITEM MODAL ── -->
<div class="modal-overlay" id="modalEditItem">
  <div class="modal-box">
    <div class="modal-title">Edit Menu Item</div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="item_id" id="editItemId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Item Name *</label><input name="name" id="editName" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Category *</label>
          <select name="category_id" id="editCat" class="form-control">
            <?php foreach ($cats as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Price (Rs.) *</label><input type="number" name="price" id="editPrice" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><input name="description" id="editDesc" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Save Changes</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalEditItem')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── ADD CATEGORY MODAL ── -->
<div class="modal-overlay" id="modalAddCat">
  <div class="modal-box">
    <div class="modal-title">Add Category</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_cat">
      <div class="form-group mb-12"><label class="form-label">Category Name *</label><input name="cat_name" class="form-control" required></div>
      <div class="modal-footer">
        <button type="submit" class="btn">Add Category</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAddCat')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
// FIX: Read from data-* attributes — safe for any characters including quotes
function editItem(btn) {
  document.getElementById('editItemId').value = btn.dataset.id;
  document.getElementById('editName').value   = btn.dataset.name;
  document.getElementById('editPrice').value  = btn.dataset.price;
  document.getElementById('editDesc').value   = btn.dataset.desc;
  document.getElementById('editCat').value    = btn.dataset.cat;
  openModal('modalEditItem');
}
</script>
<?php include '../includes/footer.php'; ?>
