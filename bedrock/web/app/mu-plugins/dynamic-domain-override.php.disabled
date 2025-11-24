<?php
/**
 * Plugin Name: Dynamic Domain Auto-Update
 * Description: Automatycznie aktualizuje URL-e w bazie gdy wykryje inną domenę
 * Version: 1.0.0
 * Author: Auto-generated
 */

// Update database URLs if they don't match current domain
add_action('init', function() {
    if (!defined('WP_HOME') || !defined('WP_SITEURL')) {
        return;
    }
    
    $db_home = get_option('home');
    $db_siteurl = get_option('siteurl');
    
    // Check if database URLs match current detected URLs
    if ($db_home !== WP_HOME || $db_siteurl !== WP_SITEURL) {
        // Update database to match current domain
        update_option('home', WP_HOME);
        update_option('siteurl', WP_SITEURL);
        
        // Clear any cache
        wp_cache_flush();
    }
}, 1);

// Output buffer to replace ALL URLs in final HTML (catches everything)
// Start buffer BEFORE any options are read
add_action('muplugins_loaded', function() {
    ob_start(function($buffer) {
        if (!defined('WP_HOME') || empty($buffer)) {
            return $buffer;
        }
        
        // Only replace if the actual HTTP request is NOT from localhost
        $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $is_localhost = (strpos($current_host, 'localhost') !== false || strpos($current_host, '127.0.0.1') !== false);
        
        if ($is_localhost) {
            // Fix broken URLs that start with ://
            $buffer = preg_replace('#(href|src)=(["\'])://#', '$1=$2http://localhost:8080/#', $buffer);
            
            // Fix empty href="" and src="" by replacing with localhost URL
            $buffer = preg_replace('#(href|src)=(["\'"])\2#', '$1=$2http://localhost:8080/$2', $buffer);
            
            return $buffer;
        }
        
        // Replace http://localhost:8080 with current domain
        // Also fix malformed URLs with extra slashes
        $old_urls = [
            'http://localhost:8080',
            'https://localhost:8080',
        ];
        
        foreach ($old_urls as $old_url) {
            if (strpos($buffer, $old_url) !== false) {
                $buffer = str_replace($old_url, WP_HOME, $buffer);
            }
        }
        
        // Fix empty href="" and src="" by replacing with current domain
        $buffer = preg_replace('#(href|src)=(["\'"])\2#', '$1=$2' . WP_HOME . '/$2', $buffer);
        
        // Fix malformed URLs like http://example.com/:/// or http://example.com/://
        $buffer = preg_replace('#(https?://[^/]+)/:+/+#', '$1/', $buffer);
        
        return $buffer;
    });
}, -999); // Very early priority
