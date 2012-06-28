<?php
namespace Psyche\Core;
use Psyche\Core\Query;
use Psyche\Core\DB;

class Drill
{

	protected $vars = array();
	protected $is_new = false;
	protected $condition;

	public function __construct ($where = null)
	{
		if (isset($where))
		{
			$this->condition = $where;
			if (is_numeric($where))
			{
				$this->condition = "id = $where";
			}
		}
		else
		{
			$is_new = true;
		}
	}

	public function __set ($name, $value)
	{
		$this->vars[$name] = $value;
	}

	public function __get ($name)
	{
		$results = Query::select($name)->from(static::table())->where($this->condition)->first();
		return $results->$name;
	}

	public static function find ($what)
	{

	}

	protected static function table ()
	{
		if (isset(static::$table))
		{
			return static::$table;
		}
		else
		{
			return strtolower(substr(get_called_class(), strrpos(get_called_class(), '\\') + 1));
		}
	}

}