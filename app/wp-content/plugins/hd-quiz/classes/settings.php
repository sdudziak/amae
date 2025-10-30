<?php

class _hdq_settings
{
	public $data = array(); // product data
	public $fields = array();

	function __construct($flat = false)
	{
		$this->fields();
		$this->get($flat);
	}

	private function fields()
	{
		$fields = '[
	{
		"label": "' . esc_attr(trim(__("General", "hd-quiz"))) . '",
		"id": "General",
		"children": [
			{
				"id": "i_love_hd_quiz",
				"label": "I ❤️ HD Quiz",
				"required": "",
				"default": "",
				"tooltip": "",
				"description": "Enable this to show your appreciation for the work I\'ve put into HD Quiz. This will add a small, subtle link to your quiz results letting users know that your quizzes were built using HD Quiz.",
				"placeholder": "",
				"options": [{ "label": "Yes", "value": "yes" }],
				"type": "radio"
			},
			{"heading_type": "H3", "heading_content": "Social Media", "type": "heading" },
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "allow_social_media",
						"label": "Allow social media sharing",
						"required": "",
						"default": "yes",
						"tooltip": "",
						"description": "Disable this to remove the social media icons on share functionality from quiz results",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "enhanced_facebook",
						"label": "Enable enhanced Facebook sharing",
						"required": "",
						"tooltip": "",
						"description": "This is an *experimental feature* and will not work for all users. It works by creating a dedicated share page just for Facebook. Do not ask for support for this feature.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{ "id": "twitter_handle", "label": "Twitter/X handle", "required": "", "default": "", "placeholder": "", "description": "do NOT include the @ symbol", "tooltip": "The sent tweet will mention your handle", "prefix": "", "postfix": "", "type": "text" }
				]
			},
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "share_text",
						"label": "Share text [general / scored]",
						"required": "",
						"default": "I scored %score% on the %quiz% quiz. Can you beat me?",
						"placeholder": "",
						"description": "You can use custom variables here to add dynamic content. <code>%name%</code> will display the quiz name, and <code>%score%</code> will display the user\'s score as a fraction.",
						"tooltip": "This is the text that will appear when a user share\'s their quiz",
						"prefix": "",
						"postfix": "",
						"type": "text"
					},
					{
						"id": "share_text_personality",
						"label": "Share text [personality]",
						"required": "",
						"default": "I got %score% on the %quiz% quiz.",
						"placeholder": "",
						"description": "You can use custom variables here to add dynamic content. <code>%name%</code> will display the quiz name, and <code>%score%</code> will display the outcome title.",
						"tooltip": "This is the text that will appear when a user share\'s their quiz",
						"prefix": "",
						"postfix": "",
						"type": "text"
					}
				]
			},
			{ "heading_type": "H3", "heading_content": "Other Options", "type": "heading" },
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{
						"id": "allow_authors_access",
						"label": "Allow authors access to create quizzes",
						"required": "",
						"default": "no",
						"tooltip": "Authors can only edit their own quizzes. Enabling this also grants the Editor role access to edit all quizzes.",
						"description": "By default, only Admins can add or edit questions. Enabling this will allow Authors to create quizzes as well.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "enable_percent_results",
						"label": "Show results as percentage",
						"required": "",
						"default": "yes",
						"tooltip": "",
						"description": "By default, HD Quiz will only show the score as a fraction (example: 9/10). Enabling this will also show the score as a percentage (example: 90%)",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					},
					{
						"id": "replace_the_content_filter",
						"label": "Replace <code>the_content</code> filter for quizzes",
						"required": "",
						"default": "yes",
						"tooltip": "",
						"description": "HD Quiz uses a custom version of <code>the_content</code> filter to print content to the page (extra text, and result text). This is to prevent other plugins from automatically adding their own code to quizzes. You can disable this if you want to allow other plugins to add their content again.",
						"placeholder": "",
						"options": [{ "label": "Yes", "value": "yes" }],
						"type": "radio"
					}					
				]
			},			
			{
				"id": "adset_code",
				"label": "Adset Code",
				"required": "",
				"default": "",
				"placeholder": "You can paste your ad code here",
				"description": "<p><strong>DO NOT USE</strong> if you are already displaying auto ads on your site. HD Quiz will place the ad code after every 5th question, but how the ad appears is entirely based on the ads and ad networks themselves. Some ads do not play nice with pagination. This is because they do not like loading on the page hidden.</p><p><small><strong>NOTE</strong>: If using JavaScript, it is best to have all of your code on one line. This is to stop WordPress from adding paragraph tags where you don\'t want them</small></p>",
				"prefix": "",
				"postfix": "",
				"type": "textarea_code"
			}
		]
	},
	{
		"label": "' . esc_attr(trim(__("Translations", "hd-quiz"))) . '",
		"id": "Translations",
		"children": [
			{
				"column_type": "1-1",
				"type": "column",
				"children": [
					{ "id": "translate_finish", "label": "Rename \"Finish\" Button", "required": "", "default": "' . esc_attr(trim(__("Finish", "hd-quiz"))) . '", "placeholder": "", "description": "", "tooltip": "", "prefix": "", "postfix": "", "type": "text" },
					{ "id": "translate_next", "label": "Rename \"Next\" Button", "required": "", "default": "' . esc_attr(trim(__("Next", "hd-quiz"))) . '", "placeholder": "", "description": "", "tooltip": "", "prefix": "", "postfix": "", "type": "text" },
					{ "id": "translate_previous", "label": "Rename \"Previous\" Button", "required": "", "default": "' . esc_attr(trim(__("Previous", "hd-quiz"))) . '", "placeholder": "", "description": "", "tooltip": "", "prefix": "", "postfix": "", "type": "text" },
					{ "id": "translate_results", "label": "Rename \"Results\" text", "required": "", "default": "' . esc_attr(trim(__("Results", "hd-quiz"))) . '", "placeholder": "", "description": "", "tooltip": "", "prefix": "", "postfix": "", "type": "text" },
					{
						"id": "translate_quiz_start",
						"label": "Rename \"QUIZ START\" text",
						"required": "",
						"default": "' . esc_attr(trim(__("QUIZ START", "hd-quiz"))) . '",
						"placeholder": "",
						"description": "",
						"tooltip": "Used if you are using a timer feature, or for direct links to the quiz on category/search pages",
						"prefix": "",
						"postfix": "",
						"type": "text"
					},
					{
						"id": "translate_enter_answer_here",
						"label": "Rename \"enter answer here\" text",
						"required": "",
						"default": "' . esc_attr(trim(__("enter answer here", "hd-quiz"))) . '",
						"placeholder": "",
						"description": "This text appears as a placeholder for the \"Text Based Answers\" question type",
						"tooltip": "",
						"prefix": "",
						"postfix": "",
						"type": "text"
					},
					{
						"id": "translate_select_all_that_apply",
						"label": "Rename \"Select all that apply:\" text",
						"required": "",
						"default": "' . esc_attr(trim(__("Select all that apply:", "hd-quiz"))) . '",
						"placeholder": "",
						"description": "This text appears as a placeholder for the \"Select All That Apply\" question type",
						"tooltip": "",
						"prefix": "",
						"postfix": "",
						"type": "text"
					},
					{
						"id": "translate_submit",
						"label": "Rename \"Submit\" button text",
						"required": "",
						"default": "' . esc_attr(trim(__("Submit", "hd-quiz"))) . '",
						"placeholder": "",
						"description": "This button appears with the \"Select all that apply\" question type when either \"Immediately mark answer as correct or incorrect\" is enabled, or Timer per question is enabled.",
						"tooltip": "",
						"prefix": "",
						"postfix": "",
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

		$fields = apply_filters("hdq_add_settings_field", $fields);
		$this->fields = $fields;
	}

	private function get($flat)
	{
		$data = get_option("hdq_settings", array());
		$data = $this->mapOld($data);
		$fields = new hdquiz\_hd_fields($this->fields, $data);
		$data = $fields->get_values($flat);
		$this->data = $data;
	}

	private function mapOld($data)
	{
		$data_clean = array();
		if (!isset($data["hd_qu_fb"])) {
			return $data;
		}

		$map = array(
			"hd_qu_tw" => "twitter_handle",
			"hd_qu_share_text" => "share_text",
			"hd_qu_authors" => "allow_authors_access",
			"hd_qu_percent" => "enable_percent_results",
			"hd_qu_the_content" => "replace_the_content_filter",
			"hd_qu_legacy_scroll" => "enable_legacy_scroll",
			"hd_qu_heart" => "i_love_hd_quiz",
			"hd_qu_adcode" => "adset_code",
			"hd_qu_finish" => "translate_finish",
			"hd_qu_next" => "translate_next",
			"hd_qu_results" => "translate_results",
			"hd_qu_start" => "translate_quiz_start",
			"hd_qu_text_based_answers" => "translate_enter_answer_here",
			"hd_qu_select_all_apply" => "translate_select_all_that_apply"
		);

		foreach ($map as $k => $setting) {
			if (isset($data[$k]["value"])) {
				if (is_array($data[$k]["value"])) {
					$data[$k]["value"] = $data[$k]["value"][0];
				}
				$data_clean[$setting] = array("value" => $data[$k]["value"]);

				if ($k === "hd_qu_adcode") {
					$data_clean[$setting] = array("value" => $this->decode($data[$k]["value"]));
				}
			}
		}
		return $data_clean;
	}

	// old encryption function
	private function decode($ciphertext = "")
	{
		if ($ciphertext === "") {
			return "";
		}
		$c = base64_decode($ciphertext);
		$cipher = "AES-128-CBC";
		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len = 32);
		$ciphertext_raw = substr($c, $ivlen + $sha2len);
		$original_plaintext = stripslashes(openssl_decrypt($ciphertext_raw, $cipher, wp_salt(), OPENSSL_RAW_DATA, $iv));
		$calcmac = hash_hmac('sha256', $ciphertext_raw, wp_salt(), true);
		if (@hash_equals($hmac, $calcmac)) {
			return $original_plaintext;
		} else {
			return "";
		}
	}

	public function save($data)
	{
		if (!current_user_can("manage_options")) {
			$res = new stdClass();
			$res->status = "fail";
			$res->message = "Permission denied";
			return $res;
		}

		$data = new hdquiz\_hd_fields($this->fields, $data, "horizontal");
		$data = $data->get_values(false);

		update_option("hdq_settings", $data);

		$res = new stdClass();
		$res->status = "success";
		return $res;
	}

	public function display()
	{
		if (!current_user_can("manage_options")) {
			die();
		}

		wp_enqueue_style("hdfields", plugins_url('/../hdfields/style.css', __FILE__), array(), HDFIELDS_VERSION);
		wp_enqueue_script("hdfields", plugins_url('/../hdfields/script.js', __FILE__), array(),  HDFIELDS_VERSION);
		$fields = new hdquiz\_hd_fields($this->fields, $this->data, "vertical");
		echo $fields->display();
	}
}
