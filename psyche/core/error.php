<?php
namespace Psyche\Core;

/**
 * Error Handler
 * 
 * Catches User Errors and Uncaught Exceptions. When in DEBUG mode,
 * messages will be shown fully together with a single backtrace.
 *
 * @package Psyche\Core\Error
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Error
{

	/**
	 * Constructor. Sets error handlers.
	 * 
	 * @return void
	 */
	public function __construct ()
	{
		set_error_handler(array($this, 'error_handler'));
		set_exception_handler(array($this, 'exception_handler'));
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
	 * The Exception Handler. Uncaught exceptions will be shown
	 * with some useful information.
	 */
	public function exception_handler($exception) {
		$trace = $exception->getTrace();

		echo '<b>Uncaught exception</b> in ['.$trace[0]['file'].'] at line ['.$trace[0]['line'].']';
		echo '<div style="background:#e6edf3; border:1px solid #a2bcd2; color:#7691a9; padding:15px; margin-bottom: 20px;">'.$exception->getMessage().'</div>';
		
		$i = 1;
		$msg = '';
		foreach ($trace as $t)
		{
			if ($i > 5) break;

			$msg = 'Trace '.$i;

			if ((isset($t['file']) and $t['file'] != '') and (isset($t['line']) and $t['line'] != ''))
			{
				$msg .= ' in ['.$t['file'].'] at line ['.$t['line'].']';
			}

			if ((isset($t['class']) and $t['class'] != '') and (isset($t['function']) and $t['function'] != ''))
			{
				$msg .= '<br>Started by: '.$t['class'].'::'.$t['function'].'()';
			}

			$i++;
			echo '<div style="background:#f0dddd; border:1px solid #cf9898; color:#c58080; padding:15px; margin-bottom: 5px;">'.$msg.'</div>';
		}
	}
	
	/**
	 * The Error Handler. Errors will be shown with some useful information.
	 */
	public function error_handler ($code, $message, $file, $line)
	{
		if (!(error_reporting() & $code))
		{
			return;
		}

		$debug = (bool) config('debug');

		if ($code == E_USER_ERROR or $code == E_USER_WARNING or $code == E_USER_NOTICE)
		{
			if (!$debug)
			{
				echo 'We are sorry but an error occurred. Please try again later.';
				if ($code == E_USER_ERROR)
				{
					exit;
				}
			}

			$type = 'Error';
			$exit = 0;
		
			if ($code == E_USER_ERROR)
			{
				$type = 'Fatal Error';
				$exit = 1;
			}

			$trace = debug_backtrace();

			echo "<b>".$type."</b> in [$file] at line $line";
			echo '<div style="background:#e6edf3; border:1px solid #a2bcd2; color:#7691a9; padding:15px; margin-bottom: 20px;">'.$message.'</div>';

			$i = 1;
			$msg = '';
			foreach ($trace as $t)
			{
				if ($i > 5) break;

				$msg = 'Trace '.$i;

				if ((isset($t['file']) and $t['file'] != '') and (isset($t['line']) and $t['line'] != ''))
				{
					$msg .= ' in ['.$t['file'].'] at line ['.$t['line'].']';
				}

				if ((isset($t['class']) and $t['class'] != '') and (isset($t['function']) and $t['function'] != ''))
				{
					$msg .= '<br>Started by: '.$t['class'].'::'.$t['function'].'()';
				}

				$i++;
				echo '<div style="background:#f0dddd; border:1px solid #cf9898; color:#c58080; padding:15px; margin-bottom: 5px;">'.$msg.'</div>';
			}
			
			if ($exit) exit;
		}
	
		return true;
	}
}