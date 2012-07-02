<?php
namespace Psyche\Core\View;

/**
 * Mold Template Engine
 * 
 * A simple, efficient and easy to use pseudo-code parser that compiles templates
 * into native PHP code. In addition to variable echoing, conditionals and iterators,
 * it has some nice inheritance capabilities. Files are compiled once and served from
 * cache until the original template file is changed. Overhead is minimal, even when
 * compile happens, as there are only a few simple regular expressions that parse
 * Mold syntax. It isn't supposed to be called directly, but will be run by Psyche\Core\View
 * when mold template files (defaults to .mold.php) are found.
 *
 * @package Psyche\Core\View\Mold
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Mold
{

	/**
	 * @var string Filename of the template file without the path or extension
	 */
	protected static $filename;

	/**
	 * @var string Path of the compiled file
	 */
	protected static $compiled;

	/**
	 * @var string Path of the template file
	 */
	protected static $file;

	/**
	 * @var string Contents of the template file
	 */
	protected static $contents;

	/**
	 * @var string Path of the parent if inheritance is detected
	 */
	protected static $parent;

	/**
	 * @var string Name of the parent without the path or extension
	 */
	protected static $use;

	/**
	 * @var array List of available parses. Each one will call a class method
	 */
	protected static $parsers = array(
		'comments', 'use', 'partials', 'reserves', 'includes', 'structures', 'echo', 'setters'
	);

	/**
	 * Starts the Mold Engine.
	 * 
	 * @param string $file Template filename
	 * 
	 * @return string
	 */
	public static function run ($file)
	{
		static::$filename = pathinfo($file, PATHINFO_BASENAME);
		static::$file = $file;

		static::$contents = file_get_contents(static::$file);

		// Matches the {use 'file'} syntax to check for any defined inheritance.
		// From the returned matches, the parent's name and path are set.
		if (preg_match("|\{\s*use\s+'(.+?)'\s*\}|i", static::$contents, $matches))
		{
			static::$use = $matches[1];
			static::$parent = config('views path').$matches[1];
			if (pathinfo(static::$parent, PATHINFO_EXTENSION) == '')
			{
				static::$parent .= config('mold extension');
			}
		}

		static::$compiled = 'stash/views/'.md5(static::$filename).'.php';

		// Will only parse the template if it hasn't expired yet. Otherwise
		// the existing, compiled file will be used.
		if (static::expired())
		{
			static::parse();
			static::save();
		}

		return static::$compiled;
	}

	/**
	 * Iterates through the available parsers.
	 * 
	 * @return void
	 */
	protected static function parse ()
	{
		foreach (static::$parsers as $parser)
		{
			$method = 'parse_'.$parser;
			static::$method();
		}
	}

	/**
	 * Parses {use 'file'} syntax for defining a parent.
	 * 
	 * @return null|void
	 */
	protected static function parse_use ()
	{
		// If @var $parent wasn't set in the constructore, no parent was specified.
		if (!isset(static::$parent))
		{
			return;
		}

		static::$contents = preg_replace("|\{\s*use\s+'".static::$use."'\s*\}\n*|i", file_get_contents(static::$parent), static::$contents);
	}

	/**
	 * Parses {partial 'name'} syntax for defining inheritance blocks.
	 * The found instances will be put into their corresponding {reserve 'name'} blocks.
	 * 
	 * @return null|void
	 */
	protected static function parse_partials ()
	{
		if (!isset(static::$parent))
		{
			return;
		}

		preg_match_all("|\{\s*partial\s+'(.+?)'\s*\}\n*(.+?)\n*\{\s*/partial\s*\}\n*|is", static::$contents, $matches);

		$find = $matches[0];
		$partials = $matches[1];
		$inner = $matches[2];

		if (count($partials))
		{
			$i = 0;
			foreach ($partials as $partial)
			{
				// Each partial is confronted with a {reserve} of the same name. If it exists,
				// the partial content will be insterted into the parent.
				if (preg_match("|\{\s*reserve\s+'".$partial."'\s*\}|i", static::$contents, $matches))
				{
					static::$contents = preg_replace("|\{\s*reserve\s+'".$partial."'\s*\}(\n*(.+?)\n*\{/reserve\})?|is", $inner[$i], static::$contents);
					static::$contents = str_replace($find[$i], '', static::$contents);
				}

				$i++;
			}
		}
	}

	/**
	 * Parses block reserves with the {reserve 'name'}default value{/reserve} syntax.
	 * Those will be compiled only if no partial used them.
	 * 
	 * @return void
	 */
	protected static function parse_reserves ()
	{
		static::$contents = preg_replace("|\{\s*reserve\s+'(.+?)'\s*\}\n*(.+?)\n*\{/reserve\}|is", '$2', static::$contents);
	}

	/**
	 * Parses variables echos with the {{$var}} syntax.
	 * 
	 * @return void
	 */
	protected static function parse_echo ()
	{
		static::$contents = preg_replace('|\{\{\s*(.+?)\s*\}\}|', "<?= $1; ?>", static::$contents);
	}

	/**
	 * Parses control structures: if, elseif, else, foreach, for and while.
	 * Parses endings, break and continue too.
	 * 
	 * @return void
	 */
	protected static function parse_structures ()
	{
		static::$contents = preg_replace('/\{\s*((if|elseif|foreach|for|while)\s*(.+?))\s*\}/i', "<?php $2 ($3): ?>", static::$contents);
		static::$contents = preg_replace('|\{\s*else\s*\}|i', "<?php else: ?>", static::$contents);
		static::$contents = preg_replace('|\{\s*\/if\s*\}|i', "<?php endif; ?>", static::$contents);
		static::$contents = preg_replace('|\{\s*\/foreach\s*\}|i', "<?php endforeach; ?>", static::$contents);
		static::$contents = preg_replace('|\{\s*\/for\s*\}|i', "<?php endfor; ?>", static::$contents);
		static::$contents = preg_replace('|\{\s*\/while\s*\}|i', "<?php endwhile; ?>", static::$contents);
		static::$contents = preg_replace('/\{\s*(continue|break)\s*\}/i', "<?php $1; ?>", static::$contents);
	}

	/**
	 * Parses includes with the {include 'file'} syntax. This doesn't get
	 * compiled as a normal PHP include, as the included file's content
	 * wouldn't be parsed. Instead, the file content is read and replaced
	 * with the pseudo-function.
	 * 
	 * @return void
	 */
	protected static function parse_includes ()
	{
		if (preg_match_all("|\{\s*include\s+'(.+?)'\s*\}|i", static::$contents, $matches))
		{
			$finds = $matches[0];
			$includes = $matches[1];

			$i = 0;

			// Every included file is read and put in it's position.
			foreach ($includes as $include)
			{
				// Files without extension will have it added automatically.
				if (pathinfo($include, PATHINFO_EXTENSION) == '')
				{
					$include .= config('mold extension');
				}

				$file = config('views path').$include;

				if (file_exists($file))
				{
					static::$contents = str_replace($finds[$i], file_get_contents($file), static::$contents);
				}
				$i++;
			}

		}
	}

	/**
	 * Parses comments with the {* Some comment *} syntax. They are written as PHP comments, so
	 * there will be no trace of them in the HTML output.
	 * 
	 * @return void
	 */
	protected static function parse_comments ()
	{
		static::$contents = preg_replace('|\{\s*\*(.+?)\*\s*\}|', "<?php //$1; ?>", static::$contents);
	}

	/**
	 * Parses generic variables with the syntax {$var}, {$var++}, {$var = 'value'}, etc. It's here mostly
	 * to provide an option to set variables in the view file, something that should be avoided
	 * and done in the controller (where the logic resides). However, it can be useful on those cases
	 * when a where() needs to be used and the variable is incremented/decremented dynamically in the view.
	 * 
	 * @return void
	 */
	protected static function parse_setters ()
	{
		static::$contents = preg_replace('|\{\s*\$(.+?)\s*\}|', "<?php $$1; ?>", static::$contents);
	}

	/**
	 * Checks if the compiled template file has expired. Expiration is based upon files modification
	 * time, which is a very simple, but rather effective way to provide caching. Checking is not
	 * only done for the original template, but for parents too.
	 * 
	 * @return bool
	 */
	protected static function expired ()
	{
		$return = false;

		// If the compiled template's modification time is lower then the original's, it
		// means that it needs to be recompiled. In the elseif() part, the original's
		// modification time is checked with the parent template (if it exists).
		if (filemtime(static::$compiled) < filemtime(static::$file))
		{
			$return = true;
		}
		elseif (isset(static::$parent) and filemtime(static::$compiled) < filemtime(static::$parent))
		{
			$return = true;
		}

		return $return;
	}

	/**
	 * Saves the compiled template.
	 * 
	 * @return void
	 */
	protected static function save ()
	{
		if (is_writeable('stash/views'))
		{
			file_put_contents(static::$compiled, static::$contents);
		}
		else
		{	
			throw new \Exception("stash/views/ is not writable");
		}
	}
	
}