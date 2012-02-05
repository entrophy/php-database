<?php
class Entrophy_Database_QueryBuilder_Condition {
	private $sql;
	
	private $conditions = array();
	
	public function __construct($params, $remap = true, $qb) {
		$db = Entrophy_Database::getInstance();
		
		if (is_array($params)) {
			$parts = array();
			foreach ($params as $field => $value) {				
				if ($remap && $value != ':'.$field) {
					$qb->bindParam($field, $value);
					$value = ':'.$field;
				} elseif ($value != ':'.$field) {
					$value = $db->wrapValue($value);
				}
				
				$field = $db->field($field);
				$parts[] = $field." = $value";
			}
			$this->sql = implode(' AND ', $parts);
		} else {
			$this->sql = $params;
		}
		
		unset($qb, $db);
	}
	
	public function getSql() {
		$sql = $this->sql;
		
		foreach ($this->conditions as $condition) {
			$sql = '('.$sql.') OR ('.$condition->getSql().')';
		}

		return $sql;
	}

	public function _or($condition) {
		$this->conditions[] = $condition;
		
		return $this;
	}

	public function __call($method, $args) {
		if ($method == 'or') {
			return $this->_or($args[0]);
		}
	}

	public function __toString() {
		return $this->getSql();
	}
}
?>
