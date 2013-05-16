<?php
namespace Psyche\Core;
use Psyche\Core\Event;

class Response
{

	private static $output = '';

	public static function write ($output, $method = null, $parameters = null)
	{
		ob_start();

		header("Content-type: text/html");

		if (is_object($output) and isset($method))
		{
			if (!isset($parameters))
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

		if (isset($delay))
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
		$filtered = Event::trigger('psyche output', static::$output);

		if (count($filtered))
		{
			foreach ($filtered as $filter)
			{
				static::$output = $filter;
			}
		}

		echo static::$output;
	}

	public static function error ($error = '404')
	{
		echo 'Error ' . $error;
		
	}

}