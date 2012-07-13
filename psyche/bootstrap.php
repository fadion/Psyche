<?php
session_start();

// Psyche folder path relative to the site's index.php file.
// When "psyche" is placed as a sibling of the sites, the
// default value is fine. If it's placed outside the document root
// it can be set to: '../../psyche/'
define('PSYCHE_PATH', '../psyche/');
define('PSYCHE_START', microtime(true));

require_once 'core/autoload.php';

Psyche\Core\Autoload::start();
Psyche\Core\Error::start();

require_once 'functions.php';

// Set error levels depending on the DEBUG option.
if (config('debug') == 1)
{
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}
else
{
	ini_set('display_errors', 0);
}

if (count(config('database:')))
{
	Psyche\Core\DB::connect();
}

Psyche\Core\Router::start();

if (config('debug') == 1 and config('debug driver') == 'gizmo')
{
	Psyche\Core\Debug::toolbar();
}

Psyche\Core\Response::output();