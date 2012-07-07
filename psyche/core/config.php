<?php
namespace Psyche\Core;

/**
 * Config helper
 * 
 * Reads the config file and provides an easy way to access options.
 *
 * @package Psyche\Core\Config
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Config
{

	/**
	 * @var array Stores config options
	 */
	protected static $keys;

	/**
	 * Returns a config option.
	 * 
	 * @param string $key The key of the config option
	 * @return string|bool
	 */
	public static function get ($key)
	{
		static::open_file();
		static::auto_path();

		$return = false;

		if (isset(static::$keys[$key]))
		{
			$return = static::$keys[$key];
		}

		return $return;
	}

	/**
	 * Sets a config option.
	 * 
	 * @param string $key The key to be created
	 * @param string $value Value of the key
	 * @return bool
	 */
	public static function set ($key, $value)
	{
		if (isset(static::$keys[$key]))
		{
			static::$keys[$key] = $value;
			return true;
		}

		return false;
	}

	/**
	 * Opens the config file.
	 * 
	 * @return void
	 */
	protected static function open_file ()
	{
		// Keys are cached. If they are already set, no need to
		// open the file again.
		if (empty(static::$keys))
		{
			// The config file returns an array.
			$file = 'config/config.php';

			if (file_exists($file))
			{
				static::$keys = require_once 'config/config.php';
			}
			else
			{
				trigger_error('Config file not found. Please be sure it exists and is correctly formatted', E_USER_ERROR);
			}
		}
	}

	/**
	 * Attempts to automatically set HTTP and File System paths. It will be
	 * triggered if those config options will be "auto".
	 * 
	 * @return void
	 */
	protected static function auto_path () {
		if (isset(static::$keys['path']) and static::$keys['path'] == 'auto')
		{
			static::$keys['path'] = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
		}

		if (isset(static::$keys['absolute path']) and static::$keys['absolute path'] == 'auto')
		{
			static::$keys['absolute path'] = realpath('.') . '/';
		}
	}

}