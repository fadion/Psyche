<?php
namespace FW\Core;

class Cfg {

	const DB_HOST = 'localhost';
	const DB_USER = 'root';
	const DB_PASSWORD = 'password';
	const DB_NAME = 'test';
	
	const BASE_LOCALE = 'en';
	const ROLLBACK_LOCALE = 'en';
	
	const DEBUG = 1;
	
	const PATH = BASE_URL;

	const CONTROLLERS_PATH = 'app/controllers/';
	const MODELS_PATH = 'app/models/';
	const VIEWS_PATH = 'app/views/';
	const LOCALE_PATH = 'locale/';
	
	const ASSETS_PATH = 'app/assets/';
	const JS_PATH = 'js/';
	const CSS_PATH = 'css/';
	const IMG_PATH = 'img/';
	
	const DEFAULT_CONTROLLER = 'index';
	const DEFAULT_METHOD = 'index';

	const LOG_FILE = 'log.txt';

}