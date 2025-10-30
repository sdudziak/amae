<?php

namespace hdquiz;

// gets, saves, and displays data
// ENHANCE: image and gallery attributes for title and button labels
class _hd_fields
{
    public $tabs = ""; // if data is contained in tabs. Accepts: "", "vertical", or "horizontal"
    public $fields_list = array(); // stores field type, value sanitization type, and field components
    public $fields = array(); // stores field data for display
    public $field_values = array(); // stores field value data
    public $values = array(); // stores flat array of cleaned values
    public $media = false; // if we need to enqueue media libraries (for WP Admin editor or media upload)
    public $returnLabels = false; // if we should return label, value pair
    private $html = ""; // rendered HTML to return

    public function __construct($fields = array(), $values = array(), $tabs = "")
    {
        $this->tabs = sanitize_text_field($tabs);
        $this->fields = $fields;
        $this->field_values = $values;
        $this->getFields();
    }

    private function getFields()
    {
        $JSON = '{
            "heading": { "value": "wp_kses", "components": ["heading_type", "heading_content"] },
            "text": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "textarea": { "value": "textarea_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "textarea_code": { "value": "textarea_code", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "email": { "value": "email_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "website": { "value": "url", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "integer": { "value": "intval", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "float": { "value": "floatval", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "currency": { "value": "currency", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "content": { "value": "wp_kses_post", "components": ["content"] },
            "divider": { "value": "", "components": [] },
            "date": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "prefix", "postfix", "attributes"] },
            "colour": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes"] },
            "image": { "value": "intval", "components": ["label", "required", "tooltip", "description", "attributes"] },
            "gallery": { "value": "intval", "components": ["label", "required", "tooltip", "description", "attributes"] },
            "select": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "prefix", "postfix", "attributes", "options"] },
            "radio": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "attributes", "options"] },
            "checkbox": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "placeholder", "attributes", "options"] },
			"image_toggle": { "value": "text_field", "components": ["multiple", "sortable", "label", "required", "tooltip", "description", "placeholder", "attributes", "options"] },            
            "editor": { "value": "wp_kses_post", "components": ["media", "label", "required", "tooltip", "description", "attributes"] },
            "column": { "value": "children", "components": ["column_type", "id"] },
			"hidden": { "value": "text_field", "components": ["required"] },
            "search_list": { "value": "text_field", "components": ["label", "required", "tooltip", "description", "attributes", "placeholder", "options"] }
        }';
        $fields = json_decode($JSON, true);

        // allow field types to be filterable
        $fields = apply_filters("hd_add_new_field_types", $fields);
        $this->fields_list = $fields;
    }

    // get all form values so we can set defaults and override with given data
    public function get_values($flat = true)
    {
        $valueArr = $this->get_value_array();
        foreach ($valueArr as $k => $value) {
            if (isset($this->field_values[$k])) {
                if ($this->field_values[$k]["value"] !== "") {
                    $valueArr[$k]["value"] = $this->field_values[$k]["value"];
                }
            }
        }
        $this->sanitize($valueArr, $flat);
        return $this->values;
    }

    private function sanitize($values, $flat)
    {
        $sanitize = new _hd_sanitize($values);
        if ($this->returnLabels) {
            $sanitize->returnLabels = true;
        }
        $this->values = $sanitize->values($flat);
    }

    // creates flat array of id => value
    private function get_value_array($values = array(), $fields = null)
    {
        if ($fields === null && $this->tabs !== false && $this->tabs !== "") {
            $fields = array();
            foreach ($this->fields as $tab) {
                $fields = array_merge($fields, $tab["children"]);
            }
        }

        if ($fields === null) {
            $fields = $this->fields;
        }

        foreach ($fields as $field) {
            if (isset($field["id"]) && isset($field["type"])) {
                $values[$field["id"]] = array(
                    "value" => "",
                    "type" => $field["type"]
                );
                if ($this->returnLabels) {
                    if (isset($field["label"])) {
                        $values[$field["id"]]["label"] = $field["label"];
                    }
                }
                if (isset($field["default"])) {
                    $values[$field["id"]]["value"] = $field["default"];
                }
            }

            if (isset($field["children"]) && is_array($field["children"])) {
                $values = $this->get_value_array($values, $field["children"]);
            }
        }

        return $values;
    }

    public function display()
    {
        $this->get_values(false);
        $this->html = $this->render();
        if ($this->media) {
            wp_enqueue_media();
        }
        return $this->html;
    }

    private function render($fields = array())
    {
        if (empty($fields)) {
            $fields = $this->fields;
        }

        $html = "";
        if (!$this->tabs || $this->tabs === "") {
            $html = $this->render_fields($fields);
        } else {
            $html .= $this->render_tabs($fields);
        }
        return $html;
    }

    private function render_tabs($fields)
    {
        $html = '<div class = "hd_tabs_anchor"></div>';

        // tab nav        
        if ($this->tabs === "vertical") {
            $html .= '<div class = "hd_content_tabs">';
            $logo = plugins_url('/../../assets/images/hd-logo.png', __FILE__);
            $html .= '<div class="hd_tab_nav_wrapper">
					<div class = "hd_logo">
						<span class="hd_logo_tooltip">
							<img src="' . $logo . '" alt="Harmonic Design Logo">
						</span>
					</div>
					<div class = "hd_tab_nav">';
        } else {
            $html .= '<div class = "hd_content_tabs hd_content_tabs_' . $this->tabs . '">';
            $html .= '<div class="hd_tab_nav_wrapper">					
					<div class = "hd_tab_nav">';
        }

        $this->tabs = false; // reset for tab children

        $i = 0;
        foreach ($fields as $tab) {
            $active = "";
            if ($i === 0) {
                $active = "hd_tab_nav_item_active";
            }
            $html .= '<div role="button" class="hd_tab_nav_item hd_kb ' . esc_attr($active) . '" tabindex = "0" data-id="' . esc_attr($tab["id"]) . '">' . esc_html($tab["label"]) . '</div>';
            $i++;
        }

        $html .= '</div></div>';

        // tab content
        $html .= '<div class = "hd_tab_content">';
        $i = 0;
        foreach ($fields as $tab) {
            $active = "";
            if ($i === 0) {
                $active = "hd_tab_content_section_active";
            }
            $html .= '<div id = "hd_tab_content_' . esc_attr($tab["id"]) . '" class = "hd_tab_content_section ' . $active . '">';
            $html .= '<h2 class="hd_tab_heading">' . esc_html($tab["label"]) . '</h2>';
            $html .= $this->render_fields($tab["children"], $tab["id"]);
            $html .= '</div>';
            $i++;
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function render_fields($fields)
    {
        $html = "";

        // array of field types that don't need an ID
        $no_id = array(
            "column",
            "divider",
            "heading",
            "content"
        );
        foreach ($fields as $field) {

            // sanitize field data
            $sanitize = new _hd_sanitize($field, $this->fields_list);
            $field = $sanitize->fields($field);    // get sanitized field (value + components)					

            $method = "render_" . $field["type"];
            if (isset($field["id"]) || in_array($field["type"], $no_id)) {
                $html .= '<div class = "hd_input_item">';
                if (method_exists($this, $method)) {
                    $field = $this->createComponents($field);
                    $html .= $this->$method($field);
                } else {
                    if (function_exists($method)) {
                        $field = $this->createComponents($field);
                        $value = "";
                        if (isset($this->values[$field["id"]])) {
                            $value = $this->values[$field["id"]];
                        }
                        $field["value"] = $this->get_value($field);
                        $html .= $method($field);
                    } else {
                        $html .= $this->render_field_not_found($field);
                    }
                }
                $html .= '</div>';
            } else {
                // n00b. add an ID to the field
            }
        }
        return $html;
    }

    private function createComponents($field)
    {
        $type = $field["type"];
        $components = $this->fields_list[$type]["components"];
        foreach ($components as $component) {
            if (!isset($field[$component])) {
                $field[$component] = ""; // set component value
            }
        }

        // list of fields whose value is an array
        $field_arrays = array(
            "checkbox",
            "radio",
            "gallery",
            "imageSelect"
        ); // TODO: should make filterable for custom fields?

        if (!isset($field["value"])) {
            if (in_array($field["type"], $field_arrays)) {
                $field["value"] = array();
            } else {
                $field["value"] = "";
            }
        }
        if (!isset($field["default"])) {
            if (in_array($field["type"], $field_arrays)) {
                $field["default"] = array();
            } else {
                $field["default"] = "";
            }
        }
        return $field;
    }


    /*
        * Render Components
    */

    public function get_value($field)
    {
        if (isset($this->values[$field["id"]])) {
            if (isset($this->values[$field["id"]]["value"])) {
                return $this->values[$field["id"]]["value"];
            }
        }
        return "";
    }

    private function get_required_label($field)
    {
        $html = "";
        if (!isset($field["required"])) {
            $field["required"] = false;
        }
        if ($field["required"] == true) {
            $html = '<span class="hd_required_icon"></span>';
        }
        return $html;
    }

    private function get_tooltip($field)
    {
        if (!isset($field["tooltip"]) || $field["tooltip"] == "") {
            return "";
        }
        $html = '<span class = "hd_tooltip_item">?<span class = "hd_tooltip"><div class = "hd_tooltip_content">' . $field["tooltip"] . '</div></span></span>';
        return $html;
    }

    public function get_label($field)
    {
        if ($field["label"] === "") {
            return "";
        }
        $required = $this->get_required_label($field);
        $tooltip = $this->get_tooltip($field);
        $html = '<label class="hd_input_label" for="' . esc_attr($field["id"]) . '">' . $required . $field["label"] . $tooltip . '</label>';
        if (!isset($field["hasParent"]) || $field["hasParent"] !== true) {
            $html .= $this->get_description($field);
        }
        return $html;
    }

    public function get_description($field, $after = false)
    {
        if ($after && !isset($field["description"]) || $field["description"] == "") {
            return "";
        }
        if ($after && !isset($field["hasParent"]) || isset($field["hasParent"]) && $field["hasParent"] !== true) {
            return "";
        }
        $afterClass = "";
        if ($after) {
            $afterClass = "hd_input_description_after";
        }
        return '<div class = "hd_input_description ' . $afterClass . '">' . $field["description"] . '</div>';
    }

    private function get_attributes($field)
    {
        if (empty($field["attributes"])) {
            return "";
        }
        $html = "";
        foreach ($field["attributes"] as $a) {
            if ($a["value"] === "") {
                $html .= esc_attr($a["name"]) . " ";
            } else {
                $html .= esc_attr($a["name"]) . ' = "' . esc_attr($a["value"]) . '" ';
            }
        }
        return $html;
    }

    private function get_fix($field, $html)
    {
        if (isset($field["prefix"]) && $field["prefix"] !== "") {
            $html = '<div class = "hd_prefix"><div class = "hd_fix">' . $field["prefix"] . '</div>' . $html . '</div>';
        } elseif ($field["postfix"] !== "") {
            $html = '<div class = "hd_postfix">' . $html . '<div class = "hd_fix">' . $field["postfix"] . '</div></div>';
        }
        return $html;
    }

    /*
        * Render fields
    */

    private function render_field_not_found($field)
    {
        return 'render function for field type ' . $field["type"] . ' not found';
    }

    private function render_column($field)
    {
        $html = "";
        $id = "";
        if (isset($field["id"]) && $field["id"] !== "") {
            $id = 'id = "' . $field["id"] . '"';
        }
        if (!empty($field["children"])) {
            $html .= '<div class = "hd_cols hd_cols_' . esc_attr($field["column_type"]) . '" ' . $id . '>';
            foreach ($field["children"] as $child) {
                $child["hasParent"] = true;
                $html .= $this->render(array($child));
            }
            $html .= '</div>';
        }
        return $html;
    }

    private function render_heading($field)
    {
        $allowed = array("h1", "h2", "h3", "h4", "h5", "h6");
        $field["heading_type"] = strtolower($field["heading_type"]);
        if (!in_array($field["heading_type"], $allowed)) {
            $field["heading_type"] = "H2";
        }
        if (!isset($field["id"])) {
            $field["id"] = "";
        }
        return '<' . esc_attr($field["heading_type"]) . ' id = "' . esc_attr($field["id"]) . '">' . $field["heading_content"] . '</' . esc_attr($field["heading_type"]) . '>';
    }

    private function render_content($field)
    {
        return $field["content"];
    }

    private function render_divider($field)
    {
        return '<hr/>';
    }

    private function render_text($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="text" data-type="text" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" placeholder="' . $field["placeholder"] . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_email($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="email" data-type="email" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($field["placeholder"]) . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_website($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);
        if ($field["placeholder"] == "") {
            $field["placeholder"] = "https://";
        }

        $input = '<input type="text" data-type="website" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($field["placeholder"]) . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_integer($field)
    {
        $value = $this->get_value($field);

        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="number" data-type="integer" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($field["placeholder"]) . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);

        return $html;
    }

    private function render_float($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="number" data-type="float" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($field["placeholder"]) . '">';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_currency($field)
    {
        $currency = array(
            "prefix" => '$'
        );
        $currency = apply_filters("_hdf_currency", $currency);

        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        if (!isset($field["placeholder"]) || $field["placeholder"] == "") {
            $field["placeholder"] = "0.00";
        }

        $input = '<input type="number" data-type="currency" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($field["placeholder"]) . '">';

        $html .= $this->get_fix($currency, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_textarea($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $html .= '<textarea type="text" data-type="textarea" data-required="' . $required . '" ' . $attributes . ' rows = "8" class="hderp hd_input" id="' . esc_attr($field["id"]) . '" placeholder="' . esc_attr($field["placeholder"]) . '">' . esc_textarea($value) . '</textarea>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_textarea_code($field)
    {
        $value = $this->get_value($field);
        if ($value !== "") {
            $value = htmlspecialchars_decode($value);
        }
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $html .= '<textarea type="text" data-type="textarea_code" data-required="' . $required . '" ' . $attributes . ' rows = "8" class="hderp hd_input" id="' . esc_attr($field["id"]) . '" placeholder="' . esc_attr($field["placeholder"]) . '">' . esc_textarea($value) . '</textarea>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_select($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<select data-type="select" data-required="' . $required . '" class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" ' . $attributes . '>';
        if ($field["placeholder"] !== "") {
            $input .= '<option value = "">' . esc_html($field["placeholder"]) . '</option>';
        } else {
            if ($required === "required") {
                $input .= '<option value = "">- - -</option>';
            }
        }
        foreach ($field["options"] as $option) {
            $selected = "";
            if ($option["value"] == $value) {
                $selected = "selected";
            }
            $input .= '<option value = "' . esc_attr($option["value"]) . '" ' . $selected . '>' . esc_attr($option["label"]) . '</option>';
        }
        $input .= '</select>';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_radio($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $html .= '<div data-type="radio" data-required="' . $required . '" class="hderp hd_input_radio" id="' . esc_attr($field["id"]) . '">';
        $i = 0;
        foreach ($field["options"] as $option) {
            $checked = "";
            if ($option["value"] == $value) {
                $checked = 'checked';
            }
            $label = $option["label"];

            if (isset($option["tooltip"]) && $option["tooltip"] !== "") {
                $label .=  $this->get_tooltip($option);
            }

            $html .= '<div class="hd_input_row">
		<label class="hd_label_input" data-type="radio" data-id="' . esc_attr($field["id"]) . '" for="' . esc_attr($field["id"]) . $i . '">
			<div class="hd_options_check">
				<input type="checkbox" title="' . esc_attr($option["label"]) . '" data-id="' . esc_attr($field["id"]) . '" class="hd_option hd_radio_input" data-type="radio" value="' . esc_attr($option["value"]) . '" name="' . esc_attr($field["id"]) . $i . '" autocomplete="off" ' . $attributes . ' id="' . esc_attr($field["id"]) . $i . '" ' . $checked . '>
				<span class="hd_toggle"><span class="hd_aria_label">' . esc_attr($option["label"]) . '</span></span>			
			</div>
			' . $label . '
		</label>';

            if (isset($option["description"]) && $option["description"] !== "") {
                $html .= '<span class = "hd_option_description">' . $option["description"] . '</span>';
            }

            $html .= '</div>';
            $i++;
        }
        $html .= '</div>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_checkbox($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $columns = "";
        if (count($field["options"]) > 30) {
            $columns = "hd_cols hd_cols_fit hd_cols_1-1";
        }

        $html .= '<div data-type="checkbox" data-required="' . $required . '" class="hderp hd_input_checkbox ' . esc_attr($columns) . '" id="' . esc_attr($field["id"]) . '">';
        $i = 0;
        foreach ($field["options"] as $option) {
            $checked = "";
            if (in_array($option["value"], $value)) {
                $checked = 'checked';
            }
            $label = $option["label"];

            $html .= '<div class="hd_input_row">
		<label class="hd_label_input" data-type="radio" data-id="' . esc_attr($field["id"]) . '" for="' . esc_attr($field["id"]) . $i . '">
			<div class="hd_options_check">
				<input type="checkbox" title="' . esc_attr($option["label"]) . '" data-id="' . esc_attr($field["id"]) . '" class="hd_option hd_check_input" data-type="radio" value="' . esc_attr($option["value"]) . '" name="' . esc_attr($field["id"]) . $i . '" autocomplete="off" id="' . esc_attr($field["id"]) . $i . '" ' . $checked . '>
				<span class="hd_toggle"><span class="hd_aria_label">' . esc_attr($option["label"]) . '</span></span>			
			</div>
			' . $label . '
		</label>';

            if (isset($option["description"]) && $option["description"] !== "") {
                $html .= '<span class = "hd_option_description">' . $option["description"] . '</span>';
            }

            $html .= '</div>';

            $i++;
        }
        $html .= '</div>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_image_toggle($field)
    {
        $value = $this->get_value($field);
        if (!is_array($value)) {
            $value = array();
        }
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }

        $html .= '<div class="hd_image_toggle hderp" data-type = "image_toggle" data-required="' . esc_attr($required) . '"  data-multiple = "' . esc_attr($field["multiple"]) . '"  data-sortable = "' . esc_attr($field["sortable"]) . '" id = "' . esc_attr($field["id"]) . '">';

        // print toggles
        $html .= '<div class="hd_image_toggle_items">';
        foreach ($field["children"] as $toggle) {
            $selected = "";
            if (in_array($toggle["id"], $value)) {
                $selected = "active";
            }

            $html .= '<div class = "hd_image_toggle_item ' . esc_attr($selected) . '" data-id = "' . esc_attr($toggle["id"]) . '" title = "Select ' . esc_attr($toggle["title"]) . '">';
            $html .= '<img src = "' . esc_attr($toggle["image"]) . '" alt = "' . esc_attr($toggle["title"]) . '"/>';
            $html .= '</div>';
        }
        $html .= "</div>";

        // print toggle content
        foreach ($field["children"] as $toggle) {
            $selected = "";
            if (in_array($toggle["id"], $value)) {
                $selected = "active";
            }
            $html .= '<div class = "hd_image_toggle_content ' . $selected . '" data-id = "' . esc_attr($toggle["id"]) . '">';
            $html .= $this->render_fields($toggle['children']);
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function render_colour($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);
        if ($field["placeholder"] == "") {
            $field["placeholder"] = "#000000";
        }

        $style = "";
        if ($field["default"] !== "") {
            $style = "background-color: " . $field["default"];
        }
        if ($value !== "") {
            $style = "background-color: " . $value;
        }

        $html .= '<div class="hd_postfix"><input type="text" maxlength = "7" data-type="colour" data-required="' . esc_attr($required) . '" class="hderp hd_input hd_colour" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" placeholder="' .  esc_attr($field["placeholder"]) . '"><div class="hd_fix hd_colour_select" title = "Select Colour" data-id = "' . esc_attr($field["id"]) . '" style = "' .  esc_attr($style) . '">&nbsp;&nbsp;&#x270E;&nbsp;&nbsp;</div></div>';
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_date($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $input = '<input type="date" data-type="date" data-required="' . $required . '" ' . $attributes . ' class="hderp hd_input" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '" >';

        $html .= $this->get_fix($field, $input);
        $html .= $this->get_description($field, true);
        return $html;
    }

    private function render_image($field)
    {
        $this->media = true;
        $value = $this->get_value($field);

        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $title = "Set image";
        $button = "Set image";
        $active = "";
        if ($value > 0) {
            $active = "active";
        }

        $content = "upload image";
        if ($value > 0) {
            $content = wp_get_attachment_image($value, "medium");
        }

        $html .= '<div data-title="' .  esc_attr($title) . '" data-button="' .  esc_attr($button) . '" data-multiple="no" data-type="image" data-required="' . $required . '" class="hderp hd_image" data-value="' .  esc_attr($value) . '" id="' . esc_attr($field["id"]) . '" role="button" title="upload image">' . $content . '</div>';
        $html .= '<span class="hd_image_remove ' . $active . '" data-type="image" data-id="' . esc_attr($field["id"]) . '" onclick="_hd.images.remove(this)" role="button">remove image</span>';

        return $html;
    }

    private function render_gallery($field)
    {
        $value = $this->get_value($field);
        if ($value == "") {
            $value = array();
        }
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }

        $title = "Set image";
        $button = "Set image";

        $images = "";
        foreach ($value as $imageId) {
            if ($imageId > 0) {
                $images .= '<div class="hd_gallery_image" data-type="gallery" onclick="_hd.images.remove(this)" data-value="' . esc_attr($imageId) . '" role="button" title="click to delete, drag and drop to reorder">' . wp_get_attachment_image($imageId, "thumb") . '</div>';
            }
        }
        $html = $this->get_label($field);
        $html .= '<div data-title="' .  esc_attr($title) . '" data-button="' .  esc_attr($button) . '" data-multiple="yes" data-type="gallery" data-required="' . $required . '" class="hderp hd_image" id="' . esc_attr($field["id"]) . '" role="button" title="upload image">upload images</div>';
        $html .= '<div class = "hd_gallery_content">' . $images . '</div>';
        return $html;
    }

    private function render_editor($field)
    {
        $this->media = true;
        $value = $this->get_value($field);
        $requiredClass = "";
        $required = "";
        if ($field["required"]) {
            $required = "required";
            $requiredClass = "hd_editor_required";
        }
        $attributes = $this->get_attributes($field);

        $html = "";
        $html .= $this->get_label($field);
        $media = false;
        if ($field["media"]) {
            $media = true;
        }
        ob_start();
        add_filter('user_can_richedit', '__return_true', 999999);
        wp_editor(stripslashes(urldecode($value)), $field["id"], array('textarea_name' => $field["id"], 'editor_class' => "hderp hd_input hd_editor_input " . $requiredClass, 'media_buttons' => $media, 'textarea_rows' => 20, 'quicktags' => true, 'editor_height' => 240));
        remove_filter('user_can_richedit', '__return_true');
        $html .= ob_get_clean();

        return $html;
    }

    private function render_action($field)
    {
        // NOTE: This should not be used to create new fields
        // and is more for one off custom content areas that might need dynamic values
        // from outside of HDFields
        $f = $field["action"];
        if (!function_exists($f)) {
            return "No function with name " . $f;
        }
        $field["value"] = $this->get_value($field);
        return $f($field);
    }

    private function render_hidden($field)
    {
        $value = $this->get_value($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $html = '<input type="hidden" disabled style = "display: none !important;" data-type="text" data-required="' . $required . '" class="hderp" id="' . esc_attr($field["id"]) . '" value="' . esc_attr($value) . '">';
        return $html;
    }

    private function render_search_list($field)
    {
        $value = $this->get_value($field);
        $html = "";
        $html .= $this->get_label($field);
        $required = "";
        if ($field["required"]) {
            $required = "required";
        }
        $attributes = $this->get_attributes($field);

        $html .= '<div class = "hd_search_list" id = "' . esc_attr($field["id"]) . '_wrapper">';
        $html .= '<input type="text" autocapitalize = "none" spellcheck = "false" data-required="' . $required . '" class="hd_input" id="' . esc_attr($field["id"]) . '_input" placeholder = "' . esc_attr($field["placeholder"]) . '" data-list = "' . esc_attr(json_encode($field["options"])) . '">';
        $html .= '<div class = "hd_search_list_open">Enter 3 or more characters</div>';
        $html .= '<div class = "hderp hd_search_list_wrapper" id = "' . esc_attr($field["id"]) . '" data-type = "search_list"></div>';
        if (is_array($value)) {
            foreach ($value as $item) {
                $html .= '<div class="hderp hd_search_list_wrapper" id="products" data-type="search_list" data-tab="products"><span onclick="_hd.search_list.remove(this)" class="hd_search_list_item" data-value="' . esc_attr($item) . '">' . esc_html(get_the_title($item)) . '</span></div>';
            }
        }
        $html .= '</div>';
        return $html;
    }
}
