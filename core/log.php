<?php
namespace FW\Core;
use FW\Core\File;
use FW\Core\CFG;

class Log {

	public static function write ($message, $type = null) {
		$file = CFG::LOG_FILE;

		$time = date('Y-m-d H:i:s');

		$message = "$time: $message\r\n";

		if ($type !== null) {
			$message = '@'.strtoupper($type).': '.$message;
		}

		if (!File::exists($file)) {
			File::write($file, $message);
		} else {
			File::prepend($file, $message);
		}
	}

	public static function debug ($message) {
		static::write($message, 'debug');
	}

	public static function error ($message) {
		static::write($message, 'error');
	}

	public static function warning ($message) {
		static::write($message, 'warning');
	}

	public static function notice ($message) {
		static::write($message, 'notice');
	}

	public static function security ($message) {
		static::write($message, 'security');
	}

	public static function info ($message) {
		static::write($message, 'info');
	}

	public static function critical ($message) {
		static::write($message, 'critical');
	}

}