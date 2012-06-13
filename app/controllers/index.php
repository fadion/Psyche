<?php
class C_Index {

	public function action_index () {
		
		//M_Index Should Autoload here		
		
		$content = array(
			'title' => 'Wokrs!', 
			'content' => 'This framework is build to work.'
		);

		
		View::set( $content )->output('index');

	}

}