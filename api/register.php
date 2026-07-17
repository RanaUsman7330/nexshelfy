<?php require __DIR__.'/bootstrap.php'; require_method('POST'); $d=json_input(); verify_csrf($d);
$name=clean((string)($d['name']??''),100); $email=strtolower(clean((string)($d['email']??''),190)); $password=(string)($d['password']??'');
$captchaA=(int)($d['captcha_a']??-1); $captchaB=(int)($d['captcha_b']??-1); $captchaAnswer=(int)($d['captcha_answer']??-999999);
if($captchaA < 0 || $captchaB < 0 || $captchaAnswer !== ($captchaA+$captchaB)) fail('Captcha answer is incorrect.',422);
if(mb_strlen($name)<2) fail('Please enter your full name.'); if(!valid_email($email)) fail('Enter a valid email address.'); if(strlen($password)<8) fail('Password must be at least 8 characters.');
$st=db()->prepare('SELECT id FROM users WHERE email=?'); $st->execute([$email]); if($st->fetch()) fail('An account with this email already exists.',409);
$st=db()->prepare('INSERT INTO users(name,email,password_hash,role,status,created_at,updated_at) VALUES(?,?,?,"customer","active",NOW(),NOW())');
$st->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]); $_SESSION['user_id']=(int)db()->lastInsertId(); site_log('create','user',(int)$_SESSION['user_id'],['email'=>$email]); session_regenerate_id(true);
respond(['ok'=>true,'message'=>'Account created successfully.','user'=>current_user()]);
