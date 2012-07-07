<?php
namespace Psyche\Core;

/**
 * Image manipulation helper
 * 
 * Offers some very easy to use methods and flexible chaining
 * for the most common image manipulation requirements.
 *
 * @package Psyche\Core\Form
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Image
{

	/**
	 * @var resource Image resource handler
	 */
	protected $image;

	/**
	 * @var string The opened image filename
	 */
	protected $original;

	/**
	 * Constructor. Sets the image handler.
	 * 
	 * @param string $file Path to the image file
	 */
	public function __construct ($file)
	{
		$this->original = $file;
		$image_r = $this->create($file);

		if ($image_r)
		{
			$this->image = $image_r;
		}
		else
		{
			trigger_error('Not a valid image', E_USER_WARNING);
		}
	}

	/**
	 * Destructor. Detroys the image handler.
	 * 
	 * @return void
	 */
	public function __descruct ()
	{
		imagedestroy($this->image);
	}

	/**
	 * Factory static method.
	 * 
	 * @param string $file Path to the image file
	 * @return Image
	 */
	public static function open ($file)
	{
		return new static($file);
	}

	/**
	 * Resizes the image by maintaining aspect ratio. If only the
	 * width is specified, it will be resized in % insted of px.
	 * 
	 * @param string $width
	 * @param string $height
	 * @param string $ratio Can be: 'auto', 'width' or 'height'
	 * @return Image
	 */
	public function resize ($width, $height = null, $ratio = 'auto')
	{
		list($o_width, $o_height) = $this->dimensions($this->image);

		// If the height isn't set, dimensions will be resized
		// by percentage. Ex: a 20 value will resize the image
		// 20% of it's original dimensions.
		if ($height == null)
		{
			$new_width = round($o_width * ($width / 100));
			$new_height = round($o_height * ($width / 100));
		}
		// Otherwise, calculate dimensions based on the original
		// ones and the defined aspect ratio type.
		else
		{
			$w_aspect = $width / $o_width;
			$h_aspect = $height / $o_height;

			if ($ratio == 'auto')
			{
				$aspect = min($w_aspect, $h_aspect);

				$new_width = round($o_width * $aspect);
				$new_height = round($o_height * $aspect);
			}
			elseif ($ratio == 'width')
			{
				$new_width = $width;
				$new_height = round($o_height * $w_aspect);
			}
			elseif ($ratio == 'height')
			{
				$new_width = round($o_width * $h_aspect);
				$new_height = $height;
			}
			else
			{
				$new_width = $width;
				$new_height = $height;
			}
		}

		// Creates the new image resource by beeing aware of transparency
		$resize = imagecreatetruecolor($new_width, $new_height);
		$transparent = imagecolorallocatealpha($resize, 0, 0, 0, 127);
		imagefill($resize, 0, 0, $transparent);
		imagecopyresampled($resize, $this->image, 0, 0, 0, 0, $new_width, $new_height, $o_width, $o_height);
		imagedestroy($this->image);
		imagecolortransparent($resize, $transparent);

		$this->image = $resize;

		return $this;
	}

	/**
	 * Crops the image.
	 * 
	 * @param string $width
	 * @param string $height
	 * @param int $x X-coordinate of the crop
	 * @param int $y Y-coordinate of the crop
	 * @return Image
	 */
	public function crop ($width, $height, $x = 0, $y = 0)
	{
		list($o_width, $o_height) = $this->dimensions($this->image);

		$crop = imagecreatetruecolor($width, $height);
		$transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);

		imagefill($crop, 0, 0, $transparent);
		imagecopyresampled($crop, $this->image, 0, 0, $x, $y, $o_width, $o_height);
		imagedestroy($this->image);
		imagecolortransparent($crop, $transparent);

		$this->image = $crop;
		
		return $this;
	}

	/**
	 * Rotates the image clock-wise.
	 * 
	 * @param float $angle Angle of rotation
	 * @return Image
	 */
	public function rotate ($angle)
	{
		list($o_width, $o_height) = $this->dimensions($this->image);

		$transparent = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
		$this->image = imagerotate($this->image, 360 - $angle, $transparent);
		imagecolortransparent($this->image);

		return $this;
	}

	/**
	 * Places a watermak on top of the source image.
	 * 
	 * @param string $file Path to the watermark image file
	 * @param string|array $position Position of the watermak
	 * @param int $opacity Transparency of the watermak
	 * @return Image
	 */
	public function watermark ($file, $position = null, $opacity = 100)
	{
		if (!file_exists($file))
		{
			trigger_error('Watemark image file was not found', E_USER_WARNING);
			return false;
		}

		$watermark = $this->create($file);

		list($w_width, $w_height) = $this->dimensions($watermark);
		list($o_width, $o_height) = $this->dimensions($this->image);

		if ($opacity > 100 or $opacity < 0)
		{
			$opacity = 100;
		}

		// Calculate alpha based on opacity. The alpha parameter in
		// Imagecolloralocatealpha() takes values from 0 - 127.
		$alpha = min(round(abs(($opacity * 127 / 100) - 127)), 127);
		$transparent = imagecolorallocatealpha($watermark, 0, 0, 0, $alpha);

		imagelayereffect($watermark, IMG_EFFECT_OVERLAY);
		imagefilledrectangle($watermark, 0, 0, $w_width, $w_height, $transparent);	

		// If position is set manually, take the $x and $y coordinates directly.
		// Otherwise, calculate them based on the string parameter.
		if (is_array($position) and count($position) == 2)
		{
			list($x, $y) = $position;
		}
		else
		{
			switch ($position)
			{
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
				case 'br':
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

	/**
	 * Gets the width and height of an image.
	 * 
	 * @param resource $image
	 * @return array
	 */
	protected function dimensions ($image)
	{
		return array(imagesx($image), imagesy($image));
	}

	/**
	 * Creates an image resource handler based on the image type.
	 * 
	 * @param string $file Path to the image file
	 * @return GD Resource
	 */
	protected function create ($file)
	{
		$info = getimagesize($file);
		$mime = $info['mime'];

		switch($mime)
		{
			case 'image/jpeg':
				$img = @imagecreatefromjpeg($file);
				break;
			case 'image/gif':
				$img = @imagecreatefromgif($file);
				break;
			case 'image/png':
				$img = @imagecreatefrompng($file);
				break;
			default:
				$img = false;
				trigger_error('Not a valid image file', E_USER_WARNING);
		}

		return $img;
	}

	/**
	 * Saves an image in the filesystem.
	 * 
	 * @param string $file Path to the image file
	 * @param int $quality Quality the JPEG image will be saved
	 * @return void
	 */
	public function save ($file, $quality = 80)
	{
		$original = $this->original;

		// Checks for an overwrite '-self.ext' wildcard.
		if ($file == '-self.ext')
		{
			$file = $original;
		}
		// Checks for a partial wildcard (ex: thumb_-self.ext).
		elseif (strpos($file, '-self.ext'))
		{
			$original_base = pathinfo($original, PATHINFO_BASENAME);
			$original_dir = pathinfo($original, PATHINFO_DIRNAME);

			$file = $original_dir.'/'.str_replace('-self.ext', $original_base, $file);
		}

		$dir = pathinfo($file, PATHINFO_DIRNAME);
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		if (!is_writable($dir))
		{
			trigger_error('Directory is not writable', E_USER_WARNING);
			return false;
		}

		switch($extension)
		{
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
				trigger_error('Could not save file', E_USER_WARNING);
				return false;
		}
	}

}