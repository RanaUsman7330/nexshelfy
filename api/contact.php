<?php require __DIR__.'/bootstrap.php'; require_method('POST'); $d=json_input(); verify_csrf($d);
$name=clean((string)($d['name']??''),100); $email=strtolower(clean((string)($d['email']??''),190)); $subject=clean((string)($d['subject']??'Website enquiry'),160); $message=clean((string)($d['message']??''),5000);
if(mb_strlen($name)<2||!valid_email($email)||mb_strlen($message)<10) fail('Please complete all fields correctly.');
$st=db()->prepare('INSERT INTO contact_messages(name,email,subject,message,status,ip_address,created_at) VALUES(?,?,?,?,"new",?,NOW())'); $st->execute([$name,$email,$subject,$message,$_SERVER['REMOTE_ADDR']??null]); respond(['ok'=>true,'message'=>'Message received. We will respond soon.']);
