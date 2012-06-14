<?php
namespace FW\Core;

class Asset
{

	public static function js ($file)
	{
		$file = config('assets path') . config('js path') . $file . '.js';

		if (file_exists($file))
		{
			return '<script src="' . config('path') . $file . '"></script>';
		}
	}

	public static function css ($file)
	{
		$file = config('assets path') . config('css path') . $file . '.css';

		if (file_exists($file))
		{
			return '<link rel="stylesheet" href="' . config('path') . $file . '">';
		}
	}

	public static function img ($file, $alt = '')
	{
		$extension = pathinfo($file, PATHINFO_EXTENSION);

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