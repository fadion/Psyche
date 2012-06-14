<?php
/**
 * 
 * Legacy Loader. The autoloader does a much better job, is safier and less resource hungry.
 * However I'm not deleting this file as long as we've tested namespaces and the autoloader.
 * 
 * TO BE DELETED
 * 
 */
class Loader {
	
	private static $controllers_path = CFG::CONTROLLERS_PATH;
	private static $controllers_path_extra = '';
	private static $models_path = CFG::MODELS_PATH;
	private static $type = 'objects';
	private static $objects = array(
		'objects' => array(),
		'controllers' => array(),
		'models' => array()
	);

	public static function load ($class, $multi = false) {
		$method = 'single';

		if ($multi == true) {
			$method = 'multi';
		}

		return static::$method($class);
	}

	public static function single ($class) {
		$result = false;
		$class = strtolower($class);

		if (!empty($class) and class_exists($class)) {
			if (!array_key_exists($class, static::$objects[static::$type])) {
				static::$objects[static::$type][$class] = new $class;
			}

			$result = static::$objects[static::$type][$class];
		}

		return $result;
	}

	public static function multi ($class) {
		$result = false;

		$object = static::single($class, static::$type);

		if ($object) {
			$result = clone $object;
		}

		return $result;
	}
	
	public static function model ($class, $multi = false) {
		$result = false;
		$file = static::$models_path . strtolower($class) . '.php';
		$class = static::fix_name($class);
		$class = 'm_' . $class;

		static::$type = 'models';

		if (file_exists($file)) {
			require_once($file);
			$result = static::load($class, $multi);
		}

		static::$type = 'objects';

		return $result;
	}

	public static function controller ($class) {
		$result = false;
		$file = static::$controllers_path . static::$controllers_path_extra . strtolower($class) . '.php';
		$class = static::fix_name($class);
		$class = 'c_' . $class;

		static::$type = 'controllers';

		if (file_exists($file)) {
			require_once($file);
			$result = static::single($class);
		}

		static::$type = 'objects';

		return $result;
	}

	public static function get_controller_path () {
		return static::$controllers_path;
	}

	public static function set_controller_path ($path) {
		static::$controllers_path_extra = $path;
	}

	private static function fix_name ($class) {
		$class_name = $class;
		if ((bool) strpos($class, '/') === true) {
			$class_name = substr($class, strrpos($class, '/') + 1);
		}

		return $class_name;
	}

}