<?php
session_start();

require_once 'core/autoload.php';

FW\Core\Autoload::start();

function __ ()
{
	return call_user_func_array('FW\Core\Localizer::get', func_get_args());
}

function config ($key)
{
	return FW\Core\Config::get($key);
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

$error = new FW\Core\Error;

FW\Core\DB::connect();

FW\Core\Router::start();
FW\Core\Response::output();
?>