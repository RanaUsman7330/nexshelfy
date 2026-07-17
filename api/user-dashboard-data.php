<?php
require __DIR__.'/bootstrap.php';
$u=require_user();
try { ensure_commerce_schema(); } catch(Throwable $e) {}
$pdo=db();
function safe_all(PDO $pdo,string $sql,array $params=[]): array { try{$st=$pdo->prepare($sql);$st->execute($params);return $st->fetchAll()?:[];}catch(Throwable $e){return [];} }
function safe_one(PDO $pdo,string $sql,array $params=[]): array { try{$st=$pdo->prepare($sql);$st->execute($params);return $st->fetch()?:[];}catch(Throwable $e){return [];} }
$user=safe_one($pdo,'SELECT id,name,email,phone,avatar_url,bio,role,status,created_at,last_login_at FROM users WHERE id=? LIMIT 1',[$u['id']]);
if(!$user) $user=$u;
$orders=safe_all($pdo,'SELECT o.id,o.order_number,o.status,o.payment_status,o.payment_method,o.currency,o.subtotal,o.total,o.customer_phone,o.address_line1,o.address_line2,o.city,o.emirate,o.postal_code,o.order_notes,o.created_at,(SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS item_count FROM orders o WHERE o.user_id=? ORDER BY o.id DESC LIMIT 50',[$u['id']]);
$items=safe_all($pdo,'SELECT id,order_id,product_id,product_name,unit_price,quantity,line_total FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id=?) ORDER BY id',[$u['id']]);
$group=[]; foreach($items as $it){$group[(int)$it['order_id']][]=$it;} foreach($orders as &$o){$o['items']=$group[(int)$o['id']]??[];} unset($o);
$downloads=safe_all($pdo,'SELECT d.id,d.download_count,d.last_downloaded_at,d.created_at,oi.product_name,p.slug,p.file_path,o.order_number FROM downloads d JOIN order_items oi ON oi.id=d.order_item_id JOIN orders o ON o.id=oi.order_id LEFT JOIN products p ON p.id=oi.product_id WHERE d.user_id=? AND o.payment_status="paid" AND o.status="completed" ORDER BY d.id DESC',[$u['id']]);
$wishlist=safe_all($pdo,'SELECT p.id,p.slug,p.name,p.description,p.price,p.currency,w.created_at FROM wishlists w JOIN products p ON p.id=w.product_id WHERE w.user_id=? ORDER BY w.id DESC',[$u['id']]);
$resources=safe_all($pdo,'SELECT r.id,r.slug,r.title,r.description,r.resource_type,r.file_name,s.created_at FROM saved_resources s JOIN content_resources r ON r.id=s.resource_id WHERE s.user_id=? AND r.status="active" ORDER BY s.id DESC',[$u['id']]);
$bookmarks=safe_all($pdo,'SELECT id,article_slug,article_title,created_at FROM bookmarks WHERE user_id=? ORDER BY id DESC',[$u['id']]);
$notifications=safe_all($pdo,'SELECT id,title,message,type,is_read,action_url,created_at FROM user_notifications WHERE user_id=? ORDER BY id DESC LIMIT 50',[$u['id']]);
$activity=safe_all($pdo,'SELECT id,action,description,created_at FROM user_activity_logs WHERE user_id=? ORDER BY id DESC LIMIT 80',[$u['id']]);
$messages=safe_all($pdo,'SELECT id,subject,message,status,created_at FROM contact_messages WHERE user_id=? ORDER BY id DESC LIMIT 50',[$u['id']]);
try{$cart=get_user_cart((int)$u['id']);}catch(Throwable $e){$cart=[];}
$stats=['orders'=>count($orders),'downloads'=>count($downloads),'wishlist'=>count($wishlist),'resources'=>count($resources),'bookmarks'=>count($bookmarks),'cart'=>array_sum(array_map(fn($x)=>(int)($x['qty']??0),$cart)),'unread_notifications'=>count(array_filter($notifications,fn($n)=>(int)($n['is_read']??0)===0)),'spent'=>array_reduce($orders,fn($c,$o)=>$c+(($o['payment_status']??'')==='paid'?(float)$o['total']:0),0.0)];
respond(['ok'=>true,'csrf'=>csrf_token(),'user'=>$user,'stats'=>$stats,'orders'=>$orders,'downloads'=>$downloads,'wishlist'=>$wishlist,'resources'=>$resources,'bookmarks'=>$bookmarks,'notifications'=>$notifications,'activity'=>$activity,'messages'=>$messages,'cart'=>$cart]);
