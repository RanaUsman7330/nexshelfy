<?php
require __DIR__.'/bootstrap.php';
$u=require_user();
ensure_commerce_schema();
$pdo=db();
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET') respond(['ok'=>true,'cart'=>get_user_cart((int)$u['id']),'csrf'=>csrf_token()]);
require_method('POST');
$d=json_input(); verify_csrf($d); $action=(string)($d['action']??'sync');
if($action==='clear'){
  $pdo->prepare('DELETE FROM user_cart_items WHERE user_id=?')->execute([$u['id']]);
  log_user_activity((int)$u['id'],'cart_cleared','Cleared the shopping cart.');
  respond(['ok'=>true,'cart'=>[]]);
}
$items=$d['items']??[];
if(!is_array($items)) fail('Invalid cart data.');
$valid=[];
foreach($items as $item){
  $slug=clean((string)($item['slug']??''),160); $qty=max(0,min(10,(int)($item['qty']??1)));
  if(!$slug) continue; $p=product_by_slug($slug); if(!$p||$p['status']!=='active') continue; $valid[(int)$p['id']]=[$p,$qty];
}
$pdo->beginTransaction();
try{
  if($action==='sync') $pdo->prepare('DELETE FROM user_cart_items WHERE user_id=?')->execute([$u['id']]);
  $up=$pdo->prepare('INSERT INTO user_cart_items(user_id,product_id,quantity,created_at,updated_at) VALUES(?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),updated_at=NOW()');
  $del=$pdo->prepare('DELETE FROM user_cart_items WHERE user_id=? AND product_id=?');
  foreach($valid as $pid=>[$p,$qty]){ if($qty<=0)$del->execute([$u['id'],$pid]); else $up->execute([$u['id'],$pid,$qty]); }
  $pdo->commit();
}catch(Throwable $e){$pdo->rollBack();throw $e;}
log_user_activity((int)$u['id'],'cart_updated','Updated shopping cart.');
respond(['ok'=>true,'cart'=>get_user_cart((int)$u['id'])]);
