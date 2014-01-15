<?php

class TPriorityMapTest_MapItem {
  public $data='data';
}

Prado::using('System.Collections.TPriorityMap');
/**
 * @package System.Collections
 */
class TPriorityMapTest extends PHPUnit_Framework_TestCase {
  protected $map;
  protected $item1,$item2,$item3,$item4,$item5;

  public function setUp() {
  	// test that TPriorityMap complies with TMap
    $this->map=new TPriorityMap;
    $this->item1=new TPriorityMapTest_MapItem;
    $this->item2=new TPriorityMapTest_MapItem;
    $this->item3=new TPriorityMapTest_MapItem;
    $this->item4=new TPriorityMapTest_MapItem;
    $this->item5=new TPriorityMapTest_MapItem;
    $this->map->add('key1',$this->item1);
    $this->map->add('key2',$this->item2);

    //Test the priority capabilities
  }

  public function setUpPriorities() {
	$this->map->add('key3', $this->item3, 0);
	$this->map->add('key4', $this->item4, 100);
	$this->map->add('key5', $this->item5, 1);
  }

  public function tearDown() {
    $this->map=null;
    $this->item1=null;
    $this->item2=null;
    $this->item3=null;
  }

  public function testConstruct() {
    $a=array(1,2,'key3'=>3);
    $map=new TPriorityMap($a);
    $this->assertEquals(3,$map->getCount());
    $map2=new TPriorityMap($this->map);
    $this->assertEquals(2,$map2->getCount());

	/* Test the priority functionality of TPriorityMap  */

    $map3=new TPriorityMap($this->map, false, 100, -1);
    $this->assertEquals(100,$map3->getDefaultPriority());
    $this->assertEquals(-1,$map3->getPrecision());
  }

  /* Test that TPriorityMap complies with TMap   */


	public function testGetReadOnly() {
		$map = new TPriorityMap(null, true);
		self::assertEquals(true, $map->getReadOnly(), 'List is not read-only');
		$map = new TList(null, false);
		self::assertEquals(false, $map->getReadOnly(), 'List is read-only');
	}

  public function testGetCount() {
    $this->assertEquals(2,$this->map->getCount());
  }

  public function testGetKeys() {
    $keys=$this->map->getKeys();
    $this->assertTrue(count($keys)===2 && $keys[0]==='key1' && $keys[1]==='key2');
  }

	public function testAdd()
	{
		$this->map->add('key3',$this->item3);
		$this->assertTrue($this->map->getCount()==3 && $this->map->contains('key3'));
	}

	public function testCanNotAddWhenReadOnly() {
		$map = new TPriorityMap(array(), true);
		try {
			$map->add('key', 'value');
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

	public function testRemove()
	{
		$this->map->remove('key1');
		$this->assertTrue($this->map->getCount()==1 && !$this->map->contains('key1'));
		$this->assertTrue($this->map->remove('unknown key')===null);
	}

	public function testCanNotRemoveWhenReadOnly() {
		$map = new TPriorityMap(array('key' => 'value'), true);
		try {
			$map->remove('key');
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

	public function testClear()
	{
		$this->map->clear();
		$this->assertTrue($this->map->getCount()==0 && !$this->map->contains('key1') && !$this->map->contains('key2'));
	}

	public function testContains()
	{
		$this->assertTrue($this->map->contains('key1'));
		$this->assertTrue($this->map->contains('key2'));
		$this->assertFalse($this->map->contains('key3'));
	}

	public function testCopyFrom()
	{
		$array=array('key3'=>$this->item3,'key4'=>$this->item1);
		$this->map->copyFrom($array);
		$this->assertTrue($this->map->getCount()==2 && $this->map['key3']===$this->item3 && $this->map['key4']===$this->item1);
		try
		{
			$this->map->copyFrom($this);
			$this->fail('no exception raised when copying a non-traversable object');
		}
		catch(TInvalidDataTypeException $e)
		{

		}
	}

	public function testMergeWith()
	{
		$array=array('key2'=>$this->item1,'key3'=>$this->item3);
		$this->map->mergeWith($array);
		$this->assertEquals(3,$this->map->getCount());
		$this->assertTrue($this->map['key2']===$this->item1);
		$this->assertTrue($this->map['key3']===$this->item3);
		try
		{
			$this->map->mergeWith($this);
			$this->fail('no exception raised when copying a non-traversable object');
		}
		catch(TInvalidDataTypeException $e)
		{

		}
	}

	public function testArrayRead()
	{
		$this->assertTrue($this->map['key1']===$this->item1);
		$this->assertTrue($this->map['key2']===$this->item2);
		$this->assertEquals(null,$this->map['key3']);
	}

	public function testArrayWrite()
	{
		$this->map['key3']=$this->item3;
		$this->assertTrue($this->map['key3']===$this->item3);
		$this->assertEquals(3,$this->map->getCount());
		$this->map['key1']=$this->item3;
		$this->assertTrue($this->map['key1']===$this->item3);
		$this->assertEquals(3,$this->map->getCount());
		unset($this->map['key2']);
		$this->assertEquals(2,$this->map->getCount());
		$this->assertFalse($this->map->contains('key2'));
		try
		{
			unset($this->map['unknown key']);

		}
		catch(Exception $e)
		{
			$this->fail('exception raised when unsetting element with unknown key');
		}
	}

	public function testArrayForeach()
	{
		$n=0;
		$found=0;
		foreach($this->map as $index=>$item)
		{
			$n++;
			if($index==='key1' && $item===$this->item1)
				$found++;
			if($index==='key2' && $item===$this->item2)
				$found++;
		}
		$this->assertTrue($n==2 && $found==2);
	}

	public function testArrayMisc()
	{
		$this->assertEquals($this->map->Count,count($this->map));
		$this->assertTrue(isset($this->map['key1']));
		$this->assertFalse(isset($this->map['unknown key']));
	}

	public function testToArray() {
		$map = new TPriorityMap(array('key' => 'value'));
		self::assertEquals(array('key' => 'value'), $map->toArray());
	}




	/* Test the priority functionality of TPriorityMap  */


	public function testDefaultPriorityAndPrecision() {

		$this->assertEquals(10, $this->map->DefaultPriority);

		$this->map->DefaultPriority = 5;
		$this->assertEquals(5, $this->map->getDefaultPriority());

		$this->assertEquals(8, $this->map->Precision);

		$this->map->Precision = 0;
		$this->assertEquals(0, $this->map->getPrecision());

		;

		$this->assertEquals(5, $this->map->add('key3', $this->item3));
		$this->assertEquals(10, $this->map->add('key4', $this->item1, 10));
		$this->assertTrue(10 == $this->map->add('key4', $this->item1, 10.01));
		$this->assertTrue(100 == $this->map->add('key4', $this->item1, 100));
		$this->map->Precision = 1;
		$this->assertTrue(10.1 == $this->map->add('key5', $this->item1, 10.1));

		$this->assertEquals(5, $this->map->getCount());
	}

	public function testAddWithPriorityAndPriorityOfAt() {

		$this->setUpPriorities();

		$this->assertEquals(5, $this->map->getCount());
		$this->assertEquals(10, $this->map->priorityOf($this->item1));
		$this->assertEquals(0, $this->map->priorityOf($this->item3));
		$this->assertEquals(100, $this->map->priorityOf($this->item4));
		$this->assertEquals(1, $this->map->priorityOf($this->item5));
		$this->assertEquals(false, $this->map->priorityOf(null));
		$this->assertEquals(false, $this->map->priorityOf('foo'));

		$this->assertEquals(10, $this->map->priorityAt('key1'));
		$this->assertEquals(0, $this->map->priorityAt('key3'));
		$this->assertEquals(100, $this->map->priorityAt('key4'));
		$this->assertEquals(1, $this->map->priorityAt('key5'));
		$this->assertEquals(false, $this->map->priorityAt(null));
		$this->assertEquals(false, $this->map->priorityAt('foo'));

	}

	public function testRemoveWithPriorityAndItemsAtWithPriority() {

		$this->setUpPriorities();

		$this->assertEquals(5, $this->map->getCount());
		$this->map->remove('key6');
		$this->assertEquals(5, $this->map->getCount());
		$this->map->remove('key6', null);
		$this->assertEquals(5, $this->map->getCount());


		// key5 is at priority 1...   not the default priority defined by null...  nothing should happen here
		$this->map->remove('key5', null);
		$this->assertEquals(5, $this->map->getCount());

		// key5 is at priority 1...   not 50...  nothing should happen here
		$this->map->remove('key5', 50);
		$this->assertEquals(5, $this->map->getCount());



		$this->assertEquals(array('key3'=>$this->item3), $this->map->itemsAtPriority(0));
		$this->assertEquals(array('key1'=>$this->item1, 'key2'=>$this->item2), $this->map->itemsAtPriority($this->map->DefaultPriority));

		$this->assertEquals($this->item2, $this->map->itemAt('key2'));
		$this->assertEquals($this->item2, $this->map->itemAt('key2', 10));
		$this->assertNull($this->map->itemAt('key2', 11)); //'key2' doesn't exist and priority 11...  it is only at priority 10
		$this->assertNull($this->map->itemAt('key2', 10.1)); //'key2' doesn't exist and priority 10.1...  it is only at priority 10

		$this->assertEquals($this->item4, $this->map->remove('key4'));
		$this->assertEquals(4, $this->map->getCount());

		$this->assertEquals($this->item5, $this->map->remove('key5'));
		$this->assertEquals(3, $this->map->getCount());
	}
	public function testIteratorAndArrayWithPriorities() {

		$this->setUpPriorities();

		// This is the primary reason for a TPriorityMap
		$array = $this->map->toArray();

		$ordered_keys = array_keys($array);
		$this->assertEquals('key3', $ordered_keys[0]);
		$this->assertEquals('key5', $ordered_keys[1]);
		$this->assertEquals('key1', $ordered_keys[2]);
		$this->assertEquals('key2', $ordered_keys[3]);
		$this->assertEquals('key4', $ordered_keys[4]);

		$ordered_values = array_values($array);
		$this->assertEquals($this->item3, $ordered_values[0]);
		$this->assertEquals($this->item5, $ordered_values[1]);
		$this->assertEquals($this->item1, $ordered_values[2]);
		$this->assertEquals($this->item2, $ordered_values[3]);
		$this->assertEquals($this->item4, $ordered_values[4]);

		$iter = $this->map->getIterator();

		$this->assertTrue($iter->valid());
		$this->assertEquals('key3', $iter->key());
		$this->assertEquals($this->item1, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key5', $iter->key());
		$this->assertEquals($this->item3, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key1', $iter->key());
		$this->assertEquals($this->item5, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key2', $iter->key());
		$this->assertEquals($this->item5, $iter->current());
		$iter->next();
		$this->assertTrue($iter->valid());
		$this->assertEquals('key4', $iter->key());
		$this->assertEquals($this->item5, $iter->current());
		$iter->next();
		$this->assertFalse($iter->valid());
		$this->assertEquals(null, $iter->key());
		$this->assertEquals(null, $iter->current());
	}


	public function testGetPriorities() {
		$this->setUpPriorities();

		$priorities = $this->map->getPriorities();

		$this->assertEquals(0, $priorities[0]);
		$this->assertEquals(1, $priorities[1]);
		$this->assertEquals(10, $priorities[2]);
		$this->assertEquals(100, $priorities[3]);
		$this->assertEquals(false, isset($priorities[4]));
	}


	public function testCopyAndMergeWithPriorities() {
		$this->setUpPriorities();

		$map1 = new TPriorityMap();
		$map1->add('key1', $this->item1);
		$map1->add('keyc', 'valuec');
		$map1->copyFrom($this->map);

		$this->assertEquals(5, $map1->getCount());

		$array = $map1->toArray();
		$ordered_keys = array_keys($array);
		$this->assertEquals('key3', $ordered_keys[0]);
		$this->assertEquals('key5', $ordered_keys[1]);
		$this->assertEquals('key1', $ordered_keys[2]);
		$this->assertEquals('key2', $ordered_keys[3]);
		$this->assertEquals('key4', $ordered_keys[4]);

		$ordered_values = array_values($array);
		$this->assertEquals($this->item3, $ordered_values[0]);
		$this->assertEquals($this->item5, $ordered_values[1]);
		$this->assertEquals($this->item1, $ordered_values[2]);
		$this->assertEquals($this->item2, $ordered_values[3]);
		$this->assertEquals($this->item4, $ordered_values[4]);



		$map2 = new TPriorityMap();
		$map2->add('startkey', 'startvalue', -1000);
		$map2->add('key5', 'value5', 40);
		$map2->add('endkey', 'endvalue', 1000);
		$map2->mergeWith($this->map);

		$this->assertEquals(7, $map2->getCount());

		$array = $map2->toArray();
		$ordered_keys = array_keys($array);
		$this->assertEquals('startkey', $ordered_keys[0]);
		$this->assertEquals('key3', $ordered_keys[1]);
		$this->assertEquals('key5', $ordered_keys[2]);
		$this->assertEquals('key1', $ordered_keys[3]);
		$this->assertEquals('key2', $ordered_keys[4]);
		$this->assertEquals('key4', $ordered_keys[5]);
		$this->assertEquals('endkey', $ordered_keys[6]);

		$ordered_values = array_values($array);
		$this->assertEquals('startvalue', $ordered_values[0]);
		$this->assertEquals($this->item3, $ordered_values[1]);
		$this->assertEquals($this->item5, $ordered_values[2]);
		$this->assertEquals($this->item1, $ordered_values[3]);
		$this->assertEquals($this->item2, $ordered_values[4]);
		$this->assertEquals($this->item4, $ordered_values[5]);
		$this->assertEquals('endvalue',   $ordered_values[6]);

		$this->assertEquals(1, $map2->priorityAt('key5'));
		$this->assertEquals(1, $map2->priorityOf($this->item5));
	}

	public function testSetPriorityAt() {

		$this->assertEquals(10, $this->map->priorityAt('key2'));
		$this->assertEquals(10, $this->map->setPriorityAt('key2', 1));
		$this->assertEquals(1, $this->map->priorityAt('key2'));
		$this->assertEquals(1, $this->map->setPriorityAt('key2'));
		$this->assertEquals(10, $this->map->priorityAt('key2'));
	}

	public function testToArrayBelowPriority() {
		$this->setUpPriorities();

		$array = $this->map->toArrayBelowPriority(1);
		$this->assertEquals(array('key3'=> $this->item3), $array);
		$this->assertEquals(1, count($array));

		$array = $this->map->toArrayBelowPriority(1, true);
		$this->assertEquals(array('key3'=> $this->item3, 'key5'=> $this->item5), $array);
		$this->assertEquals(2, count($array));

		$array = $this->map->toArrayBelowPriority(2);
		$this->assertEquals(array('key3'=> $this->item3, 'key5'=> $this->item5), $array);
		$this->assertEquals(2, count($array));

		$array = $this->map->toArrayBelowPriority(10);
		$this->assertEquals(array('key3'=> $this->item3, 'key5'=> $this->item5), $array);
		$this->assertEquals(2, count($array));

		$array = $this->map->toArrayBelowPriority(10, true);
		$this->assertEquals(array('key3'=> $this->item3, 'key5'=> $this->item5, 'key1' => $this->item1, 'key2' => $this->item2), $array);
		$this->assertEquals(4, count($array));

		$array = $this->map->toArrayBelowPriority(100);
		$this->assertEquals(array('key3'=> $this->item3, 'key5'=> $this->item5, 'key1' => $this->item1, 'key2' => $this->item2), $array);
		$this->assertEquals(4, count($array));

		$array = $this->map->toArrayBelowPriority(100, true);
		$this->assertEquals(array('key3'=> $this->item3, 'key5'=> $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 'key4' => $this->item4), $array);
		$this->assertEquals(5, count($array));
	}

	public function testToArrayAbovePriority() {
		$this->setUpPriorities();

		$array = $this->map->toArrayAbovePriority(100, false);
		$this->assertEquals(0, count($array));

		$array = $this->map->toArrayAbovePriority(100, true);
		$this->assertEquals(1, count($array));
		$this->assertEquals(array('key4' => $this->item4), $array);

		$array = $this->map->toArrayAbovePriority(11);
		$this->assertEquals(array('key4' => $this->item4), $array);
		$this->assertEquals(1, count($array));

		$array = $this->map->toArrayAbovePriority(10, false);
		$this->assertEquals(array('key4' => $this->item4), $array);
		$this->assertEquals(1, count($array));

		$array = $this->map->toArrayAbovePriority(10);
		$this->assertEquals(array('key1' => $this->item1, 'key2' => $this->item2, 'key4' => $this->item4), $array);
		$this->assertEquals(3, count($array));

		$array = $this->map->toArrayAbovePriority(0);
		$this->assertEquals(array('key3' => $this->item3, 'key5' => $this->item5, 'key1' => $this->item1, 'key2' => $this->item2, 'key4' => $this->item4), $array);
		$this->assertEquals(5, count($array));
	}



}

