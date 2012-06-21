<?php
namespace FW\Core;
use FW\Core\DB;

// WIP
class Query {

	protected $results;
	protected $query = array(
		'select'	  	=> '',
		'from'		  	=> '',
		'join'		  	=> array(),
		'left_join'	  	=> array(),
		'right_join'  	=> array(),
		'outer_join'	=> '',
		'on'			=> array(),
		'using'			=> array(),
		'where'		 	=> '',
		'like'		  	=> '',
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
		}
	}

	public static function select ($fields = '*')
	{
		return new static($fields, 'select');
	}

	// done
	public function make_select ($fields)
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
				$function = substr($field, 0, strpos($field, '(') + 1);
				$field = substr($field, strpos($field, '(') + 1);
				$field = str_replace(')', '', $field);
				$function_end = ')';
			}

			list($field, $as) = $this->fix_as($field);
			list($field, $table) = $this->fix_dot($field);
			if ($field !== '*')
			{
				$field = $this->tick($field);
			}

			$select_pieces[] = $function.$table.$field.$function_end.$as;
		}

		$select = implode(', ', $select_pieces);
		
		$this->query['select'] = $select;

		return $this;
	}
	
	// done
	public function from ($table) {
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
	
	public function where ($where) {
		$wheres = explode(' ', $where);
		$where_final = '';

		foreach ($wheres as $where)
		{
			$where = trim($where);
			$table = '';
			$bool = array('or', 'OR', 'and', 'AND');

			if (!in_array($where, $bool))
			{
				if (strpos($where, '>='))
				{
					$operand = '>=';
				}
				elseif (strpos($where, '<='))
				{
					$operand = '<=';
				}
				elseif (strpos($where, '='))
				{
					$operand = '=';
				}
				elseif (strpos($where, '>'))
				{
					$operand = '>';
				}
				elseif (strpos($where, '<'))
				{
					$operand = '<';
				}

				list($where, $value) = explode($operand, $where);

				if (strpos($where, '.'))
				{
					list($table, $where) = explode('.', $where);
				}

				if ($table !== '')
				{
					$table = $this->tick($table).'.';
				}

				if ($value != '?')
				{
					$value = $this->quote($value);
				}

				$where = $table.$this->tick($where).$operand.$value;

				if (strpos($where, '('))
				{
					$where = '('.str_replace('(', '', $where);
				}

				if (strpos($where, ')'))
				{
					$where = str_replace(')', '', $where).')';
				}
			}
			else
			{
				$where = " $where ";
			}

			$where_final .= $where;
		}

		$this->query['where'] = $where_final;

		return $this;
	}
	
	public function like ($field, $like) {
		if (!empty($field) and !empty($like)) {
			$this->query['like'] = "$field LIKE '$like'";
		}

		return $this;
	}

	// done
	public function id ($id)
	{
		list($id, $table) = $this->fix_dot($id);

		$this->query['where'] = $this->tick('id').'='.$this->quote($id);

		return $this;
	}
	
	// done
	public function group ($group) {
		if (!is_array($group))
		{
			$groups = explode(',', $group);
		}

		$group_pieces = array();
		foreach ($groups as $group)
		{
			$group = trim($group);
			list($group, $as) = $this->fix_as($group);
			list($group, $table) = $this->fix_dot($group);

			$group_pieces[] = $table.$this->tick($group).$as;
		}

		$group = implode(', ', $group_pieces);

		$this->query['group'] = $group;

		return $this;
	}
	
	// done
	public function asc ($field) {
		return $this->order($field, 'asc');
	}
	
	// done
	public function desc ($field) {
		return $this->order($field, 'desc');
	}

	// done
	protected function order ($field, $type)
	{
		if (!is_array($field))
		{
			$fields = explode(',', $field);
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
	public function limit ($start, $limit = '') {
		if (!empty($start) and is_int($start)) {
			$this->query['limit'] = $start;
			
			if ($limit !== '' and is_int($limit)) {
				$this->query['limit'] .= ", $limit";
			}
		}

		return $this;
	}
	
	// done
	public function join ($table) {
		return $this->make_join($table, 'join');
	}
	
	// done
	public function left_join ($table) {
		return $this->make_join($table, 'left_join');
	}
	
	// done
	public function right_join ($table) {
		return $this->make_join($table, 'right_join');
	}
	
	// done
	public function outer_join ($table) {
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
	public function on ($clause) {
		list($first, $second) = explode('=', $clause);

		list($first, $table1) = $this->fix_dot($first);
		list($second, $table2) = $this->fix_dot($second);
		
		$this->query['on'][] = $table1.$this->tick($first).'='.$table2.$this->tick($second);

		return $this;
	}

	// done
	public function using ($clause) {
		list($clause, $table) = $this->fix_dot($clause);
		
		$this->query['using'][] = $table.$this->tick($clause);

		return $this;
	}
	
	public function __toString () {
		$query = $this->query;
		$sql = '';
		
		if ($query['select'] == '') {
			trigger_error("SELECT clause shouldn't be empty.", FATAL);
		}
		
		if ($query['from'] == '') {
			trigger_error("FROM clause shouldn't be empty.", FATAL);
		}
		
		foreach ($query as $key=>$val) {
			switch ($key) {
				case 'select':
					$sql .= "SELECT $val";
					break;
				case 'from':
					$sql .= " FROM $val";
					break;
				case 'join':
				case 'left_join':
				case 'right_join':
					if (count($val)) {
						$type = strtoupper(str_replace('_', ' ', $key));
						
						$i = 0;
						foreach ($val as $join) {
							if (count($query['on'])) {
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
					if ($val != '' and count($query['on'])) {
						$sql .= " LEFT JOIN $val ON {$query['on'][0]}";
						$sql .= " UNION";
						$sql .= " SELECT {$query['select']}";
						$sql .= " FROM {$query['from']}";
						$sql .= " RIGHT JOIN $val ON {$query['on'][0]}";
					}
					break;
				case 'where':
				case 'like':
					if ($val != '') {
						$sql .= " WHERE $val";
					}
					break;
				case 'group':
					if ($val != '') {
						$sql .= " GROUP BY $val";
					}
					break;
				case 'order':
					if ($val != '') {
						$sql .= " ORDER BY $val";
					}
					break;
				case 'limit':
					if ($val != '') {
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

		if (strpos($field, 'AS'))
		{
			list($field, $as) = explode(' AS ', $field);
		}
		elseif (strpos($field, 'as'))
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