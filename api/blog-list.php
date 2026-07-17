<?php
require __DIR__ . '/bootstrap.php';
$rows=db()->query('SELECT title,slug,excerpt,category,cover_color,cover_image,published_at FROM blog_posts WHERE status="published" ORDER BY COALESCE(published_at,created_at) DESC')->fetchAll();
respond(['ok'=>true,'posts'=>$rows,'csrf'=>csrf_token()]);
