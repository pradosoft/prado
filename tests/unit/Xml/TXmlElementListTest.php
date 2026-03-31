<?php

/**
NOTE TO AGENTS:
- Do not instance a "new TXmlElementList" with a TXmlElement its parent (in parameter 1).
- A TXmlElementList is never created outside the context of TXmlElement.
- A "new TXmlElementList($parent)" does not set the parents Elements list. TXmlElement is not designed to accept external TXmlElementList.
- The only valid context for TXmlElementList is as the result of TXmlElement::getElements() or equivalent.
- TXmlElement::getElements() must be used to properly access TXmlElementList.
*/

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Xml\TXmlElement;
use Prado\Xml\TXmlElementList;

class TXmlElementListTest extends PHPUnit\Framework\TestCase
{
	protected function getPrivatePropertyValue($object, $property)
	{
		$reflectionClass = new ReflectionClass($object);
		$reflectionProperty = $reflectionClass->getProperty($property);
		$reflectionProperty->setAccessible(true);
		return $reflectionProperty->getValue($object);
	}

	public function testConstruct()
	{
		$element = new TXmlElement('tag');
		$list = new TXmlElementList($element);
		$result = $this->getPrivatePropertyValue($list, '_o');
		self::assertEquals($element, $result);
	}

	public function testInsertAt()
	{
		$element = new TXmlElement('tag');
		$list = new TXmlElementList($element);
		try {
			$list->insertAt(0, 'ABadElement');
			self::fail('Expected TInvalidDataTypeException not thrown');
		} catch (TInvalidDataTypeException $e) {
		}
		$newElement = new TXmlElement('newTag');
		$list->insertAt(0, $newElement);
		self::assertEquals($newElement, $list->itemAt(0));
	}
	
	public function testInsertAt_SameList()
	{
		$element = new TXmlElement('tag');
		$list = $element->getElements();
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$child3 = new TXmlElement('child3');
		$child4 = new TXmlElement('child4');
		$list->add($child1);
		$list->add($child2);
		$list->add($child3);
		$list->add($child4);
		$list->insertAt(3, $child2);
		
		self::assertEquals($child3, $list[1]);
		self::assertEquals($child2, $list[2]);
		
		$list->insertAt(1, $child2);
		
		self::assertEquals($child2, $list[1]);
		self::assertEquals($child3, $list[2]);
	}
	
	public function testInsertAt_DifferentList()
	{
		$element = new TXmlElement('tag');
		$list = $element->getElements();
		$child = new TXmlElement('child');
		$list->add($child);
		
		$element2 = new TXmlElement('tag');
		$list2 = $element2->getElements();
		$list2->add($child);
		
		// check
		self::assertEquals(0, $list->getCount());
		self::assertEquals(1, $list2->getCount());
		self::assertEquals($child, $list2->itemAt(0));
	}

	public function testRemoveAt()
	{
		$element = new TXmlElement('tag');
		$list = new TXmlElementList($element);
		$newElement = new TXmlElement('newTag');
		$list->insertAt(0, $newElement);
		self::assertEquals($newElement, $list->removeAt(0));
	}
	
	/**
	 * Test edge cases with empty and invalid elements
	 */
	public function testEdgeCases()
	{
		$element = new TXmlElement('parent');
		$list = new TXmlElementList($element);
		
		// Test with null element
		try {
			$list->insertAt(0, null);
			self::fail('Expected TInvalidDataTypeException not thrown for null element');
		} catch (TInvalidDataTypeException $e) {
		}
		
		// Test with non-element object
		try {
			$list->insertAt(0, 'invalid');
			self::fail('Expected TInvalidDataTypeException not thrown for string element');
		} catch (TInvalidDataTypeException $e) {
		}
		
		// Test with negative index for insertAt
		$child = new TXmlElement('child');
		try {
			$list->insertAt(-1, $child);
			self::fail('Expected exception for negative index');
		} catch (Exception $e) {
			// This should throw an exception or handle gracefully
		}
		
		// Test with out of bounds index for insertAt
		try {
			$list->insertAt(5, $child);
			self::fail('Expected exception for out of bounds index');
		} catch (Exception $e) {
			// This should also throw
		}
		
		// Test with element that is already in another list
		$child2 = new TXmlElement('child2');
		$list->add($child);
		$list->add($child2);
		
		// Test valid operations
		self::assertEquals(2, $list->getCount());
		self::assertEquals($child, $list->itemAt(0));
		self::assertEquals($child2, $list->itemAt(1));
		
		// Test removeAt with invalid indices
		try {
			$list->removeAt(-1);
			self::fail('Expected exception for negative index in removeAt');
		} catch (Exception $e) {
		}
		
		try {
			$list->removeAt(10);
			self::fail('Expected exception for out of bounds index in removeAt');
		} catch (Exception $e) {
		}
	}
	
	/**
	 * Test parent/child relationship handling in list
	 */
	public function testParentChildRelationships()
	{
		$parent = new TXmlElement('parent');
		$list = new TXmlElementList($parent);
		
		// Create elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		
		// Add to list
		$list->add($child1);
		$list->add($child2);
		
		// Verify parent-child relationships
		self::assertEquals($parent, $child1->getParent());
		self::assertEquals($parent, $child2->getParent());
		
		// Test removing from list
		$removed = $list->removeAt(0);
		// Parent should be null after removal from list
		self::assertNull($child1->getParent()); // The child should no longer reference its parent after being removed from list
	}
	
	/**
	 * Test with large number of elements
	 */
	public function testLargeElementList()
	{
		$parent = new TXmlElement('parent');
		$list = new TXmlElementList($parent);
		
		// Add many elements
		$elements = [];
		for ($i = 0; $i < 100; $i++) {
			$element = new TXmlElement("child$i");
			$elements[] = $element;
			$list->add($element);
		}
		
		self::assertEquals(100, $list->getCount());
		
		// Test accessing elements
		for ($i = 0; $i < 100; $i++) {
			$retrieved = $list->itemAt($i);
			self::assertEquals("child$i", $retrieved->getTagName());
		}
		
		// Test removing elements
		$removed = $list->removeAt(50);
		self::assertEquals('child50', $removed->getTagName());
		self::assertEquals(99, $list->getCount());
	}
	
	/**
	 * Test when the parent is null
	 * @note While not the most useful test, this does check the TXmlElementList null parent for expected behavior.
	 */
	public function testNullParent()
	{
		$list1 = new TXmlElementList(null);
		
		// Test with null parent, should work normally
		$child = new TXmlElement('child');
		$list1->add($child);
		
		self::assertEquals(1, $list1->getCount());
		self::assertEquals($child, $list1->itemAt(0));
		
		// Test removing elements
		$removed = $list1->removeAt(0);
		self::assertEquals($child, $removed);
		self::assertEquals(0, $list1->getCount());
	}
	
	/**
	 * Test complex parent/child relationship scenarios
	 */
	public function testComplexParentChildRelationships()
	{
		// Create parent elements
		$parent = new TXmlElement('parent');
		$list = $parent->Elements;
		
		// Create child elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$child3 = new TXmlElement('child3');
		
		// Add children to list
		$list->add($child1);
		$list->add($child2);
		$list->add($child3);
		
		// Test that children have proper parent references
		self::assertEquals($parent, $child1->getParent());
		self::assertEquals($parent, $child2->getParent());
		self::assertEquals($parent, $child3->getParent());
		
		// Test that elements are in correct order
		self::assertEquals($child1, $list->itemAt(0));
		self::assertEquals($child2, $list->itemAt(1));
		self::assertEquals($child3, $list->itemAt(2));
		
		// Test inserting at beginning
		$child0 = new TXmlElement('child0');
		$list->insertAt(0, $child0);
		
		self::assertEquals($child0, $list->itemAt(0));
		self::assertEquals($child1, $list->itemAt(1));
		self::assertEquals($child2, $list->itemAt(2));
		self::assertEquals($child3, $list->itemAt(3));
		
		// Test inserting in middle
		$child15 = new TXmlElement('child15');
		$list->insertAt(2, $child15);
		
		self::assertEquals($child0, $list->itemAt(0));
		self::assertEquals($child1, $list->itemAt(1));
		self::assertEquals($child15, $list->itemAt(2));
		self::assertEquals($child2, $list->itemAt(3));
		self::assertEquals($child3, $list->itemAt(4));
		
		// Test removing middle element
		$removed = $list->removeAt(2);
		self::assertEquals($child15, $removed);
		
		// Check that the array shifted properly
		self::assertEquals($child1, $list->itemAt(1));
		self::assertEquals($child2, $list->itemAt(2));
		self::assertEquals($child3, $list->itemAt(3));
		
		// Test removeAt with invalid index
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$list->removeAt(10);
	}
	
	/**
	 * Test that elements can be removed properly and parent references are updated
	 */
	public function testRemoveAtParentReference()
	{
		$parent = new TXmlElement('parent');
		$list = new TXmlElementList($parent);
		
		$child = new TXmlElement('child');
		$list->add($child);
		self::assertEquals($parent, $child->getParent());
		
		// Remove the child
		$removed = $list->removeAt(0);
		self::assertEquals($child, $removed);
		self::assertNull($child->getParent()); // Parent should be null after removing from list
	}
	
	/**
	 * Test insertAt with invalid data types
	 */
	public function testInsertAtInvalidData()
	{
		$parent = new TXmlElement('parent');
		$list = new TXmlElementList($parent);
		
		// Test with invalid types
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		$list->insertAt(0, new stdClass());
	}
}
