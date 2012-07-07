<?php
namespace Psyche\Core;

/**
 * String Helper
 * 
 * Provides functionalities for string manipulation and
 * generation.
 *
 * @package Psyche\Core\String
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class String
{

	/**
	 * @var string Alphanumeric characters.
	 */
	protected static $alphanumeric = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	/**
	 * @var string Alpha characters.
	 */
	protected static $alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	/**
	 * @var string Hex characters.
	 */
	protected static $hex = '0123456789abcdef';

	/**
	 * @var string Numbers.
	 */
	protected static $num = '0123456789';

	/**
	 * @var string Symbols.
	 */
	protected static $symbols = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';

	/**
	 * @var int Alternator's current iteration.
	 */
	protected static $alternate = 0;

	/**
	 * Generates a random string.
	 * 
	 * @param string $type Type of characters: alphanumeric|alpha|hex|num|symbols
	 * @param int $size Size of the generated string
	 * @return string
	 */
	public static function random ($type = 'alphanumeric', $size = 16)
	{
		if ($type == 'mix')
		{
			$string = static::$alphanumeric.static::$symbols;
		}
		else
		{
			// As type is mapped automatically to class
			// properties, it's first checked if it exists.
			if (property_exists('String', $type))
			{
				$string = static::${$type};
			}
			else
			{
				$string = static::$alphanumeric;
			}
		}

		$return = '';

		// Generates the random string for the given length.
		for ($i = 0, $length = strlen($string); $i < $size; $i++)
		{
			$return .= $string[mt_rand(0, $length)];
		}

		return $return;
	}

	/**
	 * A direct interface to PHP's nl2br().
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function nl2br ($string)
	{
		return nl2br($string);
	}

	/**
	 * Converts <br> to newlines.
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function br2nl ($string)
	{
		return str_replace(array('<br>', '<br/>', '<br />'), "\n", $string);
	}

	/**
	 * Converts text seperated by double new lines to paragraphs.
	 * 
	 * @param string $string
	 * @return string|void
	 */
	public static function nl2p ($string)
	{
		if (trim($string) !== '')
		{
			return '<p>'.preg_replace(array("/([\n]{2,})/i", "/([^>])\n([^<])/i"), array("</p>\n<p>", '$1<br>$2'), trim($string)).'</p>';
		}
	}

	/**
	 * Increments a certain format of string.
	 * String should be: string_number. Ex: file_1, file_10, etc.
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function increment ($string)
	{
		if (preg_match('/(.+)_(\d+)/i', $string, $matches))
		{
			$digit = (int) $matches[2];
			$digit++;
			return $matches[1].'_'.$digit;
		}
		else
		{
			return $string.'_1';
		}
	}

	/**
	 * Decrements a certain format of string.
	 * String should be: string_number. Ex: file_1, file_10, etc.
	 * 
	 * @param string $string
	 * @return string|void
	 */
	public static function decrement ($string)
	{
		if (preg_match('/(.+)_(\d+)/i', $string, $matches))
		{
			$string = $matches[1];
			$digit = (int) $matches[2];
			$digit--;

			// When the digit is higher than 0, return
			// it in the normal form. Otherwise, return
			// just the string without digit.
			if ($digit > 0)
			{
				return $string.'_'.$digit;
			}
			else
			{
				return $string;
			}
		}
	}

	/**
	 * Truncates the string.
	 * 
	 * @param string $string
	 * @param int $limit
	 * @param string $append Characters to append to the end of the string
	 * @return string
	 */
	public static function limit ($string, $limit = 100, $append = '...')
	{
		if (strlen($string) > $limit)
		{
			return substr($string, 0, $limit).$append;
		}

		return $string;
	}

	/**
	 * Limits the string into a certain number of words.
	 * 
	 * @param string $string
	 * @param int $limit
	 * @param string $append Characters to append to the end of the string
	 * @return string
	 */
	public static function limit_words ($string, $limit = 20, $append = '...')
	{
		preg_match('/^\s*+(?:\S++\s*+){1,' . $limit . '}/', $string, $matches);

		if (isset($matches[0]) and strlen($matches[0]) < strlen($string))
		{
			return trim($matches[0]).$append;
		}

		return $string;
	}

	/**
	 * Masks a string.
	 * 
	 * @param string $string
	 * @param string $mask Mask character
	 * @return string
	 */
	public static function mask ($string, $mask = '*')
	{
		$mask_length = round(strlen($string) * 0.7);

		return str_repeat($mask, $mask_length).substr($string, $mask_length);
	}

	/**
	 * Makes a URL search engine friendly.
	 * 
	 * @param string $string
	 * @param string $spaces How spaces should be treated
	 * @return string
	 */
	public static function sef ($string, $spaces = '-')
	{
		$string = strtolower($string);
		$string = preg_replace('|[^a-z0-9]|', $spaces, $string);
		$string = preg_replace('|\\'.$spaces.'+|', $spaces, $string);
		
		return $string;
	}

	/**
	 * Wraps a string occurrence with delimiters (default <b>).
	 * 
	 * @param string $string
	 * @param string $highlight Substring to be highlighted
	 * @param array $delimiters An array with two elements, one for the open tag and the second for the closing tag
	 * @return string
	 */
	public static function highlight ($string, $highlight, $delimiters = null)
	{
		$open = '<b>';
		$close = '</b>';

		// Delimiters should be a two elements array.
		if (isset($delimiters) and is_array($delimiters) and count($delimiters) == 2)
		{
			$open = $delimiters[0];
			$close = $delimiters[1];
		}

		return str_replace($highlight, $open.$highlight.$close, $string);
	}

	/**
	 * Alternates between array elements and returns one.
	 * The first call returns the first element, the second call
	 * returns the second element and so on.
	 * 
	 * @return $string
	 */
	public static function alternate ()
	{
		// Elements can be passed as either a list
		// of arguments, or an array.
		$args = func_get_args();

		if (is_array($args[0]))
		{
			$args = $args[0];
		}

		// If it reached the end, start from the beggining.
		if (static::$alternate == count($args))
		{
			static::$alternate = 0;
		}

		$string = $args[static::$alternate];
		static::$alternate++;

		return $string;
	}

	/**
	 * Parses a string with parameters. For anonymous parameters
	 * (:1, :2) parameters should be passed as a list of function
	 * arguments. Named parameters (:name) should be passed as
	 * an associative array.
	 * 
	 * @return string
	 */
	public static function param ()
	{
		$args = func_get_args();

		// The string is always the first argument.
		$string = $args[0];
		unset($args[0]);

		// If the second argument isn't an array, it iterates
		// through the list of arguments.
		if (!is_array($args[1]))
		{
			// Matches all unamed parameters with the :digit format.
			if (preg_match_all('|:\d|', $string, $matches))
			{
				$params = $matches[0];

				$i = 1;
				foreach ($params as $param)
				{
					if (isset($args[$i]))
					{
						$string = str_replace($param, $args[$i], $string);
					}

					$i++;
				}
			}
		}
		// Otherwise, iterates through the array parameters.
		else
		{
			foreach ($args[1] as $param => $value)
			{
				$string = str_replace(':'.$param, $value, $string);
			}
		}

		return $string;
	}

	/**
	 * Generates a UUID.
	 * 
	 * @return string
	 */
	public static function uuid()
	{
    	return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        				mt_rand( 0, 0xffff ),
        				mt_rand( 0, 0x0fff ) | 0x4000,
        				mt_rand( 0, 0x3fff ) | 0x8000,
        				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    	);
	}

}