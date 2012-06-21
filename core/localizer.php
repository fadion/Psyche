<?php
namespace FW\Core;

/**
 * Localizer
 * 
 * A very practical approach to localizing, using a similiar technique
 * as gettext(). It supports rollback, variables
 * and single/plural calculation.
 *
 * @package FW\Core\Localizer
 * @author Fadion Dashi, Andri Xhitoni
 * @version 1.1
 * @since 1.0
 */
class Localizer
{

	/**
	 * @var string Base path of locales
	 */
	protected static $path;

	/**
	 * @var array Language keys
	 */
	protected static $cache;

	/**
	 * @var array Rollback keys
	 */
	protected static $rollback_cache;

	/**
	 * @var string Language that's being used
	 */
	protected static $language = null;

	/**
	 * Runs the Localizer. Arguments can be as much as needed, where
	 * the first is always the language string and the rest are variables.
	 * If no language file is found, the string will be returned as is.
	 * 
	 * @return string
	 */
	public static function get ()
	{
		static::set_defaults();

		$params = func_get_args();
		
		// The first function parameter is always the language string.
		$text = $params[0];
		$vars = array();

		static::read_language_file();

		// If the key doesn't exists in the current language, the rollback
		// is checked.
		if (count(static::$cache) and array_key_exists($text, static::$cache))
		{
			$text = static::$cache[$text];
		}
		else
		{
			static::read_rollback_file();
			if (count(static::$rollback_cache) and array_key_exists($text, static::$rollback_cache))
			{
				$text = static::$rollback_cache[$text];
			}
		}
		
		// Variable parameters can either be passed as a single array or
		// as function parameters.
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

	/**
	 * Sets and gets the language that is being used.
	 * 
	 * @param string $language Language name
	 * 
	 * @return string|void
	 */
	public static function language ($language = null)
	{
		static::set_defaults();

		if ($language == null)
		{
			return static::$language;
		}
		else
		{
			static::$language = $language;
		}
	}

	/**
	 * Sets the base locale path and default language.
	 * 
	 * @return void
	 */
	protected static function set_defaults ()
	{
		static::$path = config('locale path');

		if (static::$language == null)
		{
			static::$language = config('base locale');
		}
	}

	/**
	 * Reads the default language file.
	 * 
	 * @return void
	 */
	protected static function read_language_file ()
	{

		$path = static::$path . static::$language . '.php';

		// Locale file will be read, only if the keys aren't set yet.
		// This offers some basic optimization so files are read
		// only once a session.
		if (file_exists($path) and !count(static::$cache))
		{
			static::$cache = include($path);
		}
	}

	/**
	 * Reads the rollback locale file.
	 * 
	 * @return void
	 */
	protected static function read_rollback_file ()
	{
		$path = static::$path . config('rollback locale') . '.php';

		if (file_exists($path) and !count(static::$rollback_cache))
		{
			static::$rollback_cache = include($path);
		}
	}

	/**
	 * Replaces variable values with their occurrences in the string.
	 * :1 will be replaced with the variable value in the first position
	 * after the language string. :2 with the second and so on.
	 * 
	 * @param string $text The language string
	 * @param array $vars Variables passed
	 * 
	 * @return string
	 */
	protected static function parse_variables ($text, $vars)
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

	/**
	 * Parses cases based on variable values. Cases are written as:
	 * {1:singular|plural}. An integer variable with a value of 1 will
	 * always trigger the singular case; a value different than 1 will
	 * trigger the plural case.
	 * 
	 * @param string $text The language string
	 * @param array $vars Variable passed
	 * 
	 * @return string
	 */
	protected static function parse_case ($text, $vars)
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