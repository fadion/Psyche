<?php
namespace FW\Core;
use FW\Core\DB;

// WIP
class Query
{

	protected $results;
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
		'outer_join'	=> '',
		'on'			=> array(),
		'using'			=> array(),
		'where'		 	=> '',
		'group'			=> '',
		'order'		  	=> '',
		'limit'		  	=> ''
	);

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

	// done
	public static function select ($fields = '*')
	{
		return new static($fields, 'select');
	}

	// done
	public static function insert ($table, $fields)
	{
		return new static($fields, 'insert', $table);
	}

	// done
	public static function update ($table, $fields)
	{
		return new static($fields, 'update', $table);
	}

	// done
	public static function delete ($fields)
	{
		return new static($fields, 'delete');
	}

	// done
	protected function make_select ($fields)
	{
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
			if ($field !== '*')
			{
				$field = $this->tick($field);
			}

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

	// done
	protected function make_insert ($fields, $parameters)
	{
		$values = array_values($fields);
		$fields = array_keys($fields);
		$table = $this->tick($parameters);

		$values = array_map(array($this, "quote"), $values);
		$fields = array_map(array($this, "tick"), $fields);

		$fields = '('.implode(', ', $fields).')';
		$values = 'VALUES ('.implode(', ', $values).')';

		$this->query['insert'] = 'INSERT INTO '.$table.' '.$fields.' '.$values;
	}

	// done
	protected function make_update ($fields, $parameters)
	{
		$table = $this->tick($parameters);
		$update = array();

		foreach ($fields as $field => $value)
		{
			$update[] = $this->tick($field).'='.$this->quote($value);
		}

		$this->query['update'] = 'UPDATE '.$table.' SET '.implode(', ', $update);
	}

	//done
	protected function make_delete ($fields)
	{
		$this->query['delete'] = 'DELETE FROM '.$this->tick($fields);
	}

	public function count ($field = '*')
	{
		return $this->make_aggregate($field, 'count');
	}

	public function sum ($field)
	{
		return $this->make_aggregate($field, 'sum');
	}

	public function avg ($field)
	{
		return $this->make_aggregate($field, 'avg');
	}

	public function max ($field)
	{
		return $this->make_aggregate($field, 'max');
	}

	public function min ($field)
	{
		return $this->make_aggregate($field, 'min');
	}

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
	
	// done
	public function from ($table)
	{
		$tables = $table;

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
	
	public function where ($where)
	{
		if (stripos($where, ' is not null') !== false or stripos($where, ' is null') !== false or
			stripos($where, ' is not') !== false or stripos($where, ' is') !== false)
		{
			$field = substr($where, 0, strpos($where, ' '));
			$check = substr($where, strpos($where, ' '));

			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).strtoupper($check);
		}
		elseif (stripos($where, ' not like ') !== false or stripos($where, ' like ') !== false)
		{
			$field = substr($where, 0, strpos($where, ' '));
			$like = substr($where, strpos($where, ' '));
			$like = substr($like, 0, strrpos($like, ' '));
			$value = substr($where, strrpos($where, ' ') + 1);

			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).' '.strtoupper($like).' '.$this->quote(trim($value, '\'" '));
		}
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
		elseif (stripos($where, 'between') !== false)
		{
			$field = substr($where, 0, strpos($where, ' '));
			$values = substr($where, stripos($where, 'between') + strlen('between') + 1);
			list($first, $operand, $second) = explode(' ', $values);

			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).' BETWEEN '.$this->quote(trim($first, '\'" ')).' '.strtoupper($operand).' '.$this->quote(trim($second, '\'" '));
		}
		else
		{
			list($field, $operand, $value) = explode(' ', $where);
			list($field, $table) = $this->fix_dot($field);

			$where = $table.$this->tick($field).$operand.$this->quote(trim($value, '\'" '));
		}

		$this->query['where'][] = $where;

		return $this;
	}

	public function __call ($method, $arguments)
	{
		if (strpos($method, 'where_') !== false)
		{
			$fields = str_replace('where_', '', $method);
			$fields = explode('_', $fields);

			$i = 0;
			foreach ($fields as $field)
			{
				if ($field != 'or' and $field != 'and')
				{
					$value = $arguments[$i];
					$this->where("$field = $value");

					$i++;
				}
				else
				{
					if ($field == 'or')
					{
						$this->_or();
					}
				}
			}
		}

		return $this;
	}

	public function where_group ($function)
	{
		$this->query['where'][] = '(';
		call_user_func($function, $this);
		$this->query['where'][] = ')';

		return $this;
	}

	public function _or ()
	{
		$this->query['where'][] = 'OR';

		return $this;
	}
	
	public function like ($field, $like)
	{
		$this->where("$field LIKE '$like'");

		return $this;
	}

	public function not_like ($field, $like)
	{
		return $this->where("$field NOT LIKE '$like'");
	}

	//done
	public function starts ($field, $value)
	{
		return $this->where("$field LIKE $value%");
	}

	//done
	public function ends ($field, $value)
	{
		return $this->where("$field LIKE %$value");
	}

	//done
	public function has ($field, $value)
	{
		return $this->where("$field LIKE %$value%");
	}

	// done
	public function id ($id)
	{
		return $this->where("id = $id");
	}

	// done
	public function is_empty ($field)
	{
		return $this->where("$field = ''");
	}

	// done
	public function not_empty ($field)
	{
		return $this->where("$field != ''");
	}

	// done
	public function is_null ($field)
	{
		return $this->where("$field IS NULL");
	}

	// done
	public function not_null ($field)
	{
		return $this->where("$field IS NOT NULL");
	}

	// done
	public function in ($field, $in)
	{
		return $this->where("$field IN (".implode(', ', $in).")");
	}

	// done
	public function not_in ($field, $in)
	{
		return $this->where("$field NOT IN (".implode(', ', $in).")");
	}

	public function between ($field, $from, $to)
	{
		return $this->where("$field BETWEEN $from AND $to");
	}

	// done
	public function distinct ()
	{
		$this->query['distinct'] = 'distinct';

		return $this;
	}

	public function match ($match, $against)
	{
		$match = explode(',', $match);

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
	
	// done
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
	
	// done
	public function asc ($field)
	{
		return $this->order($field, 'asc');
	}
	
	// done
	public function desc ($field)
	{
		return $this->order($field, 'desc');
	}

	// done
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
	
	// done
	public function limit ($start, $limit = '')
	{
		if (!empty($start) and is_int($start))
		{
			$this->query['limit'] = $start;
			
			if ($limit !== '' and is_int($limit))
			{
				$this->query['limit'] .= ", $limit";
			}
		}

		return $this;
	}
	
	// done
	public function join ($table)
	{
		return $this->make_join($table, 'join');
	}
	
	// done
	public function left_join ($table)
	{
		return $this->make_join($table, 'left_join');
	}
	
	// done
	public function right_join ($table)
	{
		return $this->make_join($table, 'right_join');
	}
	
	// done
	public function outer_join ($table)
	{
		return $this->make_join($table, 'outer_join');
	}

	// done
	protected function make_join ($table, $type)
	{
		list($table, $as) = $this->fix_as($table);

		$this->query[$type][] = $this->tick($table).$as;

		return $this;
	}
	
	// done
	public function on ($clause)
	{
		list($first, $second) = explode('=', $clause);

		list($first, $table1) = $this->fix_dot($first);
		list($second, $table2) = $this->fix_dot($second);
		
		$this->query['on'][] = $table1.$this->tick($first).'='.$table2.$this->tick($second);

		return $this;
	}

	// done
	public function using ($clause)
	{
		list($clause, $table) = $this->fix_dot($clause);
		
		$this->query['using'][] = $table.$this->tick($clause);

		return $this;
	}
	
	public function __toString ()
	{
		$query = $this->query;
		$sql = '';
		
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
						foreach ($val as $join)
						{
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
				case 'outer_join':
					if ($val != '' and count($query['on']))
					{
						$sql .= " LEFT JOIN $val ON {$query['on'][0]}";
						$sql .= " UNION";
						$sql .= " SELECT {$query['select']}";
						$sql .= " FROM {$query['from']}";
						$sql .= " RIGHT JOIN $val ON {$query['on'][0]}";
					}
					break;
				case 'where':
					if (is_array($val))
					{
						$i = 0;
						foreach ($val as $where)
						{
							$operand = '';

							if ($where != 'OR')
							{
								if (isset($val[$i-1]))
								{
									if ($val[$i-1] != '(' and $where != ')')
									{
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

	public function query ($params)
	{
		return DB::query($this, $params);
	}

	protected function quote ($value)
	{
		return DB::quote($value);
	}

	protected function tick ($value)
	{
		return '`'.$value.'`';
	}

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