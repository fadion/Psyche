<?php
session_start();

define('FATAL', E_USER_ERROR);
define('ERROR', E_USER_WARNING);
define('WARNING', E_USER_NOTICE);

require_once 'core/autoload.php';
use FW\Core\Autoload;
Autoload::start();

use FW\Core\DB;
use FW\Core\Config;
use FW\Core\Router;
use FW\Core\Response;
use FW\Core\Error;

function __ ()
{
	return call_user_func_array('FW\Core\Localizer::get', func_get_args());
}

function config ($key)
{
	return Config::get($key);
}

if (config('debug') == 1)
{
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}
else
{
	ini_set('display_errors', 0);
}

$error = new Error;

DB::connect();

Router::start();
Response::output();
?>