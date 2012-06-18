<?php
namespace FW\Core;

/**
 * Class Autoloader
 * 
 * Attempts to load namespaced classes. It is psr-0 compliant, meaning
 * that the class namespace and underscore in it's name are translated
 * to folders.
 *
 * @package FW\Core\Autoload
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Autoload
{

	/**
	 * Constructor. Registers the autoload method.
	 * 
	 * @return void
	 */
	public function __construct ()
	{
		spl_autoload_register(array($this, 'load'));
	}

	/**
	 * Factory static method.
	 * 
	 * @return object
	 */
	public static function start ()
	{
		return new static;
	}

	/**
	 * Tries to include the class file.
	 * 
	 * @param string $class Class name
	 * 
	 * @return void
	 */
	private function load ($class)
	{
		$path = $this->clean($class);

		// Models' path can't be translated from it's napespace. The correct
		// path is set.
		if (strpos($path, 'models/') !== false)
		{
			$path = str_replace('models/', config('models path'), $path);
		}

		if (file_exists($path))
		{
			require_once $path;
		}
		else {
			trigger_error("Class $class not found", FATAL);
			return;
		}
	}

	/**
	 * Class names with a full qualified namespace and/or underscores in their names
	 * are translated into directories.
	 * 
	 * @param string $class Class name
	 * 
	 * @return string
	 */
	private function clean ($class) {
		$class = strtolower(trim($class, '\\'));
		$class_name = substr($class, strrpos($class, '\\') + 1);

		$path = substr($class, strpos($class, '\\') + 1);
		$path = str_replace(array('\\', '_'), '/', $path);
		$path .= '.php';

		return $path;
	}

}