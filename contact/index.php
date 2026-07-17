<?php
require __DIR__.'/../api/bootstrap.php';
require_once __DIR__ . '/../includes/site-shell.php';
function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Contact — NexShelfy</title>
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
      <a href="/shop/">Discover</a><a href="/blog/">Blog</a><a href="/collections/">Collections</a>
      <a href="/creators/">Creators</a><a href="/contact/" class="active">Contact</a>
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
    <div class="ns-kicker">Get in Touch</div>
    <h1>Contact Us</h1>
    <p>Have a question, suggestion, or just want to say hello? We would love to hear from you.</p>
  </div>
  <section class="ns-section">
    <div class="ns-shell" style="max-width:600px;">
      <form action="/api/contact.php" method="post" class="ns-animate">
        <div class="ns-form-group">
          <label for="name">Your Name</label>
          <input type="text" id="name" name="name" placeholder="John Doe" required>
        </div>
        <div class="ns-form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="john@example.com" required>
        </div>
        <div class="ns-form-group">
          <label for="subject">Subject</label>
          <input type="text" id="subject" name="subject" placeholder="How can we help?" required>
        </div>
        <div class="ns-form-group">
          <label for="message">Message</label>
          <textarea id="message" name="message" placeholder="Tell us more about your inquiry..." required></textarea>
        </div>
        <button type="submit" class="ns-btn ns-btn-primary" style="width:100%;justify-content:center;">Send Message →</button>
      </form>
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
