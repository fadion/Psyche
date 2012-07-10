<?php
namespace Psyche\Core;

/**
 * Cache Factory
 * 
 * A simple factory for initializing cache drivers.
 *
 * @package Psyche\Core\Cache
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Cache
{

	/**
	 * @var array $config Data from the cache config file.
	 */
	protected static $config;

	/**
	 * Returns an instance of a cache driver.
	 * 
	 * @param string $driver
	 * @return object
	 */
	public static function open ($driver = null)
	{
		$config = static::read_config();

		if (!isset($driver))
		{
			$driver = $config['driver'];
		}

		switch ($driver)
		{
			case 'file':
				return new \Psyche\Core\Cache\File($config['file path']);
				break;
			default:
				throw new \Exception(sprintf("The cache driver %s isn't supported.", $driver));
		}
	}

	/**
	 * Reads the cache config file.
	 * 
	 * @return string
	 */
	protected static function read_config ()
	{
		$file = 'config/cache.php';

		if (!file_exists($file))
		{
			throw new \Exception("Cache config file doesn't exist.");
		}

		// If the config data is populated, there's no need
		// to include the file again.
		if (!isset(static::$config))
		{
			static::$config = require_once $file;
		}

		return static::$config;
	}

}