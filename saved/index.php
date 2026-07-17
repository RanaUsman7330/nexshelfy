<?php
require __DIR__.'/../api/bootstrap.php';
require_once dirname(__DIR__) . '/includes/site-shell.php';
ensure_nexshelfy_serious_schema();
$u=current_user();
$products=$resources=$blogs=[];
if($u){
  $st=db()->prepare('SELECT p.slug,p.name,p.description FROM wishlists w JOIN products p ON p.id=w.product_id WHERE w.user_id=? ORDER BY w.id DESC');$st->execute([$u['id']]);$products=$st->fetchAll();
  try{$st=db()->prepare('SELECT r.slug,r.title,r.description FROM saved_resources s JOIN content_resources r ON r.id=s.resource_id WHERE s.user_id=? ORDER BY s.id DESC');$st->execute([$u['id']]);$resources=$st->fetchAll();}catch(Throwable $e){}
  $st=db()->prepare('SELECT article_slug slug,article_title title FROM bookmarks WHERE user_id=? ORDER BY id DESC');$st->execute([$u['id']]);$blogs=$st->fetchAll();
}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Saved Items · NexShelfy</title><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="/assets/content-platform.css?v=20260711-unified-admin-theme-v4"><link rel="stylesheet" href="/assets/nexshelfy-app.css?v=20260711-unified-admin-theme-v4"><link rel="stylesheet" href="/assets/nexshelfy-shop-fixes.css?v=20260717-final-ui-2"></head>
<body class="cp-page ns-unified-page ns-saved-page"><?php ns_site_header(''); ?>
<main class="cp-shell"><section class="ns-split-hero"><div class="ns-split-copy"><div class="cp-kicker">Your shelf</div><h1>Saved products, resources and articles.</h1><p><?= $u ? 'Your saved items are synced to your account.' : 'Sign in to sync saved items across browsers. Guest saves are shown from this browser.' ?></p></div><aside class="ns-product-feature-mockup"><b><?=count($products)+count($resources)+count($blogs)?></b><p>Saved on your account</p><div class="ns-card-pills"><span>Products</span><span>Resources</span><span>Blogs</span></div></aside></section>
<?php if($u):?><section class="ns-blog-section"><div class="ns-section-title"><span>Account Saved Items</span><h2>Synced shelf</h2></div><div class="ns-saved-grid"><?php foreach($products as $p):?><article class="ns-saved-card"><span>Free Product</span><h2><?=e($p['name'])?></h2><p><?=e($p['description'])?></p><a class="ns-mini-action primary" href="/shop/<?=e($p['slug'])?>/">Open</a></article><?php endforeach;?><?php foreach($resources as $r):?><article class="ns-saved-card"><span>Free Resource</span><h2><?=e($r['title'])?></h2><p><?=e($r['description'])?></p><a class="ns-mini-action primary" href="/resources/#<?=e($r['slug'])?>">Open</a></article><?php endforeach;?><?php foreach($blogs as $b):?><article class="ns-saved-card"><span>Blog</span><h2><?=e($b['title']?:$b['slug'])?></h2><a class="ns-mini-action primary" href="/blog/<?=e($b['slug'])?>/">Open</a></article><?php endforeach;?><?php if(!$products&&!$resources&&!$blogs):?><div class="cp-empty">No saved account items yet.</div><?php endif;?></div></section><?php endif;?>
<section class="ns-blog-section"><div class="ns-section-title"><span>Browser Saved Items</span><h2>Saved in this browser</h2></div><div class="ns-saved-grid" data-saved-page></div></section></main><script src="/assets/nexshelfy-app.js?v=20260711-unified-admin-theme-v4"></script></body></html>
