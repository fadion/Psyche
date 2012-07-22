<?php
namespace Psyche\Core;
use Psyche\Core\Cache\File,
	Psyche\Core\Cache\Database,
	Psyche\Core\Cache\APC,
	Psyche\Core\Cache\XCache,
	Psyche\Core\Cache\Memcached;

/**
 * Cache Factory
 * 
 * Factory for initializing cache drivers.
 *
 * @package Psyche\Core\Cache
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Cache
{

	/**
	 * Returns an instance of a cache driver.
	 * 
	 * @param string $driver
	 * @return object
	 */
	public static function open ($driver = null, $parameters = null)
	{
		// For easy of use, the first argument
		// can be an array of parameters instead
		// of the actual driver.
		if (is_array($driver))
		{
			$parameters = $driver;
			$driver = null;
		}
		
		if (!isset($driver))
		{
			$driver = config('cache:driver');
		}

		$method = $driver.'_driver';

		if (method_exists(__CLASS__, $method))
		{
			return static::$method($parameters);
		}
		else
		{
			throw new \Exception(sprintf("The cache driver %s isn't supported.", $driver));
		}
	}

	/**
	 * Initializes the File cache driver.
	 * 
	 * @return Cache\File
	 */
	protected static function file_driver ()
	{
		return new File(config('cache:file path'));
	}

	/**
	 * Initializes the Database cache driver.
	 * 
	 * @return Cache\Database
	 */
	protected static function database_driver ()
	{
		return new Database();
	}

	/**
	 * Initializes the APC cache driver.
	 * 
	 * @return Cache\APC
	 */
	protected static function apc_driver ($parameters)
	{
		return new APC($parameters);
	}

	/**
	 * Initializes the XCache cache driver.
	 * 
	 * @return Cache\XCache
	 */
	protected static function xcache_driver ($parameters)
	{
		return new XCache($parameters);
	}

	/**
	 * Initializes the Memcached cache driver.
	 * 
	 * @return Cache\Memcached
	 */
	protected static function memcached_driver ()
	{
		return new Memcached();
	}

}