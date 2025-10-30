<?php

class _hdq_dashboard
{
    public $fields = array();
    public $settings = array();

    function __construct($flat = false)
    {
        $this->settings();
        $this->fields();
    }

    private function settings()
    {
        $settings = new _hdq_settings(true);
        $this->settings = $settings->data;
    }

    private function fields()
    {
        $fields = '
        [
            {
				"id": "hdq_quiz_name",
				"label": "' . esc_attr(trim(__("Quiz name", "hd-quiz"))) . '",
				"required": true,
				"placeholder": "' . __("Enter quiz name", "hd-quiz") . '",
				"type": "text"
			},
            {
				"id": "hdq_quiz_type",
				"label": "' . esc_attr(trim(__("Quiz type", "hd-quiz"))) . '",
				"required": true,
				"default": "general",
				"type": "select",
                "tooltip": "<p><strong>General</strong> is the default quiz type and is meant for traditional quizzes with scores.</p><p><strong>Personality</strong> is a new quiz type and is meant to make quizzes similar to \"Which Harry Potter Character are you?\"</p>",
                "options": [
                    {
                        "label": "' . esc_attr(trim(__("General / Scored", "hd-quiz"))) . '",
                        "value": "general"
                    },
                    {
                        "label": "' . esc_attr(trim(__("Personality", "hd-quiz"))) . '",
                        "value": "personality"
                    }
                ]
			},
            {
			    "id": "hdq_add_quiz_content",
				"type": "content",
                "content": "<div id=\"hd_save\" data-action=\"hdq_create_quiz\" class=\"hdq_button hdq_button_primary hd_kb\" data-label = \"' . esc_attr(trim(__("Create", "hd-quiz"))) . '\" role=\"button\" tabindex=\"0\">' . esc_attr(trim(__("Create", "hd-quiz"))) . '</div>"
            }
        ]';
        $data = json_decode($fields, true);
        $this->fields = $data;
    }

    private function list_quizzes()
    {
        $args = array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        );
        $quizzes = get_terms("quiz", $args);
        $user_id = get_current_user_id();

        $hdq_admin_url = admin_url('admin.php?page=hdq_quizzes');

        $html = '<div id="hdq_list_quizzes">';
        if (!empty($quizzes) && !is_wp_error($quizzes)) {
            foreach ($quizzes as $quiz) {
                // if author mode is active, only show quizzes belonging to current author (or admins access all)
                $quiz_type = sanitize_text_field(get_term_meta($quiz->term_id, "hdq_quiz_type", true));
                $author_id = intval(get_term_meta($quiz->term_id, "hdq_author_id", true));
                if ($this->settings["allow_authors_access"] === "yes" && $author_id !== $user_id && !current_user_can('publish_posts')) {
                    continue;
                }

                // show quiz item
                $html .= '<a href = "' . $hdq_admin_url . '#/quiz/' . esc_attr($quiz->term_id) . '" class="hdq_quiz_item hdq_quiz_term hd_kb" tabindex = "0">';
                if ($quiz_type === "personality") {
                    $html .= '<span class="dashicons dashicons-networking" title = "General / Scored quiz"></span>';
                } else {
                    $html .= '<span class="dashicons dashicons-welcome-learn-more" titel = "Personality type quiz"></span>';
                }
                $html .= $quiz->name;
                $html .= '<code>[HDquiz quiz = "' . esc_attr($quiz->term_id) . '"]</code>';
                $html .= '</a>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    private function getHeader()
    {
        // $hdq_updated = sanitize_text_field(get_option('HDQ_UPDATED'));
        $notice = "";
        // if ($hdq_updated) {
        //     $notice .= '<div id = "hdq_about" style = "margin: 2rem 0; max-width: 100%; padding: 1em;"><p style = "margin-top: 0;"><strong>Thank you for updating to HD Quiz 2.0</strong></p><p style = "margin-bottom: 0;">This new version is far more powerful than the previous versions, and was rewritten from the ground up. Please <a href = "https://hdplugins.com/forum/hd-quiz-support/" target = "_blank">let me know</a> if you experience any issues or bugs. You can also always safely downgrade to the previous version <a href = "https://wordpress.org/plugins/hd-quiz/advanced/#plugin-download-history-stats" target = "_blank">here</a>.</p></div>';
        // }
        return '<div id="hdq_header" class = "">
        <h1 id="hdq_heading_title">HD Quiz - WordPress Quiz Builder</h1>
        <div id="hdq_header_actions">
            <a href="https://hdplugins.com/learn/hd-quiz/hd-quiz-documentation/?utm_source=HDQSettingsPage" class="hdq_button hdq_button_secondary" role="button" target="_blank">' . __("DOCUMENTATION", "hd-quiz") . '</a>
            <a href="https://hdplugins.com/forum/hd-quiz-support/?utm_source=HDQSettingsPage" class="hdq_button hdq_button_secondary" role="button" target="_blank">' . __("SUPPORT", "hd-quiz") . '</a>
        </div>
    </div>' . $notice;
    }

    public function display()
    {
        $data = array(); // display only, no data to read
        $fields = new hdquiz\_hd_fields($this->fields, $data);
        $fields = $fields->display();
        $fields = '<div id = "hdq_create_new_quiz_wrapper">' . $fields . '</div>';
        $header = $this->getHeader();
        $html = $header . $fields . $this->list_quizzes();

        $res = new stdClass();
        $res->status = "success";
        $res->html = $html;
        echo json_encode($res);
    }
}
