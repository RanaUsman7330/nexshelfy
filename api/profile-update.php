<?php
require __DIR__.'/bootstrap.php'; ensure_user_dashboard_schema(); require_method('POST'); $d=json_input(); verify_csrf($d); $u=require_user();
$name=clean((string)($d['name']??''),100); $phone=clean((string)($d['phone']??''),30); $bio=clean((string)($d['bio']??''),500);
if(mb_strlen($name)<2) fail('Please enter your full name.');
$st=db()->prepare('UPDATE users SET name=?,phone=?,bio=?,updated_at=NOW() WHERE id=?'); $st->execute([$name,$phone?:null,$bio?:null,$u['id']]);
$log=db()->prepare('INSERT INTO user_activity_logs(user_id,action,description,ip_address,created_at) VALUES(?,?,?,?,NOW())'); $log->execute([$u['id'],'profile_updated','Profile information updated.',$_SERVER['REMOTE_ADDR']??null]);
respond(['ok'=>true,'message'=>'Profile updated successfully.','user'=>current_user()]);
