<?php
// Load WordPress
require_once __DIR__ . '/wp-config.php';

header('Content-Type: text/plain');
echo "=== WordPress URL Detection ===\n\n";
echo "WP_HOME: " . WP_HOME . "\n";
echo "WP_SITEURL: " . WP_SITEURL . "\n";
echo "home_url(): " . home_url() . "\n";
echo "site_url(): " . site_url() . "\n";
