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
	 * Returns a config option. It can be either a single
	 * key for the main config file, or a "file:key" format
	 * for one of the additional config files.
	 * 
	 * @param string $key The key of the config option
	 * @return string|bool
	 */
	public static function get ($key)
	{
		// If a colon is found, it means that a file
		// is being searched for a key. Otherwise,
		// the file is the main config.
		if (strpos($key, ':') !== false)
		{
			list($file, $key) = explode(':', $key);
		}
		else
		{
			$file = 'config';
		}

		static::open_file($file);
		static::auto_path();

		$return = false;

		// If only a file is being searched (file:), the contents
		// of that config file will be returned as array.
		if (isset($file) and (!isset($key) or $key === ''))
		{
			$return = static::$keys[$file];
		}
		elseif (isset(static::$keys[$file][$key]))
		{
			$return = static::$keys[$file][$key];
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
		// As with get(), keys can be set for files
		// other than the main config.
		if (strpos($key, ':') !== false)
		{
			list($file, $key) = explode(':', $key);
		}
		else
		{
			$file = 'config';
		}

		if (isset(static::$keys[$file][$key]))
		{
			static::$keys[$file][$key] = $value;
			return true;
		}

		return false;
	}

	/**
	 * Opens the config file.
	 * 
	 * @return void
	 */
	protected static function open_file ($file)
	{
		// Keys are cached. If they are already set, no need to
		// open the file again.
		if (empty(static::$keys[$file]))
		{
			$path = 'config/'.$file.'.php';

			if (file_exists($path))
			{
				// The config file returns an array.
				static::$keys[$file] = require_once $path;
			}
			else
			{
				throw new \Exception(sprintf("Config file %s not found.", $file.'php'));
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
		if (isset(static::$keys['config']['path']) and static::$keys['config']['path'] == 'auto')
		{
			static::$keys['config']['path'] = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']) . '/';
		}

		if (isset(static::$keys['config']['absolute path']) and static::$keys['config']['absolute path'] == 'auto')
		{
			static::$keys['config']['absolute path'] = realpath('.').'/';
		}
	}

}