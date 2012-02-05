<?php
class Entrophy_Database_CRUD {
	private $database;
	private $qb;
	
	private static $instance;
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Entrophy_Database_CRUD();
		}
		return self::$instance;	
	}

	public function __construct() {
		$this->database = Entrophy_Database::getInstance();
		$this->qb = $this->database->queryBuilder();
	}
	
	public function cou($table, $data, $where) {
		$result = $this->read($table, '*', 'WHERE '.$where);
		
		if (count($result)) {
			return $this->update($table, $data, $where);
		} else {
			return $this->create($table, $data);
		}
	}

	public function create($table, $values) {
		$table = $this->database->matchTable($table);
		
		$this->qb->setType('INSERT');
		$this->qb->setTable($table);
		$this->qb->setValues($values);

		$result = $this->qb->execute();

		return $result;
	}

	public function read($table, $fields, $additional = null) {
		$table = $this->database->matchTable($table);

		$this->qb->setType('SELECT');
		$this->qb->setTable($table);

		$query = $this->qb->buildQuery();

		if ($additional) {
			$query .= ' '.$additional;
		}

		$result = $this->qb->execute('SELECT', $query);

		return $result;
	}

	public function update($table, $data, $where) {
		$table = $this->database->matchTable($table);

		$this->qb->setType('UPDATE');
		$this->qb->setTable($table);
		$this->qb->setValues($data);

		$query = $this->qb->buildQuery();
		$query .= ' WHERE '.$where;

		$result = $this->qb->execute('UPDATE', $query);

		return $result;
	}

	public function delete($table, $where) {
		$table = $this->qb->matchTable($table);
			
		$this->qb->setType('DELETE');
		$this->qb->setTable($table);

		$query = $this->qb->buildQuery();
		$query .= ' WHERE '.$where;

		$result = $this->qb->execute('DELETE', $query);

		return $result;
	}
}
?>
