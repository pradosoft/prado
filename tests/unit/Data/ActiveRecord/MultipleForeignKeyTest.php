<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class MultipleForeignKeyTest extends \PHPUnit\Framework\TestCase
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
		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling');
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
		$arr = Table2::finder()->findByPk(2);

		$this->assertNotNull($arr->state2); //lazy load
		$this->assertEquals(count($arr->state2), 1);
		$this->assertEquals($arr->state2[0]->id, "1");
		$this->assertNotNull($arr->state2[0]->object2);
		$this->assertEquals($arr->state2[0]->object2->id, "2");

		$this->assertNotSame($arr, $arr->state2[0]->object2);
	}
}
