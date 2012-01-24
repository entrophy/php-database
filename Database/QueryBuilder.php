<?php
class ENT_Database_QueryBuilder {
	private $type = "SELECT";
	protected $table;
	protected $tableAlias;
	protected $fields = '*';
	private $left_joins;
	private $values;
	private $conditions;
	private $params;
	private $havings;
	protected $groups;
	protected $orders;
	protected $amount;
	protected $offset = 0;

	protected $database;
	private $query;
	
	public function __construct($database) {
		$this->database = $database;
	}
	
	public function setType($type) {
		$this->type = strtoupper($type);
		return $this;
	}
	public function setTable($table, $alias = null) {
		$this->table = $table;
		$this->tableAlias = $alias;
		return $this;
	}
	
	public function addLeftJoin($table, $condition) {
		$this->left_joins[] = array($table, $condition);
	}
	
	public function resetFields() {
		$this->fields = '';
	}
	public function setFields($fields) {
		if (is_string($fields)) {
			$this->fields = array($fields);
		} else {
			$this->fields = $fields;
		}
		return $this;
	}
	
	public function addField($field) {
		if ($this->fields == '*') {
			$this->fields = array();
		}
		$this->fields[] = $field;
		return $this;
	}
	
	public function getFields() {
		return $this->fields;
	}
	
	public function setValues($values) {
		$this->values = $values;
		return $this;
	}
	
	public function newCondition($params, $weight = 0) {
		return new ENT_Database_QueryBuilder_Condition($params, $weight);
	}
	
	public function setCondition($condition, $weight = 0, $key = null) {
		$condition = is_object($condition) || is_array($condition) ? $condition : $this->newCondition($condition, $weight);

		$key ? $this->conditions[$key] = array($condition, $weight) : $this->conditions[] = array($condition, $weight);
		
		return $this;
	}
	public function removeCondition($key) {
		$this->conditions[$key] = null;
		unset($this->conditions[$key]);
	}
	
	public function addHaving($having, $glue = 'AND', $key = null) {
		if (is_array($having)) {
			foreach ($having as $field => $value) {
				$_having = array("$field = 'mysql_real_escape_string($value)'", $glue);

				$key ? $this->havings[$key] = $_having : $this->havings[] = $_having;
			}
		} else {
			$key ? $this->havings[$key] = array($having, $glue) : $this->havings[] = array($having, $glue);
		}
		
		return $this;
	}
	public function removeHaving($key) {
		$this->havings[$key] = null;
		unset($this->havings[$key]);
	}
	
	public function addGroupBy($group, $key = null) {
		$key ? $this->groups[$key] = array($group) : $this->groups[] = array($group);
		return $this;
	}
	public function removeGroupBy($key) {
		$this->groups[$key] = null;
		unset($this->groups[$key]);
	}

	public function addOrder($order, $dir = 'asc', $key = null) {
		$key ? $this->orders[$key] = array($order, $dir) : $this->orders[] = array($order, $dir);
	}
	public function removeOrder($key) {
		$this->orders[$key] = null;
		unset($this->orders[$key]);
	}

	public function bindParam($data, $value = null) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				
				$this->params[$key[0] === ':' ? $key : ':'.$key] = $value;
			}
		} else if ($value !== null) {
			$this->bindParam(array($data => $value));
		}
	}
	
	public function setAmount($amount) {
		$this->amount = $amount;
		return $this;
	}
	public function setLimit($limit) {
		return $this->setAmount($limit);
	}
	public function setOffset($offset) {
		$this->offset = $offset;
		return $this;
	}
	
	private function escapeName($name) {
		return $this->database->field($name);
	}
	private function _escapeName(&$name) {
		$name = $this->escapeName($name);
	}
	
	private function sortConditions($a, $b) {
		$a = $a[1];
		$b = $b[1];

		if ($a == $b) {
			return 0;
		}

		return ($a < $b) ? -1 : 1;
	}
	
	public function buildQuery() {
		$query = $this->type." ";
			
		switch ($this->type) {
			case "SELECT":
				if (!is_array($this->fields)) {
					$this->fields = array($this->fields);
				}

				if ($this->amount) {
					$query .= "SQL_CALC_FOUND_ROWS ";
				}
				
				array_walk($this->fields, array($this, '_escapeName'));
				$query .= implode(", ", $this->fields);
				
				$query .= " FROM ";
				break;
			case "CREATE";
			case "INSERT":
				$query .= "INTO ";
				break;
			case "DELETE":
				$query .= "FROM ";
				break;
			}
		
		if (is_array($this->table)) {
			array_walk($this->table, array($this, '_escapeName'));
			$query .= implode(", ", $this->table);
		} else {
			$query .= $this->escapeName($this->table);
			if ($this->tableAlias) {
				$query .= " ".$this->tableAlias;
			}
		}
		
		if (is_array($this->left_joins) && count($this->left_joins) && $this->type == "SELECT") {
			foreach ($this->left_joins as $left_join) {
				$query .= " LEFT JOIN `".$left_join[0]."` ON ".$left_join[1];
			}
		}
		
		if ($this->values) {
			$count = count($this->values);
			$x = 1;
			switch ($this->type) {
				case "UPDATE":
					$query .= " SET ";
					foreach ($this->values as $key => $value) {
						$query .= "`".$key."` = ";
						
						if (!$value) {
							$query .= "''";
						} else if (is_numeric($value)) {
							$query .= $value;
						} else {
							$query .= "'".mysql_real_escape_string(stripslashes($value))."'";
						}

						if ($x != $count) {
							$query .= ", ";
						}
						
						$x++;
					}
					break;
				case "CREATE";
				case "INSERT":
					$keys = array_keys($this->values);
					array_walk($keys, array($this, '_escapeName'));
				
					$query .= ' ('.implode(', ', $keys).') VALUES (';
					
					foreach ($this->values as $key => $value) {
						if (!$value) {
							$query .= "''";
						} elseif (is_numeric($value)) {
							$query .= $value;
						} else {
							$query .= "'".mysql_real_escape_string(stripslashes($value))."'";
						}
						
						if ($x != $count) {
							$query .= ", ";
						}
						
						$x++;
					}
					
					$query .= ")";
					break;
			}
		}
		
		$count = count($this->conditions);
		$x = 1;
		if ($count && $this->type != 'INSERT' && $this->type != 'CREATE') {
			usort($this->conditions, array($this, 'sortConditions'));
		
			$query .= " WHERE";
			foreach ($this->conditions as $condition) {
				$_conditions = is_array($condition[0]) ? $condition[0] : array($condition[0]);

				foreach ($_conditions as $_condition) {
					$query .= $x != 1 ? ' AND ' : ' ';

					$query .= is_object($_condition) ? "(".$_condition->getSql().")" : "(".$_condition.")";
					
					$x++;
				}
			}
		}

		if (is_array($this->groups) && ($count = count($this->groups))) {
			$x = 1;
			$query .= ' GROUP BY ';

			foreach ($this->groups as $group) {
				$query .= $x != $count ? $group[0].', ' : $group[0];

				$x++;
			}
		}
		
		if (is_array($this->havings) && ($count = count($this->havings)) && $this->type != 'INSERT' && $this->type != 'CREATE') {
			$query .= " HAVING ";
			$x = 1;
			foreach ($this->havings as $having) {
				if ($x != 1) {
					$query .= $having[1]." ";
				}
				$query .= "(".$having[0].")";
				
				$x++;
			}
		}

		if (is_array($this->orders) && ($count = count($this->orders))) {
			$x = 1;
			$query .= ' ORDER BY ';

			foreach ($this->orders as $order) {
				$query .= $x != $count ? $order[0].' '.strtoupper($order[1]).', ' : $order[0].' '.strtoupper($order[1]);

				$x++;
			}
		}
		
		if ($this->amount) {
			$query .= ' LIMIT '.$this->amount;
		}

		if ($this->offset) {
			$query .= ' OFFSET '.$this->offset;
		}
		
		$query;

		return $query;
	}
	
	public function getQuery() {
		return $this->query;
	}

	private function clear() {
		$this->type = "SELECT";
		$this->table = null;
		$this->fields = '*';
		$this->tableAlias = null;
		$this->left_joins = array();
		$this->conditions = array();
		$this->params = array();
		$this->havings = array();
		$this->amount = null;
		$this->offset = 0;
		$this->orders = array();
		$this->values = array();
		$this->groups = null;
	}
	
	public function execute($type = null, $query = null, $params = null) {
		$this->query = $query ? : $this->buildQuery();
		$this->params = $params ? : $this->params;
		$this->type = $type ? : $this->type;

		$this->database->prepare($this->query);
		$this->database->bind($this->params);
		
		$result = $this->database->execute($this->type, true);

		$this->clear();

		return $result;
	}
}
?>
