<?php
namespace MSHW\Core;

use Symfony\Component\HttpClient\HttpClient;
use MSHW\Utils\Security;

class ProxyEngine
{
    private $client;
    private $config;
    private $cookieJar;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        
        // استفاده از کوکی‌جار ساده در حافظه (RAM)
        $this->cookieJar = new CookieJar();

        // راه‌اندازی کلاینت HTTP مدرن (HTTP/2 + TLS 1.3)
        $this->client = HttpClient::create([
            'timeout' => $this->config['proxy']['timeout'],
            'max_redirects' => $this->config['proxy']['max_redirects'],
            'verify_peer' => true,
            'http_version' => '2.0',
        ]);
    }

    /**
     * انجام درخواست پروکسی با قابلیت استریم و مدیریت کلادفلر
     */
    public function request(string $url, string $method = 'GET', array $clientHeaders = [], $body = null): array
    {
        // 1. امنیت: بررسی آدرس
        if (!Security::isUrlSafe($url)) {
            return ['status' => 403, 'body' => 'Access Denied: Unsafe Host'];
        }

        // 2. آماده‌سازی هدرها (Spoofing + Sanitize)
        $headers = $this->config['cloudflare']['headers'];
        $headers['User-Agent'] = $this->config['cloudflare']['user_agents'][array_rand($this->config['cloudflare']['user_agents'])];
        
        // تزریق کوکی‌های ذخیره شده
        if ($cookies = $this->cookieJar->getHeaderForUrl($url)) {
            $headers['Cookie'] = $cookies;
        }

        // اضافه کردن هدرهای امن ورودی (بدون موارد ممنوعه)
        $cleanIncoming = Security::sanitizeHeaders($clientHeaders);
        $headers = array_merge($headers, $cleanIncoming);

        try {
            // 3. ارسال درخواست
            $response = $this->client->request($method, $url, [
                'headers' => $headers,
                'body' => $body,
            ]);

            // 4. دریافت هدرهای پاسخ
            $responseHeaders = $response->getHeaders(false);
            $statusCode = $response->getStatusCode();

            // 5. مدیریت کوکی‌های جدید (Set-Cookie)
            if (isset($responseHeaders['set-cookie'])) {
                $this->cookieJar->storeCookies($responseHeaders['set-cookie'], $url);
            }

            // 6. حذف هدرهای مزاحم (CSP, X-Frame-Options)
            $cleanResponseHeaders = [];
            $stripList = ['content-security-policy', 'x-frame-options', 'frame-options', 'content-security-policy-report-only'];
            
            foreach ($responseHeaders as $k => $v) {
                if (!in_array(strtolower($k), $stripList)) {
                    // تغییر هدرهای Location برای ریدایرکت‌ها
                    if (strtolower($k) === 'location') {
                        $v = [$this->encodeUrl($v[0])];
                    }
                    $cleanResponseHeaders[$k] = $v;
                }
            }

            // 7. بازگرداندن استریم بدنه
            return [
                'status' => $statusCode,
                'headers' => $cleanResponseHeaders,
                'stream' => $response->toStream(),
                'type' => $cleanResponseHeaders['content-type'][0] ?? 'text/html',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 502,
                'headers' => ['Content-Type' => ['text/html']],
                'stream' => $this->createErrorStream("Proxy Error: " . $e->getMessage()),
                'type' => 'text/html',
            ];
        }
    }

    /**
     * کدگذاری URL برای استفاده در آدرس‌بار پروکسی
     */
    private function encodeUrl(string $url): string
    {
        // فرمت: /proxy.php?q=<base64>
        return 'proxy.php?q=' . base64_encode($url);
    }

    private function createErrorStream(string $msg)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "<h1>Error</h1><p>$msg</p>");
        rewind($stream);
        return $stream;
    }

    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }
}
