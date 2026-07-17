<?php
require __DIR__.'/bootstrap.php'; ensure_user_dashboard_schema(); require_method('POST'); $d=json_input(); verify_csrf($d); $u=require_user(); $id=(int)($d['id']??0);
if($id>0){$st=db()->prepare('UPDATE user_notifications SET is_read=1 WHERE id=? AND user_id=?');$st->execute([$id,$u['id']]);}
else{$st=db()->prepare('UPDATE user_notifications SET is_read=1 WHERE user_id=?');$st->execute([$u['id']]);}
respond(['ok'=>true]);
