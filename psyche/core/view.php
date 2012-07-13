<?php
namespace Psyche\Core;
use Psyche\Core\Response,
	Psyche\Core\View\Mold,
	ArrayAccess;

/**
 * View Engine
 * 
 * A class that makes working with PHP view files as easy as with template engines.
 * Basically, it provides the options to open a template file and assign its variables.
 * Files are outputed as pure PHP, so there is absolutely no overhead.
 *
 * @package Psyche\Core\View
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class View implements ArrayAccess
{

	/**
	 * @var array Keeps assigned template variables
	 */
	public $vars;

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
	 */
	public function __construct ($file, $vars)
	{
		$this->filename = $file;

		// The "custom:" keyword stops View from
		// automatically building file paths. It allows
		// the flexibility to open view files from anywhere
		// in the file system.
		if (stripos($file, 'custom:') === 0)
		{
			$file = substr($file, strlen('custom:'));
		}
		else
		{
			$file = config('views path').$file;
		}

		$exists = true;

		// When no extension is set, it tries to find it
		// automatically. php files take precedence over mold.
		if (stripos($file, config('mold extension')) === false and stripos($file, '.php') === false)
		{
			if (file_exists($file.'.php'))
			{
				$file .= '.php';
			}
			elseif (file_exists($file.config('mold extension')))
			{
				$file .= config('mold extension');
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

		if (isset($vars))
		{
			$this->vars = $vars;
		}

		$this->file = $file;

		// Mold files are passed to Mold Engine for compilation.
		if (stripos($file, config('mold extension')) !== false)
		{
			$this->file = Mold::run($file);
		}
	}

	/**
	 * Factory static method.
	 * 
	 * @param string $file Template filename
	 * @param array $vars Template variables to assign
	 * @return View
	 */
	public static function open ($file, $vars = null)
	{
		return new static($file, $vars);
	}

	/**
	 * Assigns template variables.
	 * 
	 * @return void
	 */
	public function __set ($name, $value)
	{
		$this->vars[$name] = $value;
	}

	/**
	 * Assigns template variables.
	 * 
	 * @return string
	 */
	public function __get ($name)
	{
		return $this->vars[$name];
	}

	/**
	 * Returns a correct result when
	 * isset() is run on a property.
	 * 
	 * return bool
	 */
	public function __isset ($name)
	{
		return isset($this->vars[$name]);
	}

	/**
	 * Assings template variables. Parameters are dynamic and can be
	 * either a single pair of name and value, or an associative array.
	 * 
	 * @return View
	 */
	public function set ()
	{
		$args = func_get_args();

		// Elements can be an associative array, where
		// the key is the name of the variable.
		if (is_array($args[0]))
		{
			$args = $args[0];
		}
		// Or pairs.
		else
		{
			$_args = array();
		    for ($i = 0, $count = count($args); $i < $count; $i += 2) { 
		        $_args[$args[$i]] = $args[$i + 1]; 
		    }

			$args = $_args;
		}

		foreach ($args as $key=>$val)
		{
			$this->vars[$key] = $val;
		}

		return $this;
	}

	/**
	 * Sends the template output to the Response class.
	 * 
	 * @return void
	 */
	public function render ()
	{
		Response::write($this->make());
	}

	/**
	 * Returns the raw output as string.
	 * 
	 * @return void
	 */
	public function output ()
	{
		return $this->make();
	}

	/**
	 * The PHP template file is included and the buffered output is passed
	 * into a variable.
	 * 
	 * @return void
	 */
	protected function make ()
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

		// Stores the included file in the buffer, without rendering it and
		// returns the output in a variable. 
		ob_start();
		include($this->file);
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Calls the render() method when the class is treated as string,
	 * either via echo or string casting.
	 * 
	 * @return void
	 */
	public function __toString ()
	{
		$this->render();
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
		$this->tpl_constants['_view'] = config('views path');
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
		$constants = config('constants:');

		if (count($constants))
		{
			$this->tpl_constants = array_merge($this->tpl_constants, $constants);
		}
	}

	/**
	 * Implementation of ArrayAccess set.
	 * 
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->vars[$offset] = $value;
    }

    /**
	 * Implementation of ArrayAccess exists.
	 * 
	 * @return bool
	 */
    public function offsetExists($offset) {
        return isset($this->vars[$offset]);
    }

    /**
	 * Implementation of ArrayAccess unset.
	 * 
	 * @return void
	 */
    public function offsetUnset($offset) {
        unset($this->vars[$offset]);
    }

    /**
	 * Implementation of ArrayAccess get.
	 * 
	 * @return void|string
	 */
    public function offsetGet($offset) {
    	if (isset($this->vars[$offset]))
    	{
    		return $this->vars[$offset];
    	}
    }
	
}