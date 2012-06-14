<?php
namespace FW\Core;
use FW\Core\CFG;

class Localizer
{

	private static $path = CFG::LOCALE_PATH;
	private static $cache;
	private static $rollback_cache;

	public static function get ()
	{
		$params = func_get_args();
		$params = $params[0];
		
		$text = $params[0];
		$vars = array();

		$lang = CFG::BASE_LOCALE;
		$rollback = CFG::ROLLBACK_LOCALE;

		static::read_language_file($lang);

		if (count(static::$cache) and array_key_exists($text, static::$cache))
		{
			$text = static::$cache[$text];
		}
		else
		{
			static::read_rollback_file($rollback);
			if (count(static::$rollback_cache) and array_key_exists($text, static::$rollback_cache))
			{
				$text = static::$rollback_cache[$text];
			}
		}
		
		if (isset($params[1]))
		{
			if (is_array($params[1]))
			{
				$vars = $params[1];
				array_unshift($vars, '');
				unset($vars[0]);
			}
			else
			{
				unset($params[0]);
				$vars = $params;
			}
		
			$text = static::parse_variables($text, $vars);
			$text = static::parse_case($text, $vars);
		}
		
		return $text;
	}

	private static function read_language_file ($lang)
	{
		$path = static::$path . $lang . '.php';

		if (file_exists($path) and !count(static::$cache))
		{
			static::$cache = include($path);
		}
	}

	private static function read_rollback_file ($lang)
	{
		$path = static::$path . $lang . '.php';

		if (file_exists($path) and !count(static::$rollback_cache))
		{
			static::$rollback_cache = include($path);
		}
	}

	private static function parse_variables ($text, $vars)
	{
		preg_match_all('|:\d|', $text, $matches);
		$matches = $matches[0];
		
		foreach ($matches as $key=>$val)
		{
			$index = substr($val, 1);
			if (array_key_exists($index, $vars))
			{
				$text = str_replace($val, $vars[$index], $text);
			}
		}

		return $text;
	}

	private static function parse_case ($text, $vars)
	{
		preg_match_all('|(\{(.+?)\})|', $text, $matches);
		$matches = $matches[count($matches) - 1];
		
		foreach ($matches as $match)
		{
			list($var, $cases) = explode(':', $match);
			list($single, $plural) = explode('|', $cases);
			
			$case = $plural;
			if (array_key_exists($var, $vars))
			{
				if (is_numeric($vars[$var]))
				{
					if ($vars[$var] == 1)
					{
						$case = $single;
					}
				}
			}
			
			$text = str_replace('{'.$match.'}', $case, $text);
		}

		return $text;
	}

}