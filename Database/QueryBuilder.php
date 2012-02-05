<?php
class Entrophy_Database_QueryBuilder {
	private $type = "SELECT";
	private $table;
	private $fields = '*';
	private $values;
	private $conditions;
	private $params;
	private $orders;
	private $amount;
	private $offset = 0;

	private $database;
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
	
	public function resetFields() {
		$this->fields = '';
		return $this;
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
		return new Entrophy_Database_QueryBuilder_Condition($params, $weight);
	}
	
	public function setCondition($condition, $weight = 0, $key = null) {
		$condition = is_object($condition) || is_array($condition) ? $condition : $this->newCondition($condition, $weight);

		$key ? $this->conditions[$key] = array($condition, $weight) : $this->conditions[] = array($condition, $weight);
		
		return $this;
	}
	public function removeCondition($key) {
		$this->conditions[$key] = null;
		unset($this->conditions[$key]);
		return $this;
	}

	public function addOrder($order, $dir = 'asc', $key = null) {
		$key ? $this->orders[$key] = array($order, $dir) : $this->orders[] = array($order, $dir);
		return $this;
	}
	public function removeOrder($key) {
		$this->orders[$key] = null;
		unset($this->orders[$key]);
		return $this;
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
	private function wrapValue($value) {
		return $this->database->wrapValue($value);
	}
	private function _wrapValue(&$value) {
		$value = $this->escapeName($value);
	}
	private function wrapValues($values) {
		return implode(', ', array_map(array($this, 'wrapValue'), $values));
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
		$query_parts = array($this->type);
			
		switch ($this->type) {
			case "SELECT":
				if (!is_array($fields = $this->fields)) {
					$fields = array($fields);
				}

				if ($this->amount) {
					$part = 'SQL_CALC_FOUND_ROWS ';
				}
				$part .= implode(', ', array_map(array($this, 'escapeName'), $fields));
				
				$query_parts[] = $part;
				unset($part);
				break;
			case "CREATE";
			case "INSERT":
				$query_parts[] =  'INTO';
				break;
			case "DELETE":
				$query_parts[] =  'FROM';
				break;
			}
		
		if (!is_array($tables = $this->table)) {
			$tables = array($tables);
		}
		$query_parts[] = implode(', ', array_map(array($this, 'escapeName'), $tables));
			
		
		if ($values = $this->values) {
			$count = count($values);
			$x = 1;
			switch ($this->type) {
				case 'UPDATE':
					$query_parts[] = 'SET';
					foreach ($this->values as $key => $value) {
						$part = "`".$key."` = ";
						
						if (!$value) {
							$part .= "''";
						} else if (is_numeric($value)) {
							$part .= $value;
						} else {
							$part .= "'".$value."'";
						}

						if ($x != $count) {
							$part .= ", ";
						}
						
						$query_parts[] = $part;
						unset($part);
						$x++;
					}
					break;
				case 'CREATE';
				case 'INSERT':
					if (!is_array($values[0])) {
						$values = array($values);
					}
					
					$keys = array_map(array($this, 'escapeName'), array_keys($values[0])); // escape field names
					$values = array_map(array($this, 'wrapValues'), $values); // wrap string values in '' and leave numeric be			
					$part = '('.implode(', ', $keys).') VALUES ('.implode('), (', $values).')'; // build value statement
					
					$query_parts[] = $part;		
					unset($part);
					break;
			}
			
			unset($values);
		}
		
		$count = count($this->conditions);
		$x = 1;
		if ($count && $this->type != 'INSERT' && $this->type != 'CREATE') {
			usort($this->conditions, array($this, 'sortConditions'));
		
			$query_parts[] = 'WHERE';
			foreach ($this->conditions as $condition) {
				$_conditions = is_array($condition[0]) ? $condition[0] : array($condition[0]);

				foreach ($_conditions as $_condition) {
					$part = $x != 1 ? ' AND ' : ' ';

					$part .= is_object($_condition) ? "(".$_condition->getSql().")" : "(".$_condition.")";
					$query_parts[] = $part;
					unset($part);
					$x++;
				}
			}
		}

		if (is_array($this->orders) && ($count = count($this->orders))) {
			$x = 1;
			$query_parts[] = 'ORDER BY';

			foreach ($this->orders as $order) {
				$query_parts[] $x != $count ? $order[0].' '.strtoupper($order[1]).', ' : $order[0].' '.strtoupper($order[1]);

				$x++;
			}
		}
		
		if ($this->amount) {
			$query_parts[] = 'LIMIT '.$this->amount;
		}

		if ($this->offset) {
			$query_parts[] = 'OFFSET '.$this->offset;
		}
		
		$query = implode(' ', $query_parts);
		
		print_r($query_parts);
		unset($query_parts);
		echo $query;
		return $query;
	}
	
	public function getQuery() {
		return $this->query;
	}

	private function clear() {
		$this->type = "SELECT";
		$this->table = null;
		$this->fields = '*';
		$this->conditions = array();
		$this->params = array();
		$this->amount = null;
		$this->offset = 0;
		$this->orders = array();
		$this->values = array();
		return $this;
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
