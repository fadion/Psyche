<?php
namespace Psyche\Core;
use \PDO;

/**
 * Database
 * 
 * A simple layer to PDO. Database driver and configuration are set in
 * config/database.php as templates.
 *
 * @package Psyche\Core\Db
 * @author Fadion Dashi
 * @version 1.0.2
 * @since 1.0
 */
class Db {


	/**
	 * @var object PDO Object
	 */
	public static $pdo;

	/**
	 * @var array Template file cache
	 */
	protected static $templates;

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
	 * Connects to the database via PDO using one of the defined templates.
	 * 
	 * @param string $template Configuration template
	 * 
	 * @return void
	 */
	public static function connect ($template = null)
	{
		static::read_templates();

		// If a template is defined as parameter, check if it exists in the
		// configuration cache. Otherwise gets the first template.
		if (isset($template) and isset(static::$templates[$template]))
		{
			$template = static::$templates[$template];
		}
		else
		{
			$template = array_values(static::$templates);
			$template = $template[0];
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
	 * Makes the SQL query. Any parameter after the SQL code is treated
	 * as a parameter to be bound.
	 * 
	 * It can be a single array: DB::query("UPDATE table SET a=?, b=?", array($a, $b));
	 * or multi parameters: DB::query("UPDATE table SET a=?, b=?", $a, $b);
	 * 
	 * @param string $sql The raw SQL
	 * 
	 * @return int|object
	 */
	public static function query ($sql)
	{
		try
		{
			static::$results = static::$pdo->prepare($sql);

			// Sets the return as object.
			static::$results->setFetchMode(PDO::FETCH_OBJ);

			// Received the dynamic args and unsets the first (SQL query)
			$args = func_get_args();
			unset($args[0]);

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
	 * Makes a query where only the first row is required.
	 * 
	 * @param string $sql
	 * 
	 * @return int|object
	 */
	public static function first ($sql)
	{
		static::$type = 'first';
		return static::query($sql);
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
	 * 
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

	/**
	 * Reads the templates configuration file.
	 * 
	 * @return void
	 */
	protected static function read_templates ()
	{
		if (isset(static::$templates))
		{
			return;
		}

		$file = 'config/database.php';

		if (!file_exists($file))
		{
			throw new \Exception('Database configuration not found');
		}

		static::$templates = include($file);
	}

}