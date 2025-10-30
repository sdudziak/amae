<?php

/**
 * Plugin Name: AudioHub Courses (MU)
 * Description: CPT „course” i „lesson”, liczniki odtworzeń per użytkownik, shortcody listujące kursy i lekcje.
 */
if (!defined('ABSPATH')) {
    exit;
}

// UI przy dodawaniu/edycji kategorii
add_action('course_cat_add_form_fields', function () {
    $levels = ah_pmpro_levels();
    if (!$levels) return;
    echo '<div class="form-field"><label>' . esc_html__('Required membership levels', 'audiohub') . '</label>';
    foreach ($levels as $lvl) {
        printf(
            '<label style="display:block"><input type="checkbox" name="ah_levels[]" value="%d"> %s</label>',
            (int)$lvl->id,
            esc_html($lvl->name)
        );
    }
    echo '</div>';
});

add_action('course_cat_edit_form_fields', function ($term) {
    $levels = ah_pmpro_levels();
    if (!$levels) return;
    $sel = (array) get_term_meta($term->term_id, '_ah_levels', true);
    echo '<tr class="form-field"><th><label>' .
        esc_html__('Required membership levels', 'audiohub') . '</label></th><td>';
    foreach ($levels as $lvl) {
        printf(
            '<label style="display:block"><input type="checkbox" name="ah_levels[]" value="%d" %s> %s</label>',
            (int)$lvl->id,
            checked(in_array($lvl->id, $sel), true, false),
            esc_html($lvl->name)
        );
    }
    echo '</td></tr>';
});

add_action('created_course_cat', function ($term_id) {
    $vals = isset($_POST['ah_levels']) ? array_map('intval', (array)$_POST['ah_levels']) : [];
    update_term_meta($term_id, '_ah_levels', $vals);
});

add_action('edited_course_cat', function ($term_id) {
    $vals = isset($_POST['ah_levels']) ? array_map('intval', (array)$_POST['ah_levels']) : [];
    update_term_meta($term_id, '_ah_levels', $vals);
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
    // Save tags
    if (isset($_POST['post_tags'])) {
        $tags = array_map('intval', $_POST['post_tags']);
        wp_set_object_terms($post_id, $tags, 'post_tag');
    } else {
        wp_set_object_terms($post_id, [], 'post_tag');
    }
});

add_action('save_post_course', function ($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['ah_paid'])) update_post_meta($post_id, '_ah_paid', '1');
    else update_post_meta($post_id, '_ah_paid', '0');
    if ('publish' !== $post->post_status) return;
    $terms = wp_get_post_terms($post_id, 'course_cat', ['fields' => 'ids']);
    if (empty($terms)) {
        wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
        add_filter('redirect_post_location', function ($loc) {
            return add_query_arg('ah_need_cat', '1', $loc);
        });
    }
}, 10, 3);

add_action('admin_notices', function () {
    if (!empty($_GET['ah_need_cat'])) {
        echo '<div class="notice notice-error"><p>' .
            esc_html__('Course must have at least one category.', 'audiohub') .
            '</p></div>';
    }
});

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

// Pretty URL: /courses/{course-slug}/{lesson-slug}/ -> single lesson
add_action('init', function () {
    add_rewrite_rule(
        '^courses/([^/]+)/([^/]+)/?$',
        'index.php?post_type=lesson&name=$matches[2]&ah_course=$matches[1]',
        'top'
    );
}, 11);

// Jeśli kurs w URL nie pasuje do przypiętego kursu lekcji -> przekieruj na kanoniczny
add_action('template_redirect', function () {
    if (is_singular(['course', 'lesson'])) {
        $pid = get_queried_object_id();
        if (!ah_user_has_access($pid)) {
            wp_redirect(ah_paywall_url($pid));
            exit;
        }
    }
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




// Pomocnicze: poziomy PMPro
function ah_pmpro_levels()
{
    if (!function_exists('pmpro_getAllLevels')) return [];
    $levels = pmpro_getAllLevels(true, true);
    return is_array($levels) ? $levels : [];
}

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

// Zbierz wymagane poziomy PMPro z kategorii kursu i ich przodków
function ah_required_levels_for_post($post_id)
{
    $req = [];
    $terms = wp_get_post_terms($post_id, 'course_cat', ['fields' => 'all']);
    foreach ($terms as $t) {
        $chain = array_merge([$t], array_map('get_term', get_ancestors($t->term_id, 'course_cat', 'taxonomy')));
        foreach ($chain as $term) {
            $lv = (array) get_term_meta($term->term_id, '_ah_levels', true);
            if ($lv) $req = array_merge($req, array_map('intval', $lv));
        }
    }
    return array_values(array_unique($req));
}

// Czy post wymaga paywalla (uwzględnia flagę "paid")
function ah_post_paywall_required($post_id)
{
    $type = get_post_type($post_id);
    $course_id = ($type === 'lesson') ? (int) get_post_meta($post_id, '_audiohub_course_id', true) : $post_id;
    if ($type === 'lesson' && !$course_id) return [];      // brak kursu => brak wymagań
    $paid = get_post_meta($course_id, '_ah_paid', true);
    if ($paid === '') $paid = '1';                        // domyślnie płatny
    if ($paid !== '1') return [];                         // free kurs -> brak wymagań
    return ah_required_levels_for_post($course_id);
}

function ah_user_has_access($post_id, $user_id = 0)
{
    $req = ah_post_paywall_required($post_id);
    if (!$req) return true;                               // free
    if (!is_user_logged_in()) return false;
    if (!function_exists('pmpro_hasMembershipLevel')) return current_user_can('manage_options');
    foreach ($req as $level_id) {
        if (pmpro_hasMembershipLevel($level_id, $user_id)) return true; // ma którykolwiek
    }
    return false;
}

function ah_paywall_url($post_id)
{
    $req = ah_post_paywall_required($post_id);
    if (function_exists('pmpro_url')) {
        if ($req) {
            return pmpro_url('checkout') . '?level=' . $req[0] . '&redirect_to=' . rawurlencode(get_permalink($post_id));
        }
        return pmpro_url('levels');
    }
    return wp_login_url(get_permalink($post_id));
}
