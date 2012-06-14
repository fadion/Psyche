<?php
namespace FW\Core;
use FW\Core\File as File;

class Image {

	private $image;

	public function __construct ($image) {
		$image_r = $this->create($image);

		if ($image_r) {
			$this->image = $image_r;
		} else {
			trigger_errors('Not a valid image type', ERROR);
		}
	}

	public function __descruct () {
		imagedestroy($this->image);
	}

	public static function open ($image) {
		return new static($image);
	}

	public function resize ($width, $height = null, $ratio = 'auto') {
		list($o_width, $o_height) = $this->dimensions($this->image);

		if ($height == null) {
			$new_width = round($o_width * ($width / 100));
			$new_height = round($o_height * ($width / 100));
		} else {
			$w_aspect = $width / $o_width;
			$h_aspect = $height / $o_height;

			if ($ratio == 'auto') {
				$aspect = min($w_aspect, $h_aspect);

				$new_width = round($o_width * $aspect);
				$new_height = round($o_height * $aspect);
			} elseif ($ratio == 'width') {
				$new_width = $width;
				$new_height = round($o_height * $w_aspect);
			} elseif ($ratio == 'height') {
				$new_width = round($o_width * $h_aspect);
				$new_height = $height;
			} else {
				$new_width = $width;
				$new_height = $height;
			}
		}

		$resize = imagecreatetruecolor($new_width, $new_height);
		$transparent = imagecolorallocatealpha($resize, 0, 0, 0, 127);
		imagefill($resize, 0, 0, $transparent);
		imagecopyresampled($resize, $this->image, 0, 0, 0, 0, $new_width, $new_height, $o_width, $o_height);
		imagedestroy($this->image);
		imagecolortransparent($resize, $transparent);

		$this->image = $resize;

		return $this;
	}

	public function crop ($width, $height, $x = 0, $y = 0) {
		list($o_width, $o_height) = $this->dimensions($this->image);

		$crop = imagecreatetruecolor($width, $height);
		$transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);

		imagefill($crop, 0, 0, $transparent);
		imagecopy($crop, $this->image, 0, 0, $x, $y, $o_width, $o_height);
		imagedestroy($this->image);
		imagecolortransparent($crop, $transparent);

		$this->image = $crop;
		
		return $this;
	}

	public function rotate ($angle) {
		list($o_width, $o_height) = $this->dimensions($this->image);

		$transparent = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
		$this->image = imagerotate($this->image, 360 - $angle, $transparent);
		imagecolortransparent($this->image);

		return $this;
	}

	public function watermark ($file, $position = null, $opacity = 100) {
		if (!file_exists($file)) {
			trigger_error('Watemark image file was not found', ERROR);
			return false;
		}

		$watermark = $this->create($file);

		list($w_width, $w_height) = $this->dimensions($watermark);
		list($o_width, $o_height) = $this->dimensions($this->image);

		if ($opacity > 100 or $opacity < 0) {
			$opacity = 100;
		}

		$alpha = min(round(abs(($opacity * 127 / 100) - 127)), 127);
		$transparent = imagecolorallocatealpha($watermark, 0, 0, 0, $alpha);

		imagelayereffect($watermark, IMG_EFFECT_OVERLAY);
		imagefilledrectangle($watermark, 0, 0, $w_width, $w_height, $transparent);	

		if (is_array($position) and count($position) == 2) {
			list($x, $y) = $position;
		} else {
			switch ($position) {
				case 'top right':
				case 'tr':
					$x = $o_width - $w_width;
					$y = 0;
					break;
				case 'bottom left':
				case 'bl':
					$x = 0;
					$y = $o_height - $w_height;
					break;
				case 'bottom right':
				case 'bt':
					$x = $o_width - $w_width;
					$y = $o_height - $w_height;
					break;
				case 'center':
				case 'c':
					$x = ($o_width / 2) - ($w_width / 2);
					$y = ($o_height / 2) - ($w_height / 2);
					break;
				default:
					$x = 0;
					$y = 0;
			}
		}

		imagealphablending($this->image, true);
		imagecopy($this->image, $watermark, $x, $y, 0, 0, $w_width, $w_height);
		imagedestroy($watermark);
		
		return $this;
	}

	private function dimensions ($image) {
		return array(imagesx($image), imagesy($image));
	}

	private function create ($image) {
		$extension = File::extension($image);

		switch($extension) {
			case 'jpg':
			case 'jpeg':
				$img = @imagecreatefromjpeg($image);
				break;
			case 'gif':
				$img = @imagecreatefromgif($image);
				break;
			case 'png':
				$img = @imagecreatefrompng($image);
				break;
			default:
				$img = false;
				trigger_error('Not a valid image file', ERROR);
		}

		return $img;
	}

	public function save ($file, $quality = 80) {
		$dir = pathinfo($file, PATHINFO_DIRNAME);
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		if (!is_writable($dir)) {
			trigger_error('Directory is not writable', ERROR);
			return false;
		}

		switch($extension) {
			case 'jpg':
			case 'jpeg':
				imagejpeg($this->image, $file, $quality);
				break;
			case 'gif':
				imagegif($this->image, $file);
				break;
			case 'png':
				imagealphablending($this->image, true);
				imagesavealpha($this->image, true);
				imagepng($this->image, $file, (9 - (round(($quality / 100) * 9))));
				break;
			default:
				trigger_error('Could not save file', ERROR);
				return false;
		}

		return $this;
	}

}