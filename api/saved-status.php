<?php
require __DIR__.'/bootstrap.php';
ensure_nexshelfy_serious_schema();
$u=current_user();
if(!$u) respond(['ok'=>true,'authenticated'=>false,'wishlist'=>[],'resources'=>[],'bookmarks'=>[],'csrf'=>csrf_token()]);
$wish=[];$resources=[];$marks=[];
try{$st=db()->prepare('SELECT p.slug FROM wishlists w JOIN products p ON p.id=w.product_id WHERE w.user_id=?');$st->execute([$u['id']]);$wish=array_column($st->fetchAll(),'slug');}catch(Throwable $e){}
try{$st=db()->prepare('SELECT r.slug FROM saved_resources s JOIN content_resources r ON r.id=s.resource_id WHERE s.user_id=?');$st->execute([$u['id']]);$resources=array_column($st->fetchAll(),'slug');}catch(Throwable $e){}
try{$st=db()->prepare('SELECT article_slug FROM bookmarks WHERE user_id=?');$st->execute([$u['id']]);$marks=array_column($st->fetchAll(),'article_slug');}catch(Throwable $e){}
respond(['ok'=>true,'authenticated'=>true,'wishlist'=>$wish,'resources'=>$resources,'bookmarks'=>$marks,'csrf'=>csrf_token()]);
