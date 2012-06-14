<?php
session_start();

define('FATAL', E_USER_ERROR);
define('ERROR', E_USER_WARNING);
define('WARNING', E_USER_NOTICE);

require_once 'core/autoload.php';
use FW\Core\Autoload;
Autoload::start();

use FW\Core\Localizer;
use FW\Core\DB;
use FW\Core\CFG;
use FW\Core\Router;
use FW\Core\Response;
use FW\Core\Error;

function __ ()
{
	return Localizer::get(func_get_args());
}

function config ($key)
{
	return CFG::get($key);
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

if (config('db host') != '' and config('db user') != '')
{
	DB::connect(config('db host'), config('db user'), config('db password'), config('db name'));
}

Router::start();
Response::output();
?>