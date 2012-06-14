<?php
namespace FW\Controllers;
use \FW\Core;
use \FW\Models;

class Index
{

	public function action_index ()
	{
		echo Models\Users_Users::get();
	}

}