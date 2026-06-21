<?php
require_once 'includes/config.php';
// NOTE: This is a PUBLIC page — intentionally no requireLogin() call.
// Meant to be shared via QR code on tables for customers to browse the menu.
$db = getDB();

$bizName  = getSetting('business_name', 'RestoPOS');
$bizAddr  = getSetting('address', '');
$bizPhone = getSetting('phone', '');

$categories = $db->query("SELECT * FROM menu_categories WHERE active=1 ORDER BY sort_order, name")->fetchAll();
$items = $db->query("SELECT * FROM menu_items WHERE active=1 ORDER BY category_id, name")->fetchAll();

$byCategory = [];
foreach ($items as $it) {
    $byCategory[$it['category_id']][] = $it;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu — <?= htmlspecialchars($bizName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#070809;--surf:#101218;--card:#151821;--border:#252a38;
  --accent:#f5a623;--accent-dim:#f5a62322;
  --text:#eef1f6;--muted:#5d6478;
}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Space Grotesk',sans-serif;padding-bottom:40px}
.menu-header{
  text-align:center;padding:36px 20px 24px;
  background:radial-gradient(ellipse at 50% 0%, rgba(245,166,35,.1), transparent 60%);
}
.menu-icon{font-size:44px}
.menu-biz-name{font-size:26px;font-weight:800;margin-top:8px;letter-spacing:-.5px}
.menu-biz-meta{font-size:12.5px;color:var(--muted);margin-top:6px}
.menu-title{font-size:13px;color:var(--accent);font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-top:14px}

.cat-nav{
  position:sticky;top:0;z-index:10;background:rgba(7,8,9,.92);backdrop-filter:blur(8px);
  display:flex;gap:8px;overflow-x:auto;padding:12px 16px;border-bottom:1px solid var(--border);
  scrollbar-width:none;
}
.cat-nav::-webkit-scrollbar{display:none}
.cat-nav a{
  flex-shrink:0;padding:8px 16px;border-radius:20px;font-size:12.5px;font-weight:700;
  background:var(--card);border:1px solid var(--border);color:var(--muted);text-decoration:none;white-space:nowrap;
}
.cat-nav a:hover{border-color:var(--accent);color:var(--accent)}

.menu-body{max-width:760px;margin:0 auto;padding:24px 18px}
.cat-section{margin-bottom:34px;scroll-margin-top:70px}
.cat-section-title{
  font-size:19px;font-weight:800;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid var(--accent);
  display:inline-block;
}
.item-grid{display:flex;flex-direction:column;gap:2px}
.item-row{
  display:flex;justify-content:space-between;align-items:flex-start;gap:14px;
  padding:14px 4px;border-bottom:1px solid rgba(255,255,255,.05);
}
.item-info{flex:1}
.item-name{font-size:15px;font-weight:700}
.item-desc{font-size:12.5px;color:var(--muted);margin-top:3px;line-height:1.5}
.item-price{
  font-family:'JetBrains Mono',monospace;font-weight:800;font-size:15px;color:var(--accent);
  white-space:nowrap;
}

.menu-footer{text-align:center;padding:30px 20px;color:var(--muted);font-size:12px;border-top:1px solid var(--border);margin-top:20px}
.menu-footer b{color:var(--accent)}

@media(max-width:480px){
  .menu-biz-name{font-size:22px}
  .item-name{font-size:14px}
  .item-price{font-size:14px}
}
</style>
</head>
<body>

<div class="menu-header">
  <div class="menu-icon">🍛</div>
  <div class="menu-biz-name"><?= htmlspecialchars($bizName) ?></div>
  <?php if ($bizAddr || $bizPhone): ?>
    <div class="menu-biz-meta"><?= htmlspecialchars($bizAddr) ?><?= $bizAddr && $bizPhone ? ' · ' : '' ?><?= htmlspecialchars($bizPhone) ?></div>
  <?php endif; ?>
  <div class="menu-title">Our Menu</div>
</div>

<?php if (!empty($categories)): ?>
<div class="cat-nav">
  <?php foreach ($categories as $c):
      if (empty($byCategory[$c['id']])) continue;
  ?>
    <a href="#cat-<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="menu-body">
  <?php foreach ($categories as $c):
      if (empty($byCategory[$c['id']])) continue;
  ?>
  <div class="cat-section" id="cat-<?= $c['id'] ?>">
    <div class="cat-section-title"><?= htmlspecialchars($c['name']) ?></div>
    <div class="item-grid">
      <?php foreach ($byCategory[$c['id']] as $item): ?>
        <div class="item-row">
          <div class="item-info">
            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
            <?php if (!empty($item['description'])): ?>
              <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>
            <?php endif; ?>
          </div>
          <div class="item-price">Rs. <?= number_format($item['price'], 2) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($items)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted)">Menu coming soon — please check back shortly.</div>
  <?php endif; ?>
</div>

<div class="menu-footer">
  Prices are subject to applicable taxes and service charge.<br>
  Powered by <b>RestoPOS Sri Lanka</b>
</div>

</body>
</html>
