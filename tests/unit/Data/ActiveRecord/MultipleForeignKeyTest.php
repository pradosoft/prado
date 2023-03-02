<?php


abstract class MultipleFKSqliteRecord extends TActiveRecord
{
	protected static $conn;

	public function getDbConnection()
	{
		if (self::$conn === null) {
			self::$conn = new TDbConnection('sqlite:' . __DIR__ . '/test1.sqlite');
		}
		return self::$conn;
	}
}

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
		'object1' => [self::BELONGS_TO, 'Table2', 'fk1'],
		'object2' => [self::BELONGS_TO, 'Table2', 'fk2'],
		'object3' => [self::BELONGS_TO, 'Table2', 'fk3'],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

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
		'state1' => [self::HAS_MANY, 'Table1', 'fk1'],
		'state2' => [self::HAS_MANY, 'Table1', 'fk2'],
		'state3' => [self::HAS_ONE, 'Table1', 'fk3'],
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

/**
 *
 * CREATE TABLE CategoryX (
 * cat_id integer PRIMARY KEY AUTOINCREMENT,
 * category_name varchar,
 * parent_cat varchar,
 * parent_category integer CONSTRAINT fk_id1 REFERENCES CategoryX(cat_id) ON DELETE CASCADE,
 * child_categories integer CONSTRAINT fk_id2 REFERENCES CategoryX(cat_id) ON DELETE CASCADE
 * )
 */
class CategoryX extends MultipleFKSqliteRecord
{
	public $cat_id;
	public $category_name;
	public $parent_cat;

	public $parent_category;
	public $child_categories = [];

	public static $RELATIONS = [
		'parent_category' => [self::BELONGS_TO, 'CategoryX'],
		'child_categories' => [self::HAS_MANY, 'CategoryX'],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

class MultipleForeignKeyTest extends PHPUnit\Framework\TestCase
{
	public function testBelongsTo()
	{
		$obj = Table1::finder()->withObject1()->findAll();
		$this->assertEquals(count($obj), 3);
		$this->assertEquals($obj[0]->id, '1');
		$this->assertEquals($obj[1]->id, '2');
		$this->assertEquals($obj[2]->id, '3');

		$this->assertEquals($obj[0]->object1->id, '1');
		$this->assertEquals($obj[1]->object1->id, '2');
		$this->assertEquals($obj[2]->object1->id, '2');
	}

	public function testHasMany()
	{
		$obj = Table2::finder()->withState1()->findAll();
		$this->assertEquals(count($obj), 5);

		$this->assertEquals(count($obj[0]->state1), 1);
		$this->assertEquals($obj[0]->state1[0]->id, '1');

		$this->assertEquals(count($obj[1]->state1), 2);
		$this->assertEquals($obj[1]->state1[0]->id, '2');
		$this->assertEquals($obj[1]->state1[1]->id, '3');

		$this->assertEquals(count($obj[2]->state1), 0);
		$this->assertEquals($obj[2]->id, '3');

		$this->assertEquals(count($obj[3]->state1), 0);
		$this->assertEquals($obj[3]->id, '4');
	}

	public function testHasOne()
	{
		$obj = Table2::finder()->withState3('id = 3')->findAll();

		$this->assertEquals(count($obj), 5);

		$this->assertEquals($obj[0]->id, '1');
		$this->assertNull($obj[0]->state3);

		$this->assertEquals($obj[1]->id, '2');
		$this->assertNull($obj[1]->state3);

		$this->assertEquals($obj[2]->id, '3');
		$this->assertNotNull($obj[2]->state3);
		$this->assertEquals($obj[2]->state3->id, '3');

		$this->assertEquals($obj[3]->id, '4');
		$this->assertNull($obj[3]->state3);
	}

	public function testParentChild()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$obj = CategoryX::finder()->withChild_Categories()->withParent_Category()->findByPk(2);

				$this->assertEquals($obj->cat_id, '2');
				$this->assertEquals(count($obj->child_categories), 2);
				$this->assertNotNull($obj->parent_category);

				$this->assertEquals($obj->child_categories[0]->cat_id, 3);
				$this->assertEquals($obj->child_categories[1]->cat_id, 4);

				$this->assertEquals($obj->parent_category->cat_id, 1);
		*/
	}

	public function testLazyLoadingGetterSetter_hasMany()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				$arr = Table2::finder()->findByPk(2);

				$this->assertNotNull($arr->state2); //lazy load
				$this->assertEquals(count($arr->state2), 1);
				$this->assertEquals($arr->state2[0]->id, "1");
				$this->assertNotNull($arr->state2[0]->object2);
				$this->assertEquals($arr->state2[0]->object2->id, "2");

				$this->assertNotIdentical($arr, $arr->state2[0]->object2);
		*/
	}
}
