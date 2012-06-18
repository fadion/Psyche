<?php
namespace FW\Core;

class DB {

	private static $results;
	private static $query = array(
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

	public static function connect ($host, $user, $password, $database) {
		mysql_connect($host, $user, $password) or trigger_error('Database connection failed.', FATAL);
		mysql_select_db($database) or trigger_error('No database found with the selected name.', FATAL);
	}
	
	
	public static function query ($sql) {
		if ($sql == '' or !is_string($sql)) {
			trigger_error('SQL code invalid.', FATAL);
		}
		
		static::$results = mysql_query($sql) or trigger_error(mysql_error(), FATAL);

		if (!static::$results) {
			trigger_error('Query failed.', FATAL);
		}

		return new static;
	}

	public static function results () {
		$values = array();
		
		if (!static::num_rows()) return false;
		
		while ($row = mysql_fetch_assoc(static::$results)) {
			$values[] = $row;
		}

		return $values;
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
			
			static::$query['select'] = $select;
		}
		
		return new static;
	}
	
	public static function from ($table) {
		if (!empty($table)) {
			static::$query['from'] = $table;
		}

		return new static;
	}
	
	public static function where ($where) {
		if (!empty($where)){	
			static::$query['where'] = $where;
		}
		
		return new static;
	}
	
	public static function like ($field, $like) {
		if (!empty($field) and !empty($like)) {
			static::$query['like'] = "$field LIKE '$like'";
		}
		
		return new static;
	}
	
	public static function group () {
		if (func_num_args()) {
			$group = func_get_args();
			$group = implode(', ', $group);
			
			static::$query['group'] = $group;
		}
		
		return new static;
	}
	
	public static function asc ($field) {
		if (!empty($field)) {
			static::$query['order'] = "$field ASC";
		}
		
		return new static;
	}
	
	public static function desc ($field) {
		if (!empty($field)) {
			static::$query['order'] = "$field DESC";
		}
		
		return new static;
	}
	
	public static function limit ($start, $limit = '') {
		if (!empty($start) and is_int($start)) {
			static::$query['limit'] = $start;
			
			if ($limit !== '' and is_int($limit)) {
				static::$query['limit'] .= ", $limit";
			}
		}
		
		return new static;
	}
	
	public static function join ($table) {
		if (!empty($table)) {
			static::$query['join'][] = $table;
		}
		return new static;
	}
	
	public static function left_join ($table) {
		if (!empty($table)) {
			static::$query['left_join'][] = $table;
		}

		return new static;
	}
	
	public static function right_join ($table) {
		if (!empty($table)) {
			static::$query['right_join'][] = $table;
		}

		return new static;
	}
	
	public static function outer_join ($table) {
		if (!empty($table)) {
			static::$query['outer_join'] = $table;
		}

		return new static;
	}
	
	public static function on ($clause) {
		if (!empty($clause)) {
			static::$query['on'][] = $clause;
		}

		return new static;
	}
	
	public static function run () {
		return static::query(static::build());
	}
	
	public static function val () {
		return static::build();
	}
	
	private static function build () {
		$query = static::$query;
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
	
	public static function row ($table, $clause) {
		if ($table != '' and $clause != '') {
			static::$query("SELECT * FROM $table WHERE $clause");
		}

		return new static;
	}
	
	public static function col ($table, $col, $clause = NULL) {
		if ($table == '' or $col == '') {
			if (is_null($clause)) {
				$clause = '';
			} else {
				$clause = "WHERE $clause";
			}
			
			static::$query("SELECT $col FROM $table $clause");
		}

		return new static;
	}
	
	public static function fields ($table) {
		if ($table != '') {
			static::$query("SHOW COLUMNS FROM $table");
		}

		return new static;
	}
	
	public static function last_id () {
		return mysql_insert_id();
	}
	
	public static function num_rows () {
		return (int) mysql_num_rows(static::$results);	
	}

	public static function clean ($input) {
		return mysql_real_escape_string($input);	
	}
	
	public static function update ($params, $table, $where) {
		if (!count($params)) {
			trigger_error('No parameter passed for the update query.', FATAL);
		}
		
		$update_params = '';
		
		foreach ($params as $field => $value) {
			$value = static::$clean($value);
			$update_params .= "$field='$value', ";
		}
		
		$update_params = trim($update_params, ', ');
		
		$sql = "UPDATE $table SET $update_params WHERE $where";
		
		return static::$query($sql);
	}
	
	public static function insert ($params, $table) {
		if (!count($params)) {
			trigger_error('No parameter passed for the insert query.', FATAL);
		}
			
		$cols = array();
		$vals = array();
		
		foreach ($params as $col => $val) {
			$cols[] = $col;
			$vals[] = static::$clean($val);
		}
		
		$cols = '(' . implode(', ', $cols) . ')';
		$vals = "VALUES ('" . implode("', '", $vals) . "')";
		
		$sql = "INSERT INTO $table $cols $vals";
		
		return static::$query($sql);
	}
	
	public static function delete ($table, $where) {
		$results = static::$query("DELETE FROM $table WHERE $where");
	}

}