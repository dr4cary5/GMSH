<?php
namespace MSHW\Utils;

class Security
{
    /**
     * بررسی ایمن بودن آدرس (جلوگیری از SSRF)
     */
    public static function isUrlSafe(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['host'])) return false;

        $host = strtolower($parts['host']);
        
        // بررسی لیست سیاه هاست‌ها
        $config = require __DIR__ . '/../../config/app.php';
        foreach ($config['security']['blocked_hosts'] as $blocked) {
            if ($host === $blocked) return false;
        }

        // بررسی رنج IPهای خصوصی
        $ip = gethostbyname($host);
        foreach ($config['security']['blocked_ip_ranges'] as $range) {
            if (str_starts_with($ip, $range)) return false;
        }

        return true;
    }

    /**
     * پاکسازی هدرهای ورودی (حذف موارد حساس)
     */
    public static function sanitizeHeaders(array $headers): array
    {
        $config = require __DIR__ . '/../../config/app.php';
        $clean = [];
        $strip = array_map('strtolower', $config['security']['strip_headers']);

        foreach ($headers as $key => $value) {
            if (!in_array(strtolower($key), $strip)) {
                // جلوگیری از تزریق هدر (CRLF Injection)
                if (is_string($value) && !preg_match("/[\r\n]/", $value)) {
                    $clean[$key] = $value;
                }
            }
        }
        return $clean;
    }
}
