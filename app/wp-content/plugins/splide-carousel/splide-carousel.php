<?php
/**
 * Plugin Name:       Splide Carousel Block
 * Description:       Carousel block
 * Requires at least: 6.5
 * Requires PHP:      7.0
 * Version:           1.7.0
 * Author:            CloudCatch LLC
 * Author URI:        https://cloudcatch.io
 * License:           MIT
 * Text Domain:       splide-carousel
 *
 * @package           CloudCatch\SplideCarousel
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function splide_carousel_block_init() {
	register_block_type( __DIR__ . '/build/carousel' );
	register_block_type( __DIR__ . '/build/carousel-item' );
}
add_action( 'init', 'splide_carousel_block_init' );
