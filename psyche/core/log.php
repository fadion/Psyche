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
	 * 
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
	 * Writes a FATAL type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function fatal ($message)
	{
		return static::write($message, 'fatal');
	}

	/**
	 * Writes a DEBUG type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function debug ($message)
	{
		return static::write($message, 'debug');
	}

	/**
	 * Writes an ERROR type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function error ($message)
	{
		return static::write($message, 'error');
	}

	/**
	 * Writes a WARNING type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function warning ($message)
	{
		return static::write($message, 'warning');
	}

	/**
	 * Writes a NOTICE type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function notice ($message)
	{
		return static::write($message, 'notice');
	}

	/**
	 * Writes a SECURITY type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function security ($message)
	{
		return static::write($message, 'security');
	}

	/**
	 * Writes an INFO type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function info ($message)
	{
		return static::write($message, 'info');
	}

	/**
	 * Writes a CRITICAL type message.
	 * 
	 * @param string $message
	 * 
	 * @return bool
	 */
	public static function critical ($message)
	{
		return static::write($message, 'critical');
	}

}