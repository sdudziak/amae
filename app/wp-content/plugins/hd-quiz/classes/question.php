<?php

class _hdq_question
{
    public $quiz_id = 0;
    public $question_id = 0;
    public $data = array(); // product data
    public $fields = array();
    public $quiz = array(); // store quiz settings (needed for outcomes)
    public $quiz_type = "general";
    public $max_answers = 10;

    // looks for method first, 
    // then looks for function named 
    // hdq_question_type_{question_type_name} for admin,
    // hdq_render_question_type_{question_type_name} for quiz render,
    public $question_types = array(
        "general" => array(
            array(
                "value" => "multiple_choice_text",
                "label" => "Multiple choice - text"
            ),
            array(
                "value" => "multiple_choice_image",
                "label" => "Multiple choice - image"
            ),
            array(
                "value" => "select_all_apply_text",
                "label" => "Select all that apply - text"
            ),
            array(
                "value" => "select_all_apply_image",
                "label" => "Select all that apply - image"
            ),
            array(
                "value" => "text_based_answer",
                "label" => "Text-based answers"
            ),
            array(
                "value" => "question_as_title",
                "label" => "Question as title"
            )
        ),
        "personality" => array(
            array(
                "value" => "personality_multiple_choice_text",
                "label" => "Multiple choice - text"
            ),
            array(
                "value" => "personality_multiple_choice_image",
                "label" => "Multiple choice - image"
            ),
            array(
                "value" => "question_as_title",
                "label" => "Question as title"
            )
        ),
    );

    function __construct($quiz_id = 0, $question_id = 0, $flat = false)
    {
        $this->quiz_id = intval($quiz_id);
        $this->question_id = intval($question_id);
        $this->getQuiz();

        $quiz_type = sanitize_text_field(get_term_meta($this->quiz_id, "hdq_quiz_type", true));
        if ($quiz_type === "personality") {
            $this->quiz_type = $quiz_type;
        }
        $this->fields();
        $this->get($flat);
        $this->question_types = apply_filters("hdq_add_question_type", $this->question_types);

        if (defined("HDQ_MAX_ANSWERS")) {
            $this->max_answers = intval(HDQ_MAX_ANSWERS);
        }
    }

    private function fields()
    {
        $question_types = array();
        foreach ($this->question_types[$this->quiz_type] as $type) {
            array_push(
                $question_types,
                array(
                    "label" => $type["label"],
                    "value" => $type["value"]
                )
            );
        }
        $question_types = json_encode($question_types);

        $fields = '[
	{
		"label": "' . esc_attr(trim(__("Main", "hd-quiz"))) . '",
		"id": "Main",
		"children": [
            {
                "column_type": "1-1",
                "type": "column",
                "children": [
                    { "id": "question_type", "label": "Question type", "required": "true", "default": "", "tooltip": "", "description": "", "placeholder": "", "prefix": "", "postfix": "", "options": ' . $question_types . ', "type": "select" },
                     {
						"id": "paginate",
						"label": "Paginate",
						"required": "",
						"default": "",
						"tooltip": "",
						"tooltip": "Start a new page with this question. User will need to select \"next\" to see this question or ones below it",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					}
                ]
            },			
			{ "id": "question_answers", "type": "hdq_field_answers"},
            { "id": "quiz_id", "type": "hidden", "default": "' . $this->quiz_id . '"},
            { "id": "question_id", "type": "hidden", "default": "' . $this->question_id . '"}
		]
	},
	{
		"label": "' . esc_attr(trim(__("Extra", "hd-quiz"))) . '",
		"id": "Extra",
		"children": [
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "tooltip",
						"label": "Tooltip",
						"required": "",
						"default": "",
						"placeholder": "",
						"description": "",
						"tooltip": "This popup is an example of a tooltip. You can use these to add additional context or information to your questions.",
						"prefix": "",
						"postfix": "",
						"type": "text"
					},
					{ "id": "featured_image", "label": "Question featured image", "required": "", "default": "", "tooltip": "", "description": "", "type": "image" }
				]
			},
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "extra_content",
						"label": "Extra content",
						"media": "yes",
						"default": "",
						"required": "",
						"tooltip": "",
						"description": "This content will only appear if the user gets the question wrong. You can force this to appear even if the user is correct in the quiz settings.",
						"type": "editor"
					},
					{
						"id": "before_question_content",
						"label": "Before question content",
						"media": "yes",
						"default": "",
						"required": "",
						"tooltip": "Please note that how your media looks and behaves can be modified by your theme or another plugin. Because of this, I cannot offer full support for this feature.",
						"description": "This content will appear before the question title, and after the featured image. It is a great place to add audio or video to your question.",
						"type": "editor"
					}
				]
			}
		]
	}
]
';
        $data = json_decode($fields, true);

        $fields = array();
        foreach ($data as $k => $tab) {
            $fields[$tab["id"]] = $tab;
        }

        $fields = apply_filters("hdq_add_question_field", $fields);

        $this->fields = $fields;
    }

    public function getQuestionType($question_type)
    {
        $question_type = sanitize_text_field($question_type);
        if (method_exists($this, $question_type)) {
            $this->$question_type($this->data["question_answers"]);
        } else {
            echo '{"status": "fail", "message": "No render found for this question type: ' . esc_attr($question_type) . '"}';
        }
    }

    private function getQuiz()
    {
        // make sure quiz even exists
        $quiz_term = get_term($this->quiz_id, "quiz");
        if (!$quiz_term || $this->quiz_id === 0) {
            $res = new stdClass();
            $res->status = "fail";
            $res->html = '3. ERROR: Quiz ID ' . $this->quiz_id . ' not found.';
            echo json_encode($res);
            die();
        }

        $quiz = new _hdq_quiz($this->quiz_id, true);
        $this->quiz = $quiz->data;
    }

    private function getTitle($data, $flat = false)
    {
        // stop title from being sanitzed as text_field
        $title = "";
        if (isset($data["question_title"])) {
            if ($flat) {
                if (isset($data["question_title"]["value"])) {
                    $title = $data["question_title"]["value"];
                } else {
                    $title = $data["question_title"];
                }
            } else {
                $title = $data["question_title"]["value"];
            }
        }
        $title = wp_kses_post(wp_slash($title));
        return $title;
    }

    private function validateAccess()
    {
        if (!current_user_can("publish_posts")) {
            // must be author. make sure Ids match
            $author_id = intval(get_term_meta($this->quiz_id, "hdq_author_id", true));
            $user_id = get_current_user_id();
            if ($author_id !== $user_id) {
                $res = new stdClass();
                $res->status = "fail";
                $res->message = "Permission denied";
                return $res;
            }
        }
        return false;
    }

    private function get($flat)
    {
        if ($this->question_id > 0) {
            if (!get_post_status($this->question_id)) {
                $res = new stdClass();
                $res->status = "fail";
                $res->html = 'ERROR: Question ID ' . $this->question_id . ' not found.';
                echo json_encode($res);
                die();
            }
        }

        $data = get_post_meta($this->question_id, "hdq_question_data", true);
        if (!$data) {
            $data = array();
        }
        $data = $this->mapOld($data);
        $title = $this->getTitle($data, $flat);

        $fields = new hdquiz\_hd_fields($this->fields, $data);
        $data = $fields->get_values($flat);

        if ($flat) {
            $data["question_title"] = $title;
        } else {
            $data["question_title"]["value"] = $title;
        }

        $this->data = $data;
    }

    private function mapOld($data)
    {
        if (isset($data["question_type"])) {
            return $data; // we've already saved new data
        }

        // keep old data as separate field just in case user needs to revert
        $d = get_post_meta($this->question_id, "question_data", true);
        if (!$d) {
            return $data;
        }

        $data_clean = array();
        if (!isset($d["selected"])) {
            return $data;
        }
        if ($this->quiz_type !== "general") {
            return $data;
        }

        $map = array(
            "question_type" => "question_type", // need to remap again
            "paginate" => "paginate",
            "tooltip" => "tooltip",
            "featured_image" => "featured_image",
            "extra_text" => "extra_content",
        );

        $question_types = array(
            "text_based" => "text_based_answer",
            "title" => "question_as_title"
        );

        foreach ($map as $k => $setting) {
            if (isset($d[$k]["value"])) {
                if (is_array($d[$k]["value"])) {
                    $d[$k]["value"] = $d[$k]["value"][0];
                }
                $data_clean[$setting] = array("value" => $d[$k]["value"]);

                if ($k === "question_type") {
                    $value = $data_clean[$setting]["value"];
                    if (isset($question_types[$value])) {
                        $value = $question_types[$value];
                    }
                    $data_clean[$setting]["value"] = $value;
                }
            }
        }

        $data_clean["question_answers"] = array("value" => $this->getMapOldAnswers($d["selected"], $d["answers"]));
        return $data_clean;
    }

    private function getMapOldAnswers($selected, $answers)
    {
        $data = array();

        foreach ($answers["value"] as $k => $answer) {
            if ($answer["answer"] != "") {
                $correct = "";
                if (in_array($k + 1, $selected["value"])) {
                    $correct = "yes";
                }

                $row = array(
                    "value" => $answer["answer"], // $answer["answer"],
                    "image" => $answer["image"],
                    "selected" => $correct
                );

                array_push(
                    $data,
                    $row
                );
            }
        }
        return $data;
    }

    public function delete()
    {
        if (!hdq_user_permission()) {
            $res = new stdClass();
            $res->status = "fail";
            $res->message = "Permission denied";
            return $res;
        }

        $res = $this->validateAccess($this->data);

        if ($res !== false) {
            return $res;
        }

        $res = new stdClass();
        $res->status = "fail";

        $post = wp_delete_post($this->question_id, true);
        if (!$post || $post === null) {
            $res->message = "error deleting question with ID " . $this->question_id;
            return $res;
        }

        $res->status = "success";
        $res->action = new \stdClass();
        $res->action->name = "HDQ.router.views.quiz";
        $res->action->data = array("");
        $res->action->data2 = array($this->quiz_id);
        return $res;
    }

    public function save($data)
    {
        if (!hdq_user_permission()) {
            $res = new stdClass();
            $res->status = "fail";
            $res->message = "Permission denied";
            return $res;
        }

        $res = $this->validateAccess($this->data);
        if ($res !== false) {
            return $res;
        }


        if ($this->question_id === 0) {
            return $this->create($data);
        }

        $title = $this->getTitle($data);

        $data = new hdquiz\_hd_fields($this->fields, $data, "horizontal");
        $data = $data->get_values(false);

        $data["question_title"]["value"] = $title;

        update_post_meta($this->question_id, "hdq_question_data", wp_slash($data));

        // in case title was updated
        $post_main = array(
            'ID'           => $this->question_id,
            'post_title'   => $data["question_title"]["value"]
        );
        wp_update_post($post_main);

        $res = new stdClass();
        $res->status = "success";
        $res->question_id = $this->question_id;
        return $res;
    }

    private function create($data)
    {
        $total = wp_count_posts('post_type_questionna');
        $total = $total->publish + 1;

        $post_information = array(
            'post_title' => $data["question_title"]["value"],
            'post_content' => '', // post_content is required, so we leave blank
            'post_type' => 'post_type_questionna',
            'post_status' => 'publish',
            'menu_order' => $total // always set as the last question of the quiz
        );
        $this->question_id = wp_insert_post($post_information);
        $data["question_id"]["value"] = $this->question_id;


        $data = new hdquiz\_hd_fields($this->fields, $data, "horizontal");
        $data = $data->get_values(false);

        update_post_meta($this->question_id, "hdq_question_data", $data);
        wp_set_post_terms($this->question_id, array($this->quiz_id), "quiz");

        $res = new stdClass();
        $res->status = "success";
        $res->question_id = $this->question_id;
        $res->action = new stdClass();
        $res->action->name = "HDQ.router.views.question.update";
        $res->action->data = array($this->question_id);
        return $res;
    }


    private function getHeader()
    {
        $title = "";
        if ($this->question_id > 0) {
            $title = get_the_title($this->question_id);
        }
        ob_start();
?>
        <div id="hdq_question_header">
            <div id="hdq_question_header_left">
                <a href="#/quiz/<?php echo esc_attr($this->quiz_id); ?>" class="hdq_button hd_kb" tabindex="0" title="Go back to quiz page"><span class="dashicons dashicons-arrow-left-alt"></span> <?php _e("Back to quiz", "hd-quiz"); ?></a>
                <a href="#/question/<?php echo esc_attr($this->quiz_id); ?>/0" onclick="HDQ.reload(this)" class="hdq_button hd_kb" tabindex="0" title="Add another question to this quiz"><span class="dashicons dashicons-plus"></span> <?php _e("Add new question", "hd-quiz"); ?></a>
            </div>
            <div id="hdq_question_header_right">
                <div id="hd_delete_question" class="hdq_button hdq_button_warning hd_kb" role="button" data-quiz="<?php echo esc_attr($this->quiz_id); ?>" data-id="<?php echo esc_attr($this->question_id); ?>" tabindex="0" title="Delete this question"><span class="dashicons dashicons-trash"></span></div>
                <div id="hd_save" data-action="hdq_save_question" class="hdq_button hdq_button_primary hd_kb" data-label="<?php esc_attr(_e("Save", "hd-quiz")); ?>" role="button" tabindex="0"><span class="dashicons dashicons-sticky"></span> <?php _e("Save", "hd-quiz"); ?></div>
            </div>
        </div>

        <div>
            <div class="hd_input_item">
                <label class="hd_input_label" for="question_title"><?php _e("Question title", "hd-quiz"); ?>
                    <span class="hd_tooltip_item">?<span class="hd_tooltip">
                            <div class="hd_tooltip_content">You can use basic HTML tags such as <code>&lt;br/&gt;</code>, <code>&lt;strong&gt;</code>, and <code>&lt;sup&gt;</code> to help with formatting</div>
                        </span></span>
                </label>
                <input type="text" data-type="text" data-required="required" class="hderp hd_input hdq_input_large" id="question_title" value="<?php echo esc_attr($title); ?>" placeholder="Enter question here..." data-tab="Main" />
            </div>
        </div>

<?php
        return ob_get_clean();
    }

    public function display()
    {
        if (!hdq_user_permission()) {
            $res = new stdClass();
            $res->status = "fail";
            $res->message = "Permission denied";
            return $res;
        }

        $res = $this->validateAccess($this->data);
        if ($res !== false) {
            return $res;
        }

        $fields = new hdquiz\_hd_fields($this->fields, $this->data, "vertical");
        $html = $this->getHeader();
        $html .= $fields->display();
        $res = new stdClass();
        $res->status = "success";
        $res->html = $html;
        $res->question_id = $this->question_id;
        $res->quiz_id = $this->quiz_id;
        echo json_encode($res);
    }

    /* Admin Renderer */
    private function doesAnswerExist($data)
    {
        if (isset($data)) {
            return true;
        }
        return false;
    }

    private function multiple_choice_text($data)
    {
        include dirname(__FILE__) . '/questions/multiple-choice-text.php';
    }

    private function multiple_choice_image($data)
    {
        include dirname(__FILE__) . '/questions/multiple-choice-image.php';
    }

    private function select_all_apply_text($data)
    {
        include dirname(__FILE__) . '/questions/select-all-apply-text.php';
    }

    private function select_all_apply_image($data)
    {
        include dirname(__FILE__) . '/questions/select-all-apply-image.php';
    }

    private function text_based_answer($data)
    {
        include dirname(__FILE__) . '/questions/text-based-answer.php';
    }

    private function question_as_title($data)
    {
        include dirname(__FILE__) . '/questions/question-as-title.php';
    }

    private function personality_multiple_choice_text($data)
    {
        include dirname(__FILE__) . '/questions/personality-multiple-choice-text.php';
    }

    private function personality_multiple_choice_image($data)
    {
        include dirname(__FILE__) . '/questions/personality-multiple-choice-image.php';
    }
}
