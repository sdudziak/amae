<?php
// Simulate application.php loading
$_ENV = [];
$dotenv_file = __DIR__ . '/../../../.env';
if (file_exists($dotenv_file)) {
    $lines = file($dotenv_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

function env($key) {
    return $_ENV[$key] ?? getenv($key) ?: null;
}

header('Content-Type: text/plain');
echo "=== Simulated application.php detection ===\n\n";
echo ".env WP_HOME: " . (env('WP_HOME') ?: 'NOT SET') . "\n";
echo ".env WP_SITEURL: " . (env('WP_SITEURL') ?: 'NOT SET') . "\n\n";

if (env('WP_HOME') && env('WP_SITEURL')) {
    echo "Using explicit configuration from .env\n";
    $wphome = env('WP_HOME');
    $wpsiteurl = env('WP_SITEURL');
} else {
    echo "Using auto-detection\n";
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    } else {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    }
    
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        $host = 'localhost:8080';
    }
    
    $wphome = $scheme . '://' . $host;
    $wpsiteurl = $scheme . '://' . $host . '/wp';
}

echo "\nResult:\n";
echo "WP_HOME: $wphome\n";
echo "WP_SITEURL: $wpsiteurl\n";
