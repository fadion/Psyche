<?php
namespace FW\Core;
use FW\Core\CFG;
use FW\Core\Response;

class View
{

	private $vars;
	private $file;
	private $cache;
	private $tpl_constants;

	public function __construct ($file, $vars)
	{
		$file = CFG::VIEWS_PATH . "$file.php";

		if (!file_exists($file))
		{
			trigger_error('View file not found', ERROR);
			return false;
		}

		$this->file = $file;

		if (!is_null($vars))
		{
			$this->vars = $vars;
		}
	}

	public static function open ($file, $vars = null)
	{
		return new static($file, $vars);
	}

	public function __set ($name, $value)
	{
		$this->vars[$name] = $value;
	}

	public function set ()
	{
		if (func_num_args())
		{
			$args = func_get_args();
			$arg1 = $args[0];
			$arg2 = @$args[1];
			
			if (is_array($arg1))
			{
				foreach ($arg1 as $key=>$val)
				{
					$this->vars[$key] = $val;
				}
			}
			else
			{
				if (!empty($arg1))
				{
					$this->vars[$arg1] = $arg2;
				}
			}
		}

		return $this;
	}

	public function render ()
	{
		$this->tpl_constants();
		$this->custom_tpl_constants();
		
		if (is_array($this->vars))
		{
			extract($this->vars, EXTR_SKIP);
		}

		if (is_array($this->tpl_constants))
		{
			extract($this->tpl_constants, EXTR_SKIP);
		}

		ob_start();
		include($this->file);
		$output = ob_get_clean();
		
		Response::write($output);
	}

	public function exists ($file)
	{
		$file = CFG::VIEWS_PATH . "$file.php";
		if (file_exists($file))
		{
			return true;
		}

		return false;
	}

	private function tpl_constants ()
	{
		$this->tpl_constants['path'] = CFG::PATH;
		$this->tpl_constants['locale'] = CFG::BASE_LOCALE;
	}

	private function custom_tpl_constants ()
	{
		$constants = include('config/constants.php');

		if (count($constants))
		{
			$this->tpl_constants = array_merge($this->tpl_constants, $constants);
		}
	}
	
}