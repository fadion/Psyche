<?php
namespace FW\Core;

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
		'where'		 	=> '',
		'like'		  	=> '',
		'group'			=> '',
		'order'		  	=> '',
		'limit'		  	=> ''
	);

	public function __construct ($select)
	{
		$this->query['select'] = $select;

		return $this;
	}

	public static function select () {
		if (func_num_args()) {
			$select = '';
			$fields = func_get_args();
			
			if (is_array($fields[0])) {
				$fields = $fields[0];
			}
			
			foreach ($fields as $field) {
				$select .= " $field, ";
			}

			$select = trim($select, ' ,');
		}
		
		return new static($select);
	}
	
	public function from ($table) {
		if (!empty($table)) {
			$this->query['from'] = $table;
		}

		return $this;
	}
	
	public function where ($where) {
		if (!empty($where)){	
			$this->query['where'] = $where;
		}

		return $this;
	}
	
	public function like ($field, $like) {
		if (!empty($field) and !empty($like)) {
			$this->query['like'] = "$field LIKE '$like'";
		}

		return $this;
	}
	
	public function group () {
		if (func_num_args()) {
			$group = func_get_args();
			$group = implode(', ', $group);
			
			$this->query['group'] = $group;
		}

		return $this;
	}
	
	public function asc ($field) {
		if (!empty($field)) {
			$this->query['order'] = "$field ASC";
		}

		return $this;
	}
	
	public function desc ($field) {
		if (!empty($field)) {
			$this->query['order'] = "$field DESC";
		}

		return $this;
	}
	
	public function limit ($start, $limit = '') {
		if (!empty($start) and is_int($start)) {
			$this->query['limit'] = $start;
			
			if ($limit !== '' and is_int($limit)) {
				$this->query['limit'] .= ", $limit";
			}
		}

		return $this;
	}
	
	public function join ($table) {
		if (!empty($table)) {
			$this->query['join'][] = $table;
		}

		return $this;
	}
	
	public function left_join ($table) {
		if (!empty($table)) {
			$this->query['left_join'][] = $table;
		}

		return $this;
	}
	
	public function right_join ($table) {
		if (!empty($table)) {
			$this->query['right_join'][] = $table;
		}

		return $this;
	}
	
	public function outer_join ($table) {
		if (!empty($table)) {
			$this->query['outer_join'] = $table;
		}

		return $this;
	}
	
	public function on ($clause) {
		if (!empty($clause)) {
			$this->query['on'][] = $clause;
		}

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
							} else {
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

}