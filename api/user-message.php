<?php
require __DIR__.'/bootstrap.php';
$u=require_user();
try{if(!column_exists('contact_messages','user_id'))db()->exec('ALTER TABLE contact_messages ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id, ADD KEY idx_contact_user(user_id)');}catch(Throwable $e){}
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'){
  $rows=[];try{$st=db()->prepare('SELECT id,subject,message,status,created_at FROM contact_messages WHERE user_id=? ORDER BY id DESC LIMIT 50');$st->execute([$u['id']]);$rows=$st->fetchAll();}catch(Throwable $e){}
  respond(['ok'=>true,'messages'=>$rows,'csrf'=>csrf_token()]);
}
require_method('POST');$d=json_input();verify_csrf($d);$subject=clean((string)($d['subject']??''),160);$message=clean((string)($d['message']??''),3000);
if(mb_strlen($subject)<3)fail('Please enter a subject.');if(mb_strlen($message)<10)fail('Please enter a more detailed message.');
db()->prepare('INSERT INTO contact_messages(user_id,name,email,subject,message,status,ip_address,created_at) VALUES(?,?,?,?,?,"new",?,NOW())')->execute([$u['id'],$u['name'],$u['email'],$subject,$message,client_ip()]);
log_user_activity((int)$u['id'],'message_sent','Sent support inquiry: '.$subject.'.');respond(['ok'=>true,'message'=>'Your message has been sent to support.']);
