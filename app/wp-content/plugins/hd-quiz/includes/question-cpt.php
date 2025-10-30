<?php
function hdq_regsiter_custom_post_type_questions()
{
    $labels = array(
        'name'                => _x('Questions', 'Post Type General Name', 'text_domain'),
        'singular_name'       => _x('HD Quiz', 'Post Type Singular Name', 'text_domain'),
        'menu_name'           => __('HD Quiz', 'text_domain'),
        'name_admin_bar'      => __('HD Quiz', 'text_domain')
    );
    $args = array(
        'label'               => __('HD Quiz', 'text_domain'),
        'labels'              => $labels,
        'supports'            => array('title', 'thumbnail', 'quiz'),
        'hierarchical'        => false,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-clipboard',
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'page',
    );
    register_post_type('post_type_questionna', $args); // I wish I didn't name this something so stupid. 2015 Dylan was a dummy. He was young a reckless.
}
add_action('init', 'hdq_regsiter_custom_post_type_questions', 0);
