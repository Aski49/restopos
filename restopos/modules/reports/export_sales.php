<?php
require_once '../../includes/config.php';
requireAccess('reports');
$db = getDB();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$type = $_GET['type'] ?? 'All';

$where = "WHERE DATE(b.created_at) BETWEEN :from AND :to AND b.status='settled'";
$params = [':from'=>$from, ':to'=>$to];
if ($type !== 'All') { $where .= " AND b.order_type=:otype"; $params[':otype']=$type; }

$bills = $db->prepare("SELECT b.bill_no, DATE(b.created_at) as date, TIME(b.created_at) as time, b.order_type, b.table_no, b.subtotal, b.service_charge, b.discount_amt, b.tax_amt, b.total, b.payment_method, b.status, u.name as cashier FROM bills b LEFT JOIN users u ON b.created_by=u.id $where ORDER BY b.created_at DESC");
$bills->execute($params);
$bills = $bills->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sales_'.$from.'_to_'.$to.'.csv"');
$f = fopen('php://output','w');
fputcsv($f,['Bill No','Date','Time','Order Type','Table','Subtotal','Service Charge','Discount','Tax','Total','Payment Method','Cashier','Status']);
foreach ($bills as $b) {
    fputcsv($f,[$b['bill_no'],$b['date'],$b['time'],$b['order_type'],$b['table_no']??'',$b['subtotal'],$b['service_charge'],$b['discount_amt'],$b['tax_amt'],$b['total'],$b['payment_method'],$b['cashier']??'',$b['status']]);
}
fclose($f);
exit;
