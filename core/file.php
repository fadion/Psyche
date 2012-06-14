<?php
namespace FW\Core;

class File {

	public static function exists ($file) {
		return file_exists($file);
	}

	public static function read ($file) {
		$return = false;

		if (file_exists($file)) {
			$return = file_get_contents($file);
		}

		return $return;
	}

	public static function write ($file, $contents = '') {
		return file_put_contents($file, $contents);
	}

	public static function append ($file, $contents = '') {
		$file = file_put_contents($file, $contents, FILE_APPEND);
		
		return $file;
	}

	public static function prepend ($file, $contents) {
		$file_contents = static::read($file);

		return static::write($file, $contents . $file_contents);
	}

	public static function extension ($file) {
		return pathinfo($file, PATHINFO_EXTENSION);
	}

	public static function is ($extension, $file) {
		$return = false;
		$file_ext = static::extension($file);
		$extension = trim($extension, '.');

		if ($extension == $file_ext) {
			$return = true;
		}

		return $return;
	}

	public static function size ($file) {
		$return = false;

		if (file_exists($file)) {
			$return = filesize($file);
		}

		return $return;
	}

	public static function modified ($file) {
		$return = false;

		if (file_exists($file)) {
			$return = filemtime($file);
		}

		return $return;
	}

	public static function move ($file, $target) {
		$return = false;

		if (file_exists($file)) {
			$return = rename($file, $target);
		}

		return $return;
	}

	public static function copy ($file, $target) {
		$return = false;

		if (file_exists($file)) {
			$return = copy($file, $target);
		}

		return $return;
	}

	public static function delete ($file) {
		$return = false;

		if (file_exists($file)) {
			@unlink($file);
			$return = true;
		}

		return $return;
	}

	public static function upload ($file, $path, $name = null) {
		$return = false;

		if (file_exists($file)) {
			$path = trim($path, '/');

			if (!is_null($name)) {
				$path = "$path/$name";
			}

			if (move_uploaded_file($file, $path)) {
				$return = $path;
			}
		}

		return $return;
	}

}