<?php

add_shortcode('course_list', function ($atts) {
    $atts = shortcode_atts(['columns' => 3, 'status' => 'all'], $atts, 'course_list');
    $want = $atts['status'];
    $q = new WP_Query([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    if (!$q->have_posts()) return '<p>' . esc_html__('No courses yet.', 'audiohub') . '</p>';
    ob_start();
    echo '<div class="ah-grid ah-grid-cols-' . (int)$atts['columns'] . '">';
    while ($q->have_posts()) {
        $q->the_post();
        $st = audiohub_course_status(get_the_ID());
        if ($want !== 'all' && $st['status'] !== $want) continue;
        $badge = $st['status'] === 'completed' ? 'completed' : ($st['status'] === 'in_progress' ? 'in-progress' : 'unstarted');
        $url = get_permalink();
        $blocked = !ah_user_has_access(get_the_ID());
        if ($blocked) {
            $url = ah_paywall_url(get_the_ID());
        }

        echo '<article class="ah-card' . ($blocked ? ' ah-blocked' : '') . '">';
        if (has_post_thumbnail()) echo '<div class="ah-thumb">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</div>';
        echo '<h3 class="ah-title"><a href="' . esc_url($url) . '">' . esc_html(get_the_title()) . '</a></h3>';
        if ($blocked) {
            echo '<div class="ah-badge ah-' . esc_attr($badge) . '">Blocked</div>';
        } else {
            echo '<div class="ah-badge ah-' . esc_attr($badge) . '">' . esc_html(ucwords(str_replace('_', ' ', $st['status']))) . '</div>';
            echo '<div class="ah-meta">' . esc_html($st['completed'] . ' / ' . $st['total'] . ' lessons complete') . '</div>';
        }
        echo '</article>';
    }
    wp_reset_postdata();
    echo '</div>';
    return ob_get_clean();
});
