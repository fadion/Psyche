<?php
namespace Psyche\Core;

/**
 * Asset Manager
 * 
 * An asset manager that makes it easy to access css,
 * js and image files. In it's simplest form, it can
 * be used to print asset files, without any logic.
 * However, for big applications, it can be used
 * to define complex dependencies between multiple
 * files and different formats.
 *
 * @package Psyche\Core\Asset
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Asset
{

	/**
	 * @var array The final array that will be output (as string).
	 */
	protected static $output = array('css' => array(), 'js' => array(), 'img' => array());

	/**
	 * @var array Holds the added asset items.
	 */
	protected static $library;

	/**
	 * @var array Holds dependencies.
	 */
	protected static $dependencies = array();

	/**
	 * Adds an asset file to the library. For flexibility, parameters
	 * are quite dynamic. If the $path is set as string, the $name will
	 * act as an alias; otherwise the $name will be the path of the file.
	 * Dependencies can also be defined in the $path position for a shorter
	 * syntax, when no alias is wanted.
	 * 
	 * @param string $name
	 * @param string|array $path
	 * @param array $dependencies
	 * @return void
	 */
	public static function add ($name, $path = null, $dependencies = null)
	{
		// If $path is an array, it will be
		// treated as the dependencies.
		if (is_array($path))
		{
			$dependencies = $path;
			$path = $name;
		}

		// If $path isn't set, no alias is requested
		// and the path is in $name.
		if (!isset($path))
		{
			$path = $name;
		}

		$ext = pathinfo($path, PATHINFO_EXTENSION);

		if (isset($dependencies))
		{
			static::$dependencies[$name] = $dependencies;
		}

		// Builds the asset tree with every asset type
		// in it's position.
		switch ($ext)
		{
			case 'jpg':
			case 'png':
			case 'gif':
				static::$library['img'][$name] = $path;
				break;
			case 'css':
				static::$library['css'][$name] = $path;
				break;
			case 'js':
				static::$library['js'][$name] = $path;
				break;
			default:
				return false;
		}
	}

	/**
	 * Returns a CSS file or all CSS files if the
	 * name is omitted.
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function css ($name = null)
	{
		return static::build_asset($name, 'css');
	}

	/**
	 * Returns a Javascript file or all JS files if the
	 * name is omitted.
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function js ($name = null)
	{
		return static::build_asset($name, 'js');
	}

	/**
	 * Returns an Image file or all image files if the
	 * name is omitted.
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function img ($name = null)
	{
		return static::build_asset($name, 'img');
	}

	/**
	 * Builds the asset by putting all the pieces together.
	 * 
	 * @param string $name
	 * @param string $type
	 * @return string
	 */
	protected static function build_asset ($name = null, $type)
	{
		$output = '';

		// When the name is set, it's a call to a single asset.
		if (isset($name))
		{
			if (isset(static::$library[$type][$name]))
			{
				static::build_dependencies($name);
				static::$output[$type][] = static::build_html(static::$library[$type][$name], $type);
			}
			// Will print a file that isn't in the asset tree.
			else
			{
				return static::build_html($name, $type);	
			}
		}
		// Otherwise, print them all.
		elseif (count(static::$library[$type]))
		{
			foreach (static::$library[$type] as $name => $path)
			{
				static::$output[$type][] = static::build_html(static::$library[$type][$name], $type);
			}
		}

		return static::output();
	}

	/**
	 * Makes the output as string from an array. It's mostly
	 * here to correctly output asset types. CSS files will
	 * be always first, JS second and lastly IMG files.
	 * 
	 * @return string
	 */
	protected static function output ()
	{
		$output = '';

		if (count(static::$output))
		{
			foreach (array('css', 'js', 'img') as $type)
			{
				if (isset(static::$output[$type]))
				{
					foreach (static::$output[$type] as $html)
					{
						$output .= $html;
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Builds correct HTML output for each file type.
	 * 
	 * @param string $file
	 * @param string $type
	 * @return string
	 */
	protected static function build_html ($file, $type)
	{
		// An "url:" modifier means that the file should be
		// left unchanged, as it points to a URL.
		if (stripos($file, 'url:') === 0)
		{
			$path = substr($file, 4);
		}
		// An "assets:" modifier will point to the "assets" folder,
		// but not in any of the specific ones (ex: img, js, css).
		// It's useful for loading resources that don't reside inside
		// the typical asset folders.
		elseif (stripos($file, 'assets:') === 0)
		{
			$path = config('path').config('assets path').substr($file, 7);
		}
		// No modifier set.
		else
		{
			$path = config('path').config('assets path').config($type.' path').$file;
		}

		if ($type == 'css')
		{
			return '<link rel="stylesheet" href="'.$path.'">';
		}
		elseif ($type == 'js')
		{
			return '<script src="'.$path.'"></script>';
		}
		elseif ($type == 'img')
		{
			return '<img src="'.$path.'">';
		}
	}

	/**
	 * Builds dependencies. This function runs recursively
	 * to check if a dependency has dependencies of it's own.
	 * 
	 * @param string $name
	 */
	protected static function build_dependencies ($name)
	{
		if (isset(static::$dependencies[$name]))
		{
			$dependencies = static::$dependencies[$name];

			// One file can have multiple dependencies, so it's
			// iterated in each of them.
			foreach ($dependencies as $type => $file)
			{
				// The HTML of a dependency is set as the first element
				// of the output array, so that nested dependencies are
				// always printed first.
				array_unshift(static::$output[$type], static::build_html(static::$library[$type][$file], $type));
				static::build_dependencies($file);
			}
		}
	}

	/**
	 * Combine assets and serve them as a single file.
	 *
	 * @param string $type
	 * @param array $files
	 */
	public static function combine ($type = 'css', $files)
	{
		// $files should be a non-empty array and type either "css" or "js".
		if (!is_array($files) or !count($files) or !in_array($type, array('css', 'js')))
		{
			return false;
		}

		$cached = true;
		$compiled = config('assets path').config($type.' path').md5(implode('-', $files)).'.'.$type;
		$real_files = array();

		foreach ($files as $file)
		{
			$the_file = config('assets path').config($type.' path').$file;
			$real_files[] = $the_file;

			// If a compiled file exists and the asset file hasn't been modified,
			// it will serve the compiled file.
			if (!file_exists($compiled) or filemtime($the_file) > filemtime($compiled))
			{
				$cached = false;
			}
		}

		// Combine the files if not serving from "cache".
		if (!$cached)
		{
			$output = '';

			foreach ($real_files as $file)
			{
				$output .= file_get_contents($file);
			}

			file_put_contents($compiled, $output);
		}

		if ($type == 'css')
		{
			return '<link rel="stylesheet" href="'.config('path').$compiled.'">';
		}
		elseif ($type == 'js')
		{
			return '<script src="'.config('path').$compiled.'"></script>';
		}
	}

}