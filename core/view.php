<?php
class View {

	private static $output, $file, $keys, $cache, $tpl_constants;
	
	public static function set () {
		if (func_num_args()) {
			$args = func_get_args();
			$arg1 = $args[0];
			$arg2 = @$args[1];
			
			if (is_array($arg1)) {
				foreach ($arg1 as $key=>$val) {
					static::$keys[$key] = $val;
				}
			} else {
				if (!empty($arg1)) {
					static::$keys[$arg1] = $arg2;
				}
			}
		}
		
		return new static;
	}
	
	public static function output ($file) {
		$path = CFG::VIEWS_PATH . "$file.php";
		
		if (!file_exists($path)) {
			return false;
		}

		static::tpl_constants();
		static::custom_tpl_constants();
		
		static::$file = $path;
		unset($path);

		if (is_array(static::$keys)) {
			extract(static::$keys, EXTR_SKIP);
		}

		if (is_array(static::$tpl_constants)) {
			extract(static::$tpl_constants, EXTR_SKIP);
		}

		ob_start();
		include(static::$file);
		$output = ob_get_clean();
		
		Response::write($output);
	}

	private static function tpl_constants () {
		static::$tpl_constants['path'] = CFG::PATH;
		static::$tpl_constants['locale'] = CFG::BASE_LOCALE;
	}

	private static function custom_tpl_constants () {
		$constants = include('config/constants.php');

		if (count($constants)) {
			static::$tpl_constants = array_merge(static::$tpl_constants, $constants);
		}
	}

	private static function fix_includes ($contents) {
		$path = CFG::VIEWS_PATH;
		$contents = str_replace("include('", "include('$path", $contents);

		return $contents;
	}
	
}