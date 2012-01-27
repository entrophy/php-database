<?php
spl_autoload_register(array('Entrophy_Database', 'autoload'), true, true);
class Entrophy_Database {
	private $config;
	private $prefix;
	
	private $lastQuery;
	
	private $insertID;	
	private $rows;
	private $totalRows;
	
	private $pdo;
	private $statement;
	
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
		$this->pdo = null;
		$this->statement = null;
	}

	public function init($config) {
		$this->config = is_array($config) ? (object) $config : $config;
		$this->prefix = $this->config->prefix;

		$this->pdo = new PDO(
			'mysql:host='.$this->config->host.';dbname='.$this->config->database.';',
			$this->config->username,
			$this->config->password,
			array(
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$this->config->charset
			)
		);

		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		unset($this->config);
	}
	
	public function eav($table, $entityid) {
		return new Entrophy_Database_EAV($this->matchTable($table), $entityid, $this);
	}
	
	public function queryBuilder() {
		return new Entrophy_Database_QueryBuilder($this);
	}
	public function cruid() {
		return new Entrophy_Database_CRUD($this);
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
	
	public function prepare($query) {
		$this->statement = $this->pdo->prepare($query);
		
		$this->lastQuery = $query;
	}

	public function bind($data, $value = null) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->statement->bindValue($key, $value);
			}
		} else if ($value !== null) {
			$this->bind(array($data => $value));
		}
	}
	
	public function query($query) {
		return $this->pdo->query($query);
	}
	
	public function execute($type, $showQueryOnError = false) {
		// ENT_Profiler::startQuery($query);	
			$result = array();
			try {
				$this->statement->execute();

				$this->rows = $this->statement->rowCount();
				$this->totalRows = null;
				$this->insertID = $this->pdo->lastInsertID();

				$result = ($type == 'SELECT') ? $this->statement->fetchAll(PDO::FETCH_ASSOC) : array();

				if (strstr($this->lastQuery, 'LIMIT')) {
					$_statement = $this->pdo->prepare('SELECT FOUND_ROWS() as rows');
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
		// ENT_Profiler::stopQuery();
		return $result;
	}
}
?>
