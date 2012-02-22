<?php
class Entrophy_Database_QueryBuilder {
	private $type = "SELECT";
	private $table;
	private $fields = '*';
	private $values;
	private $conditions;
	private $params;
	private $orders = array();
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
	public function type($type) {
		return $this->setType($type);
	}
	
	public function setTable($table) {
		$this->table = $table;
		return $this;
	}
	public function table($table) {
		return $this->setTable($table);
	}
	public function from($table) {
		return $this->setTable($table);
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
	public function fields($fields) {
		return $this->setFields($fields);
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
	public function values($values) {
		return $this->setValues($values);
	}
	
	public function newCondition($params, $remap = true) {
		return new Entrophy_Database_QueryBuilder_Condition($params, $remap, $this);
	}
	
	public function setCondition($condition, $weight = 0, $key = null) {
		// if $condition is not a condition object or an array of condition objects, create new condition object
		if (!is_object($condition) && !(is_array($condition) && is_object($condition[0]))) {
			$condition = $this->newCondition($condition);
		}
		
		$condition = array($condition, $weight);
		if ($key) {
			$this->conditions[$key] = $condition;
		} else {
			$this->conditions[] = $condition;
		}
		
		return $this;
	}
	
	public function where($params) {
		return $this->setCondition($params);
	}
	
	public function removeCondition($key) {
		$this->conditions[$key] = null;
		unset($this->conditions[$key]);
		return $this;
	}

	public function addOrder($name, $dir = 'asc', $key = null) {	
		$order = (object) array('name' => $name, 'dir' => $dir);
		if ($key) {
			$this->orders[$key] = $order;
		} else {
			$this->orders[] = $order;
		}
		unset($order);
		return $this;
	}
	public function removeOrder($key) {
		$this->orders[$key] = null;
		unset($this->orders[$key]);
		return $this;
	}
	public function order() {
		$args = func_get_args();
		if (is_array($args[0])) {
			foreach ($args as $arg) {
				$this->addOrder($arg[0], $arg[1], $arg[0]);
			}
		} else {
			$i = 0;
			foreach ($args as $arg) {
				if (($i % 2) === 0) {
					$name = $arg;
				} else {
					$dir = $arg;
					$this->addOrder($name, $dir, $name);
				}
				
				$i++;
				unset($arg);
			}
			unset($name, $dir, $i);
		}
		unset($args);
		
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
	public function amount($amount) {
		return $this->setAmount($amount);
	}
	public function setLimit($limit) {
		return $this->setAmount($limit);
	}
	public function limit($limit) {
		return $this->setAmount($limit);
	}
	public function setOffset($offset) {
		$this->offset = $offset;
		return $this;
	}
	public function offset($offset) {
		return $this->setOffset($offset);
	}
	
	private function escapeName($name) {
		return $this->database->field($name);
	}
	private function _escapeName(&$name) {
		$name = $this->escapeName($name);
	}
	/*
	private function wrapValue($value) {
		return $this->database->wrapValue($value);
	}
	private function _wrapValue(&$value) {
		$value = $this->wrapValue($value);
	}
	private function wrapValues($values) {
		return implode(', ', array_map(array($this, 'wrapValue'), $values));
	}
	*/
	
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
		$db = $this->database;
			
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
				$query_parts[] = 'FROM';
				unset($part);
				break;
			case "CREATE";
			case "INSERT":
				$query_parts[] = 'INTO';
				break;
			case "DELETE":
				$query_parts[] = 'FROM';
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

					$this->bindParam($values);
					
					array_walk($values, function (&$value, $key) use ($db) {
						$value = $db->field($key).' = :'.$key;
					});

					
					$query_parts[] = implode(', ', $values);
					unset($values);
					break;
				case 'CREATE';
				case 'INSERT':
					if (!is_array($values[0])) {
						$values = array($values);
					}
					
					$keys = array_map(array($this, 'escapeName'), array_keys($values[0])); // escape field names
					$binds = array_map(function($bind) { return ':'.$bind; }, array_keys($values[0]));
					
					$this->bindParam($values[0]);
					
					$part = '('.implode(', ', $keys).') VALUES ('.implode(', ', $binds).')'; // build value statement
					
					$query_parts[] = $part;		
					unset($part);
					break;
			}
			
			unset($values);
		}
		
		if (count($conditions = $this->conditions) && $this->type != 'INSERT' && $this->type != 'CREATE') {
			usort($conditions, array($this, 'sortConditions'));
			// turn conditions array into a forced nested array to support array of conditions in setCondition()
			$conditions = array_map(function ($condition) {
				$condition =  is_array($condition[0]) ? $condition[0] : array($condition[0]);
				return '('.implode(') AND (', $condition).')';
			}, $conditions);
			
			$query_parts[] = 'WHERE';
			$query_parts[] = '('.implode(') AND (', $conditions).')';
			unset($conditions);
		}

		if (is_array($orders = $this->orders) && count($orders)) {
			$query_parts[] = 'ORDER BY';
			
			$query_parts[] = implode(', ', array_map(function ($order) use ($db) {
				return $db->field($order->name).' '.strtoupper($order->dir);
			}, $orders));
			unset($orders);
		}
		
		if ($this->amount) {
			$query_parts[] = 'LIMIT '.$this->amount;
		}

		if ($this->offset) {
			$query_parts[] = 'OFFSET '.$this->offset;
		}
		
		$query = implode(' ', $query_parts);
		unset($query_parts);
		unset($db);
		return $query;
	}
	
	public function getQuery() {
		return $this->query;
	}

	public function clear() {
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

		$this->database->prepare($this->query, $this->type);
		$this->database->bind($this->params);
		
		$result = $this->database->execute($this->type, true);
		$this->clear();

		return $result;
	}
}
?>
