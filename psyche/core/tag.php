<?php
namespace Psyche\Core;

/**
 * Tag
 * 
 * A class for creating, manipulating and filtering HTML
 * content that resembles Javascript and is quite inspired
 * by jQuery in naming conventions. It can be used to create
 * HTML elements via an Object Oriented approach, to manipulate
 * their attributes and contents, and traverse the tree.
 *
 * @package Psyche\Core\Tag
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Tag
{

	/**
	 * @var array The tree of elements. Acts as the "DOM" tree.
	 */
	protected static $tree;

	/**
	 * @var string The final output.
	 */
	protected static $output;

	/**
	 * @var array Holds find() results.
	 */
	protected static $results;

	/**
	 * @var array Self closing tags.
	 */
	protected static $void_tags = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr',
										'img', 'input', 'keygen', 'link', 'meta', 'param',
										'source', 'track', 'wbr');

	/**
	 * @var string ID of the current element.
	 */
	protected $id;

	/**
	 * Constructor. Sets the ID.
	 * 
	 * @param string $id
	 */
	public function __construct ($id)
	{
		$this->id = $id;
	}

	/**
	 * Creates a new element.
	 * 
	 * @param string $tag Tag name. Ex: h1, p, a, etc.
	 * @return Tag
	 */
	public static function open ($tag)
	{
		// Generates a random string as ID, so elements
		// don't get overriden.
		$id = uniqid(time(), true);

		// Each element is created as a generic object.
		$el = new \stdClass;
		$el->id = $id;
		$el->tag = $tag;
		$el->attributes = array();
		$el->contents = array();
		$el->parent = null;
		$el->hide = false;

		// The tree is populated with the new element and the
		// ID as key so it can be search easily.
		static::$tree[$id] = $el;

		return new static($id);
	}

	/**
	 * Returns the element's generic object from the tree.
	 * It's mostly useful for nesting objects.
	 * 
	 * @return Object
	 */
	public function get ()
	{
		return static::$tree[$this->id];
	}

	/**
	 * Sets the HTML (contents) of the element. Multiple
	 * values can be passed, including objects (tag nesting).
	 * 
	 * @return Tag
	 */
	public function html ()
	{
		// Takes the generic object of the current element.
		$tag = static::$tree[$this->id];

		if (func_num_args())
		{
			$contents = array();
			$args = func_get_args();

			foreach ($args as $html)
			{
				// If the parameter is an object, it's "hide"
				// parameter is set so it doesn't get printed
				// as a main element by render(). Finally, the
				// parent is filled with the current element ID.
				if (is_object($html))
				{
					$html->hide = true;
					$html->parent = $tag->id;
				}

				$contents[] = $html;
			}

			$tag->contents = $contents;
		}

		return $this;
	}

	/**
	 * Sets elements attributes.
	 * 
	 * @return Tag
	 */
	public function attr ()
	{
		$args = func_get_args();

		// Elements can be an associative array, where
		// the key is the name of the attribute and 
		// value is the, uhm, value.
		if (is_array($args[0]))
		{
			$args = $args[0];
		}
		// Or pairs where odd elements are the names of the
		// attributes and even the values.
		else
		{
			$_args = array();
		    for ($i = 0, $count = count($args); $i < $count; $i += 2) { 
		        $_args[$args[$i]] = $args[$i + 1]; 
		    }

			$args = $_args;
		}

		// The element's attributes are populated. Values are passed to
		// htmlspecialchars() for safe usage.
		foreach ($args as $name => $value)
		{
			static::$tree[$this->id]->attributes[$name] = htmlspecialchars($value);
		}

		return $this;
	}

	/**
	 * Helper to set an ID attribute.
	 * 
	 * @param string $id
	 * @return Tag
	 */
	public function id ($id)
	{
		$this->attr('id', $id);

		return $this;
	}

	/**
	 * Helper to set a Class attribute. The underscore
	 * appended to the method name wasn't a choice, but
	 * a necessity as "class" is a reserved word.
	 * 
	 * @param string $class
	 * @return Tag
	 */
	public function _class ($class)
	{
		$this->attr('class', $class);

		return $this;
	}

	/**
	 * Helper to set a Value attribute.
	 * 
	 * @param string $value
	 * @return Tag
	 */
	public function val ($value)
	{
		$this->attr('val', $value);

		return $this;
	}

	/**
	 * Helper to set an Href attribute for links.
	 * 
	 * @param string $value
	 * @return Tag
	 */
	public function href ($value)
	{
		$this->attr('href', $value);

		return $this;
	}

	/**
	 * Helper to set a Src attribute for images.
	 * 
	 * @param string $value
	 * @return Tag
	 */
	public function src ($value)
	{
		$this->attr('src', $value);

		return $this;
	}

	/**
	 * Prepends HTML to an element. As with the html()
	 * method, objects can be added too. This is useful
	 * when the contents of an element are already set
	 * and others need to be added.
	 * 
	 * @param string|object $html
	 * @return Tag
	 */
	public function add ($html)
	{
		// If an object is passed, it's hide and parent
		// properties are set.
		if (is_object($html))
		{
			$html->hide = true;
			$html->parent = $this->id;
		}

		// The element's contents are set.
		static::$tree[$this->id]->contents[] = $html;

		return $this;
	}

	/**
	 * Retrieves the contents of an element.
	 * 
	 * @return array
	 */
	public function contents ()
	{
		return static::$tree[$this->id]->contents;
	}

	/**
	 * Returns child elements of the current one.
	 * 
	 * @return array|Tag
	 */
	public function children ()
	{
		$tag = static::$tree[$this->id];
		$return = array();

		// The elements contents are iterated to find
		// any objects.
		foreach ($tag->contents as $content)
		{
			if (is_object($content))
			{
				// A Tag object is initialized with the
				// element's ID, so it can be further
				// manipulated.
				$return[] = new self($content->id);
			}
		}

		// If no child was found, a Tag object is still
		// initialized, so no "method * on non object"
		// error is triggered when used by the client.
		if (!count($return))
		{
			$return = new self;
		}

		return $return;
	}

	/**
	 * Returns the element's parent (if any).
	 * 
	 * @return Tag
	 */
	public function parent ()
	{
		$tag = static::$tree[$this->id];
		
		return new self($tag->parent);
	}

	/**
	 * Returns the previous element, relative
	 * to the current one.
	 * 
	 * @return Tag
	 */
	public function prev ()
	{
		return $this->prev_or_next('prev');
	}

	/**
	 * Returns the next element, relative to the
	 * current one.
	 * 
	 * @return Tag
	 */
	public function next ()
	{
		return $this->prev_or_next('next');
	}

	/**
	 * Finds the next or previous elements, called
	 * from the prev() or next() method.
	 * 
	 * @param string $type Next or prev
	 * @return Tag
	 */
	protected function prev_or_next ($type)
	{
		$nodes = $this->clean();

		$method = 'next';

		// On a next() call, iteration is reversed,
		// so the array pointer is set in the end.
		if ($type == 'next')
		{
			end($nodes);
			$method = 'prev';
		}

		// Iterates through the array of elements
		// by moving the pointer too. When the current
		// element is found, iteration is stopped.
		while ($next = $method($nodes))
		{
			$id = key($nodes);

			if ($id == $this->id)
			{
				break;
			}
		}

		// Based on the type, the next or prev element
		// is retrieved via the PHP's functions next()
		// or prev().
		$prev_next = $type($nodes);

		// The array is reset, so it's safe to be used
		// on another request.
		reset($nodes);

		if (isset($prev_next))
		{
			return new self($prev_next->id);
		}

		return new self;
	}

	/**
	 * Returns sibling elements relative to the current one.
	 * 
	 * @return Tag
	 */
	public function siblings ()
	{
		$nodes = $this->clean();
		$return = array();

		foreach ($nodes as $node)
		{
			$return[] = new self($node->id);
		}

		if (!count($return))
		{
			$return = new self;
		}

		return $return;
	}

	/**
	 * Cleans the tree of hidden elements or element
	 * contents from non-objects.
	 * 
	 * @return array
	 */
	protected function clean ()
	{
		$tag = static::$tree[$this->id];

		// If it's not hidden, it means that it's an
		// element with no parent.
		if (!$tag->hide)
		{
			$tree = static::$tree;
			$nodes = array();

			// Hidden elements are removed.
			foreach ($tree as $id => $node)
			{
				if (!$node->hide)
				{
					$nodes[$id] = $node;
				}
			}
		}
		// Otherwise, it's a child element.
		else
		{
			// Parent contents are retrieved.
			$contents = static::$tree[$this->get()->parent]->contents;
			$nodes = array();

			// Anything other then objects is discarded.
			foreach ($contents as $node)
			{
				if (is_object($node))
				{
					$nodes[$node->id] = $node;
				}
			}
		}

		return $nodes;
	}

	/**
	 * Returns all the found elements from find().
	 * 
	 * @return Tag
	 */
	public function all ()
	{
		$results = static::$results;
		static::$results = array();

		if (count($results))
		{
			return $results;
		}

		return new self;
	}

	/**
	 * Returns the first element from find().
	 * 
	 * @return Tag
	 */
	public function first ()
	{
		$results = static::$results;
		static::$results = array();

		if (count($results))
		{
			return $results[0];
		}

		return new self;
	}

	/**
	 * Returns the last element from find().
	 * 
	 * @return Tag
	 */
	public function last ()
	{
		$results = static::$results;
		static::$results = array();

		if (count($results))
		{
			return $results[count($results) - 1];
		}

		return new self;
	}

	/**
	 * Returns only the element with the specified index
	 * from find(). Zero-based.
	 * 
	 * @param int $index
	 * @return Tag
	 */
	public function eq ($index = 0)
	{
		$results = static::$results;
		static::$results = array();

		if (count($results) and isset($results[$index]))
		{
			return $results[$index];
		}

		return new self;
	}

	/**
	 * Same as eq() but One-based.
	 * 
	 * @param int $index
	 * @return Tag
	 */
	public function index ($index = 1)
	{
		$results = static::$results;
		static::$results = array();

		if (count($results) and isset($results[$index - 1]))
		{
			return $results[$index - 1];
		}

		return new self;
	}

	/**
	 * Applies a callback function to each element from find().
	 * This is useful when the properties of a bunch of elements
	 * needs to be changed.
	 * 
	 * @param closure $callback
	 * @return array An array with the modified results
	 */
	public function each ($callback)
	{
		$results = static::$results;
		static::$results = array();

		// As the callback function modifies the object
		// directly, there's no need for it to return values
		// or pass the array elements by reference in the iteration.
		foreach ($results as $r)
		{
			call_user_func($callback, $r);
		}

		return $results;
	}

	/**
	 * Searches for elements inside the tree and returns the results
	 * via one of the above mentioned methods. It supports element,
	 * id (::find('#id')) and class (::find('.class') or ::find('el.class'))
	 * searching.
	 * 
	 * @param string $what The search term.
	 * @return array|Tag
	 */
	public static function find ($what)
	{
		return static::make_find($what);
	}

	/**
	 * Makes the search. The find() method is just a caller to this one, so
	 * that attributes aren't exposed to the client. This method will run
	 * recursively until the last nested object is found.
	 * 
	 * @param string $what The search term.
	 * @param string $id
	 * @param string $class
	 * @param object $active_node
	 * @return array|Tag
	 */
	protected static function make_find ($what, $id = null, $class = null, $active_node = null)
	{
		$node = static::$tree;
		if (isset($active_node))
		{
			$node = $active_node;
		}

		// If it's an ID search, get the ID.
		if (strpos($what, '#') !== false)
		{
			list($what, $id) = explode('#', $what);
		}
		// Or if it's a class search, get the class.
		elseif (strpos($what, '.') !== false)
		{
			list($what, $class) = explode('.', $what);
		}

		foreach ($node as $tag)
		{
			// If the tag is hidden and the iteration is yet
			// in the main tree, skip the iteration. Hidden
			// elements are childs of others and will be
			// checked recursively.
			if ($tag->hide and !isset($active_node)) continue;

			// If it is an ID search and the tag's ID is the same
			// as the one searched for, initialize the object and
			// break the iteration (one ID per page, remember?).
			if (isset($id) and $tag->attributes['id'] == $id)
			{
				static::$results[] = new static($tag->id);
				break;
			}
			// If it's a class search but without the element set ('.class'),
			// check if the element's class is the same as the one searched for.
			elseif (empty($what) and isset($class) and $tag->attributes['class'] == $class)
			{
				static::$results[] = new static($tag->id);
			}
			// Finally, check if the tag is the same as the one searched for.
			elseif ($tag->tag == $what)
			{
				// When the class isn't set, it's an element search ('p', 'h1', etc).
				if (!isset($class))
				{
					static::$results[] = new static($tag->id);
				}
				// Otherwise, it's an element with class ('p.class') search and it
				// should match the class too.
				elseif ($tag->attributes['class'] == $class)
				{
					static::$results[] = new static($tag->id);
				}
			}

			// Contents are iterated and for each object, the same method
			// is called again. The recursion will stop until the last
			// nested object.
			foreach ($tag->contents as $content)
			{
				if (is_object($content))
				{
					static::make_find($what, $id, $class, array($content));
				}
			}
		}

		return new static;
	}

	/**
	 * Renders the HTML output. It's similiar to make_find(),
	 * as it searches the tree recursively for nested objects.
	 * 
	 * @param object $active_node
	 * @return string
	 */
	public static function render ($active_node = null)
	{
		// On the first run, the active method will be the tree.
		// The proceeding recursions will set the active to the
		// nested objects.
		if (isset($active_node))
		{
			$node = $active_node;
		}
		else
		{
			$node = static::$tree;
		}

		foreach ($node as $tag)
		{
			if ($tag->hide and !isset($active_node)) continue;

			static::$output .= static::open_tag($tag);

			foreach ($tag->contents as $content)
			{
				// Calls itself for objects, so each of them
				// gets rendered.
				if (is_object($content))
				{
					static::render(array($content));
				}
				// Otherwise, just take the content as is.
				else
				{
					static::$output .= $content;
				}
			}

			static::$output .= static::close_tag($tag);
		}

		return static::$output;
	}

	/**
	 * Builds an opening tag.
	 * 
	 * @param string $tag
	 * @return string
	 */
	protected static function open_tag ($tag)
	{
		$return = '<'.$tag->tag;

		if (count($tag->attributes))
		{
			foreach ($tag->attributes as $name => $value)
			{
				$return .= ' '.$name.'="'.$value.'"';
			}
		}

		$return .= '>';

		return $return;
	}

	/**
	 * Builds a closing tag.
	 * 
	 * @param string $tag
	 * @return string
	 */
	protected static function close_tag ($tag)
	{
		if (!in_array($tag->tag, static::$void_tags))
		{
			return '</'.$tag->tag.'>';
		}
	}

}