<?php
require_once '../includes/config.php';
requireAccess('menu');
$db = getDB();
$pageTitle = 'Menu Manager'; $activePage = 'menu';

// ── Image upload helper ───────────────────────────────────────
function handleImageUpload(array $file, ?string $oldImage = null): ?string {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 3 * 1024 * 1024) return null; // 3MB max

    $uploadDir = dirname(__DIR__) . '/assets/uploads/menu/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Delete old image if replacing
    if ($oldImage && file_exists($uploadDir . $oldImage)) {
        unlink($uploadDir . $oldImage);
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('item_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }
    return null;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $image = handleImageUpload($_FILES['image'] ?? []);
        $db->prepare("INSERT INTO menu_items(category_id,name,price,description,image) VALUES(?,?,?,?,?)")
           ->execute([$_POST['category_id'],$_POST['name'],$_POST['price'],$_POST['description']??'',$image]);
        $msg = ['type'=>'success','text'=>'Menu item added.'];

    } elseif ($action === 'edit') {
        // Get current image before updating
        $cur = $db->prepare("SELECT image FROM menu_items WHERE id=?");
        $cur->execute([$_POST['item_id']]);
        $curImage = $cur->fetchColumn();
        $image = handleImageUpload($_FILES['image'] ?? [], $curImage);
        if ($image === null) $image = $curImage; // keep existing if no new upload

        // Allow removing image
        if (!empty($_POST['remove_image'])) {
            $uploadDir = dirname(__DIR__) . '/assets/uploads/menu/';
            if ($curImage && file_exists($uploadDir . $curImage)) unlink($uploadDir . $curImage);
            $image = null;
        }

        $db->prepare("UPDATE menu_items SET category_id=?,name=?,price=?,description=?,image=? WHERE id=?")
           ->execute([$_POST['category_id'],$_POST['name'],$_POST['price'],$_POST['description']??'',$image,$_POST['item_id']]);
        $msg = ['type'=>'success','text'=>'Item updated.'];

    } elseif ($action === 'delete_item') {
        $cur = $db->prepare("SELECT image FROM menu_items WHERE id=?");
        $cur->execute([$_POST['item_id']]);
        $oldImg = $cur->fetchColumn();
        if ($oldImg) {
            $p = dirname(__DIR__) . '/assets/uploads/menu/' . $oldImg;
            if (file_exists($p)) unlink($p);
        }
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
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="../online_menu.php" target="_blank" class="btn btn-outline">🌐 Online Menu</a>
    <a href="menu_qr.php" class="btn btn-outline">📱 QR Code</a>
    <button class="btn btn-outline" onclick="openModal('modalAddCat')">+ Category</button>
    <button class="btn" onclick="openModal('modalAddItem')">+ Menu Item</button>
  </div>
</div>
<div class="alert alert-info fs-12 mb-12">🍽 Add food images to each menu item — they will appear on the online menu visible to customers.</div>
<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🍽</span><div class="stat-label">Total Items</div><div class="stat-value text-blue"><?=count($items)?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Active</div><div class="stat-value text-green"><?=count(array_filter($items,fn($i)=>$i['active']))?></div></div>
  <div class="stat-card"><span class="stat-icon">🖼</span><div class="stat-label">With Image</div><div class="stat-value text-accent"><?=count(array_filter($items,fn($i)=>!empty($i['image'])))?></div></div>
  <div class="stat-card"><span class="stat-icon">📂</span><div class="stat-label">Categories</div><div class="stat-value text-muted"><?=count($cats)?></div></div>
</div>

<div class="card">
  <div class="card-title">Menu Items</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Image</th><th>Item Name</th><th>Category</th><th class="text-right">Price</th><th>Description</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $i): ?>
        <tr style="opacity:<?=$i['active']?1:.55?>">
          <td>
            <?php if (!empty($i['image'])): ?>
              <img src="../assets/uploads/menu/<?=htmlspecialchars($i['image'])?>" alt="<?=htmlspecialchars($i['name'])?>"
                   style="width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
            <?php else: ?>
              <div style="width:52px;height:52px;border-radius:8px;border:1px dashed var(--border);display:flex;align-items:center;justify-content:center;font-size:22px;background:var(--surface)">🍽</div>
            <?php endif; ?>
          </td>
          <td class="fw-700"><?=htmlspecialchars($i['name'])?></td>
          <td><span class="badge badge-blue"><?=htmlspecialchars($i['cat_name'])?></span></td>
          <td class="text-right mono text-accent fw-700">Rs. <?=number_format($i['price'],2)?></td>
          <td class="text-muted fs-12" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=htmlspecialchars($i['description']??'—')?></td>
          <td><span class="badge <?=$i['active']?'badge-green':'badge-red'?>"><?=$i['active']?'Active':'Inactive'?></span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <button class="btn btn-sm btn-outline"
                data-id="<?=$i['id']?>"
                data-name="<?=htmlspecialchars($i['name'],ENT_QUOTES)?>"
                data-cat="<?=$i['category_id']?>"
                data-price="<?=$i['price']?>"
                data-desc="<?=htmlspecialchars($i['description']??'',ENT_QUOTES)?>"
                data-image="<?=htmlspecialchars($i['image']??'',ENT_QUOTES)?>"
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
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Item Name *</label><input name="name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Category *</label>
          <select name="category_id" class="form-control">
            <?php foreach ($cats as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Price (Rs.) *</label><input type="number" name="price" step="0.01" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><input name="description" class="form-control" placeholder="Short description for online menu"></div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Food Image <span class="text-muted">(optional — shown on online menu)</span></label>
          <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this,'addPreview')">
          <img id="addPreview" src="" alt="" style="display:none;margin-top:8px;width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
          <div class="text-muted fs-12" style="margin-top:4px">JPG, PNG or WebP · Max 3MB</div>
        </div>
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
    <form method="POST" enctype="multipart/form-data">
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
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Food Image</label>
          <div id="currentImgWrap" style="display:none;margin-bottom:8px">
            <img id="currentImg" src="" style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
            <label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;color:var(--red);cursor:pointer">
              <input type="checkbox" name="remove_image" value="1" onchange="toggleRemoveImg(this)"> Remove current image
            </label>
          </div>
          <input type="file" name="image" id="editImageInput" class="form-control" accept="image/*" onchange="previewImage(this,'editPreview')">
          <img id="editPreview" src="" alt="" style="display:none;margin-top:8px;width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
          <div class="text-muted fs-12" style="margin-top:4px">Upload a new image to replace current · Max 3MB</div>
        </div>
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
function editItem(btn) {
  document.getElementById('editItemId').value = btn.dataset.id;
  document.getElementById('editName').value   = btn.dataset.name;
  document.getElementById('editPrice').value  = btn.dataset.price;
  document.getElementById('editDesc').value   = btn.dataset.desc;
  document.getElementById('editCat').value    = btn.dataset.cat;

  var imgWrap = document.getElementById('currentImgWrap');
  var curImg  = document.getElementById('currentImg');
  var editPrev = document.getElementById('editPreview');
  editPrev.style.display = 'none'; editPrev.src = '';

  if (btn.dataset.image) {
    curImg.src = '../assets/uploads/menu/' + btn.dataset.image;
    imgWrap.style.display = '';
  } else {
    imgWrap.style.display = 'none';
  }
  openModal('modalEditItem');
}

function previewImage(input, previewId) {
  var preview = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = '';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function toggleRemoveImg(cb) {
  var imgWrap = document.getElementById('currentImgWrap');
  var editInp = document.getElementById('editImageInput');
  if (cb.checked) {
    imgWrap.querySelector('img').style.opacity = '.3';
    editInp.disabled = true;
  } else {
    imgWrap.querySelector('img').style.opacity = '1';
    editInp.disabled = false;
  }
}
</script>
<?php include '../includes/footer.php'; ?>
