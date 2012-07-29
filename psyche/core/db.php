<?php
namespace Psyche\Core;
use PDO,
	Psyche\Core\Event;

/**
 * Database
 * 
 * A simple layer to PDO. Database driver and configuration are set in
 * config/database.php as templates.
 *
 * @package Psyche\Core\Db
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Db {


	/**
	 * @var object PDO Object
	 */
	protected static $pdo;

	/**
	 * @var object PDO statement object
	 */
	protected static $results;

	/**
	 * @var object PDO fetched results
	 */
	protected static $fetch;

	/**
	 * @var string Return type: all|first
	 */
	protected static $type = 'all';

	/**
	 * Returns the database connection.
	 * 
	 * @return object
	 */
	public static function connection ()
	{
		return static::$pdo;
	}

	/**
	 * Connects to the database via PDO using one of the defined templates.
	 * 
	 * @param string $template Configuration template
	 * @return void
	 */
	public static function connect ($template = null)
	{
		$templates = config('database:');

		// If a template is defined as parameter.
		if (isset($template))
		{	
			// If the template exists in the database config, read it.
			// Otherwise throw an exception.
			if (isset($templates['templates'][$template]))
			{
				$template = $templates['templates'][$template];
			}
			else
			{
				throw new \Exception(sprintf("Template %s doesn't exist in the database configuration.", $template));
			}
		}
		else
		{
			$template = $templates['templates'][$templates['use']];
		}

		try
		{
			static::$pdo = new PDO($template['type'].':host='.$template['host'].';dbname='.$template['name'], $template['user'], $template['password']);
			static::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
		}
		catch (\PDOException $e)
		{
			throw new \Exception('Database: '.$e->getMessage());
		}
	}

	/**
	 * Makes a query where all the matched rows are returned.
	 * 
	 * @return int|object
	 */
	public static function query ()
	{
		static::$type = 'many';
		return static::make_query(func_get_args());
	}

	/**
	 * Makes a query where only the first row is required.
	 * 
	 * @return int|object
	 */
	public static function first ()
	{
		static::$type = 'first';
		return static::make_query(func_get_args());
	}

	/**
	 * Makes the SQL query. Any parameter after the SQL code is treated
	 * as a parameter to be bound.
	 * 
	 * It can be a single array: DB::query("UPDATE table SET a=?, b=?", array($a, $b));
	 * or multi parameters: DB::query("UPDATE table SET a=?, b=?", $a, $b);
	 * 
	 * @return int|object
	 */
	protected static function make_query ()
	{
		try
		{
			// Listen for Gizmo to count all made queries.
			Event::on('psyche gizmo query', function()
			{
				return 1;
			});

			// Arguments are passed from query() or first() as an array.
			$args = func_get_args();
			$args = $args[0];

			// SQL code is the first argument. Anything else are considered
			// bount parameters.
			$sql = $args[0];
			unset($args[0]);

			static::$results = static::$pdo->prepare($sql);

			// Sets the return as object.
			static::$results->setFetchMode(PDO::FETCH_OBJ);

			// If it's a single array, run it with execute().
			// Otherwise bind every parameter.
			if (is_array($args[1]) and count($args[1]))
			{
				static::$results->execute($args[1]);
			}
			else
			{
				foreach ($args as $key => $val)
				{
					static::$results->bindValue($key, $val);
				}

				static::$results->execute();
			}

			// SELECT queries return a fetched object.
			// UPDATE or INSERT return the affected rows.
			if (stripos($sql, 'select') === 0)
			{
				if (static::$type == 'first')
				{
					return static::$fetch = static::$results->fetch();
				}
				else
				{
					return static::$fetch = static::$results->fetchAll();
				}
			}
			elseif (stripos($sql, 'insert') === 0 or stripos($sql, 'update') === 0)
			{
				return static::$results->rowCount();
			}
			else
			{
				return static::$fetch;
			}
		}
		catch (\PDOException $e)
		{
			throw new \Exception('Database: '.$e->getMessage());
		}
	}

	/**
	 * Counts number of rows. For select queries and MySQL, PDO doesn't
	 * return selected rows via rowCount(), so a count() on the object is used.
	 * For insert and update, rowCount() is accurate.
	 * 
	 * @return int
	 */
	public static function count ()
	{
		if (is_object(static::$fetch))
		{
			return count(static::$fetch);
		}
		else
		{
			return static::$results->rowCount();
		}
	}

	/**
	 * Quotes (if necessary) and escapes strings for safe using in a
	 * database. Binding is better alternative in raw queries, but this
	 * method is used extensively in the Query Builder class.
	 * 
	 * @param string $string String to be quoted
	 * @return string
	 */
	public static function quote ($string)
	{
		return static::$pdo->quote($string);
	}

	/**
	 * Returns the ID of the last inserted row.
	 * 
	 * @return int
	 */
	public static function last_insert ()
	{
		return static::$pdo->lastInsertId();
	}

	/**
	 * Returns all the table columns with the
	 * associated information.
	 * 
	 * @param strint $table
	 * @return array
	 */
	public static function columns ($table)
	{
		try
		{
			static::$results = static::$pdo->prepare("SHOW COLUMNS FROM $table");
			static::$results->execute();

			return static::$results->fetchAll();
		}
		catch (\PDOException $e)
		{
			throw new \Exception('Database: '.$e->getMessage());
		}        
	}

}