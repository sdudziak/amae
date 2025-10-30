<?php
hdq_print_question_title($question, $question_number);

$hint_text = $settings["settings"]["translate_select_all_that_apply"];

$answers = $question["question_answers"];
echo '<div class = "hdq_answers">';
echo '<p>' . $hint_text . '</p>';
foreach ($answers as $k => $answer) {
    $selected = 0;
    if (!HDQ_SECURE_ANSWERS) {
        if ($answer["selected"] === "yes") {
            $selected = 1;
        }
    }
?>
    <div class="hdq_row">
        <label class="hdq_label_answer" id="hda_label_<?php echo $k . '_' . $question["question_id"]; ?>" data-type="radio" data-id="hdq_question_<?php echo $question["question_id"]; ?>" for="hdq_option_<?php echo $k . '_' . $question["question_id"]; ?>">
            <div class="hdq-options-check">
                <input type="checkbox" aria-labelledby="hda_label_<?php echo $k . '_' . $question["question_id"]; ?>" autocomplete="off" title="<?php echo htmlentities($answer["value"]); ?>" data-id="<?php echo $question["question_id"]; ?>" class="hdq_option hdq_check_input" data-type="radio" value="<?php echo $selected; ?>" name="hdq_option_<?php echo $k . '_' . $question["question_id"]; ?>" id="hdq_option_<?php echo $k . '_' . $question["question_id"]; ?>">
                <span class="hdq_toggle"></span>
            </div>
            <span class="hdq_aria_label">
                <?php
                if (str_contains($answer["value"], "[") && str_contains($answer["value"], "]")) {
                    remove_filter('the_content', 'wpautop');
                    echo apply_filters('the_content', $answer["value"]); // render out shortcode
                    add_filter('the_content', 'wpautop');
                } else {
                    echo $answer["value"];
                }
                ?>
            </span>
        </label>
    </div>
<?php
}
echo '</div>';
