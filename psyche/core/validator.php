<?php
namespace Psyche\Core;
use Psyche\Core\DB;

/**
 * Form Validator
 * 
 * A simple and intuitive, rules-based form validation.
 *
 * @package Psyche\Core\Psyc
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Validator
{

	/**
	 * @var string Extra rules parameters
	 */
	private $extra = '';

	/**
	 * @var array List of all errors
	 */
	private $errors = array();

	/**
	 * @var string Active input that's being validated
	 */
	private $input;

	/**
	 * @var array Form elements labels
	 */
	private $labels;

	/**
	 * Constructor. Starts the validation.
	 * 
	 * @param array $inputs List of inputs to be validated
	 * @param array $rules List of rules for each input
	 */
	public function __construct ($inputs, $rules)
	{
		$rules = $this->parse_labels($rules);

		foreach ($rules as $input => $rule)
		{
			$sub = null;

			// A dot in the input means it's calling a sub-element
			// of an array. Mostly for $_FILES that return a multi-dimensional
			// array and each item can be accessed as: input.name, input.size,
			// input.tmp_name, etc.
			if ((bool) strpos($input, '.') === true)
			{
				list($input, $sub) = explode('.', $input);
			}

			if (isset($sub))
			{
				$real_value = $inputs[$input][$sub];
				$input_name = $input . '.' . $sub;
			}
			else
			{
				$real_value = $inputs[$input];
				$input_name = $input;
			}

			// If the input has a name such as "name[]", it means it's a group
			// of elements with the same name and is treated as an array. Each
			// value is validated seperately.
			if (is_array($real_value))
			{
				foreach ($real_value as $rv) {
					$this->input = $input_name;
					$this->validate($rv, $rule);
				}
			}
			else
			{
				$this->input = $input_name;
				$this->validate($real_value, $rule);
			}
		}
	}

	/**
	 * Factory static method.
	 * 
	 * @param array $inputs List of inputs to be validated
	 * @param array $rules List of rules for each input
	 * @return Validator 
	 */
	public static function run ($inputs, $rules)
	{
		return new static($inputs, $rules);
	}

	/**
	 * Checks if validation passed.
	 * 
	 * @return bool
	 */
	public function passed ()
	{
		if (count($this->errors))
		{
			return false;
		}

		return true;
	}

	/**
	 * Checks if validation failed.
	 * 
	 * @return bool
	 */
	public function failed ()
	{
		if (count($this->errors))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns the list of errors.
	 * 
	 * @param string $input Input name to be returned specifically
	 * @return string|array
	 */
	public function errors ($input = null)
	{
		if (!isset($input))
		{
			return $this->errors;
		}
		else
		{
			if (isset($this->errors[ucwords($input)]))
			{
				return ucwords($input) . ' ' . $this->errors[ucwords($input)];
			}
		}
	}

	/**
	 * Runs the appropriate validation rule.
	 * 
	 * @param string $value Value of the input
	 * @param string $rule The rule for the input to be checked
	 * @return void
	 */
	protected function validate ($value, $rule)
	{
		if ($rule != '' and isset($rule))
		{
			$rules = $this->parse_rule($rule);

			// Iterates through all the rules of a single input.
			foreach ($rules as $rule)
			{
				// A colon in the rule means it's a rule with values (extras).
				// That value is passed to the $extra property.
				// Ex: length:10
				if ((bool) strpos($rule, ':') === true)
				{
					$this->$extra = substr($rule, strpos($rule, ':') + 1);
					$rule = substr($rule, 0, strpos($rule, ':'));
				}

				$method = 'validate_' . $rule;

				// If the rule has no "required" and it's empty, there's no
				// need to validated it.
				if (!in_array('required', $rules) and $value == '')
				{
					$return = false;
				}
				else
				{
					// Call the validation method if it exists.
					if (method_exists($this, $method))
					{
						$return = $this->$method($value);
					}
				}

				// If a rule fails, the whole validation for an input fails.
				if ($return === false)
				{
					break;
				}
			}
		}
	}

	/**
	 * Reads input labels and renames the input. The same label will be
	 * used for error messages.
	 * Ex: name, Full Name => '...'
	 * 
	 * @param array $rules List of rules
	 * @return array
	 */
	protected function parse_labels ($rules)
	{
		foreach ($rules as $name => $value)
		{
			unset($rules[$name]);

			// If it contains a label, the rule's name is modified.
			if ((bool) strpos($name, ','))
			{
				list($new_name, $label) = explode(',', $name);
				
				$rules[trim($new_name, ' []')] = $value;
				
				$this->labels[trim($new_name, ' []')] = trim($label);
			}
			else
			{
				$rules[trim($name, '[]')] = $value;
			}
		}

		return $rules;
	}

	/**
	 * Parses an input's rule and returns multiple rules separated
	 * with a comma.
	 * Ex: required, email
	 * 
	 * @param string $rule A single input rule
	 * @return array
	 */
	protected function parse_rule ($rule)
	{
		$rule = explode(',', $rule);
		$rule = array_map("trim", $rule);

		return $rule;
	}

	/**
	 * Adds an error.
	 * 
	 * @param string $message Error message
	 * @return void
	 */
	protected function add_error ($message)
	{
		$input = $this->input;

		// If a label exists for the active rule, it's set
		// in the message.
		if (isset($this->labels[$input]))
		{
			$input = $this->labels[$input];
		}

		$this->errors[ucwords($input)] = $message;
	}

	/**
	 * Validates a "required" rule.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_required ($value)
	{
		$value = trim($value);

		if ($value == '' or !isset($value) or $value === false)
		{
			$this->add_error(__('is required')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "email" rule.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_email ($value)
	{
		if (!filter_var($value, FILTER_VALIDATE_EMAIL))
		{
			$this->add_error(__('should be a valid email address')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "length" rule. The value's length should be exactly the same.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_length ($value)
	{
		$length = $this->extra;

		if (strlen($value) != $length)
		{
			$this->add_error(__('should be exactly :1 character{1:|s}', $length)->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "min" rule. If values is a number, it should be bigger than
	 * specified. If it's a string, it's length should be higher.
	 * 
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_min ($value)
	{
		$min = $this->extra;

		if (is_numeric($value))
		{
			if ($value < $min)
			{
				$this->add_error(__('should be higher than :1', $min)->in('errors'));
				return false;
			}
		}
		else
		{
			if (strlen($value) < $min)
			{
				$this->add_error(__('should be longer than :1 character{1:|s}', $min)->in('errors'));
				return false;
			}
		}

		return true;
	}

	/**
	 * Validates a "max" rule. If value is a number, it should be smaller than
	 * specified. If it's a string, it's length should smaller.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_max ($value)
	{
		$max = $this->extra;

		if (is_numeric($max))
		{
			if (!is_numeric($value) or $value > $max)
			{
				$this->add_error(__('should be smaller than :1', $max)->in('errors'));
				return false;
			}
		}
		elseif (strlen($value) > $max)
		{
			$this->add_error(__('should be shorter than :1 character{1:|s}', $max)->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "between" rule. Value's length should be between the specified range.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_between ($value)
	{
		$between = $this->extra;
		list($min, $max) = explode(';', $between);

		if (strlen($value) < $min or strlen($value) > $max)
		{
			$this->add_error(__('should be between :1 and :2 characters', $min, $max)->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "alpha" rule. Value should have only alphabetic characters.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_alpha ($value)
	{
		if (!preg_match('|^([a-z])+$|i', $value)) {
			$this->add_error(__('should contain only letters')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "alphanumeric" rule. Value should have only alphanumeric characters.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_alphanumeric ($value)
	{
		if (!preg_match('|^([a-z0-9])+$|i', $value))
		{
			$this->add_error(__('should contain only letters and numbers')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "alpha_dash" rule. Value should have only alphanumeric
	 * and dash characters.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_alpha_dash ($value)
	{
		if (!preg_match('|^([a-z0-9_-])+$|i', $value))
		{
			$this->add_error(__('should contain only letters, numbers, underscores and hyphens')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "url" rule.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_url ($value)
	{
		if (!filter_var($value, FILTER_VALIDATE_URL))
		{
			$this->add_error(__('should be a valid web address')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "numeric" rule.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_numeric ($value)
	{
		if (!is_numeric($value))
		{
			$this->add_error(__('should be numeric')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "integer" rule.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_integer ($value)
	{
		if (!filter_var($value, FILTER_VALIDATE_INT))
		{
			$this->add_error(__('should be integer')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "same" rule. Value should be exactly as the
	 * confirmation field.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_same ($value)
	{
		$field = $this->extra;

		if (!isset($_POST[$field]) and $value != $_POST[$field])
		{
			$this->add_error(__('should match the confirmation field')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "different" rule. Value should be different than
	 * specified field.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_different ($value)
	{
		$field = $this->extra;

		if (!isset($_POST[$field]) and $value == $_POST[$field])
		{
			$this->add_error(__('should not match the confirmation field')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "in" rule. Value should be as one of the list.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_in ($value)
	{
		$values = explode(';', $this->extra);
		var_dump($values);

		if (!in_array($value, $values))
		{
			$this->add_error(__('should be a correct value')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "out" rule. Value shouldn't be as one of the list.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_out ($value)
	{
		$values = explode(';', $this->extra);

		if (in_array($value, $values))
		{
			$this->add_error(__('should be a correct value')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "match" rule. Value should match a regular expression.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_match ($value)
	{
		$match = $this->extra;

		if (!preg_match($match, $value))
		{
			$this->add_error(__('should be a correct value')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "unique" rule. Value should be unique in the database.
	 * An optional second parameter can be passed as an ID that should
	 * not be checked for uniqueness.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_unique ($value)
	{
		$table = $this->extra;
		list($table, $field) = explode('.', $table);
		$except = '';

		if ((bool) strpos($field, ';') === true)
		{
			list($field, $except) = explode(';', $field);
			$except = 'AND ' . str_replace('=', "!='", $except) . "'";
		}

		$results = DB::query("SELECT $field FROM $table WHERE $field=? $except", $value);

		if (count($results))
		{
			$this->add_error(__('should be unique')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "exists" rule. Value should exists in the database.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_exists ($value)
	{
		$table = $this->extra;
		list($table, $field) = explode('.', $table);

		$results = DB::query("SELECT $field FROM $table WHERE $field=?", $value);

		if (!count($results))
		{
			$this->add_error(__('should be a correct value')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "before" rule. Value should be a date before
	 * the specified one.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_before ($value)
	{
		$before = $this->extra;

		if (strtotime($value) > strtotime($before))
		{
			$this->add_error(__('should be a lower date')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates an "after" rule. Value should be a date after
	 * the specified one.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_after ($value)
	{
		$before = $this->extra;

		if (strtotime($value) < strtotime($before))
		{
			$this->add_error(__('should be a higher date')->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "type" rule. Value should have an extension from the list.
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_type ($value)
	{
		$types = $this->extra;
		$types = explode(';', $types);
		$ext = pathinfo($value, PATHINFO_EXTENSION);

		if (!in_array($ext, $types))
		{
			$this->add_error(__('should be {2:|one of} the following format{2:|s}: :1', implode(', ', $types), count($types))->in('errors'));
			return false;
		}

		return true;
	}

	/**
	 * Validates a "password" rule. Value should contain the appropriate
	 * characters for the strength (easy|normal|strong).
	 * 
	 * @param string $value The input's value
	 * @return bool
	 */
	protected function validate_password ($value)
	{
		$strength = $this->extra;

		if ($strength == 'easy')
		{
			if (strlen($value) < 5)
			{
				$this->add_error(__('should be at least 5 characters')->in('errors'));
				return false;
			}
		}
		elseif ($strength == 'normal')
		{
			if (strlen($value) < 5 or !preg_match('|([A-Z])+([0-9])+|', $value))
			{
				$this->add_error(__('should be at least 5 characters, contain an upper case letter and a number')->in('errors'));
				return false;
			}
		}
		elseif ($strength == 'strong')
		{
			if (strlen($value) < 5 or !preg_match('|([A-Z])+([0-9])+|', $value) or !preg_match('|.[!,@,#,$,%,^,&,*,?,_,~,-,£,(,)]+|', $value))
			{
				$this->add_error(__('should be at least 5 characters, contain an upper case letter, a number and a symbol')->in('errors'));
				return false;
			}
		}
	}

}