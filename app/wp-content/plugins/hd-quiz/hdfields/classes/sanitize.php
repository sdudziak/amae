<?php

namespace hdquiz;

// sanitizes data (components, attributes, and values)
class _hd_sanitize
{
	protected $data = array();
	protected $components = array();
	protected $fields = array();
	public $returnLabels = false;

	public function __construct($data = array())
	{
		$this->data = $data;
		$this->getComponents();

		$fields = new _hd_fields();
		$this->fields = $fields->fields_list;
	}

	private function getComponents()
	{
		$JSON = '{
	"id": "text_field",
	"type": "text_field",
	"heading_type": "text_field",
	"heading_content": "wp_kses",
	"label": "wp_kses",
	"tooltip": "wp_kses",
	"description": "wp_kses_post",
	"content": "wp_kses_post",
	"placeholder": "text_field",
	"required": "boolean",
	"prefix": "text_field",
	"postfix": "text_field",
	"attributes": "text_field",
	"options": "text_field",
	"media": "boolean",
	"column_type": "text_field",
	"action": "text_field",
	"default": "text_field",
	"hasParent": "boolean",
	"image": "text_field",
	"single": "boolean",
	"multiple": "boolean",
	"sortable": "boolean",
	"accepts": "text_field"
}';
		$components = json_decode($JSON, true);
		// allow field components to be filterable
		$components = apply_filters("hd_add_new_field_components", $components);
		$this->components = $components;

		// NOTE: Attributes needs to be an array of objects
		// [{"name": "attribute name", "value":"attribute value"}]
	}

	// sanitize each value by type
	function values($flat = false)
	{
		$values = array();
		foreach ($this->data as $k => $field) {
			$type = $field["type"];
			if (isset($this->fields[$type])) {
				$method = $this->fields[$type]["value"];
				if (method_exists($this, $method)) {
					$v = $this->$method($field["value"]);
				} else {
					$function = $this->fields[$type]["value"];
					if (function_exists($function)) {
						$v = $function($field["value"]);
					} else {
						// no method, no function, default to text
						$v = $this->text_field($field["value"]);
					}
				}

				if ($flat) {
					if ($this->returnLabels) {
						$values[$k] = array(
							"value" => $v,
							"label" => ""
						);
						if (isset($field["label"])) {
							$values[$k]["label"] = $this->wp_kses($field["label"]);
						}
					} else {
						$values[$k] = $v;
					}
				} else {
					$values[$k] = array(
						"value" => $v,
						"type"	=> $type
					);
				}
			} else {
				echo 'No sanitization method found for field type ' . $type;
			}
		}
		$this->data = $values;
		return $values;
	}

	public function fields($field_data)
	{
		$data = array();
		foreach ($field_data as $k => $field) {
			$data[$k] = $this->component($k, $field);
		}
		return $data;
	}

	private function component($k, $field)
	{
		$data = "error";

		if (!is_array($field)) {
			if (isset($this->components[$k])) {
				$type = $this->components[$k];
				if (method_exists($this, $type)) {
					$data = $this->$type($field);
				} else {
					// Ya blew it! Return "error"
				}
			}
		} else {
			if ($k === "children") {
				return $field; // will be sanitized once we get to child field
			} else {
				if (isset($this->components[$k])) {
					// Assume it's an array of label|value pairs (or simular)
					$type = $this->components[$k];
					if (method_exists($this, $type)) {
						$data = array();
						foreach ($field as $f) {
							if (is_array($f)) {
								$arr = array();
								foreach ($f as $kk => $ff) {
									$arr[$kk] = $this->$type($ff);
								}
								array_push($data, $arr);
							} else {
								// for things like default values as array
								array_push($data, $this->$type($f));
							}
						}
					} else {
						// Ya blew it! Return "error"
					}
				}
			}
		}
		return $data;
	}


	/* Start sanitization functions */
	function text_field($value)
	{
		if (is_array($value)) {
			$data = array();
			foreach ($value as $v) {
				if (!is_array($v)) {
					array_push($data, sanitize_text_field(stripslashes($v)));
				} else {
					return "Error";
				}
			}
			return $data;
		}
		return sanitize_text_field(stripslashes($value));
	}

	function textarea_field($value)
	{
		return sanitize_textarea_field(stripslashes($value));
	}

	function textarea_code($value)
	{
		return esc_html($value);
	}

	function email_field($value)
	{
		return sanitize_email(stripslashes($value));
	}

	function url($value)
	{
		return sanitize_url(stripslashes($value));
	}

	function intval($value)
	{
		if ($value == "") {
			return "";
		}

		if (is_array($value)) {
			$data = array();
			foreach ($value as $v) {
				if (!is_array($v)) {
					array_push($data, intval($v));
				} else {
					return "Error";
				}
			}
			return $data;
		}
		return intval($value);
	}

	function floatval($value)
	{
		if ($value == "") {
			return "";
		}
		if (is_array($value)) {
			$data = array();
			foreach ($value as $v) {
				if (!is_array($v)) {
					array_push($data, floatval($v));
				} else {
					return "Error";
				}
			}
			return $data;
		}
		return floatval($value);
	}

	function currency($value)
	{
		if ($value == "") {
			return "";
		}
		return floatval($value); // NOTE: number_format($value, 2) should be used when *visually* printing
	}

	function wp_kses_post($value)
	{
		$value = wp_kses_post($value);
		$value = apply_filters('hd_content', $value);
		$value = wpautop($value);
		return $value;
	}

	function wp_kses($value)
	{
		$allowed_html = array(
			'a' =>  array(
				'id' => array(),
				'class' => array(),
				'href' => array(),
				'title' => array(),
				'target' => array()
			),
			'p' => array(),
			'span' => array(
				'id' => array(),
				'class' => array()
			),
			'strong' => array(),
			'em' => array(),
			'code' => array(),
			'sup' => array(),
			'sub' => array(),
			'small' =>  array(
				'id' => array(),
				'class' => array()
			),
			'br' => array()
		);
		return wp_kses($value, $allowed_html);
	}

	function boolean($value)
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}
}


// custom filter to replace the_content filter and stop other plugins
// from adding or modifying the content (looking at you social share plugins)
add_filter('hd_content', 'wptexturize');
add_filter('hd_content', 'convert_smilies');
add_filter('hd_content', 'convert_chars');
add_filter('hd_content', 'wpautop');
add_filter('hd_content', 'shortcode_unautop');
add_filter('hd_content', 'prepend_attachment');
