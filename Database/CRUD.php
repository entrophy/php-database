<?php
class ENT_Database_CRUD {
	private $database;
	private $queryBuilder;

	public function __construct($database) {
		$this->database = $database;
		$this->queryBuilder = $this->database->queryBuilder();
	}
	
	public function cou($table, $data, $where) {
		$result = $this->read($table, '*', 'WHERE '.$where);
		
		if (count($result)) {
			return $this->update($table, $data, $where);
		} else {
			return $this->create($table, $data);
		}
	}

	public function create($table, $data) {
		$table = $this->database->matchTable($table);
		
		$this->queryBuilder->setType('INSERT');
		$this->queryBuilder->setTable($table);
		$this->queryBuilder->setValues($data);

		$result = $this->queryBuilder->execute();

		return $result;
	}

	public function read($table, $fields, $additional = null) {
		$table = $this->database->matchTable($table);

		$this->queryBuilder->setType('SELECT');
		$this->queryBuilder->setTable($table);

		$query = $this->queryBuilder->buildQuery();

		if ($additional) {
			$query .= ' '.$additional;
		}

		$result = $this->queryBuilder->execute('SELECT', $query);

		return $result;
	}

	public function update($table, $data, $where) {
		$table = $this->database->matchTable($table);

		$this->queryBuilder->setType('UPDATE');
		$this->queryBuilder->setTable($table);
		$this->queryBuilder->setValues($data);

		$query = $this->queryBuilder->buildQuery();
		$query .= ' WHERE '.$where;

		$result = $this->queryBuilder->execute('UPDATE', $query);

		return $result;
	}

	public function delete($table, $where) {
		$table = $this->database->matchTable($table);
			
		$this->queryBuilder->setType('DELETE');
		$this->queryBuilder->setTable($table);

		$query = $this->queryBuilder->buildQuery();
		$query .= ' WHERE '.$where;

		$result = $this->queryBuilder->execute('DELETE', $query);

		return $result;
	}
}
?>
