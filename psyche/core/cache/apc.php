<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver;

/**
 * APC Cache Driver
 *
 * @package Psyche\Core\Cache\APC
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class APC extends Driver
{

	/**
	 * Constructor. Checkes if APC is installed.
	 */
	public function __construct ($parameters)
	{
		if (!function_exists('apc_store'))
		{
			throw new \Exception("APC wasn't found. Make sure it was installed correctly.");
		}

		parent::__construct($parameters);
	}

	/**
	 * Writes data to the cache.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration in minutes
	 * @return bool
	 */
	public function write ($key, $data, $expire = 0)
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

		return apc_store($this->prefix.'.'.$key, $data, $expire);
	}

	/**
	 * Writes data to the cache only if the key doesn't exist.
	 * Will return false if the key already exists.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration in minutes
	 * @return bool
	 */
	public function soft_write ($key, $data, $expire = 0)
	{
		if ($expire !== 0)
		{
			$expire = $expire*60;
		}

		if ($this->serialize)
		{
			$data = serialize($data);
		}

		return apc_add($this->prefix.'.'.$key, $data, $expire);
	}

	/**
	 * Reads data from the cache.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function read ($key)
	{
		$data = apc_fetch($this->prefix.'.'.$key);

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
	 * @return bool
	 */
	public function has ($key)
	{
		return apc_exists($this->prefix.'.'.$key);
	}

	/**
	 * Deletes a key from the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete ($key)
	{
		return apc_delete($this->prefix.'.'.$key);
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
		return apc_inc($this->prefix.'.'.$key, $step);
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
		return apc_dec($this->prefix.'.'.$key, $step);
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