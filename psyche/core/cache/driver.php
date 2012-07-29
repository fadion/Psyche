<?php
namespace Psyche\Core\Cache;

/**
 * Cache Driver
 *
 * @package Psyche\Core\Cache\Driver
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
abstract class Driver
{

	/**
	 * @var bool Whether to auto-serialize data or not.
	 */
	protected $serialize = false;

	/**
	 * @var string Redis template
	 */
	protected $template = null;

	/**
	 * @var string Prefix that's added to cache keys.
	 */
	protected $prefix;

	public function __construct ($parameters)
	{
		// Auto-serialization is specified as an array:
		// array('serialize' => true)
		if (isset($parameters['serialize']))
		{
			$this->serialize = $parameters['serialize'];
		}

		// The redis active template can be specified as an array:
		// array('template' => 'name')
		if (isset($parameters['template']))
		{
			$this->template = $parameters['template'];
		}

		$this->prefix = config('cache:prefix');
	}

	/**
	 * Saves data to the cache.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration date in minutes
	 * @return bool
	 */
	public abstract function write ($key, $data, $expire);

	/**
	 * Reads data from the cache.
	 * 
	 * @param string $key
	 * @return bool|mixed
	 */
	public abstract function read ($key);

	/**
	 * Checks if a cache key exists.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public abstract function has ($key);

	/**
	 * Deletes a cache key.
	 * 
	 * @param string $key
	 * @return void
	 */
	public abstract function delete ($key);

}