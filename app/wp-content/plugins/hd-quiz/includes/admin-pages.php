<?php

/* HD Quiz "Dashboard"
------------------------------------------------------- */
function hdq_main_page()
{
    wp_enqueue_style("hdfields", plugins_url('../hdfields/style.css', __FILE__), array(), HDQ_PLUGIN_VERSION);
    wp_enqueue_script("hdfields", plugins_url('../hdfields/script.js', __FILE__), array(),  HDFIELDS_VERSION);
    wp_enqueue_style("hdq_admin", plugins_url('../assets/admin.css', __FILE__), array(), HDQ_PLUGIN_VERSION);
    wp_enqueue_script('hdq_admin', plugins_url('../assets/admin_bundled.js', __FILE__), array(), HDQ_PLUGIN_VERSION);
    // wp_enqueue_script_module('hdq_admin', plugins_url('../assets/admin.js', __FILE__), array(), HDQ_PLUGIN_VERSION);

    $hdq_version = sanitize_text_field(get_option('HDQ_PLUGIN_VERSION'));
    if (HDQ_PLUGIN_VERSION != $hdq_version) {
        update_option('HDQ_PLUGIN_VERSION', HDQ_PLUGIN_VERSION);
        if ($hdq_version) {
            update_option("HDQ_UPDATED", $hdq_version);
        }
    }
?>
    <script>
        // NOTE: wp_inline_script won't work if I attach to a module, so...
        const HDQ_VERSION = "<?php echo esc_attr(HDQ_PLUGIN_VERSION); ?>";
    </script>

    <div id="hdq_wrapper">

        <?php $nonce = wp_create_nonce('hdq_NONCE'); ?>
        <input type="hidden" id="hd_wpnonce" name="hd_wpnonce" value="<?php echo esc_attr($nonce); ?>" />

        <section id="hdq_content">
            <!-- content goes here -->
        </section>
        <div id="hdq_loading" class="active">
            <div class="hdq_loader"></div>
        </div>
    </div>

    <div style="display:none;">
        <?php
        // load editor so that tinymce is loaded
        add_filter('user_can_richedit', '__return_true', 999999);
        wp_editor("", "hdq_enqued_editor", array('textarea_name' => 'hdq_enqued_editor', 'teeny' => true, 'media_buttons' => false, 'textarea_rows' => 3, 'quicktags' => false));
        remove_filter('user_can_richedit', '__return_true');
        // scripts and styles needed to use WP uploader
        wp_enqueue_media();
        ?>
    </div>

<?php
}

function hdq_ssl_message()
{
?>
    <h1>HD Quiz</h1>
    <p>Your site is not currently using SSL. The admin area for HD Quiz requires an SSL certificate to be installed and active.</p>
    <p>If you already have an SSL certificate, please ensure that your URL begins with http<strong>s</strong>:// instead of http://</p>
<?php
}

/* HD Quiz Settings page
------------------------------------------------------- */
function hdq_about_settings_page()
{
    wp_enqueue_style("hdq_admin", plugins_url('../assets/admin.css', __FILE__), array(), HDQ_PLUGIN_VERSION);
    $settings = new _hdq_settings();
?>
    <div id="hdq_wrapper">
        <div id="hdq_header">
            <h1 id="hdq_heading_title">HD Quiz - About / Settings</h1>
        </div>
        <?php $nonce = wp_create_nonce('hdq_NONCE'); ?>
        <input type="hidden" id="hd_wpnonce" name="hd_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
        <div id="hdq_header_actions">
            <a href="https://hdplugins.com/learn/hd-quiz/hd-quiz-documentation/?utm_source=HDQSettingsPage" class="hdq_button hdq_button_secondary" role="button" target="_blank">DOCUMENTATION</a>
            <div id="hd_save" data-id="0" data-wrapper="hdq_wrapper" data-action="hdq_settings_save" data-label="Save" class="hdq_button hdq_button_primary" title="save" role="button">Save</div>
        </div>
        <?php
        $settings->display();
        ?>
        <div id="hdfields_error_log"></div>

        <div id="hdq_about">
            <h2>Letter from the developer</h2>

            <p>Hi everyone,<br /> this is Dylan of <a href="https://harmonicdesign.ca" target="_blank">Harmonic Design</a>, the solo developer and creator of HD Quiz.</p>

            <p>I uploaded the first version of HD Quiz in October 2015, and saw immediate positive feedback from users around the world. What started as a fun little learning exercise turned into a wonderfully fulfilling experience, in large part to all of you.</p>

            <p>Seeing tens of thousands of sites use HD Quiz over the past decade has been amazing, and I am honoured that you have all decided to give HD Quiz and my work a try!</p>

            <p>I develop and support HD Quiz for free, but do offer paid add-ons (as well as several free ones) that provide more advanced "business" oriented features such as a Quiz Designer / Styler, and a more advanced way to Save Results and collect user information. View the Addons submenu page for more details.</p>

            <p>If you are enjoying HD Quiz, please consider <a href="https://wordpress.org/support/plugin/hd-quiz/reviews/#new-post" target="_blank">leaving a review</a>! It's free to do, and allows me to see, in a very real way, that my hard work is being appreciated.</p>

            <p>All the best, and happy quizzing!</p>

        </div>
    </div>
    <script>
        function hdq_highlight_love_hd_quiz() {
            const el = document.getElementById("i_love_hd_quiz");
            el.parentElement.classList.add("hdq_highlight_field");
            console.log("Add this to script.js")
        }
        hdq_highlight_love_hd_quiz();
    </script>
<?php
}

/* HD Quiz Tools page
------------------------------------------------------- */
function hdq_tools_page()
{
    if (!current_user_can('edit_others_pages')) {
        die();
    }
    wp_enqueue_style("hdq_admin", plugins_url('../assets/admin.css', __FILE__), array(), HDQ_PLUGIN_VERSION);
?>
    <div id="hdq_wrapper">
        <div id="hdq_header">
            <h1 id="hdq_heading_title">HD Quiz - Tools</h1>
        </div>

        <p>HD Quiz has grown considerably in features and complexity over the years making this page necessary. Most of you will never need to use any of the tools on this page, but these tools are still here to help you out when needed.</p>

        <div id="hdq_tools_wrapper">
            <div class="hdq_tool_item">
                <h2>Question CSV Uploader</h2>
                <div class="hdq_accordion_item">
                    <div class="hdq_accordion_heading" data-expanded="false">
                        <h3 class="hdq_accordion_title" data-expanded="false" id="what_does_this_tool_do">
                            <button class="hdq_accordion_button" aria-expanded="false" aria-controls="hdq_what_does_this_tool_do">What does this tool do?</button>
                        </h3>
                    </div>
                    <div class="hdq_accordion_content" aria-hidden="true" style="height: 0px;" data-height="369" id="hdq_what_does_this_tool_do">
                        <p>
                            If you have a CSV (comma seperated values) file of questions, this tool can help you bulk import all of your questions. Please note that this tool is limited in scope, so you will not be able to automatically set all features (such as images), but can still be used to make adding questions in bulk a <em>lot</em> faster.
                        </p>
                        <p>
                            Instructions on how to format the CSV file are provided on the CSV Uploader tool page.
                        </p>
                        <p>
                            Please also know that if you need to export and import questions, then you should use WordPress' native import or export tool for that. <em>This</em> tool is used for bulk adding brand new questions.
                        </p>
                    </div>
                </div>
                <p><strong>NOTE</strong>: It is YOUR responsibility to ensure that your CSV is properly formatted. Due to the almost infinte ways that a CSV can be inproperly formatted for HD Quiz, I can only offer a very limited support to help with any issues arising from this feature.</p>
                <a href="?page=hdq_importer" class="hdq_button hdq_button_secondary">Run tool</a>
            </div>

            <div class="hdq_tool_item">
                <h2>Questions Custom Post Type Admin</h2>
                <div class="hdq_accordion_item">
                    <div class="hdq_accordion_heading" data-expanded="false">
                        <h3 class="hdq_accordion_title" data-expanded="false" id="what_does_this_tool_do">
                            <button class="hdq_accordion_button" aria-expanded="false" aria-controls="hdq_what_does_this_tool_do">What does this tool do?</button>
                        </h3>
                    </div>
                    <div class="hdq_accordion_content" aria-hidden="true" style="height: 0px;" data-height="369" id="hdq_what_does_this_tool_do">
                        <p>HD Quiz is built using Custom Post Types, and a Custom Taxonomy. This creates a relationship similar to Posts and Categories. Just like how one of your posts can belong to multiple blog categories, questions can belong to multiple quizzes.</p>
                    </div>
                </div>
                <p>You can use this to bulk delete questions, or add questions to a quiz in bulk</p>
                <a href="./edit.php?post_type=post_type_questionna" class="hdq_button hdq_button_secondary">Run tool</a>
            </div>

            <div class="hdq_tool_item">
                <h2>Quizzes Custom Taxonomy Admin</h2>
                <div class="hdq_accordion_item">
                    <div class="hdq_accordion_heading" data-expanded="false">
                        <h3 class="hdq_accordion_title" data-expanded="false" id="what_does_this_tool_do">
                            <button class="hdq_accordion_button" aria-expanded="false" aria-controls="hdq_what_does_this_tool_do">What does this tool do?</button>
                        </h3>
                    </div>
                    <div class="hdq_accordion_content" aria-hidden="true" style="height: 0px;" data-height="369" id="hdq_what_does_this_tool_do">
                        <p>Access to this page makes it fast to delete multiple quizzes at the same time. This, paired with the Questions Custom Post Type Admin tool, can make it much faster and easier to delete questions and quizzes in bulk to clean up your database.</p>
                    </div>
                </div>
                <p>You can use this to delete quizzes. Just note that this will keep the questions</p>
                <a href="./edit-tags.php?taxonomy=quiz&post_type=post_type_questionna" class="hdq_button hdq_button_secondary">Run tool</a>
            </div>
        </div>
    </div>

    <script>
        const hdq_accodions = {
            items: {
                parents: [],
                titles: [],
                buttons: [],
                contents: [],
            },
            init: async function() {
                addEventListener("load", async function() {
                    hdq_accodions.items.parents = document.getElementsByClassName("hdq_accordion_item");
                    hdq_accodions.items.titles = document.getElementsByClassName("hdq_accordion_title");
                    hdq_accodions.items.buttons = document.getElementsByClassName("hdq_accordion_button");
                    hdq_accodions.items.contents = document.getElementsByClassName("hdq_accordion_content");

                    hdq_accodions.setHeights();
                    hdq_accodions.setEvents();
                    setTimeout(hdq_accodions.setHeights, 200); // do again to be sure
                });
                addEventListener("resize", hdq_accodions.setHeights, {
                    passive: true
                });
            },
            setHeights: function() {
                let items = hdq_accodions.items.contents;
                for (let i = 0; i < items.length; i++) {
                    items[i].style.height = "auto";
                    items[i].setAttribute("data-height", items[i].clientHeight);
                    items[i].style.height = "0";
                }
            },
            setEvents: function() {
                let buttons = hdq_accodions.items.buttons;
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].addEventListener("click", hdq_accodions.toggle);
                }
            },
            toggle: function(ev) {
                let el = this;
                const parent = el.parentElement.parentElement;
                let isExpanded = parent.getAttribute("data-expanded");
                if (isExpanded == "true") {
                    isExpanded = true;
                } else {
                    isExpanded = false;
                }

                parent.setAttribute("data-expanded", !isExpanded);
                el.setAttribute("aria-expanded", !isExpanded);

                const content = parent.parentElement.getElementsByClassName("hdq_accordion_content")[0];

                if (isExpanded) {
                    content.style.height = 0;
                    content.setAttribute("aria-hidden", true);
                } else {
                    content.setAttribute("aria-hidden", false);
                    content.style.height = content.getAttribute("data-height") + "px";
                }
            },
        };
        hdq_accodions.init();
    </script>
<?php
}

function hdq_tools_csv_importer()
{
    if (!current_user_can('edit_others_pages')) {
        die();
    }
    wp_enqueue_style("hdq_admin", plugins_url('../assets/admin.css', __FILE__), array(), HDQ_PLUGIN_VERSION);
    wp_enqueue_script('hdq_admin', plugins_url('../assets/csv-importer.js', __FILE__), array(), HDQ_PLUGIN_VERSION);
?>
    <style>
        .hdq_highlight_field>p {
            font-size: 16px;
        }

        #hdq_csv_upload_form_wrapper {
            margin: 2rem auto;
            width: fit-content;
        }

        #hdq_logs {
            margin: 2rem auto;
            padding: 1rem;
        }

        .hdq_log {
            margin: 0.4em 0;
        }
    </style>
    <div id="hdq_wrapper">
        <div id="hdq_header">
            <h1 id="hdq_heading_title">HD Quiz - CSV Import Tool</h1>
        </div>

        <?php
        if (!isset($_FILES["hdq_csv_file_upload"])) { ?>
            <p>Using this tool, you can upload a CSV to bulk import questions. Please note that due to the complexity of creating and formatting a CSV file, I can only offer limited support for this feature. This tool will only set the basic values needed for a question. You will need to manually set Quiz settings, or extra Question options such as question type and images after the import has completed.</p>
            <p><strong>This tool is ONLY for General/Scored quizzes</strong>. It is not compatible with the new Personality quiz type.</p>

            <div class="hdq_highlight_field">
                <h2>Instructions <a href="https://hdplugins.com/learn/hd-quiz/hd-quiz-documentation/?utm_source=HDQSettingsPage#hd_i-have-a-problem-with-the-csv-importer" style="font-size: 16px;" class="hdq_button hdq_button_secondary" role="button" target="_blank">DOCUMENTATION</a></h2>
                <p>Fields should use a comma <code>,</code> as a delimiter and strings should be enacpulated in double quotes <code>"</code> if the string contains a comma.</p>
                <p>
                    <strong>Fields: </strong> <code>Quiz Name</code>, <code>Question Title</code>, <code>Answer 1</code>,
                    <code>Answer 2</code>, <code>Answer 3</code>, ... <code>Answer 10</code>, <code>Correct Answer</code>.
                    <br /> The <code>Correct Answer</code> field should be an integer that corresponds to which answer is correct. So if
                    <code>Answer 3</code> is the correct answer, then set <code>Correct Answer</code> to <code>3</code>.
                </p>
                <p style="text-align: center">View example CSV to use as a reference. <a href="<?php echo plugins_url('../assets/hdq-demo-questions.csv', __FILE__); ?>" class="hdq_button" target="_blank">example CSV file</a></p>
            </div>

            <div id="hdq_csv_upload_form_wrapper">
                <form action="<?php echo get_admin_url(null, "?page=hdq_importer"); ?>" id="hdq_csv_upload_form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('hdq_tools_nonce', 'hdq_tools_nonce'); ?>
                    <label for="hdq_csv_file_upload" style="font-weight: bold">Choose CSV file:</label><br />
                    <input type="file" id="hdq_csv_file_upload" name="hdq_csv_file_upload">
                    <input type="submit" class="hdq_button hdq_button_primary" value="UPLOAD">
                </form>
            </div>
    </div>
<?php
        } else {
            hdq_accept_csv();
        }
    }

    /* HD Quiz "Addons"
------------------------------------------------------- */
    function hdq_addons_page()
    {
        if (!current_user_can('edit_others_pages')) {
            die();
        }

        $today = date("Ymd");
        update_option("hdq_new_addon", $today);
        set_transient("hdq_new_addon", array("date" => $today, "isNew" => ""), WEEK_IN_SECONDS);


        wp_enqueue_style("hdq_admin", plugins_url('../assets/admin.css', __FILE__), array(), HDQ_PLUGIN_VERSION);
?>
<style>
    #hdq_addons {
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-gap: 2rem;
    }

    .hdq_addon_item {
        margin-bottom: 2rem;
        padding: 1rem;
        border: 1px solid #c5c5c5;
        border-radius: 8px;
        box-shadow: 0 0 22px #ddd, 0 0 22px #fff;
        transition: all ease-in-out 350ms;
    }

    .hdq_addon_item:hover {
        border-color: #999;
        box-shadow: 0 14px 32px #bebebe, 0 -14px 32px #fff;
    }

    .hdq_addon_item_image {
        margin-bottom: 1.5em;
    }

    .hdq_addon_item_image>img {
        display: block;
        width: 100%;
    }

    .hdq_addon_item>* {
        font-size: 16px;
    }

    span.hdq_price {
        border-radius: 8px;
        padding: 8px 12px;
        background: darkseagreen;
        color: #000;
        font-weight: 400;
        margin-left: 2px;
        font-size: 14px;
        position: relative;
        top: -4px;
        font-weight: bold;
    }

    .hdq_featured {
        border-color: darkseagreen !important;
        border-width: 3px;
    }
</style>


<div id="hdq_wrapper">
    <div id="hdq_header">
        <h1 id="hdq_heading_title">HD Quiz - Addons</h1>
    </div>

    <p>HD Quiz is created by a solo developer, Dylan of <a href="https://harmonicdesign.ca/" target="_blank">Harmonic Design</a>. This page will contain a list of all addons I have available for HD Quiz. Some addons are FREE, while others are paid. If you are enjoying HD Quiz, please consider purchasing an addon to help continued development and support.</p>

    <div id="hdq_addons">
        <?php
        $data = wp_remote_get("https://hdplugins.com/plugins/hd-quiz/addons.txt");
        if (!is_array($data)) {
            echo '<p>Unable to retrieve list of addons. I\'m probably having some server issues, please check back later.</p>';
        } else {
            $data = $data["body"];
            $data = stripslashes(html_entity_decode($data));
            $data = json_decode($data);

            if (!empty($data)) {
                foreach ($data as $value) {
                    $title = sanitize_text_field($value->title);
                    $thumb = sanitize_text_field($value->thumb);
                    $description = wp_kses_post($value->description);
                    $url = sanitize_text_field($value->url);
                    $author = sanitize_text_field($value->author);
                    $price = sanitize_text_field($value->price);
                    $slug = sanitize_text_field($value->slug);
                    $subscription = "";
                    if (isset($value->subscription)) {
                        $subscription = sanitize_text_field($value->subscription);
                    }

                    $featured = "";
                    if ($price == 0) {
                        $price = "FREE";
                    } else {
                        $price = "$" . $price;
                        $featured = "hdq_featured";
                    }
                    if ($subscription != "") {
                        $price = $price . ' / ' . $subscription;
                    }

        ?>

                    <div class="hdq_addon_item <?php echo $featured; ?>">
                        <div class="hdq_addon_item_image">
                            <img src="<?php echo esc_attr($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                        </div>
                        <h3><?php echo $title; ?> <span class="hdq_price"><?php echo esc_html($price); ?></span></h3>
                        <?php echo apply_filters('hdq_content', $description); ?>

                        <p style="text-align:center">
                            <?php
                            if ($slug != "" && $slug != null) {
                                echo '<a class = "hdq_button" target = "_blank" href = "plugin-install.php?tab=plugin-information&amp;plugin=' . esc_attr($slug) . '">VIEW ADDON PAGE</a>';
                            } else {
                                echo '<a href = "' . esc_attr($url) . '?utm_source=HDQuiz&utm_medium=addonsPage" target = "_blank" class = "hdq_button">VIEW PRODUCT PAGE</a>';
                            } ?>
                        </p>

                    </div>

        <?php
                }
            }
        }
        ?>
    </div>



</div>
<?php
    }

    /* Add custom notices for CPT and Tax admin
------------------------------------------------------- */
    function hdq_add_warning()
    {
?>
    <style>
        #hdq_quiz_tax_warning {
            padding: 12px;
            border: 2px solid #ff6666;
        }

        #hdq_quiz_tax_warning * {
            font-size: 1.4em;
        }

        .hdq_button4 {
            font-size: 0.8em;
            display: inline-block;
            padding: 12px 14px;
            background: #ff6666;
            color: #fff;
            margin: 12px 8px;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
    </style>
    <script>
        window.onload = function(e) {
            if (jQuery("body").hasClass("post-type-post_type_questionna") && jQuery("body").hasClass("taxonomy-quiz")) {
                // add warning to quiz taxonomy page
                let warning = '<div id = "hdq_quiz_tax_warning"><h2>WARNING</h2><p>Please note that deleting a quiz here will NOT delete any attached questions to it. You can delete questions in bulk by clicking the following button</p><a href = "./edit.php?post_type=post_type_questionna" class = "hdq_button4">DELETE QUESTIONS</a></div>';
                jQuery(".form-wrap").append(warning)
            } else if (jQuery("body").hasClass("post-type-post_type_questionna")) {
                // add warning to quiz taxonomy page
                let warning = '<br/><br/><br/><br/><div id = "hdq_quiz_tax_warning"><p>This page is just a quick and easy way to bulk delete questions, or add multiple questions to a quiz at the same time. WordPress already has this functionality built in, so no point in reinventing the wheel :)</p></div><br/>';
                jQuery("#posts-filter").prepend(warning);
                jQuery(".page-title-action").remove();
            }
        };
    </script>
<?php
    }

    function hdq_add_warning_to_quiz_tax($hook)
    {
        if ($hook === "edit-tags.php" || $hook === "edit.php") {
            if (isset($_GET["taxonomy"])) {
                if ($_GET["taxonomy"] === "quiz") {
                    hdq_add_warning(); // taxonomy
                }
            } else if (isset($_GET["post_type"])) {
                if ($_GET['post_type'] === "post_type_questionna") {
                    hdq_add_warning(); // CPT
                }
            }
        }
    }
    add_action('admin_enqueue_scripts', 'hdq_add_warning_to_quiz_tax', 10, 1);

    function hdq_cpt_question_meta_notice()
    {
        add_meta_box('hdq_question_meta', "NOTICE", 'hdq_question_meta_notice', 'post_type_questionna');
    }
    add_action('add_meta_boxes', 'hdq_cpt_question_meta_notice');

    function hdq_question_meta_notice()
    {
        echo '<p>the ablity to modify question data from here has been depricated since HD Quiz 1.6, and removed since HD Quiz 1.8. The bulk question edit page still exists so that you can easily delete questions in bulk, or add questions to quizzes in bulk.</p>';
    }
