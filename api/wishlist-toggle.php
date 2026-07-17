<?php
require __DIR__.'/bootstrap.php';
ensure_nexshelfy_serious_schema();
require_method('POST');
$d=json_input();
verify_csrf($d);
$u=require_user();
ensure_user_dashboard_schema();
$slug=clean((string)($d['slug']??''),160);
$p=product_by_slug($slug);
if(!$p) fail('Product not found.',404);
$desired=array_key_exists('saved',$d) ? (bool)$d['saved'] : null;
$st=db()->prepare('SELECT id FROM wishlists WHERE user_id=? AND product_id=?');
$st->execute([$u['id'],$p['id']]);
$row=$st->fetch();
$current=(bool)$row;
$saved=$desired===null ? !$current : $desired;
if($saved && !$current){
    db()->prepare('INSERT INTO wishlists(user_id,product_id,created_at) VALUES(?,?,NOW())')->execute([$u['id'],$p['id']]);
    log_user_activity((int)$u['id'],'wishlist_saved','Saved '.$p['name'].' to wishlist.');
} elseif(!$saved && $current){
    db()->prepare('DELETE FROM wishlists WHERE id=?')->execute([$row['id']]);
    log_user_activity((int)$u['id'],'wishlist_removed','Removed '.$p['name'].' from wishlist.');
}
respond(['ok'=>true,'saved'=>$saved,'message'=>$saved?'Saved to wishlist.':'Removed from wishlist.']);
