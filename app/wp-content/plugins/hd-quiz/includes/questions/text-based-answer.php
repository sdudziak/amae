<?php
hdq_print_question_title($question, $question_number);
$answers = $question["question_answers"];
$hint_text = $settings["settings"]["translate_enter_answer_here"];

$correct = array();
if (!HDQ_SECURE_ANSWERS) {
    foreach ($answers as $answer) {
        array_push($correct,  trim(strtoupper($answer["value"])));
    }
}
$correct = hdq_encodeURIComponent(json_encode($correct));
?>
<div class="hdq_answers">
    <div class="hdq_row">
        <label for="hdq_option_<?php echo $question["question_id"]; ?>" id="hda_label_<?php echo $question["question_id"]; ?>" style="display: none"><?php echo $hint_text; ?></label>
        <input id="hdq_option_<?php echo $question["question_id"]; ?>" autocomplete="off" aria-labelledby="hda_label_<?php echo $question["question_id"]; ?>" data-id="<?php echo $question["question_id"]; ?>" class="hdq_label_answer hdq_input hdq_option" data-answers="<?php echo htmlentities($correct); ?>" data-type="text" type="text" title="<?php echo htmlentities($hint_text); ?>" placeholder="<?php echo htmlentities($hint_text); ?>" enterkeyhint="done" />
    </div>
</div>