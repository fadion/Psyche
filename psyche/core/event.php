<?php
namespace Psyche\Core;

/**
 * Gives Events to PHP
 * 
 * A very simple approach to event handling. It uses closures for the actions
 * of the event listeners and allows as many arguments as needed for them.
 * As such, it can be thought of as a simple Observer for notifying observer
 * objects of changes.
 *
 * @package Psyche\Core\Event
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Event
{

	/**
	 * @var array Holds event listeners.
	 */
	protected static $events = array();

	/**
	 * Adds a listener for an event.
	 * 
	 * @param string $name Name of the event to listen for
	 * @param closure The closure that will be executed
	 * @return void
	 */
	public static function on ($name, $callback)
	{
		if (is_callable($callback))
		{
			static::$events[$name][] = $callback;
		}
	}

	/**
	 * Clears all listeners for an event.
	 * 
	 * @param string $name
	 * @return void
	 */
	public static function clear ($name)
	{
		if (isset(static::$events[$name]))
		{
			unset(static::$events[$name]);
		}
	}

	/**
	 * Clears all event listeners.
	 * 
	 * @return void
	 */
	public static function clear_all ()
	{
		static::$events = array();
	}

	/**
	 * Checks if an event has any listeners.
	 * 
	 * @param string $name
	 * @return bool
	 */
	public static function has ($name)
	{
		return isset(static::$events[$name]);
	}

	/**
	 * Adds a listener to an event and clears any
	 * previous listeners.
	 * 
	 * @param string $name
	 * @param closure $callback
	 * @return void
	 */
	public static function bind ($name, $callback)
	{
		static::clear($name);
		static::on($name, $callback);
	}

	/**
	 * Triggers all listeners of an event.
	 * 
	 * @param string $name
	 * @param array|string $arguments Arguments passed to listener's closure. Can be a single string or array
	 * @param bool $all Defines if all listeners or just one should be triggered. Used by ::first()
	 */
	public static function trigger ($name, $arguments = array(), $all = true)
	{
		$return = array();

		// If it's a single string argument, put it in an array
		// so it can be iterated below.
		if (!is_array($arguments))
		{
			$arguments = array($arguments);
		}

		if (isset(static::$events[$name]))
		{
			// If all listeners should be triggered.
			if ($all)
			{
				// As an event can have multiple listeners, they're iterated
				// and their closure is executed.
				foreach (static::$events[$name] as $callback)
				{
					$return[] = call_user_func_array($callback, $arguments);
				}

				// Filters empty return values from closures. For events that
				// change values, this makes it easier to check if the trigger
				// returned any real value.
				$return = array_filter($return, function($val)
				{
					if (!isset($val))
					{
						return false;
					}

					return true;
				});
			}
			// Or just the first.
			else
			{
				$return = call_user_func_array(static::$events[$name][0], $arguments);
			}
		}

		return $return;
	}

	/**
	 * Triggers only the first listener of an event.
	 */
	public static function first ($name, $arguments)
	{
		return static::trigger($name, $arguments, false);
	}

}