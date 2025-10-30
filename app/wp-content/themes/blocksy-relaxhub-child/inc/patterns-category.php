<?php

if (!defined('ABSPATH')) { exit; }
add_action('init', function () {
  if ( function_exists('register_block_pattern_category') ) {
    register_block_pattern_category('relaxhub', ['label' => __('RelaxHub','relaxhub')]);
  }
});
