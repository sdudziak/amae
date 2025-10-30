<?php

/* Add custom fields to HDFields
------------------------------------------------------- */
function hdq_add_field_types($fields)
{
    $fields["hdq_field_question_order"] = array(
        "value" => "sanitize_hdq_field_question_order",
        "components" => array()
    );

    $fields["hdq_field_personality_results"] = array(
        "value" => "sanitize_hdq_field_personality_results",
        "components" => array(
            "label",
            "description"
        )
    );

    $fields["hdq_field_answers"] = array(
        "value" => "sanitize_hdq_field_answers",
        "components" => array()
    );

    return $fields;
}
add_filter("hd_add_new_field_types", "hdq_add_field_types", 10, 1);

/* Question Order
------------------------------------------------------- */
function render_hdq_field_question_order($field)
{
    return ""; // we don't actually need anything
}

function sanitize_hdq_field_question_order($value)
{
    if (!is_array($value)) {
        $value = array();
    }
    $value = array_map("intval", $value);
    return $value;
}

/* Quiz settings Personality Outcomes
------------------------------------------------------- */
function render_hdq_field_personality_results($field)
{

    $data = $field["value"];
    if (!$data || $data == "") {
        $data = array(
            array(
                "label" => "Result A",
                "id" => "result_a",
                "content" => ""
            )
        );
    }

    $f = new \hdquiz\_hd_fields();
    $field["hasParent"] = true;

    ob_start();
?>
    <div class="hd_input_item">
        <h3><?php echo $field["label"]; ?></h3>
        <?php echo $f->get_description($field); ?>

        <div style="display: grid; justify-content: end;">
            <div id="hdq_add_new_personality_outcome" class="hdq_button hdq_button_secondary hd_kb" tabindex="0" role="button" title="Add new outcome" target="_blank"><span class="dashicons dashicons-plus"></span> <?php _e("Add", "hd-quiz"); ?></div>
        </div>

        <div id="<?php echo esc_attr($field["id"]); ?>" class="hdq_field_personality_results hderp" data-type="hdq_field_personality_results">
            <?php
            foreach ($data as $k => $result) {
            ?>
                <div class="hd_input_item" style="position: relative" ;>
                    <?php
                    // don't allow removal of first. We always need at least one result
                    if ($k !== 0) {
                        echo '<div class="hdq_remove_outcome" title="Remove this outcome">x</div>';
                    }
                    ?>
                    <label class="hd_input_label" for="hdq_result_<?php echo esc_attr($result["id"]); ?>"><span class="hd_required_icon"></span> <?php _e("Outcome title", "hd-quiz"); ?> <span class="hd_tooltip_item">?<span class="hd_tooltip">
                                <div class="hd_tooltip_content">NOTE: If you rename this outcome, you will need to re-edit your questions to set the correct answer.</div>
                            </span></span></label>
                    <input type="text" data-type="text" data-required="required" class="hd_input hdq_outcome_label" id="hdq_result_<?php echo esc_attr($result["id"]); ?>" value="<?php echo esc_attr($result["label"]); ?>" placeholder="Result A" data-tab="Results" />
                    <?php wp_editor(stripslashes(urldecode($result["content"])), "hdq_result_content_" . $result["id"], array('textarea_name' => "hdq_result_content_" . $result["id"], 'editor_class' => "hd_input hd_editor_input", 'media_buttons' => true, 'textarea_rows' => 20, 'quicktags' => true, 'editor_height' => 240)); ?>
                </div>
            <?php } ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function sanitize_hdq_field_personality_results($value)
{
    if (!is_array($value)) {
        return array();
    }

    $outcomes = array();
    foreach ($value as $outcome) {
        $arr = array(
            "label" => sanitize_text_field($outcome["label"]),
            "id" => sanitize_text_field($outcome["id"]),
            "content" => wp_kses_post($outcome["content"])
        );
        array_push($outcomes, $arr);
    }
    return $outcomes;
}


/* Question answers
------------------------------------------------------- */
function render_hdq_field_answers($field)
{
    $data = $field["value"];
    if (!$data || $data == "") {
        $data = array();
    }
    ob_start();
?>

    <div id="question_answers" class="hderp" data-type="hdq_field_answers"></div>

<?php
    return ob_get_clean();
}

function sanitize_hdq_field_answers($value)
{
    if (!is_array(($value))) {
        $value = array();
    }

    $allowed_html = array(
        'a' =>  array(
            'id' => array(),
            'class' => array(),
            'href' => array(),
            'title' => array(),
            'target' => array()
        ),
        'p' => array(),
        'span' => array(
            'id' => array(),
            'class' => array()
        ),
        'strong' => array(),
        'em' => array(),
        'code' => array(),
        'sup' => array(),
        'sub' => array(),
        'small' =>  array(
            'id' => array(),
            'class' => array()
        ),
        'br' => array()
    );

    // ENHANCE: Selected and $outcomes will need to become arrays to handle weighted values
    $array = array();
    foreach ($value as $v) {
        $value = "";
        if (isset($v["value"])) {
            $value = wp_kses($v["value"], $allowed_html);
        }
        $image = 0;
        if (isset($v["image"])) {
            $image = intval($v["image"]);
        }
        $selected = "";
        if (isset($v["selected"])) {
            $selected = sanitize_text_field($v["selected"]);
        }
        $weight = 1;
        if (isset($v["weight"])) {
            $weight = intval($v["weight"]);
        }

        $arr = array(
            "value" => $value,
            "image" => $image,
            "selected" => $selected,
            "weight" => $weight
        );

        foreach ($v as $k => $outcome) {
            if (!isset($arr[$k])) {
                $arr[$k] = sanitize_text_field($outcome);
            }
        }

        array_push($array, $arr);
    }
    return $array;
}
