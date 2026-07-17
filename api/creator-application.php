<?php
require __DIR__.'/bootstrap.php';
ensure_nexshelfy_serious_schema();
require_method('POST');
ensure_content_platform_schema();
$d=json_input(); verify_csrf($d);
$name=clean((string)($d['name']??''),120); $email=strtolower(clean((string)($d['email']??''),190));
$age=clean((string)($d['age']??''),20); $gender=clean((string)($d['gender']??''),40); $qualification=clean((string)($d['qualification']??''),190);
$reason=clean((string)($d['reason']??''),3000); $contribution=clean((string)($d['contribution']??''),3000);
if(mb_strlen($name)<2 || !valid_email($email) || mb_strlen($reason)<10 || mb_strlen($contribution)<10) fail('Please complete name, email, reason and contribution.',422);
db()->prepare('INSERT INTO creator_applications(name,email,age,gender,qualification,reason,contribution,status,ip_address,created_at) VALUES(?,?,?,?,?,?,?,"new",?,NOW())')->execute([$name,$email,$age?:null,$gender?:null,$qualification?:null,$reason,$contribution,client_ip()]);
$applicationId=(int)db()->lastInsertId();
site_log('create','creator_application',$applicationId,['name'=>$name,'email'=>$email]);
try{db()->prepare('INSERT INTO contact_messages(user_id,name,email,subject,message,status,ip_address,created_at) VALUES(NULL,?,?,"Creator application",?,"new",?,NOW())')->execute([$name,$email,"Age: $age
Gender: $gender
Qualification: $qualification

Why creator:
$reason

Contribution:
$contribution",client_ip()]);}catch(Throwable $e){}
respond(['ok'=>true,'message'=>'Your creator application has been submitted. We will review it soon.']);
