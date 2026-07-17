<?php
require __DIR__.'/bootstrap.php';
ensure_nexshelfy_serious_schema();
require_method('POST');
$d=json_input();
verify_csrf($d);
$u=require_user();
$slug=clean((string)($d['slug']??''),180);
if(!$slug) fail('Resource is missing.',422);
$st=db()->prepare('SELECT id,title FROM content_resources WHERE slug=? AND status="active" LIMIT 1');
$st->execute([$slug]);
$resource=$st->fetch();
if(!$resource) fail('Resource not found.',404);
$desired=array_key_exists('saved',$d) ? (bool)$d['saved'] : null;
$st=db()->prepare('SELECT id FROM saved_resources WHERE user_id=? AND resource_id=?');
$st->execute([$u['id'],$resource['id']]);
$row=$st->fetch();
$current=(bool)$row;
$saved=$desired===null ? !$current : $desired;
if($saved && !$current){
    db()->prepare('INSERT INTO saved_resources(user_id,resource_id,created_at) VALUES(?,?,NOW())')->execute([$u['id'],$resource['id']]);
    log_user_activity((int)$u['id'],'resource_saved','Saved resource '.$resource['title'].'.');
    site_log('save','resource',(int)$resource['id'],['user_id'=>$u['id'],'title'=>$resource['title']]);
} elseif(!$saved && $current){
    db()->prepare('DELETE FROM saved_resources WHERE id=?')->execute([$row['id']]);
    log_user_activity((int)$u['id'],'resource_removed','Removed resource '.$resource['title'].'.');
    site_log('remove_save','resource',(int)$resource['id'],['user_id'=>$u['id'],'title'=>$resource['title']]);
}
respond(['ok'=>true,'saved'=>$saved,'message'=>$saved?'Resource saved.':'Resource removed.']);
