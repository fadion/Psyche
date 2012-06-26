<?php
namespace Psyche\Controllers;
use Psyche\Core;
use Psyche\Models;

class Users
{

	public function action_index ($kot)
	{
		if (Core\Uri::is('users/-any'))
		{
			echo 'Yes';
		}
	}

}