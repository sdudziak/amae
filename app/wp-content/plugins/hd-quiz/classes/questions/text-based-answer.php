<?php
ob_start();
?>
<p>Instead of multiple choice answers, the quiz taker will have to type their answers. <br />Answers are case insensitive<span class="hd_tooltip_item">?<span class="hd_tooltip"><span class="hd_tooltip_content">As in uppercase or lowercase letters don't matter</span></span></span>. Each answer will correspond with an accepted correct answer, so it's best to include common spelling mistakes.</p>
<p><small>You can add an asterisks * to the end of a word to allow all extentions of that word. Example: hop* would allow "hop, hope, hopping" etc to be accepted.</small></p>
<table id="hdq_answers_table" class="hdq_table hdq_answers_type_seelct_all_apply_image">
    <thead class="hdq_answer_row_header">
        <tr>
            <th width="1">#</th>
            <th>Answer</th>
        </tr>
    </thead>
    <tbody>
        <?php
        for ($i = 0; $i < $this->max_answers; $i++) {
            $answer = array(
                "value" => "",
                "selected" => ""
            );
            if (isset($data[$i]) && $this->doesAnswerExist($data[$i])) {
                if (isset($data[$i]["value"])) {
                    $answer["value"] = $data[$i]["value"];
                }
            }
        ?>
            <tr class="hdq_answer_row">
                <td>#<?php echo $i + 1; ?></td>
                <td><input type="text" class="hd_input hdq_answer_item_input" data-answer-type="value" value="<?php echo esc_attr($answer["value"]); ?>" placeholder="enter answer..." /></td>
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
