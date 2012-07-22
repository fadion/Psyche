<?php
return array (

	// Default cache driver
	'driver' => 'file',

	// Path to the File cache (File driver)
	'file path' => 'stash/cache/',

	// Database table if a database (Database driver)
	'database table' => 'cache',

	// XCache username and password (XCache Driver)
	'xcache user' => 'user',
	'xcache pass' => 'password',

	// Memcached server(s) (Memcached Driver)
	'memcached servers' => array(
		array('127.0.0.1', 11211)
	),

	// A prefix will be prepended to
	// cache items to prevent name collisions
	'prefix' => 'psyche'

);