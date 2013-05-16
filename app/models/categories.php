<?php
namespace Psyche\Models;

class Categories extends \Psyche\Core\Drill
{
	
	public function pages ()
	{
		return $this->has_many('Pages');
	}

}