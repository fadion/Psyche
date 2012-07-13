<?php
namespace Psyche\Core;
use Psyche\Core\Debug\Gizmo\Gizmo,
	Psyche\Core\Debug\ChromePHP,
	Psyche\Core\Debug\FirePHP;

/**
 * Debugger
 * 
 * Factory for initializing debug drivers.
 *
 * @package Psyche\Core\Debug
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Debug
{

	/**
	 * Renders the Gizmo Toolbar.
	 * 
	 * @return void
	 */
	public static function toolbar ()
	{
		Gizmo::render_toolbar();
	}

	/**
	 * Returns an instance of a debug driver.
	 * 
	 * @param string $driver
	 * @return object
	 */
	public static function open ($driver = null)
	{
		if (!isset($driver))
		{
			$driver = config('debug driver');
		}

		$method = $driver.'_driver';

		if (method_exists(__CLASS__, $method))
		{
			return static::$method();
		}
		else
		{
			throw new \Exception(sprintf("The debug driver %s isn't supported.", $driver));
		}
	}

	/**
	 * Initializes the Gizmo debug driver.
	 * 
	 * @return Cache\File
	 */
	protected static function gizmo_driver ()
	{
		return new Gizmo;
	}

	/**
	 * Initializes the ChromePHP debug driver.
	 * 
	 * @return Cache\File
	 */
	protected static function chromephp_driver ()
	{
		return ChromePHP::getInstance();
	}

	/**
	 * Initializes the FirePHP debug driver.
	 * 
	 * @return Cache\File
	 */
	protected static function firephp_driver ()
	{
		return FirePHP::getInstance(true);
	}

}