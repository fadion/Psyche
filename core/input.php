<?php
namespace FW\Core;
use FW\Core\DB;

class Input
{

	public static function val ($val, $default = null, $id = null)
	{
		$return = '';

		if ($val)
		{
			$return = $val;
		}
		else
		{
			if (is_null($id))
			{
				if (!is_null($default))
				{
					$return = $default;
				}
			}
			else
			{
				if (is_numeric($id) and $id > 0)
				{
					list($table, $field) = explode('.', $default);
					
					$results = DB::query("SELECT $field FROM $table WHERE id=$id");
					$values = $results->results();

					$return = $values[0][$field];
				}
			}
		}

		return htmlspecialchars($return);
	}

	public static function select ($val, $default, $default2 = null, $id = null)
	{
		if ($val)
		{
			if (is_null($id))
			{
				if ($val == $default)
				{
					return 'selected="selected"';
				}
			}
			else
			{
				if ($val == $default2)
				{
					return 'selected="selected"';
				}
			}
		} else
		{
			if (!is_null($id))
			{
				if (is_numeric($id) and $id > 0)
				{
					list($table, $field) = explode('.', $default);
					
					$results = DB::query("SELECT $field FROM $table WHERE id=$id");
					$values = $results->results();
					$value = $values[0][$field];

					if ($default2 == $value)
					{
						return 'selected="selected"';
					}
				}
			}
		}
	}

}