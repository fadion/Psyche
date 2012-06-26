<?php
namespace Psyche\Controllers;
use Psyche\Core;
use Psyche\Models;

class Index
{

	public function action_index ()
	{
		echo Core\Test::run();
		
		echo '<br><br>';

		echo \Some\Lib\TestLib::run();
	}

}