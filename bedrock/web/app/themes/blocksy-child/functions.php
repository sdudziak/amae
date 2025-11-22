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
    wp_enqueue_style( 'amae-custom-styles', get_stylesheet_directory_uri() . '/amae-styles.css', array('blocksy-parent-style'), '1.0.1' );
}, 999);


if ( isset( $_GET['reset_blocksy_colors'] ) && $_GET['reset_blocksy_colors'] === 'true' ) {
    // Nazwa opcji, gdzie Blocksy przechowuje customizer settings
    // Może być 'blocksy_active_colors' lub podobna
    $option_name = 'blocksy_active_colors'; // To jest najczęstsza nazwa, ale może się różnić.
                                            // Możesz to sprawdzić w bazie danych w tabeli wp_options.

    delete_option( $option_name );
    // Blocksy przechowuje też globalne opcje w 'blocksy_options'
    $global_options = get_option('blocksy_options', []);
    if (isset($global_options['global_colors'])) {
        unset($global_options['global_colors']);
        update_option('blocksy_options', $global_options);
    }
    if (isset($global_options['color_palette'])) {
        unset($global_options['color_palette']);
        update_option('blocksy_options', $global_options);
    }

    // Możesz też spróbować usunąć całą opcję Blocksy, ale to resetuje WSZYSTKO
    delete_option('theme_mods_blocksy'); // NIEBEZPIECZNE: RESETUJE WSZYSTKO W CUSTOMIZERZE!

    wp_redirect( admin_url( 'customize.php?message=colors_reset' ) );
    exit;
}