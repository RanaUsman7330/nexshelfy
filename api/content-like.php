<?php
require __DIR__ . '/bootstrap.php';
ensure_nexshelfy_serious_schema();
$type=clean((string)($_GET['type']??''),20);$key=clean((string)($_GET['key']??''),180);$visitor=clean((string)($_GET['visitor']??''),80);
if(!in_array($type,['product','blog'],true)||!$key) fail('Invalid content.',422);
$user=current_user();
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 $d=json_input();verify_csrf($d);$visitor=clean((string)($d['visitor']??$visitor),80);$liked=(bool)($d['liked']??true);
 if(!$user&&!$visitor) fail('Visitor identifier required.',422);
 if($liked){
  $sql=$user?'INSERT IGNORE INTO content_likes(user_id,visitor_key,content_type,content_key,created_at) VALUES(?,NULL,?,?,NOW())':'INSERT IGNORE INTO content_likes(user_id,visitor_key,content_type,content_key,created_at) VALUES(NULL,?,?,?,NOW())';
  db()->prepare($sql)->execute($user?[(int)$user['id'],$type,$key]:[$visitor,$type,$key]);
 }else{
  $sql=$user?'DELETE FROM content_likes WHERE user_id=? AND content_type=? AND content_key=?':'DELETE FROM content_likes WHERE visitor_key=? AND content_type=? AND content_key=?';
  db()->prepare($sql)->execute($user?[(int)$user['id'],$type,$key]:[$visitor,$type,$key]);
 }
}
$countSt=db()->prepare('SELECT COUNT(*) FROM content_likes WHERE content_type=? AND content_key=?');$countSt->execute([$type,$key]);$count=(int)$countSt->fetchColumn();
$liked=false;if($user){$s=db()->prepare('SELECT 1 FROM content_likes WHERE user_id=? AND content_type=? AND content_key=?');$s->execute([(int)$user['id'],$type,$key]);$liked=(bool)$s->fetchColumn();}elseif($visitor){$s=db()->prepare('SELECT 1 FROM content_likes WHERE visitor_key=? AND content_type=? AND content_key=?');$s->execute([$visitor,$type,$key]);$liked=(bool)$s->fetchColumn();}
respond(['ok'=>true,'liked'=>$liked,'count'=>$count,'csrf'=>csrf_token()]);
