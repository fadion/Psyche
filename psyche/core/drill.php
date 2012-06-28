<?php
namespace Psyche\Core;
use Psyche\Core\Query;
use Psyche\Core\DB;

class Drill
{

	protected static $table;

	protected $vars = array();
	protected $operation = 'insert';
	protected $condition;

	public function __construct ($where = null)
	{
		if (!isset(static::$table))
		{
			static::$table = strtolower(substr(get_called_class(), strrpos(get_called_class(), '\\') + 1));
		}

		if (!is_null($where))
		{
			$this->operation = 'update';

			if (is_numeric($where))
			{
				$this->condition = "id = $where";
			}
			else
			{
				$this->condition = $where;
			}
		}
	}

	public function __set ($name, $value)
	{
		$this->vars[$name] = $value;
	}

	public function __get ($name)
	{
		$results = Query::select($name)->from(static::$table)->where($this->condition)->first();

		return $results->$name;
	}

	public function __callStatic ($method, $arguments)
	{
		$table = static::table();
		$method = 'where_'.substr($method, strpos($method, '_') + 1);

		return Query::select()->from($table)->$method($arguments);
	}

	public function save ()
	{
		if ($this->operation == 'insert')
		{
			Query::insert(static::$table, $this->vars)->query();
			$this->vars = array();
			$this->condition = 'id = '.DB::last_insert();
			$this->operation = 'update';
		}
		else
		{
			Query::update(static::$table, $this->vars)->where($this->condition)->query();
		}
	}

	public function trash ()
	{
		Query::delete(static::$table)->id($this->id)->query();
	}

	public function trash_all ()
	{
		Query::delete(static::$table)->query();
	}

	public static function select ($fields = null)
	{
		$table = static::table();

		return Query::select($fields)->from($table);
	}

	public static function insert ($fields)
	{
		$table = static::table();

		return Query::insert($table, $fields);
	}

	public static function update ($fields)
	{
		$table = static::table();

		return Query::update($table, $fields);
	}

	public static function delete ($id)
	{
		$table = static::table();

		return Query::delete($table)->id($id);
	}

	public static function find ($what)
	{
		if (is_numeric($what))
		{
			return static::select()->id(10);
		}
		else
		{
			return static::select()->where($what);
		}
	}

	protected static function table ()
	{
		$table = static::$table;

		if (!isset(static::$table))
		{
			$table = strtolower(substr(get_called_class(), strrpos(get_called_class(), '\\') + 1));
		}

		return $table;
	}

}