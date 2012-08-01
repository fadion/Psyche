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
	protected static $extends;

	/**
	 * @var array Holds temporarily contents of raw blocks
	 */
	protected static $raw = array();

	/**
	 * @var array List of available parses. Each one will call a class method
	 */
	protected static $parsers = array(
		'start_raw', 'comments', 'extends', 'uses', 'blocks', 'includes', 'structures', 'echo', 'generics', 'end_raw'
	);

	/**
	 * Starts the Mold Engine.
	 * 
	 * @param string $file Template filename
	 * @return string
	 */
	public static function run ($file)
	{
		static::$filename = pathinfo($file, PATHINFO_BASENAME);
		static::$file = $file;

		static::$contents = file_get_contents(static::$file);

		// Matches the {% extends 'file' %} syntax to check for any defined inheritance.
		// From the returned matches, the parent's name and path are set.
		if (preg_match("/\{%\s*extends\s+(?:'|".'"'."){0,1}(.+?)(?:'|".'"'."){0,1}\s*%\}/i", static::$contents, $matches))
		{
			static::$extends = $matches[1];
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
	 * Parses {% extends 'file' %} syntax for defining a parent.
	 * 
	 * @return null|void
	 */
	protected static function parse_extends ()
	{
		// If @var $parent wasn't set in the constructor, no parent was specified.
		if (!isset(static::$parent))
		{
			return;
		}

		static::$contents = preg_replace("/\{%\s*extends\s+(?:'|".'"'."){0,1}".preg_quote(static::$extends)."(?:'|".'"'."){0,1}\s*%\}\n*/i", file_get_contents(static::$parent), static::$contents);
	}

	/**
	 * Parses {% use 'name' %} syntax for defining children blocks.
	 * The found instances will be put into their corresponding {% use 'name' %} blocks.
	 * 
	 * @return null|void
	 */
	protected static function parse_uses ()
	{
		if (!isset(static::$parent))
		{
			return;
		}

		if (preg_match_all("/\{%\s*use\s+(?:'|".'"'."){0,1}(.+?)(?:'|".'"'."){0,1}\s*%\}\n*(.+?)\n*\{%\s*enduse\s*%\}\n*/is", static::$contents, $matches))
		{
			$find = $matches[0];
			$replaces = $matches[1];
			$inner = $matches[2];

			if (count($replaces))
			{
				$i = 0;
				foreach ($replaces as $use)
				{
					// Each use block is confronted with a {% block %} of the same name. If it exists,
					// the use block content will be insterted into the parent.
					if (preg_match("/\{%\s*block\s+(?:'|".'"'."){0,1}".preg_quote($use)."(?:'|".'"'."){0,1}\s*%\}/i", static::$contents, $matches))
					{
						static::$contents = preg_replace("/\{%\s*block\s+(?:'|".'"'."){0,1}".$use."(?:'|".'"'."){0,1}\s*%\}(\n*(.+?)\n*\{%\s*endblock\s*%\})?/is", $inner[$i], static::$contents);
						static::$contents = str_replace($find[$i], '', static::$contents);
					}

					$i++;
				}
			}
		}
	}

	/**
	 * Parses block reserves with the {% block 'name' %}default value{% endblock %} syntax,
	 * only if no {% use %} block used it. Finally, it removes unused blocks and use clauses
	 * so no parse errors get thrown to the client.
	 * 
	 * @return void
	 */
	protected static function parse_blocks ()
	{
		static::$contents = preg_replace("/\{%\s*block\s+(?:'|".'"'."){0,1}(.+?)(?:'|".'"'."){0,1}\s*%\}\n*(.+?)\n*\{%\s*endblock\s*%\}/is", '$2', static::$contents);
		static::$contents = preg_replace("/\{%\s*use\s+(?:'|".'"'."){0,1}(.+?)(?:'|".'"'."){0,1}\s*%\}\n*(.+?)\n*\{%\s*enduse\s*%\}\n*/is", '', static::$contents);
		static::$contents = preg_replace("/\{%\s*block\s+(?:'|".'"'."){0,1}(.+?)(?:'|".'"'."){0,1}\s*%\}/i", '', static::$contents);
		static::$contents = preg_replace("/\{%\s*extends\s+(?:'|".'"'."){0,1}(.+?)(?:'|".'"'."){0,1}\s*%\}\n*/i", '', static::$contents);
	}

	/**
	 * Parses echos with the {{ $var }} syntax.
	 * 
	 * @return void
	 */
	protected static function parse_echo ()
	{
		if (preg_match_all('/\{\{\s*(.+?)\s*\}\}/', static::$contents, $matches))
		{
			$finds = $matches[0];
			$replaces = $matches[1];

			for ($i = 0, $count = count($replaces); $i < $count; $i++)
			{
				$replace = $replaces[$i];

				// Data is output raw only if a "raw" filter is set or automatic
				// escaping is disabled.
				if (strpos($replace, '|raw') or config('mold automatic escaping') == 0)
				{
					$replace = str_replace('|raw', '', $replace);
					$replace = "<?php echo ".$replace." ?>";
				}
				else
				{
					// Data is escaped.
					$replace = "<?php echo htmlspecialchars(stripslashes(".$replace."), ENT_QUOTES, 'UTF-8'); ?>";
				}

				static::$contents = str_replace($finds[$i], $replace, static::$contents);
			}
		}
	}

	/**
	 * Parses control structures: {% if %}, elseif, else, foreach, for and while.
	 * Parses endings, break and continue too.
	 * 
	 * @return void
	 */
	protected static function parse_structures ()
	{
		static::$contents = preg_replace('/\{%\s*((if|elseif|foreach|for|while)\s*(.+?))\s*%\}/i', "<?php $2 ($3): ?>", static::$contents);
		static::$contents = preg_replace('/\{%\s*else\s*%\}/i', "<?php else: ?>", static::$contents);
		static::$contents = preg_replace('/\{%\s*end(if|foreach|for|while)\s*%\}/', "<?php end$1; ?>", static::$contents);
		static::$contents = preg_replace('/\{%\s*(continue|break)\s*%\}/i', "<?php $1; ?>", static::$contents);
	}

	/**
	 * Parses includes with the {% include 'file' %} syntax. They don't get
	 * compiled as normal PHP includes, as the included file's content
	 * wouldn't be parsed. Instead, the file contents are read.
	 * 
	 * @return void
	 */
	protected static function parse_includes ()
	{
		if (preg_match_all("/\{%\s*include\s+(?:'|".'"'."){0,1}(.+?)(?:'|".'"'."){0,1}\s*%\}/i", static::$contents, $matches))
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
	 * Parses comments with the {# Some comment #} syntax.
	 * They are written as block PHP comments, so
	 * there will be no trace of them in the HTML output.
	 * 
	 * @return void
	 */
	protected static function parse_comments ()
	{
		static::$contents = preg_replace('/\{#\s*(.+?)\s*#\}/s', "<?php /*$1*/; ?>", static::$contents);
	}

	/**
	 * Parses generic PHP code.
	 * 
	 * @return void
	 */
	protected static function parse_generics ()
	{
		static::$contents = preg_replace('/\{%\s*(.+?)\s*%\}/', "<?php $1; ?>", static::$contents);
	}

	/**
	 * Parses raw output with the {% raw %}{% endraw %} syntax.
	 * To prevent processing of the data inside a raw block, each
	 * found instance is held in an array and output in the end
	 * of parsing via parse_end_raw().
	 * 
	 * @return void
	 */
	protected static function parse_start_raw ()
	{
		if (preg_match_all("/\{%\s*raw\s*%\}\n*(.+?)\n*\{%\s*endraw\s*%\}/is", static::$contents, $matches))
		{
			$finds = $matches[0];
			$replaces = $matches[1];

			static::$raw = $replaces;

			for ($i = 0, $count = count($replaces); $i < $count; $i++)
			{
				// Each raw block is replaced with a placeholder that's going to be replaced again
				// in the end of parsing.
				static::$contents = str_replace($finds[$i], '#####@raw'.$i.'@#####', static::$contents);
			}
		}
	}

	/**
	 * Puts raw data in their place.
	 * 
	 * @return void
	 */
	protected static function parse_end_raw ()
	{
		if (count(static::$raw))
		{
			// Matches all the placeholders for raw data.
			if (preg_match_all("/#####@raw(\d)@#####/", static::$contents, $matches))
			{
				$finds = $matches[0];
				$replaces = static::$raw;

				for ($i = 0, $count = count($replaces); $i < $count; $i++)
				{
					// Each placeholder is replaced with the correct raw data.
					static::$contents = str_replace($finds[$i], $replaces[$i], static::$contents);
				}
			}
		}
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