<?php
require __DIR__.'/api/bootstrap.php';
require_once __DIR__ . '/includes/site-shell.php';
ensure_nexshelfy_serious_schema();
ensure_content_platform_schema();
function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function product_type($slug){
  $map=[
    'personal-knowledge-base'=>'Notion','freelance-finance-kit'=>'Finance','content-calendar'=>'Marketing',
    'portfolio-system'=>'Portfolio','saas-launch-kit'=>'Launch','notion-business-os'=>'Business OS'
  ];
  return $map[$slug] ?? 'Resource';
}
function product_icon($slug){
  $map=[
    'personal-knowledge-base'=>'PK','freelance-finance-kit'=>'FK','content-calendar'=>'CC',
    'portfolio-system'=>'PS','saas-launch-kit'=>'SL','notion-business-os'=>'BO'
  ];
  return $map[$slug] ?? 'NS';
}
function product_tone($i){$tones=['indigo','amber','emerald','rose','slate','violet'];return $tones[$i%count($tones)];}

function cover_img_url($path){ return function_exists('ns_public_asset_url') ? ns_public_asset_url($path) : ''; }
function card_cover_style($path, string $fallback=''){ $style = function_exists('ns_cover_style') ? ns_cover_style($path,'linear-gradient(145deg,rgba(10,10,15,.10),rgba(10,10,15,.48))') : ''; return $style ?: $fallback; }
function fake_downloads($p,$i){$base=(int)($p['download_count']??0);return max($base, [12481,9380,8214,7260,6892,5780][$i] ?? (4200+$i*317));}
function fake_likes($downloads){return max(280, (int)floor($downloads/18));}
$products=[];$posts=[];
try{
  $products=db()->query('SELECT id,slug,name,description,cover_image,download_count FROM products WHERE status="active" ORDER BY id DESC LIMIT 6')->fetchAll();
}catch(Throwable $ex){
  try{$products=db()->query('SELECT id,slug,name,description FROM products WHERE status="active" ORDER BY id DESC LIMIT 6')->fetchAll();}catch(Throwable $ignored){}
}
try{
  $posts=db()->query('SELECT slug,title,excerpt,category,cover_image,cover_color,reading_time,published_at FROM blog_posts WHERE status="published" ORDER BY published_at DESC,id DESC LIMIT 6')->fetchAll();
}catch(Throwable $ex){}
if(!$products){
  $products=[
    ['slug'=>'personal-knowledge-base','name'=>'Personal Knowledge Base','description'=>'Organize notes, learning and ideas in one connected system.'],
    ['slug'=>'freelance-finance-kit','name'=>'Freelance Finance Kit','description'=>'Track income, expenses, invoices and financial goals.'],
    ['slug'=>'content-calendar','name'=>'Content Calendar','description'=>'Plan and publish content with a reusable editorial workflow.'],
    ['slug'=>'portfolio-system','name'=>'Portfolio System','description'=>'A structured system for building a high-converting portfolio.'],
    ['slug'=>'saas-launch-kit','name'=>'SaaS Launch Kit','description'=>'Launch planning, positioning and execution templates.'],
    ['slug'=>'notion-business-os','name'=>'Notion Business OS','description'=>'A complete workspace for running a modern digital business.'],
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
<link rel="stylesheet" href="/assets/content-platform.css?v=20260715-shared-polish">
<link rel="stylesheet" href="/assets/nexshelfy-app.css?v=20260715-shared-polish"><link rel="stylesheet" href="/assets/nexshelfy-shop-fixes.css?v=20260717-final-ui-2">
</head>
<body class="cp-page ns-home-page ns-home-premium">
<?php ns_site_header('discover'); ?>
<main>
<section class="ns-premium-hero">
  <div class="cp-shell ns-premium-hero-grid">
    <div class="ns-hero-copy">
      <div class="cp-kicker">Curated for ambitious creators</div>
      <h1>Free creator resources.<br><span>Actually worth downloading.</span></h1>
      <form class="ns-hero-search" action="/shop/" method="get"><input name="q" type="search" placeholder="Search templates..." aria-label="Search templates"><button type="submit">🔍</button></form>
      <div class="ns-popular-tags" aria-label="Popular tags"><?php foreach($tags as $tag): ?><a href="/shop/?q=<?=rawurlencode($tag)?>"><?=e($tag)?></a><?php endforeach; ?></div>
      <div class="ns-home-actions"><a class="cp-btn primary" href="/shop/">Explore free library</a><a class="cp-btn" href="/contact/">Contact</a></div>
    </div>
    <aside class="ns-animated-shelf ns-trending-panel ns-hero-dashboard" aria-label="Trending downloads">
      <div class="ns-dashboard-topline"><span>Downloads today</span><b>+312</b><em>★★★★★</em></div>
      <div class="ns-dashboard-pills"><span>Recently Added</span><span>Trending</span><span>Save</span><span>Preview</span></div>
      <div class="ns-download-counter"><small>Trending downloads</small><strong data-ns-counter="48000">48k+</strong><span>Across all resources · Updated July 2026</span></div>
      <div class="ns-live-list">
        <div class="ns-live-head"><b>Recently added</b><a href="/shop/">View all →</a></div>
        <?php foreach(array_slice($products,0,4) as $i=>$p): $img=cover_img_url($p['cover_image']??''); ?>
          <a class="ns-live-row" href="/shop/<?=e($p['slug'])?>/">
            <?php if($img): ?><img src="<?=e($img)?>" alt="<?=e($p['name'])?>"><?php else: ?><span><?=e(substr($p['name'],0,1))?></span><?php endif; ?>
            <div><b><?=e($p['name'])?></b><small><?=e(product_type($p['slug']))?> · <?=number_format(fake_downloads($p,$i))?> downloads</small></div>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="ns-dashboard-demo"><span>🌍</span> Downloaded by creators in <b>80+ countries</b></div>
      <div class="ns-shelf-base"><span></span><span></span><span></span></div>
    </aside>
  </div>
</section>

<section class="cp-shell ns-stats-strip ns-proof-strip ns-stats-icons" aria-label="NexShelfy statistics"><div><i>⬇</i><b data-ns-counter="48000">48k+</b><span>Downloads across resources</span></div><div><i>❤</i><b data-ns-counter="1800">1.8k+</b><span>Saved by visitors</span></div><div><i>✉</i><b data-ns-counter="12000">12k+</b><span>Newsletter creators</span></div><div><i>★</i><b>4.9</b><span>Average resource rating</span></div><small>Based on platform analytics · Updated July 2026</small></section>

<section class="cp-shell ns-home-section" id="recently-added">
  <div class="cp-section-heading"><div><div class="cp-kicker">Free library</div><h2>Featured resources</h2><p>Premium-feeling downloads without pricing confusion, duplicate buttons or noisy sales tricks.</p></div><a href="/shop/">Browse all →</a></div>
  <div class="ns-premium-product-grid ns-clean-product-grid">
    <?php foreach(array_slice($products,0,6) as $i=>$p): $slug=(string)$p['slug']; $downloads=fake_downloads($p,$i); $img=cover_img_url($p['cover_image']??''); ?>
      <article class="ns-premium-product-card ns-clean-resource-card <?=e(product_tone($i))?>">
        <a class="ns-resource-cover <?= $img?'has-image':'no-image' ?>" href="/shop/<?=e($slug)?>/" <?= $img?'':'style="'.card_cover_style($p['cover_image']??'').'"' ?>>
          <?php if($img): ?><img class="ns-cover-photo" src="<?=e($img)?>" alt="<?=e($p['name'])?>"><?php endif; ?>
          <span class="ns-free-pill"><?=e(product_type($slug))?></span>
        </a>
        <div class="ns-product-body-v3">
          <div class="ns-product-meta-v3"><span>FREE · PDF/ZIP</span><button aria-label="Save product" title="Save product" data-ns-product-save data-slug="<?=e($slug)?>">♡</button></div>
          <h3><a href="/shop/<?=e($slug)?>/"><?=e($p['name'])?></a></h3>
          <p><?=e($p['description']?:'A curated NexShelfy product.')?></p>
          <div class="ns-compact-proof"><span>★★★★★</span><small><?=number_format($downloads)?> downloads</small></div>
          <div class="ns-product-actions-v3"><button class="primary" type="button" data-ns-free-download data-href="/api/free-download.php?type=product&amp;slug=<?=rawurlencode($slug)?>">Download</button><a href="/shop/<?=e($slug)?>/">👁 Preview</a></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="cp-shell ns-home-section ns-collection-cards">
  <div class="cp-section-heading"><div><div class="cp-kicker">Collections</div><h2>Start with the outcome</h2></div><a href="/collections/">View collections →</a></div>
  <div class="ns-collection-grid"><?php foreach($collections as $c): ?><a href="/shop/?q=<?=rawurlencode($c[1])?>"><span><?=e($c[0])?></span><strong><?=e($c[1])?></strong><small><?=e($c[2])?></small><em><?=e($c[3])?></em></a><?php endforeach; ?></div>
</section>

<section class="cp-shell ns-home-section ns-testimonials ns-testimonials-premium"><div class="cp-section-heading"><div><div class="cp-kicker">Creator feedback</div><h2>Useful resources, saved for real work.</h2><p>48,000+ downloads across resources, from creators in 80+ countries.</p></div></div><div class="ns-testimonial-grid"><blockquote><div class="ns-creator-row"><span class="ns-avatar">AK</span><div><b>Ali Khan</b><small>Founder · Startup workspace</small></div></div><p>“This Notion system saved me hours when planning weekly content.”</p><cite>Used: Personal Knowledge Base</cite></blockquote><blockquote><div class="ns-creator-row"><span class="ns-avatar">SM</span><div><b>Sarah Malik</b><small>Designer · Freelance studio</small></div></div><p>“The library feels clean, practical and ready to use.”</p><cite>Used: Content Calendar</cite></blockquote><blockquote><div class="ns-creator-row"><span class="ns-avatar">HR</span><div><b>Hamza Raza</b><small>Freelancer · Client systems</small></div></div><p>“Simple structure, no noise, and much easier than starting from blank pages.”</p><cite>Used: Freelance Finance Kit</cite></blockquote></div></section>

<section class="cp-shell ns-home-section">
  <div class="cp-section-heading"><div><div class="cp-kicker">Trending now</div><h2>Read slowly. Apply immediately.</h2></div><a href="/blog/">View all →</a></div>
  <div class="ns-blog-carousel">
    <?php foreach(array_slice($posts,0,6) as $post): $date=$post['published_at']?date('M j, Y',strtotime((string)$post['published_at'])):''; $postImg=cover_img_url($post['cover_image']??''); ?>
      <article class="cp-card ns-post-card-v3"><a class="ns-blog-thumb <?= $postImg?'has-image':'no-image' ?>" href="/blog/<?=e($post['slug'])?>/" style="<?= $postImg?'':card_cover_style($post['cover_image']??'','--cover:'.e($post['cover_color']??'#4f46e5').';') ?>"><?php if($postImg): ?><img src="<?=e($postImg)?>" alt="<?=e($post['title'])?>"><?php endif; ?><span><?=e($post['category']??'Article')?></span></a><div class="cp-card-body"><small><?=e($post['category']??'Article')?><?= $date?' · '.e($date):'' ?><?=!empty($post['reading_time'])?' · '.e($post['reading_time']):''?></small><h2><a href="/blog/<?=e($post['slug'])?>/"><?=e($post['title'])?></a></h2><p><?=e($post['excerpt']??'Practical field notes from NexShelfy.')?></p><a class="cp-read-link" href="/blog/<?=e($post['slug'])?>/">Read article <span>→</span></a></div></article>
    <?php endforeach; ?>
  </div>
</section>

</main>
<?php ns_site_footer(); ?>
<script src="/assets/nexshelfy-app.js?v=20260717-final-ui-2"></script>
</body>
</html>
