<?php
namespace FW\Controllers;
use \FW\Core;
use \FW\Models;

<<<<<<< HEAD
class Index
{

	public function action_index ()
	{
		echo Models\Users_Users::get();
=======
	public function action_index () {
		
		//M_Index Should Autoload here		
		
		$content = array(
			'title' => 'Wokrs!', 
			'content' => 'This framework is build to work.'
		);

		
		View::set( $content )->output('index');

>>>>>>> d6f1858a058d63ce703b8b27225b76dd0ccc294f
	}

}