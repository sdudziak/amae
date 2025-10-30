<?php

/**
 * Shortcode: [protected_audio path="audio/episode-01.mp3" ttl="180" class="my-player"]
 * Generates a short‑lived signed <audio> tag tied to the current user session.
 */
if (!defined('ABSPATH')) {
    exit;
}


add_shortcode('protected_audio', function ($atts) {
    $atts = shortcode_atts([
        'path' => '', // relative under /protected/
        'ttl' => 180, // seconds
        'class' => '',
        'preload' => 'metadata',
        'lesson_id' => 0
    ], $atts, 'protected_audio');
    $lesson_id = (int) $atts['lesson_id'];
    if (!$lesson_id && get_post_type() === 'lesson') {
        $lesson_id = get_the_ID();
    }
    $lesson_attr = $lesson_id ? ' data-lesson-id="'.esc_attr($lesson_id).'"' : '';

    if (!is_user_logged_in() || empty($atts['path'])) {
        return '';
    }

    $uid = get_current_user_id();
    $session = function_exists('wp_get_session_token') ? wp_get_session_token() : '';

    $rel = ltrim(wp_normalize_path($atts['path']), '/');
    $exp = time() + max(30, intval($atts['ttl'])); // min 30s
    $data = $uid . '|' . $rel . '|' . $exp . '|' . $session . '|' . $lesson_id; // + lesson_id
    $sig  = hash_hmac('sha256', $data, wp_salt('auth'));
    $src  = home_url('/protected/' . $rel . '?exp=' . $exp . '&sig=' . $sig . '&lesson=' . (int)$lesson_id);

    // controlsList is advisory but helps on Chromium
    $lesson_attr = $lesson_id ? ' data-lesson-id="'.esc_attr($lesson_id).'"' : '';
    $attrs = sprintf(
        'controls preload="%s" controlsList="nodownload noplaybackrate"',
        esc_attr($atts['preload'])
    );


    $classes = esc_attr($atts['class']);


    $html = '<audio ' . $attrs . ' class="' . $classes . '" src="' . esc_url($src). '"'.$lesson_attr.'></audio>';
    $html .= '<script>
        (function(){
            var a=document.currentScript.previousElementSibling; if(!a) return;
            a.addEventListener("contextmenu", function(e){ e.preventDefault(); }, false);
        })();
    </script>';


    return $html;
});
