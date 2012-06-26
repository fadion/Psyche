<?php
namespace Psyche\Core;

/**
 * Helper for working with files
 * 
 * Provide some nice functions for file handling, information and upload.
 *
 * @package Psyche\Core\Psyc
 * @see Psyche\Core\View
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class File
{

	/**
	 * Checks if a file exists.
	 * 
	 * @param string $file File path
	 * 
	 * @return bool
	 */
	public static function exists ($file)
	{
		return file_exists($file);
	}

	/**
	 * Reads a file if it exists.
	 * 
	 * @param string $file File path
	 * 
	 * @return bool|string
	 */
	public static function read ($file)
	{
		$return = false;

		if (file_exists($file))
		{
			$return = file_get_contents($file);
		}

		return $return;
	}

	/**
	 * Writes contents to a file. Will create it if it doesn't exist.
	 * 
	 * @param string $file File path
	 * @param strint $contents The contents to be written
	 * 
	 * @return bool|int file_put_contents() returns boolen FALSE on failure
	 */
	public static function write ($file, $contents = '')
	{
		return file_put_contents($file, $contents);
	}

	/**
	 * Appends contents to a file. Will create it if it doesn't exist.
	 * 
	 * @param string $file File path
	 * @param string $contents The contents to be written
	 * 
	 * @return bool|int
	 */
	public static function append ($file, $contents)
	{
		return file_put_contents($file, $contents, FILE_APPEND);
	}

	/**
	 * Prepends contents to a file. Will create it if it doesn't exist.
	 * 
	 * @param string $file File path
	 * @param string $contents The contents to be written
	 * 
	 * @return bool|int
	 */
	public static function prepend ($file, $contents)
	{
		$file_contents = static::read($file);

		return static::write($file, $contents . $file_contents);
	}

	/**
	 * Gets the file extension.
	 * 
	 * @param string $file File path
	 * 
	 * @return string
	 */
	public static function extension ($file)
	{
		return pathinfo($file, PATHINFO_EXTENSION);
	}

	/**
	 * Checks if the file has the given extension.
	 * 
	 * @param string $extension The extension the file will be checked for
	 * @param string $file File path
	 * 
	 * @return bool
	 */
	public static function is ($extension, $file)
	{
		$return = false;
		$file_ext = static::extension($file);
		$extension = trim($extension, '.');

		if ($extension == $file_ext)
		{
			$return = true;
		}

		return $return;
	}

	/**
	 * Gets the size in bytes of a file.
	 * 
	 * @param string $file File path
	 * 
	 * @return bool|int
	 */
	public static function size ($file)
	{
		$return = false;

		if (file_exists($file))
		{
			$return = filesize($file);
		}

		return $return;
	}

	/**
	 * Gets the modification time of a file.
	 * 
	 * @param string $file File path
	 * 
	 * @return bool|int
	 */
	public static function modified ($file)
	{
		$return = false;

		if (file_exists($file))
		{
			$return = filemtime($file);
		}

		return $return;
	}

	/**
	 * Moves a file to a different path.
	 * 
	 * @param string $file File path
	 * @param string $target Destination path
	 * 
	 * @return bool
	 */
	public static function move ($file, $target)
	{
		$return = false;

		if (file_exists($file))
		{
			$return = rename($file, $target);
		}

		return $return;
	}

	/**
	 * Copies a file to a different path.
	 * 
	 * @param string $file File path
	 * @param string $target Destination path
	 * 
	 * @return bool
	 */
	public static function copy ($file, $target)
	{
		$return = false;

		if (file_exists($file))
		{
			$return = copy($file, $target);
		}

		return $return;
	}

	/**
	 * Deletes a file from the file system.
	 * 
	 * @param string $file File path
	 * 
	 * @return bool
	 */
	public static function delete ($file)
	{
		$return = false;

		if (file_exists($file))
		{
			@unlink($file);
			$return = true;
		}

		return $return;
	}

	/**
	 * Uploads a file using the standart move_uploaded_file().
	 * 
	 * @param string $file File path
	 * @param string $path Path to upload the file to
	 * @param string $name Rename the file
	 * 
	 * @return bool|string
	 */
	public static function upload ($file, $path, $name = null)
	{
		$return = false;

		if (file_exists($file))
		{
			$path = trim($path, '/');

			if (!is_null($name))
			{
				$path = "$path/$name";
			}

			if (move_uploaded_file($file, $path))
			{
				$return = $path;
			}
		}

		return $return;
	}

}