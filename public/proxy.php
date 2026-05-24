<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MSHW\Core\ProxyEngine;
use MSHW\Core\Rewriter;

$q = $_GET['q'] ?? $_POST['q'] ?? '';
if (!$q) {
    http_response_code(400);
    exit('Missing target URL');
}

$targetUrl = base64_decode($q);
if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid URL');
}

$engine = new ProxyEngine();
$result = $engine->request($targetUrl, $_SERVER['REQUEST_METHOD'], $_SERVER, $_SERVER['REQUEST_METHOD'] === 'POST' ? file_get_contents('php://input') : null);

// تنظیم هدرهای پاسخ
http_response_code($result['status']);
foreach ($result['headers'] as $k => $v) {
    foreach ($v as $val) header("$k: $val", false);
}

// بازنویسی محتوا در صورت نیاز
$contentType = $result['type'];
$stream = $result['stream'];

if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml')) {
    $raw = stream_get_contents($stream);
    fclose($stream);
    
    $rewriter = new Rewriter(rtrim(dirname($_SERVER['PHP_SELF']), '/'));
    echo $rewriter->rewriteHtml($raw, $targetUrl);
} elseif (str_contains($contentType, 'text/css') || str_contains($contentType, 'application/javascript')) {
    $raw = stream_get_contents($stream);
    fclose($stream);
    
    // بازنویلی ساده برای CSS/JS (آدرس‌های url() و fetch)
    $rewriter = new Rewriter(rtrim(dirname($_SERVER['PHP_SELF']), '/'));
    $raw = str_replace("{$targetUrl}/", $targetUrl . '/', $raw); // patch نسبی
    echo $rewriter->rewriteCss($raw, $targetUrl);
} else {
    // استریم مستقیم برای فایل‌های باینری
    fpassthru($stream);
}
