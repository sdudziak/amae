<?php

class _hdq_quiz
{
	public $quiz_id = 0;
	public $data = array(); // product data
	public $fields = array();
	public $term = null;
	public $quiz_type = "general";
	public $paged = 1; // for pagination
	public $quiz_name = "";

	function __construct($quiz_id = 0, $flat = false)
	{
		$this->quiz_id = intval($quiz_id);

		$quiz_type = sanitize_text_field(get_term_meta($this->quiz_id, "hdq_quiz_type", true));
		if ($quiz_type === "personality") {
			$this->quiz_type = $quiz_type;
		}

		$this->term = get_term($this->quiz_id, "quiz");
		$this->quiz_name = $this->term->name;

		$this->fields();
		$this->get($flat);
	}

	private function fields()
	{
		$fields = '[]';

		if ($this->quiz_type === "general") {
			$fields = $this->getFieldsGeneral();
		} else {
			$fields = $this->getFieldsPersonality();
		}

		$this->fields = $fields;
	}

	private function getFieldsGeneral()
	{
		$fields = '[
	{
		"label": "' . esc_attr(trim(__("Results", "hd-quiz"))) . '",
		"id": "Results",
		"children": [
			{				
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "quiz_pass_percentage",
						"label": "Quiz pass percentage",
						"required": true,
						"default": "70",
						"tooltip": "",
						"description": "The minimum percentage of questions a user needs to get correct in order to see the Quiz Pass Content",
						"placeholder": "",
						"attributes": [
							{
								"name": "min",
								"value": 0
							},
							{
								"name": "max",
								"value": 100
							}
						],
						"prefix": "",
						"postfix": "%",
						"options": "",
						"type": "float"
					},
					{
						"id": "hide_questions_after_completion",
						"label": "Hide questions after quiz completion",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "This will automatically hide the questions once a quiz has been completed so that only the results are shown.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{ "id": "quiz_pass_content", "label": "Quiz pass content", "media": "yes", "default": "", "required": "", "tooltip": "This content will show once the quiz has been completed and the user has passed the quiz", "description": "", "type": "editor" },
					{ "id": "quiz_fail_content", "label": "Quiz fail content", "media": "yes", "default": "", "required": "", "tooltip": "This content will show once the quiz has been completed and the user has failed the quiz", "description": "", "type": "editor" },
					{
						"id": "quiz_redirect_url",
						"label": "Quiz redirect URL",
						"placeholder": "",
						"type": "website",
						"description": "If you want to automatically redirect to another page on quiz completion."
					},
					{
						"id": "quiz_redirect_delay",
						"label": "Quiz redirect delay",
						"default": "0",
						"tooltip": "",
						"description": "How many seconds to wait after results to redirect.",
						"placeholder": "",
						"attributes": [
							{
								"name": "min",
								"value": 0
							},
							{
								"name": "max",
								"value": 100
							}
						],
						"prefix": "",
						"postfix": "seconds",
						"type": "integer"
					}
				]
			}
		]
	},
	{
		"label": "' . esc_attr(trim(__("Marking", "hd-quiz"))) . '",
		"id": "Marking",
		"children": [
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "mark_questions",
						"label": "Highlight questions as correct or incorrect",
						"required": "",
						"default": "yes",
						"tooltip": "",
						"description": "This will show the user which questions they got right, and which they got wrong on quiz completion.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "mark_answers",
						"label": "Show correct answers on completion",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "This feature goes the extra step and shows what the correct answer was in the case that the user selected the wrong one.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "immediately_mark_answers",
						"label": "Immediately mark answer as correct or incorrect",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "Enabling this will show if the answer was right or wrong as soon as an answer has been selected.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "stop_answer_reselect",
						"label": "Stop users from changing their answers",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "Enabling this will stop users from being able to change their answer once one has been selected.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "force_show_extra_content",
						"label": "Always show extra content",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "Always show the content that appears if the user got the question wrong, even if the answer was correct.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "force_answers",
						"label": "Force users to answer all questions",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "This will check to make sure that all questions have been answered before submission. <small><strong>NOTE:</strong> will not work with timer-per-question. If user does not answer in time, that question is marked as incorrect.</small>",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					}
				]
			}
		]
	},
	{
		"label": "' . esc_attr(trim(__("Timer", "hd-quiz"))) . '",
		"id": "Timer",
		"children": [
			{ "content": "If the timer is enabled, the quiz will be hidden behind a \"START QUIZ\" button. You can rename this button from the HD Quiz -> About / Options page", "type": "content" },
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "timer",
						"label": "Timer / countdown",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "Enter how many seconds total. So 3 minutes would be 180. Please note that the timer will NOT work if the WP Pagination feature is being used.",
						"placeholder": "60",
						"prefix": "",
						"postfix": "seconds",
						"options": "",
						"type": "integer"
					},
					{
						"id": "timer_per_question",
						"label": "Per question",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "Enable this if you want the timer to be per question instead of for the entire quiz.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					}
				]
			}
		]
	},
	{
		"label": "' . esc_attr(trim(__("Advanced", "hd-quiz"))) . '",
		"id": "Advanced",
		"children": [
            { "content": "If you are having trouble with either the Randomization options, or the Pool of Questions, make sure you do not have page caching enabled on the pages with quizzes. Your caching solution is creating a static version of the quiz once, and loading that same order each time.", "type": "content" },
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "share_quiz_results",
						"label": "Share quiz results",
						"required": "",
						"description": "Show the social media share icons in the quiz results. <small>if the global settings \"Allow social media sharing\" is disabled, then this will be disabled too.</small>",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"default": "yes",
						"type": "radio"
					},
					{
						"id": "results_position",
						"label": "Show results above or below quiz",
						"required": "",
						"default": "above",
						"tooltip": "",
						"description": "HD Quiz will automatically scroll to the position of your results on quiz completion. It is recommend to keep default if you are using any kind of marking.",
						"placeholder": "",
						"options": [
							{ "label": "Above quiz", "value": "above" },
							{ "label": "below quiz", "value": "below" }
						],
						"type": "radio"
					},
					{
						"id": "random_question_order",
						"label": "Randomize question order",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "Please note that randomizing the questions is NOT possible WP Pagination is enabled. <small>and also not a good idea to use this if you are using the \"questions as title\" option for any questions attached to this quiz</small>",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "random_answer_order",
						"label": "Randomize answer order",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "This feature will randomize the order that each answer is displayed and is compatible with WP Pagination.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "pool_of_questions",
						"label": "Use pool of questions",
						"required": "",
						"default": "",
						"tooltip": "Set to 0 or leave blank to disable",
						"description": "If you want each quiz to randomly grab a number of questions from the quiz, then enter that amount here. <small>Example: you might have 100 questions attached to this quiz, but entering 20 here will make the quiz randomly grab 20 of the questions on each load.</small>",
						"placeholder": "",
						"prefix": "",
						"postfix": "questions",
						"options": "",
						"type": "integer"
					},
					{
						"id": "wp_pagination",
						"label": "WP pagination",
						"required": "",
						"default": "",
						"tooltip": "WARNING: It is generally not recommended using this feature unless you have a specific use case for it.",
						"description": "WP Paginate will force this number of questions per page, and force new page loads for each new question group. <small>The only benefit of this is for additional ad views. The downside is reduced compatibility of features. It is recommended to use the \"paginate\" option on each question instead.</small>",
						"placeholder": "",
						"prefix": "",
						"postfix": "per page",
						"options": "",
						"type": "integer"
					},
					{
						"id": "rename_quiz",
						"label": "Rename quiz",
						"required": true,
						"default": "' . esc_attr(trim($this->quiz_name)) . '",
						"placeholder": "",
						"type": "text"
					}
				]
			}
		]
	}
]
';

		$data = json_decode($fields, true);
		if ($data === null) {
			$data = json_decode('[
	{
		"label": "Error",
		"id": "Results",
		"children": [
			{
				"type": "content",
				"content": "There was an issue decoding the JSON string for these quiz settings. This is usually caused by use of an invalid character in one of the settings. Please <a href = \"https://hdplugins.com/forum/hd-quiz-support/\" target = \"_blank\">request support</a> for help and provide useful information such as what language you are using, or if you can think of anything special might have done to trigger this."
			}
		]
	}
]
', true);
		}
		$fields = array();
		foreach ($data as $k => $tab) {
			$fields[$tab["id"]] = $tab;
		}

		$fields = apply_filters("hdq_add_quiz_field", $fields);
		return $fields;
	}

	private function getFieldsPersonality()
	{
		$fields = '[
	{
		"label": "' . esc_attr(trim(__("Results", "hd-quiz"))) . '",
		"id": "Results",
		"children": [
			{ "content": "This is a brand new quiz type. New options and features will be added to it as development continues.\nPlease feel free to contact me on the HDPlugins support forum to provide any feedback.\n\nEach answer will award points towards one of these outcomes. The outcome with the highest score will be the final result. In the event that multiple outcomes are possible (a tie), the first possible outcome will be the final result.", "type": "content" },
			{ "id": "personality_results", "type": "hdq_field_personality_results", "label": "Outcomes", "description": "<strong>Renaming an outcome is the same as creating a new one</strong>, so remember to edit your questions to set the correct answers after." }
		]
	},
	{
		"label": "' . esc_attr(trim(__("Advanced", "hd-quiz"))) . '",
		"id": "Advanced",
		"children": [
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "share_quiz_results",
						"label": "Share quiz results",
						"required": "",
						"description": "Show the social media share icons in the quiz results. <small>if the global settings \"Allow social media sharing\" is disabled, then this will be disabled too.</small>",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"default": "yes",
						"type": "radio"
					},
					{
						"id": "results_position",
						"label": "Show results above or below quiz",
						"required": "",
						"default": "above",
						"tooltip": "",
						"description": "HD Quiz will automatically scroll to the position of your results on quiz completion. It is recommend to keep default if you are using any kind of marking.",
						"placeholder": "",
						"options": [
							{ "label": "Above quiz", "value": "above" },
							{ "label": "below quiz", "value": "below" }
						],
						"type": "radio"
					},
					{
						"id": "random_question_order",
						"label": "Randomize question order",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "The questions will be in a random order on each pageload. <small>just a reminder to disable page caching on pages with this quiz</small>",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "random_answer_order",
						"label": "Randomize answer order",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "This feature will randomize the order that each answer is displayed.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "force_answers",
						"label": "Force users to answer all questions",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "This will check to make sure that all questions have been answered before submission. <small><strong>NOTE:</strong> will not work with timer-per-question. If user does not answer in time, that question is marked as incorrect.</small>",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "hide_questions_after_completion",
						"label": "Hide questions after quiz completion",
						"required": "",
						"default": "",
						"tooltip": "",
						"description": "This will automatically hide the questions once a quiz has been completed so that only the results are shown.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "quiz_redirect_url",
						"label": "Quiz redirect URL",
						"placeholder": "",
						"type": "website",
						"description": "If you want to automatically redirect to another page on quiz completion."
					},
					{
						"id": "quiz_redirect_delay",
						"label": "Quiz redirect delay",
						"default": "0",
						"tooltip": "",
						"description": "How many seconds to wait after results to redirect.",
						"placeholder": "",
						"attributes": [
							{
								"name": "min",
								"value": 0
							},
							{
								"name": "max",
								"value": 100
							}
						],
						"prefix": "",
						"postfix": "seconds",
						"type": "integer"
					},					
					{
						"id": "rename_quiz",
						"label": "Rename quiz",
						"required": true,
						"default": "' . esc_attr(trim($this->quiz_name)) . '",					
						"placeholder": "",
						"type": "text"
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

		$fields = apply_filters("hdq_add_quiz_field_personality", $fields);
		return $fields;
	}

	private function get($flat)
	{
		$data = get_term_meta($this->quiz_id, "quiz_data", true);
		$data = $this->mapOld($data);
		$fields = new hdquiz\_hd_fields($this->fields, $data);
		$data = $fields->get_values($flat);
		if ($flat) {
			$data["quiz_id"] = $this->quiz_id;
			$data["quiz_name"] = $this->term->name;
		}
		$this->data = $data;
	}

	private function mapOld($data)
	{
		$data_clean = array();
		if (!isset($data["quiz_pass_text"])) {
			return $data;
		}
		if ($this->quiz_type !== "general") {
			return $data;
		}

		$map = array(
			"quiz_pass_percentage" => "quiz_pass_percentage",
			"hide_questions" => "hide_questions_after_completion",
			"quiz_pass_text" => "quiz_pass_content",
			"quiz_fail_text" => "quiz_fail_content",
			"show_results" => "mark_questions",
			"show_results_correct" => "mark_answers",
			"show_results_now" => "immediately_mark_answers",
			"stop_answer_reselect" => "stop_answer_reselect",
			"show_extra_text" => "force_show_extra_content",
			"quiz_timer" => "timer",
			"quiz_timer_question" => "timer_per_question",
			"share_results" => "share_quiz_results",
			"results_position" => "results_position",
			"randomize_questions" => "random_question_order",
			"randomize_answers" => "random_answer_order",
			"pool_of_questions" => "pool_of_questions",
			"wp_paginate" => "wp_pagination",
		);

		foreach ($map as $k => $setting) {
			if (isset($data[$k]["value"])) {
				if (is_array($data[$k]["value"])) {
					$data[$k]["value"] = $data[$k]["value"][0];
				}
				$data[$k]["value"] = $data[$k]["value"];
				$data_clean[$setting] = array("value" => $data[$k]["value"]);
			}
		}
		return $data_clean;
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

	public function create($data)
	{
		if (!hdq_user_permission()) {
			$res = new stdClass();
			$res->status = "fail";
			$res->message = "Permission denied";
			return $res;
		}

		$quiz_name = sanitize_text_field($data["hdq_quiz_name"]["value"]);
		$quiz_type = sanitize_text_field($data["hdq_quiz_type"]["value"]);

		// mark duplicate quiz names and increment to ensure unique names
		$term = term_exists($quiz_name, "quiz");
		if ($term !== 0 && $term !== null) {
			$quiz_name = $quiz_name . ' (' . wp_count_terms("quiz") . ')';
		}

		$quiz = wp_insert_term(
			$quiz_name, // the term
			'quiz' // the taxonomy
		);

		if ($quiz->errors) {
			$res = new stdClass();
			$res->status = "fail";
			$res->message = $quiz->errors;
			return $res;
		}

		// save current userID as meta
		$user_id = get_current_user_id();
		add_term_meta($quiz["term_id"], "hdq_author_id", $user_id);
		// save quiz type as meta
		add_term_meta($quiz["term_id"], "hdq_quiz_type", $quiz_type);

		$res = new stdClass();
		$res->status = "success";
		$res->quizID = $quiz;
		$res->action = new \stdClass();
		$res->action->name = "HDQ.router.views.quiz.get";
		$res->action->data = array("");
		$res->action->data2 = array($quiz["term_id"]);

		return $res;
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

		// start by delete all questions that belong to this term
		$args = array(
			'post_type' => array('post_type_questionna'),
			'tax_query' => array(
				array(
					'taxonomy' => 'quiz',
					'terms' => $this->quiz_id,
				),
			),
			'posts_per_page' => -1
		);
		$query = new WP_Query($args);

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$question_id = get_the_ID();
				wp_delete_post($question_id, true);
			}
		}

		$res = new stdClass();
		$res->status = "fail";
		$term  = wp_delete_term($this->quiz_id, "quiz");
		if (!$term || $term === null) {
			$res->message = "error deleting term with ID " . $this->quiz_id;
			return $res;
		}
		$res->status = "success";
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

		$res = $this->validateAccess();
		if ($res !== false) {
			return $res;
		}

		if ($this->quiz_id === 0) {
			$res = new stdClass();
			$res->status = "fail";
			$res->html = '1. ERROR: Quiz ID ' . $this->quiz_id . ' not found.';
			echo json_encode($res);
			die();
		}

		if (isset($data["hdq_questions_list"])) {
			$question_order = $data["hdq_questions_list"]["value"];
			$question_order = sanitize_hdq_field_question_order($question_order);
			// update each question to set question menu_order
			foreach ($question_order as $k => $question) {
				$post = array(
					'ID' => $question,
					"menu_order" => $k
				);
				wp_update_post($post);
			}
		}

		$data = new hdquiz\_hd_fields($this->fields, $data, "horizontal");
		$data = $data->get_values(false);

		update_term_meta($this->quiz_id, "quiz_data", $data);

		if ($this->quiz_name !== $data["rename_quiz"]["value"]) {
			wp_update_term($this->quiz_id, 'quiz', array(
				'name' => $data["rename_quiz"]["value"],
			));
		}

		$res = new stdClass();
		$res->status = "success";
		return $res;
	}

	private function getHeader()
	{
		$author = "";
		$author_id = intval(get_term_meta($this->quiz_id, "hdq_author_id", true));
		$user_id = get_current_user_id();
		if ($author_id && $author_id !== $user_id) {
			$author = get_the_author_meta("display_name", $author_id);
			$author = '<span class="hdq_quiz_type_tag" title = "Quiz owned by">' . $author . '</span>';
		}

		$shortcode = '<p>
	<strong>Quiz shortcode</strong>:
	<span class="hd_tooltip_line">
		<code id = "hdq_copy_shortcode">[HDquiz quiz = "' . esc_attr($this->quiz_id) . '"]</code>
		<span class="hd_tooltip">
			Click to copy shortcode to your clipboard
		</span>
	</span>
		' . $author . '
	<p>
	<p>You can copy / paste the above shortcode (remember to paste without formatting!) onto any post or page to display this quiz, or use the built-in Gutenberg block. You can also reorder the questions with drag-n-drop. Just remember to save the quiz after reordering.
</p>
';

		$type = '<span class = "hdq_quiz_type_tag" title = "quiz type">' . esc_attr($this->quiz_type) . '</span>';
		$html = '<h2 id = "hdq_quiz_title">' . $this->term->name . ' ' . $type . '</h2>';
		$saveButtons = '<div>
		<div id="hd_delete_quiz" class="hdq_button hdq_button_warning hd_kb" role="button" data-quiz="' . esc_attr($this->quiz_id) . '" tabindex="0" title="Delete this quiz"><span class="dashicons dashicons-trash"></span></div>
		<a href = "#/question/' . esc_attr($this->quiz_id) . '/0" id = "hdq_add_new_quiz" class="hdq_button hd_kb" tabindex="0">' . __("Add new question", "hd-quiz") . '</a>
		<div id="hd_save" data-action="hdq_save_quiz" class="hdq_button hdq_button_primary hd_kb" data-label = "' . __("Save", "hd-quiz") . '" role="button" tabindex="0">' . __("Save", "hd-quiz") . '</div>
		</div>';

		$html = '<div id = "hdq_quiz_heading">' . $html . $saveButtons . '</div>';
		$html .= $shortcode;
		return $html;
	}

	private function getTabs()
	{
		$fields = new hdquiz\_hd_fields($this->fields, $this->data, "vertical");
		$html = '<div id="hdq_quiz_tabs">
	<div id="hdq_quiz_tabs_labels">
		<div role="button" data-id="hdq_questions_list" tabindex = "0" class="hdq_quiz_tab hd_kb hdq_quiz_tab_active">' . __("QUESTIONS", "hd-quiz") . '</div>
		<div role="button" data-id="hdq_settings_page" tabindex = "0" class="hdq_quiz_tab hd_kb">' . __("QUIZ SETTINGS", "hd-quiz") . '</div>
	</div>
	<div id = "hdq_quiz_tabs_content">
		<div id = "hdq_questions_list" data-type = "hdq_field_question_order" class = "hdq_tab_content hdq_tab_content_active">' . $this->displayQuestions() . '</div>
		<div id = "hdq_settings_page"  class = "hdq_tab_content">' . $fields->display() . '</div>
	</div>
</div>';
		return $html;
	}

	private function displayQuestions()
	{
		$html = "";
		$hdq_per_page = 50;
		if (defined('HDQ_PER_PAGE')) {
			$hdq_per_page = intval(HDQ_PER_PAGE);
		}

		$args = array(
			'post_type' => array('post_type_questionna'),
			'tax_query' => array(
				array(
					'taxonomy' => 'quiz',
					'terms' => $this->quiz_id,
				),
			),
			'posts_per_page' => $hdq_per_page,
			'order' => 'ASC',
			'orderby' => 'menu_order',
			'paged'  => $this->paged
		);

		$query = new WP_Query($args);
		$menu_number = 0;
		if ($this->paged > 1) {
			$menu_number = $menu_number + ($hdq_per_page * ($this->paged - 1));
		}

		$hdq_admin_url = admin_url('admin.php?page=hdq_quizzes');
		$has_posts = false;

		if ($query->have_posts()) {
			$has_posts = true;
			while ($query->have_posts()) {
				$query->the_post();

				$menu_number = $menu_number + 1;

				$title = get_the_title();
				if (function_exists("mb_strimwidth")) {
					$title = mb_strimwidth($title, 0, 70, "...");
				}

				$question_id = get_the_ID();

				$html .= '<a href = "' . $hdq_admin_url . '#/question/' . $this->quiz_id . '/' . $question_id . '" class = "hdq_quiz_item hdq_quiz_question hd_kb" data-id = "' . $question_id . '" tabindex = "0"><span class = "hdq_quiz_item_drag" title = "drag and drop to reorder questions">≡</span><span>' . $menu_number . ". " . $title . '</span></a>';
			}
		} else {
			if ($this->quiz_type === "personality") {
				$html .= '<p id = "hdq_about" style = "text-align:center">' . __("Please edit your quiz settings to create your different outcomes before creating questions.") . '</p>';
			} else {
				$html .= '<p>' . __("Newly added questions will appear here", "hd-quiz") . '</p>';
			}
		}

		// Restore original Post Data
		wp_reset_postdata();

		$html .= '<div id="hdq_admin_pagination">';
		if ($has_posts) {
			$max = $query->max_num_pages;
			if ($max != $this->paged) {
				if ($this->paged == 1 && $this->paged < $max) {
					$next_page = $this->paged + 1;
					$html .= '<a title = "Next page of questions" class = "hdq_admin_pagination" href = "#/quiz/' . esc_attr($this->quiz_id) . '/' . $next_page . '"><span class="dashicons dashicons-arrow-right-alt2"></span></a>';
				} elseif ($this->paged > 1 && $this->paged < $max) {
					$next_page = $this->paged - 1;
					$html .= '<a title = "Previous page of questions" class = "hdq_admin_pagination" href = "#/quiz/' . esc_attr($this->quiz_id) . '/' . $next_page . '"><span class="dashicons dashicons-arrow-left-alt2"></span></a>';

					$next_page = $this->paged + 1;
					$html .= '<a title = "Next page of questions" class = "hdq_admin_pagination" href = "#/quiz/' . esc_attr($this->quiz_id) . '/' . $next_page . '"><span class="dashicons dashicons-arrow-right-alt2"></span></a>';
				} else {
					$next_page = $this->paged - 1;
					$html .= '<a title = "Previous page of questions" class = "hdq_admin_pagination" href = "#/quiz/' . esc_attr($this->quiz_id) . '/' . $next_page . '"><span class="dashicons dashicons-arrow-left-alt2"></span></a>';
				}
			} elseif ($this->paged > 1) {
				$next_page = $this->paged - 1;
				$html .= '<a title = "Previous page of questions" class = "hdq_admin_pagination" href = "#/quiz/' . esc_attr($this->quiz_id) . '/' . $next_page . '"><span class="dashicons dashicons-arrow-left-alt2"></span></a>';
			}
		}
		$html .= '</div>';
		return $html;
	}

	public function display()
	{
		if (!hdq_user_permission()) {
			$res = new stdClass();
			$res->status = "fail";
			$res->message = "Permission denied";
			return $res;
		}

		$this->term = get_term($this->quiz_id, "quiz");

		$res = $this->validateAccess($this->data);
		if ($res !== false) {
			return $res;
		}

		if (!$this->term || $this->quiz_id === 0) {
			$res = new stdClass();
			$res->status = "fail";
			$res->html = '2. ERROR: Quiz ID ' . $this->quiz_id . ' not found.';
			echo json_encode($res);
			die();
		}

		$html = $this->getHeader();
		$html .= $this->getTabs();
		$html .= '<input type="hidden" style = "display:none;" data-type="integer" data-required="required" class="hderp hd_input" id="quiz_id" value="' . esc_attr($this->quiz_id) . '">';

		$res = new stdClass();
		$res->status = "success";
		$res->html = $html;
		$res->type = $this->quiz_type;
		echo json_encode($res);
	}
}
