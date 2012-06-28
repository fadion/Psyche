<?php
namespace Psyche\Core;

class Response
{

	private static $output = '';

	public static function write ($output, $method = null, $parameters = null)
	{
		ob_start();

		if (is_object($output) and !is_null($method))
		{
			if (is_null($parameters))
			{
				$output->$method();
			}
			else
			{
				call_user_func_array(array($output, $method), $parameters);
			}
		}
		else
		{
			echo $output;
		}

		static::$output .= ob_get_clean();
	}

	public static function redirect ($url, $delay = null)
	{
		if ((bool) strpos($url, 'http://') == false and (bool) strpos($url, 'https://') == false)
		{
			$url = config('path') . $url . '/';
		}

		if (!is_null($delay))
		{
			echo '<meta http-equiv="refresh" content="'.$delay.'; url='.$url.'">';
		}
		else
		{
			header("Location: $url");
		}

		exit;
	}

	public static function output ()
	{
		echo static::$output;
	}

	public static function error ($error = '404')
	{
		echo 'Error ' . $error;
		
	}

}