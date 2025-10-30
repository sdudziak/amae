<?php
// Register Quiz taxonomy
function hdq_regsiter_taxonomy_quizzes()
{
    $labels = array(
        'name'                       => _x('Quizzes', 'Taxonomy General Name', 'hd-quiz'),
        'singular_name'              => _x('Quiz', 'Taxonomy Singular Name', 'hd-quiz'),
        'menu_name'                  => __('Quizzes', 'hd-quiz'),
        'all_items'                  => __('All Quizzes', 'hd-quiz')
    );
    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => false,
        'show_ui'                    => true,
        'show_in_rest'               => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
        'rewrite'                    => false,
    );
    register_taxonomy('quiz', array('post_type_questionna'), $args);
}
add_action('init', 'hdq_regsiter_taxonomy_quizzes', 0);
