<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver,
	Psyche\Core\Query;

/**
 * Database Cache Driver
 * 
 * Uses a database table as key=>value caching solution,
 * with data serialization and expiration timestamp.
 * Due to the nature of queries and overhead of the
 * Query Builder, this solution should be considered
 * only for small applications or sections where performance
 * isn't critical.
 *
 * @package Psyche\Core\Cache\Database
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Database extends Driver
{

	/**
	 * @var string Database table to use for caching.
	 */
	protected $table;

	/**
	 * Constructor. Reads the database table from the cache
	 * config.
	 */
	public function __construct ()
	{
		$this->table = config('cache:database table');
	}

	/**
	 * Saves data to the cache.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration in minutes
	 * @return bool
	 */
	public function write ($key, $data, $expire = 0)
	{
		if (!isset($key) or $data == '') 
		{
			return false;
		}

		// If expiration is zero, it's set to expire after
		// 30 days.
		if ($expire == 0)
		{
			$expire = 60*24*30;
		}

		// Expiration is calculated from the current time
		// plus the set minutes (60 seconds * minutes)
		// and is prepended to the data.
		$expire = time()+60*$expire;
		$data = serialize($data);

		// Check if a key exists.
		$results = Query::select('key')->count('* AS total')->from($this->table)->where("key = $key")->first();

		// If it doesn't, insert it.
		if ($results->total == 0)
		{
			Query::insert($this->table, array(
				'key' => $key,
				'data' => $data,
				'expiration' => $expire
			))->query();
		}
		// Otherwise, update (override) the existing key.
		else
		{
			Query::update($this->table, array(
				'key' => $key,
				'data' => $data,
				'expiration' => $expire
			))->where('key = '.$results->key)->query();
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
		$results = Query::select('data, expiration')->from($this->table)->where("key = $key")->first();

		if (isset($results->data))
		{
			// If the key hasn't expired yet, return the
			// unserialized data.
			if ($results->expiration > time())
			{
				return unserialize($results->data);
			}
			// Otherwise, delete it.
			else
			{
				$this->delete($key);
			}
		}

		return false;
	}

	/**
	 * Checks if a key exists in the cache.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has ($key)
	{
		$results = Query::select('expiration')->from($this->table)->where("key = $key")->first();

		// For a key to be valid, it should exist and be within
		// the expiration date.
		if (isset($results->expiration) and ($results->expiration > time()))
		{
			return true;
		}

		return false;
	}

	/**
	 * Deletes a key from the cache.
	 * 
	 * @param string $key
	 * @return void
	 */
	public function delete ($key)
	{
		Query::delete($this->table)->where("key = $key")->query();
	}

}