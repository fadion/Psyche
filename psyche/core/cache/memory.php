<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver;

/**
 * Memory Cache Driver
 * 
 * Uses PHP arrays for non-persistent caching.
 * Consider this more of a dummy class, useful
 * during development. Don't use it as a caching
 * solution because it won't give any benefit.
 *
 * @package Psyche\Core\Cache\Memory
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Memory extends Driver
{

	/**
	 * @var array Cache held in a variable.
	 */
	protected $cache = array();

	/**
	 * Constructor. In here just to override the parent's
	 * constructor.
	 */
	public function __construct () {}

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
		else
		{
			$expire = time()+60*60*24;
		}

		$this->cache[$key] = array('data' => $data, 'expire' => $expire);

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
		if (!isset($this->cache[$key])) return false;

		if ($this->cache[$key]['expire'] > time())
		{
			$this->delete($key);
			return false;
		}

		return $this->cache[$key]['data'];
	}

	/**
	 * Checks if a key exists in the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has ($key)
	{
		return (bool) $this->read($key);
	}

	/**
	 * Deletes a key from the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function delete ($key)
	{
		if (isset($this->cache[$key]))
		{
			unset($this->cache[$key]);
			return true;
		}

		return false;
	}

	/**
	 * Clears the cache.
	 * 
	 * @return bool
	 */
	public function clear ()
	{
		$this->cache = array();
		return true;
	}

}