<?php
namespace Psyche\Core;

/**
 * Session Helper
 * 
 * Provides some abstraction and facilities for working with sessions.
 *
 * @package Psyche\Core\Session
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Session
{

	/**
	 * Sets a session variable.
	 * 
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public static function set ($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * Gets the value of a session variable.
	 * 
	 * @param string $name
	 * @param string $default
	 * @return void|string
	 */
	public static function get ($name, $default = null)
	{
		$return = false;

		if (isset($_SESSION[$name]))
		{
			$return = $_SESSION[$name];
		}
		else
		{
			if (isset($default))
			{
				$return = $default;
			}
		}

		return $return;
	}

	/**
	 * Deletes a session variable.
	 * 
	 * @param string $name
	 * @return void
	 */
	public static function delete ($name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * Checks if a session variable exists.
	 * 
	 * @param string $name
	 * @return bool
	 */
	public static function has ($name)
	{
		$return = false;

		if (isset($_SESSION[$name]))
		{
			$return = true;
		}

		return $return;
	}

	/**
	 * Clears all session variables and session cookies.
	 * 
	 * @return void
	 */
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

	/**
	 * Returns the session ID.
	 * 
	 * @return string
	 */
	public static function id ()
	{
		return session_id();
	}

	/**
	 * Regenerates the session ID.
	 * 
	 * @return bool
	 */
	public static function regenerate ($delete_old_session = false)
	{
		return session_regenerate_id($delete_old_session);
	}

}