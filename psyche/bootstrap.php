<?php
session_start();

// Psyche folder path relative to the site's index.php file.
// When "psyche" is placed as a sibling of the sites, the
// default value is fine. If it's placed outside the document root
// it can be set to: '../../psyche/'
define('PSYCHE_PATH', '../psyche/');

require_once 'core/autoload.php';

Psyche\Core\Autoload::start();
Psyche\Core\Error::start();

/**
 * A global helper to print localized strings.
 * 
 * @return string
 */
function __ ()
{
	return call_user_func_array('Psyche\Core\Localizer::get', func_get_args());
}

/**
 * A global helper to get configuration keys.
 * 
 * @return string
 */
function config ($key)
{
	return Psyche\Core\Config::get($key);
}

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

Psyche\Core\DB::connect();

Psyche\Core\Router::start();
Psyche\Core\Response::output();
?>