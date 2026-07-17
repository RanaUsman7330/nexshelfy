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
<title>Get Started — NexShelfy</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/content-platform.css?v=20260715">
<link rel="stylesheet" href="/assets/nexshelfy-app.css?v=20260715">
<link rel="stylesheet" href="/assets/css/nexshelfy-v2.css?v=2.0">
<script src="/assets/js/nexshelfy-v2.js?v=2.0" defer></script>
</head>
<body class="cp-page">
<div class="ns-auth-page">
  <div class="ns-auth-card ns-animate">
    <a href="/" class="ns-brand"><span class="ns-brand-icon">NS</span>NexShelfy</a>
    <h2>Create Account</h2>
    <p class="ns-auth-subtitle">Join thousands of creators getting free resources.</p>
    <form action="/api/register.php" method="post">
      <div class="ns-form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="John Doe" required>
      </div>
      <div class="ns-form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="john@example.com" required>
      </div>
      <div class="ns-form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Min 8 characters" required>
      </div>
      <button type="submit" class="ns-btn ns-btn-primary">Create Account →</button>
    </form>
    <div class="ns-auth-footer">
      Already have an account? <a href="/account/">Sign in</a>
    </div>
  </div>
</div>
</body>
</html>
