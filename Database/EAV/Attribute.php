<?php
class ENT_Database_EAV_Attribute {
	private $key;
	private $table;
	private $entity_id;
	private $database;
	private $data;
	private $id;
	private $baseValueID;
	
	public function __construct($key, $table, $entity_id, $database) {
		$this->key = $key;
		$this->table = $table;
		$this->entity_id = $entity_id;
		$this->database = $database;
		
		$builder = $this->database->queryBuilder();
		$builder->setTable($this->table."_attribute")
				->addCondition("`key` = '$key'");
		
		
		$result = $builder->execute();
		$this->data = $this->database->getArray($result);
		
		$this->id = $this->data['id'];
	}
	
	public function getKey() {
		return $this->key;
	}
	
	public function getType() {
		return $this->data['type'];
	}
	
	public function valueExists() {
		$builder = $this->database->queryBuilder();
		$builder->setTable($this->table."_value_".$this->getType())
				->addCondition("entity_id  = ".$this->entity_id)
				->addCondition("attribute_id = ".$this->id)
				->addField('id');
				
		return $this->database->getRows($builder->execute());
	}

	public function getValue() {	
		$builder = $this->database->queryBuilder();
		$builder->setTable($this->table."_value_".$this->getType())
				->addCondition("entity_id = ".$this->entity_id)
				->addCondition("attribute_id = ".$this->id)
				->addField('value');
		
		$value = $this->database->getArray($builder->execute());
		return $value[0];
	}
	
	public function saveValue($value) {			
		if (!$this->valueExists()) {
			$builder = $this->database->queryBuilder();
			$builder->setTable($this->table."_value_".$this->getType())
					->setType('insert')
					->setData(array(
						'entity_id' => $this->entity_id,
						'attribute_id' => $this->id,
						'value' => $value
					));
					
			$builder->execute();
		} else {
			$builder = $this->database->queryBuilder();
			$builder->setTable($this->table."_value_".$this->getType())
					->setType('update')
					->setData(array(
						'value' => $value
					))
					->addCondition("entity_id = ".$this->entity_id)
					->addCondition("attribute_id = ".$this->id);
			
			$builder->execute();
		}
		
		/*$builder = $this->database->queryBuilder();
		$builder->setTable($this->table."_value")
				->addCondition($this->table."_attribute_id = ".$this->id)
				->addCondition($this->table."_id = ".$this->entity_id)
				->addField('id');			
		$initValue = $builder->getQuery();
	
		$builder = $this->database->queryBuilder();
		$builder->setTable($this->table."_value_".$this->getType())
				->setType('UPDATE')
				->setData(array('value' => $value))
				->addCondition($this->table."_value_id = (".$initValue.")");
		
		echo $builder->getQuery();*/
	}
}
?>