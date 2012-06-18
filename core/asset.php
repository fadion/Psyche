<?php
namespace FW\Core;

/**
 * Asset Helper
 * 
 * Builds paths to the asset files and returns html code.
 *
 * @package FW\Core\Asset
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Asset
{

	/**
	 * Outputs a <script src="file"> tag.
	 * 
	 * @param string $file JS file to be opened. Just the filename, with or without extension
	 * 
	 * @return string
	 */
	public static function js ($file)
	{
		$file = config('assets path') . config('js path') . $file;

		if (pathinfo($file, PATHINFO_EXTENSION) == '')
		{
			$file .= '.js';
		}

		if (file_exists($file))
		{
			return '<script src="' . config('path') . $file . '"></script>';
		}
	}

	/**
	 * Outputs a <link rel="stylesheet" href="file"> tag.
	 * 
	 * @param string $file CSS file to be opened. Just the filename, with or without extension
	 * 
	 * @return string
	 */
	public static function css ($file)
	{
		$file = config('assets path') . config('css path') . $file;

		if (pathinfo($file, PATHINFO_EXTENSION) == '')
		{
			$file .= '.css';
		}

		if (file_exists($file))
		{
			return '<link rel="stylesheet" href="' . config('path') . $file . '">';
		}
	}

	/**
	 * Outputs an <img src="file" alt="alt"> tag.
	 * 
	 * @param string $file Image file to be opened. Just the filename, with or without extension
	 * @param string $alt Alternative text
	 * 
	 * @return string
	 */
	public static function img ($file, $alt = '')
	{
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		// Defaults to '.png' if no extension is set
		if ($extension == '')
		{
			$file = $file . '.png';
		}

		$file = config('assets path') . config('img path') . $file;

		if (file_exists($file))
		{
			return '<img src="' . config('path') . $file . '" alt="' . $alt . '">';
		}
	}

}