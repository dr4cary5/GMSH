<?php
// public/proxy.php - Final Stable Version
require_once __DIR__ . '/../vendor/autoload.php';

use MSHW\Core\ProxyEngine;
use MSHW\Core\Rewriter;

// غیرفعال کردن فشرده‌سازی خروجی برای جلوگیری از تداخل
if (ob_get_level()) ob_end_clean();

try {
    // هندل اکشن‌های داشبورد
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    if ($action === 'cookies') {
        $engine = new ProxyEngine();
        header('Content-Type: application/json');
        echo json_encode($engine->getCookieJar()->getAll());
        exit;
    }
    if ($action === 'clear-cookies' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $engine = new ProxyEngine();
        $engine->getCookieJar()->clear();
        echo json_encode(['status' => 'cleared']);
        exit;
    }

    // دریافت آدرس مقصد
    $q = $_GET['q'] ?? '';
    if (!$q) {
        http_response_code(400);
        echo '<h1>MSHW Proxy</h1><p>Usage: ?q=base64_url</p>';
        exit;
    }

    $targetUrl = base64_decode($q);
    if (!$targetUrl || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo '<h1>Error</h1><p>Invalid URL</p>';
        exit;
    }

    // آماده‌سازی هدرهای ورودی
    $httpHeaders = [];
    foreach ($_SERVER as $key => $val) {
        if (str_starts_with($key, 'HTTP_')) {
            $name = str_replace('_', '-', substr($key, 5));
            $httpHeaders[ucwords($name, '-')] = $val;
        }
    }
    if (isset($_SERVER['CONTENT_TYPE'])) $httpHeaders['Content-Type'] = $_SERVER['CONTENT_TYPE'];
    if (isset($_SERVER['CONTENT_LENGTH'])) $httpHeaders['Content-Length'] = $_SERVER['CONTENT_LENGTH'];

    // اجرای پروکسی
    $engine = new ProxyEngine();
    $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? file_get_contents('php://input') : null;
    $result = $engine->request($targetUrl, $_SERVER['REQUEST_METHOD'], $httpHeaders, $body);

    // ارسال هدرهای پاسخ (حذف موارد مسدودکننده iframe)
    http_response_code($result['status']);
    $strip = ['content-security-policy', 'x-frame-options', 'frame-ancestors', 'content-security-policy-report-only'];
    foreach ($result['headers'] as $k => $v) {
        if (!in_array(strtolower($k), $strip) && is_array($v)) {
            foreach ($v as $val) header("$k: $val", false);
        }
    }

    // پردازش بدنه
    $contentType = $result['type'] ?? '';
    $stream = $result['stream'] ?? null;

    if (!$stream) {
        echo "<!-- Empty response -->";
        exit;
    }

    $raw = stream_get_contents($stream);
    fclose($stream);
    
    if ($raw === false) $raw = '';

    // اگر HTML بود، بازنویسی کن؛ در غیر این صورت خام بفرست
    if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml')) {
        $rewriter = new Rewriter(rtrim(dirname($_SERVER['PHP_SELF']), '/'));
        $output = $rewriter->rewriteHtml($raw, $targetUrl);
        // اطمینان از خروجی صحیح
        if ($output === '') $output = $raw;
        echo $output;
    } else {
        // CSS/JS/Binary: ارسال خام
        echo $raw;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><body style='font-family:monospace;padding:2rem;background:#1e1e1e;color:#0f0'>";
    echo "<h1>🔥 Proxy Error</h1><pre>";
    echo "Target: $targetUrl\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre></body></html>";
    error_log("MSHW Proxy: " . $e->getMessage());
}
