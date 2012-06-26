<?php
namespace Psyche\Core;

/**
 * Class Autoloader
 * 
 * A psr-0 compliant autoloader that attempts to load namespaced classes.
 *
 * @package Psyche\Core\Autoload
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Autoload
{

	/**
	 * @var array Class mappings cache.
	 */
	protected $maps;

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
	protected function load ($class)
	{
		$this->maps();
		
		$path = $this->clean($class);

		// Models' path can't be translated from it's namespace. The correct
		// path is set.
		if (strpos($path, 'models/') !== false)
		{
			$path = str_replace('models/', config('models path'), $path);
		}
		// Psyche's folder relative path is appended to the class name.
		else
		{
			$path = PSYCHE_PATH.$path;
			$packs_path = str_replace('core/', 'packs/', $path);
		}

		if (isset($this->maps))
		{
			// Maps are set as [namespace] => [path]
			foreach ($this->maps as $ns => $map_path)
			{
				if (strtolower(trim($ns, '\\')) == strtolower($class))
				{
					// If not extension is set, use '.php' as default.
					if (pathinfo($map_path, PATHINFO_EXTENSION) == '')
					{
						$map_path .= '.php';
					}

					$path = PSYCHE_PATH.'libraries/'.$map_path;
				}
			}
		}

		if (file_exists($packs_path))
		{
			require_once $packs_path;
		}
		elseif (file_exists($path))
		{
			require_once $path;
		}
		else {
			throw new \Exception("Class $class not found");
		}
	}

	/**
	 * Reads the maps files for external libraries and passes
	 * the returned array to a variable, so it is cached and
	 * read only on the first run.
	 * 
	 * @return array
	 */
	protected function maps ()
	{
		$file = 'config/maps.php';

		if (!isset($this->maps))
		{
			if (file_exists($file))
			{
				$this->maps = include($file);
			}
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
	protected function clean ($class) {
		$class = strtolower(trim($class, '\\'));
		$class_name = substr($class, strrpos($class, '\\') + 1);

		$path = substr($class, strpos($class, '\\') + 1);
		$path = str_replace(array('\\', '_'), '/', $path);
		$path .= '.php';

		return $path;
	}

}