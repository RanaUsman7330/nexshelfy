<?php
define('NEXSHELFY_ADMIN_SESSION', true);
require __DIR__.'/../api/bootstrap.php';
$_SESSION=[];
if(ini_get('session.use_cookies')){
    $p=session_get_cookie_params();
    setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']);
}
session_destroy();
header('Location: ./login.php');
exit;
