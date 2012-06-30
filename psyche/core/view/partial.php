<?php
namespace Psyche\Core\View;

// WIP
class Partial
{

	protected static $master;
	protected static $reserves = array();
	protected static $active;

	public static function _use ($file)
	{
		$file = static::path($file);

		ob_start();
		include($file);
		static::$master = ob_get_clean();
	}

	public static function reserve ($name)
	{
		static::$reserves[$name] = true;
		echo '#####@'.$name.'@#####';
	}

	public static function begin ($name)
	{
		if (isset(static::$reserves[$name]))
		{
			static::$active = $name;
			ob_start();
		}
	}

	public static function end ()
	{
		if (isset(static::$active))
		{
			$partial = ob_get_clean();
			echo str_replace('#####@'.static::$active.'@#####', $partial, static::$master);
		}
	}

	protected static function path ($file)
	{
		$file = config('views path').$file;
		$exists = true;

		if (stripos($file, config('mold extension')) === false and stripos($file, '.php') === false)
		{
			if (file_exists($file.'.php'))
			{
				$file .= '.php';
			}
			elseif (file_exists($file.config('mold extension')))
			{
				$file .= config('mold extension');
			}
			else
			{
				$exists = false;
			}
		}
		else
		{
			if (!file_exists($file))
			{
				$exists = false;
			}
		}

		if ($exists)
		{
			return $file;
		}
		else
		{
			throw new \Exception(sprintf("View %s doesn't exist", $file));
		}
	}

}