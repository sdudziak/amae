<?php

add_filter('query_vars', function ($vars) {
    $vars[] = 'ah_course';
    return $vars;
});

// Generuj linki lekcji z kursem w ścieżce
add_filter('post_type_link', function ($permalink, $post, $leavename, $sample) {
    if ($post->post_type === 'lesson') {
        $cid = audiohub_get_course_id($post->ID); // helper z naszej wtyczki
        if ($cid) {
            $cslug = get_post_field('post_name', $cid);
            return home_url(user_trailingslashit('courses/' . $cslug . '/' . $post->post_name));
        }
    }
    return $permalink;
}, 10, 4);

// Kolumna "Course" w /wp-admin/edit.php?post_type=lesson
add_filter('manage_lesson_posts_columns', function ($cols) {
    $cols['ah_course'] = __('Course', 'audiohub');
    return $cols;
});

// Zastosowanie filtra do zapytań listy
add_filter('parse_query', function ($q) {
    global $pagenow;
    if ($pagenow !== 'edit.php' || ($q->get('post_type') !== 'lesson')) return;
    $cid = isset($_GET['ah_course_filter']) ? (int) $_GET['ah_course_filter'] : 0;
    if ($cid > 0) {
        $q->set('meta_key', '_audiohub_course_id');
        $q->set('meta_value', $cid);
    }
});

