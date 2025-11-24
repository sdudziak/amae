<?php
header('Content-Type: text/plain');
echo "=== $_SERVER variables during config ===\n\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_HOST: " . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] .  'not set') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "\n";

// Simulate detection logic
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
} else {
    $host = 'localhost:8080';
}

echo "\nDetected host would be: $host\n";
