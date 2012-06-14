<?php
namespace FW\Core;

class Log
{

	public static function write ($message, $type = null)
	{
		$file = config('log file');

		$time = date('Y-m-d H:i:s');

		$message = "$time: $message\r\n";

		if ($type !== null)
		{
			$message = '@'.strtoupper($type).': '.$message;
		}

		if (!file_exists($file))
		{
			file_put_contents($file, $message);
		}
		else
		{
			$contents = file_get_contents($file);
			file_put_contents($file, $message.$contents);
		}
	}

	public static function debug ($message)
	{
		static::write($message, 'debug');
	}

	public static function error ($message)
	{
		static::write($message, 'error');
	}

	public static function warning ($message)
	{
		static::write($message, 'warning');
	}

	public static function notice ($message)
	{
		static::write($message, 'notice');
	}

	public static function security ($message)
	{
		static::write($message, 'security');
	}

	public static function info ($message)
	{
		static::write($message, 'info');
	}

	public static function critical ($message)
	{
		static::write($message, 'critical');
	}

}