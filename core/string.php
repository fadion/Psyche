<?php
namespace FW\Core;

class String
{

	private static $alphanumeric = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	private static $alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	private static $hex = '0123456789abcdef';
	private static $num = '0123456789';
	private static $symbols = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';

	public static function random ($type = 'alphanumeric', $size = 16)
	{
		if ($type == 'mix')
		{
			$string = static::$alphanumeric.static::$symbols;
		}
		else
		{
			$string = static::${$type};
		}

		$return = '';

		for ($i = 0, $length = strlen($string); $i < $size; $i++)
		{
			$return .= $string[mt_rand(0, $length)];
		}

		echo $return;
	}

	public static function nl2br ($string)
	{
		return nl2br($string);
	}

	public static function br2nl ($string)
	{
		return str_replace(array('<br>', '<br/>', '<br />'), "\n", $string);
	}

	public static function limit ($string, $limit = 100, $prepend = '...')
	{
		if (strlen($string) > $limit)
		{
			return substr($string, 0, $limit).$prepend;
		}

		return $string;
	}

	public static function limit_words ($string, $limit = 20, $prepend = '...')
	{
		preg_match('/^\s*+(?:\S++\s*+){1,' . $limit . '}/', $string, $matches);

		if (isset($matches[0]) and strlen($matches[0]) < strlen($string))
		{
			return trim($matches[0]).$prepend;
		}

		return $string;
	}

	public static function mask ($string, $mask = '*')
	{
		$mask_length = round(strlen($string) * 0.7);

		return str_repeat($mask, $mask_length).substr($string, $mask_length);
	}

	public static function sef ($string, $spaces = '-')
	{
		$string = strtolower($string);
		$string = preg_replace('|[^a-z0-9]|', $spaces, $string);
		$string = preg_replace('|\\'.$spaces.'+|', $spaces, $string);
		
		return $string;
	}

	public static function highlight ($string, $highlight, $delimiters = null)
	{
		$open = '<b>';
		$close = '</b>';

		if (!is_null($delimiters) and is_array($delimiters) and count($delimiters) == 2)
		{
			$open = $delimiters[0];
			$close = $delimiters[1];
		}

		return str_replace($highlight, $open.$highlight.$close, $string);
	}

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