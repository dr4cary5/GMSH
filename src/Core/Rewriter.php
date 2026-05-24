<?php
namespace MSHW\Core;

class Rewriter
{
    private string $proxyBase;

    public function __construct(string $proxyBase = '')
    {
        $this->proxyBase = rtrim($proxyBase, '/');
    }

    public function rewriteHtml(string $html, string $targetUrl): string
    {
        // اگر HTML خالی یا خیلی کوتاه بود، برگردان بدون تغییر
        if (empty($html) || strlen($html) < 50) {
            return $html;
        }

        try {
            // استفاده از DOMDocument ساده‌تر و پایدارتر
            $dom = new \DOMDocument();
            @$dom->loadHTML(
                mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            $xpath = new \DOMXPath($dom);
            
            // بازنویسی لینک‌های اصلی
            $this->rewriteAttributes($xpath, ['a' => 'href', 'img' => 'src', 'script' => 'src', 'link' => 'href', 'iframe' => 'src', 'form' => 'action'], $targetUrl);
            
            // بازنویسی CSSهای اینلاین
            $this->rewriteInlineStyles($xpath, $targetUrl);
            
            // تزریق اسکریپت رهگیر (فقط اگر <head> وجود داشت)
            $head = $xpath->query('//head')->item(0);
            if ($head) {
                $script = $dom->createElement('script');
                $script->setAttribute('src', $this->proxyBase . '/assets/js/intercept.js');
                $script->setAttribute('data-proxy-base', $this->proxyBase);
                $head->insertBefore($script, $head->firstChild);
            }

            // برگرداندن HTML با حفظ encoding
            $output = $dom->saveHTML();
            return $output ?: $html; // فال‌بک به خام اگر saveHTML شکست خورد
            
        } catch (\Throwable $e) {
            // در صورت هر خطا، برگرداندن HTML خام + لاگ خطا در کنسول سرور
            error_log("Rewriter error: " . $e->getMessage());
            return $html;
        }
    }

    private function rewriteAttributes(\DOMXPath $xpath, array $map, string $baseUrl): void
    {
        foreach ($map as $tag => $attr) {
            $nodes = $xpath->query("//{$tag}[@{$attr}]");
            foreach ($nodes as $node) {
                if (!$node instanceof \DOMElement) continue;
                $val = $node->getAttribute($attr);
                if ($val && !preg_match('#^(javascript|mailto|tel|data|blob):#i', $val)) {
                    $node->setAttribute($attr, $this->proxifyUrl($val, $baseUrl));
                }
            }
        }
    }

    private function rewriteInlineStyles(\DOMXPath $xpath, string $baseUrl): void
    {
        $nodes = $xpath->query('//*[@style]');
        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $style = $node->getAttribute('style');
                // بازنویلی ساده url() در CSS
                $style = preg_replace_callback('#url\(\s*([\'"]?)([^\'")]+)\1\s*\)#i', function($m) use ($baseUrl) {
                    return "url('{$this->proxifyUrl(trim($m[2]), $baseUrl)}')";
                }, $style);
                $node->setAttribute('style', $style);
            }
        }
    }

    private function proxifyUrl(string $url, string $base): string
    {
        if (preg_match('#^(https?://|/)#i', $url)) {
            // آدرس مطلق یا پروتکل‌دار
            $absolute = $url;
            if (!preg_match('#^https?://#i', $url)) {
                // آدرس نسبی به ریشه (مثل /img/logo.png)
                $parts = parse_url($base);
                $absolute = "{$parts['scheme']}://{$parts['host']}" . (isset($parts['port']) ? ":{$parts['port']}" : '') . $url;
            }
        } else {
            // آدرس نسبی به مسیر فعلی
            $absolute = rtrim(dirname($base), '/') . '/' . ltrim($url, './');
        }
        return $this->proxyBase . '/proxy.php?q=' . base64_encode($absolute);
    }

    public function rewriteCss(string $css, string $baseUrl): string
    {
        return preg_replace_callback('#url\(\s*([\'"]?)([^\'")]+)\1\s*\)#i', function($m) use ($baseUrl) {
            return "url('{$this->proxifyUrl(trim($m[2]), $baseUrl)}')";
        }, $css);
    }
}
