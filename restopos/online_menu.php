<?php
// ══════════════════════════════════════════════════════════════
// COVE CAFÉ & LOUNGE — Online Menu
// All POST/AJAX handled here BEFORE any HTML output
// ══════════════════════════════════════════════════════════════
error_reporting(0);
ini_set('display_errors', 0);

require_once 'includes/config.php';
$db       = getDB();
$bizPhone = '+94772298545';
$svcPct   = (float)getSetting('service_charge_pct', '10');
$taxPct   = (float)getSetting('tax_pct', '8');

// ── Handle ALL AJAX POST requests before any HTML output ───────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any accidental output buffer
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache');

    // ── RESERVATION ────────────────────────────────────────────
    if (isset($_POST['make_reservation'])) {
        $rName  = trim($_POST['res_name']  ?? '');
        $rPhone = trim($_POST['res_phone'] ?? '');
        $rDate  = $_POST['res_date']       ?? '';
        $rTime  = $_POST['res_time']       ?? '';
        $rEnd   = !empty($_POST['res_end_time']) ? $_POST['res_end_time'] : null;
        $rPax   = (int)($_POST['res_pax'] ?? 2);
        $rLoc   = trim($_POST['res_location'] ?? '');
        $rNote  = trim($_POST['res_note']  ?? '');

        if (!$rName || !$rPhone || !$rDate || !$rTime) {
            echo json_encode(['ok'=>false, 'error'=>'Please fill all required fields.']);
            exit;
        }
        try {
            $db->prepare("INSERT INTO reservations
                (customer_name,contact,res_date,res_time,res_end_time,pax,location,notes,status)
                VALUES(?,?,?,?,?,?,?,?,'Confirmed')")
               ->execute([$rName,$rPhone,$rDate,$rTime,$rEnd,$rPax,$rLoc,$rNote]);
            echo json_encode([
                'ok'   => true,
                'name' => $rName,
                'date' => date('d M Y', strtotime($rDate)),
                'time' => date('h:i A', strtotime($rTime)),
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok'=>false, 'error'=>'Reservation failed. Please call us directly.']);
        }
        exit;
    }

    // ── PLACE ORDER ────────────────────────────────────────────
    if (isset($_POST['place_order'])) {
        $name    = trim($_POST['customer_name']  ?? '');
        $phone   = trim($_POST['customer_phone'] ?? '');
        $note    = trim($_POST['customer_note']  ?? '');
        $rawType = $_POST['order_type'] ?? 'takeaway';
        $type    = in_array($rawType, ['takeaway','card','bank_transfer']) ? $rawType : 'takeaway';
        $cartRaw = json_decode($_POST['cart'] ?? '[]', true);

        if (!$name || !$phone) {
            echo json_encode(['ok'=>false, 'error'=>'Name and phone are required.']);
            exit;
        }
        if (empty($cartRaw)) {
            echo json_encode(['ok'=>false, 'error'=>'Your cart is empty.']);
            exit;
        }
        try {
            $db->beginTransaction();
            $orderNo  = 'ONL-' . date('Ymd') . '-' . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);
            $subtotal = array_sum(array_map(fn($i) => (float)$i['price'] * (int)$i['qty'], $cartRaw));
            $svc      = round($subtotal * $svcPct / 100, 2);
            $tax      = round(($subtotal + $svc) * $taxPct / 100, 2);
            $total    = round($subtotal + $svc + $tax, 2);

            $db->prepare("INSERT INTO online_orders
                (order_no,customer_name,customer_phone,customer_note,
                 order_type,subtotal,service_charge,tax,total,status,seen)
                VALUES(?,?,?,?,?,?,?,?,?,'new',0)")
               ->execute([$orderNo,$name,$phone,$note,$type,$subtotal,$svc,$tax,$total]);

            $orderId = $db->lastInsertId();
            $ins = $db->prepare("INSERT INTO online_order_items
                (order_id,menu_item_id,item_name,price,qty,line_total)
                VALUES(?,?,?,?,?,?)");

            foreach ($cartRaw as $ci) {
                $itemId    = isset($ci['id']) && is_numeric($ci['id']) ? (int)$ci['id'] : null;
                $itemName  = substr(trim($ci['name'] ?? 'Item'), 0, 150);
                $itemPrice = (float)($ci['price'] ?? 0);
                $itemQty   = max(1, (int)($ci['qty'] ?? 1));
                $ins->execute([$orderId, $itemId, $itemName, $itemPrice, $itemQty, round($itemPrice * $itemQty, 2)]);
            }
            $db->commit();
            echo json_encode(['ok'=>true, 'order_no'=>$orderNo, 'total'=>$total]);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['ok'=>false, 'error'=>'Order could not be saved. Please try again.']);
        }
        exit;
    }

    // Unknown POST action
    echo json_encode(['ok'=>false, 'error'=>'Unknown action.']);
    exit;
}

// ── Fetch menu data for page display ──────────────────────────
$categories = $db->query("SELECT * FROM menu_categories WHERE active=1 ORDER BY sort_order,name")->fetchAll();
$allItems   = $db->query("SELECT mi.*,mc.name AS cat_name FROM menu_items mi
                           JOIN menu_categories mc ON mi.category_id=mc.id
                           WHERE mi.active=1 ORDER BY mc.sort_order,mi.name")->fetchAll();
$byCategory = [];
foreach ($allItems as $it) { $byCategory[$it['category_id']][] = $it; }

// ── Fetch active promotions ────────────────────────────────────
$promos = [];
try {
    $promos = $db->query("SELECT * FROM promotions WHERE active=1
                          AND (valid_to IS NULL OR valid_to >= CURDATE())
                          ORDER BY created_at DESC")->fetchAll();
} catch (Exception $e) { $promos = []; }

// ── Helpers ────────────────────────────────────────────────────
$PALETTE = ['#6B9E3A','#C8923A','#4A7DB5','#9B6BAA','#B55A5A','#3A9E8A','#C87A3A','#6B7AB5'];
function catEmoji($n) {
    $m = ['rice'=>'🍛','kottu'=>'🥘','noodle'=>'🍜','beverage'=>'🥤','drink'=>'🥤',
          'snack'=>'🍟','dessert'=>'🍮','soup'=>'🍲','chicken'=>'🍗','fish'=>'🐟',
          'sea'=>'🦐','veg'=>'🥗','burger'=>'🍔','pizza'=>'🍕','pasta'=>'🍝',
          'coffee'=>'☕','tea'=>'🍵','juice'=>'🥤','shake'=>'🥤','cake'=>'🍰',
          'sandwich'=>'🥪','shisha'=>'💨','hookah'=>'💨','bread'=>'🍞','short'=>'🥮'];
    $n = strtolower($n);
    foreach ($m as $k => $e) { if (str_contains($n, $k)) return $e; }
    return '🍽';
}
function promoLabel($p) {
    if ($p['promo_type']==='percent_off') return number_format($p['discount_value'],0).'% OFF';
    if ($p['promo_type']==='fixed_off')   return 'Rs.'.number_format($p['discount_value'],0).' OFF';
    if ($p['promo_type']==='buy_x_get_y') return 'Buy '.$p['buy_qty'].' Get '.$p['get_qty'];
    return 'OFFER';
}

// ── Photo / asset paths ────────────────────────────────────────
$LOGO   = 'assets/uploads/cafe/logo.jpg';
$PHOTOS = [
    ['file'=>'assets/uploads/cafe/entrance.webp','label'=>'Entrance &amp; Murals',
     'title'=>'Where the City<br><em>Falls Quiet</em>',
     'desc'=>'A neighbourhood café unlike any other. Step in and the Colombo rush disappears.'],
    ['file'=>'assets/uploads/cafe/garden.webp','label'=>'Backyard Garden',
     'title'=>'Tropical<br><em>Cabanas</em>',
     'desc'=>'Thatched-roof cabanas, stone pathways, lush grass. Our hidden backyard paradise.'],
    ['file'=>'assets/uploads/cafe/interior.webp','label'=>'Inside Cove',
     'title'=>'Green Walls &amp;<br><em>Golden Light</em>',
     'desc'=>'Warm pendant lights, lush green walls, fresh coffee. A slow morning feels like this.'],
    ['file'=>'assets/uploads/cafe/shisha.webp','label'=>'Shisha Lounge',
     'title'=>'End the Day<br><em>Your Way</em>',
     'desc'=>'Premium blends, relaxed atmosphere. The perfect way to close a long day.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Cove Café &amp; Lounge — Kirulapone, Colombo</title>
<meta name="description" content="Cove Café &amp; Lounge — Coffee, wood-fired pizza, karaoke, shisha, backyard cabanas in Kirulapone, Colombo.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════════════
   COVE CAFÉ & LOUNGE — COMPLETE CLEAN CSS
   ═══════════════════════════════════════════════════════════ */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --void:#050703;--v2:#0A0E07;--v3:#111A0C;
  --parch:#F2EDE4;--parch2:#C0B8A8;
  --g:#6B9E3A;--g2:#8BC050;--g3:#4A7A20;
  --gold:#C8923A;
  --rule:rgba(107,158,58,0.12);--glow:rgba(107,158,58,0.07);
  --serif:'Cormorant Garamond',Georgia,serif;
  --sans:'DM Sans',system-ui,sans-serif;
  --mono:'JetBrains Mono',monospace;
}
html{scroll-behavior:smooth}
body{background:var(--void);color:var(--parch);font-family:var(--sans);overflow-x:hidden;-webkit-font-smoothing:antialiased}
::selection{background:var(--g);color:var(--void)}
::-webkit-scrollbar{width:2px}::-webkit-scrollbar-track{background:var(--void)}::-webkit-scrollbar-thumb{background:var(--g3)}
button{font-family:var(--sans);cursor:pointer;border:none}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}

/* ── FLOATING SOCIAL ─────────────────────────────────────── */
.float-social{position:fixed;right:0;top:50%;transform:translateY(-50%);z-index:750;display:flex;flex-direction:column;border-radius:10px 0 0 10px;overflow:hidden;box-shadow:-4px 0 20px rgba(0,0,0,.4)}
.fsb{width:48px;height:48px;display:flex;align-items:center;justify-content:center;color:#fff;transition:width .3s;text-decoration:none}
.fsb:hover{width:58px}
.fsb svg{width:22px;height:22px;fill:currentColor;flex-shrink:0}
.fsb-wa{background:#25D366}.fsb-wa:hover{background:#1DA851}
.fsb-ig{background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)}
.fsb-gm{background:#4285F4}.fsb-gm:hover{background:#2563EB}

/* ── NAVBAR ──────────────────────────────────────────────── */
#nav{position:fixed;top:0;left:0;right:0;z-index:800;display:flex;align-items:center;justify-content:space-between;padding:0 5%;height:64px;transition:background .4s,border-color .4s}
#nav.scrolled{background:rgba(5,7,3,.96);backdrop-filter:blur(20px);border-bottom:1px solid var(--rule)}
.nav-brand{display:flex;align-items:center;gap:10px;cursor:pointer}
.nav-logo{height:34px;width:auto}
.nav-brand-name{font-family:var(--serif);font-size:18px;font-weight:600;color:var(--g)}
.nav-brand-sub{font-family:var(--serif);font-size:11px;font-weight:300;font-style:italic;color:var(--gold);opacity:.8;letter-spacing:1px}
.nav-center{display:flex;align-items:center;gap:0;position:absolute;left:50%;transform:translateX(-50%)}
.nlink{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(242,237,228,.45);background:none;padding:8px 12px;transition:.2s;white-space:nowrap}
.nlink:hover{color:var(--parch)}
.nav-r{display:flex;align-items:center;gap:8px}
.n-ord{display:none;align-items:center;gap:7px;background:rgba(107,158,58,.08);border:1px solid rgba(107,158,58,.25);color:var(--g);font-size:10px;letter-spacing:1.5px;text-transform:uppercase;border-radius:100px;padding:7px 14px;cursor:pointer;white-space:nowrap}
.n-ord.on{display:flex}
.ord-b{background:var(--g);color:var(--void);border-radius:100px;padding:1px 7px;font-size:9px;font-weight:700}
.n-book{background:var(--g);color:var(--void);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;border-radius:100px;padding:9px 18px;cursor:pointer;white-space:nowrap}
.n-book:hover{background:var(--g2)}
.n-ham{display:none;flex-direction:column;gap:5px;background:none;padding:4px;cursor:pointer}
.n-ham span{width:20px;height:1px;background:var(--parch2)}
.mob-nav{display:none;position:fixed;top:64px;left:0;right:0;z-index:790;background:rgba(5,7,3,.97);border-bottom:1px solid var(--rule);flex-direction:column;padding:12px 5% 20px}
.mob-nav.open{display:flex}
.ml{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:rgba(242,237,228,.5);background:none;padding:12px 0;text-align:left;border-bottom:1px solid rgba(255,255,255,.04);cursor:pointer}
.ml:hover{color:var(--parch)}
.mob-bk{margin-top:12px;padding:13px;background:rgba(107,158,58,.1);border:1px solid rgba(107,158,58,.25);color:var(--g);font-size:11px;letter-spacing:2px;text-transform:uppercase;cursor:pointer;text-align:center}

/* ── HERO SLIDER ──────────────────────────────────────────── */
#gallery{position:relative;height:100vh;overflow:hidden;background:#050703}
.slide{position:absolute;inset:0;opacity:0;transition:opacity 1.4s ease}
.slide.on{opacity:1}
.slide-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.slide::after{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(5,7,3,0) 0%,rgba(5,7,3,.3) 40%,rgba(5,7,3,.7) 70%,rgba(5,7,3,.97) 100%)}
.gal-content{position:absolute;bottom:80px;left:5%;right:5%;z-index:2}
.sl-label{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:var(--g);margin-bottom:12px;display:flex;align-items:center;gap:10px}
.sl-label::before{content:'';width:18px;height:1px;background:var(--g);opacity:.6}
.sl-title{font-family:var(--serif);font-size:clamp(30px,6vw,68px);font-weight:400;line-height:.92;letter-spacing:-2px;color:var(--parch);margin-bottom:14px}
.sl-title em{font-style:italic;color:var(--gold)}
.sl-desc{font-size:clamp(12px,1.4vw,14px);font-weight:300;color:rgba(242,237,228,.6);max-width:460px;line-height:1.8;margin-bottom:22px}
.sl-cta{display:inline-flex;align-items:center;gap:10px;background:rgba(107,158,58,.12);border:1px solid rgba(107,158,58,.4);color:var(--g);font-size:10px;letter-spacing:2.5px;text-transform:uppercase;border-radius:100px;padding:12px 24px;cursor:pointer;transition:.3s}
.sl-cta:hover{background:rgba(107,158,58,.22)}
.sl-dots{position:absolute;right:5%;bottom:88px;z-index:3;display:flex;flex-direction:column;gap:7px}
.sdot{width:3px;height:3px;border-radius:50%;background:rgba(242,237,228,.25);cursor:pointer;transition:.4s}
.sdot.on{background:var(--g);transform:scaleY(2.8);border-radius:2px}
.sl-prog{position:absolute;bottom:0;left:0;right:0;height:2px;background:rgba(255,255,255,.06);z-index:3}
.prog-f{height:100%;background:var(--g);width:0%}

/* ── PROMOTIONS ───────────────────────────────────────────── */
#promos{background:var(--v2);border-top:1px solid var(--rule);border-bottom:1px solid var(--rule);padding:56px 5%}
.sec-hed{text-align:center;margin-bottom:36px}
.sec-tag{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:rgba(107,158,58,.55);margin-bottom:8px}
.sec-title{font-family:var(--serif);font-size:clamp(24px,3.5vw,38px);font-weight:400;color:var(--parch);font-style:italic}
.promo-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;max-width:1100px;margin:0 auto}
.promo-card{background:rgba(107,158,58,.05);border:1px solid rgba(107,158,58,.18);border-radius:4px;padding:20px 22px;display:flex;align-items:flex-start;gap:14px;position:relative;overflow:hidden;transition:.3s}
.promo-card:hover{border-color:rgba(107,158,58,.4);background:rgba(107,158,58,.1)}
.promo-card::before{content:'';position:absolute;top:0;left:0;bottom:0;width:3px;background:var(--g)}
.promo-badge{background:var(--g);color:var(--void);font-size:12px;font-weight:700;border-radius:4px;padding:6px 12px;flex-shrink:0;white-space:nowrap}
.promo-name{font-family:var(--serif);font-size:17px;font-weight:500;color:var(--parch);margin-bottom:4px}
.promo-desc{font-size:12px;color:rgba(242,237,228,.45);line-height:1.6}
.promo-valid{font-size:10px;color:rgba(107,158,58,.5);margin-top:6px}
.promo-empty{text-align:center;padding:24px;font-size:13px;color:rgba(242,237,228,.3);font-style:italic}

/* ── STORY PANELS ─────────────────────────────────────────── */
.panel{min-height:100vh;display:flex;align-items:flex-end;position:relative;overflow:hidden}
.panel::after{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(5,7,3,0) 0%,rgba(5,7,3,.4) 40%,rgba(5,7,3,.85) 70%,rgba(5,7,3,.98) 100%);z-index:1}
.p-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;transition:transform 12s ease}
.panel:hover .p-bg{transform:scale(1.04)}
.p-num{position:absolute;top:52px;left:5%;z-index:2;font-family:var(--serif);font-size:clamp(80px,14vw,180px);font-weight:300;color:rgba(107,158,58,.05);line-height:1;pointer-events:none}
.p-tag{position:absolute;top:58px;right:5%;z-index:2;font-size:9px;letter-spacing:3px;text-transform:uppercase;color:rgba(107,158,58,.4)}
.p-body{position:relative;z-index:2;padding:clamp(30px,5vw,60px) 5% clamp(52px,8vw,80px);max-width:700px}
.p-eye{display:flex;align-items:center;gap:10px;font-size:9px;letter-spacing:3.5px;text-transform:uppercase;color:var(--g);margin-bottom:14px;opacity:.85}
.p-eye::before{content:'';width:14px;height:1px;background:var(--g);flex-shrink:0}
.p-icon{font-size:clamp(26px,4vw,42px);margin-bottom:14px;display:block}
.p-title{font-family:var(--serif);font-size:clamp(34px,6vw,72px);font-weight:400;line-height:.92;letter-spacing:-2px;color:var(--parch);margin-bottom:18px}
.p-title em{font-style:italic;color:var(--gold)}
.p-txt{font-size:clamp(13px,1.4vw,15px);font-weight:300;line-height:1.9;color:rgba(242,237,228,.52);max-width:500px}
.p-txt p{margin-bottom:10px}

/* ── CATEGORY BAR ─────────────────────────────────────────── */
#catBar{position:sticky;top:64px;z-index:700;background:rgba(5,7,3,.95);backdrop-filter:blur(20px);border-bottom:1px solid var(--rule)}
.cat-in{max-width:1100px;margin:0 auto;display:flex;overflow-x:auto;scrollbar-width:none;padding:0 5%}
.cat-in::-webkit-scrollbar{display:none}
.cb{flex-shrink:0;padding:14px 15px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(242,237,228,.32);background:none;border-bottom:2px solid transparent;transition:.2s;white-space:nowrap;cursor:pointer}
.cb:hover{color:var(--parch2)}.cb.on{color:var(--g);border-bottom-color:var(--g)}

/* ── MENU SECTION ─────────────────────────────────────────── */
#menu{background:var(--void);padding:40px 0 180px}
.menu-wrap{max-width:1100px;margin:0 auto;padding:0 5%;display:flex;gap:32px}
.menu-main{flex:1;min-width:0}
.srch-row{position:relative;margin-bottom:36px}
.srch{width:100%;padding:0 0 12px;background:transparent;border:none;border-bottom:1px solid rgba(107,158,58,.2);color:var(--parch);font-family:var(--sans);font-size:14px;transition:.3s}
.srch::placeholder{color:rgba(242,237,228,.25);font-size:11px;letter-spacing:2px;text-transform:uppercase}
.srch:focus{outline:none;border-bottom-color:rgba(107,158,58,.5)}
.msec{margin-bottom:48px}
.ms-head{display:flex;align-items:center;gap:12px;padding-bottom:12px;margin-bottom:4px;border-bottom:1px solid var(--rule)}
.ms-icon{font-size:18px;opacity:.55}.ms-title{font-family:var(--serif);font-size:21px;font-weight:400;color:var(--parch2)}
.ms-cnt{font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(107,158,58,.4);margin-left:auto}
/* Menu item row */
.mi{display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.04);cursor:pointer;transition:padding .25s;position:relative}
.mi:hover{padding-left:6px}
.mi-th{width:78px;height:78px;border-radius:10px;flex-shrink:0;overflow:hidden;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;font-size:30px;border:1px solid rgba(255,255,255,.06)}
.mi-th img{width:100%;height:100%;object-fit:cover}
.mi-info{flex:1;min-width:0}
.mi-cat{font-size:9px;letter-spacing:2px;text-transform:uppercase;margin-bottom:3px;opacity:.5}
.mi-name{font-family:var(--serif);font-size:17px;font-weight:500;line-height:1.2;margin-bottom:4px;color:var(--parch)}
.mi-desc{font-size:12px;font-weight:300;color:rgba(242,237,228,.38);line-height:1.6;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.mi-r{display:flex;flex-direction:column;align-items:flex-end;gap:10px;flex-shrink:0}
.mi-price{font-family:var(--mono);font-size:14px;font-weight:500;color:var(--gold);white-space:nowrap}
.mi-price small{font-size:9px;color:rgba(200,146,58,.5);margin-right:1px}
.madd{width:34px;height:34px;border-radius:50%;border:1px solid rgba(107,158,58,.35);background:transparent;color:var(--g);font-size:22px;line-height:1;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s}
.madd:hover{background:rgba(107,158,58,.15);border-color:var(--g);transform:scale(1.1)}
.mqc{display:none;align-items:center;gap:6px}
.mqb{width:28px;height:28px;border-radius:50%;border:1px solid rgba(255,255,255,.1);background:transparent;color:rgba(242,237,228,.5);font-size:15px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s}
.mqb:hover{border-color:var(--g);color:var(--g)}
.mqn{font-family:var(--mono);font-size:13px;min-width:18px;text-align:center;color:var(--g)}

/* ── SIDEBAR CART ─────────────────────────────────────────── */
.cart-col{width:264px;flex-shrink:0}
.cart-p{position:sticky;top:118px;border:1px solid var(--rule);background:var(--v2)}
.cart-h{padding:14px 16px;border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between}
.cart-ttl{font-family:var(--serif);font-size:15px;color:var(--parch2)}
.c-badge{background:var(--g);color:var(--void);font-size:9px;font-weight:700;border-radius:100px;padding:1px 7px;display:none}
.cart-body{padding:10px 14px;min-height:60px;max-height:280px;overflow-y:auto}
.c-empty{font-size:11px;color:rgba(242,237,228,.22);text-align:center;padding:24px;font-style:italic}
.ci{display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.ci-em{font-size:17px;flex-shrink:0;width:22px;text-align:center}
.ci-info{flex:1;min-width:0}
.ci-n{font-size:12px;color:var(--parch2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ci-p{font-size:10px;color:rgba(200,146,58,.45);font-family:var(--mono)}
.ci-ctrl{display:flex;align-items:center;gap:3px;flex-shrink:0}
.cib{width:20px;height:20px;border-radius:50%;border:1px solid rgba(255,255,255,.1);background:transparent;color:rgba(242,237,228,.45);font-size:13px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s}
.cib:hover{border-color:var(--g);color:var(--g)}
.ci-q{font-family:var(--mono);font-size:11px;min-width:14px;text-align:center;color:var(--g)}
.ci-t{font-size:10px;font-family:var(--mono);color:rgba(200,146,58,.6);min-width:52px;text-align:right}
.c-sum{padding:10px 14px;border-top:1px solid var(--rule)}
.cs-r{display:flex;justify-content:space-between;font-size:10px;color:rgba(242,237,228,.3);font-family:var(--mono);margin-bottom:4px}
.cs-t{display:flex;justify-content:space-between;font-size:13px;margin-top:10px;padding-top:10px;border-top:1px solid var(--rule)}
.cs-t .v{color:var(--gold);font-family:var(--mono)}
.c-cta{width:100%;padding:13px;background:var(--g);color:var(--void);font-family:var(--sans);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:.2s}
.c-cta:hover{background:var(--g2)}.c-cta:disabled{opacity:.3;cursor:not-allowed}

/* ── PHOTO GRID ───────────────────────────────────────────── */
#photos{background:var(--v2);padding:72px 5%;border-top:1px solid var(--rule)}
.photo-grid{max-width:1100px;margin:36px auto 0;display:grid;grid-template-columns:1.4fr 1fr;grid-template-rows:310px 310px;gap:8px}
.photo-card{position:relative;overflow:hidden;cursor:pointer}.photo-card:first-child{grid-row:1/3}
.photo-card::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(5,7,3,.8) 0%,rgba(5,7,3,.1) 55%,transparent 100%);transition:.4s}
.photo-card:hover::after{opacity:.6}
.photo-card img{width:100%;height:100%;object-fit:cover;transition:transform 8s ease}
.photo-card:hover img{transform:scale(1.05)}
.photo-caption{position:absolute;bottom:0;left:0;right:0;z-index:2;padding:16px 18px}
.pc-tag{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:rgba(107,158,58,.75);margin-bottom:3px}
.pc-title{font-family:var(--serif);font-size:17px;color:var(--parch);font-weight:400}

/* ── FEATURES ─────────────────────────────────────────────── */
#features{background:var(--void);border-top:1px solid var(--rule);padding:64px 5%}
.feat-grid{max-width:1100px;margin:36px auto 0;display:grid;grid-template-columns:repeat(4,1fr)}
.fi{padding:26px 20px;text-align:center;border-right:1px solid var(--rule);border-bottom:1px solid var(--rule);transition:.3s}
.fi:nth-child(4n){border-right:none}.fi:nth-child(n+5){border-bottom:none}
.fi:hover{background:rgba(107,158,58,.03)}
.fi-icon{font-size:26px;margin-bottom:10px}
.fi-t{font-family:var(--serif);font-size:15px;color:var(--parch2);margin-bottom:4px}
.fi-d{font-size:11px;color:rgba(242,237,228,.3);line-height:1.7;font-weight:300}

/* ── RESERVATION ──────────────────────────────────────────── */
#reserve{background:var(--v3);border-top:1px solid var(--rule);padding:88px 5%}
.res-wrap{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:start}
.res-eye{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:rgba(107,158,58,.5);margin-bottom:10px}
.res-tit{font-family:var(--serif);font-size:clamp(32px,5vw,54px);font-weight:400;line-height:1;margin-bottom:16px;color:var(--parch)}
.res-tit em{font-style:italic;color:var(--gold)}
.res-desc{font-size:13px;font-weight:300;color:rgba(242,237,228,.45);line-height:1.9;margin-bottom:22px}
.res-ilist{display:flex;flex-direction:column;gap:10px;margin-bottom:22px}
.ri{display:flex;align-items:center;gap:10px;font-size:12px;color:rgba(242,237,228,.5)}
.ri strong{color:var(--g);font-weight:400}
.soc-row{display:flex;gap:9px;flex-wrap:wrap}
.soc-btn{display:inline-flex;align-items:center;gap:7px;border:1px solid rgba(255,255,255,.12);border-radius:100px;padding:8px 15px;font-size:11px;color:rgba(242,237,228,.5);background:rgba(255,255,255,.04);transition:.3s;text-decoration:none}
.soc-btn svg{width:13px;height:13px;fill:currentColor;flex-shrink:0}
.soc-wa:hover{border-color:rgba(37,211,102,.5);color:#25D366;background:rgba(37,211,102,.06)}
.soc-ig:hover{border-color:rgba(225,48,108,.5);color:#E1306C;background:rgba(225,48,108,.05)}
.soc-gm:hover{border-color:rgba(66,133,244,.5);color:#4285F4;background:rgba(66,133,244,.05)}
/* Form */
.rf-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px 16px;margin-bottom:20px}
.ff{position:relative;padding-top:18px;border-bottom:1px solid rgba(107,158,58,.15);transition:.3s}
.ff:focus-within{border-bottom-color:rgba(107,158,58,.5)}
.ff label{position:absolute;top:18px;left:0;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.45);pointer-events:none}
.ff input,.ff select{display:block;width:100%;padding:7px 0 10px;background:transparent;border:none;color:var(--parch);font-family:var(--sans);font-size:14px}
.ff input:focus,.ff select:focus{outline:none}
.ff select option{background:var(--v3)}
.dur-box{display:none;padding:9px 12px;background:rgba(107,158,58,.07);border:1px solid rgba(107,158,58,.18);font-size:12px;color:var(--g);margin-bottom:16px}
.res-note{padding-top:18px;border-bottom:1px solid rgba(107,158,58,.15);margin-bottom:22px}
.res-note label{display:block;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.45);margin-bottom:7px}
.res-note textarea{width:100%;background:transparent;border:none;color:var(--parch);font-family:var(--sans);font-size:14px;resize:none;height:56px}
.res-note textarea:focus{outline:none}
.res-note textarea::placeholder{color:rgba(242,237,228,.2);font-size:12px}
.r-sub{width:100%;padding:14px;background:var(--g);color:var(--void);font-family:var(--sans);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:.2s}
.r-sub:hover{background:var(--g2)}.r-sub:disabled{opacity:.35;cursor:not-allowed}
.r-suc{display:none;text-align:center;padding:28px 0}
.rs-i{font-size:48px;margin-bottom:12px}
.rs-t{font-family:var(--serif);font-size:26px;color:var(--g);margin-bottom:7px}
.rs-m{font-size:13px;color:rgba(242,237,228,.45);line-height:1.8;margin-bottom:18px}
.rs-r{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.4);background:none;border:none;text-decoration:underline;text-underline-offset:3px;cursor:pointer}

/* ── FOOTER ───────────────────────────────────────────────── */
footer{background:var(--void);border-top:1px solid var(--rule);padding:48px 5% 28px}
.ft-grid{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr;gap:44px;margin-bottom:28px}
.ft-logo{height:48px;width:auto;margin-bottom:10px;display:block}
.ft-desc{font-size:12px;font-weight:300;color:rgba(242,237,228,.28);line-height:1.85;max-width:280px}
.ft-soc{display:flex;gap:10px;margin-top:14px}
.ft-soc a{width:34px;height:34px;border-radius:50%;border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:rgba(242,237,228,.4);transition:.3s;text-decoration:none}
.ft-soc a svg{width:16px;height:16px;fill:currentColor}
.ft-soc a:hover{border-color:rgba(107,158,58,.4);color:var(--g);background:rgba(107,158,58,.06)}
.ft-label{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.35);margin-bottom:12px}
.ft-li{font-size:12px;color:rgba(242,237,228,.3);margin-bottom:7px}
.ft-li a{color:rgba(107,158,58,.5);transition:.2s}.ft-li a:hover{color:var(--g)}
.ft-bottom{max-width:1100px;margin:0 auto;padding-top:18px;border-top:1px solid rgba(255,255,255,.04);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px}
.ft-copy{font-size:11px;color:rgba(242,237,228,.2)}
.ft-power{font-size:10px;color:rgba(255,255,255,.07)}

/* ── FAB (mobile) ─────────────────────────────────────────── */
.fab-zone{position:fixed;bottom:0;left:0;right:0;z-index:600;padding:12px 5% 20px;background:linear-gradient(to top,rgba(5,7,3,1) 30%,transparent);pointer-events:none;display:flex;justify-content:center}
.mob-fab{pointer-events:auto;display:none;align-items:center;justify-content:space-between;width:100%;max-width:380px;background:rgba(5,7,3,.96);border:1px solid rgba(107,158,58,.4);border-radius:100px;padding:12px 20px;backdrop-filter:blur(20px)}
.mob-fab.on{display:flex;animation:fabrise .3s cubic-bezier(.34,1.56,.64,1)}
@keyframes fabrise{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.fab-l{display:flex;align-items:center;gap:8px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--g);cursor:pointer}
.fab-pill{background:var(--g);color:var(--void);border-radius:100px;padding:2px 7px;font-size:9px;font-weight:700}
.fab-r{font-family:var(--mono);font-size:11px;color:rgba(200,146,58,.5)}

/* ── DRAWER (mobile cart) ─────────────────────────────────── */
.ovl{position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:900;display:none;backdrop-filter:blur(10px)}.ovl.on{display:block}
.drw{position:fixed;right:0;top:0;bottom:0;width:min(390px,100vw);background:var(--v2);border-left:1px solid var(--rule);z-index:950;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .4s cubic-bezier(.4,0,.2,1)}
.drw.on{transform:translateX(0)}
.dr-h{padding:18px 20px;border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.dr-ttl{font-family:var(--serif);font-size:17px;color:var(--parch2)}
.dr-x{width:30px;height:30px;border-radius:50%;border:1px solid rgba(255,255,255,.1);background:transparent;color:rgba(242,237,228,.4);font-size:15px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s}
.dr-x:hover{border-color:var(--g);color:var(--g)}
.dr-body{flex:1;overflow-y:auto;padding:12px 20px}
.dr-empty{text-align:center;padding:36px;color:rgba(242,237,228,.22);font-size:12px;font-style:italic}
.dri{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.dri-em{font-size:20px;flex-shrink:0;width:28px;text-align:center}
.dri-info{flex:1;min-width:0}.dri-n{font-family:var(--serif);font-size:14px;color:var(--parch2)}
.dri-p{font-size:10px;color:rgba(200,146,58,.45);font-family:var(--mono)}
.dri-ctrl{display:flex;align-items:center;gap:5px;flex-shrink:0}
.drb{width:24px;height:24px;border-radius:50%;border:1px solid rgba(255,255,255,.1);background:transparent;color:rgba(242,237,228,.45);font-size:14px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s}
.drb:hover{border-color:var(--g);color:var(--g)}
.drq{font-family:var(--mono);font-size:12px;min-width:14px;text-align:center;color:var(--g)}
.dri-t{font-family:var(--mono);font-size:11px;color:rgba(200,146,58,.6);min-width:58px;text-align:right}
.dr-f{border-top:1px solid var(--rule);padding:14px 20px;flex-shrink:0}
.drs-r{display:flex;justify-content:space-between;font-size:11px;color:rgba(242,237,228,.3);margin-bottom:3px;font-family:var(--mono)}
.drs-t{display:flex;justify-content:space-between;font-size:14px;margin-top:8px;padding-top:8px;border-top:1px solid var(--rule)}
.drs-t .v{color:var(--gold);font-family:var(--mono)}
.dr-cta{width:100%;padding:12px;margin-top:10px;background:var(--g);color:var(--void);font-family:var(--sans);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:.2s}
.dr-cta:hover{background:var(--g2)}

/* ── CHECKOUT SHEET ───────────────────────────────────────── */
.cobg{position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:1000;display:none;align-items:flex-end;justify-content:center;backdrop-filter:blur(16px)}.cobg.on{display:flex}
.cosheet{background:var(--v2);border-top:1px solid var(--rule);width:min(540px,100vw);max-height:92vh;overflow-y:auto;padding:28px 26px 48px;transform:translateY(100%);transition:transform .45s cubic-bezier(.4,0,.2,1)}
.cobg.on .cosheet{transform:translateY(0)}
.co-bar{width:26px;height:1px;background:rgba(107,158,58,.3);margin:0 auto 22px}
.co-ttl{font-family:var(--serif);font-size:24px;text-align:center;color:var(--parch);margin-bottom:3px}
.co-sub{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(242,237,228,.28);text-align:center;margin-bottom:26px}
.co-ff{position:relative;padding-top:17px;border-bottom:1px solid rgba(107,158,58,.15);margin-bottom:18px;transition:.3s}
.co-ff:focus-within{border-bottom-color:rgba(107,158,58,.5)}
.co-ff label{position:absolute;top:17px;left:0;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.45);pointer-events:none}
.co-ff input,.co-ff textarea{display:block;width:100%;padding:7px 0 9px;background:transparent;border:none;color:var(--parch);font-family:var(--sans);font-size:14px}
.co-ff input:focus,.co-ff textarea:focus{outline:none}
.co-ff textarea{resize:none;height:48px}
.popts{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:20px}
.popt{border:1px solid var(--rule);padding:12px 6px;text-align:center;cursor:pointer;transition:.2s;background:transparent}
.popt.on{border-color:rgba(107,158,58,.55);background:rgba(107,158,58,.08)}
.po-icon{font-size:19px;margin-bottom:4px;display:block}
.po-lbl{font-size:9px;letter-spacing:1px;text-transform:uppercase;color:rgba(242,237,228,.4)}
.popt.on .po-lbl{color:var(--g)}
.co-sum{background:var(--glow);border:1px solid var(--rule);padding:12px;margin-bottom:16px}
.csr{display:flex;justify-content:space-between;font-size:12px;padding:2px 0}
.csn{color:rgba(242,237,228,.4)}.csv{font-family:var(--mono);font-size:11px;color:rgba(200,146,58,.7)}
.cs-hr{border:none;border-top:1px solid var(--rule);margin:7px 0}
.cs-tot{display:flex;justify-content:space-between;font-size:13px}
.cs-tot .v{color:var(--gold);font-family:var(--mono)}
.pbtn{width:100%;padding:14px;background:var(--g);color:var(--void);font-family:var(--sans);font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:.2s;margin-top:3px}
.pbtn:hover{background:var(--g2)}.pbtn:disabled{opacity:.3;cursor:not-allowed}
.suc{text-align:center;padding:18px 0}
.suc-i{font-size:50px;margin-bottom:12px}
.suc-t{font-family:var(--serif);font-size:26px;color:var(--g);margin-bottom:5px}
.suc-n{font-family:var(--mono);font-size:13px;color:rgba(200,146,58,.6);margin-bottom:10px}
.suc-m{font-size:13px;color:rgba(242,237,228,.4);line-height:1.8;margin-bottom:20px}
.suc-c{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.4);border:1px solid rgba(107,158,58,.2);background:none;padding:9px 24px;cursor:pointer;transition:.2s}
.suc-c:hover{border-color:var(--g);color:var(--g)}

/* ── RESPONSIVE ───────────────────────────────────────────── */
@media(max-width:1000px){
  .cart-col{display:none}.mob-fab{display:flex}
  .res-wrap{grid-template-columns:1fr;gap:44px}.res-left-info{display:none}
  .ft-grid{grid-template-columns:1fr}.feat-grid{grid-template-columns:repeat(2,1fr)}
  .photo-grid{grid-template-columns:1fr 1fr;grid-template-rows:250px 250px}
  .photo-grid .photo-card:first-child{grid-row:auto}
  .float-social{display:none}
}
@media(max-width:768px){
  .nav-center{display:none}.n-book{display:none}.n-ham{display:flex}
  .popts{grid-template-columns:1fr}.rf-grid{grid-template-columns:1fr}
  .gal-content{bottom:60px}
}
@media(max-width:540px){
  .feat-grid{grid-template-columns:1fr}.fi{border-right:none}
  .photo-grid{grid-template-columns:1fr;grid-template-rows:auto}
  .photo-card:first-child{grid-row:auto}.photo-card{height:260px}
}
</style>
</head>
<body>

<!-- FLOATING SOCIAL (always visible, right edge) -->
<div class="float-social">
  <a href="https://wa.me/94772298545" target="_blank" rel="noopener" class="fsb fsb-wa" title="WhatsApp">
    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  </a>
  <a href="https://www.instagram.com/covecafesl?igsh=enM4M2thMTI1dXVr" target="_blank" rel="noopener" class="fsb fsb-ig" title="@covecafesl">
    <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
  </a>
  <a href="https://maps.app.goo.gl/JYW6yPbEfMZyhTMi6" target="_blank" rel="noopener" class="fsb fsb-gm" title="Google Maps">
    <svg viewBox="0 0 24 24"><path d="M12 0C7.802 0 4 3.403 4 7.602 4 11.8 7.469 16.812 12 24c4.531-7.188 8-12.2 8-16.398C20 3.403 16.199 0 12 0zm0 11c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"/></svg>
  </a>
</div>

<!-- NAVBAR -->
<nav id="nav">
  <div class="nav-brand" onclick="goTo('gallery')">
    <img class="nav-logo" src="<?=$LOGO?>" alt="Cove Cafe Logo">
    <div><div class="nav-brand-name">COVE</div><div class="nav-brand-sub">Café &amp; Lounge</div></div>
  </div>
  <div class="nav-center">
    <button class="nlink" onclick="goTo('gallery')">Home</button>
    <button class="nlink" onclick="goTo('promos')">Promotions</button>
    <button class="nlink" onclick="goTo('menu')">Menu</button>
    <button class="nlink" onclick="goTo('photos')">Gallery</button>
    <button class="nlink" onclick="goTo('features')">Experiences</button>
    <button class="nlink" onclick="goTo('reserve')">Reserve</button>
    <button class="nlink" onclick="goTo('foot')">Find Us</button>
  </div>
  <div class="nav-r">
    <button class="n-ord" id="nOrd" onclick="openDr()">🛒 <span class="ord-b" id="nCnt">0</span></button>
    <button class="n-book" onclick="goTo('reserve')">Book a Table</button>
    <button class="n-ham" onclick="toggleM()"><span></span><span></span><span></span></button>
  </div>
</nav>
<div class="mob-nav" id="mnav">
  <button class="ml" onclick="goTo('gallery');toggleM()">Home</button>
  <button class="ml" onclick="goTo('promos');toggleM()">Promotions</button>
  <button class="ml" onclick="goTo('menu');toggleM()">Menu</button>
  <button class="ml" onclick="goTo('photos');toggleM()">Gallery</button>
  <button class="ml" onclick="goTo('features');toggleM()">Experiences</button>
  <button class="ml" onclick="goTo('reserve');toggleM()">Reserve</button>
  <button class="ml" onclick="goTo('foot');toggleM()">Find Us</button>
  <button class="mob-bk" onclick="goTo('reserve');toggleM()">📅 Book a Table</button>
</div>

<!-- HERO SLIDER -->
<section id="gallery">
  <?php foreach($PHOTOS as $i=>$p): ?>
  <div class="slide <?=($i===0)?'on':''?>">
    <img class="slide-img" src="<?=$p['file']?>" alt="<?=strip_tags($p['label'])?>">
  </div>
  <?php endforeach; ?>
  <div class="gal-content">
    <div class="sl-label" id="slabel"><?=strip_tags($PHOTOS[0]['label'])?></div>
    <h1 class="sl-title" id="stitle"><?=$PHOTOS[0]['title']?></h1>
    <p class="sl-desc" id="sdesc"><?=$PHOTOS[0]['desc']?></p>
    <button class="sl-cta" onclick="goTo('menu')">Explore the Menu →</button>
  </div>
  <div class="sl-dots" id="sdots"></div>
  <div class="sl-prog"><div class="prog-f" id="progf"></div></div>
</section>

<!-- PROMOTIONS -->
<section id="promos">
  <div class="sec-hed">
    <div class="sec-tag">Today's Deals</div>
    <div class="sec-title">Current Promotions</div>
  </div>
  <div class="promo-grid">
    <?php if(empty($promos)): ?>
      <div class="promo-empty">No active promotions right now. Check back soon!</div>
    <?php else: foreach($promos as $pr): ?>
    <div class="promo-card">
      <div class="promo-badge"><?=htmlspecialchars(promoLabel($pr))?></div>
      <div>
        <div class="promo-name"><?=htmlspecialchars($pr['name'])?></div>
        <div class="promo-desc"><?=htmlspecialchars($pr['description'])?></div>
        <?php if(!empty($pr['valid_to'])): ?>
        <div class="promo-valid">Valid until <?=date('d M Y',strtotime($pr['valid_to']))?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</section>

<!-- STORY PANELS -->
<?php
$panels = [
  ['img'=>'assets/uploads/cafe/interior.webp', 'eye'=>'Inside Cove',       'icon'=>'🌿','num'=>'01','tag'=>'The Interior',
   'title'=>'Green Walls &amp;<br><em>Golden Light</em>',
   'txt'=>'<p>Step inside and the city disappears. Lush green walls, woven pendant lights casting warm golden rays. Coffee, conversations, and that familiar neighbourhood calm.</p>'],
  ['img'=>'assets/uploads/cafe/entrance.webp', 'eye'=>'Cove Entrance',     'icon'=>'🎨','num'=>'02','tag'=>'The Entrance',
   'title'=>'A Mural That<br><em>Welcomes You</em>',
   'txt'=>'<p>Our entrance wall is a living artwork — hand-painted murals, ornate frames, tropical foliage, and our glowing neon sign. You know you\'ve arrived somewhere special.</p>'],
  ['img'=>'assets/uploads/cafe/shisha.webp',   'eye'=>'Evening Vibes',     'icon'=>'💨','num'=>'03','tag'=>'Shisha Lounge',
   'title'=>'Slow Down<br><em>Your Evenings</em>',
   'txt'=>'<p>Our dedicated shisha lounge offers a completely relaxed, low-lit environment to decompress. Premium blends, comfortable seating, good company.</p>'],
  ['img'=>'assets/uploads/cafe/garden.webp',   'eye'=>'The Great Outdoors','icon'=>'🏡','num'=>'04','tag'=>'Backyard Garden',
   'title'=>'Backyard<br><em>Cabanas</em>',
   'txt'=>'<p>Stone pathways wind through lush grass, tropical plants, and thatched-roof cabanas under open blue sky. A cold drink, the rustle of leaves. Simple perfection.</p>'],
];
foreach($panels as $panel): ?>
<section class="panel">
  <img class="p-bg" src="<?=$panel['img']?>" alt="<?=$panel['eye']?>">
  <div class="p-num"><?=$panel['num']?></div>
  <div class="p-tag"><?=$panel['tag']?></div>
  <div class="p-body">
    <div class="p-eye"><?=$panel['eye']?></div>
    <span class="p-icon"><?=$panel['icon']?></span>
    <h2 class="p-title"><?=$panel['title']?></h2>
    <div class="p-txt"><?=$panel['txt']?></div>
  </div>
</section>
<?php endforeach; ?>

<!-- CATEGORY BAR -->
<div id="catBar">
  <div class="cat-in">
    <button class="cb on" onclick="fcat(this,'all')">🍽 All</button>
    <?php foreach($categories as $c): if(empty($byCategory[$c['id']])) continue; ?>
    <button class="cb" onclick="fcat(this,'cat-<?=$c['id']?>')"><?=catEmoji($c['name'])?> <?=htmlspecialchars($c['name'])?></button>
    <?php endforeach; ?>
  </div>
</div>

<!-- MENU -->
<section id="menu">
  <div class="menu-wrap">
    <div class="menu-main">
      <div class="srch-row">
        <input class="srch" placeholder="Search the menu…" oninput="doSearch(this.value)">
      </div>
      <?php if(empty($allItems)): ?>
      <div style="text-align:center;padding:80px 20px;color:rgba(242,237,228,.3)">
        <div style="font-size:48px;margin-bottom:16px">🍽</div>
        <div style="font-family:var(--serif);font-size:22px;font-style:italic;margin-bottom:8px">Menu coming soon</div>
        <div style="font-size:13px">Please call <a href="tel:+94772298545" style="color:var(--g)">+94 77 229 8545</a></div>
      </div>
      <?php else: $pi=0; foreach($categories as $c):
        if(empty($byCategory[$c['id']])) continue;
        $col=$PALETTE[$pi%count($PALETTE)]; $pi++;
        $em=catEmoji($c['name']); ?>
      <div class="msec" data-sec="cat-<?=$c['id']?>">
        <div class="ms-head">
          <span class="ms-icon"><?=$em?></span>
          <span class="ms-title"><?=htmlspecialchars($c['name'])?></span>
          <span class="ms-cnt"><?=count($byCategory[$c['id']])?> item<?=count($byCategory[$c['id']])!=1?'s':''?></span>
        </div>
        <?php foreach($byCategory[$c['id']] as $it):
          $iid = (int)$it['id'];
          $iname = addslashes($it['name']);
          $iprice = (float)$it['price'];
          $hasImg = !empty($it['image']) && file_exists(__DIR__.'/assets/uploads/menu/'.$it['image']); ?>
        <div class="mi" onclick="addI(<?=$iid?>,'<?=$iname?>',<?=$iprice?>,'<?=$em?>','<?=$col?>')">
          <div class="mi-th">
            <?php if($hasImg): ?>
              <img src="assets/uploads/menu/<?=htmlspecialchars($it['image'])?>" alt="<?=htmlspecialchars($it['name'])?>">
            <?php else: ?><?=$em?><?php endif; ?>
          </div>
          <div class="mi-info">
            <div class="mi-cat" style="color:<?=$col?>"><?=htmlspecialchars($c['name'])?></div>
            <div class="mi-name"><?=htmlspecialchars($it['name'])?></div>
            <div class="mi-desc"><?=htmlspecialchars($it['description'] ?: 'Made fresh to order.')?></div>
          </div>
          <div class="mi-r">
            <div class="mi-price"><small>Rs.</small><?=number_format($iprice,2)?></div>
            <button class="madd" id="ab-<?=$iid?>" onclick="event.stopPropagation();addI(<?=$iid?>,'<?=$iname?>',<?=$iprice?>,'<?=$em?>','<?=$col?>')">+</button>
            <div class="mqc" id="qc-<?=$iid?>">
              <button class="mqb" onclick="event.stopPropagation();ch(<?=$iid?>,-1)">−</button>
              <span class="mqn" id="qn-<?=$iid?>">0</span>
              <button class="mqb" onclick="event.stopPropagation();ch(<?=$iid?>,1)">+</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <!-- SIDEBAR CART (desktop) -->
    <div class="cart-col">
      <div class="cart-p">
        <div class="cart-h"><span class="cart-ttl">Your Order</span><span class="c-badge" id="cbadge">0</span></div>
        <div class="cart-body" id="cbody"><div class="c-empty">Your order is empty</div></div>
        <div id="csumw" style="display:none">
          <div class="c-sum">
            <div class="cs-r"><span>Subtotal</span><span id="csub"></span></div>
            <div class="cs-r"><span>Service (<?=$svcPct?>%)</span><span id="csvc"></span></div>
            <div class="cs-r"><span>Tax (<?=$taxPct?>%)</span><span id="ctax"></span></div>
            <div class="cs-t"><span>Total</span><span class="v" id="ctot"></span></div>
          </div>
          <button class="c-cta" onclick="openCo()">Checkout →</button>
        </div>
        <div id="cemp"><button class="c-cta" disabled>Add items to order</button></div>
      </div>
    </div>
  </div>
</section>

<!-- GALLERY -->
<section id="photos">
  <div class="sec-hed">
    <div class="sec-tag">Our Space</div>
    <div class="sec-title">A Café Worth Visiting</div>
  </div>
  <div class="photo-grid">
    <div class="photo-card"><img src="assets/uploads/cafe/garden.webp" alt="Backyard Garden"><div class="photo-caption"><div class="pc-tag">Backyard</div><div class="pc-title">Tropical Garden &amp; Cabanas</div></div></div>
    <div class="photo-card"><img src="assets/uploads/cafe/entrance.webp" alt="Entrance"><div class="photo-caption"><div class="pc-tag">Entrance</div><div class="pc-title">Neon Sign &amp; Murals</div></div></div>
    <div class="photo-card"><img src="assets/uploads/cafe/interior.webp" alt="Interior"><div class="photo-caption"><div class="pc-tag">Interior</div><div class="pc-title">Green Walls &amp; Golden Light</div></div></div>
  </div>
</section>

<!-- EXPERIENCES -->
<section id="features">
  <div class="sec-hed">
    <div class="sec-tag">At Cove</div>
    <div class="sec-title">What's Waiting for You</div>
  </div>
  <div class="feat-grid">
    <div class="fi"><div class="fi-icon">☕</div><div class="fi-t">Food &amp; Coffee</div><div class="fi-d">Wood-fired pizza, freshly ground coffee, handcrafted drinks from scratch.</div></div>
    <div class="fi"><div class="fi-icon">🎤</div><div class="fi-t">Karaoke &amp; Movies</div><div class="fi-d">Private entertainment rooms for dates and group hangouts.</div></div>
    <div class="fi"><div class="fi-icon">🏡</div><div class="fi-t">Backyard Cabanas</div><div class="fi-d">Thatched-roof cabanas, stone pathways, tropical garden under open sky.</div></div>
    <div class="fi"><div class="fi-icon">💻</div><div class="fi-t">Work Ready</div><div class="fi-d">High-speed Wi-Fi and charging points at every single table.</div></div>
    <div class="fi"><div class="fi-icon">🚪</div><div class="fi-t">Private Rooms</div><div class="fi-d">For focused work, deep talks, or anything that needs to stay between you.</div></div>
    <div class="fi"><div class="fi-icon">💨</div><div class="fi-t">Shisha Lounge</div><div class="fi-d">Premium blends, relaxed atmosphere. The perfect slow evening.</div></div>
    <div class="fi"><div class="fi-icon">🎉</div><div class="fi-t">Host Your Moments</div><div class="fi-d">Birthdays, workshops, family celebrations. We handle the space.</div></div>
    <div class="fi" style="border-right:none"><div class="fi-icon">🌿</div><div class="fi-t">The Vibe</div><div class="fi-d">Tropical calm alongside the city. Open daily 11 AM until late.</div></div>
  </div>
</section>

<!-- RESERVATION -->
<section id="reserve">
  <div class="res-wrap">
    <div class="res-left-info">
      <div class="res-eye">Table Booking</div>
      <h2 class="res-tit">Reserve Your<br><em>Table at Cove</em></h2>
      <p class="res-desc">Book in advance and we'll have everything ready. Private rooms, karaoke setups, cabana seating, and shisha tables all available.</p>
      <div class="res-ilist">
        <div class="ri">📅&nbsp; Open <strong>7 days a week</strong>, 11 AM – Late</div>
        <div class="ri">👥&nbsp; Groups from <strong>2 to 50+</strong> guests</div>
        <div class="ri">📍&nbsp; Kirulapone, <strong>Colombo, Sri Lanka</strong></div>
        <div class="ri">📞&nbsp; Call: <strong><?=$bizPhone?></strong></div>
        <div class="ri">🎤&nbsp; Karaoke · Movies · <strong>Shisha</strong> · Cabanas</div>
      </div>
      <div class="soc-row">
        <a href="https://wa.me/94772298545" target="_blank" class="soc-btn soc-wa">
          <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          WhatsApp
        </a>
        <a href="https://www.instagram.com/covecafesl?igsh=enM4M2thMTI1dXVr" target="_blank" class="soc-btn soc-ig">
          <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          @covecafesl
        </a>
        <a href="https://maps.app.goo.gl/JYW6yPbEfMZyhTMi6" target="_blank" class="soc-btn soc-gm">
          <svg viewBox="0 0 24 24"><path d="M12 0C7.802 0 4 3.403 4 7.602 4 11.8 7.469 16.812 12 24c4.531-7.188 8-12.2 8-16.398C20 3.403 16.199 0 12 0zm0 11c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"/></svg>
          Directions
        </a>
      </div>
    </div>
    <div>
      <div id="rfwrap">
        <div class="rf-grid">
          <div class="ff"><label>Full Name *</label><input id="rn" placeholder=" "></div>
          <div class="ff"><label>Phone *</label><input id="rp" type="tel" placeholder=" "></div>
          <div class="ff"><label>Date *</label><input id="rd" type="date" min="<?=date('Y-m-d')?>"></div>
          <div class="ff"><label>Guests *</label>
            <select id="rpx"><?php for($g=1;$g<=20;$g++): ?><option value="<?=$g?>" <?=$g==2?'selected':''?>><?=$g?> Guest<?=$g>1?'s':''?></option><?php endfor; ?></select>
          </div>
          <div class="ff"><label>Start Time *</label><input id="rt" type="time" oninput="calcD()"></div>
          <div class="ff"><label>End Time</label><input id="ret" type="time" oninput="calcD()"></div>
        </div>
        <div class="dur-box" id="durb">⏱ Duration: <span id="durt"></span></div>
        <div class="ff" style="margin-bottom:20px"><label>Preference (Karaoke / Cabana / Private Room)</label><input id="rl" placeholder=" "></div>
        <div class="res-note"><label>Special Requests</label><textarea id="rnote" placeholder="Occasion, allergies, arrangements…"></textarea></div>
        <button class="r-sub" id="rsbtn" onclick="subRes()">Confirm Reservation</button>
      </div>
      <div class="r-suc" id="rsuc">
        <div class="rs-i">🌿</div>
        <div class="rs-t">Reservation Confirmed!</div>
        <div class="rs-m" id="rsmsg">Your table is booked. We look forward to welcoming you.</div>
        <button class="rs-r" onclick="resetRes()">Make Another Booking</button>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer id="foot">
  <div class="ft-grid">
    <div>
      <img class="ft-logo" src="<?=$LOGO?>" alt="Cove Café &amp; Lounge">
      <div class="ft-desc">A quiet neighbourhood café in Kirulapone, Colombo. Coffee, wood-fired pizza, karaoke, backyard cabanas, and shisha — built for slow mornings and unforgettable evenings.</div>
      <div class="ft-soc">
        <a href="https://wa.me/94772298545" target="_blank" title="WhatsApp"><svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
        <a href="https://www.instagram.com/covecafesl?igsh=enM4M2thMTI1dXVr" target="_blank" title="Instagram"><svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>
        <a href="https://maps.app.goo.gl/JYW6yPbEfMZyhTMi6" target="_blank" title="Google Maps"><svg viewBox="0 0 24 24"><path d="M12 0C7.802 0 4 3.403 4 7.602 4 11.8 7.469 16.812 12 24c4.531-7.188 8-12.2 8-16.398C20 3.403 16.199 0 12 0zm0 11c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"/></svg></a>
      </div>
    </div>
    <div>
      <div class="ft-label">Find Us</div>
      <div class="ft-li">📍 Kirulapone, Colombo</div>
      <div class="ft-li">🕐 Daily · 11 AM – Late</div>
      <div class="ft-li">📞 <?=$bizPhone?></div>
      <div class="ft-li"><a href="https://maps.app.goo.gl/JYW6yPbEfMZyhTMi6" target="_blank">View on Google Maps →</a></div>
    </div>
    <div>
      <div class="ft-label">Follow Us</div>
      <div class="ft-li"><a href="https://www.instagram.com/covecafesl?igsh=enM4M2thMTI1dXVr" target="_blank">📷 @covecafesl</a></div>
      <div class="ft-li"><a href="https://wa.me/94772298545" target="_blank">💬 +94 77 229 8545</a></div>
      <div class="ft-label" style="margin-top:18px">Experiences</div>
      <div class="ft-li">☕ Food &amp; Coffee</div>
      <div class="ft-li">🎤 Karaoke &amp; Movies</div>
      <div class="ft-li">🏡 Backyard Cabanas</div>
      <div class="ft-li">💨 Shisha Lounge</div>
    </div>
  </div>
  <div class="ft-bottom">
    <div class="ft-copy">© <?=date('Y')?> Cove Café &amp; Lounge · Kirulapone, Colombo · All prices inclusive of service charge &amp; taxes</div>
    <div class="ft-power">Powered by RestoPOS Sri Lanka</div>
  </div>
</footer>

<!-- MOBILE FAB -->
<div class="fab-zone">
  <button class="mob-fab" id="mfab" onclick="openDr()">
    <div class="fab-l">🛒 View Order <span class="fab-pill" id="mfcnt">0</span></div>
    <span class="fab-r" id="mftot">Rs. 0.00</span>
  </button>
</div>

<!-- CART DRAWER -->
<div class="ovl" id="ovl" onclick="closeDr()"></div>
<div class="drw" id="drw">
  <div class="dr-h"><span class="dr-ttl">Your Order</span><button class="dr-x" onclick="closeDr()">✕</button></div>
  <div class="dr-body" id="drbody"><div class="dr-empty">Your order is empty</div></div>
  <div class="dr-f" id="drf" style="display:none">
    <div class="drs-r"><span>Subtotal</span><span id="drsub"></span></div>
    <div class="drs-r"><span>Service (<?=$svcPct?>%)</span><span id="drsvc"></span></div>
    <div class="drs-r"><span>Tax (<?=$taxPct?>%)</span><span id="drtax"></span></div>
    <div class="drs-t"><span>Total</span><span class="v" id="drtot"></span></div>
    <button class="dr-cta" onclick="closeDr();openCo()">Proceed to Checkout →</button>
  </div>
</div>

<!-- CHECKOUT -->
<div class="cobg" id="cobg" onclick="coClose(event)">
  <div class="cosheet">
    <div id="cocon">
      <div class="co-bar"></div>
      <div class="co-ttl">Complete Your Order</div>
      <div class="co-sub">Your details &amp; payment method</div>
      <div class="co-ff"><label>Full Name *</label><input id="cn" placeholder=" "></div>
      <div class="co-ff"><label>Phone Number *</label><input id="cph" type="tel" placeholder=" "></div>
      <div style="margin-bottom:18px">
        <div style="font-size:9px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.45);margin-bottom:9px">Payment Method</div>
        <div class="popts">
          <div class="popt on" onclick="sPay(this,'takeaway')"><span class="po-icon">🥡</span><div class="po-lbl">Takeaway</div></div>
          <div class="popt" onclick="sPay(this,'card')"><span class="po-icon">💳</span><div class="po-lbl">Card</div></div>
          <div class="popt" onclick="sPay(this,'bank_transfer')"><span class="po-icon">🏦</span><div class="po-lbl">Bank Transfer</div></div>
        </div>
      </div>
      <div style="margin-bottom:16px">
        <div style="font-size:9px;letter-spacing:2px;text-transform:uppercase;color:rgba(107,158,58,.45);margin-bottom:9px">Order Summary</div>
        <div class="co-sum" id="cosum"></div>
      </div>
      <div class="co-ff" style="margin-bottom:18px"><label>Special Requests</label><textarea id="cnote" placeholder=" "></textarea></div>
      <button class="pbtn" id="pbtn" onclick="placeO()">Place Order</button>
    </div>
    <div id="cosuc" class="suc" style="display:none">
      <div class="suc-i">🌿</div>
      <div class="suc-t">Order Received!</div>
      <div class="suc-n" id="sno"></div>
      <div class="suc-m">Our team will prepare your order shortly.<br>We may call <strong id="sph"></strong> to confirm.<br><br>📞 Questions? Call <strong><?=$bizPhone?></strong></div>
      <button class="suc-c" onclick="closeAll()">Done</button>
    </div>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════════════════
   COVE CAFÉ — ALL JAVASCRIPT
   ══════════════════════════════════════════════════════════ */

/* ── SLIDER ──────────────────────────────────────────────── */
var SLIDES=<?=json_encode(array_map(function($p){return['label'=>html_entity_decode(strip_tags($p['label'])),'title'=>$p['title'],'desc'=>$p['desc']];},$PHOTOS))?>;
var cur=0,animId=null;

function buildDots(){
  var c=document.getElementById('sdots');
  c.innerHTML='';
  SLIDES.forEach(function(_,i){
    var d=document.createElement('div');
    d.className='sdot'+(i===0?' on':'');
    d.onclick=function(){goSlide(i);};
    c.appendChild(d);
  });
}
function goSlide(n){
  document.querySelectorAll('.slide').forEach(function(s,i){s.classList.toggle('on',i===n);});
  document.querySelectorAll('.sdot').forEach(function(d,i){d.classList.toggle('on',i===n);});
  var sl=SLIDES[n];
  document.getElementById('slabel').textContent=sl.label;
  document.getElementById('stitle').innerHTML=sl.title;
  document.getElementById('sdesc').textContent=sl.desc;
  cur=n;
  if(animId)cancelAnimationFrame(animId);
  startProg();
}
function startProg(){
  var pf=document.getElementById('progf');
  var st=null;
  function tick(ts){
    if(!st)st=ts;
    var p=((ts-st)/5000)*100;
    pf.style.width=Math.min(p,100)+'%';
    if(p<100){animId=requestAnimationFrame(tick);}
    else{cur=(cur+1)%SLIDES.length;goSlide(cur);}
  }
  animId=requestAnimationFrame(tick);
}
buildDots();
startProg();

/* ── NAV ─────────────────────────────────────────────────── */
window.addEventListener('scroll',function(){
  document.getElementById('nav').classList.toggle('scrolled',window.scrollY>60);
  document.getElementById('mnav').classList.remove('open');
},{passive:true});

function goTo(id){
  var el=document.getElementById(id);
  if(!el)return;
  var top=el.getBoundingClientRect().top+window.scrollY-64;
  window.scrollTo({top:top,behavior:'smooth'});
}
function toggleM(){document.getElementById('mnav').classList.toggle('open');}

/* ── CART ────────────────────────────────────────────────── */
var cart={};
var payType='takeaway';
var SVC=<?=$svcPct?>/100;
var TAX=<?=$taxPct?>/100;

function Rs(n){return'Rs. '+parseFloat(n).toFixed(2);}

function tots(){
  var items=Object.values(cart);
  var sub=items.reduce(function(s,i){return s+i.price*i.qty;},0);
  var svc=sub*SVC;
  var tax=(sub+svc)*TAX;
  var tot=sub+svc+tax;
  var cnt=items.reduce(function(s,i){return s+i.qty;},0);
  return{items:items,sub:sub,svc:svc,tax:tax,tot:tot,cnt:cnt};
}

function addI(id,name,price,emoji,color){
  var key=String(id);
  if(cart[key]){cart[key].qty++;}
  else{cart[key]={id:id,name:name,price:parseFloat(price),emoji:emoji,color:color,qty:1};}
  syncBtn(key);
  renderAll();
}

function ch(id,d){
  var key=String(id);
  if(!cart[key])return;
  cart[key].qty+=d;
  if(cart[key].qty<=0)delete cart[key];
  syncBtn(key);
  renderAll();
}

function syncBtn(key){
  var qty=cart[key]?cart[key].qty:0;
  var ab=document.getElementById('ab-'+key);
  var qc=document.getElementById('qc-'+key);
  var qn=document.getElementById('qn-'+key);
  if(!ab)return;
  if(qty>0){ab.style.display='none';qc.style.display='flex';if(qn)qn.textContent=qty;}
  else{ab.style.display='';qc.style.display='none';}
}

function renderAll(){renderSidebar();renderMob();}

function renderSidebar(){
  var t=tots();
  var body=document.getElementById('cbody');
  var sw=document.getElementById('csumw');
  var ce=document.getElementById('cemp');
  var badge=document.getElementById('cbadge');
  badge.textContent=t.cnt;
  badge.style.display=t.cnt>0?'':'none';
  if(!t.items.length){
    body.innerHTML='<div class="c-empty">Your order is empty</div>';
    sw.style.display='none';ce.style.display='';return;
  }
  ce.style.display='none';sw.style.display='';
  body.innerHTML=t.items.map(function(i){
    return '<div class="ci"><div class="ci-em">'+i.emoji+'</div><div class="ci-info"><div class="ci-n">'+i.name+'</div><div class="ci-p">'+Rs(i.price)+'</div></div><div class="ci-ctrl"><button class="cib" onclick="ch('+i.id+',-1)">−</button><span class="ci-q">'+i.qty+'</span><button class="cib" onclick="ch('+i.id+',1)">+</button></div><div class="ci-t">'+Rs(i.price*i.qty)+'</div></div>';
  }).join('');
  document.getElementById('csub').textContent=Rs(t.sub);
  document.getElementById('csvc').textContent=Rs(t.svc);
  document.getElementById('ctax').textContent=Rs(t.tax);
  document.getElementById('ctot').textContent=Rs(t.tot);
}

function renderMob(){
  var t=tots();
  var fab=document.getElementById('mfab');
  document.getElementById('mfcnt').textContent=t.cnt;
  document.getElementById('mftot').textContent=Rs(t.tot);
  if(t.cnt>0)fab.classList.add('on');else fab.classList.remove('on');
  var no=document.getElementById('nOrd');
  document.getElementById('nCnt').textContent=t.cnt;
  if(t.cnt>0)no.classList.add('on');else no.classList.remove('on');
}

/* ── DRAWER ──────────────────────────────────────────────── */
function openDr(){
  document.getElementById('ovl').classList.add('on');
  document.getElementById('drw').classList.add('on');
  document.body.style.overflow='hidden';
  renderDr();
}
function closeDr(){
  document.getElementById('ovl').classList.remove('on');
  document.getElementById('drw').classList.remove('on');
  document.body.style.overflow='';
}
function renderDr(){
  var t=tots();
  var body=document.getElementById('drbody');
  var foot=document.getElementById('drf');
  if(!t.items.length){body.innerHTML='<div class="dr-empty">Your order is empty</div>';foot.style.display='none';return;}
  body.innerHTML=t.items.map(function(i){
    return '<div class="dri"><div class="dri-em">'+i.emoji+'</div><div class="dri-info"><div class="dri-n">'+i.name+'</div><div class="dri-p">'+Rs(i.price)+' each</div></div><div class="dri-ctrl"><button class="drb" onclick="ch('+i.id+',-1)">−</button><span class="drq">'+i.qty+'</span><button class="drb" onclick="ch('+i.id+',1)">+</button></div><div class="dri-t">'+Rs(i.price*i.qty)+'</div></div>';
  }).join('');
  foot.style.display='';
  document.getElementById('drsub').textContent=Rs(t.sub);
  document.getElementById('drsvc').textContent=Rs(t.svc);
  document.getElementById('drtax').textContent=Rs(t.tax);
  document.getElementById('drtot').textContent=Rs(t.tot);
}

/* ── CHECKOUT ────────────────────────────────────────────── */
function openCo(){
  var t=tots();
  document.getElementById('cosum').innerHTML=
    t.items.map(function(i){return'<div class="csr"><span class="csn">'+i.qty+'× '+i.name+'</span><span class="csv">'+Rs(i.price*i.qty)+'</span></div>';}).join('')+
    '<hr class="cs-hr"><div class="csr"><span class="csn">Service (<?=$svcPct?>%)</span><span>'+Rs(t.svc)+'</span></div>'+
    '<div class="csr"><span class="csn">Tax (<?=$taxPct?>%)</span><span>'+Rs(t.tax)+'</span></div>'+
    '<hr class="cs-hr"><div class="cs-tot"><span style="color:var(--parch2)">Total</span><span class="v">'+Rs(t.tot)+'</span></div>';
  document.getElementById('cocon').style.display='';
  document.getElementById('cosuc').style.display='none';
  document.getElementById('cobg').classList.add('on');
  document.body.style.overflow='hidden';
}
function sPay(el,type){
  document.querySelectorAll('.popt').forEach(function(p){p.classList.remove('on');});
  el.classList.add('on');payType=type;
}
function coClose(e){if(e.target===document.getElementById('cobg'))closeAll();}
function closeAll(){
  document.getElementById('cobg').classList.remove('on');
  document.body.style.overflow='';
}
function placeO(){
  var name=document.getElementById('cn').value.trim();
  var phone=document.getElementById('cph').value.trim();
  if(!name){document.getElementById('cn').style.borderBottomColor='#E05050';document.getElementById('cn').focus();return;}
  if(!phone){document.getElementById('cph').style.borderBottomColor='#E05050';document.getElementById('cph').focus();return;}
  var btn=document.getElementById('pbtn');
  btn.disabled=true;btn.textContent='Placing order…';
  var fd=new FormData();
  fd.append('place_order','1');
  fd.append('customer_name',name);
  fd.append('customer_phone',phone);
  fd.append('customer_note',document.getElementById('cnote').value.trim());
  fd.append('order_type',payType);
  fd.append('cart',JSON.stringify(Object.values(cart)));
  fetch(window.location.pathname,{method:'POST',body:fd})
    .then(function(r){return r.text();})
    .then(function(t){
      try{
        var d=JSON.parse(t);
        if(d.ok){
          document.getElementById('sno').textContent=d.order_no;
          document.getElementById('sph').textContent=phone;
          document.getElementById('cocon').style.display='none';
          document.getElementById('cosuc').style.display='';
          cart={};
          document.querySelectorAll('.madd').forEach(function(b){b.style.display='';});
          document.querySelectorAll('.mqc').forEach(function(c){c.style.display='none';});
          renderAll();
        }else{
          alert(d.error||'Something went wrong. Please try again.');
          btn.disabled=false;btn.textContent='Place Order';
        }
      }catch(e){
        alert('Server error. Please call <?=$bizPhone?>.');
        btn.disabled=false;btn.textContent='Place Order';
      }
    })
    .catch(function(){
      alert('Network error. Please call <?=$bizPhone?>.');
      btn.disabled=false;btn.textContent='Place Order';
    });
}

/* ── FILTER & SEARCH ─────────────────────────────────────── */
function fcat(btn,sec){
  document.querySelectorAll('.cb').forEach(function(b){b.classList.remove('on');});
  btn.classList.add('on');
  document.querySelectorAll('.msec').forEach(function(s){
    s.style.display=(sec==='all'||s.dataset.sec===sec)?'':'none';
  });
  if(sec!=='all'){
    var t=document.querySelector('[data-sec="'+sec+'"]');
    if(t)setTimeout(function(){t.scrollIntoView({behavior:'smooth',block:'start'});},80);
  }
}
function doSearch(q){
  q=q.toLowerCase().trim();
  document.querySelectorAll('.mi').forEach(function(it){
    it.style.display=(!q||it.textContent.toLowerCase().includes(q))?'':'none';
  });
  if(q)document.querySelectorAll('.msec').forEach(function(s){s.style.display='';});
}

/* ── RESERVATION ─────────────────────────────────────────── */
function calcD(){
  var s=document.getElementById('rt').value;
  var e=document.getElementById('ret').value;
  var box=document.getElementById('durb');
  var txt=document.getElementById('durt');
  if(!s||!e){box.style.display='none';return;}
  var sm=s.split(':').map(Number);var em=e.split(':').map(Number);
  var diff=(em[0]*60+em[1])-(sm[0]*60+sm[1]);
  if(diff<=0){txt.textContent='⚠ End must be after start';box.style.display='';return;}
  txt.textContent=(Math.floor(diff/60)?Math.floor(diff/60)+'h ':'')+((diff%60)?diff%60+'min':'');
  box.style.display='';
}
function subRes(){
  var name=document.getElementById('rn').value.trim();
  var phone=document.getElementById('rp').value.trim();
  var date=document.getElementById('rd').value;
  var time=document.getElementById('rt').value;
  if(!name||!phone||!date||!time){alert('Please fill your name, phone, date and time.');return;}
  var btn=document.getElementById('rsbtn');
  btn.disabled=true;btn.textContent='Confirming…';
  var fd=new FormData();
  fd.append('make_reservation','1');
  fd.append('res_name',name);fd.append('res_phone',phone);
  fd.append('res_date',date);fd.append('res_time',time);
  fd.append('res_end_time',document.getElementById('ret').value);
  fd.append('res_pax',document.getElementById('rpx').value);
  fd.append('res_location',document.getElementById('rl').value.trim());
  fd.append('res_note',document.getElementById('rnote').value.trim());
  fetch(window.location.pathname,{method:'POST',body:fd})
    .then(function(r){return r.text();})
    .then(function(t){
      try{
        var d=JSON.parse(t);
        if(d.ok){
          document.getElementById('rsmsg').innerHTML='<strong>'+d.name+'</strong>, your table is confirmed for <strong>'+d.date+'</strong> at <strong>'+d.time+'</strong>.<br>We look forward to welcoming you to Cove! 🌿';
          document.getElementById('rfwrap').style.display='none';
          document.getElementById('rsuc').style.display='block';
        }else{
          alert(d.error||'Failed. Please call <?=$bizPhone?>.');
          btn.disabled=false;btn.textContent='Confirm Reservation';
        }
      }catch(e){
        alert('Server error. Please call <?=$bizPhone?> directly.');
        btn.disabled=false;btn.textContent='Confirm Reservation';
      }
    })
    .catch(function(){
      alert('Network error. Please call <?=$bizPhone?>.');
      btn.disabled=false;btn.textContent='Confirm Reservation';
    });
}
function resetRes(){
  document.getElementById('rfwrap').style.display='';
  document.getElementById('rsuc').style.display='none';
  document.getElementById('rsbtn').disabled=false;
  document.getElementById('rsbtn').textContent='Confirm Reservation';
  ['rn','rp','rd','rt','ret','rl','rnote'].forEach(function(id){document.getElementById(id).value='';});
  document.getElementById('durb').style.display='none';
}
</script>
</body>
</html>
