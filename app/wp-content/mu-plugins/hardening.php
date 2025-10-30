<?php
/*
* Minimal hardening for local/dev.
*/
if (!defined('ABSPATH')) {
    exit;
}


// Disallow file editing in the WP admin
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}


// Turn off XML-RPC on local
add_filter('xmlrpc_enabled', '__return_false');


// Hide REST API for non-logged-in users (optional; comment if you need it public)
add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }
    if (!is_user_logged_in()) {
        return new WP_Error('rest_forbidden', __('REST API restricted to logged-in users in local mode.'), array('status' => 401));
    }
    return $result;
});
