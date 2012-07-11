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
		if (!isset($driver))
		{
			$driver = config('cache:driver');
		}

		switch ($driver)
		{
			case 'file':
				return new \Psyche\Core\Cache\File(config('cache:file path'));
				break;
			default:
				throw new \Exception(sprintf("The cache driver %s isn't supported.", $driver));
		}
	}

}