<?php
return array
(
	// Default and rollback locales
	// The rollback will be used when a key is not found
	'base locale' => 'en',
	'rollback locale' => 'sq',
	
	// Active/Deactive debugging features
	// Change to 0 for production sites
	'debug' => 1,
	
	// HTTP and absolute file system paths
	// Set to auto to let the framework compute them
	// Set them manually if "auto" doesn't work correctly
	'path' => 'auto',
	'absolute path' => 'auto',

	// Relative paths from the main index
	// Trailing slash needed
	'controllers path' => 'application/controllers/',
	'models path' => 'application/models/',
	'views path' => 'application/views/',
	'locale path' => 'locale/',
	'base controllers path' => 'application/base/',
	
	'assets path' => 'application/assets/',
	'js path' => 'js/',
	'css path' => 'css/',
	'img path' => 'img/',
	
	// Default controller and method
	// The default controller will be executed in sub-folders too
	// The default method shouldn't be one of the reserved: route, before or after. action_ will be appended automatically
	'default controller' => 'index',
	'default method' => 'index',

	// Default Mold Template Engine file extensions
	'mold extension' => '.mold.php',

	// Debug driver
	'debug driver' => 'gizmo',

	// Whether Gizmo should load the buil-tin jQuery library or not.
	// Disable (set to 0) if planning to use jQuery yourself to prevent
	// problems from loading it twice. Ignore if not using Gizmo.
	'gizmo load jquery' => 1,

	// Randomly generated SALT (for passwords) and TOKEN (for anything else)
	'salt' => '$0R[Q4Cf6w$b?Ote+~RWu(u*n[gT#',
	'token' => 'v8_]bQ4DT(<G3%Y+nFAT}1Y{#?Z!3'
);