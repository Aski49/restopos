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
  const opts = { weekday:'short', day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' };
  el.textContent = now.toLocaleString('en-GB', opts);
}
setInterval(updateClock, 1000);
updateClock();

// ── MODAL ────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id){ document.getElementById(id)?.classList.remove('open'); }
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
});

// ── POS ─────────────────────────────────────────────────────
let cart = [];
let orderType = 'Dine-In';
let payMethod  = 'Cash';
let discountPct = 0;
let serviceChargePct = parseFloat(window.SVC_PCT ?? 10);
let taxPct           = parseFloat(window.TAX_PCT ?? 8);

function setOrderType(t) {
  orderType = t;
  document.querySelectorAll('.ot-btn').forEach(b => b.classList.toggle('active', b.dataset.type === t));
  const tbl = document.getElementById('tableSelector');
  if (tbl) tbl.style.display = (t === 'Dine-In') ? 'inline-block' : 'none';
  renderCart();
}

function setPayMethod(m) {
  payMethod = m;
  document.querySelectorAll('.pay-btn').forEach(b => b.classList.toggle('active', b.dataset.method === m));
  const cashRow = document.getElementById('cashRow');
  if (cashRow) cashRow.style.display = (m === 'Cash') ? 'flex' : 'none';
  renderCart();
}

function filterCategory(cat) {
  document.querySelectorAll('.cat-btn').forEach(b => b.classList.toggle('active', b.dataset.cat === cat));
  document.querySelectorAll('.menu-btn').forEach(btn => {
    const show = cat === 'All' || btn.dataset.cat === cat;
    btn.style.display = show ? '' : 'none';
  });
}

function menuSearch(val) {
  const q = val.toLowerCase();
  document.querySelectorAll('.menu-btn').forEach(btn => {
    btn.style.display = btn.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
}

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

function renderCart() {
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
  set('billSubtotal',  'Rs. ' + subtotal.toFixed(2));
  set('billSvc',       'Rs. ' + svc.toFixed(2));
  set('billDisc',      '- Rs. ' + disc.toFixed(2));
  set('billTax',       'Rs. ' + tax.toFixed(2));
  set('billTotal',     'Rs. ' + total.toFixed(2));
  set('billTotalHidden', total.toFixed(2));

  const cashGiven = parseFloat(document.getElementById('cashGiven')?.value) || 0;
  const change = Math.max(0, cashGiven - total);
  set('changeAmt', 'Rs. ' + change.toFixed(2));

  // Store hidden fields
  ['billSubtotalH','billSvcH','billDiscH','billTaxH','billTotalH'].forEach(id => {
    const el = document.getElementById(id);
  });
  const form = document.getElementById('posForm');
  if (form) {
    setHidden(form, 'f_subtotal', subtotal.toFixed(2));
    setHidden(form, 'f_svc', svc.toFixed(2));
    setHidden(form, 'f_disc_amt', disc.toFixed(2));
    setHidden(form, 'f_tax', tax.toFixed(2));
    setHidden(form, 'f_total', total.toFixed(2));
    setHidden(form, 'f_order_type', orderType);
    setHidden(form, 'f_pay_method', payMethod);
    setHidden(form, 'f_cart', JSON.stringify(cart));
  }
}

function setHidden(form, name, val) {
  let inp = form.querySelector(`input[name="${name}"]`);
  if (!inp) { inp = document.createElement('input'); inp.type = 'hidden'; inp.name = name; form.appendChild(inp); }
  inp.value = val;
}

function updateChange() {
  const cashGiven = parseFloat(document.getElementById('cashGiven')?.value) || 0;
  const totalEl   = document.getElementById('billTotal');
  const total     = parseFloat(totalEl?.textContent.replace(/[^0-9.]/g,'')) || 0;
  const change    = Math.max(0, cashGiven - total);
  const el = document.getElementById('changeAmt');
  if (el) el.textContent = 'Rs. ' + change.toFixed(2);
  const form = document.getElementById('posForm');
  if (form) setHidden(form, 'f_cash_given', cashGiven.toFixed(2));
}

function clearBill() {
  cart = [];
  discountPct = 0;
  const disc = document.getElementById('discountInput');
  if (disc) disc.value = '';
  const cg = document.getElementById('cashGiven');
  if (cg) cg.value = '';
  document.querySelectorAll('.menu-btn').forEach(b => b.classList.remove('in-cart'));
  document.getElementById('billSuccess')?.setAttribute('style','display:none');
  renderCart();
}

// ── TABS ────────────────────────────────────────────────────
function switchTab(group, tabId) {
  document.querySelectorAll(`[data-tabgroup="${group}"]`).forEach(el => {
    el.classList.toggle('active', el.dataset.tab === tabId);
  });
  document.querySelectorAll(`[data-tabcontent="${group}"]`).forEach(el => {
    el.style.display = el.dataset.content === tabId ? '' : 'none';
  });
}

// ── ALERTS ──────────────────────────────────────────────────
function showAlert(msg, type = 'success', containerId = 'alertContainer') {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
  setTimeout(() => { el.innerHTML = ''; }, 4000);
}

// ── PRINT ───────────────────────────────────────────────────
function printReceipt() { window.print(); }
