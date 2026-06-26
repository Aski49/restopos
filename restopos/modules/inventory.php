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
        $adjustQty = (float)$_POST['adjust_qty'];
        $db->prepare("UPDATE inventory_items SET qty=GREATEST(0,qty+?) WHERE id=?")->execute([$adjustQty,$_POST['item_id']]);
        // Log negative manual adjustments (stock going OUT) into the usage log too,
        // so manual corrections/wastage also show up in the Usage Report.
        if ($adjustQty < 0) {
            $db->prepare("INSERT INTO stock_usage(item_id,usage_date,qty,source,notes) VALUES(?,?,?,?,?)")
               ->execute([$_POST['item_id'], date('Y-m-d'), abs($adjustQty), 'manual', 'Manual stock adjustment']);
        }
        $msg = ['type'=>'success','text'=>'Stock adjusted.'];
    } elseif ($action === 'purchase') {
        $db->beginTransaction();
        $cost = $_POST['qty'] * $_POST['unit_cost'];
        $db->prepare("INSERT INTO stock_purchases(purchase_date,item_id,qty,unit_cost,total_cost,supplier,invoice_no,payment_method,created_by) VALUES(?,?,?,?,?,?,?,?,?)")
           ->execute([date('Y-m-d'),$_POST['item_id'],$_POST['qty'],$_POST['unit_cost'],$cost,$_POST['supplier']??'',$_POST['invoice_no']??'',$_POST['payment_method'],$_SESSION['user_id']]);
        $db->prepare("UPDATE inventory_items SET qty=qty+?, unit_cost=? WHERE id=?")->execute([$_POST['qty'],$_POST['unit_cost'],$_POST['item_id']]);
        $db->commit();
        $msg = ['type'=>'success','text'=>'Purchase recorded. Stock updated.'];

    // ── Recipe management ─────────────────────────────────────
    } elseif ($action === 'save_recipe_line') {
        $menuItemId = (int)$_POST['menu_item_id'];
        $invItemId  = (int)$_POST['inventory_item_id'];
        $qtyPerUnit = (float)$_POST['qty_per_unit'];
        if ($qtyPerUnit > 0) {
            $db->prepare("INSERT INTO menu_item_recipes(menu_item_id,inventory_item_id,qty_per_unit) VALUES(?,?,?)
                          ON DUPLICATE KEY UPDATE qty_per_unit=VALUES(qty_per_unit)")
               ->execute([$menuItemId,$invItemId,$qtyPerUnit]);
            $msg = ['type'=>'success','text'=>'Recipe ingredient saved.'];
        } else {
            $db->prepare("DELETE FROM menu_item_recipes WHERE menu_item_id=? AND inventory_item_id=?")
               ->execute([$menuItemId,$invItemId]);
            $msg = ['type'=>'success','text'=>'Recipe ingredient removed.'];
        }
    } elseif ($action === 'delete_recipe_line') {
        $db->prepare("DELETE FROM menu_item_recipes WHERE id=?")->execute([$_POST['recipe_id']]);
        $msg = ['type'=>'success','text'=>'Recipe ingredient removed.'];
    }
}

$cats  = $db->query("SELECT * FROM inventory_categories ORDER BY name")->fetchAll();
$items = $db->query("SELECT ii.*, ic.name as cat FROM inventory_items ii JOIN inventory_categories ic ON ii.category_id=ic.id ORDER BY ic.name,ii.name")->fetchAll();
$low   = array_filter($items, fn($i)=>$i['qty']<=$i['min_qty']);
$totalValue = array_sum(array_map(fn($i)=>$i['qty']*$i['unit_cost'], $items));

$tab = $_GET['tab'] ?? 'stock';

// ── Menu items + their current recipe (for Recipes tab) ───────
$menuItems = $db->query("SELECT mi.*, mc.name as cat_name FROM menu_items mi JOIN menu_categories mc ON mi.category_id=mc.id WHERE mi.active=1 ORDER BY mc.name, mi.name")->fetchAll();
$recipeRows = $db->query("SELECT r.*, mi.name as menu_name, ii.name as inv_name, ii.unit as inv_unit
    FROM menu_item_recipes r
    JOIN menu_items mi ON r.menu_item_id=mi.id
    JOIN inventory_items ii ON r.inventory_item_id=ii.id
    ORDER BY mi.name, ii.name")->fetchAll();
$recipesByMenuItem = [];
foreach ($recipeRows as $rr) { $recipesByMenuItem[$rr['menu_item_id']][] = $rr; }

// ── Usage Report (filterable by date range / month / year) ────
$usageFrom  = $_GET['from']  ?? date('Y-m-01');
$usageTo    = $_GET['to']    ?? date('Y-m-d');
$usageGroupBy = $_GET['group'] ?? 'item'; // item | day | month

$usageStmt = $db->prepare("SELECT su.*, ii.name as item_name, ii.unit as item_unit, ii.unit_cost
    FROM stock_usage su
    JOIN inventory_items ii ON su.item_id=ii.id
    WHERE su.usage_date BETWEEN ? AND ?
    ORDER BY su.usage_date DESC, su.id DESC");
$usageStmt->execute([$usageFrom, $usageTo]);
$usageRows = $usageStmt->fetchAll();

// Summary: total qty used per item within the filtered range
$usageByItem = [];
foreach ($usageRows as $u) {
    $key = $u['item_id'];
    if (!isset($usageByItem[$key])) {
        $usageByItem[$key] = ['name'=>$u['item_name'],'unit'=>$u['item_unit'],'qty'=>0,'cost'=>0,'count'=>0];
    }
    $usageByItem[$key]['qty']   += $u['qty'];
    $usageByItem[$key]['cost']  += $u['qty'] * $u['unit_cost'];
    $usageByItem[$key]['count']++;
}
uasort($usageByItem, fn($a,$b) => $b['qty'] <=> $a['qty']);
$totalUsageCost = array_sum(array_column($usageByItem, 'cost'));

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

<div class="tab-bar mb-16">
  <a href="?tab=stock" class="tab-btn <?=$tab==='stock'?'active':''?>">📦 Stock Register</a>
  <a href="?tab=recipes" class="tab-btn <?=$tab==='recipes'?'active':''?>">🍳 Recipes</a>
  <a href="?tab=usage" class="tab-btn <?=$tab==='usage'?'active':''?>">📉 Usage Report</a>
</div>

<?php if ($tab === 'stock'): ?>

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

<?php elseif ($tab === 'recipes'): ?>
<!-- ═══ RECIPES TAB ═══ -->
<div class="alert alert-info mb-16">
  🍳 Define how much of each inventory item is consumed every time a menu item is sold. Once a recipe is set, settling a bill in POS will automatically deduct the matching quantity from stock — no manual entry needed.
</div>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🍽</span><div class="stat-label">Menu Items</div><div class="stat-value text-blue"><?=count($menuItems)?></div></div>
  <div class="stat-card"><span class="stat-icon">🍳</span><div class="stat-label">With Recipe Defined</div><div class="stat-value text-green"><?=count($recipesByMenuItem)?></div></div>
  <div class="stat-card"><span class="stat-icon">⚠</span><div class="stat-label">No Recipe Yet</div><div class="stat-value text-red"><?=count($menuItems)-count($recipesByMenuItem)?></div></div>
</div>

<div class="card">
  <div class="card-title">Menu Item Recipes</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Menu Item</th><th>Category</th><th>Ingredients (Inventory Item — Qty per Unit Sold)</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($menuItems as $mi): $hasRecipe = isset($recipesByMenuItem[$mi['id']]); ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($mi['name'])?></td>
          <td><span class="badge badge-blue"><?=htmlspecialchars($mi['cat_name'])?></span></td>
          <td>
            <?php if ($hasRecipe): ?>
              <?php foreach ($recipesByMenuItem[$mi['id']] as $rline): ?>
                <span class="badge badge-muted" style="margin:2px 3px 2px 0">
                  <?=htmlspecialchars($rline['inv_name'])?> — <?=number_format($rline['qty_per_unit'],3)?> <?=htmlspecialchars($rline['inv_unit'])?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Remove this ingredient from the recipe?')">
                    <input type="hidden" name="action" value="delete_recipe_line">
                    <input type="hidden" name="recipe_id" value="<?=$rline['id']?>">
                    <button type="submit" style="border:none;background:none;color:var(--red);cursor:pointer;font-weight:700;margin-left:4px">×</button>
                  </form>
                </span>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="text-muted fs-12">No ingredients set</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-sm btn-outline"
              data-menu-id="<?=$mi['id']?>"
              data-menu-name="<?=htmlspecialchars($mi['name'],ENT_QUOTES)?>"
              onclick="openRecipeModal(this)">+ Add Ingredient</button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($menuItems)): ?><tr><td colspan="4" class="text-center text-muted">No active menu items found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<!-- ═══ USAGE REPORT TAB ═══ -->
<div class="card mb-16" style="padding:14px 16px">
  <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="tab" value="usage">
    <div class="form-group"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?=$usageFrom?>"></div>
    <div class="form-group"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?=$usageTo?>"></div>
    <button type="submit" class="btn btn-sm">Filter</button>
    <a href="?tab=usage&from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">Today</a>
    <a href="?tab=usage&from=<?=date('Y-m-01')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">This Month</a>
    <a href="?tab=usage&from=<?=date('Y-01-01')?>&to=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">This Year</a>
    <a href="print_inventory_usage.php?from=<?=urlencode($usageFrom)?>&to=<?=urlencode($usageTo)?>" target="_blank" class="btn btn-sm btn-green">🖨 Print / PDF</a>
  </form>
</div>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">📉</span><div class="stat-label">Total Usage Events</div><div class="stat-value text-blue"><?=count($usageRows)?></div></div>
  <div class="stat-card"><span class="stat-icon">📦</span><div class="stat-label">Distinct Items Used</div><div class="stat-value text-accent"><?=count($usageByItem)?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Total Usage Cost</div><div class="stat-value text-red"><?=fmt($totalUsageCost)?></div></div>
</div>

<div class="card mb-16">
  <div class="card-title">Usage Summary by Item <span class="badge badge-blue"><?=$usageFrom?> → <?=$usageTo?></span></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Item</th><th class="text-right">Total Qty Used</th><th class="text-right">Times Used</th><th class="text-right">Est. Cost</th></tr></thead>
      <tbody>
      <?php foreach ($usageByItem as $u): ?>
        <tr>
          <td class="fw-700"><?=htmlspecialchars($u['name'])?></td>
          <td class="text-right mono text-red fw-700"><?=number_format($u['qty'],3)?> <?=htmlspecialchars($u['unit'])?></td>
          <td class="text-right mono text-blue"><?=$u['count']?></td>
          <td class="text-right mono text-accent fw-700"><?=fmt($u['cost'])?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($usageByItem)): ?><tr><td colspan="4" class="text-center text-muted">No stock usage recorded for this period.</td></tr><?php endif; ?>
      </tbody>
      <?php if (!empty($usageByItem)): ?>
      <tfoot><tr><td><strong>TOTAL</strong></td><td></td><td></td><td class="text-right mono text-red"><strong><?=fmt($totalUsageCost)?></strong></td></tr></tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-title">Usage — Event by Event</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Date</th><th>Item</th><th class="text-right">Qty Used</th><th>Source</th><th>Detail</th></tr></thead>
      <tbody>
      <?php foreach ($usageRows as $u): ?>
        <tr>
          <td class="mono fs-12 text-muted"><?=date('d/m/Y',strtotime($u['usage_date']))?></td>
          <td class="fw-700"><?=htmlspecialchars($u['item_name'])?></td>
          <td class="text-right mono text-red"><?=number_format($u['qty'],3)?> <?=htmlspecialchars($u['item_unit'])?></td>
          <td><span class="badge <?=$u['source']==='bill'?'badge-green':'badge-accent'?>"><?=$u['source']==='bill'?'Sale':'Manual'?></span></td>
          <td class="text-muted fs-12"><?=htmlspecialchars($u['menu_item_name'] ?: ($u['notes'] ?: '—'))?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($usageRows)): ?><tr><td colspan="5" class="text-center text-muted">No stock usage records for this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

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
          <select name="unit" id="itemUnit" class="form-control">
            <option>kg</option><option>g</option><option>mg</option>
            <option>L</option><option>ml</option>
            <option>packs</option><option>packets</option><option>boxes</option><option>dozen</option>
            <option>units</option><option>pieces</option><option>bottles</option><option>cylinders</option><option>bags</option>
          </select>
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

<!-- Add Recipe Ingredient Modal -->
<div class="modal-overlay" id="modalRecipe">
  <div class="modal-box">
    <div class="modal-title" id="recipeModalTitle">Add Ingredient</div>
    <form method="POST">
      <input type="hidden" name="action" value="save_recipe_line">
      <input type="hidden" name="menu_item_id" id="recipeMenuId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Menu Item</label>
          <input type="text" id="recipeMenuName" class="form-control" disabled>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Inventory Item (ingredient) *</label>
          <select name="inventory_item_id" class="form-control" required>
            <?php foreach($items as $i): ?><option value="<?=$i['id']?>"><?=htmlspecialchars($i['name'])?> (<?=htmlspecialchars($i['unit'])?>)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Quantity Used per 1 Unit Sold *</label>
          <input type="number" name="qty_per_unit" step="0.0001" min="0.0001" class="form-control" required placeholder="e.g. 0.3 (kg of chicken per Chicken Kottu sold)">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Save Ingredient</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalRecipe')">Cancel</button>
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

function openRecipeModal(btn) {
  document.getElementById('recipeModalTitle').textContent = 'Add Ingredient — ' + btn.dataset.menuName;
  document.getElementById('recipeMenuId').value   = btn.dataset.menuId;
  document.getElementById('recipeMenuName').value = btn.dataset.menuName;
  openModal('modalRecipe');
}
</script>
<?php include '../includes/footer.php'; ?>
