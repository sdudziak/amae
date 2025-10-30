<?php
if ($this->quiz["personality_results"] == "" || empty($this->quiz["personality_results"])) {
    $res = new stdClass();
    $res->status = "success";
    $res->html = '<p id = "hdq_about">' . __("Please edit your quiz settings to create your different outcomes before creating questions.") . '</p>';
    echo json_encode($res);
    return;
}

ob_start();
?>
<table id="hdq_answers_table" class="hdq_table hdq_answers_type_multiple_choice_image">
    <thead class="hdq_answer_row_header">
        <tr>
            <th width="1">#</th>
            <th>Answer<span class="hd_tooltip_item">?<span class="hd_tooltip"><span class="hd_tooltip_content">You can use basic HTML tags or even shortcodes to help with formatting.</span></span></span></th>
            <th width="30" class="hdq_answer_selected">Point</th>
        </tr>
    </thead>
    <tbody>
        <?php
        for ($i = 0; $i < $this->max_answers; $i++) {
            $answer = array(
                "value" => "",
            );

            if (isset($data[$i]["value"])) {
                $answer["value"] = $data[$i]["value"];
            }
        ?>
            <tr class="hdq_answer_row">
                <td>#<?php echo $i + 1; ?></td>
                <td><input type="text" class="hd_input hdq_answer_item_input" data-answer-type="value" value="<?php echo esc_attr($answer["value"]); ?>" placeholder="enter answer..." /></td>
                <td>
                    <?php
                    foreach ($this->quiz["personality_results"] as $outcome) {
                        $checked = "";
                        if (isset($data[$i]["selected_" . $outcome["id"]])) {
                            $checked = "checked";
                        }
                    ?>
                        <div class="hdq_answer_item">
                            <div data-type="checkbox" data-required="" class="hd_input_checkbox hdq_answer_input" data-tab="Main">
                                <div class="hd_input_row">
                                    <label class="hd_label_input" style="width: max-content" data-type="radio" data-id="hdq_correct_answer" for="hdq_correct_answer_<?php echo $i . '_' . esc_attr($outcome["id"]); ?>">
                                        <div class="hd_options_check">
                                            <input type="checkbox" title="Correct" data-answer-type="selected_<?php echo esc_attr($outcome["id"]); ?>" data-id="hdq_correct_answer" class="hd_option hd_check_input hdq_answer_item_input" data-type="radio" value="yes" name="hdq_correct_answer_<?php echo $i . '_' . esc_attr($outcome["id"]); ?>" autocomplete="off" id="hdq_correct_answer_<?php echo $i . '_' . esc_attr($outcome["id"]); ?>" <?php echo $checked; ?> />
                                            <span class="hd_toggle"><span class="hd_aria_label" style="display: none">Correct answer</span></span>
                                        </div>
                                        <span><?php echo $outcome["label"]; ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </td>
            </tr>

        <?php
        }
        ?>
    </tbody>
</table>
<?php
$html = ob_get_clean();

$res = new stdClass();
$res->status = "success";
$res->html = $html;
echo json_encode($res);
