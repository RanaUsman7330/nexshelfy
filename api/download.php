<?php
require __DIR__.'/bootstrap.php'; $u=require_user(); $id=(int)($_GET['id']??0); if($id<1){http_response_code(404);exit('Download not found.');}
$st=db()->prepare('SELECT d.id,d.download_count,p.file_path,oi.product_name FROM downloads d JOIN order_items oi ON oi.id=d.order_item_id JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id WHERE d.id=? AND d.user_id=? AND o.payment_status="paid" AND o.status="completed" LIMIT 1');
$st->execute([$id,$u['id']]); $row=$st->fetch(); if(!$row||!$row['file_path']){http_response_code(404);exit('This download is not available yet.');}
$base=realpath(dirname(__DIR__).'/storage/downloads'); $file=realpath(dirname(__DIR__).'/'.ltrim($row['file_path'],'/'));
if(!$base||!$file||!str_starts_with($file,$base)||!is_file($file)){http_response_code(404);exit('Download file is missing.');}
$up=db()->prepare('UPDATE downloads SET download_count=download_count+1,last_downloaded_at=NOW() WHERE id=?');$up->execute([$id]);
header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="'.basename($file).'"'); header('Content-Length: '.filesize($file)); readfile($file); exit;
