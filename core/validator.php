<?php
namespace FW\Core;
use FW\Core\Request as Request;
use FW\Core\DB as DB;
use FW\Core\File as File;

class Validator {

	private static $extra = '';
	private static $errors = array();
	private static $input;
	private static $labels;

	public static function run ($inputs, $rules) {
		$rules = static::parse_labels($rules);
		$return = true;

		foreach ($rules as $input => $rule) {
			$sub = null;

			if ((bool) strpos($input, '.') === true) {
				list($input, $sub) = explode('.', $input);
			}

			if (!is_null($sub)) {
				$real_value = $inputs[$input][$sub];
				$input_name = $input . '.' . $sub;
			} else {
				$real_value = $inputs[$input];
				$input_name = $input;
			}

			if (is_array($real_value)) {
				foreach ($real_value as $rv) {
					static::$input = $input_name;
					static::validate($rv, $rule);
				}
			} elseif (isset($real_value)) {
				static::$input = $input_name;
				static::validate($real_value, $rule);
			}
		}

		if (count(static::$errors)) {
			$return = false;
		}

		return $return;
	}

	private static function validate ($value, $rule) {
		if ($rule != '' and !is_null($rule)) {
			$rules = static::parse_rule($rule);

			foreach ($rules as $rule) {
				if ((bool) strpos($rule, ':') === true) {
					static::$extra = substr($rule, strpos($rule, ':') + 1);
					$rule = substr($rule, 0, strpos($rule, ':'));
				}

				$method = 'validate_' . $rule;

				if (!in_array('required', $rules) and $value == '') {
					$return = false;
				} else {
					if (method_exists('Validator', $method)) {
						$return = static::$method($value);
					}
				}

				if ($return === false) {
					break;
				}
			}
		}
	}

	private static function parse_labels ($rules) {
		foreach ($rules as $name => $value) {
			unset($rules[$name]);

			if ((bool) strpos($name, ',')) {
				list($new_name, $label) = explode(',', $name);
				
				$rules[trim($new_name, ' []')] = $value;
				
				static::$labels[trim($new_name, ' []')] = trim($label);
			} else {
				$rules[trim($name, '[]')] = $value;
			}
		}

		return $rules;
	}

	private static function parse_rule ($rule) {
		$rule = explode(',', $rule);
		$rule = array_map("trim", $rule);

		return $rule;
	}

	public static function errors ($input = null) {
		if (is_null($input)) {
			return static::$errors;
		} else {
			if (isset(static::$errors[ucwords($input)])) {
				return ucwords($input) . ' ' . static::$errors[ucwords($input)];
			}
		}
	}

	private static function add_error ($message) {
		$input = static::$input;

		if (isset(static::$labels[$input])) {
			$input = static::$labels[$input];
		}

		static::$errors[ucwords($input)] = $message;
	}

	private static function validate_required ($value) {
		$value = trim($value);

		if ($value == '' or is_null($value)) {
			static::add_error(__('is required'));
			return false;
		}

		return true;
	}

	private static function validate_email ($value) {
		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			static::add_error(__('should be a valid email address'));
			return false;
		}

		return true;
	}

	private static function validate_length ($value) {
		$length = static::$extra;

		if (strlen($value) != $length) {
			static::add_error(__('should be exactly :1 character{1:|s}', $length));
			return false;
		}

		return true;
	}

	private static function validate_min ($value) {
		$min = static::$extra;

		if (is_numeric($value)) {
			if (!is_numeric($value) or $value < $min) {
				static::add_error(__('should be higher than :1', $min));
				return false;
			}
		} else {
			if (strlen($value) < $min) {
				static::add_error(__('should be longer than :1 character{1:|s}', $min));
				return false;
			}
		}

		return true;
	}

	private static function validate_max ($value) {
		$max = static::$extra;

		if (is_numeric($max)) {
			if (!is_numeric($value) or $value > $max) {
				static::add_error(__('should be smaller than :1', $max));
				return false;
			}
		} elseif (strlen($value) > $max) {
			static::add_error(__('should be shorter than :1 character{1:|s}', $max));
			return false;
		}

		return true;
	}

	private static function validate_between ($value) {
		$between = static::$extra;
		list($min, $max) = explode(';', $between);

		if (strlen($value) < $min or strlen($value) > $max) {
			static::add_error(__('should be between :1 and :2 characters', $min, $max));
			return false;
		}

		return true;
	}

	private static function validate_alpha ($value) {
		if (!preg_match('|^([a-z])+$|i', $value)) {
			static::add_error(__('should contain only letters'));
			return false;
		}

		return true;
	}

	private static function validate_alphanumeric ($value) {
		if (!preg_match('|^([a-z0-9])+$|i', $value)) {
			static::add_error(__('should contain only letters and numbers'));
			return false;
		}

		return true;
	}

	private static function validate_alpha_dash ($value) {
		if (!preg_match('|^([a-z0-9_-])+$|i', $value)) {
			static::add_error(__('should contain only letters, numbers, underscores and hyphens'));
			return false;
		}

		return true;
	}

	private static function validate_url ($value) {
		if (!filter_var($value, FILTER_VALIDATE_URL)) {
			static::add_error(__('should be a valid web address'));
			return false;
		}

		return true;
	}

	private static function validate_numeric ($value) {
		if (!is_numeric($value)) {
			static::add_error(__('should be numeric'));
			return false;
		}

		return true;
	}

	private static function validate_integer ($value) {
		if (!filter_var($value, FILTER_VALIDATE_INT)) {
			static::add_error(__('should be integer'));
			return false;
		}

		return true;
	}

	private static function validate_same ($value) {
		$field = static::$extra;

		if (!Request::post($field) and $value != Request::post($field)) {
			static::add_error(__('should match the confirmation field'));
			return false;
		}

		return true;
	}

	private static function validate_different ($value) {
		$field = static::$extra;

		if (!Request::post($field) and $value == Request::post($field)) {
			static::add_error(__('should not match the confirmation field'));
			return false;
		}

		return true;
	}

	private static function validate_in ($value) {
		$values = explode(';', static::$extra);

		if (!in_array($value, $values)) {
			static::add_error(__('should be a correct value'));
			return false;
		}

		return true;
	}

	private static function validate_out ($value) {
		$values = explode(';', static::$extra);

		if (in_array($value, $values)) {
			static::add_error(__('should be a correct value'));
			return false;
		}

		return true;
	}

	private static function validate_match ($value) {
		$match = static::$extra;

		if (!preg_match($match, $value)) {
			static::add_error(__('should be a correct value'));
			return false;
		}

		return true;
	}

	private static function validate_unique ($value) {
		$table = static::$extra;
		list($table, $field) = explode('.', $table);
		$except = '';

		if ((bool) strpos($field, ';') === true) {
			list($field, $except) = explode(';', $field);
			$except = 'AND ' . str_replace('=', "!='", DB::clean($except)) . "'";
		}

		$value = DB::clean($value);
		$results = DB::query("SELECT $field FROM $table WHERE $field='$value' $except");

		if ($results->num_rows()) {
			static::add_error(__('should be unique'));
			return false;
		}

		return true;
	}

	private static function validate_exists ($value) {
		$table = static::$extra;
		list($table, $field) = explode('.', $table);

		$value = DB::clean($value);
		$results = DB::query("SELECT $field FROM $table WHERE $field='$value'");

		if (!$results->num_rows()) {
			static::add_error(__('should be a correct value'));
			return false;
		}

		return true;
	}

	private static function validate_before ($value) {
		$before = static::$extra;

		if (strtotime($value) > strtotime($before)) {
			static::add_error(__('should be a lower date'));
			return false;
		}

		return true;
	}

	private static function validate_after ($value) {
		$before = static::$extra;

		if (strtotime($value) < strtotime($before)) {
			static::add_error(__('should be a higher date'));
			return false;
		}

		return true;
	}

	private static function validate_type ($value) {
		$types = static::$extra;
		$types = explode(';', $types);
		$ext = File::extension($value);

		if (!in_array($ext, $types)) {
			static::add_error(__('should be on of the following format{2:|s}: :1', implode(', ', $types), count($types)));
			return false;
		}

		return true;
	}

}