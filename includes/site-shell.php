<?php
if (!function_exists('ns_e')) { function ns_e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('ns_theme_style')) {
function ns_theme_style(): void {
    $s = function_exists('public_site_settings') ? public_site_settings() : [
        'theme_primary'=>'#2563eb','theme_secondary'=>'#06b6d4','theme_surface'=>'#f8fbff','theme_ink'=>'#0f172a'
    ];
    foreach (['theme_primary','theme_secondary','theme_surface','theme_ink'] as $key) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)($s[$key] ?? ''))) $s[$key] = ['theme_primary'=>'#2563eb','theme_secondary'=>'#06b6d4','theme_surface'=>'#f8fbff','theme_ink'=>'#0f172a'][$key];
    }
    echo '<style id="ns-server-theme">:root{--ns-accent:'.ns_e($s['theme_primary']).';--ns-accent-2:'.ns_e($s['theme_secondary']).';--ns-theme-surface:'.ns_e($s['theme_surface']).';--ns-theme-ink:'.ns_e($s['theme_ink']).';--cp-accent:'.ns_e($s['theme_primary']).';--cp-bg:'.ns_e($s['theme_surface']).';--cp-text:'.ns_e($s['theme_ink']).'}</style>';
}}

if (!function_exists('ns_public_asset_url')) {
function ns_public_asset_url(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^(https?:)?//~i', $path)) return $path;
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('~^/?(public_html/|\./)+~', '', $path);
    $path = ltrim($path, '/');
    if (preg_match('~^(storage|uploads|assets|images|media)/~i', $path)) return '/' . $path;
    if (preg_match('~\.(jpe?g|png|webp|gif|svg)$~i', $path) && strpos($path, '/') === false) {
        foreach (['storage/products/','storage/resources/','storage/blog/','uploads/products/','uploads/resources/','uploads/blog/','uploads/'] as $base) {
            if (is_file(dirname(__DIR__) . '/' . $base . $path)) return '/' . $base . $path;
        }
        return '/uploads/' . $path;
    }
    return '/' . $path;
}}
if (!function_exists('ns_cover_style')) {
function ns_cover_style(?string $path, string $overlay = 'linear-gradient(180deg,rgba(5,7,12,.08),rgba(5,7,12,.52))'): string {
    $url = ns_public_asset_url($path);
    if ($url === '') return '';
    return "background-image:" . $overlay . ",url('" . ns_e($url) . "') !important;background-size:cover !important;background-position:center !important;";
}}

if (!function_exists('ns_site_header')) {
function ns_site_header(string $active=''): void { ns_theme_style(); ?>
<header class="cp-site-header" data-active="<?=ns_e($active)?>">
  <div class="cp-shell cp-header-inner">
    <a class="cp-brand" href="/" aria-label="NexShelfy home"><span>N</span><b>NexShelfy</b></a>
    <nav class="cp-nav" aria-label="Primary navigation">
      <a class="<?= $active==='discover'?'active':'' ?>" href="/">Discover</a>
      <a class="<?= $active==='shop'?'active':'' ?>" href="/shop/">Free Products</a>
      <a class="<?= $active==='resources'?'active':'' ?>" href="/resources/">Free Resources</a>
      <a class="<?= $active==='collections'?'active':'' ?>" href="/collections/">Collections</a>
      <a class="<?= $active==='blog'?'active':'' ?>" href="/blog/">Blog</a>
      <a class="<?= $active==='creators'?'active':'' ?>" href="/creators/">Creators</a>
    </nav>
    <div class="cp-header-actions" data-ns-header-actions></div>
  </div>
</header>
<?php }
function ns_footer_newsletter(): void { ?>
<section class="cp-shell ns-newsletter ns-premium-newsletter ns-global-footer-newsletter">
  <div><span>12,000+ creators already subscribed</span><h2>One useful creator email every week.</h2><p>New templates, creator systems, AI resources and business ideas.</p><small>No spam. Unsubscribe anytime.</small></div>
  <form data-ns-newsletter><div class="ns-newsletter-line"><input type="email" name="email" placeholder="you@example.com" required><button class="ns-btn" type="submit">Join Free</button></div></form>
</section>
<?php }
function ns_site_footer(): void { ?>
<footer class="cp-site-footer ns-premium-footer">
  <?php ns_footer_newsletter(); ?>
  <div class="cp-shell cp-footer-grid ns-footer-grid-three">
    <div class="ns-footer-intro"><a class="cp-footer-brand" href="/">NexShelfy</a><p>Free creator resources, practical articles and digital systems—made to help you do useful work faster.</p></div>
    <div><b>Explore</b><a href="/shop/">Free Products</a><a href="/resources/">Free Resources</a><a href="/collections/">Collections</a><a href="/blog/">Articles</a></div>
    <div><b>Company</b><a href="/creators/">Creators</a><a href="/become-a-creator/">Become a Creator</a><a href="/contact/">Contact</a><a href="/privacy/">Privacy</a></div>
  </div>
  <div class="cp-shell cp-footer-bottom"><span>© <?=date('Y')?> NexShelfy. All rights reserved.</span><span>Designed by Mr Usman · mrusman.com</span></div>
</footer>
<?php }
}
