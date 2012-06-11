<?php
class Error {

	public function __construct () {
		set_error_handler(array($this, 'error_handler'));	
	}
	
	function error_handler ($code, $string, $file, $line) {
		if (!(error_reporting() & $code)) {
			return;
		}
		
		$cfg = Repo::load('config');
	
		if ($cfg->debug) {
			$file_lines = file($file);
			@$error_line = $file_lines[$line - 2] . '<div style="background:#f0c0c0; color:#853f3f;">' . $file_lines[$line - 1] . '</div>' . $file_lines[$line];
			@$error_line = trim($error_line, PHP_EOL);
		}
	
		switch ($code) {
			case FATAL:
				switch ($cfg->debug) {
					case 0:
						echo 'An error ocurred. Please try again later.';
						exit;
					case 1:
						echo "<b>Fatal Error</b> in [$file] at line $line";
						echo "<div style='background:#f0dddd; border:1px solid #cf9898; color:#c58080; padding:15px; margin-bottom: 5px;'>$error_line</div>";
						echo "<div style='background:#e6edf3; border:1px solid #a2bcd2; color:#7691a9; padding:15px;'>$string</div>";
						exit;
				}
			case ERROR:
				switch ($cfg->debug) {
					case 0:
						echo 'An error ocurred. Please try again later.';
						break;
					case 1:
						echo "<b>Error</b> in [$file] at line $line";
						echo "<div style='background:#f0dddd; border:1px solid #cf9898; color:#c58080; padding:15px; margin-bottom: 5px;'>$error_line</div>";
						echo "<div style='background:#e6edf3; border:1px solid #a2bcd2; color:#7691a9; padding:15px;'>$string</div>";
						break;
				}
				break;
			case WARNING:
				switch ($cfg->debug) {
					case 0:
						echo 'A small error ocurred, but the application will continue working.';
						break;
					case 1:
						echo "<b>Warning</b> in [$file] at line $line";
						echo "<div style='background:#f0dddd; border:1px solid #cf9898; color:#c58080; padding:15px; margin-bottom: 5px;'>$error_line</div>";
						echo "<div style='background:#e6edf3; border:1px solid #a2bcd2; color:#7691a9; padding:15px;'>$string</div>";
						break;
				}
		}
	
		return true;
	}
}