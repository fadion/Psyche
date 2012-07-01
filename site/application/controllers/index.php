<?php
namespace Psyche\Controllers;
use Psyche\Core;
use Psyche\Models;

class Index
{

	public function action_index ()
	{
		$view = Core\View::open('content');
		$view->title = 'Master Page';
		$view->render();
	}

}