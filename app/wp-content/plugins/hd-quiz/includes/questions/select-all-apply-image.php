<?php
hdq_print_question_title($question, $question_number);
$answers = $question["question_answers"];
$hint_text = $settings["settings"]["translate_select_all_that_apply"];
echo '<p>' . $hint_text . '</p>';
echo '<div class = "hdq_answers hdq_question_answers_images">';
foreach ($answers as $k => $answer) {
    $selected = 0;
    if (!HDQ_SECURE_ANSWERS) {
        if ($answer["selected"] === "yes") {
            $selected = 1;
        }
    }
?>
    <div class="hdq_row hdq_row_image">
        <label class="hdq_label_answer" id="hda_label_<?php echo $k . '_' . $question["question_id"]; ?>" data-type="image" data-id="hdq_question_<?php echo $question["question_id"]; ?>" for="hdq_option_<?php echo $k . '_' . $question["question_id"]; ?>">
            <?php
            $image = "";
            if ($answer["image"] != "" && $answer["image"] != 0) {
                $image = hdq_get_answer_image_url($answer["image"]);
            }
            if ($image != "" && $image != null) {
                echo '<img src = "' . $image . '" alt = "' . htmlentities($answer["value"]) . '"/>';
            } ?>

            <div>
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
            </div>
        </label>
    </div>
<?php
}
echo '</div>';
