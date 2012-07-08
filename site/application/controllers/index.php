<?php
namespace Psyche\Controllers;
use Psyche\Core,
	Psyche\Models;

class Index
{

	public function action_index ()
	{
		Core\View::open('tag')->render();
	}

}