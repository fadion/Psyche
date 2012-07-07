<?php
namespace Psyche\Core;

/**
 * Log Helper
 * 
 * A very simple logging class that is aware of existing log files
 * and provides some built-in message types.
 *
 * @package Psyche\Core\Molder
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Log
{

	/**
	 * Writes message in the log file.
	 * 
	 * @param string $message
	 * @param string $type Message type
	 * @return bool
	 */
	public static function write ($message, $type = null)
	{
		$date = date('Y-m-d');
		$time = date('Y-m-d H:i:s');

		// Files are saved with the date as filename.
		$file = 'stash/logs/'.$date.'.txt';

		$message = "$time: $message\r\n";

		// Type, if is set, is placed in the beginning of the line.
		if ($type !== null)
		{
			$message = '@'.strtoupper($type).': '.$message;
		}

		// When a file exists, the message is prepended.
		if (file_exists($file))
		{
			$contents = file_get_contents($file);
			$message .= $contents;
		}

		if (is_writable('stash/logs'))
		{
			file_put_contents($file, $message);
			return true;
		}
		else
		{
			trigger_error("Stash/Logs is not writable", E_USER_NOTICE);
		}
	}

	/**
	 * Adds message types dynamically.
	 * 
	 * @param string $name
	 * @param array $args
	 * @return bool
	 */
	public static function __callStatic($name, $args)
	{
		static::write($args[0], $name);
	}

}