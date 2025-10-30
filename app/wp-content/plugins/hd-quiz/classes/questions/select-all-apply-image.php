<?php
ob_start();
?>
<p>With this question type, the user will need to correctly select ALL of the correct answers in order to be awarded a point for this question</p>
<table id="hdq_answers_table" class="hdq_table hdq_answers_type_multiple_choice_image">
    <thead class="hdq_answer_row_header">
        <tr>
            <th width="1">#</th>
            <th>Answer<span class="hd_tooltip_item">?<span class="hd_tooltip"><span class="hd_tooltip_content">You can use basic HTML tags or even shortcodes to help with formatting.</span></span></span></th>
            <th width="225" class="hdq_answer_as_image">Image</th>
            <th width="30" class="hdq_answer_selected">Correct</th>
        </tr>
    </thead>
    <tbody>
        <?php
        for ($i = 0; $i < $this->max_answers; $i++) {
            $answer = array(
                "value" => "",
                "selected" => "",
                "image" => 0
            );
            $checked = "";
            if (isset($data[$i]) && $this->doesAnswerExist($data[$i])) {
                if (isset($data[$i]["value"])) {
                    $answer["value"] = $data[$i]["value"];
                }
                if (isset($data[$i]["selected"]) && $data[$i]["selected"] === "yes") {
                    $answer["selected"] = "yes";
                    $checked = "checked";
                }
                if (isset($data[$i]["image"])) {
                    $answer["image"] = $data[$i]["image"];
                }
            }
        ?>
            <tr class="hdq_answer_row">
                <td>#<?php echo $i + 1; ?></td>
                <td><input type="text" class="hd_input hdq_answer_item_input" data-answer-type="value" value="<?php echo esc_attr($answer["value"]); ?>" placeholder="enter answer..." /></td>
                <td>
                    <div class="hd_input_item">
                        <div data-title="Set image" data-answer-type="image" data-button="Set image" data-multiple="no" data-type="image" data-required="" class="hd_image hdq_answer_item_input" data-value="<?php echo esc_attr($answer["image"]); ?>" id="hdq_answers_image_<?php echo $i; ?>" role="button" title="upload image" data-tab="Main">
                            <?php
                            $activeClass = "";
                            if ($answer["image"] == 0 || $answer["image"] == "") {
                                echo 'set image';
                            } else {
                                $activeClass = "active";
                                echo wp_get_attachment_image($answer["image"], "medium");
                            }
                            ?>
                        </div>
                        <span class="hd_image_remove <?php echo $activeClass; ?>" data-type="image" data-answer-type="image" data-id="hdq_answers_image_<?php echo $i; ?>" onclick="_hd.images.remove(this)" role="button">remove image</span>
                    </div>
                </td>
                <td>
                    <div class="hdq_answer_item">
                        <div data-type="checkbox" data-required="" class="hd_input_checkbox hdq_answer_input" data-tab="Main">
                            <div class="hd_input_row">
                                <label class="hd_label_input" data-type="radio" data-id="hdq_correct_answer" for="hdq_correct_answer_<?php echo $i; ?>">
                                    <div class="hd_options_check">
                                        <input type="checkbox" title="Correct" data-answer-type="selected" data-id="hdq_correct_answer" class="hd_option hd_check_input hdq_answer_item_input" data-type="radio" value="yes" name="hdq_correct_answer_<?php echo $i; ?>" autocomplete="off" id="hdq_correct_answer_<?php echo $i; ?>" <?php echo $checked; ?> />
                                        <span class="hd_toggle"><span class="hd_aria_label" style="display: none">Correct answer</span></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
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
$res->action = new \stdClass();
$res->action->name = "images";
echo json_encode($res);
