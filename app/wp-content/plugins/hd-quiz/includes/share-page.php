<?php
/* 
    Experimental template for Facebook sharing 
    This template will show for any page that has a permalink structure of ./hd-quiz/*
*/

$quizId = 0;
if (isset($_GET["quiz_id"])) {
    $quizId = intval($_GET["quiz_id"]);
}

if ($quizId === 0) {
    die("No quiz ID was provided for this share dialogue");
}

$permalink = null;
if (isset($_GET["permalink"])) {
    $permalink = sanitize_url($_GET["permalink"]);
}
if ($permalink === null) {
    die("Incorrect share link does not contain permlaink of quiz");
}

$pageId = url_to_postid($permalink);
$image = get_the_post_thumbnail_url($pageId);

$score = "";

// Get HDQ Settings
$settings = new _hdq_settings();
$settings = $settings->data;
$share_text = $settings["share_text"]["value"];
$share_text_personality = $settings["share_text_personality"]["value"];
$quiz_start_text = $settings["translate_quiz_start"]["value"];

// Get Quiz Settings
$quiz = new _hdq_quiz($quizId);
if ($quiz->term == "") {
    die("Quiz no longer exists");
}
$quiz_name = $quiz->quiz_name;
$quiz_type = $quiz->quiz_type;

$title = "";
if ($quiz_type != "personality") {
    $title = $share_text;
    if (isset($_GET["score"])) {
        // I know this is extra, but doing it this way in case anyone ever
        // needs to modify how the score is presented and this forces clean data
        $score = array();
        $scoreStr = sanitize_text_field($_GET["score"]);
        $scoreArr = explode(",", $scoreStr);
        $scoreArr = array_map("intval", $scoreArr);
        array_push($score, $scoreArr[0]);
        array_push($score, $scoreArr[1]);
        $score = $score[0] . '/' . $score[1];
    }

    $title = str_replace("%score%", '<span>' . $score . '</span>', $title);
} else {
    $title = $share_text_personality;

    if (isset($_GET["score"])) {
        $score = sanitize_text_field($_GET["score"]);
    }
    $title = str_replace("%score%", '<span>' . $score . '</span>', $title);
}
$title = str_replace("%quiz%", '<span>' . $quiz_name . '</span>', $title);


?>
<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name='robots' content='max-image-preview:large' />
    <title>HD Quiz Share Results</title>
    <meta name="generator" content="HD Quiz - Free WordPress quiz builder" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo $quiz_name . ' | ' . get_bloginfo('name'); ?>" />
    <meta property="og:description" content="<?php echo esc_attr(sanitize_text_field($title)); ?> #hdquiz" />
    <meta property="og:image" content="<?php echo $image; ?>" />

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background-color: #fefefe;
            color: #222;
            line-height: 1.2;
            font-size: 18px;
        }

        #hd_quiz_share_wrapper {
            max-width: 600px;
            margin-top: 4rem;
            margin-left: auto;
            margin-right: auto;
            border: 2px solid #eee;
            padding: 2rem;
            box-shadow: 0 0 22px #ddd, 0 0 22px #fff;
        }

        #hdq_view_quiz_button {
            text-align: center;
            display: inline-block;
        }

        #hd_quiz_share_wrapper>h1 {
            margin: 0 0 1em 0;
            padding: 0;
            color: #444;
            font-weight: normal;
        }

        #hd_quiz_share_wrapper>h1>span {
            color: #222;
            font-weight: bold;
        }

        #hd_quiz_share_wrapper>p {
            margin: 0
        }

        #hd_quiz_share_wrapper>p>a {
            text-decoration: none;
            color: #fff;
            background-color: #222;
            padding: 1em;
            line-height: 1;
        }

        #featured_image_wrapper>img {
            display: block;
            max-width: 100%;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <main id="hd_quiz_share_wrapper">
        <?php
        if ($image) {
        ?>
            <div id="featured_image_wrapper">
                <img src="<?php echo $image; ?>" alt="HD Quiz featured image" />
            </div>
        <?php
        }
        ?>
        <h1><?php echo $title; ?></h1>
        <p><a href="<?php echo esc_attr($permalink); ?>" id="hdq_view_quiz_button"><?php echo esc_html($quiz_start_text); ?></a></p>
    </main>
</body>

</html>