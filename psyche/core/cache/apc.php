<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver;

/**
 * APC Cache Driver
 * 
 * Uses APC as the cache driver.
 *
 * @package Psyche\Core\Cache\APC
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class APC implements Driver
{

	/**
	 * @var bool Whether to auto-serialize data or not.
	 */
	protected $serialize = true;

	/**
	 * Constructor. Checkes if APC is installed and
	 * sets the $serialize property.
	 */
	public function __construct ($parameters)
	{
		if (!function_exists('apc_store'))
		{
			throw new \Exception("APC wasn't found. Make sure it was installed correctly.");
		}

		// Auto-serialization is specified as an array:
		// array('serialize' => true)
		if (isset($parameters) and isset($parameters['serialize']))
		{
			$this->serialize = $parameters['serialize'];
		}
	}

	/**
	 * Writes data to the cache.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration in minutes
	 * @return bool|array
	 */
	public function write ($key, $data, $expire = 15)
	{
		// Expiration is converted to seconds, the right
		// format used by APC.
		if ($expire !== 0)
		{
			$expire = $expire*60;
		}

		// Data is serialized only if auto-serialization
		// is active.
		if ($this->serialize)
		{
			$data = serialize($data);
		}

		return apc_store($key, $data, $expire);
	}

	/**
	 * Writes data to the cache only if the key doesn't exist.
	 * Will return false if the key already exists.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration in minutes
	 * @return bool|array
	 */
	public function soft_write ($key, $data, $expire = 15)
	{
		if ($expire !== 0)
		{
			$expire = $expire*60;
		}

		if ($this->serialize)
		{
			$data = serialize($data);
		}

		return apc_add($key, $data, $expire);
	}

	/**
	 * Reads data from the cache.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function read ($key)
	{
		$data = apc_fetch($key);

		if ($this->serialize)
		{
			$data = unserialize($data);
		}

		return $data;
	}

	/**
	 * Checks if a key exists in the cache.
	 * 
	 * @param string $key
	 * @return bool|array
	 */
	public function has ($key)
	{
		return apc_exists($key);
	}

	/**
	 * Deletes a key from the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete ($key)
	{
		return apc_delete($key);
	}

	/**
	 * Increments a number data from the cache.
	 * 
	 * @param $string $key
	 * @param int $step Number of steps to increment
	 * @return mixed
	 */
	public function inc ($key, $step = 1)
	{
		return apc_inc($key, $step);
	}

	/**
	 * Decrements a number data from the cache.
	 * 
	 * @param $string $key
	 * @param int $step Number of steps to decrement
	 * @return mixed
	 */
	public function dec ($key, $step = 1)
	{
		return apc_dec($key, $step);
	}

	/**
	 * Clears the user or system cache. If the type
	 * is 'user', it will clear the user cache only.
	 * Otherwise, any cached files will be cleared.
	 * 
	 * @param string $type
	 * @return bool
	 */
	public function clear ($type = 'system')
	{
		return apc_clear_cache($type);
	}

}