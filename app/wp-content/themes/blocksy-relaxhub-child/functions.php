<?php
if (!defined('ABSPATH')) {
    exit;
}

define('RELAXHUB_CHILD_VER', '1.2.0');

/**
 * Enqueue styles & scripts
 */
add_action('wp_enqueue_scripts', function () {
    // Parent (Blocksy)
    wp_enqueue_style('blocksy-parent', get_template_directory_uri() . '/style.css', [], null);
    // Child
    wp_enqueue_style('relaxhub-theme', get_stylesheet_directory_uri() . '/assets/css/theme.css', ['blocksy-parent'], RELAXHUB_CHILD_VER);
    wp_enqueue_script('relaxhub-motif', get_stylesheet_directory_uri() . '/assets/js/motif.js', [], RELAXHUB_CHILD_VER, true);
}, 20);

/**
 * Register block pattern "Recommended Courses"
 */
require_once get_stylesheet_directory() . '/inc/patterns-category.php';


/**
 * Body class for easy scoping
 */
add_filter('body_class', function ($classes) {
    $classes[] = 'rh-theme';
    return $classes;
});

/**
 * Output side motif and dark-mode toggle button
 */
add_action('wp_body_open', function () {
    if (is_admin()) return; ?>
    <aside class="rh-motif rh-motif--right" aria-hidden="true">
        <div class="rh-motif__layer rh-motif__layer--back" data-speed="0.20">
            <?php @include get_stylesheet_directory() . '/assets/svg/motif-wide-fade.svg'; ?>
        </div>
        <div class="rh-motif__layer rh-motif__layer--mid" data-speed="0.30">
            <?php @include get_stylesheet_directory() . '/assets/svg/motif-wide-ink.svg'; ?>
        </div>
        <div class="rh-motif__layer rh-motif__layer--front" data-speed="0.45">
            <?php @include get_stylesheet_directory() . '/assets/svg/motif-wide-brush.svg'; ?>
        </div>
    </aside>
    <button class="rh-dark-toggle" type="button" aria-pressed="false">
        <span class="rh-dark-toggle__sun">☀️</span>
        <span class="rh-dark-toggle__moon">🌙</span>
    </button>
<?php });

add_action('wp_head', function () {
    $dir = get_stylesheet_directory_uri() . '/assets/logo';
    echo '<link rel="icon" type="image/png" sizes="16x16" href="' . $dir . '/favicon-16.png" />' . "\n";
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . $dir . '/favicon-32.png" />' . "\n";
    echo '<link rel="icon" type="image/png" sizes="48x48" href="' . $dir . '/favicon-48.png" />' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . $dir . '/apple-touch-icon.png" />' . "\n";
});

add_filter('get_custom_logo', function ($html) {
    $dir  = get_stylesheet_directory_uri() . '/assets/logo';
    $light = $dir . '/logo-enso-light.svg';
    $dark  = $dir . '/logo-enso-dark.svg';

    $out = '<a href="' . esc_url(home_url('/')) . '" class="custom-logo-link" rel="home">';
    $out .= '<picture>';
    $out .= '<source media="(prefers-color-scheme: dark)" srcset="' . esc_url($dark) . '">';
    $out .= '<img src="' . esc_url($light) . '" class="custom-logo" alt="' . esc_attr(get_bloginfo('name')) . '">';
    $out .= '</picture>';
    $out .= '</a>';

    return $out;
}, 10, 1);

add_theme_support('custom-logo', [
  'height'      => 120,
  'width'       => 120,
  'flex-height' => true,
  'flex-width'  => true,
]);
