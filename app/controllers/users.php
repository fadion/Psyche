<?php
namespace FW\Controllers;
use FW\Core;
use FW\Models;

class Users
{

	public function action_index ()
	{
		$results = Core\DB::query("SELECT name, surname FROM users WHERE name=? and surname=?", 'Fadion', 'Dashi');
		print_r($results);

		foreach ($results as $row)
		{
			echo $row->name.' '.$row->surname;
		}
	}

}