<?php
require __DIR__ . '/bootstrap.php';
$u=require_user();$uid=(int)$u['id'];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 $d=json_input();verify_csrf($d);$action=clean((string)($d['action']??''),20);
 if($action==='create'){$name=clean((string)($d['name']??''),80);if(!$name)fail('Category name required.',422);db()->prepare('INSERT IGNORE INTO saved_categories(user_id,name,created_at) VALUES(?,?,NOW())')->execute([$uid,$name]);}
 elseif($action==='assign'){$id=(int)($d['category_id']??0);$requestedType=(string)($d['item_type']??'product');$type=in_array($requestedType,['product','resource','blog'],true)?$requestedType:'product';$key=clean((string)($d['item_key']??''),180);$s=db()->prepare('SELECT id FROM saved_categories WHERE id=? AND user_id=?');$s->execute([$id,$uid]);if(!$s->fetchColumn())fail('Category not found.',404);db()->prepare('DELETE FROM saved_category_items WHERE item_type=? AND item_key=? AND category_id IN (SELECT id FROM saved_categories WHERE user_id=?)')->execute([$type,$key,$uid]);if($id)db()->prepare('INSERT IGNORE INTO saved_category_items(category_id,item_type,item_key,created_at) VALUES(?,?,?,NOW())')->execute([$id,$type,$key]);}
 elseif($action==='delete'){$id=(int)($d['category_id']??0);db()->prepare('DELETE FROM saved_categories WHERE id=? AND user_id=?')->execute([$id,$uid]);}
}
$q=db()->prepare('SELECT c.id,c.name,i.item_type,i.item_key FROM saved_categories c LEFT JOIN saved_category_items i ON i.category_id=c.id WHERE c.user_id=? ORDER BY c.name,i.id');$q->execute([$uid]);$cats=[];foreach($q->fetchAll() as $r){$id=(int)$r['id'];if(!isset($cats[$id]))$cats[$id]=['id'=>$id,'name'=>$r['name'],'items'=>[]];if($r['item_key'])$cats[$id]['items'][]=['type'=>$r['item_type'],'key'=>$r['item_key']];}respond(['ok'=>true,'categories'=>array_values($cats),'csrf'=>csrf_token()]);
