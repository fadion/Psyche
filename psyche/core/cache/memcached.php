<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver;

/**
 * Memcached Cache Driver
 *
 * @package Psyche\Core\Cache\Memcached
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Memcached extends Driver
{

	/**
	 * @var array List of servers.
	 */
	protected static $servers;

	/**
	 * @var Memcached Memcached instance.
	 */
	protected $memcached;

	/**
	 * Constructor. Checkes if Memcached is installed and
	 * adds server(s).
	 */
	public function __construct ()
	{
		if (!class_exists('\Memcached', false))
		{
			throw new \Exception("Memcached wasn't found. Make sure it was installed correctly.");
		}

		// Reads servers once.
		if (!isset(static::$servers))
		{
			static::$servers = config('cache:memcached servers');
		}

		$this->memcached = new \Memcached;

		// Makes sure that servers are added once. As Memcached doesn't
		// do any duplicate checking, the servers pool can become quite
		// large if they are added on multiple runs.
		if (!count($this->memcached->getServerList()))
		{
			$this->memcached->addServers(static::$servers);
		}

		parent::__construct();
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
		// format used by Memcached.
		if ($expire !== 0)
		{
			$expire = time()+$expire*60;
		}

		return $this->memcached->set($this->prefix.'.'.$key, $data, $expire);
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
	public function soft_write ($key, $data, $expire = 15)
	{
		if ($expire !== 0)
		{
			$expire = time()+$expire*60;
		}

		return $this->memcached->add($this->prefix.'.'.$key, $data, $expire);
	}

	/**
	 * Reads data from the cache.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function read ($key)
	{
		return $this->memcached->get($this->prefix.'.'.$key);
	}

	/**
	 * Checks if a key exists in the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has ($key)
	{
		$data = $this->memcached->get($this->prefix.'.'.$key);

		if ($this->memcached->getResultCode() == \Memcached::RES_NOTFOUND)
		{
			return false;
		}

		return true;
	}

	/**
	 * Deletes a key from the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete ($key)
	{
		return $this->memcached->delete($this->prefix.'.'.$key, 0);
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
		return $this->memcached->increment($this->prefix.'.'.$key, $step);
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
		return $this->memcached->decrement($this->prefix.'.'.$key, $step);
	}

	/**
	 * Clears the user cache.
	 * 
	 * @return bool
	 */
	public function clear ()
	{
		return $this->memcached->flush();
	}

	/**
	 * Gets or sets a Memcached option.
	 * 
	 * @param int $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function option ($name, $value = null)
	{
		if (!isset($value))
		{
			return $this->memcached->getOption($name);
		}
		else
		{
			return $this->memcached->setOption($name, $value);
		}
	}

}