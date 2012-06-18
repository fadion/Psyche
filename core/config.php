<?php
namespace FW\Core;

/**
 * Config helper
 * 
 * Reads the config file and provides an easy way to access options.
 *
 * @package FW\Core\Config
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Config
{

	/**
	 * @var array Stores config options
	 */
	private static $keys;

	/**
	 * Returns a config option.
	 * 
	 * @param string $key The key of the config option
	 * 
	 * @return string|void
	 */
	public static function get ($key = null)
	{
		static::open_file();
		static::auto_path();

		if (isset(static::$keys[$key]))
		{
			return static::$keys[$key];
		}
	}

	/**
	 * Opens the config file.
	 * 
	 * @return void
	 */
	private static function open_file ()
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
				trigger_error('Config file not found. Please be sure it exists and is correctly formatted', FATAL);
			}
		}
	}

	/**
	 * Attempts to automatically set HTTP and File System paths. It will be
	 * triggered if those config options will be "auto".
	 * 
	 * @return void
	 */
	private static function auto_path () {
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