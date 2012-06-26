<?php
namespace Psyche\Core;

/**
 * Cookie Helper
 * 
 * Provides some abstraction and facilities for working with cookies.
 *
 * @package Psyche\Core\Cookie
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Cookie
{

	/**
	 * Creates a cookie.
	 * 
	 * @param string $name
	 * @param string $value
	 * @param int $expire Expiration time in minutes
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 * 
	 * @return void
	 */
	public static function set ($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
	{
		if ($expire !== 0 and $expire > 0)
		{
			$expire = time() + $expire * 60;
		}

		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}

	/**
	 * Return the value of a cookie.
	 * 
	 * @param string $name
	 * 
	 * @return void|mixed
	 */
	public static function val ($name)
	{
		if (isset($_COOKIE[$name]))
		{
			return $_COOKIE[$name];
		}
	}

	/**
	 * Deletes a cookie.
	 * 
	 * @param string $name
	 * 
	 * @return void
	 */
	public static function delete ($name)
	{
		static::set($name, '', -7200);
	}

	/**
	 * Sets a cookie for "forever". Actually for 1 year.
	 * 
	 * @param string $name
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function forever ($name, $value)
	{
		$forever = time() + 60 * 24 * 365;

		static::set($name, $value, $forever);
	}

	/**
	 * Checks if a cookie with the given name exists.
	 * 
	 * @param string $name
	 * 
	 * @return bool
	 */
	public static function has ($name)
	{
		$return = false;

		if (isset($_COOKIE[$name]))
		{
			$return = true;
		}

		return $return;
	}

}