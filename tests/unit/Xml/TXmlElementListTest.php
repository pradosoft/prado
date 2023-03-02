<?php

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

	public function testRemoveAt()
	{
		$element = new TXmlElement('tag');
		$list = new TXmlElementList($element);
		$newElement = new TXmlElement('newTag');
		$list->insertAt(0, $newElement);
		self::assertEquals($newElement, $list->removeAt(0));
	}
}
