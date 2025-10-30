<?php
/* main quiz master template
------------------------------------------------------- */
wp_enqueue_style(
    'hdq_admin_style',
    plugin_dir_url(__FILE__) . '../assets/frontend/hdq_style.css',
    array(),
    HDQ_PLUGIN_VERSION
);

// let's make sure we are allowed to build the quiz
$can_build_quiz = hdq_can_build_quiz();
$quiz_id = intval($quiz); // quiz ID from shortcode
$can_build_quiz = apply_filters("hdq_build_quiz", $can_build_quiz, $quiz_id);
if (!$can_build_quiz) {
    hdq_print_quiz_in_loop();
    return;
}

$quiz_name = get_term($quiz_id, "quiz");
if ($quiz_name == null) {
    echo 'This quiz no longer exists';
    return;
}

$data = array(
    "quiz_id" => $quiz_id,
    "quiz" => hdq_get_quiz($quiz_id),
    "settings" => hdq_get_settings(),
    "quiz_name" => htmlentities($quiz_name->name)
);

$data["question_order"] = hdq_get_question_order($data["quiz"]);
$data["per_page"] = -1; // show all questions by default
$data["wp_paginate"] = false;

if ($data["quiz"]["quiz_type"] === "general") {
    $data = hdq_do_general_before($data);
} elseif ($data["quiz"]["quiz_type"] === "personality") {
    $data = hdq_do_personality_before($data);
} else {
    echo 'HD Quiz: Question type ' . $data["quiz"]["quiz_type"] . ' template not found.';
    return;
}

// if we should display ads
$data["adcode"] = false;
if ($data["settings"]["adset_code"] !== "") {
    $data["adcode"] = apply_filters("hdq_content", htmlspecialchars_decode($data["settings"]["adset_code"]));
}

$hdq_local_vars = hdq_get_local_vars($data["quiz"], $data["settings"]);
$hdq_local_vars->quiz_name = $quiz_name->name;
$hdq_local_vars = 'const HDQ_VERSION = "' . HDQ_PLUGIN_VERSION . '"; const HDQ_DATA = ' . json_encode($hdq_local_vars) . ';';
// wp_add_inline_script('hdq_script', $hdq_local_vars, "before"); // Causes some cache plugins to print this out twice if `wp_add_inline_script` is called more than once...
?>

<script>
    <?php echo $hdq_local_vars; ?>
</script>

<div class="hdq_quiz_wrapper" id="hdq_<?php echo esc_attr($quiz_id); ?>">
    <div id="hdq_offset_div" class="hdq_offset_div" style="width: 1px; height: 1px; position: relative; opacity: 0; pointer-events: none; user-select: none;z-index: 0; relative; top: -4rem; background-color:red">&nbsp;</div>
    <div class="hdq_before">
        <?php do_action("hdq_before", $quiz_id); ?>
    </div>

    <?php
    $quiz_start = hdq_print_quiz_start($data);
    echo $quiz_start["html"];
    ?>

    <div class="hdq_quiz <?php echo $quiz_start["classes"]; ?>">
        <?php
        if ($data["quiz"]["results_position"] != "below") {
            hdq_print_results($data);
        }
        hdq_print_questions($data);
        hdq_print_finish($data);
        if ($data["quiz"]["results_position"] === "below") {
            hdq_print_results($data);
        }
        ?>
    </div>
    <div class="hdq_after">
        <?php do_action("hdq_after", $quiz_id); ?>
    </div>
    <div class="hdq_loading_bar"></div>
</div>