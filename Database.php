<?php
#include_once 'Database/CRUD.php'; /* is inherited */
class Entrophy_Database {
	private $config;
	private $prefix;
	
	private $lastQuery;
	
	private $insertID;	
	private $rows;
	private $totalRows;
	
	private $statement;
	
	/**
	 * $master, $write & $read are all PDO objects
	 */
	private $master;
	private $write;
	private $read;
	
	private static $instance;
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Entrophy_Database();
		}
		return self::$instance;	
	}
	
	public static function autoload($class) {
		if (strpos($class, 'Entrophy_Database_') === 0) {
			if (class_exists($class, false) || interface_exists($class, false)) {
				return;
			}
		
			$file = str_replace(array('_', 'Entrophy/'), array('/', ''), $class).'.php';
			include $file;
		}
	}

	private function __construct() {}

	public function __destruct() {
		/*$this->master = null;
		$this->write = null;
		$this->read = null;
		$this->statement = null;*/
	}

	public function init($config) {
		$this->config = is_array($config) ? (object) $config : $config;
		$master = $this->config->master ? (object) $this->config->master : $this->config;
		
		$this->master = new PDO(
			'mysql:host='.$master->host.';dbname='.$master->database.';',
			$master->user,
			$master->password,
			array(
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$master->charset
			)
		);
		$this->master->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->write = $this->master;
		
		if ($this->config->read || $this->config->{'read-replicas'}) {
			$read = $this->config->read ? : $this->config->{'read-replicas'};
			if (is_array($read[0]) || is_object($read[0])) {
				$read = $read[array_rand($read)];
			}
			$read = (object) $read;
			
			$this->read = new PDO(
				'mysql:host='.$read->host.';dbname='.$read->database.';',
				$read->user,
				$read->password,
				array(
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$read->charset
				)
			);
			$this->read->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} else {
			$this->read = $this->master;
		}
		
		unset($this->config);
	}
	
	public function eav($table, $entityid) {
		return new Entrophy_Database_EAV($this->matchTable($table), $entityid, $this);
	}
	
	public function queryBuilder() {
		return new Entrophy_Database_QueryBuilder($this);
	}
	public function qb() {
		return $this->queryBuilder();
	}
	public function crud() {
		return Entrophy_Database_Crud::getInstance();
	}
	
	public function insertID() {
		return $this->insertID;
	}
	
	public function getTotalRows() {
		return $this->totalRows;
	}
	
	public function getRows() {
		return $this->rows;
	}
	
	public function matchTable($table) {
		if (!preg_match('/^'.$this->prefix.'(.+)/', $table)) {
			$table = $this->prefix . $table;
		}
		return $table;
	}
	
	public function escape($value) {
		if (is_array($value)) {
			foreach ($value as $key => $data) {
				$value[$key] = mysql_real_escape_string(stripslashes($data));
			}
			return $value;
		}
		
		if ($value) {
			return mysql_real_escape_string(stripslashes($value));
		}
		
		return $value;
	}
	
	public function field($name) {
		if ($name != '*' && substr($name, 0, 1) != '`' && substr($name, -1, 1) != '`' && substr($name, 0, 1) != '(') {
			if (strpos($name, '.') !== FALSE) {
				$name = str_replace('.', '`.`', $name);
			}
			$name = '`'.$name.'`';
		}		
		return $name;
	}
	public function _field(&$name) {
		$name = $this->field($name);
	}

	/*
	public function wrapValue($value) {
		if (!is_numeric($value)) {
			$value = "'".$value."'";
		}
		return $value;
	}
	*/
	
	public function getType($param) {;
		if ($param == 'read' || $param == 'SELECT' || strpos($param, 'SELECT') === 0) {
			return 'read';
		}
		return 'write';
	}
	
	public function prepare($query, $type = null) {
		$type = $type ? $this->getType($type) : $this->getType($query);
		$this->statement = $this->{$type}->prepare($query);	
		$this->lastQuery = $query;
		return $this;
	}

	public function bind($data, $value = null) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->statement->bindValue($key, $value);
			}
		} else if ($value !== null) {
			$this->bind(array($data => $value));
		}
		return $this;
	}
	
	public function query($query) {
		return $this->pdo->query($query);
	}
	
	public function execute($type, $showQueryOnError = false) {
		// Entrophy_Profiler::startQuery($query);	
			$result = array();
			try {
				$type = $this->getType($type);
				$this->statement->execute();

				$this->rows = $this->statement->rowCount();
				$this->totalRows = null;
				if ($type == 'write') {
					$this->insertID = $this->write->lastInsertID();
				}

				$result = ($type == 'read') ? $this->statement->fetchAll(PDO::FETCH_ASSOC) : array();
				
				if (strstr($this->lastQuery, 'LIMIT')) {
					$_statement = $this->read->prepare('SELECT FOUND_ROWS() as rows');
					$_statement->execute();

					$_result = $_statement->fetchAll(PDO::FETCH_ASSOC);

					$this->totalRows = $_result[0]['rows'];
				}
			} catch (PDOException $e) {
				echo "\r\n".'ERROR: '.$e->getMessage().' '.$e->getTraceAsString();

				if ($showQueryOnError) {
					echo "\r\n\r\n".'QUERY: '.$this->lastQuery;
				}
			}
		// Entrophy_Profiler::stopQuery();
		return $result;
	}
}
spl_autoload_register(array('Entrophy_Database', 'autoload'), true, true);
?>
