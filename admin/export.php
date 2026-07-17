<?php
require __DIR__.'/../api/bootstrap.php';
$u=current_user();
if(!$u||$u['role']!=='admin'){http_response_code(403);exit('Forbidden');}
$type=$_GET['type']??'';
if(!in_array($type,['subscribers','download_leads'],true)){http_response_code(400);exit('Invalid export');}
header('Content-Type: text/csv; charset=utf-8');
$filename = $type==='download_leads' ? 'nexshelfy-download-leads-' : 'nexshelfy-subscribers-';
header('Content-Disposition: attachment; filename="'.$filename.date('Y-m-d').'.csv"');
$out=fopen('php://output','w');
if($type==='download_leads'){
    fputcsv($out,['Email','Name','Item Type','Item Key','Item Title','IP Address','Created At']);
    foreach(db()->query('SELECT email,name,item_type,item_key,item_title,ip_address,created_at FROM download_leads ORDER BY id DESC') as $r) fputcsv($out,$r);
} else {
    fputcsv($out,['Email','Status','Subscribed At']);
    foreach(db()->query('SELECT email,status,subscribed_at FROM newsletter_subscribers ORDER BY id DESC') as $r) fputcsv($out,$r);
}
fclose($out);
