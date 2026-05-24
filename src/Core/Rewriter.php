<?php
namespace MSHW\Core;

use Masterminds\HTML5;
use DOMDocument;
use DOMXPath;
use DOMElement;

class Rewriter
{
    private HTML5 $html5;
    private string $proxyBase;

    public function __construct(string $proxyBase = '')
    {
        $this->html5 = new HTML5(['disable_html_ns' => true]);
        $this->proxyBase = rtrim($proxyBase, '/');
    }

    public function rewriteHtml(string $html, string $targetUrl): string
    {
        try {
            $dom = $this->html5->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // ۱. بازنویسی لینک‌ها و آدرس‌ها
            $attrs = ['href', 'src', 'action', 'formaction', 'data-src', 'longdesc', 'poster'];
            $tags = ['a', 'img', 'script', 'link', 'iframe', 'form', 'video', 'audio', 'source', 'track', 'embed', 'object'];
            
            foreach ($tags as $tag) {
                $nodes = $xpath->query("//{$tag}");
                foreach ($nodes as $node) {
                    if (!$node instanceof DOMElement) continue;
                    foreach ($attrs as $attr) {
                        if ($node->hasAttribute($attr)) {
                            $val = $node->getAttribute($attr);
                            $node->setAttribute($attr, $this->proxifyUrl($val, $targetUrl));
                        }
                    }
                }
            }

            // ۲. بازنویلی CSSهای اینلاین
            $styleNodes = $xpath->query('//*[@style]');
            foreach ($styleNodes as $node) {
                if ($node instanceof DOMElement) {
                    $node->setAttribute('style', $this->rewriteCss($node->getAttribute('style'), $targetUrl));
                }
            }
            $styleTags = $xpath->query('//style');
            foreach ($styleTags as $tag) {
                if ($tag->nodeValue) {
                    $tag->nodeValue = $this->rewriteCss($tag->nodeValue, $targetUrl);
                }
            }

            // ۳. تزریق اسکریپت رهگیر (برای SPAها)
            $head = $xpath->query('//head')->item(0);
            if ($head instanceof DOMElement) {
                $script = $dom->createElement('script');
                $script->setAttribute('src', '/assets/js/intercept.js');
                $script->setAttribute('data-proxy-base', $this->proxyBase);
                $head->insertBefore($script, $head->firstChild);
            }

            // ۴. تنظیم بیس تگ برای رزولوشن آدرس‌های نسبی
            $base = $dom->createElement('base');
            $base->setAttribute('href', $targetUrl);
            $head?->insertBefore($base, $head->firstChild);

            return $this->html5->saveHTML($dom);
        } catch (\Throwable $e) {
            return $html; // فال‌بک به HTML خام در صورت خطا
        }
    }

    private function rewriteCss(string $css, string $baseUrl): string
    {
        return preg_replace_callback('#url\(\s*([\'"]?)([^\'")]+)\1\s*\)#i', function($m) use ($baseUrl) {
            return "url('{$this->proxifyUrl(trim($m[2]), $baseUrl)}')";
        }, $css);
    }

    private function proxifyUrl(string $url, string $base): string
    {
        if (preg_match('#^(javascript|mailto|tel|data|blob):#i', $url)) return $url;
        
        // رزولوشن آدرس نسبی
        $absolute = $this->resolve($url, $base);
        return $this->proxyBase . '/proxy.php?q=' . base64_encode($absolute);
    }

    private function resolve(string $relative, string $base): string
    {
        if (preg_match('#^https?://#i', $relative)) return $relative;
        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ":{$parts['port']}" : '';
        
        if (str_starts_with($relative, '/')) {
            return "{$scheme}://{$host}{$port}{$relative}";
        }
        $dir = rtrim(dirname($base), '/');
        return "{$dir}/" . ltrim($relative, './');
    }
}
