<?php
namespace Psyche\Core\Cache;

/**
 * Cache Drivers Interface
 *
 * @package Psyche\Core\Cache\Driver
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
interface Driver
{

	/**
	 * Saves data to the cache.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration date in minutes
	 * @return bool
	 */
	public function save ($key, $data, $expire);

	/**
	 * Gets data from the cache.
	 * 
	 * @param string $key
	 * @return bool|mixed
	 */
	public function get ($key);

	/**
	 * Checks if a cache key exists.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has ($key);

	/**
	 * Deletes a cache key.
	 * 
	 * @param string $key
	 * @return void
	 */
	public function delete ($key);

}