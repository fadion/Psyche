<?php
session_start();

$loadable_classes = array(
	'core' => array(
				'error', 'loader', 'router', 'response', 'db', 'localizer', 'view', 'uri',
				'request', 'file', 'cookie', 'session', 'asset', 'validator', 'input', 'form'
			  ),
	'config' => array('cfg')
);

foreach ($loadable_classes as $type => $classes) {
	foreach ($classes as $class) {
		require_once "$type/$class.php";
	}
}

define('FATAL', E_USER_ERROR);
define('ERROR', E_USER_WARNING);
define('WARNING', E_USER_NOTICE);

define('BASE_URL', 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/');

function __ () {
	return Localizer::get(func_get_args());
}

DB::connect(CFG::DB_HOST, CFG::DB_USER, CFG::DB_PASSWORD, CFG::DB_NAME);

Router::start();
Response::output();
?>