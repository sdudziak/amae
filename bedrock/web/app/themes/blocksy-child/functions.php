<?php

if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}

add_action( 'wp_enqueue_scripts', function () {
	// 1. Load parent styles (Blocksy)
	wp_enqueue_style( 'blocksy-parent-style', get_template_directory_uri() . '/style.css' );
	// 2. Load GOOGLE FONTS (Montserrat for headings, Lato for body text - as an example of modern sans-serif)
	// This is important so we don't rely on settings stored in the database.
    wp_enqueue_style( 'amae-google-fonts', 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Montserrat:wght@300;400;500&display=swap', false );
    // 3. Load our main custom styles file (amae-styles.css)
    // We load it last to ensure it has the highest priority and overrides everything else.
    wp_enqueue_style( 'amae-custom-styles', get_stylesheet_directory_uri() . '/amae-styles.css', ['blocksy-parent-style'], '1.0.2' );
}, 999);
