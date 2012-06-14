<?php
session_start();

define('FATAL', E_USER_ERROR);
define('ERROR', E_USER_WARNING);
define('WARNING', E_USER_NOTICE);
define('BASE_URL', 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/');

require_once 'core/autoload.php';
use FW\Core\Autoload;
Autoload::start();

use FW\Core\Localizer;
use FW\Core\DB;
use FW\Core\CFG;
use FW\Core\Router;
use FW\Core\Response;

function __ ()
{
	return Localizer::get(func_get_args());
}

DB::connect(CFG::DB_HOST, CFG::DB_USER, CFG::DB_PASSWORD, CFG::DB_NAME);

Router::start();
Response::output();
?>