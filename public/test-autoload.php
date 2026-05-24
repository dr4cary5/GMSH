<?php
require_once __DIR__ . '/../vendor/autoload.php';

echo "Testing autoload...<br>";

try {
    $engine = new \MSHW\Core\ProxyEngine();
    echo "✅ ProxyEngine loaded successfully<br>";
} catch (Throwable $e) {
    echo "❌ ProxyEngine failed: " . $e->getMessage() . "<br>";
}

try {
    $jar = new \MSHW\Core\CookieJar();
    echo "✅ CookieJar loaded successfully<br>";
} catch (Throwable $e) {
    echo "❌ CookieJar failed: " . $e->getMessage() . "<br>";
}

echo "Done.";
