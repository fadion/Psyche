<?php
namespace FW\Core;
use FW\Core\CFG as CFG;

class Autoload
{

	private $classes = array();

	public function __construct ()
	{
		spl_autoload_register(array($this, 'load'));
	}

	public static function start ()
	{
		return new static;
	}

	private function load ($class)
	{
		$path = $this->clean($class);

		if (strpos($path, 'models/') !== false) {
			$path = str_replace('models/', CFG::MODELS_PATH, $path);
		}

		if (file_exists($path))
		{
			require_once $path;
		}
		else {
			trigger_error("Class $class not found", FATAL);
			return false;
		}
	}

	private function clean ($class) {
		$class = strtolower(trim($class, '\\'));
		$class_name = substr($class, strrpos($class, '\\') + 1);

		$path = substr($class, strpos($class, '\\') + 1);
		$path = str_replace(array('\\', '_'), '/', $path);
		$path .= '.php';

		return $path;
	}

}