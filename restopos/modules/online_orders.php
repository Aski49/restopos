<?php
require_once '../includes/config.php';
requireAccess('online_orders');
$db = getDB();
$pageTitle = 'Online Orders'; $activePage = 'online_orders';

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // AJAX actions return JSON
    if (in_array($action, ['update_status','mark_seen','poll','poll_new'])) {
        header('Content-Type: application/json');

        if ($action === 'poll') {
            $cnt = $db->query("SELECT COUNT(*) FROM online_orders WHERE seen=0 AND status='new'")->fetchColumn();
            echo json_encode(['new_count'=>(int)$cnt]); exit;
        }
        if ($action === 'poll_new') {
            $rows = $db->query("SELECT id,customer_name,order_type,total FROM online_orders WHERE seen=0 AND status='new' ORDER BY created_at DESC LIMIT 10")->fetchAll();
            $tl = ['takeaway'=>'Takeaway','card'=>'Card','bank_transfer'=>'Bank Transfer'];
            $out = [];
            foreach ($rows as $r) {
                $out[] = ['id'=>$r['id'],'customer_name'=>$r['customer_name'],'type_label'=>$tl[$r['order_type']]??$r['order_type'],'total'=>number_format($r['total'],2)];
            }
            echo json_encode(['new_orders'=>$out]); exit;
        }
        if ($action === 'mark_seen') {
            $db->prepare("UPDATE online_orders SET seen=1 WHERE id=?")->execute([$_POST['order_id']]);
            echo json_encode(['ok'=>true]); exit;
        }
        if ($action === 'update_status') {
            $valid = ['confirmed','preparing','ready','completed','cancelled'];
            $status = $_POST['status'] ?? '';
            $orderId = (int)$_POST['order_id'];
            if (!in_array($status, $valid)) { echo json_encode(['ok'=>false]); exit; }

            $db->prepare("UPDATE online_orders SET status=?, seen=1 WHERE id=?")->execute([$status, $orderId]);
            logActivity('Updated Online Order', 'online_orders', 'Order #'.$orderId.' → '.$status);

            // When confirmed → auto-create a real bill in billing system
            if ($status === 'confirmed') {
                try {
                    $order = $db->prepare("SELECT * FROM online_orders WHERE id=?");
                    $order->execute([$orderId]);
                    $o = $order->fetch();
                    if ($o) {
                        $db->beginTransaction();
                        $payMap = ['takeaway'=>'Cash','card'=>'Card','bank_transfer'=>'Bank Transfer'];
                        $payMethod = $payMap[$o['order_type']] ?? 'Cash';
                        $billNo = 'ONL-' . date('Ymd') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
                        $db->prepare("
                            INSERT INTO bills
                                (bill_no, order_type, table_no, payment_method,
                                 subtotal, service_charge, discount_pct, discount_amt,
                                 tax_amt, total, status, notes, created_by)
                            VALUES (?, 'Takeaway', 'Online Order', ?, ?, ?, 0, 0, ?, ?, 'settled', ?, ?)")
                           ->execute([
                               $billNo, $payMethod,
                               $o['subtotal'], $o['service_charge'],
                               $o['tax'], $o['total'],
                               'Online Order — Customer: '.$o['customer_name'].' | '.$o['order_no'],
                               $_SESSION['user_id'] ?? 1,
                           ]);
                        $billId = $db->lastInsertId();
                        $ois = $db->prepare("SELECT * FROM online_order_items WHERE order_id=?");
                        $ois->execute([$orderId]);
                        $ins = $db->prepare("INSERT INTO bill_items(bill_id,menu_item_id,item_name,price,qty,line_total) VALUES(?,?,?,?,?,?)");
                        foreach ($ois->fetchAll() as $oi) {
                            $ins->execute([$billId,$oi['menu_item_id'],$oi['item_name'],$oi['price'],$oi['qty'],$oi['line_total']]);
                        }
                        $db->commit();
                        logActivity('Auto-Created Bill', 'online_orders', 'Bill '.$billNo.' ('.$payMethod.') from '.$o['order_no']);
                    }
                } catch (Exception $e) {
                    if ($db->inTransaction()) $db->rollBack();
                }
            }
            echo json_encode(['ok'=>true]); exit;
        }
    }

    // Form POST actions (non-AJAX)
    if ($action === 'edit_order') {
        $db->prepare("UPDATE online_orders SET customer_name=?,customer_phone=?,customer_note=?,order_type=?,status=? WHERE id=?")
           ->execute([
               trim($_POST['customer_name']),
               trim($_POST['customer_phone']),
               trim($_POST['customer_note'] ?? ''),
               $_POST['order_type'],
               $_POST['status'],
               (int)$_POST['order_id'],
           ]);
        logActivity('Edited Online Order', 'online_orders', 'Order #'.$_POST['order_id'].' updated');
        header('Location: online_orders.php?date='.($_GET['date'] ?? date('Y-m-d')).'&status='.($_GET['status'] ?? ''));
        exit;
    }

    if ($action === 'delete_order') {
        $oid = (int)$_POST['order_id'];
        $no = $db->prepare("SELECT order_no FROM online_orders WHERE id=?");
        $no->execute([$oid]); $no = $no->fetchColumn();
        $db->prepare("DELETE FROM online_order_items WHERE order_id=?")->execute([$oid]);
        $db->prepare("DELETE FROM online_orders WHERE id=?")->execute([$oid]);
        logActivity('Deleted Online Order', 'online_orders', 'Order '.$no.' deleted');
        header('Location: online_orders.php?date='.($_GET['date'] ?? date('Y-m-d')).'&status='.($_GET['status'] ?? ''));
        exit;
    }
}

// ── Filters ───────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$filterDate   = $_GET['date']   ?? date('Y-m-d');

$sql = "SELECT o.* FROM online_orders o WHERE DATE(o.created_at)=?";
$params = [$filterDate];
if ($filterStatus !== '') { $sql .= " AND o.status=?"; $params[] = $filterStatus; }
$sql .= " ORDER BY o.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

$newCount   = $db->query("SELECT COUNT(*) FROM online_orders WHERE seen=0 AND status='new'")->fetchColumn();
$todayTotal = $db->prepare("SELECT COALESCE(SUM(total),0) FROM online_orders WHERE DATE(created_at)=? AND status!='cancelled'");
$todayTotal->execute([date('Y-m-d')]); $todayTotal = $todayTotal->fetchColumn();
$todayCount = $db->prepare("SELECT COUNT(*) FROM online_orders WHERE DATE(created_at)=?");
$todayCount->execute([date('Y-m-d')]); $todayCount = $todayCount->fetchColumn();

$statusColors  = ['new'=>'badge-red','confirmed'=>'badge-blue','preparing'=>'badge-accent','ready'=>'badge-green','completed'=>'badge-muted','cancelled'=>'badge-muted'];
$typeLabels    = ['takeaway'=>'🥡 Takeaway','card'=>'💳 Card','bank_transfer'=>'🏦 Bank Transfer'];

include '../includes/header.php';
?>
<div class="page-header">
  <div>
    <div class="page-title">🛒 Online Orders
      <?php if ($newCount > 0): ?><span class="badge badge-red" style="font-size:13px;margin-left:10px;animation:pulse 1.5s infinite">🔴 <?=$newCount?> NEW</span><?php endif; ?>
    </div>
    <div class="page-sub">Customer orders placed via the online menu</div>
  </div>
  <a href="../online_menu.php" target="_blank" class="btn btn-outline">🌐 View Online Menu</a>
</div>
<style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}</style>

<div class="stats-grid mb-20">
  <div class="stat-card"><span class="stat-icon">🔴</span><div class="stat-label">New Orders</div><div class="stat-value text-red"><?=$newCount?></div></div>
  <div class="stat-card"><span class="stat-icon">📦</span><div class="stat-label">Today's Orders</div><div class="stat-value text-blue"><?=$todayCount?></div></div>
  <div class="stat-card"><span class="stat-icon">💰</span><div class="stat-label">Today's Revenue</div><div class="stat-value text-green"><?=fmt($todayTotal)?></div></div>
</div>

<div class="card mb-16" style="padding:14px 16px">
  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="date" name="date" class="form-control" style="max-width:170px" value="<?=htmlspecialchars($filterDate)?>">
    <select name="status" class="form-control" style="max-width:160px">
      <option value="">All Statuses</option>
      <?php foreach(['new','confirmed','preparing','ready','completed','cancelled'] as $s): ?>
        <option value="<?=$s?>" <?=$filterStatus===$s?'selected':''?>><?=ucfirst($s)?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm">Filter</button>
    <a href="?date=<?=date('Y-m-d')?>" class="btn btn-sm btn-outline">Today</a>
  </form>
</div>

<?php if (empty($orders)): ?>
<div class="card" style="text-align:center;padding:50px">
  <div style="font-size:48px;margin-bottom:12px">📭</div>
  <div style="font-weight:700;font-size:16px">No orders found</div>
  <div class="text-muted fs-12" style="margin-top:4px">Customer orders will appear here in real-time</div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px">
<?php foreach ($orders as $o):
  $isNew = !$o['seen'] && $o['status']==='new';
  $oisStmt = $db->prepare("SELECT * FROM online_order_items WHERE order_id=?");
  $oisStmt->execute([$o['id']]);
  $oi = $oisStmt->fetchAll();
  $waMsg = "📦 *Order Update — ".$o['order_no']."*\nHi ".$o['customer_name']."!\nStatus: *".ucfirst($o['status'])."*";
  if ($o['status']==='ready') $waMsg .= "\n✅ Your order is READY for pickup!";
  elseif ($o['status']==='confirmed') $waMsg .= "\nYour order has been confirmed!";
  $waPhone = '94'.ltrim(preg_replace('/\D/','',$o['customer_phone']),'0');
  $waUrl = 'https://wa.me/'.$waPhone.'?text='.rawurlencode($waMsg);
?>
<div class="card" id="order-<?=$o['id']?>" style="border-color:<?=$isNew?'var(--red)':'var(--border)'?>;<?=$isNew?'border-width:2px':''?>">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
    <div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span class="fw-700 mono text-accent" style="font-size:16px"><?=htmlspecialchars($o['order_no'])?></span>
        <span class="badge <?=$statusColors[$o['status']]??'badge-muted'?>"><?=ucfirst($o['status'])?></span>
        <?php if($isNew): ?><span class="badge badge-red" style="animation:pulse 1.5s infinite">🔴 NEW</span><?php endif; ?>
        <span class="badge badge-blue"><?=$typeLabels[$o['order_type']]??$o['order_type']?></span>
      </div>
      <div style="margin-top:6px;font-size:13px;color:var(--muted)">
        👤 <strong style="color:var(--text)"><?=htmlspecialchars($o['customer_name'])?></strong>
        &nbsp;|&nbsp; 📞 <?=htmlspecialchars($o['customer_phone'])?>
        &nbsp;|&nbsp; 🕐 <?=date('h:i A',strtotime($o['created_at']))?>
      </div>
      <?php if($o['customer_note']): ?><div style="margin-top:4px;font-size:12px;color:var(--accent)">📝 <?=htmlspecialchars($o['customer_note'])?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
      <div class="mono fw-700 text-green" style="font-size:18px"><?=fmt($o['total'])?></div>
      <div class="text-muted fs-12"><?=count($oi)?> items</div>
    </div>
  </div>

  <!-- Items -->
  <div style="margin-top:12px;background:rgba(255,255,255,.03);border-radius:8px;padding:10px 14px">
    <?php foreach ($oi as $item): ?>
    <div style="display:flex;justify-content:space-between;font-size:13px;padding:3px 0">
      <span><?=$item['qty']?>× <?=htmlspecialchars($item['item_name'])?></span>
      <span class="mono text-muted"><?=fmt($item['line_total'])?></span>
    </div>
    <?php endforeach; ?>
    <div style="border-top:1px solid var(--border);margin-top:6px;padding-top:6px;display:flex;justify-content:space-between;font-size:12px;color:var(--muted)">
      <span>Subtotal <?=fmt($o['subtotal'])?> · Service <?=fmt($o['service_charge'])?> · Tax <?=fmt($o['tax'])?></span>
      <strong style="color:var(--green)"><?=fmt($o['total'])?></strong>
    </div>
  </div>

  <!-- Actions -->
  <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <?php
    $nextActions = [
        'new'       => [['confirmed','✅ Confirm','btn-blue'],['cancelled','❌ Cancel','btn-red']],
        'confirmed' => [['preparing','🔥 Start Prep','btn-accent'],['cancelled','❌ Cancel','btn-red']],
        'preparing' => [['ready','✅ Ready','btn-green']],
        'ready'     => [['completed','🎉 Completed','btn-green']],
    ];
    if (isset($nextActions[$o['status']])):
      foreach ($nextActions[$o['status']] as [$ns,$label,$cls]):
    ?>
      <button class="btn btn-sm <?=$cls?>" onclick="updateStatus(<?=$o['id']?>,'<?=$ns?>')">
        <?=$label?>
      </button>
    <?php endforeach; endif; ?>

    <a href="<?=htmlspecialchars($waUrl)?>" target="_blank" class="btn btn-sm btn-wa">📲 WhatsApp</a>

    <!-- EDIT button -->
    <button class="btn btn-sm btn-outline"
      data-id="<?=$o['id']?>"
      data-name="<?=htmlspecialchars($o['customer_name'],ENT_QUOTES)?>"
      data-phone="<?=htmlspecialchars($o['customer_phone'],ENT_QUOTES)?>"
      data-note="<?=htmlspecialchars($o['customer_note']??'',ENT_QUOTES)?>"
      data-type="<?=$o['order_type']?>"
      data-status="<?=$o['status']?>"
      onclick="openEdit(this)">✏ Edit</button>

    <!-- DELETE -->
    <form method="POST" style="display:inline" onsubmit="return confirm('Delete order <?=htmlspecialchars($o['order_no'],ENT_QUOTES)?>? This cannot be undone.')">
      <input type="hidden" name="action" value="delete_order">
      <input type="hidden" name="order_id" value="<?=$o['id']?>">
      <button class="btn btn-sm btn-red">🗑 Delete</button>
    </form>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── EDIT ORDER MODAL ── -->
<div class="modal-overlay" id="modalEditOrder">
  <div class="modal-box">
    <div class="modal-title">✏ Edit Online Order</div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_order">
      <input type="hidden" name="order_id" id="eoId">
      <div class="form-row form-row-2 mb-12">
        <div class="form-group"><label class="form-label">Customer Name *</label><input name="customer_name" id="eoName" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Phone *</label><input name="customer_phone" id="eoPhone" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Order Type</label>
          <select name="order_type" id="eoType" class="form-control">
            <option value="takeaway">🥡 Takeaway — Pay on pickup (Cash)</option>
            <option value="card">💳 Card Payment</option>
            <option value="bank_transfer">🏦 Bank Transfer</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" id="eoStatus" class="form-control">
            <?php foreach(['new','confirmed','preparing','ready','completed','cancelled'] as $s): ?>
              <option value="<?=$s?>"><?=ucfirst($s)?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Customer Note</label>
          <textarea name="customer_note" id="eoNote" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn">Save Changes</button>
        <button type="button" class="btn btn-outline-muted" onclick="closeModal('modalEditOrder')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(btn) {
  document.getElementById('eoId').value     = btn.dataset.id;
  document.getElementById('eoName').value   = btn.dataset.name;
  document.getElementById('eoPhone').value  = btn.dataset.phone;
  document.getElementById('eoNote').value   = btn.dataset.note;
  document.getElementById('eoType').value   = btn.dataset.type;
  document.getElementById('eoStatus').value = btn.dataset.status;
  openModal('modalEditOrder');
}

function updateStatus(orderId, status) {
  var fd = new FormData();
  fd.append('action', 'update_status');
  fd.append('order_id', orderId);
  fd.append('status', status);
  fetch('online_orders.php', {method:'POST', body:fd})
    .then(function(){ location.reload(); });
}

setInterval(function() {
  var fd = new FormData(); fd.append('action','poll');
  fetch('online_orders.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(data=>{
      if(data.new_count>0) document.title='🔴 ('+data.new_count+') Online Orders — RestoPOS';
    });
}, 30000);
</script>
<?php include '../includes/footer.php'; ?>
