<?php
// Load WordPress
require_once __DIR__ . '/wp/wp-load.php';

header('Content-Type: text/plain');
echo "=== WordPress URL Configuration ===\n\n";
echo "WP_HOME constant: " . (defined('WP_HOME') ? WP_HOME : 'NOT DEFINED') . "\n";
echo "WP_SITEURL constant: " . (defined('WP_SITEURL') ? WP_SITEURL : 'NOT DEFINED') . "\n";
echo "home option (DB): " . get_option('home') . "\n";
echo "siteurl option (DB): " . get_option('siteurl') . "\n";
echo "home_url(): " . home_url() . "\n";
echo "site_url(): " . site_url() . "\n";
