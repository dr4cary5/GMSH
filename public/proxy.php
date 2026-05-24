<?php
// public/proxy.php
require_once __DIR__ . '/../vendor/autoload.php';

use MSHW\Core\ProxyEngine;
use MSHW\Core\Rewriter;

// تنظیم گزارش خطا برای دیباگ (در پروداکشن خاموش شود)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

try {
    // مدیریت اکشن‌های خاص (مثل کوکی‌ها)
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

    // دریافت و دیکد کردن آدرس مقصد
    $q = $_GET['q'] ?? '';
    if (!$q) {
        http_response_code(400);
        exit('<h1>MSHW Proxy</h1><p>Missing target URL. Usage: ?q=base64_url</p>');
    }

    $targetUrl = base64_decode($q);
    if (!$targetUrl || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        exit('<h1>Error</h1><p>Invalid Target URL</p>');
    }

    // استخراج هدرهای HTTP واقعی از $_SERVER (فیلتر کردن نویزها)
    $httpHeaders = [];
    foreach ($_SERVER as $key => $val) {
        if (str_starts_with($key, 'HTTP_')) {
            $headerName = str_replace('_', '-', substr($key, 5));
            // تبدیل نام هدر به فرمت استاندارد (مثلا Content-Type)
            $headerName = ucwords($headerName, '-');
            $httpHeaders[$headerName] = $val;
        }
    }
    // اصلاح هدرهای خاص
    if (isset($_SERVER['CONTENT_TYPE'])) $httpHeaders['Content-Type'] = $_SERVER['CONTENT_TYPE'];
    if (isset($_SERVER['CONTENT_LENGTH'])) $httpHeaders['Content-Length'] = $_SERVER['CONTENT_LENGTH'];

    // اجرای موتور پروکسی
    $engine = new ProxyEngine();
    $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? file_get_contents('php://input') : null;
    
    $result = $engine->request($targetUrl, $_SERVER['REQUEST_METHOD'], $httpHeaders, $body);

    // ارسال هدرهای پاسخ به کلاینت
    http_response_code($result['status']);
    foreach ($result['headers'] as $k => $v) {
        // جلوگیری از ارسال هدر تکراری یا خراب
        if (is_array($v)) {
            foreach ($v as $val) header("$k: $val", false);
        }
    }

    // پردازش و بازنویسی محتوا
    $contentType = $result['type'] ?? '';
    $stream = $result['stream'];

    if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml')) {
        $raw = stream_get_contents($stream);
        if ($raw === false) $raw = '';
        fclose($stream);
        
        $rewriter = new Rewriter(rtrim(dirname($_SERVER['PHP_SELF']), '/'));
        echo $rewriter->rewriteHtml($raw, $targetUrl);
        
    } elseif (str_contains($contentType, 'text/css') || str_contains($contentType, 'javascript')) {
        $raw = stream_get_contents($stream);
        if ($raw === false) $raw = '';
        fclose($stream);
        // بازنویسی ساده برای CSS
        echo str_replace('url(', "url({$targetUrl}/", $raw); 
        
    } else {
        // استریم مستقیم فایل‌های باینری
        if ($stream) fpassthru($stream);
    }

} catch (Throwable $e) {
    // صفحه خطای تمیز برای کاربر
    http_response_code(500);
    echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:2rem;background:#1e1e1e;color:#fff'>";
    echo "<h1>🔥 Proxy Error 500</h1>";
    echo "<pre style='background:#333;padding:1rem;border-radius:4px;overflow:auto'>";
    echo "URL: " . htmlspecialchars($targetUrl ?? 'N/A') . "\n";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine();
    echo "</pre></body></html>";
    error_log("MSHW Proxy Error: " . $e->getMessage());
}
