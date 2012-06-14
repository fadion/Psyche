<?php
namespace FW\Core;

class Cfg
{

	private static $keys;

	public static function get ($key = null)
	{
		static::open_file();
		static::auto_path();

		if (isset(static::$keys[$key]))
		{
			return static::$keys[$key];
		}
	}

	private static function open_file ()
	{
		if (empty(static::$keys))
		{
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