<?php
require dirname(__DIR__).'/api/bootstrap.php';
try { ensure_user_dashboard_schema(); } catch (Throwable $e) {}
$u=current_user();
if(!$u){ header('Location: /?auth=login&redirect=/dashboard/'); exit; }
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Dashboard · NexShelfy</title><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="/dashboard/assets/dashboard.css?v=20260715-shared-polish"></head>
<body>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
<a class="brand" href="/"><span>N</span><b>NexShelfy</b></a>
<div class="user-mini"><div class="avatar" id="sideAvatar">N</div><div><strong id="sideName"><?=htmlspecialchars($u['name'])?></strong><small><?=htmlspecialchars($u['email'])?></small></div></div>
<nav id="nav">
<button class="active" data-view="overview">Overview</button><button data-view="resources">Saved Resources</button><button data-view="wishlist">Saved Products</button><button data-view="bookmarks">Saved Articles</button><button data-view="notifications">Notifications <em id="notifBadge">0</em></button><button data-view="activity">Activity</button><button data-view="messages">Messages</button><button data-view="profile">Profile</button><button data-view="settings">Security</button>
</nav>
<div class="side-bottom"><a href="/shop/">Browse shop</a><button id="logoutBtn">Sign out</button></div>
</aside>
<main class="main">
<header class="topbar"><button class="menu" id="menuBtn" aria-label="Open menu">☰</button><div><p>Customer workspace</p><h1 id="pageTitle">Overview</h1></div><a class="visit" href="/">View website ↗</a></header>
<div id="alert" class="alert" hidden></div>
<section id="loading" class="loading"><div></div><div></div><div></div></section>
<section id="content" hidden>
<div class="view active" data-view-panel="overview">
<div class="welcome"><div><span class="eyebrow">Welcome back</span><h2 id="welcomeName">Hello</h2><p>Manage your saved products, free resources and reading shelf from one calm workspace.</p></div><a class="primary" href="/resources/">Explore resources</a></div>
<div class="stats"><article><span>Saved resources</span><strong id="statResources">0</strong></article><article><span>Saved products</span><strong id="statWishlist">0</strong></article><article><span>Saved articles</span><strong id="statBookmarks">0</strong></article><article><span>Activity</span><strong id="statActivity">0</strong></article></div>
<div class="two-col"><article class="card"><div class="card-head"><div><span class="eyebrow">Library</span><h3>Saved resources</h3></div><button data-jump="resources">View all</button></div><div id="recentResources"></div></article><article class="card"><div class="card-head"><div><span class="eyebrow">Saved</span><h3>Products</h3></div><button data-jump="wishlist">View all</button></div><div id="recentWishlist"></div></article></div>
</div>
<div class="view" data-view-panel="resources"><div class="section-head"><div><span class="eyebrow">Your free library</span><h2>Saved resources</h2></div><a class="primary" href="/resources/">Browse resources</a></div><div id="resourcesGrid" class="grid-cards"></div></div>
<div class="view" data-view-panel="cart"><div class="section-head"><div><span class="eyebrow">Shopping cart</span><h2>Your cart</h2></div><a class="primary" href="/checkout/">Checkout</a></div><div id="cartGrid" class="grid-cards"></div><div class="cart-total card"><span>Cart total</span><strong id="dashboardCartTotal">AED 0.00</strong></div></div>
<div class="view" data-view-panel="orders"><div class="section-head"><div><span class="eyebrow">Purchase history</span><h2>Your orders</h2></div></div><div class="card table-wrap"><table><thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Details</th></tr></thead><tbody id="ordersBody"></tbody></table></div></div>
<div class="view" data-view-panel="downloads"><div class="section-head"><div><span class="eyebrow">Your library</span><h2>Downloads</h2></div></div><div id="downloadsGrid" class="grid-cards"></div></div>
<div class="view" data-view-panel="wishlist"><div class="section-head"><div><span class="eyebrow">Saved products</span><h2>Wishlist</h2></div></div><div id="wishlistGrid" class="grid-cards"></div></div>
<div class="view" data-view-panel="bookmarks"><div class="section-head"><div><span class="eyebrow">Reading shelf</span><h2>Bookmarks</h2></div></div><div id="bookmarksList" class="card list"></div></div>
<div class="view" data-view-panel="notifications"><div class="section-head"><div><span class="eyebrow">Updates</span><h2>Notifications</h2></div><button class="secondary" id="markAllRead">Mark all read</button></div><div id="notificationsList" class="card list"></div></div>
<div class="view" data-view-panel="activity"><div class="section-head"><div><span class="eyebrow">Account timeline</span><h2>Recent activity</h2></div></div><div id="activityList" class="card list"></div></div>
<div class="view" data-view-panel="messages"><div class="section-head"><div><span class="eyebrow">Customer support</span><h2>Messages</h2></div></div><div class="two-col"><form id="messageForm" class="card form-card"><label>Subject<input name="subject" required maxlength="160" placeholder="How can we help?"></label><label>Message<textarea name="message" required minlength="10" maxlength="3000" rows="7" placeholder="Write your inquiry..."></textarea></label><button class="primary" type="submit">Send message</button></form><article class="card"><div class="card-head"><div><span class="eyebrow">History</span><h3>Your inquiries</h3></div></div><div id="messagesList" class="list"></div></article></div></div>
<div class="view" data-view-panel="profile"><div class="section-head"><div><span class="eyebrow">Personal details</span><h2>Edit profile</h2></div></div><form id="profileForm" class="card form-card"><label>Full name<input name="name" required maxlength="100"></label><label>Email<input name="email" type="email" disabled></label><label>Phone<input name="phone" maxlength="30" placeholder="+971..."></label><label>Bio<textarea name="bio" maxlength="500" rows="5" placeholder="Tell us a little about yourself"></textarea></label><button class="primary" type="submit">Save profile</button></form></div>
<div class="view" data-view-panel="settings"><div class="section-head"><div><span class="eyebrow">Account security</span><h2>Change password</h2></div></div><form id="passwordForm" class="card form-card"><label>Current password<input name="current_password" type="password" required></label><label>New password<input name="new_password" type="password" minlength="8" required></label><label>Confirm new password<input name="confirm_password" type="password" minlength="8" required></label><button class="primary" type="submit">Update password</button></form></div>
</section>
</main></div>
<script>window.NS_CSRF=<?=json_encode(csrf_token())?>;</script><script src="/dashboard/assets/dashboard.js?v=20260715-shared-polish"></script></body></html>
