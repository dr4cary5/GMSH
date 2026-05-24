<?php
namespace MSHW\Core;

class CookieJar
{
    private array $cookies = [];

    public function storeCookies(array $setCookieHeaders, string $requestUrl): void
    {
        $domain = parse_url($requestUrl, PHP_URL_HOST);
        
        foreach ($setCookieHeaders as $header) {
            // پارس ساده کوکی: name=value; Domain=...; Path=...
            if (preg_match('/^([^=]+)=([^;]+)/', $header, $matches)) {
                $name = $matches[1];
                $value = $matches[2];
                $key = "{$domain}/{$name}";
                $this->cookies[$key] = $value;
            }
        }
    }

    public function getHeaderForUrl(string $url): ?string
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $matches = [];
        
        // پیدا کردن کوکی‌های مربوط به این دامنه
        foreach ($this->cookies as $key => $value) {
            if (strpos($key, $domain) !== false) {
                $cookieName = explode('/', $key)[1];
                $matches[] = "{$cookieName}={$value}";
            }
        }
        
        return empty($matches) ? null : implode('; ', $matches);
    }
    
    // متدهای کمکی برای داشبورد/سایدبار
    public function getAll(): array { return $this->cookies; }
    public function clear(): void { $this->cookies = []; }
}
