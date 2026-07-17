<?php
declare(strict_types=1);
ob_start();
ini_set('display_errors','0');
ini_set('log_errors','1');

function load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (!array_key_exists($key, $_ENV)) $_ENV[$key] = $value;
    }
}
load_env(dirname(__DIR__) . '/.env');
function envv(string $key, ?string $default = null): ?string { return $_ENV[$key] ?? $default; }
date_default_timezone_set(envv('APP_TIMEZONE', 'Asia/Dubai'));

$scriptPath = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$adminSessionContext = defined('NEXSHELFY_ADMIN_SESSION') || str_contains($scriptPath, '/admin/');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($adminSessionContext
        ? envv('ADMIN_SESSION_NAME', 'nexshelfy_admin_session')
        : envv('USER_SESSION_NAME', 'nexshelfy_user_session'));
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => $adminSessionContext ? '/admin' : '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = envv('DB_HOST', 'localhost');
    $port = envv('DB_PORT', '3306');
    $name = envv('DB_DATABASE', '');
    $user = envv('DB_USERNAME', '');
    $pass = envv('DB_PASSWORD', '');
    if (!$name || str_starts_with($name, 'YOUR_')) throw new RuntimeException('Database is not configured. Update .env first.');
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]);
    return $pdo;
}
function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}
function respond(array $data, int $status = 200): never {
    if (ob_get_length()) ob_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $message, int $status = 422, array $extra = []): never { respond(['ok'=>false,'message'=>$message] + $extra, $status); }
function require_method(string $method): void { if (($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) fail('Method not allowed', 405); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(array $data): void {
    $token = (string)($data['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (!$token || !hash_equals(csrf_token(), $token)) fail('Security token expired. Refresh and try again.', 419);
}
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $st = db()->prepare('SELECT id,name,email,role,created_at FROM users WHERE id=? AND status="active" LIMIT 1');
    $st->execute([(int)$_SESSION['user_id']]);
    return $st->fetch() ?: null;
}
function require_user(): array { $u=current_user(); if(!$u) fail('Please sign in first.',401); return $u; }
function clean(string $v, int $max=255): string { return mb_substr(trim($v),0,$max); }
function valid_email(string $email): bool { return (bool)filter_var($email,FILTER_VALIDATE_EMAIL); }
function product_by_slug(string $slug): ?array {
    $st=db()->prepare('SELECT id,slug,name,description,price,currency,file_path,zip_path,resource_link,status FROM products WHERE slug=? LIMIT 1');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}
function client_ip(): ?string { return isset($_SERVER['REMOTE_ADDR']) ? clean((string)$_SERVER['REMOTE_ADDR'],45) : null; }
function site_log(string $action, string $entity, ?int $entityId = null, array $meta = []): void {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) NOT NULL,
            entity_id BIGINT UNSIGNED NULL,
            metadata LONGTEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            KEY idx_admin_created (admin_id, created_at),
            KEY idx_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        db()->prepare('INSERT INTO admin_activity_logs(admin_id,action,entity_type,entity_id,metadata,ip_address,created_at) VALUES(NULL,?,?,?,?,?,NOW())')
            ->execute([clean($action,80),clean($entity,80),$entityId,json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),client_ip()]);
    } catch (Throwable $e) {}
}

/** Public-safe visual settings used by every portal. */
function public_site_settings(): array {
    $defaults = [
        'theme_preset' => 'aurora',
        'theme_primary' => '#4f46e5',
        'theme_secondary' => '#06b6d4',
        'theme_surface' => '#f8fbff',
        'theme_ink' => '#0f172a',
    ];
    try {
        if (!table_exists('app_settings')) return $defaults;
        $keys = array_keys($defaults);
        $marks = implode(',', array_fill(0, count($keys), '?'));
        $st = db()->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ($marks)");
        $st->execute($keys);
        foreach ($st->fetchAll() as $row) {
            if (isset($defaults[$row['setting_key']]) && trim((string)$row['setting_value']) !== '') {
                $defaults[$row['setting_key']] = (string)$row['setting_value'];
            }
        }
    } catch (Throwable $e) {}
    return $defaults;
}

function rate_limit(string $action, int $limit = 20, int $windowSeconds = 300): void {
    try {
        $pdo = db();
        $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          action_key VARCHAR(120) NOT NULL,
          ip_address VARCHAR(45) NOT NULL,
          attempts INT UNSIGNED NOT NULL DEFAULT 1,
          first_attempt_at DATETIME NOT NULL,
          last_attempt_at DATETIME NOT NULL,
          UNIQUE KEY uniq_rate_action_ip(action_key,ip_address),
          KEY idx_rate_last(last_attempt_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $ip = client_ip() ?: 'unknown';
        $st = $pdo->prepare('SELECT attempts, first_attempt_at FROM rate_limits WHERE action_key=? AND ip_address=? LIMIT 1');
        $st->execute([$action,$ip]);
        $row = $st->fetch();
        $now = time();
        if ($row) {
            $first = strtotime((string)$row['first_attempt_at']) ?: $now;
            if (($now - $first) > $windowSeconds) {
                $pdo->prepare('UPDATE rate_limits SET attempts=1, first_attempt_at=NOW(), last_attempt_at=NOW() WHERE action_key=? AND ip_address=?')->execute([$action,$ip]);
                return;
            }
            if ((int)$row['attempts'] >= $limit) fail('Too many attempts. Please try again after a few minutes.', 429);
            $pdo->prepare('UPDATE rate_limits SET attempts=attempts+1, last_attempt_at=NOW() WHERE action_key=? AND ip_address=?')->execute([$action,$ip]);
            return;
        }
        $pdo->prepare('INSERT INTO rate_limits(action_key,ip_address,attempts,first_attempt_at,last_attempt_at) VALUES(?,?,1,NOW(),NOW())')->execute([$action,$ip]);
    } catch (Throwable $e) {
        // Never block the main action if rate-limit storage is unavailable.
    }
}

function column_exists(string $table, string $column): bool {
    $st = db()->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute([$table, $column]);
    return (int)$st->fetchColumn() > 0;
}
function table_exists(string $table): bool {
    $st = db()->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $st->execute([$table]);
    return (int)$st->fetchColumn() > 0;
}
function ensure_user_dashboard_schema(): void {
    $pdo = db();
    $columns = [
        'phone' => 'VARCHAR(30) NULL AFTER email',
        'avatar_url' => 'VARCHAR(500) NULL AFTER phone',
        'bio' => 'VARCHAR(500) NULL AFTER avatar_url',
        'last_login_at' => 'DATETIME NULL AFTER status',
    ];
    foreach ($columns as $name => $definition) {
        if (!column_exists('users', $name)) $pdo->exec("ALTER TABLE users ADD COLUMN `$name` $definition");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,title VARCHAR(180) NOT NULL,message TEXT NOT NULL,
      type ENUM('info','success','warning','order','download') NOT NULL DEFAULT 'info',is_read TINYINT(1) NOT NULL DEFAULT 0,
      action_url VARCHAR(500) NULL,created_at DATETIME NOT NULL,KEY idx_user_read_created(user_id,is_read,created_at),
      CONSTRAINT fk_notification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_activity_logs (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,action VARCHAR(100) NOT NULL,
      description VARCHAR(500) NULL,ip_address VARCHAR(45) NULL,created_at DATETIME NOT NULL,KEY idx_user_activity(user_id,created_at),
      CONSTRAINT fk_user_activity_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function ensure_commerce_schema(): void {
    ensure_user_dashboard_schema();
    $pdo=db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_cart_items (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,
      quantity INT UNSIGNED NOT NULL DEFAULT 1,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,
      UNIQUE KEY uniq_user_product(user_id,product_id),KEY idx_cart_user(user_id),
      CONSTRAINT fk_cart_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
      CONSTRAINT fk_cart_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $columns=[
      'customer_phone'=>'VARCHAR(30) NULL AFTER customer_email',
      'address_line1'=>'VARCHAR(255) NULL AFTER customer_phone',
      'address_line2'=>'VARCHAR(255) NULL AFTER address_line1',
      'city'=>'VARCHAR(120) NULL AFTER address_line2',
      'emirate'=>'VARCHAR(120) NULL AFTER city',
      'postal_code'=>'VARCHAR(30) NULL AFTER emirate',
      'order_notes'=>'TEXT NULL AFTER postal_code',
      'payment_method'=>'VARCHAR(40) NOT NULL DEFAULT "cod" AFTER order_notes',
    ];
    foreach($columns as $name=>$definition){ if(!column_exists('orders',$name)) $pdo->exec("ALTER TABLE orders ADD COLUMN `$name` $definition"); }
}

function ensure_content_platform_schema(): void {
    $pdo=db();
    if (!column_exists('products','is_free')) $pdo->exec('ALTER TABLE products ADD COLUMN is_free TINYINT(1) NOT NULL DEFAULT 1 AFTER price');
    if (!column_exists('products','download_count')) $pdo->exec('ALTER TABLE products ADD COLUMN download_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER file_path');
    if (!column_exists('products','zip_path')) $pdo->exec('ALTER TABLE products ADD COLUMN zip_path VARCHAR(500) NULL AFTER file_path');
    if (!column_exists('products','resource_link')) $pdo->exec('ALTER TABLE products ADD COLUMN resource_link VARCHAR(500) NULL AFTER zip_path');
    if (!column_exists('products','cover_image')) $pdo->exec('ALTER TABLE products ADD COLUMN cover_image VARCHAR(500) NULL AFTER resource_link');
    $pdo->exec("UPDATE products SET price=0,is_free=1");
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_resources (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(255) NOT NULL,slug VARCHAR(180) NOT NULL UNIQUE,description TEXT NULL,file_path VARCHAR(500) NULL,zip_path VARCHAR(500) NULL,file_name VARCHAR(255) NULL,resource_link VARCHAR(500) NULL,resource_type VARCHAR(80) NULL,status ENUM('active','draft') NOT NULL DEFAULT 'active',download_count INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (table_exists('content_resources') && !column_exists('content_resources','zip_path')) $pdo->exec('ALTER TABLE content_resources ADD COLUMN zip_path VARCHAR(500) NULL AFTER file_path');
    if (table_exists('content_resources') && !column_exists('content_resources','resource_link')) $pdo->exec('ALTER TABLE content_resources ADD COLUMN resource_link VARCHAR(500) NULL AFTER file_name');
    if (table_exists('content_resources') && !column_exists('content_resources','resource_type')) $pdo->exec('ALTER TABLE content_resources ADD COLUMN resource_type VARCHAR(80) NULL AFTER resource_link');
    if (table_exists('content_resources') && !column_exists('content_resources','cover_image')) $pdo->exec('ALTER TABLE content_resources ADD COLUMN cover_image VARCHAR(500) NULL AFTER resource_type');
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,author_id BIGINT UNSIGNED NULL,title VARCHAR(255) NOT NULL,slug VARCHAR(180) NOT NULL UNIQUE,excerpt TEXT NULL,content LONGTEXT NOT NULL,category VARCHAR(120) NULL,cover_color VARCHAR(30) NOT NULL DEFAULT '#6366F1',cover_image VARCHAR(500) NULL,status ENUM('published','draft') NOT NULL DEFAULT 'draft',published_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,KEY idx_blog_status_date(status,published_at),CONSTRAINT fk_blog_author FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (table_exists('blog_posts') && !column_exists('blog_posts','cover_image')) $pdo->exec('ALTER TABLE blog_posts ADD COLUMN cover_image VARCHAR(500) NULL AFTER cover_color');
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_post_resources (blog_post_id BIGINT UNSIGNED NOT NULL,resource_id BIGINT UNSIGNED NOT NULL,sort_order INT NOT NULL DEFAULT 0,PRIMARY KEY(blog_post_id,resource_id),CONSTRAINT fk_bpr_post FOREIGN KEY(blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,CONSTRAINT fk_bpr_resource FOREIGN KEY(resource_id) REFERENCES content_resources(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_likes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NULL,visitor_key VARCHAR(80) NULL,content_type ENUM('product','blog') NOT NULL,content_key VARCHAR(180) NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_user_like(user_id,content_type,content_key),UNIQUE KEY uniq_visitor_like(visitor_key,content_type,content_key),KEY idx_content_likes(content_type,content_key),CONSTRAINT fk_like_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_comments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,blog_post_id BIGINT UNSIGNED NOT NULL,user_id BIGINT UNSIGNED NULL,name VARCHAR(100) NOT NULL,email VARCHAR(190) NULL,comment TEXT NOT NULL,status ENUM('pending','approved','spam') NOT NULL DEFAULT 'approved',created_at DATETIME NOT NULL,KEY idx_comments_post(blog_post_id,status,created_at),CONSTRAINT fk_comment_post FOREIGN KEY(blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,CONSTRAINT fk_comment_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS download_leads (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,email VARCHAR(190) NOT NULL,name VARCHAR(120) NULL,item_type VARCHAR(30) NOT NULL,item_key VARCHAR(180) NOT NULL,item_title VARCHAR(255) NULL,ip_address VARCHAR(45) NULL,created_at DATETIME NOT NULL,KEY idx_email_created(email,created_at),KEY idx_item(item_type,item_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS creator_applications (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL,age VARCHAR(20) NULL,gender VARCHAR(40) NULL,qualification VARCHAR(190) NULL,reason TEXT NULL,contribution TEXT NULL,status ENUM('new','reviewed','approved','rejected') NOT NULL DEFAULT 'new',ip_address VARCHAR(45) NULL,created_at DATETIME NOT NULL,updated_at DATETIME NULL,KEY idx_creator_status(status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_categories (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,name VARCHAR(80) NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_user_category(user_id,name),CONSTRAINT fk_saved_category_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_category_items (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,category_id BIGINT UNSIGNED NOT NULL,item_type ENUM('product','blog') NOT NULL,item_key VARCHAR(180) NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_category_item(category_id,item_type,item_key),CONSTRAINT fk_saved_item_category FOREIGN KEY(category_id) REFERENCES saved_categories(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (table_exists('saved_category_items')) {
        try { $pdo->exec("ALTER TABLE saved_category_items MODIFY item_type ENUM('product','resource','blog') NOT NULL"); } catch (Throwable $e) {}
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_resources (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,resource_id BIGINT UNSIGNED NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_user_resource(user_id,resource_id),KEY idx_saved_resource_user(user_id),CONSTRAINT fk_saved_resource_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,CONSTRAINT fk_saved_resource_resource FOREIGN KEY(resource_id) REFERENCES content_resources(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function log_user_activity(int $userId,string $action,string $description=''): void {
    try { ensure_user_dashboard_schema(); db()->prepare('INSERT INTO user_activity_logs(user_id,action,description,ip_address,created_at) VALUES(?,?,?,?,NOW())')->execute([$userId,clean($action,100),clean($description,500),client_ip()]); } catch(Throwable $e) {}
}
function notify_user(int $userId,string $title,string $message,string $type='info',?string $url=null): void {
    try { ensure_user_dashboard_schema(); db()->prepare('INSERT INTO user_notifications(user_id,title,message,type,is_read,action_url,created_at) VALUES(?,?,?,?,0,?,NOW())')->execute([$userId,clean($title,180),clean($message,2000),clean($type,20),$url?clean($url,500):null]); } catch(Throwable $e) {}
}
function get_user_cart(int $userId): array {
    ensure_commerce_schema();
    $st=db()->prepare('SELECT p.id,p.slug,p.name,p.description,p.price,p.currency,c.quantity AS qty,c.updated_at FROM user_cart_items c JOIN products p ON p.id=c.product_id WHERE c.user_id=? AND p.status="active" ORDER BY c.id DESC');
    $st->execute([$userId]);
    return $st->fetchAll();
}


function ensure_nexshelfy_serious_schema(): void {
    ensure_content_platform_schema();
    $pdo=db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS wishlists (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_user_product(user_id,product_id),KEY idx_user(user_id),CONSTRAINT fk_wishlist_user_safe FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,CONSTRAINT fk_wishlist_product_safe FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookmarks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,article_slug VARCHAR(180) NOT NULL,article_title VARCHAR(255) NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_user_article(user_id,article_slug),KEY idx_user(user_id),CONSTRAINT fk_bookmark_user_safe FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_resources (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,resource_id BIGINT UNSIGNED NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_user_resource(user_id,resource_id),KEY idx_saved_resource_user(user_id),CONSTRAINT fk_saved_resource_user_safe FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,CONSTRAINT fk_saved_resource_resource_safe FOREIGN KEY(resource_id) REFERENCES content_resources(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS download_leads (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,email VARCHAR(190) NOT NULL,name VARCHAR(120) NULL,item_type VARCHAR(40) NOT NULL,item_key VARCHAR(180) NULL,item_title VARCHAR(255) NULL,ip_address VARCHAR(45) NULL,created_at DATETIME NOT NULL,KEY idx_email(email),KEY idx_created(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,email VARCHAR(190) NOT NULL UNIQUE,status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',subscribed_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (table_exists('blog_posts') && !column_exists('blog_posts','cover_image')) $pdo->exec('ALTER TABLE blog_posts ADD COLUMN cover_image VARCHAR(500) NULL AFTER cover_color');
    if (table_exists('products') && !column_exists('products','zip_path')) $pdo->exec('ALTER TABLE products ADD COLUMN zip_path VARCHAR(500) NULL AFTER file_path');
    if (table_exists('products') && !column_exists('products','resource_link')) $pdo->exec('ALTER TABLE products ADD COLUMN resource_link VARCHAR(500) NULL AFTER zip_path');
    if (table_exists('content_resources') && !column_exists('content_resources','zip_path')) $pdo->exec('ALTER TABLE content_resources ADD COLUMN zip_path VARCHAR(500) NULL AFTER file_path');
    $pdo->exec("UPDATE products SET price=0,is_free=1 WHERE price<>0 OR is_free<>1");
}

set_exception_handler(function(Throwable $e){
    $debug = envv('APP_DEBUG','false') === 'true';
    $message = $debug ? $e->getMessage() : 'A server error occurred. Please check database configuration.';
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $isApi = str_contains($script, '/api/');
    if ($isApi) fail($message, 500);
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Application error</title></head><body style="font-family:system-ui;background:#f7f7f8;color:#18181b;padding:40px"><main style="max-width:760px;margin:auto;background:#fff;border:1px solid #e4e4e7;border-radius:18px;padding:28px"><h1 style="margin-top:0">Application error</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></main></body></html>';
    exit;
});
