<?php
namespace Psyche\Core;

/**
 * URI Helper
 * 
 * Useful to get information about the current URI, such
 * as segments, the full path, etc.
 *
 * @package Psyche\Core\Uri
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Uri
{

	/**
	 * Returns the current URI, discarding the domain name.
	 * 
	 * @return string
	 */
	public static function current ()
	{
		$url = static::parse_url();

		// The array pieces are returned as a string
		// if any are set. Otherwise, returns a slash.
		if (is_array($url))
		{
			$url = implode('/', $url);
		}
		else
		{
			$url = '/';
		}

		return $url;
	}

	/**
	 * Returns the site's full URI.
	 * 
	 * @param bool $query_string Should the query string be included or not
	 * @return string
	 */
	public static function full ($query_string = true)
	{
		$url = static::parse_url();

		if (is_array($url))
		{
			$url = implode('/', $url);
		}

		$url = config('path').$url;

		if (isset($_SERVER['QUERY_STRING']) and $query_string)
		{
			$url .= '?'.$_SERVER['QUERY_STRING'];
		}

		return $url;
	}

	/**
	 * Returns the site's path. This is just a shortcut
	 * to the "path" set in the config file.
	 * 
	 * @return string
	 */
	public static function path ()
	{
		return config('path');
	}

	/**
	 * Returns a URI segment where position 1 is the leftmost
	 * part of the URI with the domain name discarded.
	 * 
	 * @param int $loc Segment position
	 * @return bool|string
	 */
	public static function segment ($loc = 1)
	{
		$url = static::parse_url();
		$loc -= 1;

		if (!count($url) or !array_key_exists($loc, $url))
		{
			return false;
		}
		
		return $url[$loc];
	}
	
	/**
	 * Returns a URI segment where position 1 is the rightmost
	 * part of the URI with the domain name discarded.
	 * 
	 * @param int $loc Segment position
	 * @return bool|string
	 */
	public static function rsegment ($loc = 1)
	{
		$url = static::parse_url();
		$loc -= 1;
		
		if (!count($url)) return false;
		
		$url = array_reverse($url);
		
		if (!array_key_exists($loc, $url)) return false;
		
		return $url[$loc];
	}

	/**
	 * Returns an array containing all the URI segments.
	 * 
	 * @return bool|array
	 */
	public static function to_array ()
	{
		$url = static::parse_url();

		if (!count($url)) return false;

		return $url;
	}
	
	/**
	 * Returns an array containing all the URI segments
	 * as key=>value pairs.
	 * 
	 * @return bool|array
	 */
	public static function to_assoc ()
	{
		$url = static::parse_url();
		
		if (!count($url)) return false;
		
		$assoc = array();
		$i = 0;
		
		// Adds the current piece as key and
		// the next piece as value.
		foreach ($url as $val)
		{
			if ($i % 2 == 0)
			{
				$assoc[$val] = $url[$i+1];
			}
			
			$i++;
		}
		
		if (!count($assoc)) return false;
		
		return $assoc;
	}

	/**
	 * Checks if the current URI is as specified.
	 * 
	 * @param string $search
	 * @return bool
	 */
	public static function is ($search)
	{
		$url = static::parse_url();
		$search = explode('/', $search);
		$return = false;

		// Number of uri pieces should be the same
		// as those being searched for.
		if (count($url) == count($search))
		{
			$i = 0;
			foreach ($search as $val)
			{
				if ($val == $url[$i] or $val == '-any')
				{
					$return = true;
				}
				else
				{
					$return = false;
					break;
				}

				$i++;
			}
		}

		return $return;
	}

	/**
	 * Makes an absolute URI. It basically adds the base URI
	 * to the specified one and gives the option for generate
	 * secure URIs.
	 * 
	 * @param string $url
	 * @param bool $https
	 * @return string
	 */
	public static function make ($url, $https = false)
	{
		$url = config('path').trim($url, ' /').'/';

		if ($https)
		{
			$url = str_replace('http://', 'https://', $url);
		}

		return $url;
	}
	
	/**
	 * Turns the URI into segments.
	 * 
	 * @return array
	 */
	public static function parse_url ()
	{
		// Get the URI from the server constants.
		// Checks each until a valid one is found.
		if (isset($_SERVER['PATH_INFO']))
		{
			$uri = $_SERVER['PATH_INFO'];	
		}
		elseif (isset($_SERVER['ORIG_PATH_INFO']))
		{
			$uri = $_SERVER['ORIG_PATH_INFO'];
		}
		else
		{
			$uri = $_SERVER['PHP_SELF'];
		}

		if (isset($uri))
		{
			// When no rewrite is made (index.php is present), take anything
			// that's after it.
			if (strpos($uri, 'index.php') !== false)
			{
				$uri = substr($uri, strpos($uri, 'index.php') + strlen('index.php'));
			}

			$uri = trim($uri, ' /');

			// Explode to pieces if not empty.
			if ($uri != '')
			{
				$uri = explode('/', $uri);
				return $uri;
			}
		}

		return false;
	}

}