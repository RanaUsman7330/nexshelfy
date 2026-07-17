<?php
require __DIR__ . '/bootstrap.php';
ensure_nexshelfy_serious_schema();
$slug=clean((string)($_GET['slug']??''),180);if(!$slug) fail('Blog post required.',422);
$st=db()->prepare('SELECT id FROM blog_posts WHERE slug=? AND status="published" LIMIT 1');$st->execute([$slug]);$postId=(int)$st->fetchColumn();if(!$postId) fail('Blog post not found.',404);
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 $d=json_input();verify_csrf($d);$u=current_user();$name=$u?$u['name']:clean((string)($d['name']??''),100);$email=$u?$u['email']:clean((string)($d['email']??''),190);$comment=clean((string)($d['comment']??''),3000);
 if(!$name||!valid_email($email)||mb_strlen($comment)<3) fail('Name, valid email and comment are required.',422);
 db()->prepare('INSERT INTO blog_comments(blog_post_id,user_id,name,email,comment,status,created_at) VALUES(?,?,?,?,?,"approved",NOW())')->execute([$postId,$u?(int)$u['id']:null,$name,$email?:null,$comment]);
}
$q=db()->prepare('SELECT id,name,comment,created_at FROM blog_comments WHERE blog_post_id=? AND status="approved" ORDER BY id DESC LIMIT 100');$q->execute([$postId]);respond(['ok'=>true,'comments'=>$q->fetchAll(),'csrf'=>csrf_token()]);
