<?php
require_once '../includes/config.php';
requireLogin();
$db = getDB();
$pageTitle = 'POS / Billing'; $activePage = 'pos';

$svcPct = (float)getSetting('service_charge_pct', '10');
$taxPct = (float)getSetting('tax_pct', '8');

$billSuccess = '';
$billError   = '';
$lastBillId  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['f_total'])) {
    $cartData  = json_decode($_POST['f_cart'] ?? '[]', true);
    $orderType = $_POST['f_order_type'] ?? 'Dine-In';
    $tableNo   = $_POST['f_table']      ?? '';
    $payMethod = $_POST['f_pay_method'] ?? 'Cash';
    $discPct   = (float)($_POST['f_disc_pct']   ?? 0);
    $subtotal  = (float)($_POST['f_subtotal']   ?? 0);
    $svc       = (float)($_POST['f_svc']        ?? 0);
    $discAmt   = (float)($_POST['f_disc_amt']   ?? 0);
    $tax       = (float)($_POST['f_tax']        ?? 0);
    $total     = (float)($_POST['f_total']      ?? 0);
    $cashGiven = (float)($_POST['f_cash_given'] ?? 0);
    $changeAmt = max(0, $cashGiven - $total);
    $debtorId  = (int)($_POST['f_debtor_id']    ?? 0);

    if (!empty($cartData) && $total > 0) {
        try {
            $db->beginTransaction();
            $billNo = generateBillNo();
            $stmt = $db->prepare("INSERT INTO bills
                (bill_no,order_type,table_no,subtotal,service_charge,discount_pct,
                 discount_amt,tax_amt,total,payment_method,cash_given,change_amt,status,created_by)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'settled',?)");
            $stmt->execute([$billNo,$orderType,$tableNo,$subtotal,$svc,$discPct,
                            $discAmt,$tax,$total,$payMethod,$cashGiven,$changeAmt,$_SESSION['user_id']]);
            $billId = $db->lastInsertId();

            $ins = $db->prepare("INSERT INTO bill_items(bill_id,menu_item_id,item_name,price,qty,line_total) VALUES(?,?,?,?,?,?)");
            foreach ($cartData as $item) {
                $ins->execute([$billId,$item['id'],$item['name'],$item['price'],$item['qty'],round($item['price']*$item['qty'],2)]);
            }

            // Add to debtor outstanding if Credit payment
            if ($payMethod === 'Credit' && $debtorId > 0) {
                $db->prepare("UPDATE debtors SET outstanding=outstanding+? WHERE id=?")->execute([$total,$debtorId]);
                $db->prepare("INSERT INTO debtor_payments(debtor_id,bill_id,txn_date,amount,type,notes) VALUES(?,?,CURDATE(),?,'charge',?)")
                   ->execute([$debtorId,$billId,$total,'Bill '.$billNo]);
            }
            $db->commit();
            $billSuccess = $billNo;
            $lastBillId  = $billId;
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

include '../includes/header.php';
?>

<script>
window.SVC_PCT = <?= $svcPct ?>;
window.TAX_PCT = <?= $taxPct ?>;
</script>

<div class="page-header">
  <div class="page-title">POS — Billing</div>
  <a href="bills.php" class="btn btn-sm btn-outline">📋 Bill History</a>
</div>

<?php if ($billError): ?><div class="alert alert-danger"><?= htmlspecialchars($billError) ?></div><?php endif; ?>

<div class="pos-layout">

<!-- ── LEFT: MENU ─────────────────────────────────────────── -->
<div style="display:flex;flex-direction:column;gap:10px;overflow:hidden">

  <!-- Order type + table -->
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

  <!-- Search + Category -->
  <div style="flex-shrink:0">
    <input type="text" class="form-control mb-8" placeholder="🔍 Search menu items..." oninput="menuSearch(this.value)">
    <div class="cat-filters">
      <button class="cat-btn active" data-cat="All" onclick="filterCategory('All')">All</button>
      <?php foreach ($categories as $cat): ?>
        <button class="cat-btn" data-cat="<?= htmlspecialchars($cat['name']) ?>" onclick="filterCategory('<?= htmlspecialchars($cat['name']) ?>')"><?= htmlspecialchars($cat['name']) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Menu Grid -->
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

</div><!-- /left -->

<!-- ── RIGHT: BILL PANEL ──────────────────────────────────── -->
<div class="bill-panel">
  <div class="card" style="flex:1;display:flex;flex-direction:column;padding:14px;overflow:hidden">

    <div class="flex-between mb-12">
      <div style="font-weight:700;font-size:15px">Current Bill</div>
      <button class="btn btn-sm btn-outline-red" onclick="clearBill()">Clear</button>
    </div>

    <!-- Cart -->
    <div id="billItems" class="bill-items">
      <div style="color:var(--muted);text-align:center;margin-top:40px;font-size:14px">Add items to start billing</div>
    </div>

    <!-- Totals -->
    <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:10px">
      <div class="bill-total-line"><span>Subtotal</span><span id="billSubtotal" class="mono">Rs. 0.00</span></div>
      <div class="bill-total-line"><span>Service Charge (<?= $svcPct ?>%)</span><span id="billSvc" class="mono">Rs. 0.00</span></div>
      <div class="bill-total-line"><span>Discount</span><span id="billDisc" class="mono text-red">- Rs. 0.00</span></div>
      <div class="bill-total-line"><span>Tax (<?= $taxPct ?>%)</span><span id="billTax" class="mono">Rs. 0.00</span></div>
      <div class="bill-grand"><span>TOTAL</span><span id="billTotal" class="mono text-accent">Rs. 0.00</span></div>
    </div>

    <!-- Payment Method -->
    <div style="margin-top:10px">
      <div class="fs-12 text-muted mb-8" style="font-weight:600">Payment Method</div>
      <div class="pay-methods">
        <?php foreach (['Cash','Card','QR','Bank Transfer','Credit','Uber Eats','PickMe'] as $pm): ?>
          <button class="pay-btn <?= $pm==='Cash'?'active':'' ?>"
                  data-method="<?= $pm ?>"
                  onclick="posSetPay('<?= $pm ?>')"><?= $pm ?></button>
        <?php endforeach; ?>
      </div>

      <!-- Discount + Cash Given -->
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

      <!-- ══ DEBTOR PANEL ══════════════════════════════════════
           Hidden by default. setPayMethod() shows this when
           Credit is selected. 
      ══════════════════════════════════════════════════════════ -->
      <div id="debtorPanel" style="display:none;margin-top:12px;padding:14px;background:rgba(139,92,246,.1);border:2px solid rgba(139,92,246,.4);border-radius:12px">
        <div style="font-weight:700;font-size:14px;color:var(--purple);margin-bottom:10px">🏦 Select Credit Customer</div>

        <?php if (empty($debtors)): ?>
          <div style="color:var(--muted);font-size:13px;padding:8px;background:var(--surface);border-radius:8px">
            ⚠ No credit accounts found.
            <a href="debtors.php" target="_blank" style="color:var(--accent);font-weight:600">Create a debtor account first →</a>
          </div>
        <?php else: ?>
          <select id="debtorSelect"
                  class="form-control"
                  onchange="onDebtorChange(this)"
                  style="margin-bottom:10px;font-size:14px;padding:10px 14px">
            <option value="">-- Select Customer / Debtor --</option>
            <?php foreach ($debtors as $d): ?>
              <option value="<?= $d['id'] ?>"
                      data-outstanding="<?= $d['outstanding'] ?>">
                <?= htmlspecialchars($d['name']) ?>
                <?= $d['phone'] ? ' | '.$d['phone'] : '' ?>
                &nbsp;|&nbsp; Balance: Rs. <?= number_format($d['outstanding'],2) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <!-- Shows after debtor is selected -->
          <div id="debtorInfo" style="display:none;padding:10px 12px;background:var(--surface);border-radius:8px;font-size:13px">
            <div class="flex-between mb-8">
              <span class="text-muted">Current Outstanding</span>
              <span id="dCurrentBal" class="mono text-red fw-700"></span>
            </div>
            <div class="flex-between">
              <span class="text-muted">After This Bill</span>
              <span id="dAfterBal" class="mono fw-700" style="color:var(--purple)"></span>
            </div>
          </div>
          <div style="font-size:12px;color:var(--muted);margin-top:8px">
            Bill total will be added to this customer's outstanding balance.
          </div>
        <?php endif; ?>
      </div>
      <!-- ══ END DEBTOR PANEL ══════════════════════════════════ -->

    </div><!-- /payment -->

    <!-- Settle Form -->
    <form method="POST" id="posForm" onsubmit="return validateBill()">
      <input type="hidden" name="f_table"     id="fTable"    value="T1">
      <input type="hidden" name="f_disc_pct"  id="fDiscPct"  value="0">
      <input type="hidden" name="f_debtor_id" id="fDebtorId" value="0">
      <button type="submit" class="btn btn-block btn-lg" style="margin-top:14px;border-radius:10px;font-size:16px">
        💳 Settle Bill
      </button>
    </form>

  </div><!-- /card -->

  <!-- Success panel -->
  <?php if ($billSuccess): ?>
  <div class="card" style="background:rgba(16,185,129,.12);border-color:var(--green);margin-top:10px;text-align:center;padding:20px">
    <div style="font-size:32px;margin-bottom:8px">✅</div>
    <div style="font-weight:700;color:var(--green);font-size:17px">Bill Settled!</div>
    <div class="mono text-muted fs-12" style="margin-top:4px"><?= htmlspecialchars($billSuccess) ?></div>
    <button onclick="clearBill()" class="btn btn-green" style="margin-top:14px;width:100%">+ New Bill</button>
    <a href="print_bill.php?id=<?= $lastBillId ?? '' ?>" target="_blank" class="btn btn-outline" style="margin-top:8px;width:100%">🖨 Print Receipt</a>
  </div>
  <?php endif; ?>

</div><!-- /bill panel -->
</div><!-- /pos-layout -->

<script>
// ── posSetPay — fully self-contained, does NOT depend on app.js ──
function posSetPay(m) {
  // 1. Update global payMethod used by app.js renderCart
  if (typeof payMethod !== 'undefined') payMethod = m;

  // 2. Update button styles
  document.querySelectorAll('.pay-btn').forEach(function(b) {
    b.classList.toggle('active', b.dataset.method === m);
  });

  // 3. Cash Given row — only for Cash
  var cashRow = document.getElementById('cashRow');
  if (cashRow) cashRow.style.display = (m === 'Cash') ? '' : 'none';

  // 4. Debtor panel — show ONLY for Credit
  var panel = document.getElementById('debtorPanel');
  if (panel) {
    panel.style.display = (m === 'Credit') ? 'block' : 'none';
  }

  // 5. Reset debtor when switching away from Credit
  if (m !== 'Credit') {
    var fDebtor = document.getElementById('fDebtorId');
    if (fDebtor) fDebtor.value = '0';
    var dSel = document.getElementById('debtorSelect');
    if (dSel) dSel.value = '';
    var dInfo = document.getElementById('debtorInfo');
    if (dInfo) dInfo.style.display = 'none';
  }

  // 6. Trigger app.js renderCart to update hidden fields
  if (typeof renderCart === 'function') renderCart();
}

// ── Debtor selection handler ──────────────────────────────────
function onDebtorChange(sel) {
  var id  = sel.value;
  var opt = sel.options[sel.selectedIndex];
  var fDebtor = document.getElementById('fDebtorId');
  if (fDebtor) fDebtor.value = id || '0';

  var info = document.getElementById('debtorInfo');
  if (id && info) {
    var outstanding = parseFloat(opt.dataset.outstanding) || 0;
    var totalEl = document.getElementById('billTotal');
    var total = parseFloat((totalEl ? totalEl.textContent : '0').replace(/[^0-9.]/g, '')) || 0;
    var elCur   = document.getElementById('dCurrentBal');
    var elAfter = document.getElementById('dAfterBal');
    if (elCur)   elCur.textContent   = 'Rs. ' + outstanding.toFixed(2);
    if (elAfter) elAfter.textContent = 'Rs. ' + (outstanding + total).toFixed(2);
    info.style.display = '';
  } else if (info) {
    info.style.display = 'none';
  }
}

// ── Validate before submit ────────────────────────────────────
function validateBill() {
  if (typeof cart !== 'undefined' && cart.length === 0) {
    alert('Please add items to the bill first.');
    return false;
  }

  var tbl  = document.getElementById('tableSelector');
  var disc = document.getElementById('discountInput');
  if (tbl)  document.getElementById('fTable').value   = tbl.value;
  if (disc) document.getElementById('fDiscPct').value = disc.value || 0;

  // Check current pay method
  var activeBtn = document.querySelector('.pay-btn.active');
  var currentPay = activeBtn ? activeBtn.dataset.method : 'Cash';

  if (currentPay === 'Credit') {
    var did = document.getElementById('fDebtorId').value;
    if (!did || did === '0') {
      alert('Please select a customer / credit account from the dropdown.');
      var panel = document.getElementById('debtorPanel');
      if (panel) panel.style.display = 'block';
      return false;
    }
  }
  return true;
}

// ── Override app.js setPayMethod after it loads ───────────────
// app.js loads after this script. We override it once the page is ready.
window.addEventListener('load', function() {
  // Override setPayMethod so both app.js and direct calls go through posSetPay
  if (typeof window.setPayMethod !== 'undefined') {
    window.setPayMethod = posSetPay;
  }
});
</script>

<?php include '../includes/footer.php'; ?>
