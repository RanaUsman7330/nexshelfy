<?php require __DIR__.'/bootstrap.php';
ensure_nexshelfy_serious_schema(); rate_limit('newsletter', 8, 600); require_method('POST'); $d=json_input(); verify_csrf($d); $email=strtolower(clean((string)($d['email']??''),190)); if(!valid_email($email)) fail('Enter a valid email address.');
$st=db()->prepare('INSERT INTO newsletter_subscribers(email,status,subscribed_at) VALUES(?,"active",NOW()) ON DUPLICATE KEY UPDATE status="active",subscribed_at=NOW()'); $st->execute([$email]); site_log('subscribe','newsletter',null,['email'=>$email]); respond(['ok'=>true,'message'=>'You are subscribed.']);
