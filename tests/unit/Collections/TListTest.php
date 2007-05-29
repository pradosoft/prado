<?php
require_once dirname(__FILE__).'/../phpunit.php';

class ListItem {
	public $data='data';
}

/**
 * @package System.Collections
 */
class TListTest extends PHPUnit_Framework_TestCase {
	
	protected $list;
	protected $item1, $item2, $item3;

  public function setUp() {
    $this->list=new TList;
    $this->item1=new ListItem;
    $this->item2=new ListItem;
    $this->item3=new ListItem;
    $this->list->add($this->item1);
    $this->list->add($this->item2);
  }

  public function tearDown() {
    $this->list=null;
    $this->item1=null;
    $this->item2=null;
    $this->item3=null;
  }

  public function testConstruct() {
    $a=array(1,2,3);
    $list=new TList($a);
    $this->assertEquals(3,$list->getCount());
    $list2=new TList($this->list);
    $this->assertEquals(2,$list2->getCount());
  }

	public function testGetReadOnly() {
		$list = new TList(null, true);
		self::assertEquals(true, $list->getReadOnly(), 'List is not read-only');
		$list = new TList(null, false);
		self::assertEquals(false, $list->getReadOnly(), 'List is read-only');
	}

  public function testGetCount() {
    $this->assertEquals(2,$this->list->getCount());
    $this->assertEquals(2,$this->list->Count);
  }

  public function testAdd() {
    $this->list->add(null);
    $this->list->add($this->item3);
    $this->assertEquals(4,$this->list->getCount());
    $this->assertEquals(3,$this->list->indexOf($this->item3));
  }

  public function testInsertAt() {
    $this->list->insertAt(0,$this->item3);
    $this->assertEquals(3,$this->list->getCount());
    $this->assertEquals(2,$this->list->indexOf($this->item2));
    $this->assertEquals(0,$this->list->indexOf($this->item3));
    $this->assertEquals(1,$this->list->indexOf($this->item1));
    try {
      $this->list->insertAt(4,$this->item3);
      $this->fail('exception not raised when adding item at an out-of-range index');
    } catch(TInvalidDataValueException $e) {

    }
  }

	public function testCanNotInsertWhenReadOnly() {
		$list = new TList(array(), true);
		try {
			$list->insertAt(1, 2);
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

  public function testRemove() {
    $this->list->remove($this->item1);
    $this->assertEquals(1,$this->list->getCount());
    $this->assertEquals(-1,$this->list->indexOf($this->item1));
    $this->assertEquals(0,$this->list->indexOf($this->item2));
    try {
      $this->list->remove($this->item1);
      $this->fail('exception not raised when removing nonexisting item');
    } catch(Exception $e) {

    }
  }

  public function testRemoveAt() {
    $this->list->add($this->item3);
    $this->list->removeAt(1);
    $this->assertEquals(-1,$this->list->indexOf($this->item2));
    $this->assertEquals(1,$this->list->indexOf($this->item3));
    $this->assertEquals(0,$this->list->indexOf($this->item1));
    try {
      $this->list->removeAt(2);
      $this->fail('exception not raised when removing item with invalid index');
    } catch(TInvalidDataValueException $e) {

    }
  }

	public function testCanNotRemoveWhenReadOnly() {
		$list = new TList(array(1, 2, 3), true);
		try {
			$list->removeAt(2);
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

  public function testClear() {
    $this->list->clear();
    $this->assertEquals(0,$this->list->getCount());
    $this->assertEquals(-1,$this->list->indexOf($this->item1));
    $this->assertEquals(-1,$this->list->indexOf($this->item2));
  }

  public function testContains() {
    $this->assertTrue($this->list->contains($this->item1));
    $this->assertTrue($this->list->contains($this->item2));
    $this->assertFalse($this->list->contains($this->item3));
  }

  public function testIndexOf() {
    $this->assertEquals(0,$this->list->indexOf($this->item1));
    $this->assertEquals(1,$this->list->indexOf($this->item2));
    $this->assertEquals(-1,$this->list->indexOf($this->item3));
  }

  public function testCopyFrom() {
    $array=array($this->item3,$this->item1);
    $this->list->copyFrom($array);
    $this->assertTrue(count($array)==2 && $this->list[0]===$this->item3 && $this->list[1]===$this->item1);
    try {
      $this->list->copyFrom($this);
      $this->fail('exception not raised when copying from non-traversable object');
    } catch(TInvalidDataTypeException $e) {

    }
  }

  public function testMergeWith() {
    $array=array($this->item3,$this->item1);
    $this->list->mergeWith($array);
    $this->assertTrue($this->list->getCount()==4 && $this->list[0]===$this->item1 && $this->list[3]===$this->item1);
    try {
      $this->list->mergeWith($this);
      $this->fail('exception not raised when copying from non-traversable object');
    } catch(TInvalidDataTypeException $e) {

    }
  }

  public function testToArray() {
    $array=$this->list->toArray();
    $this->assertTrue(count($array)==2 && $array[0]===$this->item1 && $array[1]===$this->item2);
  }

  public function testArrayRead() {
    $this->assertTrue($this->list[0]===$this->item1);
    $this->assertTrue($this->list[1]===$this->item2);
    try {
      $a=$this->list[2];
      $this->fail('exception not raised when accessing item with out-of-range index');
    } catch(TInvalidDataValueException $e) {

    }
  }

  public function testGetIterator() {
    $n=0;
    $found=0;
    foreach($this->list as $index=>$item) {
      foreach($this->list as $a=>$b);	// test of iterator
      $n++;
      if($index===0 && $item===$this->item1)
	$found++;
      if($index===1 && $item===$this->item2)
	$found++;
    }
    $this->assertTrue($n==2 && $found==2);
  }

  public function testArrayMisc() {
    $this->assertEquals($this->list->Count,count($this->list));
    $this->assertTrue(isset($this->list[1]));
    $this->assertFalse(isset($this->list[2]));
  }

	public function testOffsetSetAdd() {
		$list = new TList(array(1, 2, 3));
		$list->offsetSet(null, 4);
		self::assertEquals(array(1, 2, 3, 4), $list->toArray());
	}

	public function testOffsetSetReplace() {
		$list = new TList(array(1, 2, 3));
		$list->offsetSet(1, 4);
		self::assertEquals(array(1, 4, 3), $list->toArray());
	}
	
	public function testOffsetUnset() {
		$list = new TList(array(1, 2, 3));
		$list->offsetUnset(1);
		self::assertEquals(array(1, 3), $list->toArray());
	}
}


?>