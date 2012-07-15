<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver;

/**
 * XCache Cache Driver
 * 
 * Uses XCache as the cache driver.
 *
 * @package Psyche\Core\Cache\APC
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class XCache extends Driver
{

	/**
	 * Constructor. Checkes if XCache is installed.
	 */
	public function __construct ($parameters)
	{
		if (!function_exists('xcache_set'))
		{
			throw new \Exception("XCache wasn't found. Make sure it was installed correctly.");
		}

		parent::__construct($parameters);
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
		// format used by XCache.
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

		return xcache_set($this->prefix.'.'.$key, $data, $expire);
	}

	/**
	 * Reads data from the cache.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function read ($key)
	{
		$data = xcache_get($this->prefix.'.'.$key);

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
		return xcache_isset($this->prefix.'.'.$key);
	}

	/**
	 * Deletes a key from the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete ($key)
	{
		return xcache_unset($this->prefix.'.'.$key);
	}

	/**
	 * Increments a number data from the cache.
	 * 
	 * @param $string $key
	 * @return mixed
	 */
	public function inc ($key)
	{
		return xcache_inc($this->prefix.'.'.$key);
	}

	/**
	 * Decrements a number data from the cache.
	 * 
	 * @param $string $key
	 * @return mixed
	 */
	public function dec ($key)
	{
		return xcache_dec($this->prefix.'.'.$key);
	}

	/**
	 * Clears the user cache.
	 * 
	 * @return bool
	 */
	public function clear ()
	{
		$return = true;

		// Set XCache password
		$tmp_user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : false;
		$tmp_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : false;

		$_SERVER['PHP_AUTH_USER'] = config('cache:xcache user');
		$_SERVER['PHP_AUTH_PW'] = config('cache:xcache password');

		// Clear the cache
		$cache_count = xcache_count(XC_TYPE_VAR);

		for($i = 0; $i < $cache_count; $i++)
		{
			if(@xcache_clear_cache(XC_TYPE_VAR, $i) === false)
			{
				$return = false;
				break;
			}
		}

		// Reset HTTP Authentication if they had any data.
		if($tmp_user !== false)
		{
			$_SERVER['PHP_AUTH_USER'] = $tmp_user;
		}
		else
		{
			unset($_SERVER['PHP_AUTH_USER']);
		}

		if($tmp_pass !== false)
		{
			$_SERVER['PHP_AUTH_PW'] = $tmp_pass;
		}
		else
		{
			unset($_SERVER['PHP_AUTH_PW']);
		}

		return $return;
	}

}