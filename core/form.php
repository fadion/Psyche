<?php
namespace FW\Core;

/**
 * Form generation helper
 * 
 * Generates html output for form elements. It makes writing forms
 * easier with the automatic ID generation and label linking.
 *
 * @package FW\Core\Form
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Form
{

	/**
	 * Opens the form.
	 * 
	 * @param string $request_type
	 * @param string $action
	 * 
	 * @return string
	 */
	public static function open ($request_type = 'post', $action = '')
	{
		return '<form method="'.$request_type.'" enctype="multipart/form-data" action="'.$action.'">';
	}

	/**
	 * Closes the form.
	 * 
	 * @return string
	 */
	public static function close ()
	{
		return '</form>';
	}

	/**
	 * Creates a submit button.
	 * 
	 * @param string $display Button label
	 * @param srray $parameters List of extra parameters
	 * 
	 * @return string
	 */
	public static function button ($display = 'Submit', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<button type="submit" '.$parameters.'>'.$display.'</button>';
	}

	/**
	 * Creates an type text input.
	 * 
	 * @param string $name Name of the input
	 * @param string $value Value of the input
	 * @param array $parameters List of extra parameters
	 * 
	 * @return string
	 */
	public static function text ($name, $value = '', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<input type="text" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" '.$parameters.'>';
	}

	/**
	 * Creates textarea.
	 * 
	 * @param string $name
	 * @param string $value
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public static function textarea ($name, $value = '', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<textarea name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" '.$parameters.'>'.htmlspecialchars($value).'</textarea>';
	}

	/**
	 * Creates a label.
	 * 
	 * @param string $display Label's text
	 * @param string $for Element to be linked with
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public static function label ($display, $for = null, $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		if (!is_null($for))
		{
			$for = 'for="control_'.htmlspecialchars($for).'"';
		}

		return '<label '.$for.' '.$parameters.'><span>'.htmlspecialchars($display).'</span></label>';
	}

	/**
	 * Creates a type password input.
	 * 
	 * @param string $name
	 * @param string $value
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public static function password ($name, $value = '', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<input type="password" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" '.$parameters.'>';
	}

	/**
	 * Creates a checkbox.
	 * 
	 * @param string $name
	 * @param string $label Label's text that will automatically be linked with
	 * @param string|bool $checked State of the checkbox
	 * @param array $parameters
	 * 
	 * @return string 
	 */
	public static function checkbox ($name, $label = null, $checked = null, $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		if ($checked)
		{
			$checked = 'checked="checked"';
		}

		$output = '<input type="checkbox" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" '.$checked.' '.$parameters.'>';

		if (!is_null($label))
		{
			$output .= '<label for="control_'.htmlspecialchars($name).'">'.htmlspecialchars($label).'</label>';
		}

		return $output;
	}

	/**
	 * Creates a group of checkboxes from a simple array data source.
	 * 
	 * @param string $name
	 * @param array $data The data source with the checkbox values
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public static function checkbox_group ($name, $data, $parameters = null)
	{
		if (!is_array($data))
		{
			return false;
		}

		$output = '';
		$parameters = static::fix_params($parameters);

		// For any of the source data elements, create a different checkbox.
		foreach ($data as $key => $val)
		{
			// If the key is set, use both the key as "value" and value as label.
			// Otherwise, value will be used for both. 
			if (!is_int($key))
			{
				$label = $val;
				$value = $key;
			}
			else
			{
				$value = $val;
				$label = &$value;
			}

			$checked = '';

			// A pipe is a special character that specified the state of the
			// checkbox. Ex: "some name|1" means that the checkbox will be checked.
			if ((bool) strpos($value, '|'))
			{
				list($value, $checked) = explode('|', $value);
			}

			if ($checked)
			{
				$checked = 'checked="checked"';
			}

			// A "[]" is prepended to the name to make it an array.
			// ID is prepended with a random value.
			$new_name = $name.'[]';
			$id = $name.rand(11111, 99991);

			$output .= '<input type="checkbox" name="'.htmlspecialchars($new_name).'" id="control_'.htmlspecialchars($id).'" value="'.htmlspecialchars($value).'" '.$checked.' '.$parameters.'>';
			$output .= '<label for="control_'.htmlspecialchars($id).'">'.htmlspecialchars($label).'</label>';
		}

		return $output;
	} 

	/**
	 * Creates a radio input.
	 * 
	 * @param string $name
	 * @param string $label Label's text that will automatically be linked with
	 * @param string|bool $checked State of the radio
	 * @param array $parameters
	 * 
	 * @return string 
	 */
	public static function radio ($name, $label = null, $checked = null, $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		if ($checked)
		{
			$checked = 'checked="checked"';
		}

		$output = '<input type="radio" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" '.$checked.' '.$parameters.'>';

		if (!is_null($label))
		{
			$output .= $output .= '<label for="control_'.htmlspecialchars($name).'">'.htmlspecialchars($label).'</label>';
		}

		return $output;
	}

	/**
	 * Creates a group of radio inputs from a simple array data source.
	 * Logic is pretty much the same as with checkbox groups.
	 * 
	 * @param string $name
	 * @param array $data The data source with the checkbox values
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public static function radio_group ($name, $data, $parameters = null)
	{
		if (!is_array($data))
		{
			return false;
		}

		$output = '';
		$parameters = static::fix_params($parameters);

		foreach ($data as $key => $val)
		{
			if (!is_int($key))
			{
				$label = $val;
				$value = $key;
			}
			else
			{
				$value = $val;
				$label = &$value;
			}

			$checked = '';

			if ((bool) strpos($value, '|'))
			{
				list($value, $checked) = explode('|', $value);
			}

			if ($checked)
			{
				$checked = 'checked="checked"';
			}

			$new_name = $name.'[]';
			$id = $name.rand(11111, 99991);

			$output .= '<input type="radio" name="'.htmlspecialchars($new_name).'" id="control_'.htmlspecialchars($id).'" value="'.htmlspecialchars($value).'" '.$checked.' '.$parameters.'>';
			$output .= '<label for="control_'.htmlspecialchars($id).'">'.htmlspecialchars($label).'</label>';
		}

		return $output;
	}

	/**
	 * Creates a select box from a simple array data source.
	 * 
	 * @param string $name
	 * @param array $data The data source with the <option> values
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public static function select ($name, $data, $parameters = null)
	{
		if (!is_array($data))
		{
			return false;
		}

		$output = '';
		$parameters = static::fix_params($parameters);

		$output .= '<select name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" '.$parameters.'>';
		foreach ($data as $key => $val)
		{
			$selected = '';

			if (!is_int($key))
			{
				$label = $val;
				$value = $key;
			} else
			{
				$value = $val;
				$label = &$value;
			}

			if ((bool) strpos($value, '|'))
			{
				list($value, $selected) = explode('|', $value);
			}

			if ($selected)
			{
				$selected = 'selected="selected"';
			}

			$output .= '<option value="'.htmlspecialchars($value).'" '.$selected.'>'.htmlspecialchars($label).'</option>';
		}
		$output .= '</select>';

		return $output;
	}

	/**
	 * Creates a type file input.
	 * 
	 * @param string $name
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public static function file ($name, $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<input type="file" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" '.$parameters.'>';
	}

	/**
	 * Fixes extra parameters. Basically, it allows writing html attributes
	 * without quotes and adds them automatically.
	 * Ex: Form::text('email', '', array('class=email', 'size=20'));
	 * 
	 * @param array $parameters
	 * 
	 * @return string
	 */
	protected static function fix_params ($parameters)
	{
		if (!is_null($parameters))
		{
			foreach ($parameters as &$param)
			{
				list($name, $value) = explode('=', $param);
				$value = htmlspecialchars(trim($value, '"'));
				$param = $name . '="' . $value . '"';
			}

			$parameters = implode(' ', $parameters);

			return $parameters;
		}
	}

}