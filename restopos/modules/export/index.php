<?php
// ── Universal Export Handler ─────────────────────────────────
// Usage: export/index.php?report=sales&from=...&to=...&format=pdf|excel|print
require_once '../../includes/config.php';
requireAccess('reports');
$db     = getDB();
$report = $_GET['report'] ?? '';
$format = $_GET['format'] ?? 'print';
$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');
$extra  = $_GET['extra']  ?? 'All';
$bizName  = getSetting('business_name','RestoPOS');
$bizAddr  = getSetting('address','');
$bizPhone = getSetting('phone','');

// ── EXCEL (CSV download) ──────────────────────────────────────
if ($format === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$report.'_'.$from.'_'.$to.'.csv"');
    $out = fopen('php://output','w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

    switch ($report) {
        case 'sales':
            $q = "SELECT b.bill_no,DATE(b.created_at) as date,TIME(b.created_at) as time,b.order_type,b.table_no,b.subtotal,b.service_charge,b.discount_pct,b.discount_amt,b.tax_amt,b.total,b.payment_method,b.status,u.name as cashier FROM bills b LEFT JOIN users u ON b.created_by=u.id WHERE DATE(b.created_at) BETWEEN ? AND ? AND b.status='settled'";
            $p=[]; if ($extra!=='All') { $q.=" AND b.order_type=?"; $p=[$from,$to,$extra]; } else $p=[$from,$to];
            $rows=$db->prepare($q." ORDER BY b.created_at DESC"); $rows->execute($p); $rows=$rows->fetchAll();
            fputcsv($out,['Bill No','Date','Time','Order Type','Table','Subtotal','Service Charge','Discount %','Discount Amt','Tax','Total','Payment','Status','Cashier']);
            foreach($rows as $r) fputcsv($out,[$r['bill_no'],$r['date'],$r['time'],$r['order_type'],$r['table_no'],$r['subtotal'],$r['service_charge'],$r['discount_pct'],$r['discount_amt'],$r['tax_amt'],$r['total'],$r['payment_method'],$r['status'],$r['cashier']]);
            break;

        case 'expenses':
            $rows=$db->prepare("SELECT e.expense_date,ec.name as category,e.description,e.supplier,e.payment_method,e.amount FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC")->execute([$from,$to]) ? : null;
            $rows=$db->prepare("SELECT e.expense_date,ec.name as category,e.description,e.supplier,e.payment_method,e.amount FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC");
            $rows->execute([$from,$to]); $rows=$rows->fetchAll();
            fputcsv($out,['Date','Category','Description','Supplier','Payment Method','Amount (Rs.)']);
            foreach($rows as $r) fputcsv($out,[$r['expense_date'],$r['category'],$r['description'],$r['supplier'],$r['payment_method'],$r['amount']]);
            break;

        case 'inventory':
            $rows=$db->query("SELECT ii.name,ic.name as category,ii.unit,ii.qty,ii.min_qty,ii.unit_cost,(ii.qty*ii.unit_cost) as total_value FROM inventory_items ii JOIN inventory_categories ic ON ii.category_id=ic.id ORDER BY ic.name,ii.name")->fetchAll();
            fputcsv($out,['Item','Category','Unit','Qty','Min Qty','Unit Cost (Rs.)','Total Value (Rs.)','Status']);
            foreach($rows as $r) fputcsv($out,[$r['name'],$r['category'],$r['unit'],$r['qty'],$r['min_qty'],$r['unit_cost'],$r['total_value'],$r['qty']<=$r['min_qty']?'LOW STOCK':'OK']);
            break;

        case 'payroll':
            $monthStart=$extra.'-01'; $monthEnd=date('Y-m-t',strtotime($monthStart));
            $rows=$db->prepare("SELECT e.name,e.position,pr.basic_salary,pr.allowances,pr.overtime_pay,pr.bonus,pr.gross_salary,pr.salary_advance,pr.other_deductions,pr.epf_employee,pr.epf_employer,pr.etf_employer,pr.net_salary,pr.status FROM payroll pr JOIN employees e ON pr.employee_id=e.id WHERE pr.pay_month=? ORDER BY e.name");
            $rows->execute([$monthStart]); $rows=$rows->fetchAll();
            fputcsv($out,['Employee','Position','Basic','Allowances','OT Pay','Bonus','Gross','Advance','Other Ded.','EPF Emp (8%)','EPF Er (12%)','ETF (3%)','Net Salary','Status']);
            foreach($rows as $r) fputcsv($out,[$r['name'],$r['position'],$r['basic_salary'],$r['allowances'],$r['overtime_pay'],$r['bonus'],$r['gross_salary'],$r['salary_advance'],$r['other_deductions'],$r['epf_employee'],$r['epf_employer'],$r['etf_employer'],$r['net_salary'],$r['status']]);
            break;

        case 'debtors':
            $rows=$db->query("SELECT name,phone,email,credit_limit,outstanding FROM debtors ORDER BY outstanding DESC")->fetchAll();
            fputcsv($out,['Debtor Name','Phone','Email','Credit Limit (Rs.)','Outstanding (Rs.)','Status']);
            foreach($rows as $r) fputcsv($out,[$r['name'],$r['phone'],$r['email'],$r['credit_limit'],$r['outstanding'],$r['outstanding']>0?'Outstanding':'Cleared']);
            break;

        case 'pl':
            $s=$db->prepare("SELECT COALESCE(SUM(total),0) as rev,COALESCE(SUM(subtotal),0) as sub,COALESCE(SUM(service_charge),0) as svc,COALESCE(SUM(discount_amt),0) as disc,COALESCE(SUM(tax_amt),0) as tax FROM bills WHERE DATE(created_at) BETWEEN ? AND ? AND status='settled'");
            $s->execute([$from,$to]); $s=$s->fetch();
            $e=$db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE expense_date BETWEEN ? AND ?");
            $e->execute([$from,$to]); $exp=$e->fetch()['t'];
            fputcsv($out,['Item','Amount (Rs.)']);
            fputcsv($out,['=== REVENUE ===','']);
            fputcsv($out,['Gross Sales',$s['sub']]);
            fputcsv($out,['Service Charges',$s['svc']]);
            fputcsv($out,['Less: Discounts','-'.$s['disc']]);
            fputcsv($out,['Tax Collected',$s['tax']]);
            fputcsv($out,['Net Revenue',$s['rev']]);
            fputcsv($out,['','']);
            fputcsv($out,['=== EXPENSES ===','']);
            $cats=$db->prepare("SELECT ec.name,COALESCE(SUM(e.amount),0) as t FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN ? AND ? GROUP BY ec.name ORDER BY t DESC");
            $cats->execute([$from,$to]); foreach($cats->fetchAll() as $c) fputcsv($out,[$c['name'],$c['t']]);
            fputcsv($out,['Total Expenses',$exp]);
            fputcsv($out,['','']);
            fputcsv($out,['NET PROFIT',$s['rev']-$exp]);
            break;

        case 'attendance':
            $rows=$db->prepare("SELECT e.name,e.position,a.att_date,a.status,a.time_in,a.time_out,a.overtime_hours,a.notes FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.att_date BETWEEN ? AND ? AND e.name NOT LIKE '[DEL]%' ORDER BY a.att_date,e.name");
            $rows->execute([$from,$to]); $rows=$rows->fetchAll();
            fputcsv($out,['Employee','Position','Date','Status','Time In','Time Out','OT Hours','Notes']);
            foreach($rows as $r) fputcsv($out,[$r['name'],$r['position'],$r['att_date'],$r['status'],$r['time_in'],$r['time_out'],$r['overtime_hours'],$r['notes']]);
            break;

        case 'promotions':
            $rows=$db->prepare("SELECT bp.promo_name,bp.discount_amt,b.bill_no,b.created_at,b.order_type,b.payment_method,b.total
                FROM bill_promotions bp JOIN bills b ON bp.bill_id=b.id
                WHERE DATE(b.created_at) BETWEEN ? AND ? ORDER BY b.created_at DESC");
            $rows->execute([$from,$to]); $rows=$rows->fetchAll();
            fputcsv($out,['Bill No','Date','Order Type','Payment Method','Promotion','Discount (Rs.)','Bill Total (Rs.)']);
            foreach($rows as $r) fputcsv($out,[$r['bill_no'],date('Y-m-d H:i',strtotime($r['created_at'])),$r['order_type'],$r['payment_method'],$r['promo_name'],$r['discount_amt'],$r['total']]);
            break;
    }
    fclose($out);
    exit;
}

// ── PRINT / PDF (shared HTML) ─────────────────────────────────
// Get data for all reports
$reportData = [];
$reportTitle = '';
$reportSubtitle = "$bizName | $from to $to";

switch ($report) {
    case 'sales':
        $reportTitle = 'Sales Report';
        $w = "WHERE DATE(b.created_at) BETWEEN :f AND :t AND b.status='settled'";
        $p=[':f'=>$from,':t'=>$to];
        if($extra!=='All'){$w.=" AND b.order_type=:ot";$p[':ot']=$extra;}
        $sum=$db->prepare("SELECT COUNT(*) as bills,COALESCE(SUM(subtotal),0) as sub,COALESCE(SUM(service_charge),0) as svc,COALESCE(SUM(discount_amt),0) as disc,COALESCE(SUM(tax_amt),0) as tax,COALESCE(SUM(total),0) as total FROM bills b $w");
        $sum->execute($p); $sum=$sum->fetch();
        $byType=$db->prepare("SELECT order_type,COUNT(*) as cnt,COALESCE(SUM(total),0) as total FROM bills b $w GROUP BY order_type ORDER BY total DESC");
        $byType->execute($p); $byType=$byType->fetchAll();
        $topItems=$db->prepare("SELECT bi.item_name,SUM(bi.qty) as qty_sold,SUM(bi.line_total) as revenue FROM bill_items bi JOIN bills b ON bi.bill_id=b.id $w GROUP BY bi.item_name ORDER BY qty_sold DESC LIMIT 15");
        $topItems->execute($p); $topItems=$topItems->fetchAll();
        $bills=$db->prepare("SELECT b.*,u.name as cashier FROM bills b LEFT JOIN users u ON b.created_by=u.id $w ORDER BY b.created_at DESC");
        $bills->execute($p); $bills=$bills->fetchAll();
        $reportData=compact('sum','byType','topItems','bills');
        break;
    case 'expenses':
        $reportTitle='Expense Report';
        $rows=$db->prepare("SELECT e.*,ec.name as cat FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC");
        $rows->execute([$from,$to]); $rows=$rows->fetchAll();
        $byCat=$db->prepare("SELECT ec.name,COALESCE(SUM(e.amount),0) as total FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN ? AND ? GROUP BY ec.name ORDER BY total DESC");
        $byCat->execute([$from,$to]); $byCat=$byCat->fetchAll();
        $total=array_sum(array_column($rows,'amount'));
        $reportData=compact('rows','byCat','total');
        break;
    case 'pl':
        $reportTitle='Profit & Loss Report';
        $s=$db->prepare("SELECT COALESCE(SUM(total),0) as rev,COALESCE(SUM(subtotal),0) as sub,COALESCE(SUM(service_charge),0) as svc,COALESCE(SUM(discount_amt),0) as disc,COALESCE(SUM(tax_amt),0) as tax FROM bills WHERE DATE(created_at) BETWEEN ? AND ? AND status='settled'");
        $s->execute([$from,$to]); $s=$s->fetch();
        $exp=$db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM expenses WHERE expense_date BETWEEN ? AND ?");
        $exp->execute([$from,$to]); $exp=$exp->fetch()['t'];
        $expCats=$db->prepare("SELECT ec.name,COALESCE(SUM(e.amount),0) as total FROM expenses e JOIN expense_categories ec ON e.category_id=ec.id WHERE e.expense_date BETWEEN ? AND ? GROUP BY ec.name ORDER BY total DESC");
        $expCats->execute([$from,$to]); $expCats=$expCats->fetchAll();
        $profit=$s['rev']-$exp;
        $margin=$s['rev']>0?round($profit/$s['rev']*100,1):0;
        $reportData=compact('s','exp','expCats','profit','margin');
        break;
    case 'inventory':
        $reportTitle='Inventory Report';
        $rows=$db->query("SELECT ii.*,ic.name as cat FROM inventory_items ii JOIN inventory_categories ic ON ii.category_id=ic.id ORDER BY ic.name,ii.name")->fetchAll();
        $totalValue=array_sum(array_map(fn($i)=>$i['qty']*$i['unit_cost'],$rows));
        $reportData=compact('rows','totalValue');
        break;
    case 'payroll':
        $reportTitle='Payroll Report';
        $monthStart=$extra.'-01';
        $rows=$db->prepare("SELECT pr.*,e.name as emp_name,e.position FROM payroll pr JOIN employees e ON pr.employee_id=e.id WHERE pr.pay_month=? ORDER BY e.name");
        $rows->execute([$monthStart]); $rows=$rows->fetchAll();
        $totals=['gross'=>0,'epfe'=>0,'epfer'=>0,'etf'=>0,'net'=>0];
        foreach($rows as $p){$totals['gross']+=$p['gross_salary'];$totals['epfe']+=$p['epf_employee'];$totals['epfer']+=$p['epf_employer'];$totals['etf']+=$p['etf_employer'];$totals['net']+=$p['net_salary'];}
        $reportData=compact('rows','totals','monthStart');
        break;
    case 'debtors':
        $reportTitle='Debtor Report';
        $rows=$db->query("SELECT * FROM debtors ORDER BY outstanding DESC")->fetchAll();
        $totalDebt=array_sum(array_column($rows,'outstanding'));
        $reportData=compact('rows','totalDebt');
        break;
    case 'attendance':
        $reportTitle='Attendance Report';
        $rows=$db->prepare("SELECT e.name,e.position,e.basic_salary,a.att_date,a.status,a.time_in,a.time_out,a.overtime_hours,a.notes FROM attendance a JOIN employees e ON a.employee_id=e.id WHERE a.att_date BETWEEN ? AND ? AND e.name NOT LIKE '[DEL]%' ORDER BY a.att_date,e.name");
        $rows->execute([$from,$to]); $rows=$rows->fetchAll();
        $reportData=compact('rows');
        break;
    case 'promotions':
        $reportTitle='Promotions Report';
        $rows=$db->prepare("SELECT bp.promo_name,bp.discount_amt,b.bill_no,b.created_at,b.order_type,b.payment_method,b.total
            FROM bill_promotions bp JOIN bills b ON bp.bill_id=b.id
            WHERE DATE(b.created_at) BETWEEN ? AND ? ORDER BY b.created_at DESC");
        $rows->execute([$from,$to]); $rows=$rows->fetchAll();
        $summary=$db->prepare("SELECT bp.promo_name, COUNT(*) as uses, SUM(bp.discount_amt) as total_disc
            FROM bill_promotions bp JOIN bills b ON bp.bill_id=b.id
            WHERE DATE(b.created_at) BETWEEN ? AND ? GROUP BY bp.promo_name ORDER BY total_disc DESC");
        $summary->execute([$from,$to]); $summary=$summary->fetchAll();
        $totalDisc=array_sum(array_column($rows,'discount_amt'));
        $reportData=compact('rows','summary','totalDisc');
        break;
}

extract($reportData);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?=$reportTitle?> — <?=$bizName?></title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; background: #fff; padding: 20px; }
.header { text-align:center; border-bottom: 2px solid #333; padding-bottom:12px; margin-bottom:16px; }
.biz-name { font-size:20px; font-weight:bold; }
.report-title { font-size:16px; font-weight:bold; margin:6px 0 2px; text-transform:uppercase; letter-spacing:1px; color:#444; }
.report-sub { font-size:11px; color:#666; }
.section { margin-bottom: 18px; }
.section-title { font-weight:bold; font-size:12px; text-transform:uppercase; letter-spacing:.5px; background:#f0f0f0; padding:5px 10px; border:1px solid #ccc; margin-bottom:6px; }
table { width:100%; border-collapse:collapse; font-size:11px; }
th { background:#f5f5f5; padding:6px 8px; text-align:left; border:1px solid #ccc; font-weight:bold; white-space:nowrap; }
td { padding:5px 8px; border:1px solid #ddd; vertical-align:middle; }
tr:nth-child(even) td { background:#fafafa; }
.text-right { text-align:right; }
.text-center { text-align:center; }
.bold { font-weight:bold; }
.red { color:#c00; } .green { color:#060; } .blue { color:#00c; }
.tfoot td { font-weight:bold; background:#f0f0f0; border-top:2px solid #999; }
.summary-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px; }
.stat-box { border:1px solid #ccc; border-radius:4px; padding:10px; text-align:center; }
.stat-val { font-size:16px; font-weight:bold; }
.stat-lbl { font-size:10px; color:#666; text-transform:uppercase; }
.no-print { padding:10px; background:#f5f5f5; margin-bottom:16px; display:flex; gap:8px; align-items:center; flex-wrap:wrap; border:1px solid #ddd; border-radius:4px; }
.no-print button { padding:7px 16px; cursor:pointer; font-size:13px; border-radius:4px; border:1px solid #999; background:#fff; }
.no-print button.primary { background:#000; color:#fff; border-color:#000; }
.low-stock { background:#fff0f0 !important; }
.badge { display:inline-block; padding:1px 7px; border-radius:3px; font-size:10px; font-weight:bold; }
.badge-green { background:#e6f9ee; color:#060; border:1px solid #0a04; }
.badge-red   { background:#ffe6e6; color:#c00; border:1px solid #c002; }
.badge-accent{ background:#fff3cd; color:#856404; border:1px solid #f0ab003a; }
@media print {
  .no-print { display:none !important; }
  body { padding:10px; }
  @page { margin: 1.5cm; size: A4 landscape; }
}
</style>
</head>
<body>

<div class="no-print">
  <strong>Export Options:</strong>
  <button class="primary" onclick="window.print()">🖨 Print / Save as PDF</button>
  <button onclick="location.href='?report=<?=$report?>&format=excel&from=<?=$from?>&to=<?=$to?>&extra=<?=$extra?>'">📊 Download Excel (CSV)</button>
  <button onclick="window.close()">✕ Close</button>
  <span style="color:#666;font-size:12px;margin-left:8px">💡 To save as PDF: Print → Choose "Save as PDF" as the printer</span>
</div>

<div class="header">
  <div class="biz-name"><?=htmlspecialchars($bizName)?></div>
  <div style="font-size:11px;color:#666"><?=htmlspecialchars($bizAddr)?> | <?=htmlspecialchars($bizPhone)?></div>
  <div class="report-title"><?=$reportTitle?></div>
  <div class="report-sub">Period: <?=$from?> to <?=$to?> | Generated: <?=date('d/m/Y H:i')?></div>
</div>

<?php if ($report === 'pl'): ?>
<!-- ═══ P&L ═══ -->
<div class="summary-grid">
  <div class="stat-box"><div class="stat-val green">Rs. <?=number_format($s['rev'],2)?></div><div class="stat-lbl">Net Revenue</div></div>
  <div class="stat-box"><div class="stat-val red">Rs. <?=number_format($exp,2)?></div><div class="stat-lbl">Total Expenses</div></div>
  <div class="stat-box"><div class="stat-val <?=$profit>=0?'green':'red'?>">Rs. <?=number_format(abs($profit),2)?></div><div class="stat-lbl">Net Profit (<?=$margin?>%)</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
<div class="section">
  <div class="section-title">Revenue Breakdown</div>
  <table>
    <tr><th>Item</th><th class="text-right">Amount (Rs.)</th></tr>
    <tr><td>Gross Sales (Subtotal)</td><td class="text-right">Rs. <?=number_format($s['sub'],2)?></td></tr>
    <tr><td>Service Charges</td><td class="text-right">Rs. <?=number_format($s['svc'],2)?></td></tr>
    <tr><td>Less: Discounts</td><td class="text-right red">- Rs. <?=number_format($s['disc'],2)?></td></tr>
    <tr><td>Tax Collected</td><td class="text-right">Rs. <?=number_format($s['tax'],2)?></td></tr>
    <tr class="tfoot"><td>Net Revenue</td><td class="text-right green">Rs. <?=number_format($s['rev'],2)?></td></tr>
  </table>
</div>
<div class="section">
  <div class="section-title">Expense Breakdown</div>
  <table>
    <tr><th>Category</th><th class="text-right">Amount (Rs.)</th></tr>
    <?php foreach($expCats as $c): ?><tr><td><?=htmlspecialchars($c['name'])?></td><td class="text-right red">Rs. <?=number_format($c['total'],2)?></td></tr><?php endforeach; ?>
    <tr class="tfoot"><td>Total Expenses</td><td class="text-right red">Rs. <?=number_format($exp,2)?></td></tr>
  </table>
</div>
</div>
<div class="section" style="margin-top:16px">
  <table style="max-width:400px;margin-left:auto">
    <tr style="background:<?=$profit>=0?'#e6f9ee':'#ffe6e6'?>">
      <td class="bold" style="font-size:16px;padding:12px">NET PROFIT / LOSS</td>
      <td class="text-right bold <?=$profit>=0?'green':'red'?>" style="font-size:16px;padding:12px">
        <?=$profit>=0?'':'(LOSS) '?>Rs. <?=number_format(abs($profit),2)?></td>
    </tr>
  </table>
</div>

<?php elseif ($report === 'sales'): ?>
<!-- ═══ SALES ═══ -->
<div class="summary-grid">
  <div class="stat-box"><div class="stat-val blue"><?=$sum['bills']?></div><div class="stat-lbl">Total Bills</div></div>
  <div class="stat-box"><div class="stat-val green">Rs. <?=number_format($sum['total'],2)?></div><div class="stat-lbl">Net Revenue</div></div>
  <div class="stat-box"><div class="stat-val red">Rs. <?=number_format($sum['disc'],2)?></div><div class="stat-lbl">Discounts Given</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
<div class="section">
  <div class="section-title">Sales by Order Type</div>
  <table><tr><th>Type</th><th class="text-right">Bills</th><th class="text-right">Revenue (Rs.)</th></tr>
  <?php foreach($byType as $r): ?><tr><td><?=$r['order_type']?></td><td class="text-right"><?=$r['cnt']?></td><td class="text-right green bold">Rs. <?=number_format($r['total'],2)?></td></tr><?php endforeach; ?>
  <tr class="tfoot"><td>Total</td><td class="text-right"><?=$sum['bills']?></td><td class="text-right green">Rs. <?=number_format($sum['total'],2)?></td></tr>
  </table>
</div>
<div class="section">
  <div class="section-title">Top Selling Items</div>
  <table><tr><th>#</th><th>Item</th><th class="text-right">Qty</th><th class="text-right">Revenue (Rs.)</th></tr>
  <?php foreach($topItems as $i=>$item): ?><tr><td><?=$i+1?></td><td><?=htmlspecialchars($item['item_name'])?></td><td class="text-right bold"><?=$item['qty_sold']?></td><td class="text-right green">Rs. <?=number_format($item['revenue'],2)?></td></tr><?php endforeach; ?>
  </table>
</div>
</div>
<div class="section">
  <div class="section-title">Bill Details (<?=count($bills)?> records)</div>
  <table>
    <tr><th>Bill No</th><th>Date</th><th>Time</th><th>Type</th><th>Table</th><th>Method</th><th class="text-right">Subtotal</th><th class="text-right">Disc.</th><th class="text-right">Tax</th><th class="text-right">Total</th><th>Cashier</th></tr>
    <?php foreach($bills as $b): ?>
    <tr>
      <td class="bold"><?=htmlspecialchars($b['bill_no'])?></td>
      <td><?=date('d/m/Y',strtotime($b['created_at']))?></td>
      <td><?=date('H:i',strtotime($b['created_at']))?></td>
      <td><?=$b['order_type']?></td><td><?=$b['table_no']??'—'?></td><td><?=$b['payment_method']?></td>
      <td class="text-right">Rs. <?=number_format($b['subtotal'],2)?></td>
      <td class="text-right red"><?=$b['discount_amt']>0?'Rs. '.number_format($b['discount_amt'],2):'—'?></td>
      <td class="text-right">Rs. <?=number_format($b['tax_amt'],2)?></td>
      <td class="text-right bold green">Rs. <?=number_format($b['total'],2)?></td>
      <td><?=htmlspecialchars($b['cashier']??'—')?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="tfoot"><td colspan="6">TOTALS</td><td class="text-right">Rs. <?=number_format($sum['sub'],2)?></td><td class="text-right red">Rs. <?=number_format($sum['disc'],2)?></td><td class="text-right">Rs. <?=number_format($sum['tax'],2)?></td><td class="text-right green">Rs. <?=number_format($sum['total'],2)?></td><td></td></tr>
  </table>
</div>

<?php elseif ($report === 'expenses'): ?>
<!-- ═══ EXPENSES ═══ -->
<div class="summary-grid">
  <div class="stat-box"><div class="stat-val red">Rs. <?=number_format($total,2)?></div><div class="stat-lbl">Total Expenses</div></div>
  <div class="stat-box"><div class="stat-val blue"><?=count($rows)?></div><div class="stat-lbl">Transactions</div></div>
  <div class="stat-box"><div class="stat-val"><?=count($byCat)?></div><div class="stat-lbl">Categories</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
<div class="section">
  <div class="section-title">By Category</div>
  <table><tr><th>Category</th><th class="text-right">Amount (Rs.)</th><th class="text-right">%</th></tr>
  <?php foreach($byCat as $c): $pct=$total>0?round($c['total']/$total*100,1):0; ?>
    <tr><td><?=htmlspecialchars($c['name'])?></td><td class="text-right red bold">Rs. <?=number_format($c['total'],2)?></td><td class="text-right"><?=$pct?>%</td></tr>
  <?php endforeach; ?>
  <tr class="tfoot"><td>TOTAL</td><td class="text-right red">Rs. <?=number_format($total,2)?></td><td class="text-right">100%</td></tr>
  </table>
</div>
<div class="section">
  <div class="section-title">By Payment Method</div>
  <?php $byM=[]; foreach($rows as $r) $byM[$r['payment_method']]=($byM[$r['payment_method']]??0)+$r['amount']; ?>
  <table><tr><th>Method</th><th class="text-right">Amount (Rs.)</th></tr>
  <?php foreach($byM as $m=>$a): ?><tr><td><?=$m?></td><td class="text-right red">Rs. <?=number_format($a,2)?></td></tr><?php endforeach; ?>
  </table>
</div>
</div>
<div class="section">
  <div class="section-title">All Expense Transactions</div>
  <table><tr><th>Date</th><th>Category</th><th>Description</th><th>Supplier</th><th>Method</th><th class="text-right">Amount (Rs.)</th></tr>
  <?php foreach($rows as $r): ?><tr><td><?=date('d/m/Y',strtotime($r['expense_date']))?></td><td><?=htmlspecialchars($r['cat'])?></td><td><?=htmlspecialchars($r['description'])?></td><td><?=htmlspecialchars($r['supplier']??'—')?></td><td><?=$r['payment_method']?></td><td class="text-right bold red">Rs. <?=number_format($r['amount'],2)?></td></tr><?php endforeach; ?>
  <tr class="tfoot"><td colspan="5">TOTAL</td><td class="text-right red">Rs. <?=number_format($total,2)?></td></tr>
  </table>
</div>

<?php elseif ($report === 'inventory'): ?>
<!-- ═══ INVENTORY ═══ -->
<div class="summary-grid">
  <div class="stat-box"><div class="stat-val blue"><?=count($rows)?></div><div class="stat-lbl">Total Items</div></div>
  <div class="stat-box"><div class="stat-val red"><?=count(array_filter($rows,fn($i)=>$i['qty']<=$i['min_qty']))?></div><div class="stat-lbl">Low Stock Items</div></div>
  <div class="stat-box"><div class="stat-val green">Rs. <?=number_format($totalValue,2)?></div><div class="stat-lbl">Total Stock Value</div></div>
</div>
<div class="section">
  <div class="section-title">Stock Register</div>
  <table>
    <tr><th>Item</th><th>Category</th><th>Unit</th><th class="text-right">Qty</th><th class="text-right">Min Qty</th><th class="text-right">Unit Cost</th><th class="text-right">Total Value</th><th>Status</th></tr>
    <?php foreach($rows as $i): $low=$i['qty']<=$i['min_qty']; ?>
    <tr <?=$low?'class="low-stock"':''?>>
      <td class="bold"><?=htmlspecialchars($i['name'])?></td><td><?=htmlspecialchars($i['cat'])?></td><td><?=$i['unit']?></td>
      <td class="text-right <?=$low?'red bold':'green bold'?>"><?=number_format($i['qty'],2)?></td>
      <td class="text-right"><?=number_format($i['min_qty'],2)?></td>
      <td class="text-right">Rs. <?=number_format($i['unit_cost'],2)?></td>
      <td class="text-right bold">Rs. <?=number_format($i['qty']*$i['unit_cost'],2)?></td>
      <td><span class="badge <?=$low?'badge-red':'badge-green'?>"><?=$low?'LOW STOCK':'OK'?></span></td>
    </tr>
    <?php endforeach; ?>
    <tr class="tfoot"><td colspan="6">TOTAL STOCK VALUE</td><td class="text-right green">Rs. <?=number_format($totalValue,2)?></td><td></td></tr>
  </table>
</div>

<?php elseif ($report === 'payroll'): ?>
<!-- ═══ PAYROLL ═══ -->
<div class="summary-grid">
  <div class="stat-box"><div class="stat-val green">Rs. <?=number_format($totals['net'],2)?></div><div class="stat-lbl">Total Net Payable</div></div>
  <div class="stat-box"><div class="stat-val blue">Rs. <?=number_format($totals['epfer'],2)?></div><div class="stat-lbl">EPF Employer (12%)</div></div>
  <div class="stat-box"><div class="stat-val" style="color:#7c3aed">Rs. <?=number_format($totals['etf'],2)?></div><div class="stat-lbl">ETF Employer (3%)</div></div>
</div>
<div class="section">
  <div class="section-title">Payroll Register — <?=date('F Y',strtotime($monthStart))?></div>
  <table>
    <tr><th>Employee</th><th>Position</th><th class="text-right">Basic</th><th class="text-right">Allow.</th><th class="text-right">OT</th><th class="text-right">Bonus</th><th class="text-right">Gross</th><th class="text-right">EPF Emp</th><th class="text-right">EPF Er</th><th class="text-right">ETF</th><th class="text-right">Advance</th><th class="text-right">Net</th><th>Status</th></tr>
    <?php foreach($rows as $p): ?>
    <tr>
      <td class="bold"><?=htmlspecialchars($p['emp_name'])?></td><td style="font-size:10px"><?=htmlspecialchars($p['position']??'')?></td>
      <td class="text-right">Rs. <?=number_format($p['basic_salary'],2)?></td>
      <td class="text-right">Rs. <?=number_format($p['allowances'],2)?></td>
      <td class="text-right">Rs. <?=number_format($p['overtime_pay'],2)?></td>
      <td class="text-right">Rs. <?=number_format($p['bonus'],2)?></td>
      <td class="text-right bold">Rs. <?=number_format($p['gross_salary'],2)?></td>
      <td class="text-right red">Rs. <?=number_format($p['epf_employee'],2)?></td>
      <td class="text-right">Rs. <?=number_format($p['epf_employer'],2)?></td>
      <td class="text-right">Rs. <?=number_format($p['etf_employer'],2)?></td>
      <td class="text-right red">Rs. <?=number_format($p['salary_advance'],2)?></td>
      <td class="text-right bold green">Rs. <?=number_format($p['net_salary'],2)?></td>
      <td><span class="badge <?=$p['status']==='paid'?'badge-green':'badge-accent'?>"><?=ucfirst($p['status'])?></span></td>
    </tr>
    <?php endforeach; ?>
    <tr class="tfoot">
      <td colspan="6">TOTALS</td>
      <td class="text-right">Rs. <?=number_format($totals['gross'],2)?></td>
      <td class="text-right red">Rs. <?=number_format($totals['epfe'],2)?></td>
      <td class="text-right">Rs. <?=number_format($totals['epfer'],2)?></td>
      <td class="text-right">Rs. <?=number_format($totals['etf'],2)?></td>
      <td></td>
      <td class="text-right green">Rs. <?=number_format($totals['net'],2)?></td>
      <td></td>
    </tr>
  </table>
</div>

<?php elseif ($report === 'debtors'): ?>
<!-- ═══ DEBTORS ═══ -->
<div class="summary-grid">
  <div class="stat-box"><div class="stat-val red">Rs. <?=number_format($totalDebt,2)?></div><div class="stat-lbl">Total Outstanding</div></div>
  <div class="stat-box"><div class="stat-val blue"><?=count($rows)?></div><div class="stat-lbl">Total Debtors</div></div>
  <div class="stat-box"><div class="stat-val green"><?=count(array_filter($rows,fn($d)=>$d['outstanding']==0))?></div><div class="stat-lbl">Cleared</div></div>
</div>
<div class="section">
  <div class="section-title">Debtor Ledger</div>
  <table>
    <tr><th>Debtor Name</th><th>Phone</th><th>Email</th><th class="text-right">Credit Limit</th><th class="text-right">Outstanding</th><th>Status</th></tr>
    <?php foreach($rows as $d): ?>
    <tr <?=$d['outstanding']>0?'class="low-stock"':''?>>
      <td class="bold"><?=htmlspecialchars($d['name'])?></td>
      <td><?=htmlspecialchars($d['phone']??'—')?></td>
      <td><?=htmlspecialchars($d['email']??'—')?></td>
      <td class="text-right"><?=$d['credit_limit']>0?'Rs. '.number_format($d['credit_limit'],2):'—'?></td>
      <td class="text-right bold red">Rs. <?=number_format($d['outstanding'],2)?></td>
      <td><span class="badge <?=$d['outstanding']>0?'badge-red':'badge-green'?>"><?=$d['outstanding']>0?'Outstanding':'Cleared'?></span></td>
    </tr>
    <?php endforeach; ?>
    <tr class="tfoot"><td colspan="4">TOTAL OUTSTANDING</td><td class="text-right red">Rs. <?=number_format($totalDebt,2)?></td><td></td></tr>
  </table>
</div>

<?php elseif ($report === 'attendance'): ?>
<!-- ═══ ATTENDANCE ═══ -->
<div class="section">
  <div class="section-title">Attendance Report — <?=$from?> to <?=$to?></div>
  <table>
    <tr><th>Employee</th><th>Position</th><th>Date</th><th>Status</th><th>Time In</th><th>Time Out</th><th class="text-right">OT Hours</th><th class="text-right">OT Pay</th><th>Notes</th></tr>
    <?php
    $emps = $db->query("SELECT * FROM employees")->fetchAll();
    $empMap = array_column($emps,null,'id');
    foreach($rows as $r):
      $basic = $empMap[$r['employee_id']??0]['basic_salary']??0;
      // find employee by name match (since we joined)
      $basic2 = 0;
      foreach($emps as $ee) if($ee['name']===$r['name']) {$basic2=$ee['basic_salary']; break;}
      $otPay = round(($basic2/26/9)*1.5*$r['overtime_hours'],2);
    ?>
    <tr>
      <td class="bold"><?=htmlspecialchars($r['name'])?></td>
      <td style="font-size:10px"><?=htmlspecialchars($r['position']??'')?></td>
      <td><?=date('d/m/Y',strtotime($r['att_date']))?></td>
      <td><span class="badge <?=$r['status']==='Present'?'badge-green':($r['status']==='Absent'?'badge-red':'badge-accent')?>"><?=$r['status']?></span></td>
      <td><?=$r['time_in']??'—'?></td>
      <td><?=$r['time_out']??'—'?></td>
      <td class="text-right bold"><?=number_format($r['overtime_hours'],2)?></td>
      <td class="text-right green"><?=$r['overtime_hours']>0?'Rs. '.number_format($otPay,2):'—'?></td>
      <td style="font-size:10px"><?=htmlspecialchars($r['notes']??'')?></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($rows)):?><tr><td colspan="9" class="text-center" style="color:#999">No records for this period.</td></tr><?php endif; ?>
  </table>
</div>

<?php elseif ($report === 'promotions'): ?>
<!-- ═══ PROMOTIONS ═══ -->
<div class="summary-grid">
  <div class="stat-box"><div class="stat-val blue"><?=count($rows)?></div><div class="stat-lbl">Promotions Used</div></div>
  <div class="stat-box"><div class="stat-val red">Rs. <?=number_format($totalDisc,2)?></div><div class="stat-lbl">Total Discount Given</div></div>
  <div class="stat-box"><div class="stat-val"><?=count($summary)?></div><div class="stat-lbl">Distinct Promotions</div></div>
</div>

<div class="section">
  <div class="section-title">Promotion Usage Summary</div>
  <table>
    <tr><th>Promotion Name</th><th class="text-right">Times Used</th><th class="text-right">Total Discount (Rs.)</th></tr>
    <?php foreach($summary as $s): ?>
    <tr>
      <td class="bold">🎉 <?=htmlspecialchars($s['promo_name'])?></td>
      <td class="text-right bold"><?=$s['uses']?></td>
      <td class="text-right red bold">- Rs. <?=number_format($s['total_disc'],2)?></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($summary)):?><tr><td colspan="3" class="text-center" style="color:#999">No promotions used in this period.</td></tr><?php endif; ?>
    <?php if(!empty($summary)): ?>
    <tr class="tfoot"><td>TOTAL</td><td class="text-right"><?=count($rows)?></td><td class="text-right red">- Rs. <?=number_format($totalDisc,2)?></td></tr>
    <?php endif; ?>
  </table>
</div>

<div class="section">
  <div class="section-title">Promotion Usage — Bill by Bill</div>
  <table>
    <tr><th>Bill No</th><th>Date/Time</th><th>Order Type</th><th>Payment</th><th>Promotion</th><th class="text-right">Discount (Rs.)</th><th class="text-right">Bill Total (Rs.)</th></tr>
    <?php foreach($rows as $r): ?>
    <tr>
      <td class="bold"><?=htmlspecialchars($r['bill_no'])?></td>
      <td style="font-size:10px"><?=date('d/m/Y H:i',strtotime($r['created_at']))?></td>
      <td><?=htmlspecialchars($r['order_type'])?></td>
      <td><?=htmlspecialchars($r['payment_method'])?></td>
      <td class="bold">🎉 <?=htmlspecialchars($r['promo_name'])?></td>
      <td class="text-right red">- Rs. <?=number_format($r['discount_amt'],2)?></td>
      <td class="text-right green bold">Rs. <?=number_format($r['total'],2)?></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($rows)):?><tr><td colspan="7" class="text-center" style="color:#999">No promotion usage records for this period.</td></tr><?php endif; ?>
  </table>
</div>
<?php endif; ?>

<div style="margin-top:30px;text-align:center;font-size:10px;color:#999;border-top:1px solid #ddd;padding-top:10px">
  <?=htmlspecialchars($bizName)?> | Generated by RestoPOS Sri Lanka | <?=date('d/m/Y H:i')?>
</div>

<?php if ($format !== 'print'): ?>
<script>window.onload = () => setTimeout(() => window.print(), 500);</script>
<?php endif; ?>
</body>
</html>
