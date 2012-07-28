<?php
return array
(
	// Default template.
	'use' => 'fw',

	// Multiple database configurations can be
	// created and used when connecting.
	'templates' => array
	(
		'fw' => array
		(
			'type' => 'mysql',
			'host' => 'localhost',
			'user' => 'root',
			'password' => 'password',
			'name' => 'fw'
		)
	)
);