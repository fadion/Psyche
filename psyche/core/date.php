<?php
namespace Psyche\Core;

class Date
{

	public static function age ($date)
	{
		return intval(substr(date('Ymd') - date('Ymd', strtotime($date)), 0, -4));
	}

}