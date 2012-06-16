<?php
namespace FW\Core;
use FW\Core\Response;

/**
 * Psyc Template Engine
 * 
 * A simple, efficient and easy to use pseudo-code parser that compiles templates
 * into native PHP code. In addition to variable echoing, conditionals and iterators,
 * it has some nice inheritance capabilities. It depends on FW\Core\View for
 * template variables assigning and rendering.
 *
 * @package FW\Core\Psyc
 * @see FW\Core\View
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Psyc extends \FW\Core\View
{

	/**
	 * @var string Filename of the template file without the path or extension.
	 */
	protected $filename;

	/**
	 * @var string Contents of the template file.
	 */
	protected $contents;

	/**
	 * @var string Path of the parent if inheritance is detected.
	 */
	protected $parent;

	/**
	 * @var string Name of the parent without the path or extension.
	 */
	protected $use;

	/**
	 * @var array List of available parses. Each one will call a class method.
	 */
	protected $parsers = array(
		'use', 'partials', 'comments', 'core', 'echo', 'if', 'foreach', 'for', 'includes', 'generics'
	);

	/**
	 * Class constructor. Inherits parent constructor for a few, shared operations.
	 * 
	 * @param string $file Template filename
	 * @param mixed $vars Template variables
	 * @return void
	 */
	public function __construct ($file, $vars)
	{
		$this->filename = $file;
		
		parent::__construct($file, $vars);

		$this->contents = file_get_contents($this->file);

		// Matches the {use 'file'} syntax to check for any defined inheritance.
		// From the returned matches, the parent's name and path are set.
		if (preg_match("|\{\s*use\s+'(.+)'\s*\}|i", $this->contents, $matches))
		{
			$this->use = $matches[1];
			$this->parent = config('views path').$matches[1];
			if (pathinfo($this->parent, PATHINFO_EXTENSION) == '')
			{
				$this->parent .= '.php';
			}
		}

		// Will only parse the template if it hasn't expired yet. Otherwise
		// the existing, compiled file will be used.
		if ($this->expired())
		{
			$this->parse();
			$this->save();
		}
	}

	/**
	 * Iterates through the available parsers.
	 * 
	 * @return void
	 */
	protected function parse ()
	{
		foreach ($this->parsers as $parser)
		{
			$method = 'parse_'.$parser;
			if (method_exists($this, $method))
			{
				$this->$method();
			}
		}
	}

	/**
	 * Parses {use 'file'} syntax for defining a parent.
	 * 
	 * @return null|void
	 */
	protected function parse_use ()
	{
		// If @var $parent wasn't set in the constructore, no parent was specified.
		if (!isset($this->parent))
		{
			return;
		}

		$this->contents = preg_replace("|\{\s*use\s+'".$this->use."'\s*\}\n*|i", file_get_contents($this->parent), $this->contents);
	}

	/**
	 * Parses {partial 'name'} syntax for defining inheritance blocks.
	 * The found instances will be put into their corresponding parent blocks.
	 * 
	 * @return null|void
	 */
	protected function parse_partials ()
	{
		if (!isset($this->parent))
		{
			return;
		}

		preg_match_all("|\{\s*partial\s+'(.+)'\s*\}\n*(.+)\n*\{\s*/partial\s*\}\n*|i", $this->contents, $matches);

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
				if (preg_match("|\{\s*reserve\s+'".$partial."'\s*\}|i", $this->contents, $matches))
				{
					$this->contents = preg_replace("|\{\s*reserve\s+'".$partial."'\s*\}|", $inner[$i], $this->contents);
					$this->contents = str_replace($find[$i], '', $this->contents);
				}

				$i++;
			}
		}
	}

	/**
	 * Parses variables echos with the {{$var}} syntax.
	 * 
	 * @return void
	 */
	protected function parse_echo ()
	{
		$this->contents = preg_replace('|\{\{\s*(.+?)\s*\}\}|', "<?= $1; ?>", $this->contents);
	}

	/**
	 * Parses {if}, {elseif}, {else} and {/if}.
	 * 
	 * @return void
	 */
	protected function parse_if ()
	{
		$this->contents = preg_replace('|\{\s*if\s+(.+)\s*\}|i', "<?php if ($1): ?>", $this->contents);
		$this->contents = preg_replace('|\{\s*elseif\s+(.+)\s*\}|i', "<?php elseif ($1): ?>", $this->contents);
		$this->contents = preg_replace('|\{\s*else\s*\}|i', "<?php else: ?>", $this->contents);
		$this->contents = preg_replace("|\{\s*/if\s*\}|i", "<?php endif; ?>", $this->contents);
	}

	/**
	 * Parses {foreach} and {/foreach}.
	 * 
	 * @return void
	 */
	protected function parse_foreach ()
	{
		$this->contents = preg_replace('|\{\s*foreach\s+(.+)\s*\}|i', "<?php foreach ($1): ?>", $this->contents);
		$this->contents = preg_replace("|\{\s*/foreach\s*\}|i", "<?php endforeach; ?>", $this->contents);
	}

	/**
	 * Parses {for} and {/for}.
	 * 
	 * @return void
	 */
	protected function parse_for ()
	{
		$this->contents = preg_replace('|\{\s*for\s+(.+)\s*\}|i', "<?php for ($1): ?>", $this->contents);
		$this->contents = preg_replace("|\{\s*/for\s*\}|i", "<?php endfor; ?>", $this->contents);
	}

	/**
	 * Parses core classes calls with a special syntax: {% Class::method() %}. The main purpose
	 * is to provide a simple access to namespaced classes, removing the need to write
	 * \FW\Core\Class::method().
	 * 
	 * @return void
	 */
	protected function parse_core ()
	{
		$this->contents = preg_replace('|\{\{%\s+(.+)::(.+)\s+%\}\}|', '<?= FW\Core\\\$1::$2; ?>', $this->contents);
		$this->contents = preg_replace('|\{%\s+(.+)::(.+)\s+%\}|', 'FW\Core\\\$1::$2', $this->contents);
	}

	/**
	 * Parses includes with the {include 'file'} syntax. As included files will always be views,
	 * the path is automatically fixed to point the right directory.
	 * 
	 * @return void
	 */
	protected function parse_includes ()
	{
		$this->contents = preg_replace("|\{\s*include\s+'(.+)'\s*\}|i", "<?php include('".config('views path')."$1'); ?>", $this->contents);
	}

	/**
	 * Parses comments with the {* Some comment *} syntax. They are written as PHP comments, so
	 * there will be no trace of them in the HTML output.
	 * 
	 * @return void
	 */
	protected function parse_comments ()
	{
		$this->contents = preg_replace('|\{\s*\*(.+)\*\s*\}|', "<?php //$1; ?>", $this->contents);
	}

	/**
	 * Parses generic variables with the syntax {$var}, {$var = 'value'}, etc. It's here mostly
	 * to provide an option to set variables in the view file, something that should be avoided
	 * and done in the controller (where the logic resides). However, this framework doesn't
	 * restrict anyone's coding style.
	 * 
	 * @return void
	 */
	protected function parse_generics ()
	{
		$this->contents = preg_replace('|\{\s*\$(.+)\s*\}|', "<?php $$1; ?>", $this->contents);
	}

	/**
	 * Checks if the compiled template file has expired. Expiration is based upon files modification
	 * time, which is a very simple, but rather effective way to provide caching. Checking is not
	 * only done for the original template, but for parents too.
	 * 
	 * @return bool
	 */
	protected function expired ()
	{
		$return = false;
		$original = $this->file;

		$this->file = 'stash/views/'.md5($this->filename).'.php';

		// If the compiled template's modification time is lower then the original's, it
		// means that it needs to be recompiled. In the elseif() part, the original's
		// modification time is checked with the parent template (if it exists).
		if (filemtime($this->file) < filemtime($original))
		{
			$return = true;
		}
		elseif (isset($this->parent) and filemtime($this->file) < filemtime($this->parent))
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
	protected function save ()
	{
		file_put_contents($this->file, $this->contents);
	}
	
}