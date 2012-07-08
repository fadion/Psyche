<?php
namespace Psyche\Core;
use Psyche\Core\DB,
	Psyche\Core\Validator,
	Psyche\Core\Drill\Cache,
	Psyche\Core\Drill\Relation,
	Psyche\Core\Drill\Callback;

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
	 * @var string Foreign key suffix.
	 */
	protected static $f_key_suffix = '_id';

	/**
	 * @var bool Cache status (on|off).
	 */
	protected static $cache_status = false;

	/**
	 * @var array Validation rules.
	 */
	protected static $rules = null;

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
	 * @var bool Sets if the raw should be allowed to be saved or not.
	 */
	protected $read_only = false;

	/**
	 * @var false|array Holds the validation errors.
	 */
	protected $errors = false;

	/**
	 * Constructor. Sets if it's a new row or not, takes the ID
	 * and hydrates column data.
	 * 
	 * @param int $id
	 */
	public function __construct ($id = null)
	{
		Callback::observe('after_create', $this);

		// When ID is set, it's an update request.
		// Otherwise it's an insert.
		if (isset($id))
		{
			$this->id = $id;
			$this->is_new = false;

			$query = Query::select()->from(static::table())->where(static::$p_key.' = '.$this->id);

			// If cache is active, check for a cached result set.
			// Otherwise, run the query as normal.
			if (static::$cache_status and Cache::has($query))
			{
				$results = Cache::get($query);
			}
			else
			{
				$results = $query->first();
				Cache::add($query, $results);
			}

			$this->hydrate($results);

			Callback::observe('after_hydrate', $this);
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
	 * @return Drill
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
	 */
	public function __set ($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * Gets column values.
	 * 
	 * @param string $name
	 * @return string|bool
	 */
	public function __get ($name)
	{
		return $this->get($name);
	}

	public function __isset ($name)
	{
		return isset($this->vars[$name]);
	}

	/**
	 * Fills column values with data.
	 * 
	 * @param array|object $data
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
	 * Activates read-only for the current row(s).
	 * 
	 * @return void
	 */
	public function read_only ()
	{
		$this->read_only = true;
	}

	/**
	 * Saves the model in the database, depending
	 * on the request type (insert or delete).
	 * 
	 * @return bool|void
	 */
	public function save ()
	{
		Callback::observe('before_save', $this);

		// When no column was modified, do nothing.
		if (!count($this->dirty))
		{
			return true;
		}

		// If any rule is set in the model, run the Validator.
		if (static::$rules)
		{
			Callback::observe('before_validation', $this);

			// The fields to be checked are the dirty values. Rules
			// are retrieved from the child model.
			$validator = Validator::run($this->dirty, static::$rules);

			Callback::observe('after_validation', $this);

			if ($validator->failed())
			{
				$this->errors = $validator->errors();
				return false;
			}
		}

		// Insert
		if ($this->is_new)
		{
			Callback::observe('before_insert', $this);

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

			Callback::observe('after_insert', $this);

			// Upon insertion, the insert ID is returned and the
			// model is opened for update.
			$this->id = DB::last_insert();
		}
		// Update
		else
		{
			Callback::observe('before_update', $this);

			if ($this->read_only)
			{
				throw new \Exception("Can't save a read-only dataset.");
			}

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

			Callback::observe('after_update', $this);
		}

		$this->is_new = false;
		$this->dirty = array();

		Callback::observe('after_save', $this);
	}

	/**
	 * Deletes a model.
	 * 
	 * @return void
	 */
	public function delete ()
	{
		Callback::observe('before_delete', $this);

		Query::delete(static::table())->where(static::$p_key.' = '.$this->id)->query();
		$this->clean();

		Callback::observe('after_delete', $this);
	}

	/**
	 * Makes a one to one relationship.
	 * 
	 * @param string $table
	 * @param string $f_key
	 * @return Model
	 */
	public function has_one ($table, $f_key = null)
	{
		return Relation::has_one_or_many($table, $f_key);
	}

	/**
	 * Makes a one to many relationship.
	 * 
	 * @param string $table
	 * @param string $f_key
	 * @return Model
	 */
	public function has_many ($table, $f_key = null)
	{
		return Relation::has_one_or_many($table, $f_key, true);
	}

	/**
	 * Makes a relationship where the foreign key is in the actual model.
	 * 
	 * @param string $table
	 * @param string $f_key
	 * @return Model
	 */
	protected function belongs_to ($table, $f_key = null)
	{
		return Relation::belongs_to($table, $f_key);
	}

	/**
	 * Builds the foreign key.
	 * 
	 * @param string $table
	 * @return string
	 */
	protected function make_foreign_key ($table = null)
	{
		$f_key = $table;

		if (!isset($table))
		{
			$f_key = static::table();
		}

		return $f_key.static::$f_key_suffix;
	}

	/**
	 * Returns error messages or false if no errors have
	 * been triggered.
	 * 
	 * @param string $field Returns a single error message for a specific field
	 * @return bool|string|array
	 */
	public function errors ($field = null)
	{
		if (!count($this->errors))
		{
			return false;
		}

		if (isset($field) and isset($this->errors[ucfirst($field)]))
		{
			return $this->errors[ucfirst($field)];
		}

		return $this->errors;
	}

	/**
	 * Creates an instance of the Drill class.
	 * It's intended to be used as a simple way
	 * of generating database objects, but it
	 * doesn't offer any of the flexibilites of using
	 * Model classes.
	 * 
	 * @param string $table
	 * @param int $id
	 * @return Drill
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
	 * @return void
	 */
	public static function find ($what, $many = true, $all = false)
	{
		$query = static::select_from();

		// If the call wasn't from find_all(), add
		// the where clause.
		if (!$all)
		{
			// If the WHERE part is a single, numeric value, it means
			// it's being called with an ID. Otherwise, it's a normal
			// where clause.
			if (is_numeric($what))
			{
				$query = $query->where(static::$p_key.' = '.$what);
			}
			else
			{
				$query = $query->where($what);
			}
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

		// Multiple results will be returned only if $many is true
		// and the value is not numeric (ID).
		if ($many and !is_numeric($what))
		{
			$method = 'query';
			$instance = 'multi_instance';
		}

		if (static::$cache_status and Cache::has($query.$many))
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
	 * Enables or disables the cache.
	 * 
	 * @param bool $status
	 * @return void|bool
	 */
	public static function cache ($status = null)
	{
		if (isset($status))
		{
			static::$cache_status = (bool) $status;
		}
		else
		{
			return static::$cache_status;
		}
	}

	/**
	 * Makes the model class name.
	 * 
	 * @param string $name
	 * @return string
	 */
	protected static function class_name ($name = null)
	{
		if (!isset($name))
		{
			$name = static::table();
		}

		return '\\Psyche\\Models\\'.$name;
	}

	/**
	 * Makes the table name. The model can override the name
	 * via a static property in it's class definition. Otherwise
	 * the name is computed automatically.
	 * 
	 * @return string
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