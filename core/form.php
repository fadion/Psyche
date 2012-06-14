<?php
namespace FW\Core;

class Form
{

	public static function open ($request_type = 'post')
	{
		return '<form method="'.$request_type.'" enctype="multipart/form-data">';
	}

	public static function close ()
	{
		return '</form>';
	}

	public static function button ($display = 'Submit', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<button type="submit" '.$parameters.'>'.$display.'</button>';
	}

	public static function text ($name, $value = '', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<input type="text" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" '.$parameters.'>';
	}

	public static function textarea ($name, $value = '', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<textarea name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" '.$parameters.'>'.htmlspecialchars($value).'</textarea>';
	}

	public static function label ($display, $for = null, $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		if (!is_null($for))
		{
			$for = 'for="control_'.htmlspecialchars($for).'"';
		}

		return '<label '.$for.' '.$parameters.'><span>'.htmlspecialchars($display).'</span></label>';
	}

	public static function password ($name, $value = '', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<input type="password" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" '.$parameters.'>';
	}

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

	public static function checkbox_group ($name, $data, $parameters = null)
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

			$output .= '<input type="checkbox" name="'.htmlspecialchars($new_name).'" id="control_'.htmlspecialchars($id).'" value="'.htmlspecialchars($value).'" '.$checked.' '.$parameters.'>';
			$output .= '<label for="control_'.htmlspecialchars($id).'">'.htmlspecialchars($label).'</label>';
		}

		return $output;
	} 

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

	public static function file ($name, $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		return '<input type="file" name="'.htmlspecialchars($name).'" id="control_'.htmlspecialchars($name).'" '.$parameters.'>';
	}

	private static function fix_params ($parameters)
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