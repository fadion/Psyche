<?php
namespace Psyche\Core;

/**
 * Locale
 * 
 * A very practical and flexible approach to localizing.
 * It can be used as a simple print function when no language
 * files are present. However, when generated, it can work with
 * multiple language files, parse variable and cases, supports
 * rollbacks and the active language can be set on the fly.
 *
 * @package Psyche\Core\Locale
 * @author Fadion Dashi
 * @author Andri Xhitoni
 * @version 1.0
 * @since 1.0
 */

class Locale
{

	/**
	 * @var array Stores read language files.
	 */
	protected static $cache = array();

	/**
	 * @var array Stores read rollback files.
	 */
	protected static $rollback = array();

	/**
	 * @var string The active language.
	 */
	protected static $language = null;

	/**
	 * @var string The temporary active language for inline calls.
	 */
	protected static $temp_language = null;

	/**
	 * @var string The text that will be outputted
	 */
	protected $text;

	/**
	 * @var array Arguments passed, including the text and variables.
	 */
	protected $args;

	/**
	 * @var string File from where language strings will be read.
	 */
	protected $file = 'main';

	/**
	 * Constructor.
	 * 
	 * @param array $args
	 */
	public function __construct ($args)
	{
		$this->args = $args;
		return $this->localize();	
	}

	/**
	 * Factory static method to initialize the class.
	 * 
	 * @return Locale
	 */
	public static function get ()
	{
		return new static(func_get_args());
	}

	/**
	 * Parses variables and cases when the class is treated
	 * as string.
	 * 
	 * @return string
	 */
	public function __toString ()
	{
		$args = $this->args;
		$text = $this->text;

		// If any variable is set.
		if (isset($args[1]))
		{
			// Parameters can either be passed as a single array or
			// as function parameters.
			if (is_array($args[1]))
			{
				$vars = $args[1];
				array_unshift($vars, '');
				unset($vars[0]);
			}
			else
			{
				unset($args[0]);
				$vars = $args;
			}
		
			$text = $this->parse_variables($text, $vars);
			$text = $this->parse_case($text, $vars);
		}

		return $text;
	}

	/**
	 * Sets the active language or returns it when no
	 * argument is passed.
	 * 
	 * @param string $language
	 * 
	 * @return void|string
	 */
	public static function language ($language = null)
	{
		if (isset($language))
		{
			static::$language = $language;
		}
		else
		{
			// If the active language is set, return it.
			// Otherwise return the base locale from config.
			if (isset(static::$language))
			{
				return static::$language;
			}
			else
			{
				return config('base locale');
			}
		}
	}

	/**
	 * Determines which language and rollback files to read,
	 * caches them and sets the text to the appropriate value.
	 * 
	 * @return Locale
	 */
	protected function localize ()
	{
		$args = $this->args;

		// The first function parameter is always the language string.
		$text = $args[0];

		// When the language is set, read it's file. Otherwise, the base
		// locale will be read. Temporary language is maintained for a single
		// call, as it's used only via the from() method.
		if (isset(static::$temp_language))
		{
			$file = 'locale/'.static::$temp_language.'/'.$this->file.'.php';
			static::$temp_language = null;
		}
		elseif (isset(static::$language))
		{
			$file = 'locale/'.static::$language.'/'.$this->file.'.php';
		}
		else
		{
			$file = 'locale/'.config('base locale').'/'.$this->file.'.php';
		}

		$rollback = 'locale/'.config('rollback locale').'/'.$this->file.'.php';

		// When the cache isn't set, the file is included and added
		// to the cache.
		if (!isset(static::$cache[$file]))
		{
			if (file_exists($file))
			{
				static::$cache[$file] = include $file;
			}
		}

		// The same happens with the rollback file. It has a cache
		// of it's own, so it doesn't mess with the language cache.
		if (!isset(static::$rollback[$file]))
		{
			if (file_exists($rollback))
			{
				static::$rollback[$file] = include $rollback;
			}
		}

		// First checks if the key exists in the language file (stored in cache).
		// If not, check in the rollback.
		if (isset(static::$cache[$file][$text]))
		{
			$text = static::$cache[$file][$text];
		}
		elseif (isset(static::$rollback[$file][$text]))
		{
			$text = static::$rollback[$file][$text];
		}

		$this->text = $text;

		return $this;
	}

	/**
	 * Chooses a locale file to search for the language string.
	 * It's supposed to be chained after the get() static method.
	 * 
	 * @param strinf $file
	 * 
	 * @return Locale
	 */
	public function in ($file)
	{
		if (trim($file) != '')
		{
			$this->file = $file;

			// Runs the localize() again, but this time with
			// a different file to look for.
			return $this->localize();
		}

		return $this;
	}

	/**
	 * Chooses the active language. It's supposed to be chained
	 * after the get() static method.
	 */
	public function from ($language)
	{
		if (trim($language) != '')
		{
			static::$temp_language = $language;

			// Runs the localize() again with the active language
			// changed.
			return $this->localize();
		}

		return $this;
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
	protected function parse_variables ($text, $vars)
	{
		// Matches any pseudo-variable with the :digit syntax.
		preg_match_all('|:\d|', $text, $matches);
		$matches = $matches[0];
		
		foreach ($matches as $key=>$val)
		{
			$index = substr($val, 1);

			// Passed variables and parsed pseudo-variables are
			// treated with a 1:1 relationship. The first pseudo
			// variable found will be replaced with the value
			// of the first passed variable.
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
	protected function parse_case ($text, $vars)
	{
		// Matches cases with the {digit:singular|plural} syntax.
		preg_match_all('|(\{(.+?)\})|', $text, $matches);
		$matches = $matches[count($matches) - 1];
		
		foreach ($matches as $match)
		{
			list($var, $cases) = explode(':', $match);
			list($single, $plural) = explode('|', $cases);
			
			$case = $plural;
			if (array_key_exists($var, $vars))
			{
				// Only numeric can set the case.
				if (is_numeric($vars[$var]))
				{
					// When it's value is 1, it's singular.
					// Otherwise, it's plural.
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