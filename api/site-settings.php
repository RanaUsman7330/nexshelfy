<?php
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
echo json_encode(['ok' => true, 'settings' => public_site_settings()], JSON_UNESCAPED_SLASHES);
