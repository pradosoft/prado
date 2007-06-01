<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Xml.TXmlDocument');

/**
 * @package System.Xml
 */
class TXmlElementTest extends PHPUnit_Framework_TestCase {

	public function setUp() {	
	}

	public function testConstruct() {
		$element = new TXmlElement('tag');
		self::assertEquals('tag', $element->getTagName());
	}

	public function testSetParent() {
		$parent = new TXmlElement('parent');
		$child = new TXmlElement('child');
		$child->setParent($parent);
		self::assertEquals($parent, $child->getParent());
	}

	public function testSetTagName() {
		$element = new TXmlElement('tag');
		$element->setTagName('newtag');
		self::assertEquals('newtag', $element->getTagName());
	}

	public function testSetValue() {
		$element = new TXmlElement('tag');
		$element->setValue('value');
		self::assertEquals('value', $element->getValue());
	}

	public function testHasElement() {
		$element = new TXmlElement('first');
		self::assertEquals(false, $element->getHasElement());
		$element->Elements[] = new TXmlElement('second');
		self::assertEquals(true, $element->getHasElement());
	}

	public function testHasAttribute() {
		$element = new TXmlElement('tag');
		self::assertEquals(false, $element->getHasAttribute());
		$element->Attributes[] = new TMap(array('key' => 'value'));
		self::assertEquals(true, $element->getHasAttribute());
	}

	public function testSetAttribute() {
		$element = new TXmlElement('tag');
		self::assertEquals(null, $element->getAttribute('key'));
		$element->setAttribute('key', 'value');
		self::assertEquals('value', $element->getAttribute('key'));
	}

	public function testGetElementByTagName() {
		$element = new TXmlElement('tag');
		self::assertEquals(null, $element->getElementByTagName('first'));
		$element->Elements[] = new TXmlElement('first');
		$first = $element->getElementByTagName('first');
		self::assertType('TXmlElement', $first);
		self::assertEquals('first', $first->getTagName());
	}

	public function testGetElementsByTagName() {
		$element = new TXmlElement('tag');
		$element->Elements[] = new TXmlElement('tag');
		$element->Elements[] = new TXmlElement('tag');
		self::assertEquals(2, count($element->getElementsByTagName('tag')));
	}

	public function testToString() {
		$element = new TXmlElement('tag');
		self::assertEquals('<tag />', (string)$element);
		$element->setAttribute('key', 'value');
		self::assertEquals('<tag key="value" />', (string)$element);
		$element->setValue('value');
		self::assertEquals('<tag key="value">value</tag>', (string)$element);
	}
}
?>
