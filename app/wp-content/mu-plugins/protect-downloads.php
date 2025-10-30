<?php

/**
 * Protected audio streaming with short‑lived signed URLs and stricter headers.
 * Route: /protected/<subpath>/<file>?exp=<timestamp>&sig=<hmac>
 */
if (!defined('ABSPATH')) {
    exit;
}


add_action('init', function () {
    add_rewrite_rule('^protected/(.+)$', 'index.php?protected_file=$matches[1]', 'top');
});


add_filter('query_vars', function ($vars) {
    $vars[] = 'protected_file';
    return $vars;
});

add_action('template_redirect', function () {
    $rel = get_query_var('protected_file');
    if (!$rel) {
        return;
    }

    // Must be logged in
    if (!is_user_logged_in()) {
        auth_redirect();
        exit;
    }

    // Optional: require PMPro membership
    if (function_exists('pmpro_hasMembershipLevel') && !pmpro_hasMembershipLevel()) {
        status_header(403);
        wp_die(__('You need an active membership to access this file.'), 403);
    }

    // Sanitize path
    $rel = wp_normalize_path($rel);
    $rel = ltrim($rel, "/" . chr(92)); // trim leading '/' and '\'
    $rel_parts = array_filter(explode('/', $rel), function ($p) {
        return $p !== '' && $p !== '.' && $p !== '..';
    });

    $safe_rel = implode('/', $rel_parts);
    $base = defined('PROTECTED_PATH') ? PROTECTED_PATH : ABSPATH . '../protected';
    $path = wp_normalize_path($base . '/' . $safe_rel);

    if (!file_exists($path) || !is_file($path)) {
        status_header(404);
        echo esc_html__('File not found.');
        exit;
    }

    // ---- Signed URL validation ----
    $uid = get_current_user_id();
    $exp = isset($_GET['exp']) ? absint($_GET['exp']) : 0;
    $sig = isset($_GET['sig']) ? sanitize_text_field(wp_unslash($_GET['sig'])) : '';
    $session = function_exists('wp_get_session_token') ? wp_get_session_token() : '';

    $lesson_id = (int) ($atts['lesson_id'] ?? 0);
    if (!$lesson_id && get_post_type() === 'lesson') {
        $lesson_id = get_the_ID();
    }

    // Allow max TTL 10 minutes window and not in the past
    if ($exp < time() || $exp > time() + 600) {
        status_header(403);
        echo 'Link expired.';
        exit;
    }
    $data = $uid . '|' . $rel . '|' . $exp . '|' . $session . '|' . $lesson_id; // + lesson_id
    $expected = hash_hmac('sha256', $data, wp_salt('auth'));
    if (!$sig || !hash_equals($expected, $sig)) {
        status_header(403);
        echo 'Invalid signature.';
        exit;
    }

    // twardy gate: bez dostępu -> checkout
    if ($lesson_id && function_exists('ah_user_has_access') && !ah_user_has_access($lesson_id)) {
        wp_redirect(ah_paywall_url($lesson_id));
        exit;
    }


    // Optional extra friction: same-origin referer (if sent by browser)
    $ref = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
    if ($ref) {
        $ref_host = wp_parse_url($ref, PHP_URL_HOST);
        $home_host = wp_parse_url(home_url(), PHP_URL_HOST);
        if ($ref_host && $home_host && !hash_equals($ref_host, $home_host)) {
            status_header(403);
            echo 'Cross-origin blocked.';
            exit;
        }
    }


    // ---- Headers ----
    $size = filesize($path);
    $ft = wp_check_filetype($path);
    $mime = $ft['type'] ?: 'audio/mpeg';


    header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; media-src 'self' blob:; frame-ancestors 'self'");
    header('Referrer-Policy: same-origin');


    // No persistent caching in browser/disk
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');


    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');

    $start = 0;
    $end = $size - 1;
    $length = $size;


    // Manual Range parsing (no regex, keeps code portable)
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = trim(str_ireplace('bytes=', '', $_SERVER['HTTP_RANGE']));
        $parts = explode('-', $range, 2);
        if (count($parts) === 2) {
            if ($parts[0] !== '') {
                $start = max(0, intval($parts[0]));
            }
            if ($parts[1] !== '') {
                $end = min($size - 1, intval($parts[1]));
            }
            if ($start > $end || $start >= $size) {
                status_header(416);
                exit;
            }
            $length = $end - $start + 1;
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        }
    }


    header('Content-Length: ' . $length);
    header('Content-Disposition: inline; filename="' . basename($path) . '"');


    $fp = fopen($path, 'rb');
    if ($start > 0) {
        fseek($fp, $start);
    }


    $chunk = 8192;
    $sent = 0;
    while (!feof($fp) && $sent < $length) {
        $buf = fread($fp, min($chunk, $length - $sent));
        echo $buf;
        $sent += strlen($buf);
        @ob_flush();
        flush();
    }
    fclose($fp);
    exit;
});
