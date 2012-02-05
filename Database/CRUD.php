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
	
	protected function mapParams($params, $qb = null) {
		if (!$qb) {
			$qb = $this->qb;
		}
		if (count($params)) {
			foreach ($params as $key => $value) {
				call_user_func(array($qb, $key), $value);
			}
		}
	}
	
	public function cou($table, $values, $where) {
		$result = $this->read($table, 'id', array('where' => $where));
		
		if (count($result)) {
			return $this->update($table, $values, $where);
		} else {
			return $this->create($table, $values);
		}
	}

	public function create($table, $values) {
		$table = $this->database->matchTable($table);
		
		$this->qb->type('INSERT');
		$this->qb->table($table);
		$this->qb->values($values);

		$result = $this->qb->execute();
		return $result;
	}


	public function read($table, $fields, $params = null) {
		$table = $this->database->matchTable($table);

		$this->qb->type('SELECT');
		$this->qb->fields($fields);
		$this->qb->from($table);
		$this->mapParams($params, $this->qb);
		
		$result = $this->qb->execute();
		return $result;
	}

	public function update($table, $values, $where) {
		$table = $this->database->matchTable($table);

		$this->qb->type('UPDATE');
		$this->qb->table($table);
		$this->qb->values($values);
		$this->qb->where($where);

		$result = $this->qb->execute();
		return $result;
	}

	public function delete($table, $where) {
		$table = $this->qb->matchTable($table);
			
		$this->qb->type('DELETE');
		$this->qb->table($table);
		$this->qb->where($where);
		
		$result = $this->qb->execute();
		return $result;
	}
}
?>
