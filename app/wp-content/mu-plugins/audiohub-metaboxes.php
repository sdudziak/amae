<?php

add_action('add_meta_boxes', function () {

    add_meta_box('audiohub_required_plays', __('Required listens', 'audiohub'), function ($post) {
        $val = (int) get_post_meta($post->ID, '_audiohub_required_plays', true);
        if ($val < 1) $val = 1;
        echo '<label>' . esc_html__('Times to listen:', 'audiohub') . '</label> ';
        echo '<input type="number" min="1" step="1" name="audiohub_required_plays" value="' . esc_attr($val) . '" style="width:120px">';
        echo '<p class="description">' . esc_html__('How many times a learner should listen to mark this lesson complete.', 'audiohub') . '</p>';
    }, 'lesson', 'side', 'high');

    add_meta_box('audiohub_course_box', __('Course', 'audiohub'), function ($post) {
        $selected = (int) get_post_meta($post->ID, '_audiohub_course_id', true);
        $courses = get_posts([
            'post_type'      => 'course',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);
        echo '<select name="audiohub_course_id" style="width:100%">';
        echo '<option value="0">' . esc_html__('-- Select course --', 'audiohub') . '</option>';
        foreach ($courses as $cid) {
            $title = get_the_title($cid);
            printf(
                '<option value="%d"%s>%s</option>',
                $cid,
                selected($selected, $cid, false),
                esc_html($title)
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Assign this lesson to a course.', 'audiohub') . '</p>';
    }, 'lesson', 'side', 'high');

    add_meta_box('ah_paid_course', __('Payment', 'audiohub'), function ($post) {
        $paid = get_post_meta($post->ID, '_ah_paid', true);
        if ($paid === '') $paid = '1'; // domyślnie płatny
        echo '<label><input type="checkbox" name="ah_paid" value="1" ' . ($paid == '1' ? 'checked="1"' : "" ) . '> ';
        echo esc_html__('This course is paid (default).', 'audiohub') . '</label>';
    }, 'course', 'side', 'high');

    add_meta_box('ah_tags_box', __('Tags', 'audiohub'), function ($post) {
        $assigned_tags = wp_get_object_terms($post->ID, 'post_tags', ['fields' => 'ids']);
        $tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
        ]);

        echo '<div style="max-height: 200px; overflow-y: auto;">';
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                printf(
                    '<label style="display:block;margin-bottom:5px;"><input type="checkbox" name="audiohub_tags[]" value="%d"%s> %s</label>',
                    $tag->term_id,
                    in_array($tag->term_id, $assigned_tags) ? ' checked' : '',
                    esc_html($tag->name)
                );
            }
        } else {
            echo '<p>' . esc_html__('No tags available. Add some tags first.', 'audiohub') . '</p>';
        }
        echo '</div>';
        echo '<p class="description">' . esc_html__('Select tags for this item.', 'audiohub') . '</p>';
    }, ['course', 'lesson'], 'side', 'low');
});
