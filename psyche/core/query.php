<?php
namespace Psyche\Core;
use Psyche\Core\DB;

/**
 * Query Builder
 * 
 * A feature-rich and quite handy class to build SQL code.
 * It ticks (adds `) table and field names to prevent reserved
 * words errors and escapes input automatically. Generally, it's
 * very similiar in usage to SQL dialiects, but offers some handy
 * helper functions too.
 *
 * @package Psyche\Core\Query
 * @author Fadion Dashi
 * @version 1.5
 * @since 1.0
 */
class Query
{

	/**
	 * @var array Keywords that will be used to build the SQL code.
	 */
	protected $query = array(
		'insert' 		=> '',
		'update'		=> '',
		'delete'		=> '',
		'select'	  	=> '',
		'distinct'		=> '',
		'from'		  	=> '',
		'join'		  	=> array(),
		'left_join'	  	=> array(),
		'right_join'  	=> array(),
		'on'			=> array(),
		'using'			=> array(),
		'where'		 	=> '',
		'group'			=> '',
		'order'		  	=> '',
		'limit'		  	=> ''
	);

	/**
	 * Constructor. Calls the appropriate method depending on
	 * the type (select, insert, update and delete).
	 * 
	 * @param string|array $fields
	 * @param string $type
	 * @param string|array $parameters
	 * 
	 * @return object
	 */
	public function __construct ($fields, $type, $parameters = null)
	{
		switch ($type)
		{
			case 'select':
				return $this->make_select($fields);
				break;
			case 'insert':
				return $this->make_insert($fields, $parameters);
				break;
			case 'update':
				return $this->make_update($fields, $parameters);
				break;
			case 'delete':
				return $this->make_delete($fields);
				break;
		}
	}

	/**
	 * Factory static method. Initializes a select.
	 * 
	 * @param string|array $fields
	 * 
	 * @return object
	 */
	public static function select ($fields = '*')
	{
		return new static($fields, 'select');
	}

	/**
	 * Factory static method. Initializes an insert.
	 * 
	 * @param string $table
	 * @param string|array $fields
	 * 
	 * @return object
	 */
	public static function insert ($table, $fields)
	{
		return new static($fields, 'insert', $table);
	}

	/**
	 * Factory static method. Initializes an update.
	 * 
	 * @param string $table
	 * @param string|array $fields
	 * 
	 * @return object
	 */
	public static function update ($table, $fields)
	{
		return new static($fields, 'update', $table);
	}

	/**
	 * Factory static method. Initializes a delete.
	 * 
	 * @param string|array $fields
	 * 
	 * @return object
	 */
	public static function delete ($fields)
	{
		return new static($fields, 'delete');
	}

	/**
	 * Makes the SELECT part of the query.
	 * 
	 * @param string|array $fields
	 * 
	 * @return void
	 */
	protected function make_select ($fields)
	{
		// Fields can be an array or just a string.
		// If string is the case, it's exploded to an array.
		if (!is_array($fields))
		{
			$fields = explode(',', $fields);
		}

		$select_pieces = array();

		foreach ($fields as $field)
		{
			$table = '';
			$as = '';
			$function = '';
			$function_end = '';

			$field = trim($field);
			
			// Check for SQL functions and parse them so the individual
			// field can be ticked. It works even for nested functions.
			if (strpos($field, '(') !== false)
			{
				$function = substr($field, 0, strrpos($field, '(') + 1);
				$function_end = str_repeat(')', substr_count($function, '('));
				$function = $function.'###'.$function_end;

				$field = substr($field, strrpos($field, '(') + 1, strpos($field, ')'));
				$field = str_replace(')', '', $field);
			}

			list($field, $as) = $this->fix_as($field);
			list($field, $table) = $this->fix_dot($field);

			// A star (*) doesn't need to be ticked.
			if ($field !== '*')
			{
				$field = $this->tick($field);
			}

			// If a function was found above, it's content is replaced
			// with the ticked field.
			if ($function != '')
			{
				$field = str_replace('###', $table.$field, $function);
			}
			else
			{
				$field = $table.$field;
			}

			$select_pieces[] = $field.$as;
		}

		$select = implode(', ', $select_pieces);
		
		$this->query['select'] = $select;
	}

	/**
	 * Makes the INSERT part of the query.
	 * 
	 * @param string|array $fields
	 * @param string $table
	 * 
	 * @return void
	 */
	protected function make_insert ($fields, $table)
	{
		// Table columns are the array keys, while the data
		// to be inserted are the array values.
		$values = array_values($fields);
		$fields = array_keys($fields);
		$table = $this->tick($table);

		// Data and Columns are respectively quoted and ticked.
		$values = array_map(array($this, "quote"), $values);
		$fields = array_map(array($this, "tick"), $fields);

		$fields = '('.implode(', ', $fields).')';
		$values = 'VALUES ('.implode(', ', $values).')';

		$this->query['insert'] = 'INSERT INTO '.$table.' '.$fields.' '.$values;
	}

	/**
	 * Makes the UPDATE part of the query. It needs to be followed by a
	 * where() or another "where" helper function to update specific row(s).
	 * 
	 * @param string|array $fields
	 * @param string $table
	 * 
	 * @return void
	 */
	protected function make_update ($fields, $table)
	{
		$table = $this->tick($table);
		$update = array();

		foreach ($fields as $field => $value)
		{
			$update[] = $this->tick($field).'='.$this->quote($value);
		}

		$this->query['update'] = 'UPDATE '.$table.' SET '.implode(', ', $update);
	}

	/**
	 * Makes the DELETE part of the query. It needs to be followed by a
	 * where() or another "where" helper function to delete specific row(s).
	 * 
	 * @param string $table
	 * 
	 * @return void
	 */
	protected function make_delete ($table)
	{
		$this->query['delete'] = 'DELETE FROM '.$this->tick($table);
	}

	/**
	 * Adds a COUNT($field) to the SELECT clause.
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function count ($field = '*')
	{
		return $this->make_aggregate($field, 'count');
	}

	/**
	 * Adds a SUM($field) to the SELECT clause.
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function sum ($field)
	{
		return $this->make_aggregate($field, 'sum');
	}

	/**
	 * Adds an AVG($field) to the SELECT clause.
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function avg ($field)
	{
		return $this->make_aggregate($field, 'avg');
	}

	/**
	 * Adds a MAX($field) to the SELECT clause.
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function max ($field)
	{
		return $this->make_aggregate($field, 'max');
	}

	/**
	 * Adds a MIN($field) to the SELECT clause.
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function min ($field)
	{
		return $this->make_aggregate($field, 'min');
	}

	/**
	 * Constructs the aggregate function and adds it
	 * to the SELECT clause.
	 * 
	 * @param string $field
	 * @param string $type Type of aggregate function
	 */
	protected function make_aggregate ($field, $type)
	{
		list($field, $table) = $this->fix_dot($field);
		list($field, $as) = $this->fix_as($field);

		if ($field != '*')
		{
			$field = $this->tick($field);
		}

		$this->query['select'] .= ', '.strtoupper($type).'('.$table.$field.')'.$as;

		return $this;
	}
	
	/**
	 * Makes the FROM part of the query.
	 * 
	 * @param string|array $table
	 * 
	 * @return object
	 */
	public function from ($table)
	{
		$tables = $table;

		// Table(s) can be passed as a string or an array.
		// If it's a string, it's exploded to an array.
		if (!is_array($table))
		{
			$tables = explode(',', $table);
		}

		$table_pieces = array();
		foreach ($tables as $table)
		{
			$table = trim($table);
			list($table, $as) = $this->fix_as($table);

			$table_pieces[] = $this->tick($table).$as;
		}

		$table = implode(', ', $table_pieces);

		$this->query['from'] = $table;

		return $this;
	}
	
	/**
	 * Makes the WHERE part of the query. Serves as ending point to
	 * the "where" helpers too. Due to the complex nature of SQL,
	 * comparisons should be spaced (Ex: id = 10, not id=10) for the
	 * ticking and escaping to work, and it supports a limited amount
	 * of built-in SQL comparison keywords. However, it works quite well
	 * for automating query escaping and it supports enough keywords
	 * for any practical application.
	 * 
	 * @param string $where The WHERE clause
	 * 
	 * @return object
	 */
	public function where ($where)
	{
		// Parses IS, IS NOT, IS NULL and IS NOT NULL.
		if (stripos($where, ' is not null') !== false or stripos($where, ' is null') !== false or
			stripos($where, ' is not') !== false or stripos($where, ' is') !== false)
		{
			$field = substr($where, 0, strpos($where, ' '));
			$check = substr($where, strpos($where, ' '));

			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).strtoupper($check);
		}
		// Parses LIKE $val and NOT LIKE $val.
		elseif (stripos($where, ' not like ') !== false or stripos($where, ' like ') !== false)
		{
			$field = substr($where, 0, strpos($where, ' '));
			$like = substr($where, strpos($where, ' '));
			$like = substr($like, 0, strrpos($like, ' '));
			$value = substr($where, strrpos($where, ' ') + 1);

			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).' '.strtoupper($like).' '.$this->quote(trim($value, '\'" '));
		}
		// Parses IN (val1, val2, ...) and NOT IN (val1, val2, ...).
		elseif (stripos($where, ' not in(') !== false or stripos($where, ' not in (') !== false or
				stripos($where, ' in (') !== false or stripos($where, ' in(') !== false)
		{
			$field = substr($where, 0, strpos($where, ' '));
			$in = substr($where, strpos($where, ' '));
			$values = trim(substr($in, strpos($in, '(') + 1, strrpos($in, ')')), ')');
			$in = strtoupper(str_replace($values, '###', $in));

			$values = explode(',', $values);

			foreach ($values as &$val)
			{
				$val = trim($val, '\'" ');
				$val = $this->quote($val);
			}

			$values = implode(', ', $values);

			list($field, $table) = $this->fix_dot($field);

			$in = str_replace('###', $values, $in);

			$where = $table.$this->tick($field).' '.$in;
		}
		// Parses BETWEEN val1 AND val2
		elseif (stripos($where, 'between') !== false)
		{
			$field = substr($where, 0, strpos($where, ' '));
			$values = substr($where, stripos($where, 'between') + strlen('between') + 1);
			list($first, $operand, $second) = explode(' ', $values);

			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).' BETWEEN '.$this->quote(trim($first, '\'" ')).' '.strtoupper($operand).' '.$this->quote(trim($second, '\'" '));
		}
		// Otherwise, treat it as a simple comparison (a = 10, a > 10, etc).
		else
		{
			list($field, $operand, $value) = explode(' ', $where);
			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).$operand.$this->quote(trim($value, '\'" '));
		}

		$this->query['where'][] = $where;

		return $this;
	}

	/**
	 * Magic method __call(). Allows to construct where clauses via
	 * method calls. Ex: where_id_or_id(10, 15).
	 * 
	 * @return object
	 */
	public function __call ($method, $arguments)
	{
		// Only method calls that start with a "where_"
		// are considered.
		if (strpos($method, 'where_') !== false)
		{
			// The "where_" part is removed and fields are
			// retrieved by exploding the string by "_".
			$fields = str_replace('where_', '', $method);
			$fields = explode('_', $fields);

			$i = 0;
			foreach ($fields as $field)
			{
				// Adds a where clause if the field isn't an OR or AND.
				if ($field != 'or' and $field != 'and')
				{
					$value = $arguments[$i];
					$this->where("$field = $value");

					$i++;
				}
				else
				{	
					// Adds an OR to the where clause if an "or" is present.
					if ($field == 'or')
					{
						$this->_or();
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Makes boolean where groups using closures. Basically,
	 * it allows unlimited nesting of where clauses.
	 * 
	 * @param closure $function
	 * 
	 * @return object
	 */
	public function where_group ($function)
	{
		$this->query['where'][] = '(';

		// $this is passed as an argument so the closure can
		// access the Query object.
		call_user_func($function, $this);

		$this->query['where'][] = ')';

		return $this;
	}

	/**
	 * Adds an OR modifier to the WHERE clause. The previous
	 * clause with the next will be evaluated as a boolean OR.
	 * 
	 * @return object
	 */
	public function _or ()
	{
		$this->query['where'][] = 'OR';

		return $this;
	}

	/**
	 * Adds an AND modifier to the WHERE clause. It practically
	 * does nothing, as AND is the default modifier, but it's here
	 * just for readibility. It can be ommited.
	 * 
	 * @return object
	 */
	public function _and ()
	{
		return $this;
	}
	
	/**
	 * Makes: $field LIKE '$like'
	 * 
	 * @param string $field
	 * @param string $like
	 * 
	 * @return object
	 */
	public function like ($field, $like)
	{
		$this->where("$field LIKE '$like'");

		return $this;
	}

	/**
	 * Makes: $field NOT LIKE '$like'
	 * 
	 * @param string $field
	 * @param string $like
	 * 
	 * @return object
	 */
	public function not_like ($field, $like)
	{
		return $this->where("$field NOT LIKE '$like'");
	}

	/**
	 * Makes: $field LIKE '$value%'
	 * 
	 * @param string $field
	 * @param string $value
	 * 
	 * @return object
	 */
	public function starts ($field, $value)
	{
		return $this->where("$field LIKE $value%");
	}

	/**
	 * Makes: $field LIKE '%value'
	 * 
	 * @param string $field
	 * @param string $value
	 * 
	 * @return object
	 */
	public function ends ($field, $value)
	{
		return $this->where("$field LIKE %$value");
	}

	/**
	 * Makes: $field LIKE '%$value%'
	 * 
	 * @param string $field
	 * @param string $value
	 * 
	 * @return object
	 */
	public function has ($field, $value)
	{
		return $this->where("$field LIKE %$value%");
	}

	/**
	 * Makes: id = $id
	 * 
	 * @param int $id
	 * 
	 * @return object
	 */
	public function id ($id)
	{
		return $this->where("id = $id");
	}

	/**
	 * Makes: $field = ''
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function is_empty ($field)
	{
		return $this->where("$field = ''");
	}

	/**
	 * Makes: $field != ''
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function not_empty ($field)
	{
		return $this->where("$field != ''");
	}

	/**
	 * Makes: $field IS NULL
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function is_null ($field)
	{
		return $this->where("$field IS NULL");
	}

	/**
	 * Makes: $field IS NOT NULL
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function not_null ($field)
	{
		return $this->where("$field IS NOT NULL");
	}

	/**
	 * Makes: $field IN (val1, val2, ...)
	 * 
	 * @param string $field
	 * @param array $in
	 * 
	 * @return object
	 */
	public function in ($field, $in)
	{
		return $this->where("$field IN (".implode(', ', $in).")");
	}

	/**
	 * Makes: $field NOT IN (val1, val2, ...)
	 * 
	 * @param string $field
	 * @param array $in
	 * 
	 * @return object
	 */
	public function not_in ($field, $in)
	{
		return $this->where("$field NOT IN (".implode(', ', $in).")");
	}

	/**
	 * Makes: $field BETWEEN val1 AND val2
	 * 
	 * @param string $field
	 * @param int $from
	 * @param int $to
	 * 
	 * @return object
	 */
	public function between ($field, $from, $to)
	{
		return $this->where("$field BETWEEN $from AND $to");
	}

	/**
	 * Adds DISTINCT to the SELECT clause.
	 * 
	 * @return object
	 */
	public function distinct ()
	{
		$this->query['distinct'] = 'distinct';

		return $this;
	}

	/**
	 * Adds a MATCH AGAINST() clause for FULLTEXT searching.
	 * It's aware of any other selected fields or where clauses.
	 * 
	 * The produced query will be:
	 * SELECT MATCH (fields) AGAINST ('*search*' IN BOOLEAN MODE) AS score
	 * WHERE MATCH (fields) AGAINST ('*search*' IN BOOLEAN MODE)
	 * 
	 * @param string|array $match
	 * @param string $against
	 * 
	 * @return object
	 */
	public function match ($match, $against)
	{
		if (!is_array($match))
		{
			$match = explode(',', $match);
		}

		foreach ($match as &$m)
		{
			list($m, $table) = $this->fix_dot($m);
			$m = $table.$this->tick(trim($m));
		}

		$match = 'MATCH ('.implode(', ', $match).')';
		$against = 'AGAINST ('.$this->quote('*'.$against.'*').' IN BOOLEAN MODE)';

		$this->query['select'] .= ', '.$match.' '.$against.' AS '.$this->tick('score');
		$this->query['where'][] = $match.' '.$against;

		return $this;
	}
	
	/**
	 * Makes a GROUP BY clause.
	 * 
	 * @param string|array $group
	 * 
	 * @return object
	 */
	public function group ($group)
	{
		$groups = $group;

		if (!is_array($group))
		{
			$groups = explode(',', $group);
		}

		$group_pieces = array();
		foreach ($groups as $group)
		{
			$group = trim($group);
			list($group, $table) = $this->fix_dot($group);

			$group_pieces[] = $table.$this->tick($group);
		}

		$group = implode(', ', $group_pieces);

		$this->query['group'] = $group;

		return $this;
	}
	
	/**
	 * Makes an ORDER BY $field ASC clause.
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function asc ($field)
	{
		return $this->order($field, 'asc');
	}
	
	/**
	 * Makes an ORDER BY $field DESC clause.
	 * 
	 * @param string $field
	 * 
	 * @return object
	 */
	public function desc ($field)
	{
		return $this->order($field, 'desc');
	}

	/**
	 * Makes an ORDER BY clause.
	 * 
	 * @param string $field
	 * @param string $type Order type: ASC or DESC
	 * 
	 * @return object
	 */
	public function order ($field, $type)
	{
		$fields = $field;

		if (!is_array($field))
		{
			$fields = explode(',', $field);
		}

		$type = strtolower($type);

		if ($type != 'asc' and $type != 'desc')
		{
			$type = 'asc';
		}

		$field_pieces = array();
		foreach ($fields as $field)
		{
			$field = trim($field);

			list($field, $table) = $this->fix_dot($field);

			$field_pieces[] = $table.$this->tick($field).$as;
		}

		$field = implode(', ', $field_pieces);

		$this->query['order'] = $field.' '.strtoupper($type);

		return $this;
	}
	
	/**
	 * Makes a LIMIT $start, $offset clause.
	 * 
	 * @param int $start
	 * @param int $offset
	 * 
	 * @return object
	 */
	public function limit ($start, $offset = '')
	{
		$limit = $start;
		
		if ($offset !== '')
		{
			$limit .= ", $offset";
		}

		$this->query['limit'] = $limit;

		return $this;
	}
	
	/**
	 * Joins a table.
	 * 
	 * @param string $table
	 * 
	 * @return object
	 */
	public function join ($table)
	{
		return $this->make_join($table, 'join');
	}
	
	/**
	 * Left joins a table.
	 * 
	 * @param string $table
	 * 
	 * @return object
	 */
	public function left_join ($table)
	{
		return $this->make_join($table, 'left_join');
	}
	
	/**
	 * Right joins a table.
	 * 
	 * @param string $table
	 * 
	 * @return object
	 */
	public function right_join ($table)
	{
		return $this->make_join($table, 'right_join');
	}

	/**
	 * Makes the join.
	 * 
	 * @param string $table
	 * @param string $type Join type: join, left_join, right_join, outer_join
	 * 
	 * @return object
	 */
	protected function make_join ($table, $type)
	{
		list($table, $as) = $this->fix_as($table);

		$this->query[$type][] = $this->tick($table).$as;

		return $this;
	}
	
	/**
	 * Adds an ON clause for joined tables.
	 * 
	 * @param string $table
	 * 
	 * @return object
	 */
	public function on ($clause)
	{
		list($first, $second) = explode('=', $clause);

		list($first, $table1) = $this->fix_dot($first);
		list($second, $table2) = $this->fix_dot($second);
		
		$this->query['on'][] = $table1.$this->tick($first).'='.$table2.$this->tick($second);

		return $this;
	}

	/**
	 * Adds a USING clause for joined tables.
	 * 
	 * @param string $table
	 * 
	 * @return object
	 */
	public function using ($clause)
	{
		list($clause, $table) = $this->fix_dot($clause);
		
		$this->query['using'][] = $table.$this->tick($clause);

		return $this;
	}
	
	/**
	 * Magic method __toString. It builds the query from the collected
	 * pieces and return the SQL code when the class is treated as a string.
	 * 
	 * @return string
	 */
	public function __toString ()
	{
		$query = $this->query;
		$sql = '';
		
		// The query keywords are iterated and checks if any value is passed.
		// The key is the keywords, while the value is the corresponding
		// SQL code.
		foreach ($query as $key=>$val)
		{
			switch ($key)
			{
				case 'insert':
				case 'update':
				case 'delete':
					if ($val != '')
					{
						$sql = $val;
						break;
					}
				case 'select':
					if ($val != '')
					{
						$sql = 'SELECT ';

						// If distinct was added, it's prepended
						// to the SELECT keyword.
						if ($query['distinct'] != '')
						{
							$sql .= 'DISTINCT ';
						}
						$sql .= $val;
						break;
					}
				case 'from':
					if ($val != '')
					{
						$sql .= " FROM $val";
						break;
					}
				case 'join':
				case 'left_join':
				case 'right_join':
					if (count($val))
					{
						$type = strtoupper(str_replace('_', ' ', $key));
						
						$i = 0;

						// Joins are added as sub-arrays to allow multiple
						// table joins.
						foreach ($val as $join)
						{
							// ON and USING are added as sub-arrays too,
							// so the corresponding key is called.
							if (count($query['on']))
							{
								$sql .= " $type $join ON {$query['on'][$i]}";
							}
							elseif (count($query['using']))
							{
								$sql .= " $type $join USING({$query['using'][$i]})";
							}
							else {
								$sql .= " $type $join";
							}
							$i++;
						}
					}
					break;
				case 'where':
					if (is_array($val))
					{
						$i = 0;

						// Where clauses are added as array to allow multiple
						// conditions.
						foreach ($val as $where)
						{
							$operand = '';

							// An "OR" is just a modifier, so it's not considered.
							if ($where != 'OR')
							{
								// If a previous condition exists, an operand (AND or OR)
								// needs to be specified.
								if (isset($val[$i-1]))
								{
									// The previous condition should not be a '(' (a group opening)
									// and the actual condition should not be a ')' (a group closing).
									if ($val[$i-1] != '(' and $where != ')')
									{
										// The default operand is AND.
										// If the previous condition is "OR", the operand
										// will become OR.
										$operand = ' AND ';
										if ($val[$i-1] == 'OR')
										{
											$operand = ' OR ';
										}
									}
								}

								$the_where .= $operand.$where;
							}

							$i++;
						}

						$sql .= " WHERE $the_where";
					}
					break;
				case 'group':
					if ($val != '')
					{
						$sql .= " GROUP BY $val";
					}
					break;
				case 'order':
					if ($val != '')
					{
						$sql .= " ORDER BY $val";
					}
					break;
				case 'limit':
					if ($val != '')
					{
						$sql .= " LIMIT $val";
					}
					break;
			}			
		}
		
		return $sql;
	}

	/**
	 * Executes the query using the DB class.
	 * 
	 * @param $params Bound parameters
	 * 
	 * @return int|array
	 */
	public function query ($params)
	{
		return DB::query($this, $params);
	}

	/**
	 * Executes the query using the DB class
	 * and returns only the first row.
	 * 
	 * @param $params Bound parameters
	 * 
	 * @return int|array
	 */
	public function first ($params)
	{
		return DB::first($this, $params);
	}

	/**
	 * Quotes and escapes a string using the
	 * DB class.
	 * 
	 * @param $value
	 * 
	 * @return string
	 */
	protected function quote ($value)
	{
		return DB::quote($value);
	}

	/**
	 * Adds ticks to a string.
	 * 
	 * @param $value
	 * 
	 * @return string
	 */
	protected function tick ($value)
	{
		return '`'.$value.'`';
	}

	/**
	 * Parses the "AS" keyword in queries. It supports
	 * "field f" and "field AS f". 
	 * 
	 * @param $params Bound parameters
	 * 
	 * @return int|array
	 */
	protected function fix_as ($field)
	{
		$as = '';

		if (strpos($field, 'AS') !== false)
		{
			list($field, $as) = explode(' AS ', $field);
		}
		elseif (strpos($field, 'as') !== false)
		{
			list($field, $as) = explode(' as ', $field);
		}
		elseif (strpos($field, ' ') !== false)
		{
			list($field, $as) = explode(' ', $field);
		}

		if ($as !== '')
		{
			$as = ' AS '.$this->tick($as);
		}

		return array($field, $as);
	}

	/**
	 * Parses dots, the table selector in queries.
	 * 
	 * @param $params Bound parameters
	 * 
	 * @return int|array
	 */
	protected function fix_dot ($field)
	{
		$table = '';

		if (strpos($field, '.') !== false)
		{
			list($table, $field) = explode('.', $field);
		}

		if ($table !== '')
		{
			$table = $this->tick($table).'.';
		}

		return array($field, $table);
	}

}