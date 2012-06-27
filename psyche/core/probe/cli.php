<?php
namespace Psyche\Core\Probe;

// CLI helper
// WIP
class CLI
{

	public static function output ($string)
	{
		fwrite(STDOUT, $string.PHP_EOL);
	}

	public static function input ($message)
	{
		fwrite(STDOUT, $message.': ');

		return trim(fgets(STDIN));
	}

	public static function confirm ($message)
	{
		$input = strtolower(static::input($message));

		if ($input == 'y')
		{
			return true;
		}
		elseif ($input == 'n')
		{
			return false;
		}
		else
		{
			return static::confirm($message);
		}
	}

	public static function beep($beeps = 1)
	{
		fwrite(STDOUT, str_repeat("\x07", $beeps));
	}

}