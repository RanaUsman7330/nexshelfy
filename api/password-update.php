<?php
require __DIR__.'/bootstrap.php'; ensure_user_dashboard_schema(); require_method('POST'); $d=json_input(); verify_csrf($d); $u=require_user();
$current=(string)($d['current_password']??''); $new=(string)($d['new_password']??''); $confirm=(string)($d['confirm_password']??'');
if(strlen($new)<8) fail('New password must be at least 8 characters.'); if($new!==$confirm) fail('New password confirmation does not match.');
$st=db()->prepare('SELECT password_hash FROM users WHERE id=?'); $st->execute([$u['id']]); $row=$st->fetch();
if(!$row||!password_verify($current,$row['password_hash'])) fail('Current password is incorrect.',401);
$st=db()->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?'); $st->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]);
$log=db()->prepare('INSERT INTO user_activity_logs(user_id,action,description,ip_address,created_at) VALUES(?,?,?,?,NOW())'); $log->execute([$u['id'],'password_changed','Account password changed.',$_SERVER['REMOTE_ADDR']??null]);
respond(['ok'=>true,'message'=>'Password changed successfully.']);
