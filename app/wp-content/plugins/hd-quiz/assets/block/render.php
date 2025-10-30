<?php

/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$quizId = intval($attributes["quizId"]);
if ($quizId > 0) {
    echo do_shortcode('[HDquiz quiz = "' . esc_attr($quizId) . '"]');
} else {
    echo 'QuizID not found => Gutenberg ';
}
