<?php
/* Check if logged in user has correct permissions
------------------------------------------------------- */
function hdq_user_permission($settings = null)
{
    if (current_user_can("manage_options")) {
        return true;
    }

    if ($settings === null) {
        $settings = new _hdq_settings(true);
        $settings = $settings->data;
    }

    if (isset($settings["allow_authors_access"]) && $settings["allow_authors_access"] === "yes") {
        if (current_user_can("publish_posts")) {
            return true;
        }
    }
    return false;
}

/* Check and validate NONCE
------------------------------------------------------- */
function hdq_validate_nonce($data)
{
    $valid = true;
    if (!isset($data["HD_NONCE"])) {
        $valid = false;
    } else {
        $nonce = sanitize_text_field($data["HD_NONCE"]);
        $valid = wp_verify_nonce($nonce, 'hdq_NONCE');
    }

    if (!$valid) {
        $res = new stdClass();
        $res->status = "success";
        $res->html = "Unable to validate your credentials. Your NONCE may have expired. Please reload this page from your WordPress admin to refresh your NONCE.";
        echo json_encode($res);
        die();
    }
}

/* Custom the_content
   Used to stop other plugins from auto adding content
------------------------------------------------------- */
add_filter('hdq_content', 'wptexturize');
add_filter('hdq_content', 'convert_smilies');
add_filter('hdq_content', 'convert_chars');
add_filter('hdq_content', 'wpautop');
add_filter('hdq_content', 'shortcode_unautop');
add_filter('hdq_content', 'prepend_attachment');


/* polyfill for < php8
------------------------------------------------------- */
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

/* Template functions
------------------------------------------------------- */
function hdq_get_settings()
{
    global $hdq_settings;
    if ($hdq_settings != "") {
        return $hdq_settings;
    }
    $hdq_settings = new _hdq_settings(true);
    $hdq_settings = $hdq_settings->data;
    return $hdq_settings;
}

function hdq_get_quiz($quiz_id)
{
    $quiz_id = intval($quiz_id);
    $quiz = new _hdq_quiz($quiz_id, true);
    $quiz->data["quiz_type"] = $quiz->quiz_type;
    return $quiz->data;
}

function hdq_get_question($question_id, $quiz_id)
{
    $question_id = intval($question_id);
    $quiz = new _hdq_question($quiz_id, $question_id, true);
    return $quiz->data;
}

function hdq_get_content_filter()
{
    $settings = hdq_get_settings();
    if ($settings["replace_the_content_filter"] === "yes") {
        return "hdq_content";
    }
    return "the_content";
}

function hdq_get_question_order($quiz)
{
    $question_order = "menu_order"; // default
    if (
        $quiz["random_question_order"] === "yes" ||
        isset($quiz["pool_of_questions"]) && intval($quiz["pool_of_questions"]) > 0
    ) {
        $question_order = "rand";
    }

    if (isset($quiz["wp_pagination"]) && intval($quiz["wp_pagination"]) > 0) {
        $question_order = "menu_order";
    }
    return $question_order;
}

/* Check if it's OK to build the quiz
------------------------------------------------------- */
function hdq_can_build_quiz()
{
    // if on cat, search, or archive
    if (!is_singular() && HDQ_REDIRECT) {
        return false;
    }

    // if we on an AMP page
    if (function_exists("is_amp_endpoint")) {
        if (is_amp_endpoint()) {
            return false;
        }
    }

    if (hdq_check_editor()) {
        return false;
    }

    return true;
}

function hdq_check_editor()
{
    // we cannot enqueue CSS or Scripts on a live page,
    // so do not built page in editor mode
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    // sometimes the above includes does not work if people use different paths
    if (function_exists('is_plugin_active')) {
        if (is_plugin_active('elementor/elementor.php')) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                return true;
            }
        }
    }
    return false;
}

function hdq_do_general_before($data)
{
    wp_enqueue_script(
        'hdq_script',
        plugins_url('../assets/frontend/hdq_script.js?', __FILE__),
        array(),
        HDQ_PLUGIN_VERSION,
        true
    );

    // check for WP Pagination
    if ($data["quiz"]["wp_pagination"] > 0) {
        if (intval($data["quiz"]["pool_of_questions"]) == 0) {
            $data["wp_paginate"] = true;
            $data["question_order"] = "menu_order";
            $data["per_page"] = $data["quiz"]["wp_pagination"];
        }
    } else {
        if ($data["quiz"]["pool_of_questions"] > 0) {
            $data["per_page"] = $data["quiz"]["pool_of_questions"];
        }
    }
    $data = apply_filters("hdq_before_quiz_data", $data);
    return $data;
}

function hdq_do_personality_before($data)
{
    wp_enqueue_script(
        'hdq_script',
        plugins_url('../assets/frontend/hdq_personality_script.js?', __FILE__),
        array(),
        HDQ_PLUGIN_VERSION,
        true
    );
    $data = apply_filters("hdq_before_quiz_data", $data);
    return $data;
}

function hdq_get_local_vars($quiz, $settings)
{
    $data = array(
        "hdq_init" => array(), // actions
        "hdq_submit" => array(), // actions
        "hdq_before_submit" => array(), // actions
        "quiz" => array(
            "ajax_url" => admin_url('admin-ajax.php'),
            "permalink" => get_the_permalink(),
        ),
        "settings" => array()
    );

    foreach ($quiz as $k => $setting) {
        $data["quiz"][$k] = $setting;
    }

    foreach ($settings as $k => $setting) {
        $data["settings"][$k] = $setting;
        if (str_contains($k, "translate_")) {
            $data["settings"][$k] = __($setting, "hd-quiz");
        }
    }

    // remove things that don't need to be stored here
    if (isset($data["settings"]["adset_code"]) && $data["settings"]["adset_code"] != "") {
        $data["settings"]["adset_code"] = "yes";
    }
    if (isset($data["quiz"]["quiz_pass_content"])) {
        unset($data["quiz"]["quiz_pass_content"]);
    }
    if (isset($data["quiz"]["quiz_fail_content"])) {
        unset($data["quiz"]["quiz_fail_content"]);
    }

    if (isset($data["quiz"]["personality_results"])) {
        unset($data["quiz"]["personality_results"]);
    }

    $object = json_decode(json_encode($data), FALSE);

    do_action("hdq_submit", $object); // add functions to quiz complete
    do_action("hdq_before_submit", $object); // add functions to quiz complete, but before submit
    do_action("hdq_init", $object); // add functions to quiz init

    return $object;
}

function hdq_randomize_answer_order($question)
{
    $answers = $question["question_answers"];
    shuffle($answers);
    $question["question_answers"] = $answers;
    return $question;
}

function hdq_print_questions($data)
{
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    if (HDQ_FORCE_ORDER !== false) {
        $data["question_order"] = HDQ_FORCE_ORDER;
    }

    $filter = hdq_get_content_filter();

    $args = array(
        'post_type' => array('post_type_questionna'),
        'tax_query' => array(
            array(
                'taxonomy' => 'quiz',
                'terms' => $data["quiz_id"],
            ),
        ),
        'pagination' => $data["wp_paginate"], // true or false
        'posts_per_page' => $data["per_page"], // also used for the pool of questions
        'paged' => $paged,
        'orderby' => $data["question_order"], // defaults to menu_order
        'order' => 'ASC',
        'suppress_filters' => true // attempt to remove any filters added by other plugins so that we can use our own order
    );

    global $hdq_query;
    $hdq_query = new WP_Query($args);
    $i = 0; // question # counter;

    if ($hdq_query->have_posts()) {
        while ($hdq_query->have_posts()) {
            $hdq_query->the_post();
            $i++;
            $question = hdq_get_question(get_the_ID(), $data["quiz_id"]);

            // Paginate
            if ($question["paginate"] === "yes") {
                if ($i !== 1) {
                    hdq_print_jPaginate($data, $question);
                } else {
                    hdq_print_jPaginate($data, $question, true); // start quiz text
                }
            }
            // used to add custom data attributes to questions
            // useful for custom question types
            $extra = apply_filters('hdq_extra_question_data', array(), $question, $data["quiz_id"]);
            $extra_data = "";
            foreach ($extra as $k => $d) {
                $extra_data = esc_attr("data-" . sanitize_text_field($k)) . ' = "' . esc_attr(sanitize_text_field($d)) . '" ';
            }
?>
            <div class="hdq_question" <?php echo $extra_data; ?> data-type="<?php echo esc_attr($question["question_type"]); ?>" id="hdq_question_<?php echo $question["question_id"]; ?>" data-weight="1">
                <?php
                hdq_print_question_featured_image($question);
                if ($question["before_question_content"] != "") {
                    echo apply_filters($filter, $question["before_question_content"]);
                }
                do_action("hdq_after_featured_image", $question);
                $f = "render_" . $question["question_type"];
                if (function_exists($f)) {
                    if ($data["quiz"]["random_answer_order"] === "yes") {
                        $question = hdq_randomize_answer_order($question);
                    }
                    if ($question["question_type"] === "question_as_title") {
                        $i = $i - 1;
                    }
                    $f($question, $data, $i);
                } else {
                    echo 'render function for question type ' . $question["question_type"] . ' not found';
                }
                hdq_print_question_extra_text($question);
                ?>
            </div>
    <?php

            // Adcode
            if ($data["adcode"]) {
                if ($i % 5 == 0 && $i != 0) {
                    echo '<div class = "hdq_adset_container">';
                    echo $data["adcode"];
                    echo '</div>';
                }
            }
        }
    }
    wp_reset_postdata();
}

function hdq_print_results($data)
{
    if ($data["quiz"]["quiz_type"] === "general") {
        hdq_print_results_general($data);
    } elseif ($data["quiz"]["quiz_type"] === "personality") {
        hdq_print_results_personality($data);
    }
}

function hdq_print_results_general($data)
{
    $filter = hdq_get_content_filter();
    ?>
    <div class="hdq_results_wrapper">
        <div class="hdq_results_inner" aria-live="polite">
            <h2 class="hdq_results_title"><?php echo esc_attr($data["settings"]["translate_results"]); ?></h2>
            <div class="hdq_result"><!-- Score --></div>
            <div class="hdq_result_pass"><?php echo apply_filters($filter, $data["quiz"]["quiz_pass_content"]); ?></div>
            <div class="hdq_result_fail"><?php echo apply_filters($filter, $data["quiz"]["quiz_fail_content"]); ?></div>
            <div class="hdq_result_after"><?php do_action("hdq_results_after_content", $data["quiz_id"]); ?></div>
        </div>
        <?php
        if ($data["settings"]["allow_social_media"] === "yes" && $data["quiz"]["share_quiz_results"] === "yes") {
        ?>
            <div class="hdq_share">
                <div class="hdq_social_icon">
                    <a title="share quiz on Facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo the_permalink(); ?>&amp;title=Quiz" target="_blank" class="hdq_facebook">
                        <img src="<?php echo plugins_url('../assets/images/fb_share.png', __FILE__); ?>" alt="Share your score!">
                    </a>
                </div>
                <div class="hdq_social_icon">
                    <a href="#" target="_blank" class="hdq_twitter" title="X, formerly Twitter"><img src="<?php echo plugins_url('../assets/images/x_share.png', __FILE__); ?>" alt="Tweet your score!"></a>
                </div>
                <div class="hdq_social_icon">
                    <a href="#" target="_blank" class="hdq_bluesky" title="Bluesky"><img src="<?php echo plugins_url('../assets/images/bluesky_share.png', __FILE__); ?>" alt="Tweet your score!"></a>
                </div>
                <div class="hdq_social_icon">
                    <a class="hdq_share_other"><img src="<?php echo plugins_url('../assets/images/share_all.png', __FILE__); ?>" alt="Share to other"></a>
                </div>
                <?php do_action("hdq_share_content", $data["quiz_id"]); ?>
            </div>
        <?php
        }

        if ($data["settings"]["i_love_hd_quiz"] === "yes") {
            echo '<p class = "hdq_heart">HD Quiz powered by <a href = "https://hdplugins.com/hd-quiz-demo/?utm_source=IheartHDQuiz" target = "_blank" title = "Best WordPress Developers">harmonic design</a></p>';
        }
        ?>
    </div>
<?php
}

function hdq_print_results_personality($data)
{
    $filter = hdq_get_content_filter();
?>
    <div class="hdq_results_wrapper">
        <div class="hdq_results_inner" aria-live="polite">
            <h2 class="hdq_results_title"><?php echo esc_attr($data["settings"]["translate_results"]); ?></h2>
            <?php
            foreach ($data["quiz"]["personality_results"] as $outcome) {
            ?>
                <div class="hdq_result_personality" id="hdq_results_<?php echo esc_attr($outcome["id"]); ?>">
                    <div class="hdq_result"><?php echo $outcome["label"]; ?></div>
                    <?php echo wpautop(apply_filters($filter, $outcome["content"])); ?>
                </div>
            <?php
            }
            ?>
            <div class="hdq_result_after"><?php do_action("hdq_results_after_content", $data["quiz_id"]); ?></div>
        </div>
        <?php
        if ($data["settings"]["allow_social_media"] === "yes" && $data["quiz"]["share_quiz_results"] === "yes") {
        ?>
            <div class="hdq_share">
                <div class="hdq_social_icon">
                    <a title="share quiz on Facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo the_permalink(); ?>&amp;title=Quiz" target="_blank" class="hdq_facebook">
                        <img src="<?php echo plugins_url('../assets/images/fb_share.png', __FILE__); ?>" alt="Share your score!">
                    </a>
                </div>
                <div class="hdq_social_icon">
                    <a href="#" target="_blank" class="hdq_twitter" title="X, formerly Twitter"><img src="<?php echo plugins_url('../assets/images/x_share.png', __FILE__); ?>" alt="Tweet your score!"></a>
                </div>
                <div class="hdq_social_icon">
                    <a href="#" target="_blank" class="hdq_bluesky" title="Bluesky"><img src="<?php echo plugins_url('../assets/images/bluesky_share.png', __FILE__); ?>" alt="Tweet your score!"></a>
                </div>
                <div class="hdq_social_icon">
                    <a class="hdq_share_other"><img src="<?php echo plugins_url('../assets/images/share_all.png', __FILE__); ?>" alt="Share to other"></a>
                </div>
                <?php do_action("hdq_share_content", $data["quiz_id"]); ?>
            </div>
        <?php
        }

        if ($data["settings"]["i_love_hd_quiz"] === "yes") {
            echo '<p class = "hdq_heart">HD Quiz powered by <a href = "https://hdplugins.com/hd-quiz-demo/?utm_source=IheartHDQuiz" target = "_blank" title = "Best WordPress Developers">harmonic design</a></p>';
        }
        ?>
    </div>
<?php
}

function hdq_print_jPaginate($data, $question, $startQuiz = false)
{
    if (isset($data["quiz"]["timer"]) && intval($data["quiz"]["timer"]) >= 3 && $data["quiz"]['timer_per_question'] === "yes" && $question["question_type"] === "question_as_title") {
        return;
    }
?>
    <div class="hdq_jPaginate">
        <?php
        if (!HDQ_DISABLE_PREV_BUTTON && !$startQuiz) {
        ?>
            <div class="hdq_hidden hdq_prev_button hdq_button hdq_kb" role="button" tabindex="0">
                <?php echo $data["settings"]["translate_previous"]; ?>
            </div>
        <?php
        }
        ?>
        <div class="hdq_next_button hdq_jPaginate_button hdq_button hdq_kb" role="button" tabindex="0">
            <?php
            if (!$startQuiz) {
                echo $data["settings"]["translate_next"];
            } else {
                echo $data["settings"]["translate_quiz_start"];
            }
            ?>
        </div>
    </div>
<?php
}

function hdq_print_quiz_in_loop()
{
    if (hdq_check_editor()) {
        echo '<div class = "hdq_elementor_block" style = "padding: 2em; border: 1px dashed #999; background-color: rgba(255,255,255,0.1)"><p><strong>HD Quiz</strong>: You are only seeing this because you are currently in Elementor\'s editor mode. This quiz will become visible on the page once you view the live public page.</p></div>';
        return;
    }

    $settings = hdq_get_settings();
    $permalink = get_the_permalink();
    echo '<div class = "hdq_quiz_wrapper"><a href = "' . esc_url($permalink) . '" rel="noamphtml" class = "hdq_quiz_start hdq_button button" role = "button">' . esc_attr($settings["translate_quiz_start"]) . '</a></div>';
}

function hdq_print_quiz_start($data)
{
    $quiz_start = array("html" => "", "classes" => "");

    // only print if a timer is in use and ads are not in use
    if ($data["settings"]["adset_code"] != "") {
        return $quiz_start;
    }
    if (isset($data["quiz"]["timer"]) && intval($data["quiz"]["timer"]) <= 3) {
        return $quiz_start;
    }
    if (!isset($data["quiz"]["timer"])) {
        return $quiz_start;
    }

    $label = $data["settings"]["translate_quiz_start"];
    $label = str_replace("%quiz%", $data["quiz"]["quiz_name"], $label);

    $quiz_start["html"] = '<div class="hdq_quiz_start hdq_button hdq_kb" role="button" tabindex="0">' . esc_attr($label) . '</div>';
    $quiz_start["classes"] = "hdq_hidden";
    return $quiz_start;
}

function hdq_print_finish($data)
{
    do_action("hdq_before_finish_button", $data["quiz_id"]);

    if ($data["wp_paginate"] == "" || intval($data["wp_paginate"]) == 0) {
        hdq_print_finish_good($data);
    } else {
        hdq_print_finish_bad($data); // WP Pagination
    }
}

function hdq_print_finish_good($data)
{
?>
    <div class="hdq_finish hdq_jPaginate hdq_hidden">
        <?php
        if (!HDQ_DISABLE_PREV_BUTTON) {
        ?>
            <div class="hdq_hidden hdq_prev_button hdq_button hdq_kb" role="button" tabindex="0">
                <?php echo $data["settings"]["translate_previous"]; ?>
            </div>
        <?php
        }
        ?>
        <div class="hdq_finsh_button hdq_button hdq_kb" role="button" tabindex="0">
            <?php echo $data["settings"]["translate_finish"]; ?>
        </div>
    </div>
<?php
}

function hdq_print_finish_bad($data)
{
    global $hdq_query;
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    echo '<div class="hdq_finish hdq_jPaginate">';

    // print inputs to grab vars from
    $currentScore = 0;
    $totalQuestions = 0;
    if (isset($_GET['currentScore'])) {
        $currentScore = intval($_GET['currentScore']);
    }
    if (isset($_GET['totalQuestions'])) {
        $totalQuestions = intval($_GET['totalQuestions']);
    }
    echo '<input type = "hidden" id = "hdq_current_score" value = "' . $currentScore . '"/>';
    echo '<input type = "hidden" id = "hdq_total_questions" value = "' . $totalQuestions . '"/>';

    if ($hdq_query->max_num_pages > 1 && $data["per_page"] != "-1" && $hdq_query->max_num_pages !== $paged) {
        if ($data["quiz"]["pool_of_questions"] == "" || $data["quiz"]["pool_of_questions"] == 0) {
            hdq_print_next_button_wp_paginate($data);
        } else {
            hdq_print_finish_button_wp_paginate($data);
        }
    } else {
        hdq_print_finish_button_wp_paginate($data);
    }
    echo '</div>';
}

function hdq_print_next_button_wp_paginate($data)
{
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $next = $paged + 1;
    $permalink = get_the_permalink() . 'page/' . $next;
?>
    <a href="<?php echo $permalink; ?>" class="hdq_next_button hdq_jPaginate_button hdq_button">
        <?php echo $data["settings"]["translate_next"]; ?>
    </a>
<?php
}

function hdq_print_finish_button_wp_paginate($data)
{
?>
    <div class="hdq_finsh_button hdq_button hdq_kb" role="button" tabindex="0">
        <?php echo $data["settings"]["translate_finish"]; ?>
    </div>
<?php
}

function hdq_print_question_featured_image($question)
{
    if ($question["featured_image"] != "" && $question["featured_image"] != 0) {
        $image = wp_get_attachment_image($question["featured_image"], "full", "", array("class" => "hdq_featured_image"));
        if ($image != null) {
            echo '<div class = "hdq_question_featured_image">';
            echo $image;
            echo '</div>';
        }
    }
}

function hdq_print_question_title($question, $question_number)
{
    if (isset($_GET['totalQuestions'])) {
        $question_number = $question_number + intval($_GET['totalQuestions']);
    }
    $tooltip = "";
    if ($question["tooltip"] != "" && $question["tooltip"] != null) {
        $tooltip = '<span class="hdq_tooltip">
    ?
    <span class="hdq_tooltip_content">
        <span>' . esc_attr($question["tooltip"]) . '</span>
    </span>
</span>';
    }
    $question_number_symbol = "#";
    $question_number_symbol = apply_filters("hdq_set_question_number_symbol", $question_number_symbol);
    echo '<h3 class = "hdq_question_heading"><span class = "hdq_question_number">' . esc_attr($question_number_symbol) . esc_attr($question_number) . '.</span> ' . get_the_title($question["question_id"]) . ' ' . $tooltip . '</h3>';
}

function hdq_print_question_extra_text($question)
{
    $filter = hdq_get_content_filter();
    if (isset($question["extra_content"]) && $question["extra_content"] != "") {
        echo '<div class = "hdq_question_after_text">';
        echo apply_filters($filter, $question["extra_content"]);
        echo '</div>';
    }
}

function hdq_get_answer_image_url($image)
{
    if (is_numeric($image)) {
        // if this uses image ID instead of URL
        $image_url = wp_get_attachment_image_src($image, "hd_qu_size2", false);
        if (!$image_url) {
            return ""; // image must not exist on server anymore
        }

        if ($image_url[0] == "" || $image_url[0] == null) {
            $image_url = wp_get_attachment_image_src($image, "thumbnail", false);
        } else {
            // check if image is a gif
            // When WP resizes a gif, the gif is no longer animated :(
            $extention =  parse_url($image_url[0], PHP_URL_PATH);
            $extention = pathinfo($extention, PATHINFO_EXTENSION);
            if ($extention === "gif") {
                $image_url = wp_get_attachment_image_src($image, "full", false);
            }
        }
        $image = $image_url[0];
        return $image;
    } else {
        // figure out what the original custom image size was
        // get the extention -400x400
        $image_parts = explode(".", $image);
        $image_extention = end($image_parts);
        unset($image_parts[count($image_parts) - 1]);
        $image_url = implode(".", $image_parts);
        $image_url = $image_url . '-400x400.' . $image_extention;
        return $image_url;
    }
}

// mimic javaScripts encodeURIComponent
function hdq_encodeURIComponent($str)
{
    $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
    return strtr(rawurlencode($str), $revert);
}

/* Experimental Facebook share */
function hdq_load_hd_template($template)
{
    global $wp;
    $request = explode('/', $wp->request);
    if (current($request) == "hd-quiz" && !is_page('hd-quiz')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
        add_filter("wp_title", function ($title) {
            return "Harmonic Design | Professional Web Development";
        });
        return dirname(__FILE__) . '/share-page.php';
    }
    return $template;
}
add_filter('template_include', 'hdq_load_hd_template');


// Makes sure that any request responds with a proper 200 http code
function hdq_rewrites_init()
{
    add_rewrite_endpoint('hd-quiz', EP_PERMALINK);
    add_rewrite_rule('hd-quiz/(.+)', 'index.php', 'top');
}
add_action('init', 'hdq_rewrites_init');

/* Question render functions
------------------------------------------------------- */

function render_multiple_choice_text($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/multiple-choice-text.php';
}

function render_multiple_choice_image($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/multiple-choice-image.php';
}

function render_personality_multiple_choice_text($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/personality-multiple-choice-text.php';
}

function render_personality_multiple_choice_image($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/personality-multiple-choice-image.php';
}

function render_select_all_apply_text($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/select-all-apply-text.php';
}

function render_select_all_apply_image($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/select-all-apply-image.php';
}

function render_text_based_answer($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/text-based-answer.php';
}

function render_question_as_title($question, $settings, $question_number)
{
    include dirname(__FILE__) . '/questions/question-as-title.php';
}
