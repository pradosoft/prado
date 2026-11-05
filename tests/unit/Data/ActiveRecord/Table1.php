<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

/**
 *
 * CREATE TABLE table1 (
 * id integer PRIMARY KEY AUTOINCREMENT,
 * field1 varchar,
 * fk1 integer CONSTRAINT fk_id1 REFERENCES table2(id) ON DELETE CASCADE,
 * fk2 integer CONSTRAINT fk_id2 REFERENCES table2(id) ON DELETE CASCADE,
 * fk3 integer CONSTRAINT fk_id3 REFERENCES table2(id) ON DELETE CASCADE)
 */

class Table1 extends MultipleFKSqliteRecord
{
	public $id;
	public $field1;
	public $fk1;
	public $fk2;
	public $fk3;

	public $object1;
	//public $object2; //commented out for testing __get/__set
	public $object3;

	public static $RELATIONS = [
		'object1' => [self::BELONGS_TO, Table2::class, 'fk1'],
		'object2' => [self::BELONGS_TO, Table2::class, 'fk2'],
		'object3' => [self::BELONGS_TO, Table2::class, 'fk3'],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}
