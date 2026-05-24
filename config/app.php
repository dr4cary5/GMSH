<?php
// تنظیمات مرکزی پروژه (God Mode Config)
return [
    'proxy' => [
        'port' => getenv('PROXY_PORT') ?: 8080,
        'timeout' => 30,
        'max_redirects' => 5,
        'stream_chunk_size' => 8192,
    ],
    'security' => [
        // لیست سیاه برای جلوگیری از دسترسی به شبکه داخلی (SSRF Protection)
        'blocked_hosts' => ['localhost', '127.0.0.1', '0.0.0.0', '::1'],
        'blocked_ip_ranges' => ['10.', '192.168.', '172.16.'],
        // هدرهایی که هرگز نباید به سرور مقصد ارسال شوند
        'strip_headers' => ['host', 'origin', 'referer', 'cookie', 'authorization'],
    ],
    'cloudflare' => [
        'strategy' => getenv('CF_STRATEGY') ?: 'auto',
        'user_agents' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
        ],
        'headers' => [
            'sec-ch-ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'document',
            'sec-fetch-mode' => 'navigate',
            'sec-fetch-site' => 'none',
            'sec-fetch-user' => '?1',
            'upgrade-insecure-requests' => '1',
        ],
    ],
    'ui' => [
        'title' => 'MSHW-RemoteBrowser',
        'theme' => 'dark', // dark | light
    ]
];
