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
        <form id="url-form" class="url-input" autocomplete="off">
            <input type="text" id="address-bar" value="<?= htmlspecialchars($startUrl) ?>" placeholder="Enter URL or search...">
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

        <iframe id="content-frame" src="/proxy.php?q=<?= $encodedStart ?>" sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-modals" frameborder="0"></iframe>
    </main>

    <script src="/assets/js/shell.js"></script>
</body>
</html>
