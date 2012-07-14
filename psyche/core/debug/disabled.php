<?php
namespace Psyche\Core\Debug;

/**
 * Activates when debug is disabled.
 * 
 * Overrides any method call to a debug driver so that
 * no messages are printed.
 *
 * @package Psyche\Core\Debug\Disabled
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Disabled
{

	/**
	 * @var Disabled singleton instance.
	 */
	protected static $instance;

	/**
	 * Returns the singleton instance.
	 * 
	 * @return Disabled
	 */
	public static function get_instance ()
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Overrides any method call and returns itself
	 * just in case of method chaining.
	 */
	public function __call ($method, $arguments)
	{
		return $this;
	}

}