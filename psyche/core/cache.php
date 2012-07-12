<?php
namespace Psyche\Core;
use Psyche\Core\Cache\File;

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
	 * Returns an instance of a cache driver.
	 * 
	 * @param string $driver
	 * @return object
	 */
	public static function open ($driver = null)
	{
		if (!isset($driver))
		{
			$driver = config('cache:driver');
		}

		$method = $driver.'_driver';

		if (method_exists(__CLASS__, $method))
		{
			return static::$method();
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

}