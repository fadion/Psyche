<?php
namespace Psyche\Core;

/**
 * Asset Helper
 * 
 * Builds paths to the asset files and returns html code.
 *
 * @package Psyche\Core\Asset
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
	public static function img ($file, $alt = '', $parameters = null)
	{
		$parameters = static::fix_params($parameters);

		$extension = pathinfo($file, PATHINFO_EXTENSION);

		// Defaults to '.png' if no extension is set
		if ($extension == '')
		{
			$file = $file . '.png';
		}

		$file = config('assets path') . config('img path') . $file;

		if (file_exists($file))
		{
			return '<img src="'.config('path').$file.'" alt="'.$alt.'"'.$parameters.'>';
		}
	}

	/**
	 * Fixes extra parameters. Basically, it allows writing html attributes
	 * without quotes and adds them automatically.
	 * Ex: Asset::img('image', '', array('class=photo'));
	 * 
	 * @param array $parameters
	 * 
	 * @return string
	 */
	protected static function fix_params ($parameters)
	{
		if (isset($parameters))
		{
			foreach ($parameters as &$param)
			{
				list($name, $value) = explode('=', $param);
				$value = htmlspecialchars(trim($value, '"'));
				$param = $name . '="' . $value . '"';
			}

			$parameters = implode(' ', $parameters);

			return $parameters;
		}
	}

}