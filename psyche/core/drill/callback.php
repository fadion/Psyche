<?php
namespace Psyche\Core\Drill;

/**
 * Drill ORM Callbacks
 * 
 * A very simple class that listens for callback
 * triggers and executes the right model method.
 * It could have been a single Drill method, but
 * it was packed in a class for extensibility.
 *
 * @package Psyche\Core\Drill\Callback
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Callback
{

	/**
	 * Observes for callbacks and triggers the model method.
	 * 
	 * @param string $type Callback type
	 * @param Model $drill The model instance
	 */
	public static function observe ($type, $drill)
	{
		if (method_exists($drill, $type))
		{
			$drill->$type();
		}
	}

}