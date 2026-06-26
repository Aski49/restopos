// ── RestoPOS Sri Lanka — Main JS ─────────────────────────────

// ── SIDEBAR TOGGLE ──────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('collapsed');
  document.getElementById('mainWrap').classList.toggle('collapsed');
}

// ── CLOCK ────────────────────────────────────────────────────
function updateClock() {
  const el = document.getElementById('clock');
  if (!el) return;
  const now = new Date();
  el.textContent = now.toLocaleString('en-GB', {weekday:'short',day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock, 1000);
updateClock();

// ── MODAL ────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id){ document.getElementById(id)?.classList.remove('open'); }
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});

// ── POS STATE ────────────────────────────────────────────────
let cart = [];
let orderType = 'Dine-In';
let payMethod  = 'Cash';
let discountPct = 0;
let serviceChargePct = parseFloat(window.SVC_PCT ?? 10);
let taxPct           = parseFloat(window.TAX_PCT ?? 8);

// ── ORDER TYPE ───────────────────────────────────────────────
function setOrderType(t) {
  orderType = t;
  document.querySelectorAll('.ot-btn').forEach(b => b.classList.toggle('active', b.dataset.type === t));
  const tbl = document.getElementById('tableSelector');
  if (tbl) tbl.style.display = (t === 'Dine-In') ? 'inline-block' : 'none';
  renderCart();
}

// ── PAYMENT METHOD ───────────────────────────────────────────
function setPayMethod(m) {
  // If posSetPay is defined (we're on POS page), delegate to it
  if (typeof posSetPay === 'function') {
    posSetPay(m);
    return;
  }

  payMethod = m;
  document.querySelectorAll('.pay-btn').forEach(b => b.classList.toggle('active', b.dataset.method === m));

  var cashRow = document.getElementById('cashRow');
  if (cashRow) cashRow.style.display = (m === 'Cash') ? '' : 'none';

  var debtorPanel = document.getElementById('debtorPanel');
  if (debtorPanel) debtorPanel.style.display = (m === 'Credit') ? 'block' : 'none';

  if (m !== 'Credit') {
    var fDebtor = document.getElementById('fDebtorId');
    if (fDebtor) fDebtor.value = '0';
    var dSel = document.getElementById('debtorSelect');
    if (dSel) dSel.value = '';
    var dInfo = document.getElementById('debtorInfo');
    if (dInfo) dInfo.style.display = 'none';
  }

  renderCart();
}

// ── MENU FILTER ───────────────────────────────────────────────
function filterCategory(cat) {
  document.querySelectorAll('.cat-btn').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
  document.querySelectorAll('.menu-btn').forEach(btn => {
    btn.style.display = (cat === 'All' || btn.dataset.cat === cat) ? '' : 'none';
  });
}

function menuSearch(val) {
  const q = val.toLowerCase();
  document.querySelectorAll('.menu-btn').forEach(btn => {
    btn.style.display = btn.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── CART OPERATIONS ───────────────────────────────────────────
function addToCart(id, name, price) {
  const ex = cart.find(x => x.id === id);
  if (ex) ex.qty++;
  else cart.push({ id, name, price: parseFloat(price), qty: 1 });
  document.querySelector(`.menu-btn[data-id="${id}"]`)?.classList.add('in-cart');
  renderCart();
}

function changeQty(id, delta) {
  const item = cart.find(x => x.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) {
    cart = cart.filter(x => x.id !== id);
    document.querySelector(`.menu-btn[data-id="${id}"]`)?.classList.remove('in-cart');
  }
  renderCart();
}

function removeFromCart(id) {
  cart = cart.filter(x => x.id !== id);
  document.querySelector(`.menu-btn[data-id="${id}"]`)?.classList.remove('in-cart');
  renderCart();
}

function setDiscount(v) {
  discountPct = Math.min(100, Math.max(0, parseFloat(v) || 0));
  renderCart();
}

// ── RENDER CART (default implementation — used when no page-specific
//    override like pos.php's posRenderAll exists) ─────────────────
function renderCart() {
  // If a page (e.g. pos.php) has installed its own authoritative
  // calculation function, always defer to it so there is only ever
  // ONE source of truth for totals/change on that page.
  if (typeof window.posRenderAll === 'function') {
    window.posRenderAll();
    return;
  }

  const billItems = document.getElementById('billItems');
  if (!billItems) return;

  if (cart.length === 0) {
    billItems.innerHTML = '<div style="color:var(--muted);text-align:center;margin-top:40px;font-size:14px">Add items to start billing</div>';
  } else {
    billItems.innerHTML = cart.map(item => `
      <div class="bill-item">
        <div style="flex:1">
          <div class="bill-item-name">${item.name}</div>
          <div class="bill-item-sub">Rs. ${item.price.toFixed(2)} each</div>
        </div>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changeQty(${item.id},-1)">−</button>
          <span class="qty-val">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty(${item.id},1)">+</button>
          <button class="rm-btn" onclick="removeFromCart(${item.id})">×</button>
        </div>
        <div style="width:72px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600">
          Rs. ${(item.price * item.qty).toFixed(2)}
        </div>
      </div>`).join('');
  }

  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const svc       = orderType === 'Dine-In' ? subtotal * serviceChargePct / 100 : 0;
  const disc      = subtotal * discountPct / 100;
  const tax       = (subtotal + svc - disc) * taxPct / 100;
  const total     = subtotal + svc - disc + tax;

  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  set('billSubtotal', 'Rs. ' + subtotal.toFixed(2));
  set('billSvc',      'Rs. ' + svc.toFixed(2));
  set('billDisc',     '- Rs. ' + disc.toFixed(2));
  set('billTax',      'Rs. ' + tax.toFixed(2));
  set('billTotal',    'Rs. ' + total.toFixed(2));

  // Change calc
  const cashGiven = parseFloat(document.getElementById('cashGiven')?.value) || 0;
  set('changeAmt', 'Rs. ' + Math.max(0, cashGiven - total).toFixed(2));

  // Update debtor "after bill" amount
  updateDebtorAfter(total);

  // Sync hidden form fields
  const form = document.getElementById('posForm');
  if (form) {
    setHidden(form, 'f_subtotal',   subtotal.toFixed(2));
    setHidden(form, 'f_svc',        svc.toFixed(2));
    setHidden(form, 'f_disc_amt',   disc.toFixed(2));
    setHidden(form, 'f_tax',        tax.toFixed(2));
    setHidden(form, 'f_total',      total.toFixed(2));
    setHidden(form, 'f_order_type', orderType);
    setHidden(form, 'f_pay_method', payMethod);
    setHidden(form, 'f_cart',       JSON.stringify(cart));
  }
}

function setHidden(form, name, val) {
  let inp = form.querySelector(`input[name="${name}"]`);
  if (!inp) { inp = document.createElement('input'); inp.type='hidden'; inp.name=name; form.appendChild(inp); }
  inp.value = val;
}

// ── DEBTOR FUNCTIONS ──────────────────────────────────────────
function onDebtorChange(sel) {
  const opt = sel.options[sel.selectedIndex];
  const id  = sel.value;
  const fDebtor = document.getElementById('fDebtorId');
  if (fDebtor) fDebtor.value = id || '0';

  const info = document.getElementById('debtorInfo');
  if (id && info) {
    const outstanding = parseFloat(opt.dataset.outstanding) || 0;
    const totalEl = document.getElementById('billTotal');
    const total = parseFloat((totalEl?.textContent || '0').replace(/[^0-9.]/g, '')) || 0;
    const elCur = document.getElementById('dCurrentBal');
    const elAfter = document.getElementById('dAfterBal');
    if (elCur)   elCur.textContent   = 'Rs. ' + outstanding.toFixed(2);
    if (elAfter) elAfter.textContent = 'Rs. ' + (outstanding + total).toFixed(2);
    info.style.display = '';
  } else if (info) {
    info.style.display = 'none';
  }
}

function updateDebtorAfter(total) {
  const sel = document.getElementById('debtorSelect');
  if (!sel || !sel.value) return;
  const opt = sel.options[sel.selectedIndex];
  const outstanding = parseFloat(opt?.dataset?.outstanding) || 0;
  const elAfter = document.getElementById('dAfterBal');
  if (elAfter) elAfter.textContent = 'Rs. ' + (outstanding + total).toFixed(2);
}

// ── CASH CHANGE ───────────────────────────────────────────────
function updateChange() {
  // Defer to the page-specific authoritative calculator if present
  // (pos.php's posRenderAll keeps Change in sync with the promo-adjusted total)
  if (typeof window.posRenderAll === 'function') {
    window.posRenderAll();
    return;
  }
  const cashGiven = parseFloat(document.getElementById('cashGiven')?.value) || 0;
  const totalEl   = document.getElementById('billTotal');
  const total     = parseFloat((totalEl?.textContent || '0').replace(/[^0-9.]/g,'')) || 0;
  const el = document.getElementById('changeAmt');
  if (el) el.textContent = 'Rs. ' + Math.max(0, cashGiven - total).toFixed(2);
  const form = document.getElementById('posForm');
  if (form) setHidden(form, 'f_cash_given', cashGiven.toFixed(2));
}

// ── CLEAR BILL ────────────────────────────────────────────────
function clearBill() {
  cart = [];
  discountPct = 0;
  const disc = document.getElementById('discountInput');
  if (disc) disc.value = '';
  const cg = document.getElementById('cashGiven');
  if (cg) cg.value = '';
  document.querySelectorAll('.menu-btn').forEach(b => b.classList.remove('in-cart'));
  // Reset debtor
  const dSel = document.getElementById('debtorSelect');
  if (dSel) dSel.value = '';
  const dInfo = document.getElementById('debtorInfo');
  if (dInfo) dInfo.style.display = 'none';
  const fDebtor = document.getElementById('fDebtorId');
  if (fDebtor) fDebtor.value = '0';
  renderCart();
}

// ── TABS ─────────────────────────────────────────────────────
function switchTab(group, tabId) {
  document.querySelectorAll(`[data-tabgroup="${group}"]`).forEach(el => {
    el.classList.toggle('active', el.dataset.tab === tabId);
  });
  document.querySelectorAll(`[data-tabcontent="${group}"]`).forEach(el => {
    el.style.display = el.dataset.content === tabId ? '' : 'none';
  });
}

// ── ALERTS ───────────────────────────────────────────────────
function showAlert(msg, type = 'success', containerId = 'alertContainer') {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
  setTimeout(() => { el.innerHTML = ''; }, 4000);
}

function printReceipt() { window.print(); }
