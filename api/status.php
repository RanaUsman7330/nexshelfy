<?php
require __DIR__.'/bootstrap.php';
try { db()->query('SELECT 1'); $dbReady=true; } catch(Throwable $e){ $dbReady=false; }
$user=null; try{$user=current_user();}catch(Throwable $e){}
respond(['ok'=>true,'database'=>$dbReady,'csrf'=>csrf_token(),'user'=>$user,'app'=>envv('APP_NAME','NexShelfy')]);
