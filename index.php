<?php
require __DIR__.'/api/bootstrap.php';
require_once __DIR__ . '/includes/site-shell.php';
ensure_nexshelfy_serious_schema();
ensure_content_platform_schema();
function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function product_type($slug){
  $map=['personal-knowledge-base'=>'Notion','freelance-finance-kit'=>'Finance','content-calendar'=>'Marketing',
    'portfolio-system'=>'Portfolio','saas-launch-kit'=>'Launch','notion-business-os'=>'Business OS'];
  return $map[$slug] ?? 'Resource';
}
function product_icon($slug){
  $map=['personal-knowledge-base'=>'PK','freelance-finance-kit'=>'FK','content-calendar'=>'CC',
    'portfolio-system'=>'PS','saas-launch-kit'=>'SL','notion-business-os'=>'BO'];
  return $map[$slug] ?? 'NS';
}
function product_tone($i){$tones=['indigo','amber','emerald','rose','slate','violet'];return $tones[$i%count($tones)];}
function cover_img_url($path){ return function_exists('ns_public_asset_url') ? ns_public_asset_url($path) : ''; }
function card_cover_style($path, string $fallback=''){ $style = function_exists('ns_cover_style') ? ns_cover_style($path,'linear-gradient(145deg,rgba(10,10,15,.10),rgba(10,10,15,.48))') : ''; return $style ?: $fallback; }
function fake_downloads($p,$i){$base=(int)($p['download_count']??0);return max($base, [12481,9380,8214,7260,6892,5780][$i] ?? (4200+$i*317));}
function fake_likes($downloads){return max(280, (int)floor($downloads/18));}

// Product gradients for placeholder images
function product_gradient($slug){
  $gradients=[
    'notion-business-os'=>'linear-gradient(135deg,#6366f1,#8b5cf6)',
    'saas-launch-kit'=>'linear-gradient(135deg,#06b6d4,#3b82f6)',
    'portfolio-system'=>'linear-gradient(135deg,#f59e0b,#ef4444)',
    'content-calendar'=>'linear-gradient(135deg,#10b981,#06b6d4)',
    'freelance-finance-kit'=>'linear-gradient(135deg,#8b5cf6,#ec4899)',
    'personal-knowledge-base'=>'linear-gradient(135deg,#3b82f6,#6366f1)'
  ];
  return $gradients[$slug] ?? 'linear-gradient(135deg,#6366f1,#8b5cf6)';
}

$products=[];$posts=[];
try{
  $products=db()->query('SELECT id,slug,name,description,cover_image,download_count FROM products WHERE status="active" AND slug != "test" ORDER BY id DESC LIMIT 6')->fetchAll();
}catch(Throwable $ex){
  try{$products=db()->query('SELECT id,slug,name,description FROM products WHERE status="active" AND slug != "test" ORDER BY id DESC LIMIT 6')->fetchAll();}catch(Throwable $ignored){}
}
try{
  $posts=db()->query('SELECT slug,title,excerpt,category,cover_image,cover_color,reading_time,published_at FROM blog_posts WHERE status="published" ORDER BY published_at DESC,id DESC LIMIT 6')->fetchAll();
}catch(Throwable $ex){}
if(!$products){
  $products=[
    ['slug'=>'notion-business-os','name'=>'Notion Business OS','description'=>'A complete workspace for running a modern digital business.'],
    ['slug'=>'saas-launch-kit','name'=>'SaaS Launch Kit','description'=>'Launch planning, positioning and execution templates.'],
    ['slug'=>'portfolio-system','name'=>'Portfolio System','description'=>'A structured system for building a high-converting portfolio.'],
    ['slug'=>'content-calendar','name'=>'Content Calendar','description'=>'Plan and publish content with a reusable editorial workflow.'],
    ['slug'=>'freelance-finance-kit','name'=>'Freelance Finance Kit','description'=>'Track income, expenses, invoices and financial goals.'],
    ['slug'=>'personal-knowledge-base','name'=>'Personal Knowledge Base','description'=>'Organize notes, learning and ideas in one connected system.'],
  ];
}
if(!$posts){
  $posts=[
    ['slug'=>'build-a-digital-system','title'=>'Build a digital system that works while you sleep','excerpt'=>'A practical framework for turning scattered knowledge into a calm, profitable digital shelf.','category'=>'Business','reading_time'=>'8 min','published_at'=>'2026-06-24'],
    ['slug'=>'quiet-design','title'=>'Why quiet design converts better','excerpt'=>'How restraint, rhythm and useful hierarchy create trust without shouting.','category'=>'Design','reading_time'=>'6 min','published_at'=>'2026-06-18'],
    ['slug'=>'creator-operating-system','title'=>'The creator operating system','excerpt'=>'A lightweight workflow for publishing consistently without burning out.','category'=>'Productivity','reading_time'=>'10 min','published_at'=>'2026-06-11'],
  ];
}
$featured=$products[0] ?? null;
$tags=['Notion','Canva','Marketing','Business','Finance','AI','Productivity'];
$collections=[['📚','Productivity','Knowledge bases, planners and weekly systems.','42 resources'],['🎨','Design','Covers, portfolios and visual templates.','28 resources'],['🚀','Launch','SaaS, offer and product launch kits.','19 resources'],['💰','Business','Finance, client and founder systems.','31 resources']];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NexShelfy — Free Creator Resources Worth Downloading</title>
<meta name="description" content="Free creator resources, templates, systems and digital tools for modern creators, founders and freelancers.">
<link rel="canonical" href="https://nexshelfy.com/">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Load unified fixes LAST to override everything -->
<link rel="stylesheet" href="/assets/content-platform.css?v=20260718">
<link rel="stylesheet" href="/assets/nexshelfy-app.css?v=20260718">
<link rel="stylesheet" href="/assets/nexshelfy-shop-fixes.css?v=20260718">
<link rel="stylesheet" href="/assets/css/nexshelfy-v2.css?v=20260718">
<link rel="stylesheet" href="/assets/nexshelfy-unified-fixes.css?v=20260718">
<script src="/assets/js/nexshelfy-v2.js?v=20260718" defer></script>
<script src="/assets/nexshelfy-app.js?v=20260718" defer></script>
</head>
<body class="cp-page ns-home-page ns-home-premium">

<!-- UNIFIED HEADER -->
<header class="cp-site-header ns-header">
  <div class="cp-header-inner ns-header-inner">
    <a href="/" class="cp-brand ns-brand" aria-label="NexShelfy Home">
      <span>NS</span>
      <b>NexShelfy</b>
    </a>
    <nav class="cp-nav ns-nav" aria-label="Main navigation">
      <a href="/" class="active">Discover</a>
      <a href="/shop/">Free Products</a>
      <a href="/resources/">Free Resources</a>
      <a href="/collections/">Collections</a>
      <a href="/blog/">Blog</a>
      <a href="/creators/">Creators</a>
      <a href="/contact/">Contact</a>
    </nav>
    <div class="cp-header-actions ns-header-actions ns-header-inline" data-ns-header-actions>
      <a href="/shop/" class="ns-header-btn ns-free-link">Browse Library</a>
      <button type="button" class="ns-header-btn ns-icon-action" data-mobile-saved aria-label="Saved items">
        <span class="ns-action-icon">♡</span>
        <span class="ns-action-count">0</span>
      </button>
      <button type="button" class="ns-header-btn ns-account" data-auth-trigger>Sign in</button>
    </div>
  </div>
</header>

<main>
<!-- HERO SECTION -->
<section class="ns-hero">
  <div class="ns-hero-bg"></div>
  <div class="ns-shell ns-hero-grid">
    <div class="ns-hero-copy">
      <div class="ns-kicker">Curated for ambitious creators</div>
      <h1>Free creator resources.<br><span>Actually worth downloading.</span></h1>
      <p class="ns-hero-desc">Premium-feeling templates, systems and tools for modern creators, founders and freelancers — without the price tag.</p>
      <form class="ns-hero-search" action="/shop/" method="get">
        <input name="q" type="search" placeholder="Search templates, systems, tools..." aria-label="Search templates">
        <button type="submit"><i class="fas fa-search"></i></button>
      </form>
      <div class="ns-hero-tags">
        <?php foreach($tags as $tag): ?>
          <a href="/shop/?q=<?=rawurlencode($tag)?>"><?=e($tag)?></a>
        <?php endforeach; ?>
      </div>
      <div class="ns-hero-actions">
        <a class="ns-btn ns-btn-primary" href="/shop/">Explore Free Library <i class="fas fa-arrow-right"></i></a>
        <a class="ns-btn ns-btn-secondary" href="/contact/">Contact Us</a>
      </div>
    </div>
    <div class="ns-hero-visual">
      <div class="ns-hero-card">
        <div class="ns-hero-card-header">
          <span>Trending Now</span>
          <b><i class="fas fa-fire"></i> Live</b>
        </div>
        <?php foreach(array_slice($products,0,4) as $i=>$p): $img=cover_img_url($p['cover_image']??''); ?>
          <div class="ns-hero-stat-row">
            <?php if($img): ?>
              <img src="<?=e($img)?>" alt="<?=e($p['name'])?>">
            <?php else: ?>
              <span class="stat-icon" style="background:<?=e(product_gradient($p['slug']))?>"><?=e(product_icon($p['slug']))?></span>
            <?php endif; ?>
            <div>
              <b><?=e($p['name'])?></b>
              <small><?=e(product_type($p['slug']))?> · <?=number_format(fake_downloads($p,$i))?> downloads</small>
            </div>
            <span class="stat-num">+<i class="fas fa-arrow-trend-up"></i><?=rand(12,89)?>%</span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="ns-float-badge ns-float-badge-1"><i class="fas fa-star"></i> 4.9 Rating</div>
      <div class="ns-float-badge ns-float-badge-2"><i class="fas fa-globe"></i> 80+ Countries</div>
      <div class="ns-float-badge ns-float-badge-3"><i class="fas fa-chart-line"></i> 48k+ Downloads</div>
    </div>
  </div>
</section>

<!-- STATS STRIP -->
<section class="ns-stats-strip ns-animate">
  <div class="ns-stat-item">
    <i><i class="fas fa-download"></i></i>
    <b data-counter="48000" data-suffix="+">48,000+</b>
    <span>Downloads across resources</span>
  </div>
  <div class="ns-stat-item">
    <i><i class="fas fa-heart"></i></i>
    <b data-counter="1800" data-suffix="+">1,800+</b>
    <span>Saved by visitors</span>
  </div>
  <div class="ns-stat-item">
    <i><i class="fas fa-envelope"></i></i>
    <b data-counter="12000" data-suffix="+">12,000+</b>
    <span>Newsletter creators</span>
  </div>
  <div class="ns-stat-item">
    <i><i class="fas fa-star"></i></i>
    <b>4.9</b>
    <span>Average resource rating</span>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="ns-section" id="recently-added">
  <div class="ns-shell">
    <div class="ns-section-heading ns-animate ns-animate-delay-1">
      <div class="heading-left">
        <div class="ns-kicker">Free Library</div>
        <h2>Featured Resources</h2>
        <p>Premium-feeling downloads without pricing confusion, duplicate buttons or noisy sales tricks.</p>
      </div>
      <a href="/shop/" class="ns-link-arrow">Browse all <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="ns-product-grid">
      <?php foreach(array_slice($products,0,6) as $i=>$p): $slug=(string)$p['slug']; $downloads=fake_downloads($p,$i); $img=cover_img_url($p['cover_image']??''); ?>
        <article class="ns-product-card ns-animate ns-animate-delay-<?=($i%5)+1?>">
          <a class="ns-product-cover <?= $img?'has-image':'no-image' ?>" href="/shop/<?=e($slug)?>/" style="<?= $img?'':('background:'.e(product_gradient($slug))) ?>">
            <?php if($img): ?><img class="ns-cover-photo" src="<?=e($img)?>" alt="<?=e($p['name'])?>"><?php endif; ?>
            <span class="ns-product-type"><?=e(product_type($slug))?></span>
            <div class="ns-cover-overlay">
              <span class="ns-btn ns-btn-primary" style="font-size:0.8rem;padding:8px 16px;"><i class="fas fa-eye"></i> Quick Preview</span>
            </div>
          </a>
          <div class="ns-product-body">
            <div class="ns-product-meta">
              <span class="ns-badge-free">Free · PDF/ZIP</span>
              <button class="ns-product-save" data-ns-product-save data-slug="<?=e($slug)?>" data-title="<?=e($p['name'])?>" aria-label="Save product"><i class="far fa-heart"></i></button>
            </div>
            <h3><a href="/shop/<?=e($slug)?>/"><?=e($p['name'])?></a></h3>
            <p><?=e($p['description']?:'A curated NexShelfy product.')?></p>
            <div class="ns-product-footer">
              <span><i class="fas fa-download"></i> <?=number_format($downloads)?> downloads</span>
              <a href="/shop/<?=e($slug)?>/" class="ns-download-btn">Get <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- COLLECTIONS -->
<section class="ns-section" style="background:linear-gradient(180deg,var(--ns-surface-2),var(--ns-surface));">
  <div class="ns-shell">
    <div class="ns-section-heading ns-animate">
      <div class="heading-left">
        <div class="ns-kicker">Browse by Topic</div>
        <h2>Collections</h2>
        <p>Hand-picked bundles organized by what creators actually need.</p>
      </div>
      <a href="/collections/" class="ns-link-arrow">View all <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="ns-collection-grid">
      <?php foreach($collections as $i=>$c): ?>
        <a href="/collections/?cat=<?=rawurlencode($c[1])?>" class="ns-collection-card ns-animate ns-animate-delay-<?=($i%4)+1?>">
          <div class="ns-collection-icon"><?=e($c[0])?></div>
          <h3><?=e($c[1])?></h3>
          <p><?=e($c[2])?></p>
          <span class="ns-count"><?=e($c[3])?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- BLOG SECTION -->
<section class="ns-section">
  <div class="ns-shell">
    <div class="ns-section-heading ns-animate">
      <div class="heading-left">
        <div class="ns-kicker">From the Blog</div>
        <h2>Latest Articles</h2>
        <p>Practical guides, frameworks and insights for the modern creator.</p>
      </div>
      <a href="/blog/" class="ns-link-arrow">Read all <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="ns-blog-grid">
      <?php foreach(array_slice($posts,0,3) as $i=>$post): 
        $img=cover_img_url($post['cover_image']??'');
        $color=$post['cover_color']??'#6366f1';
        $date=date('M j, Y', strtotime($post['published_at']??'now'));
      ?>
        <article class="ns-blog-card ns-animate ns-animate-delay-<?=($i%3)+1?>">
          <a href="/blog/<?=e($post['slug'])?>/" class="ns-blog-cover">
            <?php if($img): ?>
              <img src="<?=e($img)?>" alt="<?=e($post['title'])?>">
            <?php else: ?>
              <div style="width:100%;height:100%;background:<?=e($color)?>;"></div>
            <?php endif; ?>
            <span class="ns-blog-category"><?=e($post['category']??'Article')?></span>
          </a>
          <div class="ns-blog-body">
            <h3><a href="/blog/<?=e($post['slug'])?>/"><?=e($post['title'])?></a></h3>
            <p><?=e($post['excerpt']?:'')?></p>
            <div class="ns-blog-meta">
              <span><i class="far fa-calendar"></i> <?=e($date)?></span>
              <span><i class="far fa-clock"></i> <?=e($post['reading_time']??'5 min')?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- NEWSLETTER -->
<section class="ns-newsletter">
  <div class="ns-newsletter-bg"></div>
  <div class="ns-newsletter-inner ns-animate">
    <div class="ns-kicker">Stay Updated</div>
    <h2>Join 12,000+ creators</h2>
    <p>Get free resources, templates and creator tips delivered to your inbox. No spam, unsubscribe anytime.</p>
    <form class="ns-newsletter-form" action="/api/newsletter.php" method="post">
      <input type="email" name="email" placeholder="Enter your email" required aria-label="Email">
      <button type="submit">Subscribe <i class="fas fa-paper-plane"></i></button>
    </form>
    <p class="ns-newsletter-note"><i class="fas fa-lock"></i> We respect your privacy. No spam ever.</p>
  </div>
</section>
</main>

<!-- BACK TO TOP -->
<button class="ns-back-to-top" id="backToTop" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>

<!-- UNIFIED FOOTER -->
<footer class="cp-site-footer ns-footer ns-premium-footer">
  <div class="cp-footer-grid ns-footer-grid ns-footer-grid-three">
    <div class="ns-footer-intro">
      <a href="/" class="cp-footer-brand ns-footer-brand">NexShelfy</a>
      <p>Free creator resources, practical articles and digital systems — made to help you do useful work faster.</p>
      <div class="ns-footer-social">
        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
    <div class="ns-footer-col">
      <b>Explore</b>
      <a href="/shop/">Free Products</a>
      <a href="/resources/">Free Resources</a>
      <a href="/collections/">Collections</a>
      <a href="/blog/">Articles</a>
    </div>
    <div class="ns-footer-col">
      <b>Company</b>
      <a href="/creators/">Creators</a>
      <a href="/creators/">Become a Creator</a>
      <a href="/contact/">Contact</a>
      <a href="/privacy/">Privacy</a>
    </div>
    <div class="ns-footer-col">
      <b>Legal</b>
      <a href="/terms/">Terms</a>
      <a href="/terms/">Licenses</a>
      <a href="/privacy/">Privacy Policy</a>
    </div>
  </div>
  <div class="cp-footer-bottom ns-footer-bottom">
    <p>© <?=date('Y')?> NexShelfy. All rights reserved.</p>
    <p>Designed by Mr Usman · <a href="https://musman.com">musman.com</a></p>
  </div>
</footer>

</body>
</html>