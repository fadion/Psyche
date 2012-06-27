<?php
namespace Psyche\Models;

class Pages extends \Psyche\Core\Drill
{

	/* A static "table" property can be set if
	   the table name doesn't match with the
	   model name
	 */
	//private static $table = 'my_pages';

	public function update_all ()
	{
		$this->title = 'Some Title';
		$this->slug = 'some-title';
		$this->save();
	}

}