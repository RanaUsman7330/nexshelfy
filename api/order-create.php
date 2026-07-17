<?php
require __DIR__.'/bootstrap.php';
require_method('POST');
$d=json_input(); verify_csrf($d); $u=require_user(); ensure_commerce_schema();
$items=$d['items']??[]; if(!is_array($items)||!count($items)) fail('Your cart is empty.');
$name=clean((string)($d['name']??$u['name']),100);
$email=strtolower(clean((string)($d['email']??$u['email']),190));
$phone=clean((string)($d['phone']??''),30);
$address1=clean((string)($d['address_line1']??''),255);
$address2=clean((string)($d['address_line2']??''),255);
$city=clean((string)($d['city']??''),120);
$emirate=clean((string)($d['emirate']??''),120);
$postal=clean((string)($d['postal_code']??''),30);
$notes=clean((string)($d['order_notes']??''),1500);
if(mb_strlen($name)<2) fail('Enter your full name.');
if(!valid_email($email)) fail('Enter a valid email address.');
if(mb_strlen($phone)<7) fail('Enter a valid phone number.');
if(mb_strlen($address1)<5) fail('Enter your delivery address.');
if(mb_strlen($city)<2 || mb_strlen($emirate)<2) fail('Enter your city and emirate.');
$pdo=db(); $pdo->beginTransaction();
try {
  $total=0.0; $valid=[];
  foreach($items as $item){
    $slug=clean((string)($item['slug']??''),160); $qty=max(1,min(10,(int)($item['qty']??1)));
    $p=product_by_slug($slug); if(!$p||$p['status']!=='active') continue;
    $line=(float)$p['price']*$qty; $total+=$line; $valid[]=[$p,$qty,$line];
  }
  if(!$valid) throw new RuntimeException('No valid products in cart.');
  $orderNo='NS-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
  $st=$pdo->prepare('INSERT INTO orders(user_id,order_number,status,payment_status,currency,subtotal,total,customer_name,customer_email,customer_phone,address_line1,address_line2,city,emirate,postal_code,order_notes,payment_method,created_at,updated_at) VALUES(?, ?, "pending", "unpaid", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "cod", NOW(), NOW())');
  $st->execute([$u['id'],$orderNo,envv('CURRENCY','AED'),$total,$total,$name,$email,$phone,$address1,$address2?:null,$city,$emirate,$postal?:null,$notes?:null]);
  $oid=(int)$pdo->lastInsertId();
  $ist=$pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,unit_price,quantity,line_total,created_at) VALUES(?,?,?,?,?,?,NOW())');
  foreach($valid as [$p,$q,$line]) $ist->execute([$oid,$p['id'],$p['name'],$p['price'],$q,$line]);
  $pdo->prepare('DELETE FROM user_cart_items WHERE user_id=?')->execute([$u['id']]);
  $pdo->commit();
  log_user_activity((int)$u['id'],'order_placed','Placed Cash on Delivery order '.$orderNo.'.');
  notify_user((int)$u['id'],'Order received','Your order '.$orderNo.' has been received and is awaiting confirmation.','order','/nexshelfy/dashboard/?view=orders');
  respond(['ok'=>true,'message'=>'Your Cash on Delivery order has been placed successfully.','order_number'=>$orderNo,'order_id'=>$oid,'total'=>$total,'payment_method'=>'cod']);
} catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
