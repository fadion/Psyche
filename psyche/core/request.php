<?php
namespace Psyche\Core;

/**
 * Handles HTTP Requests
 * 
 * Some easy to use methods to access HTTP Requests.
 *
 * @package Psyche\Core\Request
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Request {

	/**
	 * Returns a single or an array with all the
	 * GET parameters.
	 * 
	 * @param string $name GET key
	 * @return string|array|bool
	 */
	public static function get ($name = null)
	{
		$return = false;

		if (!isset($name))
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

	/**
	 * Returns a single or an array with all the
	 * POST parameters.
	 * 
	 * @param string $name POST key
	 * @return string|array|bool
	 */
	public static function post ($name = null)
	{
		$return = false;

		if (!isset($name))
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

	/**
	 * Returns a single value or an array with all the
	 * PUT parameters.
	 * 
	 * @param string $name POST key
	 * @return string|array|bool
	 */
	public static function put ($name = null)
	{
		$return = false;

		if (static::is('put'))
		{
			// Gets PUT data from the stream and parses it
			// into an array.
			parse_str(file_get_contents("php://input"), $put);

			if (!isset($name))
			{
				if (count($put))
				{
					$return = $put;
				}
			}
			else
			{
				if (array_key_exists($name, $put))
				{
					$return = $put[$name];
				}
			}
		}

		return $return;
	}

	/**
	 * Returns a single value or an array with all the
	 * DELETE parameters.
	 * 
	 * @param string $name DELETE key
	 * @return string|array|bool
	 */
	public static function delete ($name = null)
	{
		$return = false;

		if (static::is('delete'))
		{
			// Gets PUT data from the stream and parses it
			// into an array.
			parse_str(file_get_contents("php://input"), $delete);

			if (!isset($name))
			{
				if (count($delete))
				{
					$return = $delete;
				}
			}
			else
			{
				if (array_key_exists($name, $delete))
				{
					$return = $delete[$name];
				}
			}
		}

		return $return;
	}

	/**
	 * Returns a single or an array with all the
	 * FILES parameters. It supports sub array keys
	 * with the "name.key" format.
	 * 
	 * @param string $name FILES key
	 * @return string|array|bool
	 */
	public static function files ($name = null)
	{
		$return = false;

		if (!isset($name))
		{
			if (count($_FILES))
			{
				$return = $_FILES;
			}
		}
		else
		{
			$key = null;

			// Fixes the "name.key" format for sub array access.
			if ((bool) strpos($name, '.') == true)
			{
				list($name, $key) = explode('.', $name);
			}

			if (array_key_exists($name, $_FILES))
			{
				if (!isset($key))
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

	/**
	 * Returns any set HTTP request with the option
	 * to exclude certain requests.

	 * @return array
	 */
	public static function all ()
	{
		$get = array();
		$post = array();
		$files = array();

		// Excludes can be set as single arguments.
		$excludes = func_get_args();

		// Request values will only be returned if they arent'
		// in the exclude list and are set.
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

	/**
	 * Determines if the http request is the same as the one
	 * provided.
	 * 
	 * @param string $name The request to be checked
	 * @return bool
	 */
	public static function is ($name)
	{
		if ($_SERVER['REQUEST_METHOD'] == strtoupper($name))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns the http request method.
	 * 
	 * @return bool
	 */
	public static function method ($name)
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Returns a single or an array with all the
	 * SERVER parameters.
	 * 
	 * @param string $name SERVER key
	 * @return string|array|bool
	 */
	public static function server ($name = null)
	{
		$return = false;

		if (!isset($name))
		{
			$return = $_SERVER;
		}
		else
		{
			$name = strtoupper($name);

			if (array_key_exists($name, $_SERVER))
			{
				$return = $_SERVER[$name];
			}
		}

		return $return;
	}

	/**
	 * Attemps to get the visitor's IP.
	 * 
	 * @return string
	 */
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

	/**
	 * Returns the referer.
	 * 
	 * @return string
	 */
	public static function referer ()
	{
		return static::server('HTTP_REFERER');
	}

	/**
	 * Determines if the request is from a XMLHTTPREQUEST object.
	 * Most Javascript libraries create a correct response, but
	 * it's not something to be relied for sensitive data.
	 * 
	 * @return bool
	 */
	public static function ajax ()
	{
		$return = false;

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
		{
			$return = true;
		}

		return $return;
	}

	/**
	 * Determines if the request is secure.
	 * 
	 * @return bool
	 */
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