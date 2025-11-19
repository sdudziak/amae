<?php

/**
 * Plugin Name: AudioHub Courses (MU)
 * Description: CPT „course” i „lesson”, liczniki odtworzeń per użytkownik, shortcody listujące kursy i lekcje.
 */
if (!defined('ABSPATH')) {
    exit;
}


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
});

// --- Meta: wymagana liczba odsłuchań dla lekcji ---
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
});


add_action('save_post_lesson', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['audiohub_required_plays'])) {
        $v = max(1, (int) $_POST['audiohub_required_plays']);
        update_post_meta($post_id, '_audiohub_required_plays', $v);
    }
    if (isset($_POST['audiohub_course_id'])) {
        $cid = max(0, (int) $_POST['audiohub_course_id']);
        update_post_meta($post_id, '_audiohub_course_id', $cid);
    }
});

// --- Helpers: odczyt liczników ---
function audiohub_required_plays($lesson_id)
{
    $r = (int) get_post_meta($lesson_id, '_audiohub_required_plays', true);
    return $r > 0 ? $r : 1;
}
function audiohub_user_lesson_plays($lesson_id, $user_id = 0)
{
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) return 0;
    return (int) get_user_meta($user_id, 'audiohub_plays_' . $lesson_id, true);
}
function audiohub_get_course_id($lesson_id)
{
    return (int) get_post_meta($lesson_id, '_audiohub_course_id', true);
}

// --- AJAX: zgłoszenie zakończonego odtworzenia lekcji ---
add_action('wp_ajax_audiohub_lesson_play', function () {
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'not_logged_in'], 403);
    check_ajax_referer('audiohub_progress', 'nonce');
    $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
    if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') wp_send_json_error(['message' => 'bad_lesson'], 400);


    $user_id = get_current_user_id();
    $key = 'audiohub_plays_' . $lesson_id;
    $count = (int) get_user_meta($user_id, $key, true);
    $count++;
    update_user_meta($user_id, $key, $count);

    $gkey = '_audiohub_global_plays';
    $g = (int) get_post_meta($lesson_id, $gkey, true);
    update_post_meta($lesson_id, $gkey, $g + 1);

    $required = audiohub_required_plays($lesson_id);
    wp_send_json_success([
        'plays' => $count,
        'required' => $required,
        'done' => ($count >= $required),
    ]);
});


// --- Status kursu dla użytkownika ---
function audiohub_course_status($course_id, $user_id = 0)
{
    $user_id = $user_id ?: get_current_user_id();
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
    $total = (int) $q->found_posts;
    if ($total === 0) return ['status' => 'unstarted', 'total' => 0, 'completed' => 0];


    $completed = 0;
    $touched = false;
    foreach ($q->posts as $p) {
        $req = audiohub_required_plays($p->ID);
        $plays = audiohub_user_lesson_plays($p->ID, $user_id);
        if ($plays > 0) $touched = true;
        if ($plays >= $req) $completed++;
    }
    if ($completed === $total) $status = 'completed';
    elseif ($touched) $status = 'in_progress';
    else $status = 'unstarted';


    return ['status' => $status, 'total' => $total, 'completed' => $completed];
}

// --- Shortcode: lista kursów ---
// ZAMIANA funkcji shortcode 'course_list' na wersję z filtrem 'status'
add_shortcode('course_list', function($atts){
  $atts = shortcode_atts(['columns'=>3,'status'=>'all'], $atts, 'course_list');
  $want = $atts['status'];
  $q = new WP_Query([
    'post_type'=>'course','posts_per_page'=>-1,'post_status'=>'publish',
    'orderby'=>'title','order'=>'ASC'
  ]);
  if (!$q->have_posts()) return '<p>'.esc_html__('No courses yet.','audiohub').'</p>';
  ob_start();
  echo '<div class="ah-grid ah-grid-cols-'.(int)$atts['columns'].'">';
  while($q->have_posts()){ $q->the_post();
    $st = audiohub_course_status(get_the_ID());
    if ($want!=='all' && $st['status']!==$want) continue;
    $badge = $st['status']==='completed' ? 'completed' : ($st['status']==='in_progress'?'in-progress':'unstarted');
    echo '<article class="ah-card">';
    if (has_post_thumbnail()) echo '<div class="ah-thumb">'.get_the_post_thumbnail(get_the_ID(),'medium').'</div>';
    echo '<h3 class="ah-title"><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></h3>';
    echo '<div class="ah-badge ah-'.esc_attr($badge).'">'.esc_html(ucwords(str_replace('_',' ',$st['status']))).'</div>';
    echo '<div class="ah-meta">'.esc_html($st['completed'].' / '.$st['total'].' lessons complete').'</div>';
    echo '</article>';
  }
  wp_reset_postdata();
  echo '</div>';
  return ob_get_clean();
});



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
        echo '<li class="ah-lesson ' . ($done ? 'done' : '') . '">';
        echo '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
        echo ' <span class="ah-counter" data-lesson-id="' . get_the_ID() . '">' . esc_html($pl) . ' / ' . esc_html($req) . '</span>';
        echo '</li>';
    }
    wp_reset_postdata();
    echo '</ul>';
    return ob_get_clean();
});

// Pretty URL: /courses/{course-slug}/{lesson-slug}/ -> single lesson
add_action('init', function () {
    add_rewrite_rule(
        '^courses/([^/]+)/([^/]+)/?$',
        'index.php?post_type=lesson&name=$matches[2]&ah_course=$matches[1]',
        'top'
    );
}, 11);

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

// Jeśli kurs w URL nie pasuje do przypiętego kursu lekcji -> przekieruj na kanoniczny
add_action('template_redirect', function () {
    if (is_singular('lesson')) {
        $q_course = get_query_var('ah_course');
        if ($q_course) {
            $cid   = audiohub_get_course_id(get_the_ID());
            $cslug = $cid ? get_post_field('post_name', $cid) : '';
            if (!$cid || $q_course !== $cslug) {
                wp_redirect(get_permalink(get_the_ID()), 301);
                exit;
            }
        }
    }
});

// Kolumna "Course" w /wp-admin/edit.php?post_type=lesson
add_filter('manage_lesson_posts_columns', function ($cols) {
    $cols['ah_course'] = __('Course', 'audiohub');
    return $cols;
});
add_action('manage_lesson_posts_custom_column', function ($col, $post_id) {
    if ($col !== 'ah_course') return;
    $cid = (int) get_post_meta($post_id, '_audiohub_course_id', true);
    if ($cid) echo '<a href="' . esc_url(get_edit_post_link($cid)) . '">' . esc_html(get_the_title($cid)) . '</a>';
    else echo '—';
}, 10, 2);

// Dropdown filtrujący po kursie
add_action('restrict_manage_posts', function () {
    global $typenow;
    if ($typenow !== 'lesson') return;
    $selected = isset($_GET['ah_course_filter']) ? (int) $_GET['ah_course_filter'] : 0;
    $courses = get_posts(['post_type' => 'course', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids']);
    echo '<select name="ah_course_filter"><option value="0">' . esc_html__('All courses', 'audiohub') . '</option>';
    foreach ($courses as $cid) {
        printf('<option value="%d"%s>%s</option>', $cid, selected($selected, $cid, false), esc_html(get_the_title($cid)));
    }
    echo '</select>';
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
