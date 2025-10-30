<?php

class _hdq_csv_import_tool
{
    public $csv = null;
    public $status = array(
        "status" => "fail",
        "data" => array(),
        "message" => ""
    );
    public $quiz_id = 0;

    function __construct()
    {
        //
    }

    private function log()
    {
        echo json_encode($this->status);
        die();
    }

    public function import()
    {
        $this->validateUser();
        $this->validateNonce();

        $data = stripslashes($_POST["question"]);
        $data = json_decode($data, true);
        $data = $this->formatQuestion($data);

        $question = new _hdq_question($this->quiz_id);
        $res = $question->save($data);
        echo json_encode($res);
        die();
    }

    private function formatQuestion($question)
    {
        /*
            0 => Quiz Name
            1 => Question title
            2-11 => Answers
            12 => Correct answer
            13 => Secret field, extra text
        */

        $this->quiz_id = $this->getQuizIdByName($question[0]);
        $answers = $this->getQuestionAnswers($question);

        $extra = "";
        if (isset($question[13])) {
            $extra = wp_kses_post($question[13]);
        }

        $data = array(
            "question_title" => array(
                "type" => "text",
                "value" => sanitize_text_field($question[1])
            ),
            "question_type" => array(
                "type" => "select",
                "value" => "multiple_choice_text",
            ),
            "quiz_id" => array(
                "type" => "integer",
                "value" => $this->quiz_id,
            ),
            "question_answers" => array(
                "type" => "hdq_field_answers",
                "value" => $answers,
            ),
            "extra_content" => array(
                "type" => "editor",
                "value" => $extra
            )
        );

        return $data;
    }

    private function getQuestionAnswers($question)
    {
        $answers = array();
        for ($i = 2; $i < 12; $i++) {
            if ($question[$i] !== "") {
                $answer = array(
                    "value" => sanitize_text_field($question[$i]),
                    "image" => "",
                    "imageURL" => "",
                    "selected" => ""
                );
                array_push($answers, $answer);
            }
        }

        for ($i = 0; $i < count($answers); $i++) {
            if ($i + 1 == intval($question[12])) {
                $answers[$i]["selected"] = "yes";
                break;
            }
        }
        return $answers;
    }

    private function getQuizIdByName($quiz_name)
    {
        $quiz_name = sanitize_text_field($quiz_name);
        $quiz_id = term_exists($quiz_name, "quiz");
        if ($quiz_id == null) {
            // create new quiz
            $quiz_id = wp_insert_term(
                $quiz_name, // the term
                'quiz' // the taxonomy
            );
            $quiz_id = $quiz_id["term_id"];
            add_term_meta($quiz_id, "hdq_quiz_type", "general");
        } else {
            $quiz_id = $quiz_id["term_id"];
        }
        return $quiz_id;
    }

    public function upload()
    {
        if (!isset($_FILES['hdq_csv_file_upload'])) {
            $this->status["message"] = 'File upload not found';
            $this->log();
        }
        $this->csv = $_FILES['hdq_csv_file_upload'];

        $this->validateFileType();
        $this->moveFile();

        $this->status['status'] = "success";


        $data = array_map(function ($v) {
            return str_getcsv($v, ",", '"');
        }, file($this->csv));


        // $file = fopen($this->csv, 'r');
        // $data = [];
        // while (($row = fgetcsv($file)) !== FALSE) {
        //     $data[] = $row;
        // }
        // fclose($file);
        $this->status["data"] = $data;
        $this->status["message"] = "Upload succesful. Import has begin. Please do not leave or close this page until complete.";
        $this->log();
    }

    private function moveFile()
    {
        $upload_dir = wp_upload_dir();
        $hdq_upload_dir = $upload_dir['basedir'] . '/hd-quiz/';
        wp_mkdir_p($hdq_upload_dir);

        if (!move_uploaded_file($this->csv['tmp_name'], $hdq_upload_dir . sanitize_text_field($this->csv['name']))) {
            $this->status["message"] = 'Error uploading file - check destination is writeable => <code>' . esc_attr($hdq_upload_dir) . '</code>';
            $this->log();
        }
        $this->csv = $hdq_upload_dir . sanitize_file_name($this->csv['name']);
    }

    private function validateFileType()
    {
        $this->validateUser();
        $this->validateNonce();
        $this->validateExtension();
        $this->validateMime();
    }

    private function validateUser()
    {
        if (!current_user_can('edit_others_pages')) {
            $this->status["message"] = '1. Permission not granted';
            $this->log();
        }
    }

    private function validateNonce()
    {
        if (!isset($_POST['hdq_tools_nonce'])) {
            $this->status["message"] = '2. Permission not granted';
            $this->log();
        }
        $nonce = sanitize_text_field($_POST['hdq_tools_nonce']);
        if (!wp_verify_nonce($nonce, 'hdq_tools_nonce')) {
            $this->status["message"] = '3. Permission not granted';
            $this->log();
        }
    }

    private function validateExtension()
    {
        $extention = strtolower(pathinfo($this->csv['name'], PATHINFO_EXTENSION));
        if ($extention != "csv") {
            $this->status["message"] = 'Uploaded file was not a CSV, or did not end with a .csv extension';
            $this->log();
        }
    }

    private function validateMime()
    {
        $mime = null;
        if (function_exists("finfo_file")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $mime = finfo_file($finfo, $this->csv['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists("mime_content_type")) {
            $mime = mime_content_type($this->csv['tmp_name']);
        } else {
            $this->status["message"] = 'Unable to determine upload MIMETYPE';
            $this->log();
        }

        if ($mime !== "text/plain" && $mime !== "text/csv") {
            $this->status["message"] = 'Uploaded file was not a CSV';
            $this->log();
        }
    }
}
