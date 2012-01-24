<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

echo 'init';

include __DIR__.'/Database.php';
include __DIR__.'/Database/CRUD.php';
include __DIR__.'/Database/QueryBuilder.php';
include __DIR__.'/Database/QueryBuilder/Condition.php';

$database = ENT_Database::getInstance();
$database->init(array(
	'host' => 'localhost',
	'username' => 'aspx',
	'password' => 'HpYhYvXyYmGJ4Snb',
	'database' => 'aspx-test',
	'charset' => 'utf8',
	'prefix' => ''
));

$crud = new ENT_Database_CRUD($database);

$result = $crud->read('label', array('id'), 'WHERE id < 10');
print_r($result);

$result = $crud->create('label', array('title' => 'tech'));
print_r($result);


$result = $crud->update('label', array('title' => 't  e c h'), 'id = 4');
print_r($result);

$result = $crud->delete('label', 'id = 5');
print_r($result);

$result = $crud->cou('label', array('title' => 't e c h'), 'id = 800');
print_r($result);


/*
$database->prepare("INSERT INTO label (id, title) VALUES (0, 'tech')");
$database->execute('insert', true);

$database->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM label WHERE id < :id");
$database->bind(':id', 4);
$result = $database->execute('SELECT', true);

print_r($result);
echo $database->getRows()." <- rows\r\n";
echo $database->getTotalRows()." <- rows total\r\n";


$database->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM label WHERE id < :id LIMIT 2");
$database->bind(array(':id' => 4));
$result = $database->execute('SELECT', true);

print_r($result);
echo $database->getRows()." <- rows\r\n";
echo $database->getTotalRows()." <- rows total\r\n";

echo "\r\n\r\n".'-------------'."\r\n\r\n";
*//*
$q = new ENT_Database_QueryBuilder($database);

$q->setType('select');
$q->setTable('label');
$q->setFields(array('id', 'title'));
$q->setCondition($q->newCondition('id = :id')->or($q->newCondition('id = :other_id')), 5);
$q->bindParam('id', 2);
$q->bindParam(array(
	'other_id' => 3	
));
$q->setCondition("title != 'hello'", 0);
$q->addOrder('id', 'desc');
$q->addOrder('title', 'asc');
$q->setAmount(20);
$q->setOffset(1);
$q->addGroupBy('id');
$q->addHaving('SUM(id) < 10');

$result = $q->execute();

echo $q->getQuery()."\r\n";

print_r($result);
echo $database->getRows()." <- rows\r\n";
echo $database->getTotalRows()." <- rows total\r\n";

$q->setType('insert');
$q->setTable('label');
$q->setFields(array('id', 'title'));
$q->setValues(array(
	'title' => 'tech'
));

$result = $q->execute();

echo $q->getQuery()."\r\n";

print_r($result);
echo $database->getRows()." <- rows\r\n";
echo $database->getTotalRows()." <- rows total\r\n";

/*
$q->setType('select');
$q->setTable('label');
$q->setFields(array('id', 'title'));
$q->setCondition(array(
	$q->newCondition('id = :id')->or($q->newCondition('id = :other_id')),
	$q->newCondition('title = :some_title')
));

$q->bindParam(array(
	'id' => 2,
	'other_id' => 3,
	':some_title' => 'tech'
));

$result = $q->execute();

echo $q->getQuery()."\r\n";

print_r($result);
echo $database->getRows()." <- rows\r\n";
echo $database->getTotalRows()." <- rows total\r\n";

echo "\r\n\r\n".'final';
*/
?>
