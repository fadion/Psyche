<?php
namespace Psyche\Models;

class Pages extends \Psyche\Core\Drill
{

	public function categories ()
	{
		return $this->has_one('Category');
	}

}