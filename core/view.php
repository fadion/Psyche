<?php
namespace FW\Core;
use FW\Core\Response;
use FW\Core\Psyc;

/**
 * View Engine
 * 
 * A class that makes working with PHP view files as easy as with template engines.
 * Basically, it provides the options to open a template file and assign its variables.
 * Files are outputed as pure PHP, so there is absolutely no overhead.
 *
 * @package FW\Core\View
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class View
{

	/**
	 * @var array Keeps assigned template variables
	 */
	protected $vars;

	/**
	 * @var string Path of the opened template
	 */
	protected $file;

	/**
	 * @var string Filename of the template file without the path or extension
	 */
	protected $filename;

	/**
	 * @var array Built-in and external template constants
	 */
	protected $tpl_constants;

	/**
	 * Constructor.
	 * 
	 * @param string $file Template filename
	 * @param array $vars Template variables to assign
	 * 
	 * @return void
	 */
	public function __construct ($file, $vars)
	{
		$this->filename = $file;

		$file = config('views path').$file;
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		$exists = true;

		if ($ext == '')
		{
			if (file_exists($file.'.php'))
			{
				$file .= '.php';
			}
			elseif (file_exists($file.'.psy'))
			{
				$file .= '.psy';
			}
			else
			{
				$exists = false;
			}
		}
		else
		{
			if (!file_exists($file))
			{
				$exists = false;
			}
		}

		if (!$exists)
		{
			throw new \Exception(sprintf("View %s doesn't exist", $file));
		}

		if (!is_null($vars))
		{
			$this->vars = $vars;
		}

		$this->file = $file;

		if (pathinfo($file, PATHINFO_EXTENSION) == 'psy')
		{
			$this->file = Psyc::run($file);
		}
	}

	/**
	 * Factory static method.
	 * 
	 * @param string $file Template filename
	 * @param array $vars Template variables to assign
	 * 
	 * @return object
	 */
	public static function open ($file, $vars = null)
	{
		return new static($file, $vars);
	}

	/**
	 * Magic method. Assigns template variables.
	 * 
	 * @param string $name Variable name
	 * @param mixed $value Variable value
	 * 
	 * @return void
	 */
	public function __set ($name, $value)
	{
		$this->vars[$name] = $value;
	}

	/**
	 * Assings template variables. Parameters are dynamic and can be
	 * either a single pair of name and value, or an associative array.
	 * 
	 * @return object
	 */
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

	/**
	 * The PHP template file is included and the buffered output is passed
	 * into a variable. The Response class takes further responsability.
	 * 
	 * @return void
	 */
	public function render ()
	{
		$this->tpl_constants();
		$this->custom_tpl_constants();
		
		// Extracts assigned template variables into the current scope,
		// so the included file can access them.
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

	/**
	 * Checks if a template file exists.
	 * 
	 * @param string $file Template file
	 * 
	 * @return bool
	 */
	public function exists ($file)
	{
		$file = config('views path') . "$file.php";
		if (file_exists($file))
		{
			return true;
		}

		return false;
	}

	/**
	 * Defines built-in template constants. These can be used directly,
	 * without assigning.
	 * 
	 * @return void
	 */
	protected function tpl_constants ()
	{
		$this->tpl_constants['_path'] = config('path');
		$this->tpl_constants['_locale'] = config('base locale');
	}

	/**
	 * Defines external template constants, read from a config file.
	 * As with the built-ins, these can be used without prio assigning.
	 * 
	 * @return void
	 */
	protected function custom_tpl_constants ()
	{
		$constants = include('config/constants.php');

		if (count($constants))
		{
			$this->tpl_constants = array_merge($this->tpl_constants, $constants);
		}
	}
	
}