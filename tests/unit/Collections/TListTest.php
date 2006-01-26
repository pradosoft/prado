<?php
require_once dirname(__FILE__).'/../phpunit2.php';

class ListItem
{
	public $data='data';
}

class NewList extends TList
{
	private $_canAddItem=true;
	private $_canRemoveItem=true;
	private $_itemAdded=false;
	private $_itemRemoved=false;

	protected function addedItem($item)
	{
		$this->_itemAdded=true;
	}

	protected function removedItem($item)
	{
		$this->_itemRemoved=true;
	}

	protected function canAddItem($item)
	{
		return $this->_canAddItem;
	}

	protected function canRemoveItem($item)
	{
		return $this->_canRemoveItem;
	}

	public function setCanAddItem($value)
	{
		$this->_canAddItem=$value;
	}

	public function setCanRemoveItem($value)
	{
		$this->_canRemoveItem=$value;
	}

	public function isItemAdded()
	{
		return $this->_itemAdded;
	}

	public function isItemRemoved()
	{
		return $this->_itemRemoved;
	}
}

/**
 * @package System.Collections
 */
class TListTest extends PHPUnit2_Framework_TestCase {
  protected $list;
  protected $item1,$item2,$item3;
  
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

  public function testInsert() {
    $this->list->insert(0,$this->item3);
    $this->assertEquals(3,$this->list->getCount());
    $this->assertEquals(2,$this->list->indexOf($this->item2));
    $this->assertEquals(0,$this->list->indexOf($this->item3));
    $this->assertEquals(1,$this->list->indexOf($this->item1));
    try {
      $this->list->insert(4,$this->item3);
      $this->fail('exception not raised when adding item at an out-of-range index');
    } catch(TInvalidDataValueException $e) {
      
    }
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
  
  /*public function testArrayWrite() {
    $this->list[]=$this->item3;
    $this->assertTrue($this->list[2]===$this->item3 && $this->list->getCount()===3);
    $this->list[0]=$this->item3;
    $this->assertTrue($this->list[0]===$this->item3 && $this->list->getCount()===3 && $this->list->indexOf($this->item1)===-1);
    unset($this->list[1]);
    $this->assertTrue($this->list->getCount()===2 && $this->list->indexOf($this->item2)===-1);
    try {
      $this->list[5]=$this->item3;
      $this->fail('exception not raised when setting item at an out-of-range index');
    } catch(TInvalidDataValueException $e) {
      
    }
    try {
      unset($this->list[5]);
      $this->fail('exception not raised when unsetting item at an out-of-range index');
    } catch(TInvalidDataValueException $e) {
      
    }
  }*/
  
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
    $this->assertEquals(1,count($this->list));
    $this->assertTrue(isset($this->list[1]));
    $this->assertFalse(isset($this->list[2]));
  }
  
  public function testDerivedClasses() {
    $newList=new NewList;
    $this->assertFalse($newList->isItemAdded());
    $newList->add($this->item1);
    $this->assertTrue($newList->isItemAdded());
    $newList->add($this->item2);
    
    $newList->setCanAddItem(false);
    try {
      $newList->add($this->item3);
      $this->fail('no exception raised when adding an item that is disallowed');
    } catch(TInvalidOperationException $e) {
      $this->assertEquals(2,$newList->getCount());
    }
    
    $this->assertFalse($newList->isItemRemoved());
    $newList->remove($this->item1);
    $this->assertTrue($newList->isItemRemoved());
    
    $newList->setCanRemoveItem(false);
    try {
      $newList->remove($this->item2);
      $this->fail('no exception raised when removing an item that is disallowed');
    } catch(TInvalidOperationException $e) {
      $this->assertEquals(1,$newList->getCount());
    }
  }
}


?>