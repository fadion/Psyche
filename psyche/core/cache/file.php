<?php
namespace Psyche\Core\Cache;
use Psyche\Core\Cache\Driver;

/**
 * File Cache Driver
 * 
 * Stores cache data in the file system. This is the most
 * basic type of caching solution provided natively by
 * Psyche and it should do well for most small applications.
 * However, when in need of a better and more powerful
 * solution, use any of the other drivers.
 *
 * @package Psyche\Core\Cache\File
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class File implements Driver
{

	/**
	 * @var string Path of the cache directory.
	 */
	protected $path;

	/**
	 * Constructor. Sets the path.
	 * 
	 * @param string $path
	 */
	public function __construct ($path)
	{
		$this->path = $path;
	}

	/**
	 * Saves data to a file. Data is serialized and the
	 * expiration timestamp is prepended to the file
	 * contents.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire Expiration date in minutes
	 * @return bool
	 */
	public function save ($key, $data, $expire = 15)
	{
		if (!isset($key) or $data == '') 
		{
			return false;
		}

		// Expiration can't be zero or negative.
		if ($expire <= 0)
		{
			$expire = 15;
		}

		// Expiration is calculated from the current time
		// plus the set minutes (60 seconds * minutes)
		// and is prepended to the data.
		$expire = time()+60*$expire;
		$data = $expire.serialize($data);

		if (file_put_contents($this->file($key), $data) !== false)
		{
			return true;
		}

		return false;
	}

	/**
	 * Gets data from a cache file.
	 * 
	 * @param string $key
	 * @return bool|mixed
	 */
	public function get ($key)
	{
		$file = $this->file($key);

		if (!file_exists($file))
		{
			return false;
		}

		$data = file_get_contents($file);

		// The expiration timestamp is exactly
		// 10 characters longs.
		$expire = substr($data, 0, 10);
		$data = unserialize(substr($data, 10));

		// For the cache file to be valid, the current
		// timestamp should be lower than the expiration.
		// If not, the file is deleted.
		if (time() > $expire)
		{
			$this->delete($key);
			return false;
		}

		return $data;
	}

	/**
	 * Checks if a cache file exists.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has ($key)
	{
		return (bool) $this->get($key);
	}

	/**
	 * Deletes a cache file.
	 * 
	 * @param string $key
	 * @return void
	 */
	public function delete ($key)
	{
		if (file_exists($this->file($key)))
		{
			@unlink($this->file($key));
		}
	}

	/**
	 * Builds the file path.
	 * 
	 * @param string $key
	 * @return string
	 */
	protected function file ($key)
	{
		return $this->path.$key;
	}

}