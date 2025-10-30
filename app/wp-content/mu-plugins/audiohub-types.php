<?php

// --- CPT: course, lesson ---
add_action('init', function () {
    register_post_type('course', [
        'label' => __('Courses', 'audiohub'),
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
        'rewrite' => ['slug' => 'courses'],
    ]);

    register_post_type('lesson', [
        'label' => __('Lessons', 'audiohub'),
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'hierarchical' => true, // pozwala wybrać „Parent” = course
        'menu_icon' => 'dashicons-controls-play',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions'],
        'rewrite' => ['slug' => 'lessons'],
    ]);

    register_taxonomy('course_cat', ['course', 'lesson'], [
        'hierarchical'      => true,
        'labels'            => ['name' => __('Course categories', 'audiohub')],
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'course-category'],
    ]);

});