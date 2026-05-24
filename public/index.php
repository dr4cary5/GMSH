<?php
$config = require __DIR__ . '/../config/app.php';
$title = $config['ui']['title'];
$theme = $config['ui']['theme'];
$startUrl = $_GET['url'] ?? 'https://example.com';
$encodedStart = base64_encode($startUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/assets/css/shell.css">
</head>
<body class="<?= $theme ?>">
    <header class="browser-bar">
        <div class="nav-controls">
            <button id="btn-back" title="Back">←</button>
            <button id="btn-forward" title="Forward">→</button>
            <button id="btn-reload" title="Reload">↻</button>
        </div>
        <form id="url-form" class="url-input" autocomplete="off" style="flex:1; display:flex; gap:0.5rem;">
              <input type="text" id="address-bar" value="<?= htmlspecialchars($startUrl) ?>"
                  placeholder="example.com یا https://..." style="flex:1;" autofocus>
      <button type="submit" style="background:var(--accent); color:#fff; border:none; padding:0.4rem 1rem; border-radius:4px; cursor:pointer;">
        Go
    </button>
</form>
        <div class="window-controls">⋮</div>
    </header>

    <main class="browser-layout">
        <aside class="sidebar">
            <h3>🍪 Cookies</h3>
            <pre id="cookie-list" class="cookie-view">Loading...</pre>
            <button id="btn-clear-cookies" class="btn-sm">Clear All</button>
            
            <h3>⚙️ Settings</h3>
            <label><input type="checkbox" id="toggle-js" checked> Enable JS Interceptor</label>
            <label><input type="checkbox" id="toggle-cf" checked> Cloudflare Headers</label>
        </aside>

       <!-- 
  تغییرات: 
  1. حذف allow-same-origin برای رفع هشدار (اگر سایت مقصد به کوکی‌های iframe نیاز نداشت)
  2. افزودن allow-popups-to-escape-sandbox برای باز شدن لینک‌های جدید
  نکته: اگر سایتی لود نشد، allow-same-origin را برگردانید.
-->
<iframe id="content-frame" 
        src="/proxy.php?q=<?= $encodedStart ?>" 
        sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-modals allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation" 
        frameborder="0"
        style="width:100%; height:100%; border:none; background:#fff;">
</iframe>

    <script src="/assets/js/shell.js"></script>
</body>
</html>
