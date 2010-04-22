<?php
require_once dirname(__FILE__).'/../phpunit.php';

class PriorityListItem {
	public $data='data';
}
Prado::using('System.Collections.TPriorityList');

/**
 *	All Test cases for the TList are here.  The TPriorityList should act just like a TList when used exactly like a TList
 *
 * The TPriority List should start behaving differently when using the class outside of the standard TList Function calls
 *
 * @package System.Collections
 */
class TPriorityListTest extends PHPUnit_Framework_TestCase {
	
	protected $list;
	protected $item1, $item2, $item3, $item4;
	
	protected $plist;
	protected $pfirst, $pitem1, $pitem2, $pitem3, $pitem4, $pitem5;

  public function setUp() {
    $this->list=new TPriorityList;
    $this->item1=new PriorityListItem;
    $this->item2=new PriorityListItem;
    $this->item3=new PriorityListItem;
    $this->item4=new PriorityListItem;
    $this->list->add($this->item1);
    $this->list->add($this->item2);
    
    // ****  start the setup for non-TList things
    $this->plist=new TPriorityList;
    $this->pfirst=new PriorityListItem;
    $this->pitem1=new PriorityListItem;
    $this->pitem2=new PriorityListItem;
    $this->pitem3=new PriorityListItem;
    $this->pitem4=new PriorityListItem;
    $this->pitem5=new PriorityListItem;
    $this->plist->add($this->pitem1);
    $this->plist->add($this->pitem3, 100);
    $this->plist->add($this->pitem2);
    $this->plist->add($this->pfirst, -10000000);
    // 4 and 5 are not inserted
    // ending setup: pfirst @ -10000000[0], pitem1 @ 10[0], pitem2 @ 10[1], pitem3 @ 100[0]
  }

  public function tearDown() {
    $this->list=null;
    $this->item1=null;
    $this->item2=null;
    $this->item3=null;
    $this->item4=null;
    
    // ****  start the setup for non-TList things
    $this->list=null;
    $this->item1=null;
    $this->item2=null;
    $this->item3=null;
    $this->item4=null;
    $this->item5=null;
  }

	//*****************************************************************
	//*******  start test cases for TList operations
	//*******		TPriorityList should act exactly like a TList if no special functions are used
	
  public function testConstructTList() {
    $a=array(1,2,3);
    $list=new TPriorityList($a);
    $this->assertEquals(3,$list->getCount());
    $list2=new TPriorityList($this->list);
    $this->assertEquals(2,$list2->getCount());
  }

	public function testGetReadOnlyTList() {
		$list = new TPriorityList(null, true);
		self::assertEquals(true, $list->getReadOnly(), 'List is not read-only');
		$list = new TPriorityList(null, false);
		self::assertEquals(false, $list->getReadOnly(), 'List is read-only');
	}

  public function testGetCountTList() {
    $this->assertEquals(2,$this->list->getCount());
    $this->assertEquals(2,$this->list->Count);
  }

  public function testAddTList() {
    $this->assertEquals(2,$this->list->add(null));
    $this->assertEquals(3,$this->list->add($this->item3));
    $this->assertEquals(4,$this->list->getCount());
    $this->assertEquals(3,$this->list->indexOf($this->item3));
  }

  public function testInsertAtTList() {
    $this->assertNull($this->list->insertAt(0,$this->item3));
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

  public function testInsertBeforeTList() {
    try {
      $this->list->insertBefore($this->item4,$this->item3);
      $this->fail('exception not raised when adding item before a non-existant base item');
    } catch(TInvalidDataValueException $e) {
    }
    $this->assertEquals(2,$this->list->getCount());
    $this->assertEquals(0,$this->list->insertBefore($this->item1,$this->item3));
    $this->assertEquals(3,$this->list->getCount());
    $this->assertEquals(0,$this->list->indexOf($this->item3));
    $this->assertEquals(1,$this->list->indexOf($this->item1));
    $this->assertEquals(2,$this->list->indexOf($this->item2));
  }

  public function testInsertAfterTList() {
    try {
      $this->list->insertAfter($this->item4,$this->item3);
      $this->fail('exception not raised when adding item after a non-existant base item');
    } catch(TInvalidDataValueException $e) {
    }
    $this->assertEquals(2,$this->list->getCount());
    $this->assertEquals(2,$this->list->insertAfter($this->item2,$this->item3));
    $this->assertEquals(3,$this->list->getCount());
    $this->assertEquals(0,$this->list->indexOf($this->item1));
    $this->assertEquals(1,$this->list->indexOf($this->item2));
    $this->assertEquals(2,$this->list->indexOf($this->item3));
  }

	public function testCanNotInsertWhenReadOnlyTList() {
		$list = new TPriorityList(array(), true);
		try {
			$list->insertAt(1, 2);
			self::fail('An expected TInvalidOperationException was not raised');
		} catch(TInvalidOperationException $e) {
		}
		try {
			$list->insertAt(0, 2);
			self::fail('An expected TInvalidOperationException was not raised');
		} catch(TInvalidOperationException $e) {
		}
	}

  public function testRemoveTList() {
    $this->assertEquals(0,$this->list->remove($this->item1));
    $this->assertEquals(1,$this->list->getCount());
    $this->assertEquals(-1,$this->list->indexOf($this->item1));
    $this->assertEquals(0,$this->list->indexOf($this->item2));
    try {
      $this->list->remove($this->item1);
      $this->fail('exception not raised when removing nonexisting item');
    } catch(Exception $e) {

    }
  }

  public function testRemoveAtTList() {
    $this->list->add($this->item3);
    $this->assertEquals($this->item2, $this->list->removeAt(1));
    $this->assertEquals(-1,$this->list->indexOf($this->item2));
    $this->assertEquals(1,$this->list->indexOf($this->item3));
    $this->assertEquals(0,$this->list->indexOf($this->item1));
    try {
      $this->list->removeAt(2);
      $this->fail('exception not raised when removing item with invalid index');
    } catch(TInvalidDataValueException $e) {

    }
  }

	public function testCanNotRemoveWhenReadOnlyTList() {
		$list = new TPriorityList(array(1, 2, 3), true);
		try {
			$list->removeAt(2);
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

  public function testClearTList() {
    $this->list->clear();
    $this->assertEquals(0,$this->list->getCount());
    $this->assertEquals(-1,$this->list->indexOf($this->item1));
    $this->assertEquals(-1,$this->list->indexOf($this->item2));
  }

  public function testContainTLists() {
    $this->assertTrue($this->list->contains($this->item1));
    $this->assertTrue($this->list->contains($this->item2));
    $this->assertFalse($this->list->contains($this->item3));
  }

  public function testIndexOfTList() {
    $this->assertEquals(0,$this->list->indexOf($this->item1));
    $this->assertEquals(1,$this->list->indexOf($this->item2));
    $this->assertEquals(-1,$this->list->indexOf($this->item3));
  }

  public function testCopyFromTList() {
    $array=array($this->item3,$this->item1);
    $this->list->copyFrom($array);
    $this->assertTrue(count($array)==2 && $this->list[0]===$this->item3 && $this->list[1]===$this->item1);
    try {
      $this->list->copyFrom($this);
      $this->fail('exception not raised when copying from non-traversable object');
    } catch(TInvalidDataTypeException $e) {

    }
  }

  public function testMergeWithTList() {
    $array=array($this->item3,$this->item1);
    $this->list->mergeWith($array);
    $this->assertTrue($this->list->getCount()==4 && $this->list[0]===$this->item1 && $this->list[3]===$this->item1);
    try {
      $this->list->mergeWith($this);
      $this->fail('exception not raised when copying from non-traversable object');
    } catch(TInvalidDataTypeException $e) {

    }
  }

  public function testToArrayTList() {
    $array=$this->list->toArray();
    $this->assertTrue(count($array)==2 && $array[0]===$this->item1 && $array[1]===$this->item2);
  }

  public function testArrayReadTList() {
    $this->assertTrue($this->list[0]===$this->item1);
    $this->assertTrue($this->list[1]===$this->item2);
    try {
      $a=$this->list[2];
      $this->fail('exception not raised when accessing item with out-of-range index');
    } catch(TInvalidDataValueException $e) {

    }
  }

  public function testGetIteratorTList() {
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

  public function testArrayMiscTList() {
    $this->assertEquals($this->list->Count,count($this->list));
    $this->assertTrue(isset($this->list[1]));
    $this->assertFalse(isset($this->list[2]));
  }

	public function testOffsetSetAddTList() {
		$list = new TPriorityList(array(1, 2, 3));
		$list->offsetSet(null, 4);
		self::assertEquals(array(1, 2, 3, 4), $list->toArray());
	}

	public function testOffsetSetReplaceTList() {
		$list = new TPriorityList(array(1, 2, 3));
		$list->offsetSet(1, 4);
		self::assertEquals(array(1, 4, 3), $list->toArray());
	}
	
	public function testOffsetUnsetTList() {
		$list = new TPriorityList(array(1, 2, 3));
		$list->offsetUnset(1);
		self::assertEquals(array(1, 3), $list->toArray());
	}
	
	//*******  end test cases for TList operations
	//*****************************************************************
	
	
  public function testConstructPriorities() {
    $a=array('a' => 1, '0.5' => 2, 9 => 8);
    
    $list=new TPriorityList($a);
    $this->assertEquals(3,$list->getCount());
    
    $list2=new TPriorityList($this->plist);
    $this->assertEquals(4,$list2->getCount());
    $this->assertEquals(-10000000,$list2->priorityOf($this->pfirst));
    $this->assertEquals(100,$list2->priorityOf($this->pitem3));
    $this->assertEquals(-10000000,$list2->priorityAt(0));
    $this->assertEquals($list2->DefaultPriority,$list2->priorityAt(2));
    $this->assertEquals(100,$list2->priorityAt(3));
  }

  public function testGetCountPriorities() {
    $this->assertEquals(4,$this->plist->getCount());
    $this->assertEquals(4,$this->plist->Count);
  }

  public function testAddPriorities() {
    $this->assertEquals(3,$this->plist->add(null));
    $this->assertEquals(4,$this->plist->add($this->pitem4));
    $this->assertEquals(6,$this->plist->getCount());
    $this->assertEquals(4,$this->plist->indexOf($this->pitem4));
    $this->assertEquals($this->plist->DefaultPriority,$this->plist->priorityAt(4));
    $this->assertEquals(100,$this->plist->priorityAt(5));
  }

  public function testInsertAtPriorities() {
    $this->assertNull($this->plist->insertAt(0,$this->pitem3));
    $this->assertEquals(5,$this->plist->getCount());
    $this->assertEquals(3,$this->plist->indexOf($this->pitem2));
    $this->assertEquals(0,$this->plist->indexOf($this->pitem3));
    $this->assertEquals(1,$this->plist->indexOf($this->pitem1));
    try {
      $this->plist->insertAt(4,$this->item3);
      $this->fail('exception not raised when adding item at an out-of-range index');
    } catch(TInvalidDataValueException $e) {

    }
  }

  public function testInsertBeforePriorities() {
    try {
      $this->plist->insertBefore($this->item4,$this->item3);
      $this->fail('exception not raised when adding item before a non-existant base item');
    } catch(TInvalidDataValueException $e) {
    }
    $this->assertEquals(2,$this->plist->getCount());
    $this->plist->insertBefore($this->item1,$this->item3);
    $this->assertEquals(3,$this->plist->getCount());
    $this->assertEquals(0,$this->plist->indexOf($this->item3));
    $this->assertEquals(1,$this->plist->indexOf($this->item1));
    $this->assertEquals(2,$this->plist->indexOf($this->item2));
  }

  public function testInsertAfterPriorities() {
    try {
      $this->plist->insertAfter($this->item4,$this->item3);
      $this->fail('exception not raised when adding item after a non-existant base item');
    } catch(TInvalidDataValueException $e) {
    }
    $this->assertEquals(2,$this->plist->getCount());
    $this->plist->insertAfter($this->item2,$this->item3);
    $this->assertEquals(3,$this->plist->getCount());
    $this->assertEquals(0,$this->plist->indexOf($this->item1));
    $this->assertEquals(1,$this->plist->indexOf($this->item2));
    $this->assertEquals(2,$this->plist->indexOf($this->item3));
  }

	public function testCanNotInsertWhenReadOnlyPriorities() {
		$list = new TPriorityList(array(), true);
		try {
			$list->insertAt(1, 2);
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

  public function testRemovePriorities() {
    $this->assertEquals(1,$this->plist->remove($this->pitem1));
    $this->assertEquals(3,$this->plist->getCount());
    $this->assertEquals(-1,$this->plist->indexOf($this->pitem1));
    $this->assertEquals(1,$this->plist->indexOf($this->pitem2));
    $this->assertEquals(2,$this->plist->indexOf($this->pitem3));
    try {
      $this->plist->remove($this->item1);
      $this->fail('exception not raised when removing nonexisting item');
    } catch(Exception $e) {

    }
  }

  public function testRemoveAtPriorities() {
    $this->plist->add($this->item3);
    $this->assertEquals($this->item2, $this->plist->removeAt(1));
    $this->assertEquals(-1,$this->plist->indexOf($this->item2));
    $this->assertEquals(1,$this->plist->indexOf($this->item3));
    $this->assertEquals(0,$this->plist->indexOf($this->item1));
    try {
      $this->plist->removeAt(2);
      $this->fail('exception not raised when removing item with invalid index');
    } catch(TInvalidDataValueException $e) {

    }
  }

	public function testCanNotRemoveWhenReadOnlyPriorities() {
		$list = new TPriorityList(array(1, 2, 3), true);
		try {
			$list->removeAt(2);
		} catch(TInvalidOperationException $e) {
			return;
		}
		self::fail('An expected TInvalidOperationException was not raised');
	}

  public function testClearPriorities() {
    $this->plist->clear();
    $this->assertEquals(0,$this->plist->getCount());
    $this->assertEquals(-1,$this->plist->indexOf($this->item1));
    $this->assertEquals(-1,$this->plist->indexOf($this->item2));
  }

  public function testContainsPriorities() {
    $this->assertTrue($this->plist->contains($this->item1));
    $this->assertTrue($this->plist->contains($this->item2));
    $this->assertFalse($this->plist->contains($this->item3));
  }

  public function testIndexOfPriorities() {
    $this->assertEquals(0,$this->plist->indexOf($this->item1));
    $this->assertEquals(1,$this->plist->indexOf($this->item2));
    $this->assertEquals(-1,$this->plist->indexOf($this->item3));
  }

  public function testCopyFromPriorities() {
    $array=array($this->item3,$this->item1);
    $this->plist->copyFrom($array);
    $this->assertTrue(count($array)==2 && $this->plist[0]===$this->item3 && $this->plist[1]===$this->item1);
    try {
      $this->plist->copyFrom($this);
      $this->fail('exception not raised when copying from non-traversable object');
    } catch(TInvalidDataTypeException $e) {

    }
  }

  public function testMergeWithPriorities() {
    $array=array($this->item3,$this->item1);
    $this->plist->mergeWith($array);
    $this->assertTrue($this->plist->getCount()==4 && $this->plist[0]===$this->item1 && $this->plist[3]===$this->item1);
    try {
      $this->plist->mergeWith($this);
      $this->fail('exception not raised when copying from non-traversable object');
    } catch(TInvalidDataTypeException $e) {

    }
  }

  public function testToArrayPriorities() {
    $array=$this->plist->toArray();
    $this->assertTrue(count($array)==2 && $array[0]===$this->item1 && $array[1]===$this->item2);
  }

  public function testArrayReadPriorities() {
    $this->assertTrue($this->plist[0]===$this->item1);
    $this->assertTrue($this->plist[1]===$this->item2);
    try {
      $a=$this->plist[2];
      $this->fail('exception not raised when accessing item with out-of-range index');
    } catch(TInvalidDataValueException $e) {

    }
  }

  public function testGetIteratorPriorities() {
    $n=0;
    $found=0;
    foreach($this->plist as $index=>$item) {
      foreach($this->plist as $a=>$b);	// test of iterator
      $n++;
      if($index===0 && $item===$this->item1)
	$found++;
      if($index===1 && $item===$this->item2)
	$found++;
    }
    $this->assertTrue($n==2 && $found==2);
  }

  public function testArrayMiscPriorities() {
    $this->assertEquals($this->plist->Count,count($this->plist));
    $this->assertTrue(isset($this->plist[1]));
    $this->assertFalse(isset($this->plist[2]));
  }

	public function testOffsetSetAddPriorities() {
		$list = new TPriorityList(array(1, 2, 3));
		$list->offsetSet(null, 4);
		self::assertEquals(array(1, 2, 3, 4), $list->toArray());
	}

	public function testOffsetSetReplacePriorities() {
		$list = new TPriorityList(array(1, 2, 3));
		$list->offsetSet(1, 4);
		self::assertEquals(array(1, 4, 3), $list->toArray());
	}
	
	public function testOffsetUnsetPriorities() {
		$list = new TPriorityList(array(1, 2, 3));
		$list->offsetUnset(1);
		self::assertEquals(array(1, 3), $list->toArray());
	}
	
	
}