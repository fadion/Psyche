<?php
class Asset {

	public static function js ($file) {
		$file = CFG::ASSETS_PATH . CFG::JS_PATH . $file . '.js';

		if (file_exists($file)) {
			return '<script src="' . CFG::PATH . $file . '"></script>';
		}
	}

	public static function css ($file) {
		$file = CFG::ASSETS_PATH . CFG::CSS_PATH . $file . '.css';

		if (file_exists($file)) {
			return '<link rel="stylesheet" href="' . CFG::PATH . $file . '">';
		}
	}

	public static function img ($file, $alt = '') {
		$extension = File::extension($file);

		if ($extension == '') {
			$file = $file . '.png';
		}

		$file = CFG::ASSETS_PATH . CFG::IMG_PATH . $file;

		if (file_exists($file)) {
			return '<img src="' . CFG::PATH . $file . '" alt="' . $alt . '">';
		}
	}

}