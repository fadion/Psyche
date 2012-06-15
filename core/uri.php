<?php
namespace FW\Core;

class Uri
{

	public static function current ()
	{
		$url = static::parse_url();

		if (is_array($url))
		{
			$url = implode('/', $url);
			return $url;
		}
	}

	public static function full ()
	{
		$url = static::parse_url();

		if (is_array($url))
		{
			$url = implode('/', $url);
			return config('path') . $url;
		}
	}

	public static function path ()
	{
		return config('path');
	}

	public static function segment ($loc = 1)
	{
		$url = static::parse_url();
		$loc -= 1;
		
		if (!count($url)) return false;
		
		if (!array_key_exists($loc, $url)) return false;
		
		return $url[$loc];
	}
	
	public static function rsegment ($loc = 1)
	{
		$url = static::parse_url();
		$loc -= 1;
		
		if (!count($url)) return false;
		
		$url = array_reverse($url);
		
		if (!array_key_exists($loc, $url)) return false;
		
		return $url[$loc];
	}
	
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

	public static function is ($search)
	{
		$url = static::parse_url();
		$search = explode('/', $search);
		$return = false;

		if (count($url) == count($search) or in_array('*', $search) and count($url) >= count($search))
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