<?php
return array(

	/* Database credentials */
	/* Leave the host and user empty for database-less application */
	'db host' => 'localhost',
	'db user' => 'root',
	'db password' => 'password',
	'db name' => 'fw',
	
	/* Default and rollback locales */
	/* The rollback will be used when a key is not found */
	'base locale' => 'en',
	'rollback locale' => 'en',
	
	/* Active/Deactive debugging features */
	/* Change to 0 for production sites */
	'debug' => 1,
	
	/* HTTP and absolute file system paths */
	/* Set to auto to let the framework compute them */
	/* Set them manually if "auto" doesn't work correctly */
	'path' => 'auto',
	'absolute path' => 'auto',

	/* Relative paths from the main index */
	/* Trailing slash needed */
	'controllers path' => 'app/controllers/',
	'models path' => 'app/models/',
	'views path' => 'app/views/',
	'locale path' => 'locale/',
	
	'assets path' => 'app/assets/',
	'js path' => 'js/',
	'css path' => 'css/',
	'img path' => 'img/',
	
	'log path' => 'stash/logs/',
	
	/* Default controller and method */
	/* The default controller will be executed in sub-folders too */
	/* The default method shouldn't be one of the reserved: route, before or after. action_ will be appended automatically */
	'default controller' => 'index',
	'default method' => 'index',

	/* Randomly generated SALT (for passwords) and TOKEN (for anything else) */
	'salt' => '$0R[Q4Cf6w$b?Ote+~RWu(u*n[gT#',
	'token' => 'v8_]bQ4DT(<G3%Y+nFAT}1Y{#?Z!3'

);