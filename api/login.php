<?php
require __DIR__.'/bootstrap.php';
ensure_user_dashboard_schema();
require_method('POST');
$d=json_input();
verify_csrf($d);
$email=strtolower(clean((string)($d['email']??''),190));
$password=(string)($d['password']??'');
$st=db()->prepare('SELECT id,password_hash,status,role FROM users WHERE email=? LIMIT 1');
$st->execute([$email]);
$u=$st->fetch();
if(!$u || $u['status']!=='active' || !password_verify($password,$u['password_hash'])) {
    fail('Incorrect customer email or password.',401);
}
$_SESSION['user_id']=(int)$u['id'];
session_regenerate_id(true);
try {
    db()->prepare('UPDATE users SET last_login_at=NOW(),updated_at=NOW() WHERE id=?')->execute([(int)$u['id']]);
    db()->prepare('INSERT INTO user_activity_logs(user_id,action,description,ip_address,created_at) VALUES(?,?,?,?,NOW())')->execute([(int)$u['id'],'login','Signed in to the customer account.',$_SERVER['REMOTE_ADDR']??null]);
} catch (Throwable $ignored) {}
respond(['ok'=>true,'message'=>'Signed in successfully.','user'=>current_user()]);
