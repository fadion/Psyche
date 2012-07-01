<?php
namespace Psyche\Core;
use Psyche\Core\Query;
use Psyche\Core\DB;
use Psyche\Core\Drill\Cache;

/**
 * Drill ORM
 * 
 * A rather simple, but powerful ORM implemenation.
 * It treats Model classes as database tables, providing
 * an intuitive approach to CRUD operations. In addition,
 * it handles query caching, relations and offers the same
 * expressive power of the Query Builder.
 *
 * @package Psyche\Core\Drill
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Drill extends \Psyche\Core\Drill\Query
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

			$query = Query::select()->from(static::table())->where(static::$p_key.' = '.$this->id);

			// Check first for a cached result with the built
			// query. If yes, get it from cache. Otherwise, run it.
			if (Cache::has($query))
			{
				$results = Cache::get($query);
			}
			else
			{
				$results = $query->first();
				Cache::add($query, $results);
			}

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
			// Checks if an insertion time field is specified.
			if (isset(static::$insert_time))
			{
				$time_field = static::$insert_time;

				// Checks if the insertion time field was overriden. It would mean
				// that the user chose to set it manually.
				if (!isset($this->dirty[$time_field]) or $this->dirty[$time_field] == '')
				{
					$this->dirty[$time_field] = date('Y-m-d H:i:s');
				}
			}

			Query::insert(static::table(), $this->dirty)->query();

			// Upon insertion, the insert ID is returned and the
			// model is opened for update.
			$this->id = DB::last_insert();
		}
		// Update
		else
		{
			// Checks if an update time field is specified.
			if (isset(static::$update_time))
			{
				$time_field = static::$update_time;

				if (!isset($this->dirty[$time_field]) or $this->dirty[$time_field] == '')
				{
					$this->dirty[$time_field] = date('Y-m-d H:i:s');
				}
			}

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
	 * Creates an instance of the Drill class.
	 * It's intended to be used as a simple way
	 * of generating database objects, but it
	 * doesn't offer any of the flexibilites of using
	 * Model classes.
	 */
	public static function create ($table, $id = null)
	{
		static::$table = $table;

		return new static($id);
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
	 * Finds and returns all results from a table.
	 * 
	 * @return object
	 */
	public static function find_all ()
	{
		return static::find('', true, true);
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
	public static function find ($what, $many = true, $all = false)
	{
		$query = static::select_from();

		// If the call wasn't from find_all(), add
		// the where clause.
		if (!$all)
		{
			$query = $query->where($what);
		}

		// If it is a closure, get the query from it's return value.
		// static::select_from() is passed as an instance of the
		// Query Builder with the select and from clause built.
		if (is_callable($what))
		{
			$query = call_user_func($what, static::select_from());
		}

		// Query method and type of instance are built dynamically.
		// Defaults are for a single result, triggered by find_one().
		$method = 'first';
		$instance = 'single_instance';

		if ($many)
		{
			$method = 'query';
			$instance = 'multi_instance';
		}

		// First check if the query exists in the cache. Otherwise,
		// run it.
		if (Cache::has($query.$many))
		{
			$results = Cache::get($query.$many);
		}
		else
		{
			$results = $query->$method();
			Cache::add($query.$many, $results);
		}

		return static::$instance($results);
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
		return '\\Psyche\\Models\\'.static::table();
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