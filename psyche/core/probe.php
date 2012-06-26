<?php
namespace Psyche\Core;
use Psyche\Core\Probe;

class Probe
{

	public static function create ()
	{
		Probe\Create::run();
	}

	public static function write ($string)
	{
		fwrite(STDOUT, $string);
	}

}