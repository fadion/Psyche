<?php
class Cookie {

	public static function set ($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false) {
		if ($expire !== 0 and $expire > 0) {
			$expire = time() + $expire * 60;
		}

		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}

	public static function val ($name) {
		if (isset($_COOKIE[$name])) {
			return $_COOKIE[$name];
		}
	}

	public static function delete ($name) {
		static::set($name, '', -7200);
	}

	public static function forever ($name, $value) {
		$forever = time() + 60 * 60 * 24 * 365;

		static::set($name, $value, $forever);
	}

	public static function has ($name) {
		$return = false;

		if (isset($_COOKIE[$name])) {
			$return = true;
		}

		return $return;
	}

}