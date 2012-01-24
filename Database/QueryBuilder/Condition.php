<?php
class ENT_Database_QueryBuilder_Condition {
	private $sql;
	
	private $conditions = array();
	
	public function __construct($params) {
		if (is_array($params)) {
			foreach ($params as $field => $value) {
				$field = ENT_Database::getInstance()->field($field);
				
				if (is_numeric($value)) {
					$this->sql = $field." = $value";
				} else {
					$this->sql = $field." = '$value'";
				}
			}
		} else {
			$this->sql = $params;
		}
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
