# MSHW-RemoteBrowser
Modern ephemeral proxy browser for GitHub Actions + ngrok.

## 🚀 Deploy
1. Set `NGROK_AUTH_TOKEN` in repo Secrets.
2. Run workflow `Deploy MSHW-proxy`.
3. Access via generated ngrok URL.

## 🌐 Usage
Enter any URL in the address bar. SPA navigation, cookies, and Cloudflare bypass are handled automatically.

MSHW-RemoteBrowser/
├── .github/workflows/
│   └── deploy.yml              # استقرار خودکار (PHP + ngrok + Health)
│
├── public/
│   ├── index.php               # شل اصلی مرورگر (آدرس‌بار + سایدبار + فریم)
│   ├── proxy.php               # هندلر درخواست‌های پروکسی (اتصال به Core)
│   └── assets/
│       ├── css/shell.css       # استایل‌های مرورگرگونه (Fixed Top Bar, Sidebar)
│       └── js/
│           ├── shell.js        # مدیریت آدرس‌بار، فریم، همگام‌سازی URL
│           └── intercept.js    # رهگیری fetch/XHR/History + Injection به DOM
│
├── src/
│   ├── Core/
│   │   ├── ProxyEngine.php     # موتور درخواست/پاسخ (Symfony Client + Stream)
│   │   ├── Rewriter.php        # بازنویسی HTML/CSS/JS (DOM + Regex هوشمند)
│   │   └── CookieJar.php       # مدیریت کوکی در RAM (RFC 6265، همگام با درخواست)
│   └── Utils/
│       ├── UrlCodec.php        # انکود/دیکود ایمن آدرس‌ها
│       └── Security.php        # SSRF Block، Header Sanitizer، CSP Strip
│
├── config/
│   └── app.php                 # تنظیمات مرکزی (پورت، تایم‌اوت، استراتژی CF)
│
├── .env.example
├── .gitignore
├── composer.json
└── README.md
