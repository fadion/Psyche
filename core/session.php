<?php
namespace FW\Core;

class Session
{

	public static function set ($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	public static function val ($name)
	{
		if (isset($_SESSION[$name]))
		{
			return $_SESSION[$name];
		}
	}

	public static function delete ($name)
	{
		unset($_SESSION[$name]);
	}

	public static function has ($name)
	{
		$return = false;

		if (isset($_SESSION[$name]))
		{
			$return = true;
		}

		return $return;
	}

	public static function clear ()
	{
		$_SESSION = array();

		if (ini_get("session.use_cookies"))
		{
		    $params = session_get_cookie_params();
		    setcookie(session_name(), '', time() - 42000,
		        $params["path"], $params["domain"],
		        $params["secure"], $params["httponly"]
		    );
		}

		session_destroy();
	}

	public static function id ()
	{
		return session_id();
	}

	public static function regenerate ($delete_old_session = false)
	{
		return session_regenerate_id($delete_old_session);
	}

}