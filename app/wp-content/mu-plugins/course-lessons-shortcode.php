<?php
// --- Shortcode: lista lekcji kursu ---
add_shortcode('course_lessons', function ($atts) {
    $atts = shortcode_atts(['course_id' => 0], $atts, 'course_lessons');
    $course_id = $atts['course_id'] ? absint($atts['course_id']) : get_the_ID();
    if (!$course_id) return '';
    $q = new WP_Query([
        'post_type'      => 'lesson',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'meta_query'     => [[
            'key'   => '_audiohub_course_id',
            'value' => $course_id,
            'type'  => 'NUMERIC',
            'compare' => '='
        ]],
    ]);
    if (!$q->have_posts()) return '<p>' . esc_html__('No lessons yet.', 'audiohub') . '</p>';
    ob_start();
    echo '<ul class="ah-lesson-list">';
    while ($q->have_posts()) {
        $q->the_post();
        $req = audiohub_required_plays(get_the_ID());
        $pl = audiohub_user_lesson_plays(get_the_ID());
        $done = $pl >= $req;
        $blocked = !ah_user_has_access(get_the_ID());
        $link = $blocked ? ah_paywall_url(get_the_ID()) : get_permalink();
        echo '<li class="ah-lesson ' . ($blocked ? 'blocked' : ($done ? 'done' : '')) . '">';
        echo '<a href="' . esc_url($link) . '">' . esc_html(get_the_title()) . '</a>';
        echo ' <span class="ah-counter" data-lesson-id="' . get_the_ID() . '">' . esc_html($pl) . ' / ' . esc_html($req) . '</span>';
        echo '</li>';
    }
    wp_reset_postdata();
    echo '</ul>';
    return ob_get_clean();
});
