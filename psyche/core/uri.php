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
	 * @return void|string
	 */
	public static function current ()
	{
		$url = static::parse_url();

		if (is_array($url))
		{
			$url = implode('/', $url);
			return $url;
		}
	}

	/**
	 * Returns the site's full URI.
	 * 
	 * @return void|string
	 */
	public static function full ()
	{
		$url = static::parse_url();

		if (is_array($url))
		{
			$url = implode('/', $url);
			return config('path') . $url;
		}
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
		
		$url = array_slice($url, 2);
		$assoc = array();
		$i = 0;
		
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

		print_r($search);
		print_r($url);

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
	 * Turns the URI into segments.
	 * 
	 * @return array
	 */
	private static function parse_url ()
	{
		if (isset($_GET['s']))
		{
			$url = rtrim($_GET['s'], ' /');
			
			if ($url != '')
			{
				$url = explode('/', $url);
			}
			else
			{
				$url = array();
			}

			return $url;
		}
	}

}