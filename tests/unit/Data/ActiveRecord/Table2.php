<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

/**
 * CREATE TABLE table2 (id integer PRIMARY KEY AUTOINCREMENT,field1 varchar)
 */
class Table2 extends MultipleFKSqliteRecord
{
	public $id;
	public $field1;

	private $_state1;
	//public $state2; //commented out for testing __get/__set
	public $state3;

	public static $RELATIONS = [
		'state1' => [self::HAS_MANY, Table1::class, 'fk1'],
		'state2' => [self::HAS_MANY, Table1::class, 'fk2'],
		'state3' => [self::HAS_ONE, Table1::class, 'fk3'],
	];

	public function setState1($obj)
	{
		$this->_state1 = $obj;
	}

	public function getState1()
	{
		if (null === $this->_state1) {
			$this->fetchResultsFor('state1');
		}
		return $this->_state1;
	}

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}
