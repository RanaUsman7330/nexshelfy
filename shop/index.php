<?php
require __DIR__.'/../api/bootstrap.php';
require_once __DIR__ . '/../includes/site-shell.php';
$q = trim($_GET['q'] ?? '');
$products = [];
try {
  if ($q) {
    $stmt = db()->prepare('SELECT id,slug,name,description,price,cover_image,download_count FROM products WHERE status="active" AND (name LIKE ? OR description LIKE ?) ORDER BY id DESC');
    $like = '%'.$q.'%';
    $stmt->execute([$like, $like]);
    $products = $stmt->fetchAll();
  } else {
    $products = db()->query('SELECT id,slug,name,description,price,cover_image,download_count FROM products WHERE status="active" ORDER BY id DESC')->fetchAll();
  }
} catch (Throwable $ex) {}
function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function product_type($slug){
  $map=['personal-knowledge-base'=>'Notion','freelance-finance-kit'=>'Finance','content-calendar'=>'Marketing',
    'portfolio-system'=>'Portfolio','saas-launch-kit'=>'Launch','notion-business-os'=>'Business OS'];
  return $map[$slug] ?? 'Resource';
}
function cover_img_url($path){ return function_exists('ns_public_asset_url') ? ns_public_asset_url($path) : ''; }
$categories = ['All', 'Notion', 'Finance', 'Marketing', 'Portfolio', 'Launch', 'Business'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shop — NexShelfy</title>
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
      <a href="/shop/" class="active">Discover</a>
      <a href="/blog/">Blog</a>
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
    <div class="ns-kicker">Free Library</div>
    <h1><?= $q ? 'Search: '.e($q) : 'All Resources' ?></h1>
    <p>Browse our complete collection of free creator resources, templates and systems.</p>
  </div>

  <div class="ns-filter-bar">
    <?php foreach($categories as $cat): ?>
      <button class="ns-filter-btn <?= $cat==='All'?'active':'' ?>"><?=e($cat)?></button>
    <?php endforeach; ?>
  </div>

  <section class="ns-section">
    <div class="ns-shell">
      <div class="ns-product-grid">
        <?php foreach($products as $i=>$p): $img=cover_img_url($p['cover_image']??''); ?>
          <article class="ns-product-card ns-animate ns-animate-delay-<?=($i%5)+1?>">
            <a class="ns-product-cover <?= $img?'has-image':'' ?>" href="/shop/<?=e($p['slug'])?>/">
              <?php if($img): ?><img src="<?=e($img)?>" alt="<?=e($p['name'])?>"><?php endif; ?>
              <span class="ns-product-type"><?=e(product_type($p['slug']))?></span>
              <div class="ns-cover-overlay"><span class="ns-btn ns-btn-primary" style="font-size:0.8rem;padding:8px 16px;">View</span></div>
            </a>
            <div class="ns-product-body">
              <div class="ns-product-meta">
                <span class="ns-badge-free">Free</span>
                <button class="ns-product-save" data-save>♡</button>
              </div>
              <h3><a href="/shop/<?=e($p['slug'])?>/"><?=e($p['name'])?></a></h3>
              <p><?=e($p['description']?:'')?></p>
              <div class="ns-product-footer">
                <span>⬇ <?=number_format($p['download_count']??0)?> downloads</span>
                <a href="/shop/<?=e($p['slug'])?>/" class="ns-download-btn">Get →</a>
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
    <div class="ns-footer-col"><h4>Product</h4><ul><li><a href="/shop/">Templates</a></li><li><a href="/collections/">Collections</a></li><li><a href="/resources/">Resources</a></li></ul></div>
    <div class="ns-footer-col"><h4>Company</h4><ul><li><a href="/about/">About</a></li><li><a href="/contact/">Contact</a></li></ul></div>
    <div class="ns-footer-col"><h4>Legal</h4><ul><li><a href="/privacy/">Privacy</a></li><li><a href="/terms/">Terms</a></li></ul></div>
  </div>
  <div class="ns-footer-bottom"><p>© <?=date('Y')?> NexShelfy</p></div>
</footer>
<button class="ns-back-to-top" id="backToTop">↑</button>
<nav class="ns-mobile-dock"><div class="ns-mobile-dock-inner">
  <a href="/"><span>🏠</span>Home</a><a href="/shop/" class="active"><span>🔍</span>Explore</a>
  <a href="/saved/"><span>♡</span>Saved</a><a href="/dashboard/"><span>👤</span>Profile</a>
</div></nav>
</body>
</html>
