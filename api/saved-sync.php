<?php
require __DIR__.'/bootstrap.php';
ensure_nexshelfy_serious_schema(); require_method('POST'); $d=json_input(); verify_csrf($d); $u=require_user();
$wishlist=is_array($d['wishlist']??null)?$d['wishlist']:[]; $resources=is_array($d['resources']??null)?$d['resources']:[]; $bookmarks=is_array($d['bookmarks']??null)?$d['bookmarks']:[];
$pdo=db();
foreach($wishlist as $slug){$p=product_by_slug(clean((string)$slug,160));if(!$p)continue;try{$pdo->prepare('INSERT IGNORE INTO wishlists(user_id,product_id,created_at) VALUES(?,?,NOW())')->execute([$u['id'],$p['id']]);}catch(Throwable $e){}}
foreach($resources as $slug){$slug=clean((string)$slug,180);if(!$slug)continue;try{$st=$pdo->prepare('SELECT id FROM content_resources WHERE slug=? AND status="active" LIMIT 1');$st->execute([$slug]);$rid=(int)$st->fetchColumn();if($rid)$pdo->prepare('INSERT IGNORE INTO saved_resources(user_id,resource_id,created_at) VALUES(?,?,NOW())')->execute([$u['id'],$rid]);}catch(Throwable $e){}}
foreach($bookmarks as $b){$slug=clean((string)($b['slug']??''),180);$title=clean((string)($b['title']??$slug),255);if(!$slug)continue;try{$pdo->prepare('INSERT IGNORE INTO bookmarks(user_id,article_slug,article_title,created_at) VALUES(?,?,?,NOW())')->execute([$u['id'],$slug,$title]);}catch(Throwable $e){}}
respond(['ok'=>true,'message'=>'Saved items synchronized.']);
