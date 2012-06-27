<?php
namespace Psyche\Core\Probe\Command;
use Psyche\Core\Probe\CLI;

// php probe help
class Help
{

	public static function exec ()
	{
		$output  = "---------------------------".PHP_EOL;
		$output .= "|   Probe CLI Tool v1.0   |".PHP_EOL;
		$output .= "|  Helper for Psyche PHP  |".PHP_EOL;
		$output .= "---------------------------".PHP_EOL.PHP_EOL;

		$output .= "Usage: php probe <command> [options]".PHP_EOL.PHP_EOL;

		$output .= "     create [site_path] [controllers] : Creates a basic site structure".PHP_EOL;
		$output .= "     update : Updates Psyche".PHP_EOL;
		$output .= "     pack install [name] : Installs a Pack".PHP_EOL;
		$output .= "     pack update [name] : Updates a Pack".PHP_EOL;
		$output .= "     pack remove [name] : Removes a Pack".PHP_EOL;
		$output .= "     localize : Creates local files".PHP_EOL.PHP_EOL;

		$output .= "Check the online documentation for further details at:".PHP_EOL;
		$output .= "http://www.somenicename.com/docs/probe";

		CLI::output($output);
	}

}