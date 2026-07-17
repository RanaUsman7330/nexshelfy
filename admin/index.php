<?php
require __DIR__ . '/../api/bootstrap.php';

function admin_user(): ?array {
    $u = current_user();
    return ($u && ($u['role'] ?? '') === 'admin') ? $u : null;
}
function require_admin_page(): array {
    $u = admin_user();
    if (!$u) {
        header('Location: ./login.php');
        exit;
    }
    return $u;
}
function esc(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function admin_asset_url(?string $path): string { $path=trim((string)$path); if($path==='') return ''; if(preg_match('~^(https?:)?//~i',$path)) return $path; $path=preg_replace('~^/?(public_html/|\./)+~','',$path); return '/' . ltrim($path,'/'); }
function redirect_admin(string $view = 'dashboard', string $notice = ''): never {
    $url = './?view=' . rawurlencode($view);
    if ($notice !== '') $url .= '&notice=' . rawurlencode($notice);
    header('Location: ' . $url);
    exit;
}
function admin_log(int $adminId, string $action, string $entity, ?int $entityId = null, array $meta = []): void {
    try {
        $st = db()->prepare('INSERT INTO admin_activity_logs(admin_id,action,entity_type,entity_id,metadata,ip_address,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $st->execute([$adminId,$action,$entity,$entityId,json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {}
}

$admin = require_admin_page();

/**
 * Keep the admin panel self-healing on shared hosting. The database user used
 * by Hostinger normally has CREATE TABLE permission, so missing admin-only
 * tables are created automatically after an authenticated admin signs in.
 */
function ensure_admin_schema(): void {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_activity_logs (
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(120) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $seed = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value, updated_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key)");
    foreach ([
        'site_name' => 'NexShelfy',
        'support_email' => 'enquiry@mrusman.com',
        'currency' => 'AED',
        'maintenance_mode' => '0',
        'theme_preset' => 'aurora',
        'theme_primary' => '#4f46e5',
        'theme_secondary' => '#06b6d4',
        'theme_surface' => '#f8fbff',
        'theme_ink' => '#0f172a',
    ] as $key => $value) {
        $seed->execute([$key, $value]);
    }
}

try {
    ensure_admin_schema();
    ensure_commerce_schema();
    ensure_content_platform_schema();
    ensure_nexshelfy_serious_schema();
} catch (Throwable $e) {
    http_response_code(500);
    $message = envv('APP_DEBUG', 'false') === 'true'
        ? $e->getMessage()
        : 'Admin database tables could not be prepared. Import database/migrations/admin-panel.sql in phpMyAdmin.';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NexShelfy Admin Setup Error</title><link rel="stylesheet" href="./assets/admin.css?v=20260711-unified-admin-theme-v4"></head><body><main style="max-width:760px;margin:80px auto;padding:24px"><section class="panel"><h1>Admin database setup required</h1><p>' . esc($message) . '</p><p>Import <code>database/migrations/admin-panel.sql</code> once, then refresh this page.</p></section></main></body></html>';
    exit;
}
$view = preg_replace('/[^a-z_-]/', '', $_GET['view'] ?? 'dashboard');
$allowed = ['dashboard','users','products','blogs','resources','comments','download_leads','creator_apps','messages','subscribers','orders','activity','settings'];
if (!in_array($view, $allowed, true)) $view = 'dashboard';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $d = $_POST;
    verify_csrf($d);
    $action = $d['action'] ?? '';
    try {
        if ($action === 'user_save') {
            $id=(int)($d['id']??0);$name=clean((string)($d['name']??''),100);$email=strtolower(clean((string)($d['email']??''),190));$password=(string)($d['password']??'');$role=($d['role']??'customer')==='admin'?'admin':'customer';$status=($d['status']??'active')==='blocked'?'blocked':'active';
            if(mb_strlen($name)<2||!valid_email($email)) throw new RuntimeException('Name and valid email are required.');
            if($id){
                $du=db()->prepare('SELECT id FROM users WHERE email=? AND id<>?');$du->execute([$email,$id]);if($du->fetch()) throw new RuntimeException('This email is already used by another user.');
                if($password){db()->prepare('UPDATE users SET name=?,email=?,password_hash=?,role=?,status=?,updated_at=NOW() WHERE id=?')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$status,$id]);}
                else{db()->prepare('UPDATE users SET name=?,email=?,role=?,status=?,updated_at=NOW() WHERE id=?')->execute([$name,$email,$role,$status,$id]);}
                admin_log((int)$admin['id'],'update','user',$id,['email'=>$email]);
            } else {
                if(strlen($password)<8) throw new RuntimeException('Password must be at least 8 characters for new users.');
                $du=db()->prepare('SELECT id FROM users WHERE email=?');$du->execute([$email]);if($du->fetch()) throw new RuntimeException('This email already exists.');
                db()->prepare('INSERT INTO users(name,email,password_hash,role,status,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$status]);$id=(int)db()->lastInsertId();admin_log((int)$admin['id'],'create','user',$id,['email'=>$email]);
            }
            redirect_admin('users','User saved.');
        }
        if ($action === 'user_status') {
            $id=(int)($d['id']??0); $status=($d['status']??'active')==='blocked'?'blocked':'active';
            if ($id === (int)$admin['id'] && $status === 'blocked') throw new RuntimeException('You cannot block your own admin account.');
            db()->prepare('UPDATE users SET status=?,updated_at=NOW() WHERE id=?')->execute([$status,$id]);
            admin_log((int)$admin['id'],'update_status','user',$id,['status'=>$status]);
            redirect_admin('users','User status updated.');
        }
        if ($action === 'user_role') {
            $id=(int)($d['id']??0); $role=($d['role']??'customer')==='admin'?'admin':'customer';
            if ($id === (int)$admin['id'] && $role !== 'admin') throw new RuntimeException('You cannot remove your own admin role.');
            db()->prepare('UPDATE users SET role=?,updated_at=NOW() WHERE id=?')->execute([$role,$id]);
            admin_log((int)$admin['id'],'update_role','user',$id,['role'=>$role]);
            redirect_admin('users','User role updated.');
        }
        if ($action === 'user_delete') {
            $id=(int)($d['id']??0);
            if ($id < 1) throw new RuntimeException('User not found.');
            if ($id === (int)$admin['id']) throw new RuntimeException('You cannot delete your own admin account.');
            $st=db()->prepare('SELECT email FROM users WHERE id=? LIMIT 1');$st->execute([$id]);$target=$st->fetch();
            if(!$target) throw new RuntimeException('User not found.');
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            admin_log((int)$admin['id'],'delete','user',$id,['email'=>$target['email']??'']);
            redirect_admin('users','User deleted.');
        }
        if ($action === 'product_save') {
            $id=(int)($d['id']??0); $slug=clean((string)($d['slug']??''),160); $name=clean((string)($d['name']??''),255);
            $description=trim((string)($d['description']??'')); $price=0.0; $currency='AED'; $cover=clean((string)($d['cover_image']??''),500);
            $status=($d['status']??'active')==='draft'?'draft':'active'; $file=clean((string)($d['file_path']??''),500); $zip=clean((string)($d['zip_path']??''),500); $link=clean((string)($d['resource_link']??''),500);
            if(!empty($_FILES['product_file']['name']) && is_uploaded_file($_FILES['product_file']['tmp_name'])){ $dir=dirname(__DIR__).'/storage/products'; if(!is_dir($dir))mkdir($dir,0755,true); $safe=preg_replace('/[^A-Za-z0-9._-]/','-',basename($_FILES['product_file']['name'])); $dest=$dir.'/'.time().'-'.$safe; if(!move_uploaded_file($_FILES['product_file']['tmp_name'],$dest)) throw new RuntimeException('Product file upload failed.'); $file='storage/products/'.basename($dest); }
            if(!empty($_FILES['product_zip']['name']) && is_uploaded_file($_FILES['product_zip']['tmp_name'])){ $dir=dirname(__DIR__).'/storage/products'; if(!is_dir($dir))mkdir($dir,0755,true); $safe=preg_replace('/[^A-Za-z0-9._-]/','-',basename($_FILES['product_zip']['name'])); $dest=$dir.'/'.time().'-'.$safe; if(!move_uploaded_file($_FILES['product_zip']['tmp_name'],$dest)) throw new RuntimeException('Product ZIP upload failed.'); $zip='storage/products/'.basename($dest); }
            if(!empty($_FILES['product_cover']['name']) && is_uploaded_file($_FILES['product_cover']['tmp_name'])){ $dir=dirname(__DIR__).'/storage/products'; if(!is_dir($dir))mkdir($dir,0755,true); $safe=preg_replace('/[^A-Za-z0-9._-]/','-',basename($_FILES['product_cover']['name'])); $dest=$dir.'/'.time().'-'.$safe; if(!move_uploaded_file($_FILES['product_cover']['tmp_name'],$dest)) throw new RuntimeException('Product cover upload failed.'); $cover='storage/products/'.basename($dest); }
            if (!$slug || !$name || $price < 0) throw new RuntimeException('Product name, slug and valid price are required.');
            if ($id && !$cover) { $old=db()->prepare('SELECT cover_image FROM products WHERE id=?'); $old->execute([$id]); $cover=(string)($old->fetchColumn() ?: ''); }
            if ($status === 'active' && !$cover) throw new RuntimeException('A cover image is required before an active product can be published.');
            if ($id) {
                $st=db()->prepare('UPDATE products SET slug=?,name=?,description=?,price=?,currency=?,file_path=?,zip_path=?,resource_link=?,cover_image=?,status=?,is_free=1,updated_at=NOW() WHERE id=?');
                $st->execute([$slug,$name,$description,$price,$currency,$file ?: null,$zip ?: null,$link ?: null,$cover ?: null,$status,$id]);
                admin_log((int)$admin['id'],'update','product',$id,['name'=>$name]);
            } else {
                $st=db()->prepare('INSERT INTO products(slug,name,description,price,currency,file_path,zip_path,resource_link,cover_image,status,is_free,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
                $st->execute([$slug,$name,$description,$price,$currency,$file ?: null,$zip ?: null,$link ?: null,$cover ?: null,$status,1]);
                $id=(int)db()->lastInsertId(); admin_log((int)$admin['id'],'create','product',$id,['name'=>$name]);
            }
            redirect_admin('products','Product saved.');
        }
        if ($action === 'product_delete') {
            $id=(int)($d['id']??0);
            $used=db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id=?'); $used->execute([$id]);
            if((int)$used->fetchColumn()>0) throw new RuntimeException('This product is attached to an order and cannot be deleted. Set it to draft instead.');
            db()->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
            admin_log((int)$admin['id'],'delete','product',$id);
            redirect_admin('products','Product deleted.');
        }
        if ($action === 'blog_save') {
            $id=(int)($d['id']??0);$title=clean((string)($d['title']??''),255);$slug=clean((string)($d['slug']??''),180);$excerpt=trim((string)($d['excerpt']??''));$content=trim((string)($d['content']??''));$category=clean((string)($d['category']??''),120);$color=clean((string)($d['cover_color']??'#6366F1'),30);$cover=clean((string)($d['cover_image']??''),500);$status=($d['status']??'draft')==='published'?'published':'draft';
            if(!empty($_FILES['cover_upload']['name']) && is_uploaded_file($_FILES['cover_upload']['tmp_name'])){ $dir=dirname(__DIR__).'/storage/blog'; if(!is_dir($dir))mkdir($dir,0755,true); $safe=preg_replace('/[^A-Za-z0-9._-]/','-',basename($_FILES['cover_upload']['name'])); $dest=$dir.'/'.time().'-'.$safe; if(!move_uploaded_file($_FILES['cover_upload']['tmp_name'],$dest)) throw new RuntimeException('Cover image upload failed.'); $cover='storage/blog/'.basename($dest); }
            if(!$title||!$slug||!$content) throw new RuntimeException('Title, slug and content are required.');
            if($id && !$cover){$old=db()->prepare('SELECT cover_image FROM blog_posts WHERE id=?');$old->execute([$id]);$cover=(string)($old->fetchColumn()?:'');}
            if($status==='published' && !$cover) throw new RuntimeException('A cover image is required before a blog can be published.');
            if($id){db()->prepare('UPDATE blog_posts SET title=?,slug=?,excerpt=?,content=?,category=?,cover_color=?,cover_image=?,status=?,published_at=IF(?="published",COALESCE(published_at,NOW()),published_at),updated_at=NOW() WHERE id=?')->execute([$title,$slug,$excerpt,$content,$category,$color,$cover?:null,$status,$status,$id]);}
            else{db()->prepare('INSERT INTO blog_posts(author_id,title,slug,excerpt,content,category,cover_color,cover_image,status,published_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,IF(?="published",NOW(),NULL),NOW(),NOW())')->execute([(int)$admin['id'],$title,$slug,$excerpt,$content,$category,$color,$cover?:null,$status,$status]);$id=(int)db()->lastInsertId();}
            db()->prepare('DELETE FROM blog_post_resources WHERE blog_post_id=?')->execute([$id]);$link=db()->prepare('INSERT IGNORE INTO blog_post_resources(blog_post_id,resource_id,sort_order) VALUES(?,?,?)');foreach((array)($d['resource_ids']??[]) as $i=>$rid){if((int)$rid)$link->execute([$id,(int)$rid,(int)$i]);}
            admin_log((int)$admin['id'],'save','blog',$id,['title'=>$title]);redirect_admin('blogs','Blog saved.');
        }
        if ($action === 'blog_delete') {$id=(int)($d['id']??0);db()->prepare('DELETE FROM blog_posts WHERE id=?')->execute([$id]);admin_log((int)$admin['id'],'delete','blog',$id);redirect_admin('blogs','Blog deleted.');}
        if ($action === 'resource_save') {
            $id=(int)($d['id']??0);$title=clean((string)($d['title']??''),255);$slug=clean((string)($d['slug']??''),180);$description=trim((string)($d['description']??''));$file=clean((string)($d['file_path']??''),500);$zip=clean((string)($d['zip_path']??''),500);$fileName=clean((string)($d['file_name']??''),255);$resourceLink=clean((string)($d['resource_link']??''),500);$resourceType=clean((string)($d['resource_type']??''),80);$cover=clean((string)($d['cover_image']??''),500);$status=($d['status']??'active')==='draft'?'draft':'active';
            if(!$title||!$slug) throw new RuntimeException('Resource title and slug are required.');
            if($id && !$cover){$old=db()->prepare('SELECT cover_image FROM content_resources WHERE id=?');$old->execute([$id]);$cover=(string)($old->fetchColumn()?:'');}
            if($status==='active' && !$cover) throw new RuntimeException('A cover image is required before an active resource can be published.');
            $dir=dirname(__DIR__).'/storage/resources'; if(!is_dir($dir))mkdir($dir,0755,true);
            if(!empty($_FILES['resource_file']['name']) && is_uploaded_file($_FILES['resource_file']['tmp_name'])){$safe=preg_replace('/[^A-Za-z0-9._-]/','-',basename($_FILES['resource_file']['name']));$dest=$dir.'/'.time().'-'.$safe;if(!move_uploaded_file($_FILES['resource_file']['tmp_name'],$dest))throw new RuntimeException('File upload failed.');$file='storage/resources/'.basename($dest);$fileName=$safe;}
            if(!empty($_FILES['resource_zip']['name']) && is_uploaded_file($_FILES['resource_zip']['tmp_name'])){$safe=preg_replace('/[^A-Za-z0-9._-]/','-',basename($_FILES['resource_zip']['name']));$dest=$dir.'/'.time().'-'.$safe;if(!move_uploaded_file($_FILES['resource_zip']['tmp_name'],$dest))throw new RuntimeException('ZIP upload failed.');$zip='storage/resources/'.basename($dest);if(!$fileName)$fileName=$safe;}
            if(!empty($_FILES['resource_cover']['name']) && is_uploaded_file($_FILES['resource_cover']['tmp_name'])){$safe=preg_replace('/[^A-Za-z0-9._-]/','-',basename($_FILES['resource_cover']['name']));$dest=$dir.'/'.time().'-'.$safe;if(!move_uploaded_file($_FILES['resource_cover']['tmp_name'],$dest))throw new RuntimeException('Resource cover upload failed.');$cover='storage/resources/'.basename($dest);}
            if($id){db()->prepare('UPDATE content_resources SET title=?,slug=?,description=?,file_path=?,zip_path=?,file_name=?,resource_link=?,resource_type=?,cover_image=?,status=?,updated_at=NOW() WHERE id=?')->execute([$title,$slug,$description,$file?:null,$zip?:null,$fileName?:null,$resourceLink?:null,$resourceType?:null,$cover?:null,$status,$id]);}
            else{db()->prepare('INSERT INTO content_resources(title,slug,description,file_path,zip_path,file_name,resource_link,resource_type,cover_image,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),NOW())')->execute([$title,$slug,$description,$file?:null,$zip?:null,$fileName?:null,$resourceLink?:null,$resourceType?:null,$cover?:null,$status]);$id=(int)db()->lastInsertId();}
            admin_log((int)$admin['id'],'save','resource',$id,['title'=>$title]);redirect_admin('resources','Resource saved.');
        }
        if ($action === 'resource_delete') {$id=(int)($d['id']??0);db()->prepare('DELETE FROM content_resources WHERE id=?')->execute([$id]);admin_log((int)$admin['id'],'delete','resource',$id);redirect_admin('resources','Resource deleted.');}
        if ($action === 'comment_delete') {$id=(int)($d['id']??0);db()->prepare('DELETE FROM blog_comments WHERE id=?')->execute([$id]);admin_log((int)$admin['id'],'delete','comment',$id);redirect_admin('comments','Comment deleted.');}
        if ($action === 'comment_status') {$id=(int)($d['id']??0);$status=in_array(($d['status']??''),['pending','approved','spam'],true)?$d['status']:'pending';db()->prepare('UPDATE blog_comments SET status=? WHERE id=?')->execute([$status,$id]);admin_log((int)$admin['id'],'update_status','comment',$id,['status'=>$status]);redirect_admin('comments','Comment updated.');}
        if ($action === 'order_update') {
            $id=(int)($d['id']??0); $status=(string)($d['status']??'pending'); $payment=(string)($d['payment_status']??'unpaid');
            $statuses=['pending','processing','completed','cancelled','refunded']; $payments=['unpaid','paid','failed','refunded'];
            if (!in_array($status,$statuses,true) || !in_array($payment,$payments,true)) throw new RuntimeException('Invalid order state.');
            $before=db()->prepare('SELECT user_id,order_number,status,payment_status FROM orders WHERE id=? LIMIT 1'); $before->execute([$id]); $orderBefore=$before->fetch();
            db()->prepare('UPDATE orders SET status=?,payment_status=?,updated_at=NOW() WHERE id=?')->execute([$status,$payment,$id]);
            if ($status==='completed' && $payment==='paid') {
                $sql='INSERT IGNORE INTO downloads(user_id,order_item_id,download_count,created_at) SELECT o.user_id,oi.id,0,NOW() FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.id=?';
                db()->prepare($sql)->execute([$id]);
            }
            if($orderBefore){
                $message='Order '.$orderBefore['order_number'].' is now '.$status.'; payment is '.$payment.'.';
                notify_user((int)$orderBefore['user_id'],'Order updated',$message,'order','/nexshelfy/dashboard/?view=orders');
                log_user_activity((int)$orderBefore['user_id'],'order_updated',$message);
            }
            admin_log((int)$admin['id'],'update','order',$id,['status'=>$status,'payment_status'=>$payment]);
            redirect_admin('orders','Order updated.');
        }
        if ($action === 'message_status') {
            $id=(int)($d['id']??0); $status=(string)($d['status']??'read'); $allowedStatus=['new','read','replied','archived'];
            if (!in_array($status,$allowedStatus,true)) throw new RuntimeException('Invalid message status.');
            db()->prepare('UPDATE contact_messages SET status=? WHERE id=?')->execute([$status,$id]);
            admin_log((int)$admin['id'],'update_status','contact_message',$id,['status'=>$status]);
            redirect_admin('messages','Message updated.');
        }
        if ($action === 'subscriber_status') {
            $id=(int)($d['id']??0); $status=($d['status']??'active')==='unsubscribed'?'unsubscribed':'active';
            db()->prepare('UPDATE newsletter_subscribers SET status=? WHERE id=?')->execute([$status,$id]);
            admin_log((int)$admin['id'],'update_status','subscriber',$id,['status'=>$status]);
            redirect_admin('subscribers','Subscriber updated.');
        }
        if ($action === 'settings_save') {
            $presetPalettes = [
                'aurora'=>['#4f46e5','#06b6d4','#f8fbff','#0f172a'],
                'midnight'=>['#8b5cf6','#22d3ee','#0b1020','#f8fafc'],
                'sand'=>['#b45309','#0f766e','#fff7ed','#1f2937'],
                'mint'=>['#059669','#2563eb','#f0fdf4','#052e2b'],
                'rose'=>['#e11d48','#7c3aed','#fff1f5','#3f0719'],
                'mono'=>['#111827','#6b7280','#f9fafb','#111827'],
                'indigo'=>['#4f46e5','#7c3aed','#f8fbff','#111827'],
                'ocean'=>['#0284c7','#0f766e','#f0f9ff','#082f49'],
                'emerald'=>['#059669','#0f766e','#ecfdf5','#06281f'],
                'graphite'=>['#18181b','#71717a','#fafafa','#18181b'],
                'skyline'=>['#2563eb','#0891b2','#f8fafc','#0f172a'],
                'cream'=>['#92400e','#166534','#fffbeb','#1f2937'],
                'plum'=>['#7e22ce','#db2777','#faf5ff','#2e1065'],
            ];
            $preset = clean((string)($d['theme_preset'] ?? 'aurora'), 50);
            if ($preset !== 'custom' && isset($presetPalettes[$preset])) {
                [$d['theme_primary'],$d['theme_secondary'],$d['theme_surface'],$d['theme_ink']] = $presetPalettes[$preset];
            }
            $pairs=['site_name','support_email','currency','maintenance_mode','theme_preset','theme_primary','theme_secondary','theme_surface','theme_ink'];
            foreach(['theme_primary','theme_secondary','theme_surface','theme_ink'] as $colour){ if(!preg_match('/^#[0-9a-fA-F]{6}$/',(string)($d[$colour]??''))) throw new RuntimeException('Please choose valid global theme colours.'); }
            $st=db()->prepare('INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()');
            foreach($pairs as $key){ $st->execute([$key, clean((string)($d[$key]??''),500)]); }
            admin_log((int)$admin['id'],'update','settings',null);
            redirect_admin('settings','Settings saved.');
        }
    } catch (Throwable $e) {
        $error=$e->getMessage();
    }
}

$counts=[];
foreach([
 'users'=>'SELECT COUNT(*) FROM users',
 'products'=>'SELECT COUNT(*) FROM products',
 'messages'=>'SELECT COUNT(*) FROM contact_messages WHERE status="new"',
 'subscribers'=>'SELECT COUNT(*) FROM newsletter_subscribers WHERE status="active"'
] as $k=>$sql){ $counts[$k]=db()->query($sql)->fetchColumn(); }

$notice=$_GET['notice']??'';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NexShelfy Admin</title><link rel="stylesheet" href="./assets/admin.css?v=20260711-unified-admin-theme-v4"></head>
<body><div class="shell">
<aside class="sidebar"><a class="brand" href="./"><span>N</span><b>NexShelfy</b><small>Admin</small></a>
<nav aria-label="Admin navigation">
<div class="nav-group"><small>Workspace</small><?php foreach(['dashboard'=>['Overview','⌂'],'activity'=>['Activity','↻']] as $k=>$item): ?><a class="<?= $view===$k?'active':'' ?>" href="./?view=<?= $k ?>"><i><?=esc($item[1])?></i><span><?=esc($item[0])?></span></a><?php endforeach; ?></div>
<div class="nav-group"><small>Content</small><?php foreach(['products'=>['Free Products','▣'],'resources'=>['Resources','◆'],'blogs'=>['Blogs','✎'],'comments'=>['Comments','◌']] as $k=>$item): ?><a class="<?= $view===$k?'active':'' ?>" href="./?view=<?= $k ?>"><i><?=esc($item[1])?></i><span><?=esc($item[0])?></span></a><?php endforeach; ?></div>
<div class="nav-group"><small>Community</small><?php foreach(['users'=>['Users','◎'],'creator_apps'=>['Creator Apps','✦'],'messages'=>['Messages','✉'],'subscribers'=>['Subscribers','＋'],'download_leads'=>['Download Leads','⇩']] as $k=>$item): ?><a class="<?= $view===$k?'active':'' ?>" href="./?view=<?= $k ?>"><i><?=esc($item[1])?></i><span><?=esc($item[0])?></span></a><?php endforeach; ?></div>
<div class="nav-group"><small>Commerce & system</small><?php foreach(['orders'=>['Orders','◇'],'settings'=>['Settings','⚙']] as $k=>$item): ?><a class="<?= $view===$k?'active':'' ?>" href="./?view=<?= $k ?>"><i><?=esc($item[1])?></i><span><?=esc($item[0])?></span></a><?php endforeach; ?></div>
</nav>
<div class="sidefoot"><div class="admin-avatar"><?=esc(strtoupper(substr($admin['name']??'A',0,1)))?></div><div><strong><?= esc($admin['name']) ?></strong><span><?= esc($admin['email']) ?></span></div><a class="signout" href="./logout.php" title="Sign out">↗</a></div></aside>
<main><header class="top"><button class="menu" id="menuBtn">☰</button><div><p>Control center</p><h1><?= esc(ucfirst($view)) ?></h1></div><a class="site" href="../" target="_blank">View website ↗</a></header>
<?php if($notice):?><div class="alert success"><?=esc($notice)?></div><?php endif;?><?php if(!empty($error)):?><div class="alert error"><?=esc($error)?></div><?php endif;?>

<?php if($view==='dashboard'):
$recentUsers=db()->query('SELECT id,name,email,role,status,created_at FROM users ORDER BY id DESC LIMIT 6')->fetchAll(); ?>
<section class="cards"><?php foreach([['Users',$counts['users']],['Free products',$counts['products']],['New messages',$counts['messages']],['Subscribers',$counts['subscribers']]] as $c):?><article><span><?=esc((string)$c[0])?></span><strong><?=esc((string)$c[1])?></strong></article><?php endforeach;?></section>
<div class="grid2"><section class="panel"><div class="panelhead"><h2>Free content focus</h2><a href="./?view=resources">Manage resources</a></div><div class="notice">Paid/legacy orders are hidden because NexShelfy currently publishes free products and free resources only.</div></section><section class="panel"><div class="panelhead"><h2>Newest users</h2><a href="./?view=users">View all</a></div><div class="list"><?php foreach($recentUsers as $u):?><div><span class="avatar"><?=esc(strtoupper(substr($u['name'],0,1)))?></span><p><b><?=esc($u['name'])?></b><small><?=esc($u['email'])?></small></p><em><?=esc($u['role'])?></em></div><?php endforeach;?></div></section></div>

<?php elseif($view==='users'):
$editUserId=(int)($_GET['edit']??0);$editUser=null;if($editUserId){$es=db()->prepare('SELECT id,name,email,role,status FROM users WHERE id=?');$es->execute([$editUserId]);$editUser=$es->fetch();}
$q=clean((string)($_GET['q']??''),100); $params=[]; $sql='SELECT id,name,email,role,status,created_at FROM users'; if($q){$sql.=' WHERE name LIKE ? OR email LIKE ?';$params=["%$q%","%$q%"];} $sql.=' ORDER BY id DESC LIMIT 200';$st=db()->prepare($sql);$st->execute($params);$rows=$st->fetchAll(); ?>
<div class="gridform"><section class="panel"><div class="panelhead"><h2>Add / edit user</h2></div><form method="post" class="form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="user_save"><input type="hidden" name="id" value="<?=esc((string)($editUser['id']??0))?>"><label>Name<input required name="name" value="<?=esc($editUser['name']??'')?>"></label><label>Email<input required type="email" name="email" value="<?=esc($editUser['email']??'')?>"></label><label>Password<input type="password" name="password" placeholder="<?= $editUser?'Leave blank to keep current password':'Required for new user' ?>"></label><div class="twocol"><label>Role<select name="role"><option <?=($editUser['role']??'customer')==='customer'?'selected':''?>>customer</option><option <?=($editUser['role']??'')==='admin'?'selected':''?>>admin</option></select></label><label>Status<select name="status"><option <?=($editUser['status']??'active')==='active'?'selected':''?>>active</option><option <?=($editUser['status']??'')==='blocked'?'selected':''?>>blocked</option></select></label></div><button class="primary">Save user</button></form></section><section class="panel"><div class="panelhead"><h2>User management</h2><form class="search"><input name="q" value="<?=esc($q)?>" placeholder="Search name or email"><input type="hidden" name="view" value="users"><button>Search</button></form></div><div class="tablewrap"><table><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $u):?><tr><td><b><?=esc($u['name'])?></b><small><?=esc($u['email'])?></small></td><td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="user_role"><input type="hidden" name="id" value="<?=$u['id']?>"><select name="role"><option <?=$u['role']==='customer'?'selected':''?>>customer</option><option <?=$u['role']==='admin'?'selected':''?>>admin</option></select><button>Save</button></form></td><td><span class="badge <?=$u['status']?>"><?=esc($u['status'])?></span></td><td><?=esc(date('d M Y',strtotime($u['created_at'])))?></td><td><div class="row-actions"><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="user_status"><input type="hidden" name="id" value="<?=$u['id']?>"><input type="hidden" name="status" value="<?=$u['status']==='active'?'blocked':'active'?>"><button class="<?=$u['status']==='active'?'danger':''?>"><?=$u['status']==='active'?'Block':'Activate'?></button><a href="./?view=users&edit=<?=$u['id']?>">Edit</a></form><?php if((int)$u['id'] !== (int)$admin['id']):?><form method="post" class="inline" onsubmit="return confirm('Delete this user permanently?')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="user_delete"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="danger">Delete</button></form><?php endif;?></div></td></tr><?php endforeach;?></tbody></table></div></section></div>

<?php elseif($view==='products'):
$editId=(int)($_GET['edit']??0); $editing=null;if($editId){$s=db()->prepare('SELECT * FROM products WHERE id=?');$s->execute([$editId]);$editing=$s->fetch();}
$rows=db()->query('SELECT p.*, (SELECT COUNT(*) FROM download_leads dl WHERE dl.item_type="product" AND (dl.item_key=p.slug OR dl.item_key=CAST(p.id AS CHAR))) AS leads_count FROM products p ORDER BY p.id DESC')->fetchAll();
$total=count($rows);$active=count(array_filter($rows,fn($x)=>($x['status']??'')==='active'));$drafts=$total-$active;$downloads=array_sum(array_map(fn($x)=>(int)($x['download_count']??0),$rows));$leads=array_sum(array_map(fn($x)=>(int)($x['leads_count']??0),$rows)); ?>
<section class="admin-page-hero"><div><span>Content / Products</span><h2>Products</h2><p>Manage free downloadable products with fast cards, previews and cleaner actions.</p></div><a class="primary" href="./?view=products">+ New Product</a></section>
<section class="cards admin-kpi-row"><?php foreach([['Total products',$total],['Active',$active],['Draft',$drafts],['Downloads',$downloads],['Email leads',$leads]] as $c):?><article><span><?=esc((string)$c[0])?></span><strong><?=number_format((int)$c[1])?></strong></article><?php endforeach;?></section>
<div class="gridform admin-editor-layout"><section class="panel admin-form-panel"><div class="panelhead"><h2><?= $editing?'Edit product':'Create product' ?></h2><a class="button" href="./?view=products">Clear</a></div><form method="post" enctype="multipart/form-data" class="form admin-stacked-form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="product_save"><input type="hidden" name="id" value="<?=esc((string)($editing['id']??0))?>"><div class="admin-form-section"><b>Basic</b><label>Name<input required name="name" value="<?=esc($editing['name']??'')?>"></label><label>Slug<input required name="slug" value="<?=esc($editing['slug']??'')?>"></label><label>Description<textarea name="description" rows="4"><?=esc($editing['description']??'')?></textarea></label></div><div class="admin-form-section"><b>Media</b><label class="admin-dropzone">Drop cover or browse<input type="file" name="product_cover" accept="image/*" data-image-preview="product"></label><label>Cover image path<input name="cover_image" value="<?=esc($editing['cover_image']??'')?>" placeholder="storage/products/cover.jpg"></label><div class="admin-image-preview" data-preview="product"><?php if(!empty($editing['cover_image'])):?><img src="<?=esc(admin_asset_url($editing['cover_image']))?>" alt="Current product cover"><?php else:?><span>Live cover preview</span><?php endif;?></div></div><div class="admin-form-section"><b>Download</b><div class="admin-tabs-note">Use file, ZIP or external URL. Visitors still see the email-gated popup.</div><label>Upload product file<input type="file" name="product_file"></label><label>Upload ZIP file<input type="file" name="product_zip" accept=".zip,.rar,.7z,.pdf,.doc,.docx,.xlsx,.pptx"></label><label>Download file path<input name="file_path" value="<?=esc($editing['file_path']??'') ?>" placeholder="storage/products/file.pdf"></label><label>ZIP file path<input name="zip_path" value="<?=esc($editing['zip_path']??'') ?>" placeholder="storage/products/file.zip"></label><label>External link<input name="resource_link" value="<?=esc($editing['resource_link']??'') ?>" placeholder="https://..."></label></div><div class="admin-form-section"><b>Publish</b><label>Status<select name="status"><option value="active" <?=($editing['status']??'')==='active'?'selected':''?>>Active</option><option value="draft" <?=($editing['status']??'')==='draft'?'selected':''?>>Draft</option></select></label></div><div class="admin-sticky-actions"><a class="button" href="./?view=products">Cancel</a><button class="primary">Save product</button></div></form></section><section class="panel admin-list-panel"><div class="panelhead"><div><h2>Product library</h2><small class="muted"><?=number_format($total)?> product(s)</small></div><form class="search"><input id="adminProductSearch" type="search" placeholder="Search products..."><button type="button">Search</button></form></div><?php if(!$rows):?><div class="empty">📦<br><b>No products yet.</b><br>Create your first free product.</div><?php else:?><div class="admin-product-grid"><?php foreach($rows as $p): $img=admin_asset_url($p['cover_image']??''); ?><article class="admin-product-card"><div class="admin-card-cover"><?php if($img):?><img src="<?=esc($img)?>" alt="<?=esc($p['name'])?>"><?php else:?><span><?=esc(strtoupper(substr($p['name'],0,2)))?></span><?php endif;?><em class="badge <?=$p['status']?>"><?=esc($p['status'])?></em></div><div class="admin-card-body"><small><?=esc($p['slug'])?></small><h3><?=esc($p['name'])?></h3><p><?=esc(mb_strimwidth((string)($p['description']??''),0,110,'…'))?></p><div class="admin-card-stats"><span><?=number_format((int)($p['download_count']??0))?><small>Downloads</small></span><span><?=number_format((int)($p['leads_count']??0))?><small>Leads</small></span><span><?=!empty($p['updated_at'])?esc(date('d M',strtotime($p['updated_at']))):'—'?><small>Updated</small></span></div><div class="admin-card-actions"><a href="../shop/<?=esc($p['slug'])?>/" target="_blank">Preview</a><a href="./?view=products&edit=<?=$p['id']?>">Quick edit</a><form method="post" onsubmit="return confirm('Delete this product?')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="product_delete"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="danger">Delete</button></form></div></div></article><?php endforeach;?></div><?php endif;?></section></div>
<?php elseif($view==='blogs'):
$editId=(int)($_GET['edit']??0);$editing=null;if($editId){$q=db()->prepare('SELECT * FROM blog_posts WHERE id=?');$q->execute([$editId]);$editing=$q->fetch();}$posts=db()->query('SELECT b.*, (SELECT COUNT(*) FROM blog_post_resources br WHERE br.blog_post_id=b.id) AS attached_resources FROM blog_posts b ORDER BY id DESC')->fetchAll();$resources=db()->query('SELECT * FROM content_resources WHERE status="active" ORDER BY title')->fetchAll();$selected=[];if($editing){$q=db()->prepare('SELECT resource_id FROM blog_post_resources WHERE blog_post_id=?');$q->execute([$editId]);$selected=array_map('intval',array_column($q->fetchAll(),'resource_id'));}$published=count(array_filter($posts,fn($x)=>($x['status']??'')==='published'));$draft=count($posts)-$published; ?>
<section class="admin-page-hero"><div><span>Content / CMS</span><h2>Blogs</h2><p>A faster CMS layout with thumbnails, status, attached resources and preview actions.</p></div><a class="primary" href="./?view=blogs">+ New Article</a></section><section class="cards admin-kpi-row"><?php foreach([['Articles',count($posts)],['Published',$published],['Draft',$draft],['Resources attached',array_sum(array_map(fn($x)=>(int)$x['attached_resources'],$posts))]] as $c):?><article><span><?=esc((string)$c[0])?></span><strong><?=number_format((int)$c[1])?></strong></article><?php endforeach;?></section>
<div class="gridform admin-editor-layout"><section class="panel admin-form-panel"><div class="panelhead"><h2><?=$editing?'Edit article':'Create article'?></h2><a class="button" href="./?view=blogs">Clear</a></div><form method="post" enctype="multipart/form-data" class="form admin-stacked-form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="blog_save"><input type="hidden" name="id" value="<?=esc((string)($editing['id']??0))?>"><div class="admin-form-section"><b>Article</b><label>Title<input required name="title" value="<?=esc($editing['title']??'')?>"></label><label>Slug<input required name="slug" value="<?=esc($editing['slug']??'')?>"></label><label>Excerpt<textarea name="excerpt" rows="3"><?=esc($editing['excerpt']??'')?></textarea></label><label>Article content<textarea required name="content" rows="12" placeholder="Use HTML for callouts, headings and buttons until rich editor is added."><?=esc($editing['content']??'')?></textarea></label></div><div class="admin-form-section"><b>Media & taxonomy</b><div class="twocol"><label>Category<input list="blogCategories" name="category" value="<?=esc($editing['category']??'')?>"></label><label>Cover colour<input type="color" name="cover_color" value="<?=esc($editing['cover_color']??'#6366F1')?>"></label></div><datalist id="blogCategories"><?php foreach(db()->query('SELECT DISTINCT category FROM blog_posts WHERE category IS NOT NULL AND category<>"" ORDER BY category')->fetchAll() as $cat):?><option value="<?=esc($cat['category'])?>"><?php endforeach;?></datalist><label class="admin-dropzone">Drop blog thumbnail or browse<input type="file" name="cover_upload" accept="image/*" data-image-preview="blog"></label><label>Cover image path<input name="cover_image" value="<?=esc($editing['cover_image']??'')?>" placeholder="storage/blog/image.jpg"></label><div class="admin-image-preview" data-preview="blog"><?php if(!empty($editing['cover_image'])):?><img src="<?=esc(admin_asset_url($editing['cover_image']))?>" alt="Current blog cover"><?php else:?><span>Blog thumbnail preview</span><?php endif;?></div></div><div class="admin-form-section"><b>Attached resources</b><label>Related downloadable resources<select name="resource_ids[]" multiple size="7"><?php foreach($resources as $r):?><option value="<?=$r['id']?>" <?=in_array((int)$r['id'],$selected,true)?'selected':''?>><?=esc($r['title'])?></option><?php endforeach;?></select></label><label>Status<select name="status"><option value="published" <?=($editing['status']??'')==='published'?'selected':''?>>Published</option><option value="draft" <?=($editing['status']??'draft')==='draft'?'selected':''?>>Draft</option></select></label></div><div class="admin-sticky-actions"><a class="button" href="./?view=blogs">Cancel</a><button class="primary">Save article</button></div></form></section><section class="panel admin-list-panel"><div class="panelhead"><div><h2>Blog library</h2><small class="muted"><?=count($posts)?> article(s)</small></div><a class="button" href="../blog/" target="_blank">View blog ↗</a></div><?php if(!$posts):?><div class="empty">✍️<br><b>No articles yet.</b><br>Create your first post.</div><?php else:?><div class="admin-product-grid admin-blog-grid"><?php foreach($posts as $p): $img=admin_asset_url($p['cover_image']??''); ?><article class="admin-product-card"><div class="admin-card-cover blog-cover"><?php if($img):?><img src="<?=esc($img)?>" alt="<?=esc($p['title'])?>"><?php else:?><span><?=esc(strtoupper(substr($p['title'],0,2)))?></span><?php endif;?><em class="badge <?=$p['status']?>"><?=esc($p['status'])?></em></div><div class="admin-card-body"><small><?=esc(($p['category']?:'Article').' · '.$p['slug'])?></small><h3><?=esc($p['title'])?></h3><p><?=esc(mb_strimwidth((string)($p['excerpt']??''),0,110,'…'))?></p><div class="admin-card-stats"><span><?=esc((string)($p['reading_time']?:'—'))?><small>Read</small></span><span><?=number_format((int)$p['attached_resources'])?><small>Resources</small></span><span><?=!empty($p['published_at'])?esc(date('d M',strtotime($p['published_at']))):'Draft'?><small>Date</small></span></div><div class="admin-card-actions"><a href="../blog/<?=esc($p['slug'])?>/" target="_blank">Preview</a><a href="./?view=blogs&edit=<?=$p['id']?>">Edit</a><form method="post" onsubmit="return confirm('Delete this blog?')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="blog_delete"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="danger">Delete</button></form></div></div></article><?php endforeach;?></div><?php endif;?></section></div>
<?php elseif($view==='resources'):
$editId=(int)($_GET['edit']??0);$editing=null;if($editId){$q=db()->prepare('SELECT * FROM content_resources WHERE id=?');$q->execute([$editId]);$editing=$q->fetch();}$rows=db()->query('SELECT r.*, (SELECT COUNT(*) FROM download_leads dl WHERE dl.item_type="resource" AND (dl.item_key=r.slug OR dl.item_key=CAST(r.id AS CHAR))) AS leads_count FROM content_resources r ORDER BY id DESC')->fetchAll();$active=count(array_filter($rows,fn($x)=>($x['status']??'')==='active'));$downloads=array_sum(array_map(fn($x)=>(int)($x['download_count']??0),$rows)); ?>
<section class="admin-page-hero"><div><span>Content / Resources</span><h2>Resources</h2><p>Manage PDFs, packs, templates and downloads with visual cards and clear file metadata.</p></div><a class="primary" href="./?view=resources">+ New Resource</a></section><section class="cards admin-kpi-row"><?php foreach([['Resources',count($rows)],['Active',$active],['Draft',count($rows)-$active],['Downloads',$downloads],['Leads',array_sum(array_map(fn($x)=>(int)($x['leads_count']??0),$rows))]] as $c):?><article><span><?=esc((string)$c[0])?></span><strong><?=number_format((int)$c[1])?></strong></article><?php endforeach;?></section>
<div class="gridform admin-editor-layout"><section class="panel admin-form-panel"><div class="panelhead"><h2><?=$editing?'Edit resource':'Add resource'?></h2><a class="button" href="./?view=resources">Clear</a></div><form method="post" enctype="multipart/form-data" class="form admin-stacked-form"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="resource_save"><input type="hidden" name="id" value="<?=esc((string)($editing['id']??0))?>"><div class="admin-form-section"><b>Basic</b><label>Title<input required name="title" value="<?=esc($editing['title']??'')?>"></label><label>Slug<input required name="slug" value="<?=esc($editing['slug']??'')?>"></label><label>Description<textarea name="description" rows="4"><?=esc($editing['description']??'')?></textarea></label><label>Resource type<input name="resource_type" value="<?=esc($editing['resource_type']??'')?>" placeholder="PDF / ZIP / Canva / Notion"></label></div><div class="admin-form-section"><b>Media</b><label class="admin-dropzone">Drop cover or browse<input type="file" name="resource_cover" accept="image/*" data-image-preview="resource"></label><label>Cover image path<input name="cover_image" value="<?=esc($editing['cover_image']??'')?>" placeholder="storage/resources/cover.jpg"></label><div class="admin-image-preview" data-preview="resource"><?php if(!empty($editing['cover_image'])):?><img src="<?=esc(admin_asset_url($editing['cover_image']))?>" alt="Current resource cover"><?php else:?><span>Resource preview</span><?php endif;?></div></div><div class="admin-form-section"><b>Files</b><label>Upload main file<input type="file" name="resource_file"></label><label>Upload ZIP file<input type="file" name="resource_zip" accept=".zip,.rar,.7z,.pdf,.doc,.docx,.xlsx,.pptx"></label><label>Existing file path<input name="file_path" value="<?=esc($editing['file_path']??'')?>" placeholder="storage/resources/file.pdf"></label><label>ZIP file path<input name="zip_path" value="<?=esc($editing['zip_path']??'')?>" placeholder="storage/resources/file.zip"></label><label>Download filename<input name="file_name" value="<?=esc($editing['file_name']??'')?>"></label><label>External resource link<input name="resource_link" value="<?=esc($editing['resource_link']??'')?>" placeholder="https://..."></label><label>Status<select name="status"><option value="active" <?=($editing['status']??'active')==='active'?'selected':''?>>Active</option><option value="draft" <?=($editing['status']??'')==='draft'?'selected':''?>>Draft</option></select></label></div><div class="admin-sticky-actions"><a class="button" href="./?view=resources">Cancel</a><button class="primary">Save resource</button></div></form></section><section class="panel admin-list-panel"><div class="panelhead"><div><h2>Resource board</h2><small class="muted"><?=count($rows)?> resource(s)</small></div><div class="admin-filter-pills"><span>Top downloaded</span><span>Most saved</span><span>Newest</span></div></div><?php if(!$rows):?><div class="empty">◇<br><b>No resources yet.</b><br>Add a PDF, template or downloadable pack.</div><?php else:?><div class="admin-product-grid admin-resource-grid"><?php foreach($rows as $r): $img=admin_asset_url($r['cover_image']??''); ?><article class="admin-product-card"><div class="admin-card-cover"><?php if($img):?><img src="<?=esc($img)?>" alt="<?=esc($r['title'])?>"><?php else:?><span><?=esc(strtoupper(substr($r['title'],0,2)))?></span><?php endif;?><em class="badge <?=$r['status']?>"><?=esc($r['status'])?></em></div><div class="admin-card-body"><small><?=esc(($r['resource_type']?:'Resource').' · '.$r['slug'])?></small><h3><?=esc($r['title'])?></h3><p><?=esc(mb_strimwidth((string)($r['description']??''),0,105,'…'))?></p><div class="admin-card-stats"><span><?=number_format((int)($r['download_count']??0))?><small>Downloads</small></span><span><?=number_format((int)($r['leads_count']??0))?><small>Leads</small></span><span><?=esc($r['file_name']?:'File')?><small>Asset</small></span></div><div class="admin-card-actions"><a href="../api/free-download.php?type=resource&id=<?=$r['id']?>" target="_blank">Test</a><a href="./?view=resources&edit=<?=$r['id']?>">Edit</a><form method="post" onsubmit="return confirm('Delete this resource?')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="resource_delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="danger">Delete</button></form></div></div></article><?php endforeach;?></div><?php endif;?></section></div>
<?php elseif($view==='comments'):
$rows=db()->query('SELECT c.*,b.title blog_title FROM blog_comments c JOIN blog_posts b ON b.id=c.blog_post_id ORDER BY c.id DESC LIMIT 300')->fetchAll(); ?>
<section class="panel"><div class="panelhead"><h2>Blog comments</h2></div><div class="messagegrid"><?php foreach($rows as $c):?><article><div class="panelhead"><div><b><?=esc($c['name'])?></b><small><?=esc($c['blog_title'].' · '.$c['email'])?></small></div><span class="badge <?=$c['status']?>"><?=esc($c['status'])?></span></div><p><?=nl2br(esc($c['comment']))?></p><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="comment_status"><input type="hidden" name="id" value="<?=$c['id']?>"><select name="status"><?php foreach(['pending','approved','spam'] as $st):?><option <?=$c['status']===$st?'selected':''?>><?=$st?></option><?php endforeach;?></select><button>Update</button></form><form method="post" class="inline" onsubmit="return confirm('Delete this comment?')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="comment_delete"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="danger">Delete</button></form></article><?php endforeach;?></div></section>

<?php elseif($view==='orders'):
$rows=db()->query('SELECT o.id,o.order_number,o.customer_name,o.customer_email,o.customer_phone,o.address_line1,o.address_line2,o.city,o.emirate,o.postal_code,o.order_notes,o.payment_method,o.total,o.currency,o.status,o.payment_status,o.created_at,(SELECT GROUP_CONCAT(CONCAT(oi.product_name," ×",oi.quantity) SEPARATOR "||") FROM order_items oi WHERE oi.order_id=o.id) AS items_summary FROM orders o ORDER BY o.id DESC LIMIT 300')->fetchAll(); ?>
<section class="panel"><div class="panelhead"><h2>Order management</h2></div><?php render_orders_table($rows,true);?></section>


<?php elseif($view==='download_leads'):
$rows=db()->query('SELECT * FROM download_leads ORDER BY id DESC LIMIT 500')->fetchAll(); ?>
<section class="panel"><div class="panelhead"><h2>Download leads</h2><div><span class="muted"><?=count($rows)?> lead(s)</span> <a class="button" href="./export.php?type=download_leads">Export CSV</a></div></div><div class="tablewrap"><table><thead><tr><th>Email</th><th>Item</th><th>Type</th><th>IP</th><th>Date</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><b><?=esc($r['email'])?></b><small><?=esc($r['name']??'')?></small></td><td><?=esc($r['item_title']?:$r['item_key'])?></td><td><?=esc($r['item_type'])?></td><td><?=esc($r['ip_address']??'')?></td><td><?=esc(date('d M Y, H:i',strtotime($r['created_at'])))?></td></tr><?php endforeach;?></tbody></table></div></section>

<?php elseif($view==='creator_apps'):
$rows=db()->query('SELECT * FROM creator_applications ORDER BY id DESC LIMIT 300')->fetchAll(); ?>
<section class="panel"><div class="panelhead"><h2>Creator applications</h2><span class="muted"><?=count($rows)?> application(s)</span></div><div class="messagegrid"><?php foreach($rows as $r):?><article><div class="panelhead"><div><b><?=esc($r['name'])?></b><small><?=esc($r['email'].' · '.$r['age'].' · '.$r['gender'].' · '.$r['qualification'])?></small></div><span class="badge <?=$r['status']?>"><?=esc($r['status'])?></span></div><p><b>Why:</b><br><?=nl2br(esc($r['reason']))?></p><p><b>Contribution:</b><br><?=nl2br(esc($r['contribution']))?></p><small><?=esc(date('d M Y, H:i',strtotime($r['created_at'])))?></small></article><?php endforeach;?></div></section>

<?php elseif($view==='messages'):
$rows=db()->query('SELECT m.*,u.name AS account_name,u.email AS account_email FROM contact_messages m LEFT JOIN users u ON u.id=m.user_id ORDER BY m.id DESC LIMIT 300')->fetchAll(); ?>
<section class="panel"><div class="panelhead"><h2>Contact inbox</h2><span class="muted"><?=count($rows)?> message(s)</span></div><?php if(!$rows):?><div class="empty">No customer messages yet.</div><?php else:?><div class="messagegrid"><?php foreach($rows as $m):?><article><div class="panelhead"><div><b><?=esc($m['subject'])?></b><small><?=esc($m['name'].' · '.$m['email'])?></small></div><span class="badge <?=$m['status']?>"><?=esc($m['status'])?></span></div><p><?=nl2br(esc($m['message']))?></p><footer><time><?=esc(date('d M Y, H:i',strtotime($m['created_at'])))?></time><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="message_status"><input type="hidden" name="id" value="<?=$m['id']?>"><select name="status"><?php foreach(['new','read','replied','archived'] as $s):?><option <?=$m['status']===$s?'selected':''?>><?=$s?></option><?php endforeach;?></select><button>Update</button></form></footer></article><?php endforeach;?></div><?php endif;?></section>

<?php elseif($view==='subscribers'):
$rows=db()->query('SELECT * FROM newsletter_subscribers ORDER BY id DESC LIMIT 500')->fetchAll(); ?>
<section class="panel"><div class="panelhead"><h2>Newsletter subscribers</h2><a class="button" href="./export.php?type=subscribers">Export CSV</a></div><div class="tablewrap"><table><thead><tr><th>Email</th><th>Status</th><th>Subscribed</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=esc($r['email'])?></td><td><span class="badge <?=$r['status']?>"><?=esc($r['status'])?></span></td><td><?=esc(date('d M Y',strtotime($r['subscribed_at'])))?></td><td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="subscriber_status"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="status" value="<?=$r['status']==='active'?'unsubscribed':'active'?>"><button><?=$r['status']==='active'?'Unsubscribe':'Activate'?></button></form></td></tr><?php endforeach;?></tbody></table></div></section>

<?php elseif($view==='activity'):
$rows=db()->query('SELECT l.*,u.name admin_name FROM admin_activity_logs l LEFT JOIN users u ON u.id=l.admin_id ORDER BY l.id DESC LIMIT 300')->fetchAll(); ?>
<section class="panel"><div class="panelhead"><h2>Admin activity log</h2></div><div class="timeline"><?php foreach($rows as $r):?><div><span></span><p><b><?=esc($r['admin_name']??'System')?></b> <?=esc($r['action'].' '.$r['entity_type'])?> <?= $r['entity_id']?'#'.(int)$r['entity_id']:'' ?><small><?=esc(date('d M Y, H:i',strtotime($r['created_at'])))?> · <?=esc($r['ip_address']??'')?></small></p></div><?php endforeach;?></div></section>

<?php elseif($view==='settings'):
$settings=[];foreach(db()->query('SELECT setting_key,setting_value FROM app_settings')->fetchAll() as $r)$settings[$r['setting_key']]=$r['setting_value']; ?>
<section class="panel narrow"><div class="panelhead"><h2>Application settings</h2></div><form method="post" class="form" data-theme-settings><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="settings_save"><label>Site name<input name="site_name" value="<?=esc($settings['site_name']??'NexShelfy')?>"></label><label>Support email<input type="email" name="support_email" value="<?=esc($settings['support_email']??'enquiry@mrusman.com')?>"></label><label>Default currency<input maxlength="3" name="currency" value="<?=esc($settings['currency']??'AED')?>"></label><label>Global colour preset<select name="theme_preset"><option value="aurora" <?=($settings['theme_preset']??'aurora')==='aurora'?'selected':''?>>Aurora Clean — safest light</option><option value="skyline" <?=($settings['theme_preset']??'')==='skyline'?'selected':''?>>Skyline Blue — premium SaaS</option><option value="graphite" <?=($settings['theme_preset']??'')==='graphite'?'selected':''?>>Graphite Mono — clean contrast</option><option value="cream" <?=($settings['theme_preset']??'')==='cream'?'selected':''?>>Warm Cream — editorial</option><option value="plum" <?=($settings['theme_preset']??'')==='plum'?'selected':''?>>Plum Studio — creative</option><option value="midnight" <?=($settings['theme_preset']??'')==='midnight'?'selected':''?>>Midnight Neon — dark</option><option value="sand" <?=($settings['theme_preset']??'')==='sand'?'selected':''?>>Warm Sand</option><option value="mint" <?=($settings['theme_preset']??'')==='mint'?'selected':''?>>Mint Growth</option><option value="rose" <?=($settings['theme_preset']??'')==='rose'?'selected':''?>>Rose Violet</option><option value="mono" <?=($settings['theme_preset']??'')==='mono'?'selected':''?>>Clean Mono</option><option value="custom" <?=($settings['theme_preset']??'')==='custom'?'selected':''?>>Custom</option></select></label><div class="twocol"><label>Primary colour<input type="color" name="theme_primary" value="<?=esc($settings['theme_primary']??'#4f46e5')?>"></label><label>Secondary colour<input type="color" name="theme_secondary" value="<?=esc($settings['theme_secondary']??'#7c3aed')?>"></label><label>Page surface<input type="color" name="theme_surface" value="<?=esc($settings['theme_surface']??'#f7f7ff')?>"></label><label>Text colour<input type="color" name="theme_ink" value="<?=esc($settings['theme_ink']??'#111827')?>"></label></div><div class="theme-swatch-row" data-theme-swatches></div><label>Maintenance mode<select name="maintenance_mode"><option value="0" <?=($settings['maintenance_mode']??'0')==='0'?'selected':''?>>Off</option><option value="1" <?=($settings['maintenance_mode']??'0')==='1'?'selected':''?>>On</option></select></label><button class="primary">Save global settings</button></form></section>
<?php endif; ?>
</main></div><script src="./assets/admin.js?v=20260711-unified-admin-theme-v4"></script></body></html>
<?php
function render_orders_table(array $rows, bool $actions): void { ?>
<div class="tablewrap"><table><thead><tr><th>Order</th><th>Customer & delivery</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><?php if($actions):?><th>Manage</th><?php endif;?></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><b><?=esc($r['order_number'])?></b><small><?=esc(strtoupper($r['payment_method']??'cod'))?></small></td><td><b><?=esc($r['customer_name'])?></b><?php if(isset($r['customer_email'])):?><small><?=esc($r['customer_email'])?> · <?=esc($r['customer_phone']??'—')?></small><?php endif;?><details><summary>Delivery address</summary><small><?=esc(implode(', ',array_filter([$r['address_line1']??'', $r['address_line2']??'', $r['city']??'', $r['emirate']??'', $r['postal_code']??''])))?></small><?php if(!empty($r['order_notes'])):?><small>Notes: <?=esc($r['order_notes'])?></small><?php endif;?></details></td><td><?php foreach(explode('||',(string)($r['items_summary']??'')) as $item): if($item!==''):?><small><?=esc($item)?></small><?php endif; endforeach;?></td><td><?=number_format((float)$r['total'],2).' '.esc($r['currency'])?></td><td><span class="badge <?=$r['status']?>"><?=esc($r['status'])?></span></td><td><span class="badge <?=$r['payment_status']?>"><?=esc($r['payment_status'])?></span></td><td><?=esc(date('d M Y',strtotime($r['created_at'])))?></td><?php if($actions):?><td><form method="post" class="orderform"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="order_update"><input type="hidden" name="id" value="<?=$r['id']?>"><select name="status"><?php foreach(['pending','processing','completed','cancelled','refunded'] as $st):?><option <?=$r['status']===$st?'selected':''?>><?=$st?></option><?php endforeach;?></select><select name="payment_status"><?php foreach(['unpaid','paid','failed','refunded'] as $ps):?><option <?=$r['payment_status']===$ps?'selected':''?>><?=$ps?></option><?php endforeach;?></select><button>Save</button></form></td><?php endif;?></tr><?php endforeach;?></tbody></table></div><?php }
