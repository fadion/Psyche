<?php
namespace FW\Core;

class Request {

	public static function get ($name = null)
	{
		$return = false;

		if (is_null($name))
		{
			if (count($_GET))
			{
				$return = $_GET;
			}
		}
		else
		{
			if (array_key_exists($name, $_GET))
			{
				$return = $_GET[$name];
			}
		}

		return $return;
	}

	public static function post ($name = null)
	{
		$return = false;

		if (is_null($name))
		{
			if (count($_POST))
			{
				$return = $_POST;
			}
		}
		else
		{
			if (array_key_exists($name, $_POST))
			{
				$return = $_POST[$name];
			}
		}

		return $return;
	}

	public static function files ($name = null)
	{
		$return = false;

		if (is_null($name))
		{
			if (count($_FILES))
			{
				$return = $_FILES;
			}
		}
		else
		{
			$key = null;

			if ((bool) strpos($name, '.') == true)
			{
				list($name, $key) = explode('.', $name);
			}

			if (array_key_exists($name, $_FILES))
			{
				if (is_null($key))
				{
					$return = $_FILES[$name];
				}
				else
				{
					$return = $_FILES[$name][$key];
				}
			}
		}

		return $return;
	}

	public static function all ()
	{
		$get = array();
		$post = array();
		$files = array();

		$excludes = func_get_args();

		if (!in_array('get', $excludes) and static::get())
		{
			$get = static::get();
		}

		if (!in_array('post', $excludes) and static::post())
		{
			$post = static::post();
		}

		if (!in_array('files', $excludes) and static::files())
		{
			$files = static::files();
		}

		return array_merge($get, $post, $files);
	}

	public static function server ($name = null)
	{
		if (is_null($name))
		{
			return $_SERVER;
		}
		else
		{
			$name = strtoupper($name);

			if (array_key_exists($name, $_SERVER))
			{
				return $_SERVER[$name];
			}
		}
	}

	public static function ip ()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	public static function referer ()
	{
		return static::server('HTTP_REFERER');
	}

	public static function ajax ()
	{
		$return = false;

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
		{
			$return = true;
		}

		return $return;
	}

	public static function secure ()
	{
		$return = false;

		if (!empty($_SERVER['HTTPS']) and ($_SERVER['HTTPS'] !== 'off' or $_SERVER['SERVER_PORT'] == 443))
		{
			$return = true;
		}

		return $return;
	}

}