<?php
namespace Psyche\Models;

class Pages extends \Psyche\Core\Drill
{

	

	public function update_me ()
	{
		$this->title = 'Some Title';
		$this->slug = 'some-title';
		$this->save();
	}

}