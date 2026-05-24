<?php
// public/proxy.php - Debug & Stream Fix Version
require_once __DIR__ . '/../vendor/autoload.php';

use MSHW\Core\ProxyEngine;
use MSHW\Core\Rewriter;

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // دریافت آدرس
    $q = $_GET['q'] ?? '';
    if (!$q) {
        http_response_code(400);
        exit('<h1>MSHW Proxy</h1><p>Missing target URL</p>');
    }

    $targetUrl = base64_decode($q);
    if (!$targetUrl || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        exit('<h1>Error</h1><p>Invalid URL</p>');
    }

    // آماده‌سازی هدرهای ورودی
    $httpHeaders = [];
    foreach ($_SERVER as $key => $val) {
        if (str_starts_with($key, 'HTTP_')) {
            $name = str_replace('_', '-', substr($key, 5));
            $name = ucwords($name, '-');
            $httpHeaders[$name] = $val;
        }
    }
    if (isset($_SERVER['CONTENT_TYPE'])) $httpHeaders['Content-Type'] = $_SERVER['CONTENT_TYPE'];
    if (isset($_SERVER['CONTENT_LENGTH'])) $httpHeaders['Content-Length'] = $_SERVER['CONTENT_LENGTH'];

    // اجرای پروکسی
    $engine = new ProxyEngine();
    $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? file_get_contents('php://input') : null;
    $result = $engine->request($targetUrl, $_SERVER['REQUEST_METHOD'], $httpHeaders, $body);

    // ارسال هدرهای پاسخ
    http_response_code($result['status']);
    
    // فیلتر و ارسال هدرها
    $stripHeaders = ['content-security-policy', 'x-frame-options', 'frame-ancestors'];
    foreach ($result['headers'] as $k => $v) {
        if (!in_array(strtolower($k), $stripHeaders) && is_array($v)) {
            foreach ($v as $val) {
                header("$k: $val", false);
            }
        }
    }

    // پردازش بدنه پاسخ
    $contentType = $result['type'] ?? '';
    $stream = $result['stream'] ?? null;

    // اگر استریم نداریم یا خالی است
    if (!$stream) {
        echo "<!-- Empty response from target -->";
        exit;
    }

    // خواندن محتوا برای بازنویسی
    $raw = stream_get_contents($stream);
    fclose($stream);
    
    if ($raw === false || $raw === '') {
        // اگر محتوا خالی بود، احتمالاً گوگل بلاک کرده
        if ($result['status'] >= 400) {
            echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:2rem;background:#1e1e1e;color:#fff'>";
            echo "<h1>⚠️ Target Blocked or Empty</h1>";
            echo "<p>Status: {$result['status']}</p>";
            echo "<p>URL: " . htmlspecialchars($targetUrl) . "</p>";
            echo "<p>Google and many modern sites often block proxy requests.</p>";
            echo "<p><strong>Try a simpler site:</strong> <a href='?q=" . base64_encode('http://example.com') . "'>example.com</a></p>";
            echo "</body></html>";
            exit;
        }
    }

    // بازنویسی HTML
    if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml')) {
        $rewriter = new Rewriter(rtrim(dirname($_SERVER['PHP_SELF']), '/'));
        echo $rewriter->rewriteHtml($raw, $targetUrl);
    } 
    // بازنویسی CSS/JS
    elseif (str_contains($contentType, 'text/css') || str_contains($contentType, 'javascript')) {
        echo $raw; // فعلاً بدون بازنویسی پیچیده برای تست
    } 
    // فایل‌های باینری
    else {
        echo $raw;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:2rem;background:#1e1e1e;color:#fff'>";
    echo "<h1>🔥 Proxy Error 500</h1>";
    echo "<pre style='background:#333;padding:1rem;border-radius:4px;overflow:auto;color:#f55'>";
    echo "Target: " . htmlspecialchars($targetUrl ?? 'N/A') . "\n";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre></body></html>";
    error_log("MSHW Proxy Error: " . $e->getMessage());
}
