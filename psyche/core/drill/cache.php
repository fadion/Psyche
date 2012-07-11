<?php
namespace Psyche\Core\Drill;

/**
 * Drill ORM Caching
 * 
 * A very simple memory caching class for storing returned
 * objects from the database. As it doesn't use any
 * external, permanent storage solution, queries will
 * obviously be available during a single instance
 * of the application. Drill uses it to store and
 * retrieve results for select queries and is disabled
 * by default.
 *
 * @package Psyche\Core\Drill\Cache
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Cache
{

	/**
	 * @var array Stores the cached results.
	 */
	public static $cache = array();

	/**
	 * Checks if a key exists.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public static function has ($key)
	{
		return (isset(static::$cache[md5($key)])) ? true : false;
	}

	/**
	 * Returns a query object.
	 * 
	 * @param string $key
	 * @return void|Query
	 */
	public static function get ($key)
	{
		if (static::has($key))
		{
			return static::$cache[md5($key)];
		}
	}

	/**
	 * Adds a key in the cache. To make it safe for
	 * storage as an array key, it's hashed.
	 * 
	 * @param string $key
	 * @param object $value
	 * @return void
	 */
	public static function add ($key, $value)
	{
		static::$cache[md5($key)] = $value;
	}

	/**
	 * Empties the cache.
	 * 
	 * @return void
	 */
	public static function clear ()
	{
		static::$cache = array();
	}
	
}