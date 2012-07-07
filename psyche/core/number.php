<?php
namespace Psyche\Core;

/**
 * Numbers Helper
 * 
 * Provides functionality for number formatting, currency, bytes, etc.
 *
 * @package Psyche\Core\Number
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Number
{

	/**
	 * Returns a correctly formated number to be used as currency.
	 * 
	 * @param int $number
	 * @param string $currency
	 * @param array $parameters
	 * @return string
	 */
	public static function currency ($number, $currency = 'usd', $parameters = null)
	{
		$default_parameters = array('position'=>'before', 'thousands'=>',', 'decimals'=>'.');

		// Undefined user parameters are taken from the default ones.
		// Merging the arrays retains undefined keys.
		if (is_array($parameters) and count($parameters))
		{
			$parameters = array_merge($default_parameters, $parameters);
		} else {
			$parameters = $default_parameters;
		}

		$number = number_format($number, 2, $parameters['decimals'], $parameters['thousands']);
		$currency = strtolower($currency);

		// Returns a currency symbol based on a limited,
		// known list.
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

		// Places the currency symbol before or after
		// the numbers, depending on the parameter.
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

	/**
	 * Formats a number to the given pattern.
	 * 
	 * @param int $number
	 * @param string $format The pattern. It can contain # or 0 (zeros).
	 * @return bool|string
	 */
	public static function format ($number, $format)
	{
		// The number length should match the number of replacements
		// in the pattern.
		if (substr_count($format, '#') != strlen($number) and substr_count($format, '0') != strlen($number))
		{
			return false;
		}

		$number = str_split($number);

		// # or zeros are replaced with '%d' to be used in sprintf().
		$format = str_replace(array('#', '0'), '%d', $format);
		$params = array_merge((array) $format, $number);

		return call_user_func_array('sprintf', $params);
	}

	/**
	 * Converts bytes to human readable quantities.
	 * Ex: 1024 bytes will return 1KB.
	 * 
	 * @param int $bytes
	 * @param int $precision The required floating point precision
	 * @return string
	 */
	public static function bytes ($bytes, $precision = 2)
	{
	    $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
	    $return = @round($bytes / pow(1024, ($i = floor(log($bytes, 1000)))), $precision).' '.$unit[$i];

	    return $return;
	}

}