<?php

require __DIR__ . '/bootstrap.php';

ensure_nexshelfy_serious_schema();
ensure_content_platform_schema();

rate_limit('free_download', 20, 600);

$type = clean((string)($_GET['type'] ?? 'product'), 20);
$key = clean((string)($_GET['slug'] ?? $_GET['id'] ?? ''), 120);
$email = strtolower(clean((string)($_POST['email'] ?? $_GET['email'] ?? ''), 120));
$name = clean((string)($_POST['name'] ?? $_GET['name'] ?? ''), 120);

if (!$key) {
    http_response_code(404);
    exit('Resource not found.');
}

if (!valid_email($email)) {
    // Show an inline "email required" page (still within the same request)
    // so we don't need to change the rest of the backend flow.
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');

    $self = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
    $prefilledEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $prefilledName  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="en"><head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Download free</title>
        <style>
            :root{--bg:#07070b;--card:#11111a;--muted:#a6a6b3;--stroke:#262633;--fg:#f7f7ff;--btn:#f7f7ff;--btnfg:#07070b;--radius:22px}
            *{box-sizing:border-box}
 body{margin:0;background:radial-gradient(1200px 900px at 10% 10%,rgba(93,63,211,.18),transparent),var(--bg);color:var(--fg);min-height:100vh;display:grid;place-items:center;padding:26px;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,"Apple Color Emoji","Segoe UI Emoji"}
            .shell{width:min(560px,100%);padding:28px;background:linear-gradient(180deg,rgba(255,255,255,.09),rgba(255,255,255,.02));border:1px solid var(--stroke);border-radius:var(--radius);box-shadow:0 20px 60px rgba(0,0,0,.32)}
            h1{margin:0;font-size:20px;line-height:1.2}
            p{margin:10px 0 18px;font-size:14px;line-height:1.55;color:var(--muted)}
            label{display:block;margin-bottom:8px;font-weight:650;font-size:13px}
            input[type="email"],input[type="text"]{width:100%;height:46px;border-radius:14px;border:1px solid var(--stroke);background:#0b0b12;color:var(--fg);padding:0 14px;font-weight:650;outline:none}
            input::placeholder{color:rgba(247,247,255,.55)}
            .row{display:grid;gap:12px}
            .row2{display:grid;gap:12px;grid-template-columns:1fr 1fr}
            button{margin-top:16px;width:100%;height:48px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:var(--btn);color:var(--btnfg);font-weight:800;cursor:pointer}
            .small{margin-top:10px;font-size:12px;color:var(--muted)}
            .back{margin-top:12px;font-size:12px;color:rgba(247,247,255,.8);display:inline-flex;align-items:center;gap:8px;cursor:pointer}
 @media (max-width:520px){.row2{grid-template-columns:1fr}}
        </style>
    </head><body>
    <form class="shell" method="get" action="'.$self.'">
        <h1>Download free resource</h1>
        <p>Enter your email to unlock the download. We may send occasional updates; you can unsubscribe anytime.</p>

        <div class="row2">
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com" value="'.$prefilledEmail.'">
            </div>
            <div>
                <label for="name">Name <span style="color:var(--muted);font-weight:500">(optional)</span></label>
                <input id="name" name="name" type="text" autocomplete="name" placeholder="Your name" value="'.$prefilledName.'">
            </div>
        </div>';

    foreach ($_GET as $k => $v) {
        if (in_array($k, ['email', 'name'], true)) continue;
        echo '<input type="hidden" name="'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8').'">';
    }

    echo '<button type="submit">Continue &amp; download</button>
        <div class="small">Tip: if the download doesn&apos;t start, double-check your email and try again.</div>
        <div class="back" onclick="history.back()">&larr; Back</div>
    </form>
    <script>document.getElementById("email").focus();</script>
    </body></html>';
    exit;
}

$root = realpath(dirname(__DIR__));
$title = 'NexShelfy Resource';
$filePath = null;
$downloadName = null;
$externalLink = null;
$itemId = 0;

if ($type === 'resource') {
    $st = db()->prepare('SELECT id,title,file_path,zip_path,resource_link,file_name FROM content_resources WHERE (slug=? OR id=?) AND status="active" LIMIT 1');
    $st->execute([$key, ctype_digit($key) ? (int)$key : 0]);
    $item = $st->fetch();
    if (!$item) {
        http_response_code(404);
        exit('Resource not found.');
    }

    $itemId = (int)$item['id'];
    $title = $item['title'];
    $filePath = $item['zip_path'] ?: $item['file_path'];
    $externalLink = $item['resource_link'] ?? null;
    $downloadName = $item['file_name'] ?: preg_replace('/[^a-z0-9-]+/i', '-', strtolower($title)).'.txt';
    db()->prepare('UPDATE content_resources SET download_count=download_count+1 WHERE id=?')->execute([$itemId]);
} else {
    $st = db()->prepare('SELECT id,name,file_path,zip_path,resource_link,file_name FROM products WHERE (slug=? OR id=?) AND status="active" LIMIT 1');
    $st->execute([$key, ctype_digit($key) ? (int)$key : 0]);
    $item = $st->fetch();
    if (!$item) {
        http_response_code(404);
        exit('Product not found.');
    }

    $itemId = (int)$item['id'];
    $title = $item['name'];
    $filePath = $item['zip_path'] ?: $item['file_path'];
    $externalLink = $item['resource_link'] ?? null;
    $downloadName = $item['file_name'] ?: preg_replace('/[^a-z0-9-]+/i', '-', strtolower($title)).'.txt';
    if (column_exists('products', 'download_count')) {
        db()->prepare('UPDATE products SET download_count=download_count+1 WHERE id=?')->execute([$itemId]);
    }
}
db()->prepare('INSERT INTO download_leads(email,name,item_key,item_type,item_title,ip_address,created_at) VALUES(?,?,?,?,?,?,NOW())')->execute([$email, $name?:null, $key, $type, $title ?? null, client_ip()]);
site_log('download',$type,$itemId,['email'=>$email,'key'=>$key,'title'=>$title]);
try {
    db()->prepare('INSERT INTO newsletter_subscribers(email,status,subscribed_at) VALUES(?, "active",NOW()) ON DUPLICATE KEY UPDATE status="active", subscribed_at=NOW()')->execute([$email]);
} catch (Throwable $e) {
}

if ($externalLink && preg_match('~^https?://~i', $externalLink)) {
    header('Location: '.$externalLink);
    exit;
}

if ($filePath) {
    $candidate = realpath($root.'/'.ltrim((string)$filePath, DIRECTORY_SEPARATOR));
    if ($candidate && str_starts_with($candidate, $root.DIRECTORY_SEPARATOR) && is_file($candidate)) {
        $downloadName = basename($candidate);
        header('Content-Type: application/octet-stream');
        header('Content-Length: '.filesize($candidate));
        header('Content-Disposition: attachment; filename="'.addslashes($downloadName).'"');
        readfile($candidate);
        exit;
    }
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Download unavailable</title><style>body{margin:0;padding:32px;background:#07070b;color:#f7f7ff;font:16px system-ui;display:grid;place-items:center;min-height:100vh}.card{max-width:560px;padding:28px;border:1px solid #30303b;border-radius:22px;background:#11111a}.card p{color:#b7b7c4;line-height:1.6}a{color:#fff}</style></head><body><main class="card"><h1>Download unavailable</h1><p>This item is published, but its file or download link has not been configured yet.</p><a href="/">Return to NexShelfy</a></main></body></html>';
exit;
