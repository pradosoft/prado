<?php

use Prado\Collections\TMap;
use Prado\Xml\TXmlElement;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;

class TXmlElementTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	public function testConstruct()
	{
		$element = new TXmlElement('tag');
		self::assertEquals('tag', $element->getTagName());
	}

	public function testSetParent()
	{
		$parent = new TXmlElement('parent');
		$child = new TXmlElement('child');
		$child->setParent($parent);
		self::assertEquals($parent, $child->getParent());
	}

	public function testSetTagName()
	{
		$element = new TXmlElement('tag');
		$element->setTagName('newtag');
		self::assertEquals('newtag', $element->getTagName());
	}

	public function testSetValue()
	{
		$element = new TXmlElement('tag');
		$element->setValue('value');
		self::assertEquals('value', $element->getValue());
	}

	public function testHasElement()
	{
		$element = new TXmlElement('first');
		self::assertEquals(false, $element->getHasElement());
		$element->Elements[] = new TXmlElement('second');
		self::assertEquals(true, $element->getHasElement());
	}

	public function testSetAttribute()
	{
		$element = new TXmlElement('tag');
		self::assertEquals(null, $element->getAttribute('key'));
		$element->setAttribute('key', 'value');
		self::assertEquals('value', $element->getAttribute('key'));
	}

	public function testRemoveAttribute()
	{
		$element = new TXmlElement('tag');
		$element->setAttribute('key', 'value');
		self::assertEquals('value', $element->getAttribute('key'));
		$element->removeAttribute('key');
		self::assertEquals(null, $element->getAttribute('key'));
	}
	
	public function testHasAttribute()
	{
		$element = new TXmlElement('tag');
		self::assertEquals(false, $element->hasAttribute);
		self::assertEquals(false, $element->getHasAttribute());
		$element->setAttribute('key', 'value');
		self::assertEquals(true, $element->hasAttribute);
		self::assertEquals(true, $element->getHasAttribute());
	}

	public function testHasAttribute_Param()
	{
		$element = new TXmlElement('tag');
		self::assertEquals(false, $element->hasAttribute('key'));
		$element->setAttribute('key', 'value');
		self::assertEquals(true, $element->hasAttribute('key'));
	}

	public function testGetElementByTagName()
	{
		$element = new TXmlElement('tag');
		self::assertEquals(null, $element->getElementByTagName('first'));
		$element->Elements[] = new TXmlElement('first');
		
		$first = $element->getElementByTagName('first');
		
		self::assertInstanceOf(TXmlElement::class, $first);
		self::assertEquals('first', $first->getTagName());
		
		self::assertNull($element->getElementByTagName('second'));
	}

	public function testGetElementsByTagName()
	{
		$element = new TXmlElement('tag');
		$element->Elements[] = new TXmlElement('tag');
		$element->Elements[] = new TXmlElement('tag');
		self::assertEquals(2, count($element->getElementsByTagName('tag')));
	}
	
	/**
	 * Test different search modes (element by tag name)
	 */
	public function testGetElementsByTagNameSearchModes()
	{
		{	// test xml basics, children of non-matching element
			$element = new TXmlElement('root');
			
			// Create nested structure properly to test the difference
			$parent = new TXmlElement('parent');
			$child1 = new TXmlElement('child');
			$child1->setAttribute('id', '1');
			$child2 = new TXmlElement('child');
			$child2->setAttribute('id', '3');
			
			$inner = new TXmlElement('inner');
			$inner->setAttribute('id', '2');
			$innerChild1 = new TXmlElement('child');
			$innerChild1->setAttribute('id', '4');
			$innerChild2 = new TXmlElement('child');
			$innerChild2->setAttribute('id', '5');
			
			$inner->getElements()->add($innerChild1);
			$inner->getElements()->add($innerChild2);
			$parent->getElements()->add($child1);
			$parent->getElements()->add($inner);
			$parent->getElements()->add($child2);
			$element->getElements()->add($parent);
			
			// Test default search (SEARCH_ELEMENT)
			$results = $element->getElementsByTagName('child');
			// This should find all direct child 'child' elements, not recursive
			self::assertEquals(0, count($results)); // Only direct child elements of parent parent
				
			$results = $parent->getElementsByTagName('child');
			// This should find all direct child 'child' elements, not recursive
			self::assertEquals(2, count($results)); // Only direct child elements of parent parent
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			
			
			// Test SEARCH_DEPTH_FIRST - go deep first
			$results = $element->getElementsByTagName('child', TXmlElement::SEARCH_DEPTH_FIRST);
			// Should also find direct children plus nested ones
			self::assertEquals(4, count($results)); // All 'child' elements, direct + nested
			self::assertEquals($child1, $results[0]);
			self::assertEquals($innerChild1, $results[1]);
			self::assertEquals($innerChild2, $results[2]);
			self::assertEquals($child2, $results[3]);
			
			// Test SEARCH_BREADTH_FIRST - go breadth first (but this is implemented differently than expected)
			$results = $element->getElementsByTagName('child', TXmlElement::SEARCH_BREADTH_FIRST);
			// Should also find all 'child' elements in breadth order
			self::assertEquals(4, count($results)); // All 'child' elements, direct + nested
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			self::assertEquals($innerChild1, $results[2]);
			self::assertEquals($innerChild2, $results[3]);
		}
	
		{	// test xml basics, children of matching element
			$element = new TXmlElement('root');
			
			// Create nested structure properly to test the difference
			$parent = new TXmlElement('parent');
			$child1 = new TXmlElement('child');
			$child1->setAttribute('id', '1');
			$child3 = new TXmlElement('child');
			$child3->setAttribute('id', '3');
			
			$child2 = new TXmlElement('child');
			$child2->setAttribute('id', '2');
			$innerChild1 = new TXmlElement('child');
			$innerChild1->setAttribute('id', '4');
			$innerChild2 = new TXmlElement('child');
			$innerChild2->setAttribute('id', '5');
			
			$child2->getElements()->add($innerChild1);
			$child2->getElements()->add($innerChild2);
			$parent->getElements()->add($child1);
			$parent->getElements()->add($child2);
			$parent->getElements()->add($child3);
			$element->getElements()->add($parent);
			
			// Test default search (SEARCH_ELEMENT)
			$results = $element->getElementsByTagName('child');
			// This should find all direct child 'child' elements, not recursive
			self::assertEquals(0, count($results)); // Only direct child elements of parent parent
				
			$results = $parent->getElementsByTagName('child');
			// This should find all direct child 'child' elements, not recursive
			self::assertEquals(3, count($results)); // Only direct child elements of parent parent
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			self::assertEquals($child3, $results[2]);
			
			
			// Test SEARCH_DEPTH_FIRST - go deep first
			$results = $element->getElementsByTagName('child', TXmlElement::SEARCH_DEPTH_FIRST);
			// Should also find direct children plus nested ones
			self::assertEquals(5, count($results)); // All 'child' elements, direct + nested
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			self::assertEquals($innerChild1, $results[2]);
			self::assertEquals($innerChild2, $results[3]);
			self::assertEquals($child3, $results[4]);
			
			// Test SEARCH_BREADTH_FIRST - go breadth first (but this is implemented differently than expected)
			$results = $element->getElementsByTagName('child', TXmlElement::SEARCH_BREADTH_FIRST);
			// Should also find all 'child' elements in breadth order
			self::assertEquals(5, count($results)); // All 'child' elements, direct + nested
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			self::assertEquals($child3, $results[2]);
			self::assertEquals($innerChild1, $results[3]);
			self::assertEquals($innerChild2, $results[4]);
		}
		
		{	// test xml second level search
			// Test with simpler structure to demonstrate different modes
			$simple = new TXmlElement('root');
			$level1child = new TXmlElement('level1');
			$level2child = new TXmlElement('level2');
			$level3child = new TXmlElement('level3');
			$level3child->setAttribute('type', 'test');
			
			$level2child->getElements()->add($level3child);
			$level1child->getElements()->add($level2child);
			$simple->getElements()->add($level1child);
			
			// Depth first should find level1, level2 and level3
			$elementResults = $simple->getElementsByTagName('level2', TXmlElement::SEARCH_ELEMENT);
			self::assertEquals(0, count($elementResults));
				
			// Depth first should find level1, level2 and level3
			$depthResults = $simple->getElementsByTagName('level2', TXmlElement::SEARCH_DEPTH_FIRST);
			// This will still find direct child and nested child
			self::assertEquals(1, count($depthResults));
			
			$breadthResults = $simple->getElementsByTagName('level2', TXmlElement::SEARCH_BREADTH_FIRST);
			self::assertEquals(1, count($breadthResults));
		}
	}
	
	/**
	 * Test different search modes (element by attribute)
	 */
	public function testGetElementsByAttributeSearchModes()
	{
		{	// xml element search, non-matching element
			$element = new TXmlElement('root');
			
			// Create nested structure with attributes
			$parent = new TXmlElement('parent');
			$child1 = new TXmlElement('child');
			$child1->setAttribute('id', '1');
			$child1->setAttribute('type', 'test');
			$child2 = new TXmlElement('child');
			$child2->setAttribute('id', '2');
			$child2->setAttribute('type', 'test');
			
			$inner = new TXmlElement('inner');
			$innerChild1 = new TXmlElement('child');
			$innerChild1->setAttribute('id', '3');
			$innerChild1->setAttribute('type', 'test');
			$innerChild2 = new TXmlElement('child');
			$innerChild2->setAttribute('id', '4');
			$innerChild2->setAttribute('type', 'test');
			
			$inner->getElements()->add($innerChild1);
			$inner->getElements()->add($innerChild2);
			$parent->getElements()->add($child1);
			$parent->getElements()->add($inner);
			$parent->getElements()->add($child2);
			$element->getElements()->add($parent);
			
			// Test default search with attribute
			$results = $element->getElementsByAttribute('type', 'test');
			self::assertEquals(0, count($results));
			
			$results = $parent->getElementsByAttribute('type', 'test');
			self::assertEquals(2, count($results));
			
			// Test SEARCH_DEPTH_FIRST
			$results = $element->getElementsByAttribute('type', 'test', TXmlElement::SEARCH_DEPTH_FIRST);
			self::assertEquals(4, count($results));
			self::assertEquals($child1, $results[0]);
			self::assertEquals($innerChild1, $results[1]);
			self::assertEquals($innerChild2, $results[2]);
			self::assertEquals($child2, $results[3]);
			
			// Test SEARCH_BREADTH_FIRST
			$results = $element->getElementsByAttribute('type', 'test', TXmlElement::SEARCH_BREADTH_FIRST);
			self::assertEquals(4, count($results));
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			self::assertEquals($innerChild1, $results[2]);
			self::assertEquals($innerChild2, $results[3]);
			
			// Test with value matching, Depth First
			$results = $element->getElementsByAttribute('id', '3', TXmlElement::SEARCH_DEPTH_FIRST);
			self::assertEquals(1, count($results));
			
			// Test with value matching, Breadth First
			$results = $element->getElementsByAttribute('id', '3', TXmlElement::SEARCH_BREADTH_FIRST);
			self::assertEquals(1, count($results));
		}
		
		{	// xml element search, matching element
			$element = new TXmlElement('root');
			
			// Create nested structure with attributes
			$parent = new TXmlElement('parent');
			$child1 = new TXmlElement('child');
			$child1->setAttribute('id', '1');
			$child1->setAttribute('type', 'test');
			$child3 = new TXmlElement('child');
			$child3->setAttribute('id', '2');
			$child3->setAttribute('type', 'test');
			
			$child2 = new TXmlElement('child');
			$child2->setAttribute('id', '5');
			$child2->setAttribute('type', 'test');
			$innerChild1 = new TXmlElement('child');
			$innerChild1->setAttribute('id', '3');
			$innerChild1->setAttribute('type', 'test');
			$innerChild2 = new TXmlElement('child');
			$innerChild2->setAttribute('id', '4');
			$innerChild2->setAttribute('type', 'test');
			
			$child2->getElements()->add($innerChild1);
			$child2->getElements()->add($innerChild2);
			$parent->getElements()->add($child1);
			$parent->getElements()->add($child2);
			$parent->getElements()->add($child3);
			$element->getElements()->add($parent);
			
			// Test default search with attribute
			$results = $element->getElementsByAttribute('type', 'test');
			self::assertEquals(0, count($results));
			
			$results = $parent->getElementsByAttribute('type', 'test');
			self::assertEquals(3, count($results));
			
			// Test SEARCH_DEPTH_FIRST
			$results = $element->getElementsByAttribute('type', 'test', TXmlElement::SEARCH_DEPTH_FIRST);
			self::assertEquals(5, count($results));
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			self::assertEquals($innerChild1, $results[2]);
			self::assertEquals($innerChild2, $results[3]);
			self::assertEquals($child3, $results[4]);
			
			// Test SEARCH_BREADTH_FIRST
			$results = $element->getElementsByAttribute('type', 'test', TXmlElement::SEARCH_BREADTH_FIRST);
			self::assertEquals(5, count($results));
			self::assertEquals($child1, $results[0]);
			self::assertEquals($child2, $results[1]);
			self::assertEquals($child3, $results[2]);
			self::assertEquals($innerChild1, $results[3]);
			self::assertEquals($innerChild2, $results[4]);
			
			// Test with value matching, Depth First
			$results = $element->getElementsByAttribute('id', '3', TXmlElement::SEARCH_DEPTH_FIRST);
			self::assertEquals(1, count($results));
			
			// Test with value matching, Breadth First
			$results = $element->getElementsByAttribute('id', '3', TXmlElement::SEARCH_BREADTH_FIRST);
			self::assertEquals(1, count($results));
		}
	}
	
	/**
	 * Test getElementAtIndex edge cases
	 */
	public function testGetElementAtIndexEdgeCases()
	{
		$element = new TXmlElement('root');
		
		// Test with index 0 on empty elements list
		self::assertNull($element->getElementAtIndex(0));
		self::assertNull($element->getElementAtIndex(-1));
		self::assertNull($element->getElementAtIndex(1));
		
		// Add some elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$element->getElements()->add($child1);
		$element->getElements()->add($child2);
		
		// Test valid indices
		self::assertEquals($child1, $element->getElementAtIndex(0));
		self::assertEquals($child2, $element->getElementAtIndex(1));
		
		// Test invalid indices - should be out of bounds
		self::assertNull($element->getElementAtIndex(-1)); // negative index
		self::assertNull($element->getElementAtIndex(2)); // out of bounds
		self::assertNull($element->getElementAtIndex(100)); // large out of bounds
	}

	public function testToString()
	{
		$element = new TXmlElement('tag');
		self::assertEquals('<tag />', (string) $element);
		$element->setAttribute('key', 'value');
		self::assertEquals('<tag key="value" />', (string) $element);
		$element->setValue('value');
		self::assertEquals('<tag key="value">value</tag>', (string) $element);
	}

	public function testXPath()
	{
		$xml = new TXmlElement('root');
		$child1 = new TXmlElement('item');
		$child1->setAttribute('id', '1');
		$child1->setValue('first');
		
		$child2 = new TXmlElement('item');
		$child2->setAttribute('id', '2');
		$child2->setValue('second');
		
		$xml->getElements()->add($child1);
		$xml->getElements()->add($child2);
		
		// Test XPath expression
		$results = $xml->xpath("//item[@id='1']");
		self::assertEquals(1, count($results));
		self::assertEquals('1', $results->itemAt(0)->getAttribute('id'));
		self::assertEquals('first', $results->itemAt(0)->getValue());
	}

	public function testNavigationMethods()
	{
		$parent = new TXmlElement('parent');
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$child3 = new TXmlElement('child3');
		
		$parent->getElements()->add($child1);
		$parent->getElements()->add($child2);
		$parent->getElements()->add($child3);
		
		// Test first child element
		self::assertEquals($child1, $parent->getFirstElementChild());
		self::assertEquals(null, $child1->getFirstElementChild());
		
		// Test last child element
		self::assertEquals($child3, $parent->getLastElementChild());
		self::assertEquals(null, $child1->getLastELementChild());
		
		// Test next sibling
		self::assertEquals($child2, $child1->getNextElementSibling());
		self::assertEquals(null, $child3->getNextElementSibling());
		
		// Test previous sibling
		self::assertEquals($child1, $child2->getPreviousElementSibling());
		self::assertEquals(null, $child1->getPreviousElementSibling());
		
		// Test parent element
		self::assertEquals(null, $parent->getParent());
		self::assertEquals($parent, $child1->getParent());
	}

	public function testClone()
	{
		$parent = new TXmlElement('parent');
		
		$original = new TXmlElement('tag');
		$parent[] = $original;
		$original->setAttribute('key', 'value');
		$original->setValue('test value');
		
		$child = new TXmlElement('child');
		$child->setAttribute('child-key', 'child-value');
		$original->getElements()->add($child);
		
		// Clone the element
		$cloned = clone $original;
		
		self::assertNull($cloned->getParent());
		
		// Check that cloned element has same properties
		self::assertEquals('tag', $cloned->getTagName());
		self::assertEquals('test value', $cloned->getValue());
		self::assertEquals('value', $cloned->getAttribute('key'));
		
		// Check that cloned element has same child
		self::assertEquals(1, count($cloned->getElements()));
		self::assertEquals('child', $cloned->getElements()->itemAt(0)->getTagName());
		self::assertEquals('child-value', $cloned->getElements()->itemAt(0)->getAttribute('child-key'));
		
		// Check that element lists are different objects (not references to the same object)
		self::assertNotSame($original->getElements(), $cloned->getElements());
	}

	/**
	 * Test that TXmlElement::__clone calls parent::__clone
	 * This test verifies that when an element with behaviors is cloned,
	 * the behavior cloning is properly performed by parent class
	 */
	public function testCloneCallsParentClone()
	{
		// Create a component with a behavior that tracks cloning
		$original = new TXmlElement('test');
		
		// Add a simple behavior to ensure we test the behavior cloning logic
		// We create a temporary behavior class to test behavior cloning
		$behavior = new class extends \Prado\Util\TBehavior {
			public $cloned = false;
			public function getClone() {
				return $this->cloned;
			}
			public function dyClone() {
				$this->cloned = true;
			}
		};
		
		// This test needs to make sure behaviors get cloned correctly  
		$original->attachBehavior('testBehavior', $behavior);
		
		// Clone the element
		$cloned = clone $original;
		
		// Check that behaviors were properly attached to the clone
		$behaviors = $cloned->getBehaviors();
		self::assertNotEmpty($behaviors);
		self::assertNotEquals($behavior, $cloned->TestBehavior);
		self::assertTrue($cloned->getClone());
		
		// Check that parent::clone was called by verifying behavior's state
		// This doesn't directly test the clone behavior itself, but we can verify
		// that behavior state management works properly
		self::assertEquals('test', $cloned->getTagName());
	}


	/**
	 * Test edge cases for element properties and methods
	 */
	public function testEdgeCaseProperties()
	{
		$element = new TXmlElement('tag');
		
		// Test with empty string
		try {
			$element->setTagName('');
			self::fail('Expected TInvalidDataValueException to be thrown');
		} catch(TInvalidDataValueException $e) {
		}
		
		// Test with zero string
		$element->setTagName('0');
		self::assertEquals('0', $element->getTagName());
		
		// Test with zero int
		$element->setTagName(0);
		self::assertEquals('0', $element->getTagName());
		
		// Test with special characters in tag name
		$element->setTagName('tag-with-special-chars_123');
		self::assertEquals('tag-with-special-chars_123', $element->getTagName());
		
		// Test with numeric values
		$element->setAttribute('num', 42);
		self::assertEquals('42', $element->getAttribute('num'));
		
		// Test with boolean values
		$element->setAttribute('bool', true);
		self::assertEquals('true', $element->getAttribute('bool'));
		
		$element->setAttribute('bool_false', false);
		self::assertEquals('false', $element->getAttribute('bool_false'));
		
		// Skip array test - causes issues with PHP version
		// Test with array values - will be converted to string 
		// which may cause warnings in some PHP configurations
	}
	
	/**
	 * Test element with large content and nested structure
	 */
	public function testLargeContentAndNestedStructure()
	{
		$element = new TXmlElement('root');
		
		// Add many nested elements
		for ($i = 0; $i < 50; $i++) {
			$child = new TXmlElement("child$i");
			$child->setValue("value$i");
			$element->getElements()->add($child);
		}
		
		self::assertEquals(50, $element->getElements()->getCount());
		
		// Test element access
		$child = $element->getElements()->itemAt(0);
		self::assertEquals('child0', $child->getTagName());
		self::assertEquals('value0', $child->getValue());
		
		// Test with large value
		$largeValue = str_repeat("A", 10000);
		$element->setValue($largeValue);
		self::assertEquals($largeValue, $element->getValue());
	}
	
	/**
	 * Test attribute edge cases
	 */
	public function testAttributeEdgeCases()
	{
		$element = new TXmlElement('tag');
		$attributeCount = 0;
		
		// Test attribute with null value
		$element->setAttribute('null_attr', null);
		self::assertEquals('', $element->getAttribute('null_attr'));
		$attributeCount++;
		
		// Test attribute with special characters
		$element->setAttribute('special', 'value with "quotes" and <tags>');
		self::assertEquals('value with "quotes" and <tags>', $element->getAttribute('special'));
		$attributeCount++;
		
		// Test attribute with ampersand
		$element->setAttribute('amp', 'a & b & c');
		self::assertEquals('a & b & c', $element->getAttribute('amp'));
		$attributeCount++;
		
		// Test many attributes
		for ($i = 0; $i < 20; $i++) {
			$element->setAttribute("attr$i", "value$i");
			$attributeCount++;
		}
		
		self::assertEquals($attributeCount, count($element->getAttributes()));
		
		// Test removing non-existent attribute
		$element->removeAttribute('nonexistent');
		self::assertEquals(null, $element->getAttribute('nonexistent'));
		
		// Test removing attribute that exists
		$element->removeAttribute('attr5');
		self::assertEquals(null, $element->getAttribute('attr5'));
	}
	
	/**
	 * Test count functionality of TXmlElement
	 */
	public function testCount()
	{
		$element = new TXmlElement('parent');
		
		// Test with no elements
		self::assertEquals(0, count($element));
		self::assertEquals(0, $element->getCount());
		
		// Add some elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$child3 = new TXmlElement('child3');
		
		$element->getElements()->add($child1);
		$element->getElements()->add($child2);
		$element->getElements()->add($child3);
		
		// Test count methods
		self::assertEquals(3, count($element));
		self::assertEquals(3, $element->getCount());
		
		// Test with empty elements list (but not null)
		$emptyElement = new TXmlElement('empty');
		// Make sure the elements list is created
		$elements = $emptyElement->getElements();
		self::assertEquals(0, count($emptyElement));
		self::assertEquals(0, $emptyElement->getCount());
	}
	
	/**
	 * Test offset operations of TXmlElement
	 */
	public function testOffsetOperations()
	{
		$element = new TXmlElement('parent');
		
		// Test offset operations on empty element
		self::assertFalse(isset($element[0]));
		self::assertFalse(isset($element[-1]));
		self::assertFalse(isset($element[1]));
		
		// Add some elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$child3 = new TXmlElement('child3');
		
		$element[] = $child1;
		$element[] = $child2;
		$element[] = $child3;
		
		// 3 children: child1, child2, child3
		
		// Test offset access
		self::assertFalse(isset($element[-1]));
		self::assertTrue(isset($element[0]));
		self::assertTrue(isset($element[1]));
		self::assertTrue(isset($element[2]));
		self::assertFalse(isset($element[3]));
		
		// Test getting elements via offset
		self::assertEquals($child1, $element[0]);
		self::assertEquals($child2, $element[1]);
		self::assertEquals($child3, $element[2]);
		
		// Test array access setting
		$child4 = new TXmlElement('child4');
		$element[1] = $child4;
		self::assertEquals($child4, $element[1]);
			
		// 3 children: child1, child4, child3
		
		// Test array access unsetting
		unset($element[1]);
		self::assertEquals(2, count($element));
		self::assertTrue(isset($element[1]));
		self::assertEquals($child3, $element[1]);
			
		// 2 children: child1, child3
		
		// Test edge case: setting element at negative index
		$child5 = new TXmlElement('child5');
		try {
			$element[-1] = $child5;
			self::fail('Expected TInvalidDataValueException to be thrown');
		} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
		}
		
		// Test edge case: setting element at out-of-bounds positive index
		$child6 = new TXmlElement('child6');
		try {
			$element[5] = $child6;
			self::fail('Expected TInvalidDataValueException to be thrown');
		} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
		}
	
		// Verify elements count and access
		$elements = $element->getElements();
		self::assertEquals(2, count($elements));
		self::assertEquals(2, count($element));
	}
	
	/**
	 * Test iteration functionality of TXmlElement
	 */
	public function testIteration()
	{
		$element = new TXmlElement('parent');
		
		// Test iteration on empty element
		$counter = 0;
		foreach ($element as $key => $child) {
			$counter++;
		}
		self::assertEquals(0, $counter);
		
		// Add some elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$child3 = new TXmlElement('child3');
		
		$element->getElements()->add($child1);
		$element->getElements()->add($child2);
		$element->getElements()->add($child3);
		
		// Test iteration using foreach
		$counter = 0;
		$elements = [];
		foreach ($element as $key => $child) {
			$elements[$key] = $child;
			$counter++;
		}
		self::assertEquals(3, $counter);
		self::assertEquals(3, count($elements));
		self::assertEquals($child1, $elements[0]);
		self::assertEquals($child2, $elements[1]);
		self::assertEquals($child3, $elements[2]);
		
		// Test iteration with iterator interface
		$iterator = $element->getIterator();
		self::assertInstanceOf(\Iterator::class, $iterator);
		$iteratorElements = [];
		foreach ($iterator as $key => $child) {
			$iteratorElements[$key] = $child;
		}
		self::assertEquals(3, count($iteratorElements));
		self::assertEquals($child1, $iteratorElements[0]);
		self::assertEquals($child2, $iteratorElements[1]);
		self::assertEquals($child3, $iteratorElements[2]);
		
		// Test edge case: iterator with empty elements
		$emptyElement = new TXmlElement('empty');
		$emptyIterator = $emptyElement->getIterator();
		self::assertInstanceOf(\Iterator::class, $emptyIterator);
		$count = 0;
		foreach ($emptyIterator as $item) {
			$count++;
		}
		self::assertEquals(0, $count);
	}
	
	/**
	 * Test child element navigation methods
	 */
	public function testChildElementNavigation()
	{
		$element = new TXmlElement('parent');
		
		// Test with no children
		self::assertEquals(null, $element->getFirstElementChild());
		self::assertEquals(null, $element->getLastElementChild());
		self::assertEquals(0, $element->childElementCount());
		self::assertEquals(false, $element->hasChildNodes());
		
		// Add some elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$child3 = new TXmlElement('child3');
		
		$element->getElements()->add($child1);
		$element->getElements()->add($child2);
		$element->getElements()->add($child3);
		
		// Test child navigation methods
		self::assertEquals($child1, $element->getFirstElementChild());
		self::assertEquals($child3, $element->getLastElementChild());
		self::assertEquals(3, $element->childElementCount());
		self::assertEquals(true, $element->hasChildNodes());
		
		// Test sibling navigation
		self::assertEquals(null, $child1->getPreviousElementSibling());
		self::assertEquals($child2, $child1->getNextElementSibling());
		self::assertEquals($child1, $child2->getPreviousElementSibling());
		self::assertEquals($child3, $child2->getNextElementSibling());
		self::assertEquals($child2, $child3->getPreviousElementSibling());
		self::assertEquals(null, $child3->getNextElementSibling());
	}
	
	/**
	 * Test namespace handling (should properly handle cases without namespaces)
	 */
	public function testNamespaceHandling()
	{
		$element = new TXmlElement('tag');
		
		// Test namespace in string output - this should not add namespaces 
		$element->setValue('test');
		$string = (string)$element;
		self::assertStringNotContainsString('xmlns:', $string);
	}
	
	/**
	 * Test array access and count interfaces
	 */
	public function testArrayAccessInterfaces()
	{
		$element = new TXmlElement('parent');
		
		// Test with no elements
		self::assertEquals(0, count($element));
		self::assertEquals(false, isset($element[0]));
		
		// Add elements
		$child1 = new TXmlElement('child1');
		$child2 = new TXmlElement('child2');
		$element[] = $child1;
		$element[] = $child2;
		
		// Test count
		self::assertEquals(2, count($element));
		
		// Test isset
		self::assertEquals(true, isset($element[0]));
		self::assertEquals(true, isset($element[1]));
		self::assertEquals(false, isset($element[2]));
		
		// Test get
		self::assertEquals($child1, $element[0]);
		self::assertEquals($child2, $element[1]);
		
		// Test set
		$child3 = new TXmlElement('child3');
		$element[1] = $child3;
		self::assertEquals($child3, $element[1]);
		
		// Test unset
		unset($element[1]);
		self::assertEquals(1, count($element));
		self::assertEquals(false, isset($element[1]));
		self::assertEquals($child1, $element[0]);
	}
	
	/**
	 * Test xpath with complex expressions
	 */
	public function testXPathComplex()
	{
		$element = new TXmlElement('root');
		
		// Create some nested structure
		$item1 = new TXmlElement('item');
		$item1->setAttribute('id', '1');
		$item1->setValue('first');
		
		$item2 = new TXmlElement('item');
		$item2->setAttribute('id', '2');
		$item2->setValue('second');
		
		$nested = new TXmlElement('nested');
		$inner = new TXmlElement('inner');
		$inner->setAttribute('type', 'test');
		$nested->getElements()->add($inner);
		
		$element->getElements()->add($item1);
		$element->getElements()->add($item2);
		$element->getElements()->add($nested);
		
		// Test basic xpath
		$results = $element->xpath("//item[@id='1']");
		self::assertEquals(1, count($results));
		self::assertEquals('1', $results->itemAt(0)->getAttribute('id'));
		self::assertEquals('first', $results->itemAt(0)->getValue());
		self::assertEquals($item1, $results->itemAt(0));
		
		// Test complex xpath
		$complexResults = $element->xpath("//nested/inner[@type='test']");
		self::assertEquals(1, count($complexResults));
		self::assertEquals('test', $complexResults->itemAt(0)->getAttribute('type'));
		self::assertEquals($inner, $complexResults->itemAt(0));
		
		// Test XPath with no results
		$noResults = $element->xpath("//item[@id='3']");
		self::assertEquals(0, count($noResults));
	}
	
	/**
	 * Test DOM compatibility methods thoroughly
	 */
	public function testDOMCompatibilityMethods()
	{
		// Test getNodeName
		$element = new TXmlElement('test');
		self::assertEquals('test', $element->getNodeName());
		
		// Test getNodeValue
		$element->setValue('test value');
		self::assertEquals('test value', $element->getNodeValue());
		
		// Test setNodeValue
		$element->setNodeValue('new value');
		self::assertEquals('new value', $element->getNodeValue());
		
		// Test getParentElement
		$parent = new TXmlElement('parent');
		$child = new TXmlElement('child');
		$parent->getElements()->add($child);
		self::assertEquals($parent, $child->getParentElement());
		
		// Test getChildNodes
		$childNodes = $element->getChildNodes();
		self::assertInstanceOf('Prado\Xml\TXmlElementList', $childNodes);
		
		// Test hasChildNodes
		self::assertEquals(false, $element->hasChildNodes());
		$element->getElements()->add(new TXmlElement('child'));
		self::assertEquals(true, $element->hasChildNodes());
		
		// Test getNodeType
		self::assertEquals(XML_ENTITY_NODE, $element->getNodeType());
	}
	
	/**
	 * Test special XML character handling
	 */
	public function testSpecialXMLCharacters()
	{
		$element = new TXmlElement('tag');
		
		// Test with special XML characters
		$element->setValue('text with "quotes" and <tags> and & characters');
		$string = (string)$element;
		self::assertStringContainsString('&quot;quotes&quot;', $string);
		self::assertStringContainsString('&lt;tags&gt;', $string);
		self::assertStringContainsString('&amp;', $string);
		
		// Test with various control characters
		$element->setValue("Value with\r\nline feeds\ttabs");
		$string = (string)$element;
		self::assertStringContainsString('&#xD;', $string);
		self::assertStringContainsString('&#xA;', $string);
		self::assertStringContainsString('&#x9;', $string);
	}
	
	/**
	 * Test recursive operation with large structures for performance
	 */
	public function testRecursiveOperationsLargeStructure()
	{
		$element = new TXmlElement('root');
		
		// Create a deeply nested structure
		$current = $element;
		for ($i = 0; $i < 20; $i++) {
			$child = new TXmlElement("level$i");
			$current->getElements()->add($child);
			$current = $child;
		}
		
		// Test recursive search
		$found = $element->getElementByTagName('level5', TXmlElement::SEARCH_DEPTH_FIRST);
		self::assertNotNull($found);
		self::assertEquals('level5', $found->getTagName());
		
		$found = $element->getElementByTagName('level5', TXmlElement::SEARCH_BREADTH_FIRST);
		self::assertNotNull($found);
		self::assertEquals('level5', $found->getTagName());
	}
}
