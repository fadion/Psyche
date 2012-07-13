<?php
/**
 * Global helper to print localized strings.
 * 
 * @return string
 */
function __ ()
{
	return call_user_func_array('Psyche\Core\Locale::get', func_get_args());
}

/**
 * Global helper to get configuration keys.
 * 
 * @return string
 */
function config ($key, $value = null)
{
	if (!isset($value))
	{
		return Psyche\Core\Config::get($key);
	}
	else
	{
		return Psyche\Core\Config::set($key, $value);
	}
}

/**
 * Global helper to print debug messages using the default driver.
 * 
 * @return string
 */
function _d ($var, $label = null)
{
	Psyche\Core\Debug::open()->log($var, $label);
}