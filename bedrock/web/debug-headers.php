<?php
// Debug headers
header('Content-Type: text/plain');
echo "=== PHP \$_SERVER Debug ===\n\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0 || strpos($key, 'FORWARDED') !== false || in_array($key, ['HTTP_HOST', 'SERVER_NAME', 'HTTPS'])) {
        echo "$key = $value\n";
    }
}
