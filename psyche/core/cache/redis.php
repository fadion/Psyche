<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver,
	Psyche\Core\Redis as RedisClient;

/**
 * Redis Cache Driver
 *
 * @package Psyche\Core\Cache\Redis
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Redis extends Driver
{

	/**
	 * @var Redis Redis object instance.
	 */
	protected $redis;

	/**
	 * Constructor. Connects to the redis server using
	 * the specified template or the default one.
	 * 
	 * @param array $parameters
	 */
	public function __construct ($parameters)
	{
		parent::__construct($parameters);

		$this->redis = RedisClient::connect($this->template);
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
		// Data is serialized only if auto-serialization
		// is active.
		if ($this->serialize)
		{
			$data = serialize($data);
		}

		$this->redis->set($this->prefix.'.'.$key, $data);

		// If expiration is different from zero, it will try
		// to send an EXPIRE command.
		if ($expire !== 0)
		{
			// Convert minutes to seconds.
			$expire = $expire*60;

			$this->redis->expire($this->prefix.'.'.$key, $expire);
		}

		return true;
	}

	/**
	 * Reads data from the cache.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function read ($key)
	{
		$data = $this->redis->get($this->prefix.'.'.$key);

		// If a key doesn't exist, redis returns null.
		if ($data === null)
		{
			return false;
		}

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
		return (bool) $this->redis->exists($this->prefix.'.'.$key);
	}

	/**
	 * Deletes a key from the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete ($key)
	{
		return (bool) $this->redis->del($this->prefix.'.'.$key);
	}

	/**
	 * Increments a number data from the cache.
	 * 
	 * @param $string $key
	 * @return mixed
	 */
	public function inc ($key)
	{
		// Redis will increment a nonexistent key too,
		// so it checks first if it exists.
		if ($this->has($key))
		{
			return $this->redis->incr($this->prefix.'.'.$key);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Decrements a number data from the cache.
	 * 
	 * @param $string $key
	 * @return mixed
	 */
	public function dec ($key)
	{
		// Redis will decrement a nonexistent key too,
		// so it checks first if it exists.
		if ($this->has($key))
		{
			return $this->redis->decr($this->prefix.'.'.$key);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Clears all the keys.
	 * 
	 * @return bool
	 */
	public function clear ()
	{
		$this->redis->flushdb();
	}

}