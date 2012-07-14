<?php
namespace Psyche\Core\Debug\Gizmo;
use Psyche\Core\View,
	Psyche\Core\Event,
	Psyche\Core\Number;

/**
 * Gizmo Toolbar
 * 
 * A basic, but informative toolbar for printing debug messages
 * in a controlled area, without visually polluting the page
 * output.
 *
 * @package Psyche\Core\Debug\Gizmo
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Gizmo
{

	/**
	 * @var array Debug messages, organized by type.
	 */
	protected static $messages = array();

	/**
	 * Log a message.
	 * 
	 * @param mixed $var
	 * @param string $label
	 * 
	 * @return void
	 */
	public function log ($var, $label = null)
	{
		$this->add($var, $label, 'log', debug_backtrace(false));
	}

	/**
	 * Alias of log().
	 * 
	 * @param mixed $var
	 * @param string $label
	 * 
	 * @return void
	 */
	public function info ($var, $label = null)
	{
		$this->add($var, $label, 'log', debug_backtrace(false));
	}

	/**
	 * Log a warning message.
	 * 
	 * @param mixed $var
	 * @param string $label
	 * 
	 * @return void
	 */
	public function warn ($var, $label = null)
	{
		$this->add($var, $label, 'warn', debug_backtrace(false));
	}

	/**
	 * Log an error message.
	 * 
	 * @param mixed $var
	 * @param string $label
	 * 
	 * @return void
	 */
	public function error ($var, $label = null)
	{
		$this->add($var, $label, 'error', debug_backtrace(false));
	}

	/**
	 * Constructs the message and adds it in the messages array.
	 * 
	 * @param mixed $var
	 * @param string $label
	 * @param string $type
	 * @param array $backtrace
	 * 
	 * @return void
	 */
	protected function add ($var, $label, $type, $backtrace)
	{
		$file = basename($backtrace[0]['file']);

		// If debug was called from the global helper _d(),
		// get the second trace.
		if ($file == 'functions.php')
		{
			$backtrace = $backtrace[1];
		}
		// Otherwise, get the first.
		else
		{
			$backtrace = $backtrace[0];
		}

		// Gets the file and number from the trace.
		$return  = '<span>'.$backtrace['file'].' : '.$backtrace['line'].'</span>';

		if (isset($label))
		{
			$return .= '<strong>'.$label.'</strong> ';
		}

		// Arrays and objects are printed as the output
		// of print_r(). 
		if (is_array($var) or is_object($var))
		{
			$return .= print_r($var, true);
		}
		// Booleans are shown as "True" or "False" instead
		// of 1 or 0 (which can't be outputted).
		elseif (is_bool($var))
		{
			$value = ($var) ? 'True' : 'False';
			$return .= '[ Boolean ]: '.$value;	
		}
		// Strings, integers or floats are shown as is.
		else
		{
			$return .= '[ '.ucfirst(gettype($var)).' ]: '.$var;
		}

		switch ($type)
		{
			case 'log':
				static::$messages['log'][] = $return;
				break;
			case 'warn':
				static::$messages['warn'][] = $return;
				break;
			case 'error':
				static::$messages['error'][] = $return;
				break;
		}
	}

	/**
	 * Renders the Gizmo Toolbar by prepending it to the
	 * application output.
	 * 
	 * @return void
	 */
	public static function render_toolbar ()
	{
		// Triggers the event, listened by the Router, to get the
		// active controller and method.
		list($controller, $method) = Event::first('psyche gizmo');

		if (isset($controller) and isset($method))
		{
			$controller = $controller.'::'.$method.'()';
		}
		else
		{
			$controller = 'none';
		}

		$console = '';

		if (count(static::$messages))
		{
			// $messages contains types, which themselves are
			// arrays with the messages as elements.
			foreach (static::$messages as $type => $messages)
			{
				foreach ($messages as $message)
				{
					$css_class = 'class="gizmo-'.$type.'"';

					$console .= '<p '.$css_class.'>';
					$console .= $message;
					$console .= '</p>';
				}
			}
		}

		// The view file is opened with a "custom:" keyword, so the
		// path is set manually instead of being constructed by the
		// View class.
		$view = View::open('custom:'.__DIR__.'/assets/index.php');

		// Execution time is calculated in milliseconds from the difference
		// of PSYCHE_START (defined at the top of the script) and the current
		// timestamp.
		$view->execution_time = round((microtime(true) - PSYCHE_START) * 1000).' ms';
		$view->controller = $controller;

		// Memory usage is converted to human readable bytes.
		$view->memory = Number::bytes(memory_get_usage(true));
		$view->console = $console;
		
		$toolbar = $view->output();

		// Listen for the output event and prepend the toolbar output to it.
		Event::on('psyche output', function($output) use ($toolbar)
		{
			return $output .= $toolbar;
		});
	}

}