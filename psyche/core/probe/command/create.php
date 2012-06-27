<?php
namespace Psyche\Core\Probe\Command;
use Psyche\Core\Probe\CLI;

// Still WIP and under heavy experimenting. Don't execute via CLI!
// php probe create ../mysite.com
class Create
{

	public static function exec ($vars)
	{
		if (isset($vars[0]))
		{
			$dir = rtrim($vars[0], '/');

			if (!file_exists($dir))
			{
				mkdir($dir);
			}

			if (count(scandir($dir)) > 2)
			{
				CLI::output('ERROR: Directory should be empty');
				CLI::beep();
			}
			else
			{
				mkdir($dir.'/application');
				mkdir($dir.'/application/controllers');
				mkdir($dir.'/application/models');
				mkdir($dir.'/application/views');
				mkdir($dir.'/application/assets');

				mkdir($dir.'/config');
				mkdir($dir.'/locale');
				mkdir($dir.'/stash');
				mkdir($dir.'/stash/views');
				mkdir($dir.'/stash/logs');

				CLI::output('Site create successfully');
			}
		}
		else
		{
			CLI::output('ERROR: Create command needs a site path');
			CLI::beep();
		}
	}

}