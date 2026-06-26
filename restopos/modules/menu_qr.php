<?php
require_once '../includes/config.php';
requireAccess('menu');
$pageTitle = 'Menu QR Code'; $activePage = 'menu';

$bizName = getSetting('business_name', 'RestoPOS');

// Build the online menu URL reliably for XAMPP/localhost
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

// PHP_SELF = /restopos/modules/menu_qr.php
// We need   /restopos/online_menu.php
$scriptDir = dirname($_SERVER['PHP_SELF'] ?? '/restopos/modules/menu_qr.php'); // /restopos/modules
$projectDir = dirname($scriptDir); // /restopos
$menuUrl  = $protocol . '://' . $host . rtrim($projectDir, '/') . '/online_menu.php';

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">📱 Menu QR Code</div>
  <a href="<?=htmlspecialchars($menuUrl)?>" target="_blank" class="btn btn-outline">🌐 Open Online Menu</a>
</div>

<!-- Load pure-JS QR code library (works offline/localhost, no API call needed) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <div class="card" style="text-align:center;padding:40px 30px">
    <div style="font-size:15px;font-weight:700;margin-bottom:20px">Scan to view our menu &amp; order online</div>

    <!-- QR renders here by JS -->
    <div style="display:inline-block;padding:18px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.4)">
      <div id="qrcode"></div>
    </div>

    <div style="margin-top:16px;font-size:12px;color:var(--muted);font-family:'JetBrains Mono',monospace;word-break:break-all">
      <?=htmlspecialchars($menuUrl)?>
    </div>
    <div style="margin-top:20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <button onclick="downloadQR()" class="btn">⬇ Download QR</button>
      <button onclick="printQR()" class="btn btn-outline">🖨 Print QR</button>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="card-title">📋 How to use this QR code</div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach([
          ['1','Download or print the QR code using the buttons on the left.'],
          ['2','Place it on tables, counter, or front door — customers scan and see your live menu instantly.'],
          ['3','Customers browse, add to cart, and check out — you\'ll get notified instantly under Online Orders.'],
          ['4','Share the link via WhatsApp — copy the URL below and send it directly to customers.'],
        ] as [$n,$t]): ?>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <span style="background:var(--accent);color:#000;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0"><?=$n?></span>
          <div><?=$t?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">🔗 Share Menu Link</div>
      <div class="form-group">
        <input type="text" id="menuUrlInput" class="form-control" value="<?=htmlspecialchars($menuUrl)?>" readonly onclick="this.select()">
      </div>
      <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap">
        <button onclick="copyLink()" class="btn btn-sm btn-outline" id="copyBtn">📋 Copy Link</button>
        <a href="https://wa.me/?text=<?=urlencode('🍽 View our menu and order online: '.$menuUrl)?>" target="_blank" class="btn btn-sm btn-wa">📲 Share via WhatsApp</a>
      </div>
    </div>

    <div class="card">
      <div class="card-title">⚙ QR Size</div>
      <select class="form-control" onchange="regenerateQR(parseInt(this.value))" style="max-width:200px">
        <option value="180">Small (180×180)</option>
        <option value="240" selected>Medium (240×240)</option>
        <option value="320">Large (320×320)</option>
      </select>
      <div class="alert alert-info fs-12" style="margin-top:12px">
        💡 QR always points to your live menu. Regenerated fresh from this page every time.
      </div>
    </div>
  </div>
</div>

<script>
var QR_URL = <?=json_encode($menuUrl)?>;
var qrInstance = null;
var currentSize = 240;

function buildQR(size) {
  currentSize = size;
  document.getElementById('qrcode').innerHTML = '';
  qrInstance = new QRCode(document.getElementById('qrcode'), {
    text: QR_URL,
    width: size,
    height: size,
    colorDark: '#000000',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
  });
}

function regenerateQR(size) { buildQR(size); }

function downloadQR() {
  setTimeout(function() {
    var canvas = document.querySelector('#qrcode canvas');
    if (!canvas) { alert('QR code still rendering, please try again.'); return; }
    var link = document.createElement('a');
    link.download = '<?=htmlspecialchars(preg_replace('/[^a-z0-9]/i','_',$bizName))?>_QR.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
  }, 300);
}

function printQR() {
  var canvas = document.querySelector('#qrcode canvas');
  if (!canvas) { alert('QR code still rendering, please try again.'); return; }
  var dataUrl = canvas.toDataURL('image/png');
  var win = window.open('','','width=500,height=600');
  win.document.write('<html><head><title>QR — <?=htmlspecialchars($bizName)?></title><style>body{text-align:center;font-family:Arial,sans-serif;padding:30px}</style></head><body>');
  win.document.write('<div style="font-size:24px;font-weight:900;margin-bottom:8px"><?=htmlspecialchars($bizName)?></div>');
  win.document.write('<div style="font-size:13px;color:#555;margin-bottom:20px">Scan to view our menu &amp; order online</div>');
  win.document.write('<img src="' + dataUrl + '" width="280" height="280">');
  win.document.write('<div style="font-size:11px;color:#777;margin-top:16px">' + QR_URL + '</div>');
  win.document.write('</body></html>');
  win.document.close();
  win.focus();
  setTimeout(function(){ win.print(); win.close(); }, 500);
}

function copyLink() {
  document.getElementById('menuUrlInput').select();
  document.execCommand('copy');
  var btn = document.getElementById('copyBtn');
  btn.textContent = '✅ Copied!';
  setTimeout(function(){ btn.textContent = '📋 Copy Link'; }, 2000);
}

// Build on load
window.onload = function() { buildQR(240); };
</script>
<?php include '../includes/footer.php'; ?>

include '../includes/header.php';
?>
<div class="page-header">
  <div class="page-title">📱 Menu QR Code</div>
  <a href="<?=htmlspecialchars($menuUrl)?>" target="_blank" class="btn btn-outline">🌐 Open Online Menu</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <!-- QR Code display -->
  <div class="card" style="text-align:center;padding:40px 30px">
    <div style="font-size:15px;font-weight:700;margin-bottom:20px">Scan to view our menu &amp; order online</div>
    <!-- Google Charts QR API — no server dependency -->
    <div style="display:inline-block;padding:16px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.4)">
      <img id="qrImg"
        src="https://chart.googleapis.com/chart?chs=280x280&cht=qr&chl=<?=urlencode($menuUrl)?>&choe=UTF-8&chld=H|1"
        alt="QR Code" width="280" height="280"
        style="display:block;border-radius:8px">
    </div>
    <div style="margin-top:16px;font-size:12px;color:var(--muted);font-family:'JetBrains Mono',monospace;word-break:break-all">
      <?=htmlspecialchars($menuUrl)?>
    </div>
    <div style="margin-top:20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
      <a id="dlBtn"
        href="https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl=<?=urlencode($menuUrl)?>&choe=UTF-8&chld=H|1"
        download="<?=htmlspecialchars(preg_replace('/[^a-z0-9]/i','_',$bizName))?>_QR.png"
        target="_blank" class="btn">⬇ Download QR</a>
      <button onclick="printQR()" class="btn btn-outline">🖨 Print QR</button>
    </div>
  </div>

  <!-- Instructions -->
  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="card-title">📋 How to use this QR code</div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;gap:12px;align-items:flex-start">
          <span style="background:var(--accent);color:#000;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0">1</span>
          <div><strong>Download or print</strong> the QR code using the buttons on the left.</div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <span style="background:var(--accent);color:#000;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0">2</span>
          <div><strong>Place it on tables, counter, or front door</strong> — customers scan and see your live menu instantly.</div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <span style="background:var(--accent);color:#000;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0">3</span>
          <div><strong>Customers browse, add to cart, and check out</strong> — you'll get notified instantly under Online Orders.</div>
        </div>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <span style="background:var(--accent);color:#000;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0">4</span>
          <div><strong>Share the link via WhatsApp</strong> — copy the URL below and send it directly to customers.</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">🔗 Share Menu Link</div>
      <div class="form-group">
        <input type="text" id="menuUrlInput" class="form-control" value="<?=htmlspecialchars($menuUrl)?>" readonly onclick="this.select()">
      </div>
      <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap">
        <button onclick="copyLink()" class="btn btn-sm btn-outline" id="copyBtn">📋 Copy Link</button>
        <a href="https://wa.me/?text=<?=urlencode('🍽 View our full menu and order online: '.$menuUrl)?>" target="_blank" class="btn btn-sm btn-wa">📲 Share via WhatsApp</a>
      </div>
    </div>

    <div class="card">
      <div class="card-title">⚙ QR Code Settings</div>
      <div class="form-group mb-12">
        <label class="form-label">QR Code Size</label>
        <select class="form-control" onchange="resizeQR(this.value)" style="max-width:200px">
          <option value="200">Small (200×200)</option>
          <option value="280" selected>Medium (280×280)</option>
          <option value="400">Large (400×400)</option>
        </select>
      </div>
      <div class="alert alert-info fs-12">
        💡 The QR code always points to your live menu. If your domain or URL changes, regenerate the QR from this page.
      </div>
    </div>
  </div>

</div>

<!-- Print-only layout -->
<div id="printArea" style="display:none">
  <div style="text-align:center;font-family:Arial,sans-serif;padding:30px">
    <div style="font-size:28px;font-weight:900;margin-bottom:8px"><?=htmlspecialchars($bizName)?></div>
    <div style="font-size:14px;color:#555;margin-bottom:20px">Scan to view our menu &amp; order online</div>
    <img src="https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=<?=urlencode($menuUrl)?>&choe=UTF-8&chld=H|1" width="280" height="280" style="display:block;margin:0 auto 16px">
    <div style="font-size:12px;color:#777"><?=htmlspecialchars($menuUrl)?></div>
  </div>
</div>

<script>
function copyLink() {
  var input = document.getElementById('menuUrlInput');
  input.select();
  document.execCommand('copy');
  var btn = document.getElementById('copyBtn');
  btn.textContent = '✅ Copied!';
  setTimeout(function(){ btn.textContent = '📋 Copy Link'; }, 2000);
}

function resizeQR(size) {
  var url = 'https://chart.googleapis.com/chart?chs=' + size + 'x' + size +
            '&cht=qr&chl=<?=urlencode($menuUrl)?>&choe=UTF-8&chld=H|1';
  document.getElementById('qrImg').src = url;
  document.getElementById('qrImg').width  = Math.min(parseInt(size), 280);
  document.getElementById('qrImg').height = Math.min(parseInt(size), 280);
}

function printQR() {
  var content = document.getElementById('printArea').innerHTML;
  var win = window.open('', '', 'width=500,height=600');
  win.document.write('<html><head><title>QR Code — <?=htmlspecialchars($bizName)?></title></head><body>' + content + '</body></html>');
  win.document.close();
  win.focus();
  win.print();
  win.close();
}
</script>
<?php include '../includes/footer.php'; ?>
