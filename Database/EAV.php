<?php
class ENT_Database_EAV {
	private $table;
	private $database;
	private $entityId;
	private $_attributes;
	private $values;
	
	public function __construct($table, $entityid, $database) {
		$this->table = $table;
		$this->database = $database;
		$this->entityid = $entityid;
	}
	
	public function saveValue($key, $value) {
		return $this->getAttribute($key)->saveValue($value);
	}
	public function getValue($key) {
		return $this->getAttribute($key)->getValue();
	}
	
	public function getAttributes() {
		if (!$this->_attributes) {
			$builder = $this->database->queryBuilder();
			$builder->setTable($this->table."_attribute");
			$builder->addField('`key`');
			$result = $builder->execute();
			
			while ($data = $this->database->getArray($result)) {
				$attribute = new ENT_Database_EAV_Attribute($data['key'], $this->table, $this->entityid, $this->database);
				$this->_attributes[] = $attribute;
				$this->attributes[$key] = $attribute;
			}
		}
		return $this->_attributes;
	}
	
	public function getAttribute($key) {
		if (!$this->attributes[$key]) {
			$this->attributes[$key] = new ENT_Database_EAV_Attribute($key, $this->table, $this->entityid, $this->database);
		}
		return $this->attributes[$key];
	}
	
	public function valueExists($key) {
		if (!$this->valueResult) {		
			$builder = $this->database->queryBuilder();
			$builder->setTable($this->table."_value")
					->addCondition($this->table."_attribute_id = ".$this->entityId)
					->addCondition("`key` = '$key'");
			
			$this->valueResult = $builder->execute();
		}
		return $this->database->getRows($this->valueResult);
	}
}
?>