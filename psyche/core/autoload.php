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
	 * @var array Class aliases cache.
	 */
	protected $aliases;

	/**
	 * Constructor. Registers the autoload method.
	 */
	public function __construct ()
	{
		spl_autoload_register(array($this, 'load'));
	}

	/**
	 * Factory static method.
	 * 
	 * @return Autoload
	 */
	public static function start ()
	{
		return new static;
	}

	/**
	 * Tries to include the class file.
	 * 
	 * @param string $class Class name
	 * @return void
	 */
	protected function load ($class)
	{
		$this->maps();
		$this->aliases();

		// Checks for any defined aliases.
		if (count($this->aliases))
		{
			// If a defined alias matches the called class,
			// it will be aliased and the autoloader will run again.
			if (isset($this->aliases[$class]))
			{
				return class_alias($this->aliases[$class], $class);
			}
		}
		
		$path = $this->clean($class);

		// Models' path can't be translated from it's namespace. The correct
		// path is set.
		if (preg_match('/^models\//i', $path))
		{
			$path = str_replace('models/', config('models path'), $path);
		}
		// As with Models, base controllers need the correct path set.
		elseif (preg_match('/^base\//i', $path))
		{
			$path = str_replace('base/', config('base controllers path'), $path);
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
					// If no extension is set, use '.php' as default.
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
		$file = '../app/config/maps.php';

		if (!isset($this->maps))
		{
			if (file_exists($file))
			{
				$this->maps = include($file);
			}
		}
	}

	/**
	 * Reads the aliases files and passes the returned array
	 * to a variable, so it is cached and read only on the first run.
	 * 
	 * @return array
	 */
	protected function aliases ()
	{
		$file = '../app/config/aliases.php';

		if (!isset($this->aliases))
		{
			if (file_exists($file))
			{
				$this->aliases = include($file);
			}
		}
	}

	/**
	 * Class names with a full qualified namespace and/or underscores in their names
	 * are translated into directories.
	 * 
	 * @param string $class Class name
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