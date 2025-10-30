<?php
/*
    * Plugin Name: HD Quiz
    * Description: Add fun and interactive quizzes to your site.
    * Plugin URI: https://harmonicdesign.ca/hd-quiz/
    * Author: Harmonic Design
    * Author URI: https://harmonicdesign.ca
    * Version: 2.0.9
	* Text Domain: hd-quiz
	* Domain Path: /languages
*/

// Future updates
// * Next/Prev question when admin editing
// * Default quiz settings (accessed from settings page)
// * Load quiz via ajax (good to bypass pagecache issues)
// * New question types
// * Filter to add new quiz types
// * More translations
// * Weighted questions

if (!defined('ABSPATH')) {
    die('Invalid request.');
}
if (!defined('HDQ_PLUGIN_VERSION')) {
    define('HDQ_PLUGIN_VERSION', '2.0.9');
}

// Settings that a power user might want to change,
// but that I don't want to have a dedicated setting for
function hdq_admin_init()
{
    if (!defined('HDQ_MAX_ANSWERS')) {
        define('HDQ_MAX_ANSWERS', 10);
    }
    if (!defined('HDQ_REDIRECT')) {
        define('HDQ_REDIRECT', true);
    }
    if (!defined('HDQ_FORCE_ORDER')) {
        define('HDQ_FORCE_ORDER', false);
    }
    if (!defined('HDQ_PER_PAGE')) {
        define('HDQ_PER_PAGE', 50);
    }
    if (!defined('HDQ_DISABLE_PREV_BUTTON')) {
        define('HDQ_DISABLE_PREV_BUTTON', false);
    }
    if (!defined('HDQ_SECURE_ANSWERS')) {
        define('HDQ_SECURE_ANSWERS', false);
    }
}
add_action("init", "hdq_admin_init", 10);

// custom quiz image sizes
add_image_size('hd_qu_size2', 400, 400, true); // image-as-answer

// load in translations
function hdq_load_translations()
{
    load_plugin_textdomain('hd-quiz', false, plugin_basename(dirname(__FILE__)) . '/languages/');
}
add_action('init', 'hdq_load_translations');

/* Include the basic required files
------------------------------------------------------- */
require dirname(__FILE__) . '/hdfields/HDFields.php';
require dirname(__FILE__) . '/includes/actions-ajax.php';
require dirname(__FILE__) . '/classes/settings.php';
require dirname(__FILE__) . '/classes/dashboard.php';
require dirname(__FILE__) . '/classes/quiz.php';
require dirname(__FILE__) . '/classes/question.php';
require dirname(__FILE__) . '/includes/functions.php';
require dirname(__FILE__) . '/includes/admin-pages.php';
require dirname(__FILE__) . '/includes/quiz-taxonomy.php';
require dirname(__FILE__) . '/includes/question-cpt.php';
require dirname(__FILE__) . '/includes/custom-fields.php';


/* Function to check if HD Quiz is active
------------------------------------------------------- */
function hdq_exists()
{
    return;
}

/* Disable Canonical redirection for paginated quizzes
------------------------------------------------------- */
function hdq_disable_redirect_canonical($redirect_url)
{
    global $post;
    if (!isset($post->post_content)) {
        return;
    }
    if (has_shortcode($post->post_content, 'hdquiz')) {
        return false;
    }
    if (has_shortcode($post->post_content, 'HDquiz')) {
        return false;
    }
    if (has_block("hdq/quiz-block")) {
        return false;
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'hdq_disable_redirect_canonical');

/* Create HD Quiz Settings page
------------------------------------------------------- */
function hdq_create_settings_page()
{
    if (hdq_user_permission()) {
        function hdq_register_quizzes_page()
        {
            $addon_text = "";
            $new_addon = get_transient("hdq_new_addon");
            if ($new_addon === false) {
                hdq_check_for_updates();
            } else {
                $new_addon["isNew"] = sanitize_text_field($new_addon["isNew"]);
                if ($new_addon["isNew"] === "yes") {
                    $addon_text = ' <span class="awaiting-mod">NEW</span>';
                }
            }

            add_menu_page('HD Quiz', 'HD Quiz', 'publish_posts', 'hdq_quizzes', 'hdq_main_page', 'dashicons-clipboard', 5);

            add_submenu_page("hdq_quizzes", "HD Quiz Addons", __("Addons", "hd-quiz") . $addon_text, "delete_others_posts", "hdq_addons", "hdq_addons_page");
            add_submenu_page("hdq_quizzes", "HD Quiz Tools", __("Tools", "hd-quiz"), "manage_options", "hdq_tools", "hdq_tools_page");
            add_submenu_page("hdq_quizzes", "HD Quiz Settings", __("Settings", "hd-quiz"), "manage_options", 'hdq_options', 'hdq_about_settings_page');

            // tools, hidden pages
            add_submenu_page("", "CSV Importer", "CSV Importer", "manage_options", "hdq_importer", "hdq_tools_csv_importer");
        }
        add_action('admin_menu', 'hdq_register_quizzes_page');
    }
}
add_action('init', 'hdq_create_settings_page');

/* Set custom plugin links on WP plugins page
------------------------------------------------------- */
function hdq_plugin_links($actions, $plugin_file, $plugin_data, $context)
{
    $new = array(
        'settings'    => sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=hdq_options')),
            esc_html__('Settings', 'hd-quiz')
        ),
        'help' => sprintf(
            '<a href="%s">%s</a>',
            'https://hdplugins.com/forum/hd-quiz-support/',
            esc_html__('Help', 'hd-quiz')
        )
    );
    return array_merge($new, $actions);
}
add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'hdq_plugin_links', 10, 4);

/* Add shortcode
------------------------------------------------------- */
function hdq_add_shortcode($atts)
{
    // Attributes
    extract(
        shortcode_atts(
            array(
                'quiz' => '',
            ),
            $atts
        )
    );

    // Code
    ob_start();
    include plugin_dir_path(__FILE__) . './includes/template.php';
    return ob_get_clean();
}
add_shortcode('HDquiz', 'hdq_add_shortcode');

function hdq_check_for_updates()
{
    $remote = wp_remote_get("https://hdplugins.com/plugins/hd-quiz/addons_updated.txt");
    $local = intval(get_option("hdq_new_addon"));
    if (is_array($remote)) {
        $remote = intval($remote["body"]);
        update_option("hdq_new_addon", $remote);

        $transient = array(
            "date" => $remote,
            "isNew" => ""
        );

        if ($remote > $local) {
            $transient["isNew"] = "yes";
        }

        set_transient("hdq_new_addon", $transient, WEEK_IN_SECONDS); // only check every week

    } else {
        update_option("hdq_new_addon", "");
        set_transient("hdq_new_addon", array("date" => 0, "isNew" => ""), DAY_IN_SECONDS); // unable to connect. try again tomorrow
    }
}

/* Clear all schedules on plugin deactivation
------------------------------------------------------- */
function hdq_deactivation()
{
    wp_clear_scheduled_hook('hdq_check_for_updates');
}
register_deactivation_hook(__FILE__, 'hdq_deactivation');

function create_block_hd_quiz_block_block_init()
{
    register_block_type(__DIR__ . '/assets/block');
}
add_action('init', 'create_block_hd_quiz_block_block_init');
