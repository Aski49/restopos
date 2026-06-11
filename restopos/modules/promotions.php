<?php
require_once '../includes/config.php';
requireAccess('promotions');
$db = getDB();
$pageTitle = 'Promotions'; $activePage = 'promotions';

// Create tables if not exist
$db->exec("CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    promo_type ENUM('percent_off','fixed_off','buy_x_get_y','free_item') NOT NULL DEFAULT 'percent_off',
    discount_value DECIMAL(10,2) DEFAULT 0,
    buy_qty INT DEFAULT 1,
    get_qty INT DEFAULT 1,
    applies_to ENUM('all','category','item') DEFAULT 'all',
    applies_id INT DEFAULT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    valid_from DATE,
    valid_to DATE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS bill_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    promo_id INT NOT NULL,
    promo_name VARCHAR(150),
    discount_amt DECIMAL(10,2) DEFAULT 0
)");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO promotions(name,description,promo_type,discount_value,buy_qty,get_qty,applies_to,applies_id,min_order_amount,valid_from,valid_to,active)
                      VALUES(?,?,?,?,?,?,?,?,?,?,?,1)")
           ->execute([$_POST['name'],$_POST['description']??'',$_POST['promo_type'],$_POST['discount_value']??0,$_POST['buy_qty']??1,$_POST['get_qty']??1,$_POST['applies_to']??'all',$_POST['applies_id']??null,$_POST['min_order_amount']??0,$_POST['valid_from']??null,$_POST['valid_to']??null]);
        $msg = ['type'=>'success','text'=>'Promotion added.'];
    } elseif ($action === 'edit') {
        $db->prepare("UPDATE promotions SET name=?,description=?,promo_type=?,discount_value=?,buy_qty=?,get_qty=?,applies_to=?,applies_id=?,min_order_amount=?,valid_from=?,valid_to=? WHERE id=?")
           ->execute([$_POST['name'],$_POST['description']??'',$_POST['promo_type'],$_POST['discount_value']??0,$_POST['buy_qty']??1,$_POST['get_qty']??1,$_POST['applies_to']??'all',$_POST['applies_id']??null,$_POST['min_order_amount']??0,$_POST['valid_from']??null,$_POST['valid_to']??null,$_POST['promo_id']]);
        $msg = ['type'=>'success','text'=>'Promotion updated.'];
    } elseif ($action === 'toggle') {
        $db->prepare("UPDATE promotions SET active=NOT active WHERE id=?")->execute([$_POST['promo_id']]);
        $msg = ['type'=>'success','text'=>'Status changed.'];
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM promotions WHERE id=?")->execute([$_POST['promo_id']]);
        $msg = ['type'=>'success','text'=>'Promotion deleted.'];
    }
}

$promos = $db->query("SELECT * FROM promotions ORDER BY active DESC, created_at DESC")->fetchAll();
$cats   = $db->query("SELECT * FROM menu_categories WHERE active=1 ORDER BY name")->fetchAll();
$items  = $db->query("SELECT * FROM menu_items WHERE active=1 ORDER BY name")->fetchAll();

$typeLabels = [
    'percent_off' => '% Off',
    'fixed_off'   => 'Fixed Rs. Off',
    'buy_x_get_y' => 'Buy X Get Y',
    'free_item'   => 'Free Item',
];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-title">Promotions</div>
  <button class="btn" onclick="openModal('modalAddPromo')">+ Add Promotion</button>
</div>

<?php if ($msg): ?><div class="alert alert-<?=$msg['type']?>"><?=htmlspecialchars($msg['text'])?></div><?php endif; ?>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🎉</span><div class="stat-label">Total Promos</div><div class="stat-value text-blue"><?=count($promos)?></div></div>
  <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-label">Active</div><div class="stat-value text-green"><?=count(array_filter($promos,fn($p)=>$p['active']))?></div></div>
  <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-label">Valid Today</div><div class="stat-value text-accent"><?=count(array_filter($promos,fn($p)=>$p['active']&&(!$p['valid_from']||$p['valid_from']<=date('Y-m-d'))&&(!$p['valid_to']||$p['valid_to']>=date('Y-m-d'))))?></div></div>
</div>

<div class="alert alert-info mb-16">
  💡 Active promotions automatically appear in the <strong>POS Billing</strong> screen. Cashiers can apply them with one click and they appear on the printed receipt.
</div>

<div class="card">
  <div class="card-title">All Promotions</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Name</th><th>Type</th><th>Discount</th><th>Applies To</th><th>Valid Period</th><th>Min Order</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($promos as $p):
        $isValid = $p['active']
          && (!$p['valid_from'] || $p['valid_from'] <= date('Y-m-d'))
          && (!$p['valid_to']   || $p['valid_to']   >= date('Y-m-d'));
      ?>
        <tr>
          <td>
            <div class="fw-700"><?=htmlspecialchars($p['name'])?></div>
            <div class="fs-12 text-muted"><?=htmlspecialchars($p['description']??'')?></div>
          </td>
          <td><span class="badge badge-blue"><?=$typeLabels[$p['promo_type']]??$p['promo_type']?></span></td>
          <td class="mono text-accent fw-700">
            <?php if ($p['promo_type']==='percent_off'): ?><?=$p['discount_value']?>%
            <?php elseif ($p['promo_type']==='fixed_off'): ?>Rs. <?=number_format($p['discount_value'],2)?>
            <?php elseif ($p['promo_type']==='buy_x_get_y'): ?>Buy <?=$p['buy_qty']?> Get <?=$p['get_qty']?>
            <?php else: ?>Free Item<?php endif; ?>
          </td>
          <td class="fs-12 text-muted"><?=ucfirst($p['applies_to'])?></td>
          <td class="fs-12">
            <?=$p['valid_from']?date('d/m/Y',strtotime($p['valid_from'])):'Any'?>
            <?=$p['valid_to']?' → '.date('d/m/Y',strtotime($p['valid_to'])):'→ Any'?>
          </td>
          <td class="mono fs-12"><?=$p['min_order_amount']>0?fmt($p['min_order_amount']):'None'?></td>
          <td>
            <?php if ($isValid): ?>
              <span class="badge badge-green">✅ Active</span>
            <?php elseif ($p['active']): ?>
              <span class="badge badge-accent">⏰ Scheduled</span>
            <?php else: ?>
              <span class="badge badge-red">Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="btn btn-sm btn-outline"
                data-id="<?=$p['id']?>"
                data-name="<?=htmlspecialchars($p['name'],ENT_QUOTES)?>"
                data-desc="<?=htmlspecialchars($p['description']??'',ENT_QUOTES)?>"
                data-type="<?=$p['promo_type']?>"
                data-val="<?=$p['discount_value']?>"
                data-bqty="<?=$p['buy_qty']?>"
                data-gqty="<?=$p['get_qty']?>"
                data-applies="<?=$p['applies_to']?>"
                data-appid="<?=$p['applies_id']??''?>"
                data-minamt="<?=$p['min_order_amount']?>"
                data-from="<?=$p['valid_from']??''?>"
                data-to="<?=$p['valid_to']??''?>"
                onclick="editPromo(this)">✏ Edit</button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="promo_id" value="<?=$p['id']?>">
                <button class="btn btn-sm <?=$p['active']?'btn-outline-red':'btn-outline-green'?>"><?=$p['active']?'Disable':'Enable'?></button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this promotion?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="promo_id" value="<?=$p['id']?>">
                <button class="btn btn-sm btn-red">🗑</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($promos)): ?>
        <tr><td colspan="8" class="text-center text-muted">No promotions yet. Click "+ Add Promotion" to create one.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── ADD PROMO MODAL ── -->
<div class="modal-overlay" id="modalAddPromo">
  <div class="modal-box" style="max-width:600px">
    <div class="modal-title">Add Promotion</div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Promotion Name *</label>
          <input name="name" class="form-control" placeholder="e.g. Happy Hour 20% Off" required>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Description</label>
          <input name="description" class="form-control" placeholder="Shown on receipt and POS">
        </div>
        <div class="form-group">
          <label class="form-label">Promotion Type *</label>
          <select name="promo_type" class="form-control" onchange="showPromoFields(this,'add')">
            <option value="percent_off">% Percentage Off</option>
            <option value="fixed_off">Fixed Rs. Amount Off</option>
            <option value="buy_x_get_y">Buy X Get Y Free</option>
          </select>
        </div>
        <div class="form-group" id="add_val_group">
          <label class="form-label" id="add_val_label">Discount Value</label>
          <input type="number" name="discount_value" class="form-control" step="0.01" placeholder="e.g. 10 for 10%">
        </div>
        <div class="form-group" id="add_bqty_group" style="display:none">
          <label class="form-label">Buy Qty</label>
          <input type="number" name="buy_qty" class="form-control" value="2" min="1">
        </div>
        <div class="form-group" id="add_gqty_group" style="display:none">
          <label class="form-label">Get Free Qty</label>
          <input type="number" name="get_qty" class="form-control" value="1" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Applies To</label>
          <select name="applies_to" class="form-control">
            <option value="all">All Items</option>
            <option value="category">Specific Category</option>
            <option value="item">Specific Item</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Min Order Amount (Rs.)</label>
          <input type="number" name="min_order_amount" class="form-control" step="0.01" value="0" placeholder="0 = no minimum">
        </div>
        <div class="form-group">
          <label class="form-label">Valid From</label>
          <input type="date" name="valid_from" class="form-control" value="<?=date('Y-m-d')?>">
        </div>
        <div class="form-group">
          <label class="form-label">Valid To</label>
          <input type="date" name="valid_to" class="form-control" value="<?=date('Y-m-d',strtotime('+30 days'))?>">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Add Promotion</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalAddPromo')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT PROMO MODAL ── -->
<div class="modal-overlay" id="modalEditPromo">
  <div class="modal-box" style="max-width:600px">
    <div class="modal-title">Edit Promotion</div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="promo_id" id="ePromoId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Promotion Name *</label>
          <input name="name" id="ePromoName" class="form-control" required>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Description</label>
          <input name="description" id="ePromoDesc" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Promotion Type *</label>
          <select name="promo_type" id="ePromoType" class="form-control" onchange="showPromoFields(this,'e')">
            <option value="percent_off">% Percentage Off</option>
            <option value="fixed_off">Fixed Rs. Amount Off</option>
            <option value="buy_x_get_y">Buy X Get Y Free</option>
          </select>
        </div>
        <div class="form-group" id="e_val_group">
          <label class="form-label" id="e_val_label">Discount Value</label>
          <input type="number" name="discount_value" id="ePromoVal" class="form-control" step="0.01">
        </div>
        <div class="form-group" id="e_bqty_group" style="display:none">
          <label class="form-label">Buy Qty</label>
          <input type="number" name="buy_qty" id="ePromoBQty" class="form-control" min="1">
        </div>
        <div class="form-group" id="e_gqty_group" style="display:none">
          <label class="form-label">Get Free Qty</label>
          <input type="number" name="get_qty" id="ePromoGQty" class="form-control" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Min Order Amount (Rs.)</label>
          <input type="number" name="min_order_amount" id="ePromoMin" class="form-control" step="0.01">
        </div>
        <div class="form-group">
          <label class="form-label">Valid From</label>
          <input type="date" name="valid_from" id="ePromoFrom" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Valid To</label>
          <input type="date" name="valid_to" id="ePromoTo" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Save Changes</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalEditPromo')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function showPromoFields(sel, prefix) {
  var type = sel.value;
  var valGrp  = document.getElementById(prefix+'_val_group');
  var valLbl  = document.getElementById(prefix+'_val_label');
  var bqtyGrp = document.getElementById(prefix+'_bqty_group');
  var gqtyGrp = document.getElementById(prefix+'_gqty_group');
  if (!valGrp) return;
  valGrp.style.display  = (type === 'buy_x_get_y') ? 'none' : '';
  bqtyGrp.style.display = (type === 'buy_x_get_y') ? '' : 'none';
  gqtyGrp.style.display = (type === 'buy_x_get_y') ? '' : 'none';
  if (valLbl) valLbl.textContent = type==='fixed_off' ? 'Fixed Amount (Rs.)' : 'Discount (%)';
}

function editPromo(btn) {
  document.getElementById('ePromoId').value   = btn.dataset.id;
  document.getElementById('ePromoName').value  = btn.dataset.name;
  document.getElementById('ePromoDesc').value  = btn.dataset.desc;
  document.getElementById('ePromoType').value  = btn.dataset.type;
  document.getElementById('ePromoVal').value   = btn.dataset.val;
  document.getElementById('ePromoBQty').value  = btn.dataset.bqty;
  document.getElementById('ePromoGQty').value  = btn.dataset.gqty;
  document.getElementById('ePromoMin').value   = btn.dataset.minamt;
  document.getElementById('ePromoFrom').value  = btn.dataset.from;
  document.getElementById('ePromoTo').value    = btn.dataset.to;
  // Trigger field visibility
  showPromoFields(document.getElementById('ePromoType'), 'e');
  openModal('modalEditPromo');
}
</script>
<?php include '../includes/footer.php'; ?>
