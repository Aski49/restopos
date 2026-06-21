<?php
require_once '../includes/config.php';
requireAccess('pos');
$db = getDB();
$pageTitle = 'POS / Billing'; $activePage = 'pos';

// Ensure promo tables exist
$db->exec("CREATE TABLE IF NOT EXISTS promotions (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, description TEXT, promo_type ENUM('percent_off','fixed_off','buy_x_get_y') NOT NULL DEFAULT 'percent_off', discount_value DECIMAL(10,2) DEFAULT 0, buy_qty INT DEFAULT 1, get_qty INT DEFAULT 1, applies_to ENUM('all','category','item') DEFAULT 'all', applies_id INT DEFAULT NULL, min_order_amount DECIMAL(10,2) DEFAULT 0, valid_from DATE, valid_to DATE, active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS bill_promotions (id INT AUTO_INCREMENT PRIMARY KEY, bill_id INT NOT NULL, promo_id INT NOT NULL, promo_name VARCHAR(150), discount_amt DECIMAL(10,2) DEFAULT 0)");

$svcPct = (float)getSetting('service_charge_pct', '10');
$taxPct = (float)getSetting('tax_pct', '8');

$billSuccess = '';
$billError   = '';
$lastBillId  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['f_total'])) {
    $cartData   = json_decode($_POST['f_cart'] ?? '[]', true);
    $orderType  = $_POST['f_order_type'] ?? 'Dine-In';
    $tableNo    = $_POST['f_table']      ?? '';
    $payMethod  = $_POST['f_pay_method'] ?? 'Cash';
    $discPct    = (float)($_POST['f_disc_pct']   ?? 0);
    $subtotal   = (float)($_POST['f_subtotal']   ?? 0);
    $svc        = (float)($_POST['f_svc']        ?? 0);
    $discAmt    = (float)($_POST['f_disc_amt']   ?? 0);
    $tax        = (float)($_POST['f_tax']        ?? 0);
    $total      = (float)($_POST['f_total']      ?? 0);
    $cashGiven  = (float)($_POST['f_cash_given'] ?? 0);
    $changeAmt  = max(0, $cashGiven - $total);
    $debtorId   = (int)($_POST['f_debtor_id']    ?? 0);
    $promoId    = (int)($_POST['f_promo_id']     ?? 0);
    $promoDisc  = (float)($_POST['f_promo_disc'] ?? 0);
    $promoName  = $_POST['f_promo_name'] ?? '';

    if (!empty($cartData) && $total > 0) {
        try {
            $db->beginTransaction();
            $billNo = generateBillNo();

            $stmt = $db->prepare("INSERT INTO bills
                (bill_no,order_type,table_no,subtotal,service_charge,discount_pct,
                 discount_amt,tax_amt,total,payment_method,cash_given,change_amt,status,created_by,created_at)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'settled',?,?)");
            $stmt->execute([$billNo,$orderType,$tableNo,$subtotal,$svc,$discPct,
                            $discAmt,$tax,$total,$payMethod,$cashGiven,$changeAmt,$_SESSION['user_id'],date('Y-m-d H:i:s')]);
            $billId = $db->lastInsertId();

            $ins = $db->prepare("INSERT INTO bill_items(bill_id,menu_item_id,item_name,price,qty,line_total) VALUES(?,?,?,?,?,?)");
            foreach ($cartData as $item) {
                $ins->execute([$billId,$item['id'],$item['name'],$item['price'],$item['qty'],round($item['price']*$item['qty'],2)]);
            }

            // Save promotion applied
            if ($promoId > 0 && $promoDisc > 0) {
                $db->prepare("INSERT INTO bill_promotions(bill_id,promo_id,promo_name,discount_amt) VALUES(?,?,?,?)")
                   ->execute([$billId,$promoId,$promoName,$promoDisc]);
            }

            // Debtor credit
            if ($payMethod === 'Credit' && $debtorId > 0) {
                $db->prepare("UPDATE debtors SET outstanding=outstanding+? WHERE id=?")->execute([$total,$debtorId]);
                $db->prepare("INSERT INTO debtor_payments(debtor_id,bill_id,txn_date,amount,type,notes) VALUES(?,?,CURDATE(),?,'charge',?)")
                   ->execute([$debtorId,$billId,$total,'Bill '.$billNo]);
            }
            $db->commit();
            $billSuccess = $billNo;
            $lastBillId  = $billId;
            logActivity('Settled Bill', 'pos', 'Bill '.$billNo.' — '.fmt($total).' via '.$payMethod);
        } catch (Exception $e) {
            $db->rollBack();
            $billError = 'Error saving bill: ' . $e->getMessage();
        }
    } else {
        $billError = 'Cart is empty. Add items before settling.';
    }
}

$categories = $db->query("SELECT * FROM menu_categories WHERE active=1 ORDER BY sort_order")->fetchAll();
$menuItems  = $db->query("SELECT mi.*, mc.name as cat_name FROM menu_items mi JOIN menu_categories mc ON mi.category_id=mc.id WHERE mi.active=1 ORDER BY mc.sort_order,mi.name")->fetchAll();
$debtors    = $db->query("SELECT id,name,phone,outstanding FROM debtors ORDER BY name")->fetchAll();

// Fetch active valid promotions
$today = date('Y-m-d');
$promos = $db->prepare("SELECT * FROM promotions WHERE active=1 AND (valid_from IS NULL OR valid_from <= ?) AND (valid_to IS NULL OR valid_to >= ?) ORDER BY name");
$promos->execute([$today,$today]); $promos=$promos->fetchAll();

include '../includes/header.php';
?>

<script>
window.SVC_PCT = <?= $svcPct ?>;
window.TAX_PCT = <?= $taxPct ?>;
window.PROMOS  = <?= json_encode($promos) ?>;
</script>

<div class="page-header">
  <div class="page-title">POS — Billing</div>
  <a href="bills.php" class="btn btn-sm btn-outline">📋 Bill History</a>
</div>

<?php if ($billError): ?><div class="alert alert-danger"><?= htmlspecialchars($billError) ?></div><?php endif; ?>

<div class="pos-layout">

<!-- ── LEFT: MENU ── -->
<div style="display:flex;flex-direction:column;gap:10px;overflow:hidden">
  <div class="card" style="padding:12px 16px;flex-shrink:0">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <div class="order-types">
        <?php foreach (['Dine-In','Takeaway','Uber Eats','PickMe','Delivery'] as $ot): ?>
          <button class="ot-btn <?= $ot==='Dine-In'?'active':'' ?>" data-type="<?= $ot ?>" onclick="setOrderType('<?= $ot ?>')"><?= $ot ?></button>
        <?php endforeach; ?>
      </div>
      <select id="tableSelector" class="form-control" style="width:90px;padding:6px 10px">
        <?php foreach (range(1,12) as $t): ?><option value="T<?= $t ?>">Table <?= $t ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>

  <div style="flex-shrink:0">
    <input type="text" class="form-control mb-8" placeholder="🔍 Search menu items..." oninput="menuSearch(this.value)">
    <div class="cat-filters">
      <button class="cat-btn active" data-cat="All" onclick="filterCategory('All')">All</button>
      <?php foreach ($categories as $cat): ?>
        <button class="cat-btn" data-cat="<?= htmlspecialchars($cat['name']) ?>" onclick="filterCategory('<?= htmlspecialchars($cat['name']) ?>')"><?= htmlspecialchars($cat['name']) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="menu-grid" style="flex:1">
    <?php foreach ($menuItems as $item): ?>
      <button class="menu-btn"
              data-id="<?= $item['id'] ?>"
              data-cat="<?= htmlspecialchars($item['cat_name']) ?>"
              data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>"
              onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>)">
        <span class="menu-emoji">🍽</span>
        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
        <span class="item-price">Rs. <?= number_format($item['price'],2) ?></span>
        <span class="item-cat"><?= htmlspecialchars($item['cat_name']) ?></span>
      </button>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── RIGHT: BILL PANEL ── -->
<div class="bill-panel">
  <div class="card" style="flex:1;display:flex;flex-direction:column;padding:14px;overflow:hidden">

    <div class="flex-between mb-12">
      <div style="font-weight:700;font-size:15px">Current Bill</div>
      <button class="btn btn-sm btn-outline-red" onclick="clearBill()">Clear</button>
    </div>

    <div id="billItems" class="bill-items">
      <div style="color:var(--muted);text-align:center;margin-top:40px;font-size:14px">Add items to start billing</div>
    </div>

    <!-- Totals -->
    <div style="border-top:1px solid var(--border);padding-top:10px;margin-top:10px">
      <div class="bill-total-line"><span>Subtotal</span><span id="billSubtotal" class="mono">Rs. 0.00</span></div>
      <div class="bill-total-line"><span>Service Charge (<?= $svcPct ?>%)</span><span id="billSvc" class="mono">Rs. 0.00</span></div>
      <div class="bill-total-line"><span>Discount</span><span id="billDisc" class="mono text-red">- Rs. 0.00</span></div>
      <!-- Promo discount line — hidden until promo applied -->
      <div class="bill-total-line" id="promoLine" style="display:none">
        <span id="promoLineName" style="color:var(--green)">🎉 Promo</span>
        <span id="promoLineAmt" class="mono text-green"></span>
      </div>
      <div class="bill-total-line"><span>Tax (<?= $taxPct ?>%)</span><span id="billTax" class="mono">Rs. 0.00</span></div>
      <div class="bill-grand"><span>TOTAL</span><span id="billTotal" class="mono text-accent">Rs. 0.00</span></div>
    </div>

    <!-- Payment -->
    <div style="margin-top:10px">
      <div class="fs-12 text-muted mb-8" style="font-weight:600">Payment Method</div>
      <div class="pay-methods">
        <?php foreach (['Cash','Card','QR','Bank Transfer','Credit','Uber Eats','PickMe'] as $pm): ?>
          <button class="pay-btn <?= $pm==='Cash'?'active':'' ?>"
                  data-method="<?= $pm ?>"
                  onclick="posSetPay('<?= $pm ?>')"><?= $pm ?></button>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;gap:8px;margin-top:10px;margin-bottom:8px">
        <div class="form-group" style="flex:1">
          <label class="form-label fs-12">Discount %</label>
          <input type="number" id="discountInput" class="form-control" min="0" max="100" placeholder="0" oninput="setDiscount(this.value)" style="padding:7px 10px">
        </div>
        <div class="form-group" id="cashRow" style="flex:1">
          <label class="form-label fs-12">Cash Given</label>
          <input type="number" id="cashGiven" class="form-control" placeholder="0.00" oninput="updateChange()" style="padding:7px 10px">
        </div>
      </div>
      <div class="bill-total-line">
        <span style="color:var(--green);font-weight:600">Change</span>
        <span id="changeAmt" class="mono text-green">Rs. 0.00</span>
      </div>

      <!-- PROMOTIONS SECTION -->
      <?php if (!empty($promos)): ?>
      <div style="margin-top:10px">
        <div class="fs-12 mb-8" style="font-weight:700;color:var(--green)">🎉 Available Promotions</div>
        <div id="promoList" style="display:flex;flex-direction:column;gap:6px">
          <?php foreach ($promos as $p): ?>
          <div class="promo-card" id="promoCard_<?=$p['id']?>"
               style="padding:8px 12px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:8px;cursor:pointer;transition:.15s"
               onclick="applyPromo(<?=$p['id']?>,'<?=addslashes($p['name'])?>','<?=$p['promo_type']?>',<?=$p['discount_value']?>,<?=$p['buy_qty']?>,<?=$p['get_qty']?>,<?=$p['min_order_amount']?>)">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-weight:700;font-size:12px;color:var(--green)"><?=htmlspecialchars($p['name'])?></div>
                <div class="fs-12 text-muted"><?=htmlspecialchars($p['description']??'')?></div>
              </div>
              <div style="font-weight:700;font-size:13px;color:var(--green);white-space:nowrap;margin-left:8px">
                <?php if ($p['promo_type']==='percent_off'): ?><?=$p['discount_value']?>% OFF
                <?php elseif ($p['promo_type']==='fixed_off'): ?>Rs. <?=number_format($p['discount_value'],2)?> OFF
                <?php else: ?>Buy <?=$p['buy_qty']?> Get Free<?php endif; ?>
              </div>
            </div>
            <?php if ($p['min_order_amount']>0): ?>
            <div class="fs-12 text-muted" style="margin-top:3px">Min order: Rs. <?=number_format($p['min_order_amount'],2)?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <button id="removePromoBtn" style="display:none;margin-top:6px" class="btn btn-sm btn-outline-red" onclick="removePromo()">✕ Remove Promotion</button>
      </div>
      <?php endif; ?>

      <!-- DEBTOR PANEL -->
      <div id="debtorPanel" style="display:none;margin-top:12px;padding:14px;background:rgba(139,92,246,.1);border:2px solid rgba(139,92,246,.4);border-radius:12px">
        <div style="font-weight:700;font-size:14px;color:var(--purple);margin-bottom:10px">🏦 Select Credit Customer</div>
        <?php if (empty($debtors)): ?>
          <div style="color:var(--muted);font-size:13px">⚠ No credit accounts. <a href="debtors.php" target="_blank" style="color:var(--accent)">Create one →</a></div>
        <?php else: ?>
          <select id="debtorSelect" class="form-control" onchange="onDebtorChange(this)" style="margin-bottom:10px;padding:10px 14px">
            <option value="">-- Select Customer --</option>
            <?php foreach ($debtors as $d): ?>
              <option value="<?= $d['id'] ?>" data-outstanding="<?= $d['outstanding'] ?>">
                <?= htmlspecialchars($d['name']) ?><?= $d['phone']?' | '.$d['phone']:'' ?> | Balance: Rs. <?= number_format($d['outstanding'],2) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div id="debtorInfo" style="display:none;padding:10px;background:var(--surface);border-radius:8px;font-size:13px">
            <div class="flex-between"><span class="text-muted">Current Balance</span><span id="dCurrentBal" class="mono text-red fw-700"></span></div>
            <div class="flex-between" style="margin-top:4px"><span class="text-muted">After This Bill</span><span id="dAfterBal" class="mono fw-700" style="color:var(--purple)"></span></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- SETTLE FORM -->
    <form method="POST" id="posForm" onsubmit="return validateBill()">
      <input type="hidden" name="f_table"      id="fTable"     value="T1">
      <input type="hidden" name="f_disc_pct"   id="fDiscPct"   value="0">
      <input type="hidden" name="f_debtor_id"  id="fDebtorId"  value="0">
      <input type="hidden" name="f_promo_id"   id="fPromoId"   value="0">
      <input type="hidden" name="f_promo_disc" id="fPromoDisc" value="0">
      <input type="hidden" name="f_promo_name" id="fPromoName" value="">
      <button type="submit" class="btn btn-block btn-lg" style="margin-top:14px;border-radius:10px;font-size:16px">
        💳 Settle Bill
      </button>
    </form>

  </div>

  <!-- Success -->
  <?php if ($billSuccess): ?>
  <div class="card" style="background:rgba(16,185,129,.12);border-color:var(--green);margin-top:10px;text-align:center;padding:20px">
    <div style="font-size:32px;margin-bottom:8px">✅</div>
    <div style="font-weight:700;color:var(--green);font-size:17px">Bill Settled!</div>
    <div class="mono text-muted fs-12" style="margin-top:4px"><?= htmlspecialchars($billSuccess) ?></div>
    <button onclick="clearBill()" class="btn btn-green" style="margin-top:14px;width:100%">+ New Bill</button>
    <a href="print_bill.php?id=<?= $lastBillId ?? '' ?>" target="_blank" class="btn btn-outline" style="margin-top:8px;width:100%">🖨 Print Receipt</a>
  </div>
  <?php endif; ?>
</div>
</div>

<style>
.promo-card.applied{background:rgba(16,185,129,.18)!important;border-color:var(--green)!important;border-width:2px!important}
.promo-card:hover{background:rgba(16,185,129,.14)!important}
</style>

<script>
// ── Applied promo state ───────────────────────────────────────
var appliedPromo = null; // {id, name, type, value}
var promoDiscAmt = 0;

// ── Apply / Remove Promo ──────────────────────────────────────
function applyPromo(id, name, type, value, buyQty, getQty, minAmt) {
  var sub = cart.reduce(function(s,i){return s+i.price*i.qty;},0);
  if (minAmt > 0 && sub < minAmt) {
    alert('Minimum order of Rs. ' + minAmt.toFixed(2) + ' required for this promotion.\nCurrent subtotal: Rs. ' + sub.toFixed(2));
    return;
  }
  // Remove previous highlight
  document.querySelectorAll('.promo-card').forEach(function(c){c.classList.remove('applied')});
  // Apply new
  appliedPromo = {id:id, name:name, type:type, value:value, buyQty:buyQty, getQty:getQty, minAmt:minAmt};
  document.getElementById('promoCard_'+id)?.classList.add('applied');
  document.getElementById('removePromoBtn').style.display = '';
  document.getElementById('fPromoId').value   = id;
  document.getElementById('fPromoName').value = name;
  posRenderAll();

  if (type === 'buy_x_get_y' && promoDiscAmt <= 0) {
    alert('⚠ "' + name + '" applied, but no items qualify yet.\nAdd at least ' + (parseInt(buyQty)+parseInt(getQty)) + ' units of the SAME item to get the discount.');
  } else {
    alert('✅ Promotion applied: ' + name);
  }
}

function removePromo() {
  appliedPromo = null;
  promoDiscAmt = 0;
  document.querySelectorAll('.promo-card').forEach(function(c){c.classList.remove('applied')});
  document.getElementById('removePromoBtn').style.display = 'none';
  document.getElementById('fPromoId').value   = '0';
  document.getElementById('fPromoDisc').value = '0';
  document.getElementById('fPromoName').value = '';
  var pl = document.getElementById('promoLine');
  if (pl) pl.style.display = 'none';
  posRenderAll();
}

function calcPromoDisc(subtotal) {
  if (!appliedPromo) return 0;
  if (appliedPromo.minAmt > 0 && subtotal < appliedPromo.minAmt) return 0;

  if (appliedPromo.type === 'percent_off') {
    return Math.round(subtotal * appliedPromo.value / 100 * 100) / 100;
  }
  if (appliedPromo.type === 'fixed_off') {
    return Math.min(appliedPromo.value, subtotal);
  }
  if (appliedPromo.type === 'buy_x_get_y') {
    var buyQty = parseInt(appliedPromo.buyQty) || 1;
    var getQty = parseInt(appliedPromo.getQty) || 1;
    var groupSize = buyQty + getQty;
    var totalDisc = 0;

    // For each distinct item in the cart, check if qty >= groupSize
    cart.forEach(function(item) {
      if (item.qty >= groupSize) {
        var freeSets = Math.floor(item.qty / groupSize);
        var freeUnits = freeSets * getQty;
        totalDisc += freeUnits * item.price;
      }
    });
    return Math.round(totalDisc * 100) / 100;
  }
  return 0;
}

// ── posSetPay ─────────────────────────────────────────────────
function posSetPay(m) {
  if (typeof payMethod !== 'undefined') payMethod = m;
  document.querySelectorAll('.pay-btn').forEach(function(b){b.classList.toggle('active', b.dataset.method===m)});
  var cashRow = document.getElementById('cashRow');
  if (cashRow) cashRow.style.display = (m==='Cash') ? '' : 'none';
  var panel = document.getElementById('debtorPanel');
  if (panel) panel.style.display = (m==='Credit') ? 'block' : 'none';
  if (m !== 'Credit') {
    var fd=document.getElementById('fDebtorId'); if(fd) fd.value='0';
    var ds=document.getElementById('debtorSelect'); if(ds) ds.value='';
    var di=document.getElementById('debtorInfo'); if(di) di.style.display='none';
  }
  posRenderAll();
}

// ── Debtor ────────────────────────────────────────────────────
function onDebtorChange(sel) {
  var id=sel.value, opt=sel.options[sel.selectedIndex];
  var fd=document.getElementById('fDebtorId'); if(fd) fd.value=id||'0';
  var info=document.getElementById('debtorInfo');
  if (id && info) {
    var out=parseFloat(opt.dataset.outstanding)||0;
    var tot=parseFloat((document.getElementById('billTotal')?.textContent||'0').replace(/[^0-9.]/g,''))||0;
    var cb=document.getElementById('dCurrentBal'); if(cb) cb.textContent='Rs. '+out.toFixed(2);
    var ab=document.getElementById('dAfterBal');   if(ab) ab.textContent='Rs. '+(out+tot).toFixed(2);
    info.style.display='';
  } else if(info) { info.style.display='none'; }
}

// ════════════════════════════════════════════════════════════════
// SINGLE SOURCE OF TRUTH for all bill calculations on this page.
// This is the ONLY function that should ever write to #billTotal,
// #changeAmt, and the hidden form fields. It is self-contained and
// does not depend on app.js's renderCart() running first/after —
// it reimplements the cart line rendering itself, then layers the
// promo discount + cash/change calculation on top in one pass.
// This avoids the previous bug where two separate functions
// (app.js's renderCart/updateChange AND pos.php's patched versions)
// could run in conflicting order depending on script load timing,
// causing Change to show a stale, incorrect amount.
// ════════════════════════════════════════════════════════════════
function posRenderAll() {
  // 1) Render the cart line items (same markup as app.js's renderCart)
  var billItems = document.getElementById('billItems');
  if (billItems) {
    if (!cart.length) {
      billItems.innerHTML = '<div style="color:var(--muted);text-align:center;margin-top:40px;font-size:14px">Add items to start billing</div>';
    } else {
      billItems.innerHTML = cart.map(function(item) {
        return '<div class="bill-item">' +
          '<div style="flex:1">' +
            '<div class="bill-item-name">' + item.name + '</div>' +
            '<div class="bill-item-sub">Rs. ' + item.price.toFixed(2) + ' each</div>' +
          '</div>' +
          '<div class="qty-ctrl">' +
            '<button class="qty-btn" onclick="changeQty(' + item.id + ',-1)">−</button>' +
            '<span class="qty-val">' + item.qty + '</span>' +
            '<button class="qty-btn" onclick="changeQty(' + item.id + ',1)">+</button>' +
            '<button class="rm-btn" onclick="removeFromCart(' + item.id + ')">×</button>' +
          '</div>' +
          '<div style="width:72px;text-align:right;font-family:\'JetBrains Mono\',monospace;font-size:13px;font-weight:600">' +
            'Rs. ' + (item.price * item.qty).toFixed(2) +
          '</div>' +
        '</div>';
      }).join('');
    }
  }

  // 2) Core money calculation — ONE place, ONE result, used everywhere below
  var subtotal = cart.reduce(function(s,i){ return s + i.price * i.qty; }, 0);
  var svc      = (typeof orderType !== 'undefined' && orderType === 'Dine-In')
                   ? subtotal * (window.SVC_PCT || 10) / 100 : 0;
  var discPct  = (typeof discountPct !== 'undefined') ? discountPct : 0;
  var discAmt  = subtotal * discPct / 100;

  promoDiscAmt = calcPromoDisc(subtotal);

  var taxableAmt = subtotal + svc - discAmt - promoDiscAmt;
  if (taxableAmt < 0) taxableAmt = 0;
  var tax   = taxableAmt * (window.TAX_PCT || 8) / 100;
  var total = taxableAmt + tax;
  if (total < 0) total = 0;

  // 3) Write all the display fields from this single total
  var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
  set('billSubtotal', 'Rs. ' + subtotal.toFixed(2));
  set('billSvc',      'Rs. ' + svc.toFixed(2));
  set('billDisc',     '- Rs. ' + discAmt.toFixed(2));
  set('billTax',      'Rs. ' + tax.toFixed(2));
  set('billTotal',    'Rs. ' + total.toFixed(2));

  // Promo line
  var pl = document.getElementById('promoLine');
  var pn = document.getElementById('promoLineName');
  var pa = document.getElementById('promoLineAmt');
  if (pl && appliedPromo && promoDiscAmt > 0) {
    pl.style.display = '';
    if (pn) pn.textContent = '🎉 ' + appliedPromo.name;
    if (pa) pa.textContent = '- Rs. ' + promoDiscAmt.toFixed(2);
  } else if (pl) {
    pl.style.display = 'none';
  }

  // 4) Change — calculated from the EXACT same `total` as above, every time
  var cashGiven = parseFloat(document.getElementById('cashGiven')?.value) || 0;
  set('changeAmt', 'Rs. ' + Math.max(0, cashGiven - total).toFixed(2));

  // 5) Debtor after-bill preview
  var ds = document.getElementById('debtorSelect');
  if (ds && ds.value) {
    var opt = ds.options[ds.selectedIndex];
    var out = parseFloat(opt?.dataset?.outstanding) || 0;
    var ab  = document.getElementById('dAfterBal');
    if (ab) ab.textContent = 'Rs. ' + (out + total).toFixed(2);
  }

  // 6) Sync all hidden form fields used on Settle Bill submit
  var form = document.getElementById('posForm');
  if (form) {
    setHidden(form, 'f_subtotal',    subtotal.toFixed(2));
    setHidden(form, 'f_svc',         svc.toFixed(2));
    setHidden(form, 'f_disc_amt',    discAmt.toFixed(2));
    setHidden(form, 'f_tax',         tax.toFixed(2));
    setHidden(form, 'f_total',       total.toFixed(2));
    setHidden(form, 'f_order_type',  typeof orderType !== 'undefined' ? orderType : 'Dine-In');
    setHidden(form, 'f_pay_method',  typeof payMethod !== 'undefined' ? payMethod : 'Cash');
    setHidden(form, 'f_cart',        JSON.stringify(cart));
    setHidden(form, 'f_promo_disc',  promoDiscAmt.toFixed(2));
    setHidden(form, 'f_cash_given',  cashGiven.toFixed(2));
  }
}

// Make this page's renderCart/updateChange ALWAYS point to the single
// authoritative function above — overriding app.js's versions completely,
// regardless of script load order. This guarantees Change is always
// calculated from the exact same total shown on screen.
window.renderCart   = posRenderAll;
window.updateChange = posRenderAll;
window.setPayMethod = posSetPay;

// ── Validate ──────────────────────────────────────────────────
function validateBill() {
  if (typeof cart!=='undefined' && cart.length===0) { alert('Please add items first.'); return false; }
  var tbl=document.getElementById('tableSelector');
  var disc=document.getElementById('discountInput');
  if (tbl)  document.getElementById('fTable').value   = tbl.value;
  if (disc) document.getElementById('fDiscPct').value = disc.value||0;
  var activeBtn=document.querySelector('.pay-btn.active');
  var curPay=activeBtn?activeBtn.dataset.method:'Cash';
  if (curPay==='Credit') {
    var did=document.getElementById('fDebtorId').value;
    if (!did||did==='0') {
      alert('Please select a credit customer from the dropdown.');
      var panel=document.getElementById('debtorPanel'); if(panel) panel.style.display='block';
      return false;
    }
  }
  return true;
}
</script>

<?php include '../includes/footer.php'; ?>
