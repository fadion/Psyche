<?php
namespace Psyche\Core\View;

/**
 * Partial Views
 * 
 * Makes template inheritance possible with a very simple
 * technique. It gets the contents of a partial block via
 * output buffering and returns it to a reserve declaration.
 * There is practically on overhead, as it works with native
 * PHP includes.
 *
 * @package Psyche\Core\View\Partial
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Partial
{

	/**
	 * @var array Holds the contents of each partial.
	 */
	protected static $partials = array();

	/**
	 * @var string The currently active partial.
	 */
	protected static $active;

	/**
	 * Replaces a reserve declaration with the appropriate
	 * partial. When no partial block (or inline) exists,
	 * the default value is returned.
	 * 
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public static function reserve ($name, $default = null)
	{
		if (isset(static::$partials[$name]))
		{
			return static::$partials[$name];
		}
		else
		{
			if (isset($default))
			{
				return $default;
			}
		}
	}

	/**
	 * The start of a partial block. Adds a key to the
	 * partial (so the class knowns it exists), makes itself
	 * the active one and starts output buffering.
	 * 
	 * @param string $name
	 * @return void
	 */
	public static function begin ($name)
	{
		static::$partials[$name] = true;
		static::$active = $name;
		ob_start();
	}

	/**
	 * Returns the contents of the buffer and adds it to
	 * the active partial key.
	 * 
	 * @return void
	 */
	public static function end ()
	{
		static::$partials[static::$active] = ob_get_clean();
	}

	/**
	 * Adds an inline partial. These are simple, one line
	 * contents and are here mostly as a shorthand to
	 * declaring a partial block.
	 * 
	 * @param $name
	 * @param $content
	 * @return void
	 */
	public static function inline ($name, $content)
	{
		static::$partials[$name] = $content;
	}

}