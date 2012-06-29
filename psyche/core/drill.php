<?php
namespace Psyche\Core;
use Psyche\Core\Query;
use Psyche\Core\DB;

/**
 * Drill ORM
 * 
 * A very simple, but rather powerful ORM implemenation.
 * It treats Model classes as database tables, providing
 * an intuitive approach to CRUD operations. In addition,
 * it handles query caching, relations and interfaces with
 * most of the funcionality of the Query Builder.
 *
 * @package Psyche\Core\Drill
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Drill
{

	/**
	 * @var string The table name. When not set, it will be retreived automatically.
	 */
	protected static $table;

	/**
	 * @var string The table's primary key.
	 */
	protected static $p_key = 'id';

	/**
	 * @var array Table columns.
	 */
	protected $vars = array();

	/**
	 * @var array The table's modified columns.
	 */
	protected $dirty = array();

	/**
	 * @var bool Determines if it's a new row (insert) or an existing one (update).
	 */
	protected $is_new = false;

	/**
	 * @var int The row ID.
	 */
	protected $id;

	/**
	 * Constructor. Sets if it's a new row or not, takes the ID
	 * and hydrates column data.
	 * 
	 * @param int $id
	 */
	public function __construct ($id = null)
	{
		// When ID is set, it's an update request.
		// Otherwise it's an insert.
		if (isset($id))
		{
			$this->id = $id;
			$this->is_new = false;

			$results = Query::select()->from(static::table())->where(static::$p_key.' = '.$this->id)->first();
			$this->hydrate($results);
		}
		else
		{
			$this->is_new = true;
		}
	}

	/**
	 * Sets column values.
	 * 
	 * @param string $name
	 * @param string $value
	 * 
	 * @return object
	 */
	public function set ($name, $value)
	{
		$this->vars[$name] = $value;
		$this->dirty[$name] = $value;

		return $this;
	}

	/**
	 * Gets column values.
	 * 
	 * @param string $name
	 * 
	 * @return string|bool
	 */
	public function get ($name)
	{
		if (isset($this->vars[$name]))
		{
			return $this->vars[$name];
		}

		return false;
	}

	/**
	 * Sets column values.
	 * 
	 * @param string $name
	 * @param string $value
	 * 
	 * @return object
	 */
	public function __set ($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * Gets column values.
	 * 
	 * @param string $name
	 * 
	 * @return string|bool
	 */
	public function __get ($name)
	{
		return $this->get($name);
	}

	/**
	 * Fills column values with data.
	 * 
	 * @param array|object $data
	 * 
	 * @return void
	 */
	public function hydrate ($data)
	{
		if (is_object($data))
		{
			$data = (array) $data;
		}

		$this->vars = $data;
	}

	/**
	 * Empties column and dirty data.
	 * 
	 * @return void
	 */
	public function clean ()
	{
		$this->vars = array();
		$this->dirty = array();
	}

	/**
	 * Checks if a column is modified or not.
	 * 
	 * @param string $name
	 * 
	 * @return bool
	 */
	public function is_dirty ($name)
	{
		return isset($this->dirty[$name]);
	}

	/**
	 * Marks a column or all as modified.
	 * 
	 * @param string $name
	 * 
	 * @return void
	 */
	public function make_dirty ($name = null)
	{
		if (isset($name) and isset($this->vars[$name]))
		{
			$this->dirty[$name] = $this->vars[$name];
		}
		else
		{
			$this->dirty = $this->vars;
		}
	}

	/**
	 * Saves the model in the database, depending
	 * on the request type (insert or delete).
	 * 
	 * @return void
	 */
	public function save ()
	{
		// When no column was modified, do nothing.
		if (!count($this->dirty))
		{
			return true;
		}

		// Insert
		if ($this->is_new)
		{
			Query::insert(static::table(), $this->dirty)->query();

			// Upon insertion, the insert ID is returned and the
			// model is opened for update.
			$this->id = DB::last_insert();
		}
		// Update
		else
		{
			Query::update(static::table(), $this->dirty)->where(static::$p_key.' = '.$this->id)->query();
		}

		$this->is_new = false;
		$this->dirty = array();
	}

	/**
	 * Deletes a model.
	 * 
	 * @return void
	 */
	public function delete ()
	{
		Query::delete(static::table())->where(static::$p_key.' = '.$this->id)->query();
		$this->clean();
	}

	/**
	 * Finds and returns one result.
	 * 
	 * @param string $what
	 * 
	 * @return object
	 */
	public static function find_one ($what)
	{
		return static::find($what, false);
	}

	/**
	 * Finds and returns one or more results.
	 * 
	 * @param string $what
	 * 
	 * @return object
	 */
	public static function find_many ($what)
	{
		return static::find($what);
	}

	/**
	 * Finds results from the database based on the conditions.
	 * It functions as a WHERE clause.
	 * 
	 * @param string $what
	 * @param bool $many Find one or many
	 * 
	 * @return void
	 */
	public static function find ($what, $many = true)
	{
		$results = static::select_from()->where($what);

		if ($many)
		{
			$results = $results->query();
			return static::multi_instance($results);
		}
		else
		{
			$results = $results->first();
			return static::single_instance($results);
		}
	}

	/**
	 * Builds the SELECT FROM part of the query.
	 * 
	 * @return string
	 */
	protected static function select_from ()
	{
		return Query::select(static::$p_key)->from(static::table());
	}

	/**
	 * Creates multiple object instances in case of find_many.
	 * 
	 * @param object $results Query object
	 * 
	 * @return array
	 */
	protected static function multi_instance ($results)
	{
		$objects = array();

		foreach ($results as $row)
		{
			$id = static::$p_key;
			$id = $row->$id;

			// Creates a new model instance by passing
			// the row ID.
			$class_name = static::class_name();
			$objects[] = new $class_name($id);
		}

		return $objects;
	}

	/**
	 * Creates a single object instance in case of find_one.
	 * 
	 * @param object $results Query object
	 * 
	 * @return object
	 */
	protected static function single_instance ($results)
	{
		$id = static::$p_key;
		$id = $results->$id;

		$class_name = static::class_name();
		return new $class_name($id);
	}

	/**
	 * Makes the model class name.
	 * 
	 * @return string
	 */
	protected static function class_name ()
	{
		return '\Psyche\Models\\'.static::table();
	}

	/**
	 * Makes the table name. The model can override the name
	 * via a static property in it's class definition. Otherwise
	 * the name is computed automatically.
	 */
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