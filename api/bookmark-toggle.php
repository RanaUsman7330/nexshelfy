<?php
require __DIR__.'/bootstrap.php';
ensure_nexshelfy_serious_schema();
require_method('POST');
$d=json_input();
verify_csrf($d);
$u=require_user();
$slug=clean((string)($d['slug']??''),180);
$title=clean((string)($d['title']??$slug),255);
if(!$slug) fail('Article is missing.',422);
$desired=array_key_exists('saved',$d) ? (bool)$d['saved'] : null;
$st=db()->prepare('SELECT id FROM bookmarks WHERE user_id=? AND article_slug=?');
$st->execute([$u['id'],$slug]);
$row=$st->fetch();
$current=(bool)$row;
$saved=$desired===null ? !$current : $desired;
if($saved && !$current){
    db()->prepare('INSERT INTO bookmarks(user_id,article_slug,article_title,created_at) VALUES(?,?,?,NOW())')->execute([$u['id'],$slug,$title]);
    log_user_activity((int)$u['id'],'bookmark_saved','Bookmarked '.$title.'.');
} elseif(!$saved && $current){
    db()->prepare('DELETE FROM bookmarks WHERE id=?')->execute([$row['id']]);
    log_user_activity((int)$u['id'],'bookmark_removed','Removed bookmark '.$title.'.');
}
respond(['ok'=>true,'saved'=>$saved,'message'=>$saved?'Article bookmarked.':'Bookmark removed.']);
