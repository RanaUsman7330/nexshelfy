<?php require __DIR__.'/bootstrap.php'; $u=require_user();
$st=db()->prepare('SELECT order_number,status,payment_status,currency,total,created_at FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 25');$st->execute([$u['id']]);$orders=$st->fetchAll();
$st=db()->prepare('SELECT p.slug,p.name,p.price,p.currency,w.created_at FROM wishlists w JOIN products p ON p.id=w.product_id WHERE w.user_id=? ORDER BY w.id DESC');$st->execute([$u['id']]);$wishlist=$st->fetchAll();
$st=db()->prepare('SELECT article_slug,article_title,created_at FROM bookmarks WHERE user_id=? ORDER BY id DESC');$st->execute([$u['id']]);$bookmarks=$st->fetchAll();
respond(['ok'=>true,'user'=>$u,'orders'=>$orders,'wishlist'=>$wishlist,'bookmarks'=>$bookmarks]);
