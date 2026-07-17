<?php
require __DIR__.'/../api/bootstrap.php';
require_once __DIR__ . '/../includes/site-shell.php';
$posts = [];
try {
  $posts = db()->query('SELECT slug,title,excerpt,category,cover_image,cover_color,reading_time,published_at FROM blog_posts WHERE status="published" ORDER BY published_at DESC')->fetchAll();
} catch (Throwable $ex) {}
function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function cover_img_url($path){ return function_exists('ns_public_asset_url') ? ns_public_asset_url($path) : ''; }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blog — NexShelfy</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/content-platform.css?v=20260715">
<link rel="stylesheet" href="/assets/nexshelfy-app.css?v=20260715">
<link rel="stylesheet" href="/assets/css/nexshelfy-v2.css?v=2.0">
<script src="/assets/js/nexshelfy-v2.js?v=2.0" defer></script>
</head>
<body class="cp-page">
<header class="ns-header" id="mainHeader">
  <div class="ns-header-inner">
    <a href="/" class="ns-brand"><span class="ns-brand-icon">NS</span>NexShelfy</a>
    <nav class="ns-nav">
      <a href="/shop/">Discover</a>
      <a href="/blog/" class="active">Blog</a>
      <a href="/collections/">Collections</a>
      <a href="/creators/">Creators</a>
      <a href="/contact/">Contact</a>
    </nav>
    <div class="ns-header-actions">
      <button class="ns-theme-toggle" id="themeToggle">🌙</button>
      <a href="/dashboard/" class="ns-header-btn">Dashboard</a>
      <a href="/signup/" class="ns-header-btn primary">Get Started</a>
    </div>
    <button class="ns-mobile-menu-btn" id="mobileMenuBtn"><span></span><span></span><span></span></button>
  </div>
</header>
<nav class="ns-mobile-nav" id="mobileNav">
  <a href="/shop/">Discover</a><a href="/blog/">Blog</a><a href="/collections/">Collections</a>
  <a href="/creators/">Creators</a><a href="/contact/">Contact</a><a href="/dashboard/">Dashboard</a>
</nav>

<main>
  <div class="ns-page-header">
    <div class="ns-kicker">From the Blog</div>
    <h1>Creator Insights</h1>
    <p>Practical guides, frameworks and insights for the modern creator.</p>
  </div>
  <section class="ns-section">
    <div class="ns-shell">
      <div class="ns-blog-grid">
        <?php foreach($posts as $i=>$post): 
          $img=cover_img_url($post['cover_image']??'');
          $color=$post['cover_color']??'#6366f1';
          $date=date('M j, Y', strtotime($post['published_at']??'now'));
        ?>
          <article class="ns-blog-card ns-animate ns-animate-delay-<?=($i%3)+1?>">
            <a href="/blog/<?=e($post['slug'])?>/" class="ns-blog-cover">
              <?php if($img): ?><img src="<?=e($img)?>" alt="<?=e($post['title'])?>"><?php else: ?>
              <div style="width:100%;height:100%;background:<?=e($color)?>;"></div><?php endif; ?>
              <span class="ns-blog-category"><?=e($post['category']??'Article')?></span>
            </a>
            <div class="ns-blog-body">
              <h3><a href="/blog/<?=e($post['slug'])?>/"><?=e($post['title'])?></a></h3>
              <p><?=e($post['excerpt']?:'')?></p>
              <div class="ns-blog-meta">
                <span>📅 <?=e($date)?></span>
                <span>⏱ <?=e($post['reading_time']??'5 min')?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<footer class="ns-footer">
  <div class="ns-footer-grid">
    <div class="ns-footer-brand">
      <a href="/" class="ns-brand"><span class="ns-brand-icon">NS</span>NexShelfy</a>
      <p>Free creator resources for modern creators.</p>
    </div>
    <div class="ns-footer-col"><h4>Product</h4><ul><li><a href="/shop/">Templates</a></li><li><a href="/blog/">Blog</a></li></ul></div>
    <div class="ns-footer-col"><h4>Company</h4><ul><li><a href="/about/">About</a></li><li><a href="/contact/">Contact</a></li></ul></div>
  </div>
  <div class="ns-footer-bottom"><p>© <?=date('Y')?> NexShelfy</p></div>
</footer>
<button class="ns-back-to-top" id="backToTop">↑</button>
<nav class="ns-mobile-dock"><div class="ns-mobile-dock-inner">
  <a href="/"><span>🏠</span>Home</a><a href="/shop/"><span>🔍</span>Explore</a>
  <a href="/saved/"><span>♡</span>Saved</a><a href="/dashboard/"><span>👤</span>Profile</a>
</div></nav>
</body>
</html>
