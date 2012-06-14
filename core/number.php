<?php
namespace FW\Core;

class Number
{

	public static function currency ($number, $currency = 'usd', $parameters = null)
	{
		$default_parameters = array('position'=>'before', 'thousands'=>',', 'decimals'=>'.');

		if (is_array($parameters) and count($parameters))
		{
			$parameters = array_merge($default_parameters, $parameters);
		} else {
			$parameters = $default_parameters;
		}

		$number = number_format($number, 2, $parameters['decimals'], $parameters['thousands']);
		$currency = strtolower($currency);

		if ($currency == 'usd')
		{
			$currency = '$';
		}
		elseif ($currency == 'gbp')
		{
			$currency = '&pound;';
		}
		elseif ($currency == 'eur' or $currency == 'euro')
		{
			$currency = '&euro;';
		}
		elseif ($currency == 'yen')
		{
			$currency = '&yen;';
		}
		elseif ($currency == 'cent')
		{
			$currency = '&cent;';
		}
		else
		{
			$currency = strtoupper($currency);
		}

		if ($parameters['position'] == 'before')
		{
			$number = $currency.' '.$number; 
		}
		else
		{
			$number .= ' '.$currency;
		}

		return $number;
	}

	public static function bytes ($bytes, $precision = 2)
	{
	    $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
	    $return = @round($bytes / pow(1000, ($i = floor(log($bytes, 1000)))), $precision).' '.$unit[$i];

	    return $return;
	}

	public static function ibytes ($bytes, $precision = 2)
	{
	    $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
	    $return = @round($bytes / pow(1024, ($i = floor(log($bytes, 1000)))), $precision).' '.$unit[$i];

	    return $return;
	}

}