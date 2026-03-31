<?php

use Prado\Exceptions\TIOException;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

class TXmlDocumentTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('utf-8', $xmldoc->getEncoding());
	}

	public function testSetVersion()
	{
		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		self::assertEquals('1.0', $xmldoc->getVersion());
		$xmldoc->setVersion('2.0');
		self::assertEquals('2.0', $xmldoc->getVersion());
	}

	public function testSetEncoding()
	{
		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		self::assertEquals('utf-8', $xmldoc->getEncoding());
		$xmldoc->setEncoding('iso8859-1');
		self::assertEquals('iso8859-1', $xmldoc->getEncoding());
	}

	public function testLoadFromFile()
	{
		$file = __DIR__ . '/data/test.xml';
		$xmldoc = new TXmlDocument();
		try {
			$xmldoc->loadFromFile('unexistentXmlFile.xml');
			self::fail('Expected TIOException not thrown');
		} catch (TIOException $e) {
		}

		self::assertTrue($xmldoc->loadFromFile($file));
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('UTF-8', $xmldoc->getEncoding());
	}

	public function testLoadFromString()
	{
		$xmlStr = '<?xml version="1.0" encoding="UTF-8"?><rootNode><node id="node1" param="attribute1"/><node id="node2" param="attribute2"/></rootNode>';
		$xmldoc = new TXmlDocument();
		self::assertTrue($xmldoc->loadFromString($xmlStr));
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('UTF-8', $xmldoc->getEncoding());
		
		// Test invalid XML, with validation default
		$invalidXml = '<?xml version="1.0" encoding="UTF-8"?><root><child>test</root>';
		self::assertFalse($xmldoc->loadFromString($invalidXml));
	}

	public function testSaveToString()
	{
		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		$xmldoc->setTagName('root');
		$node = new TXmlElement('node');
		$node->setAttribute('param', 'attribute1');
		$xmldoc->getElements()->add($node);
		$xmlString = $xmldoc->saveToString();
		// test magic method
		$magicString = (string) $xmldoc;
		self::assertEquals($magicString, $xmlString);
		// Result string should be :
		$resultString = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root>\n    <node param=\"attribute1\"\n</root>";
		self::assertEquals($xmlString, $magicString);
	}

	public function testSaveToFile()
	{
		$file = __DIR__ . '/data/tmp.xml';
		if (!is_writable(dirname($file))) {
			self::markTestSkipped(dirname($file) . ' must be writable for this test');
		}
		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		$xmldoc->setTagName('root');
		$node = new TXmlElement('node');
		$node->setAttribute('param', 'attribute1');
		$xmldoc->getElements()->add($node);
		$xmldoc->saveToFile($file);
		self::assertTrue(is_file($file));
		if (is_file($file)) {
			unlink($file);
		}
	}
	
	/**
	 * Test edge cases with empty and null inputs
	 */
	public function testLoadFromStringEdgeCases()
	{
		// Test with empty string
		$xmldoc = new TXmlDocument();
		self::assertFalse($xmldoc->loadFromString(''));
		
		// Test with null input (should be converted to string)
		self::assertFalse($xmldoc->loadFromString(null));
		
		// Test with malformed XML
		$malformedXml = '<?xml version="1.0"?><root><child>test</root>';
		self::assertFalse($xmldoc->loadFromString($malformedXml));
		
		// Test with valid XML
		$validXml = '<?xml version="1.0" encoding="UTF-8"?><root><child>test</child></root>';
		self::assertTrue($xmldoc->loadFromString($validXml));
		
		// Test with validation enabled on valid XML
		self::assertTrue($xmldoc->loadFromString($validXml, true));
		
		// Test with validation enabled on invalid XML
		self::assertFalse($xmldoc->loadFromString($malformedXml, true));
	}
	
	/**
	 * Test various encodings and version handling
	 */
	public function testEncodingAndVersionEdgeCases()
	{
		// Test with different encodings
		$xmlWithEncoding = '<?xml version="1.0" encoding="ISO-8859-1"?><root>test</root>';
		$xmldoc = new TXmlDocument();
		self::assertTrue($xmldoc->loadFromString($xmlWithEncoding));
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('ISO-8859-1', $xmldoc->getEncoding());
		
		// Test with no encoding specified
		$xmlWithoutEncoding = '<?xml version="1.0"?><root>test</root>';
		self::assertTrue($xmldoc->loadFromString($xmlWithoutEncoding));
		self::assertEquals('1.0', $xmldoc->getVersion());
	}
	
	/**
	 * Test large XML content handling
	 */
	public function testLargeXmlHandling()
	{
		// Create a large XML string with many elements
		$elements = [];
		for ($i = 0; $i < 1000; $i++) {
			$elements[] = "<item id='$i'>value $i</item>";
		}
		$largeXml = '<?xml version="1.0" encoding="UTF-8"?><root>' . implode('', $elements) . '</root>';
		
		$xmldoc = new TXmlDocument();
		self::assertTrue($xmldoc->loadFromString($largeXml));
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('UTF-8', $xmldoc->getEncoding());
	}
	
	/**
	 * Test that properties are reset when loadFromString fails
	 */
	public function testLoadFromStringResetPropertiesOnFailure()
	{
		// Create a document with some initial data
		$xmldoc = new TXmlDocument('2.0', 'UTF-8');
		$xmldoc->setTagName('initialRoot');
		$xmldoc->setValue('initialValue');
		$xmldoc->setAttribute('initialAttr', 'initialValue');
		
		// Add some child elements
		$child = new TXmlElement('child');
		$child->setValue('childValue');
		$xmldoc->getElements()->add($child);
		
		// Verify initial state
		self::assertEquals('2.0', $xmldoc->getVersion());
		self::assertEquals('UTF-8', $xmldoc->getEncoding());
		self::assertEquals('initialRoot', $xmldoc->getTagName());
		self::assertEquals('initialValue', $xmldoc->getValue());
		self::assertEquals('initialValue', $xmldoc->getAttribute('initialAttr'));
		self::assertEquals(1, $xmldoc->getCount());
		
		// Attempt to load invalid XML
		$invalidXml = '<?xml version="1.0"?><root><child>test</root>';
		$result = $xmldoc->loadFromString($invalidXml);
		
		// Should return false for failed parsing
		self::assertFalse($result);
		
		// Properties should be reset to initial state (empty)
		self::assertEquals('1.0', $xmldoc->getVersion()); // Should reset to default version 1.0
		self::assertEquals('', $xmldoc->getEncoding()); // Should reset to empty encoding
		self::assertEquals('', $xmldoc->getTagName()); // Should reset to default tag name (empty string)
		self::assertEquals('', $xmldoc->getValue()); // Should reset to empty value
		self::assertEquals(null, $xmldoc->getAttribute('initialAttr')); // Should clear attributes
		self::assertEquals(0, $xmldoc->getCount()); // Should clear child elements
	}
	
	/**
	 * Test DOM node type method
	 */
	public function testDOMNodeType()
	{
		$document = new TXmlDocument('1.0', 'utf-8');
		self::assertEquals(XML_DOCUMENT_NODE, $document->getNodeType());
	}
	
	/**
	 * Test file I/O edge cases
	 */
	public function testFileIOEdgeCases()
	{
		$document = new TXmlDocument('1.0', 'utf-8');
		
		// Test with non-existent file
		try {
			$document->loadFromFile('nonexistent.xml');
			self::fail('Expected TIOException not thrown');
		} catch (TIOException $e) {
			self::assertTrue(true); // if the exceptions don't throw, still measured
		}
		
		// Test with empty file (should not cause errors)
		$emptyFile = __DIR__ . '/data/empty.xml';
		file_put_contents($emptyFile, '');
		
		// Test with empty file
		self::assertFalse($document->loadFromFile($emptyFile));
		
		unlink($emptyFile);
	}
	
	/**
	 * Test complex XML string parsing
	 */
	public function testComplexXMLStringParsing()
	{
		// Test with complex XML structure including namespaces and special characters
		$complexXml = '<?xml version="1.0" encoding="UTF-8"?>
		<root xmlns:ns="http://example.com/ns" xmlns="http://example.com/default">
			<item id="1" type="test">Value 1</item>
			<item id="2" type="example">Value 2</item>
			<nested>
				<inner id="3">Nested value</inner>
			</nested>
		</root>';
		
		$document = new TXmlDocument();
		$result = $document->loadFromString($complexXml);
		self::assertTrue($result);
		self::assertEquals('1.0', $document->getVersion());
		self::assertEquals('UTF-8', $document->getEncoding());
		self::assertEquals('root', $document->getTagName());
		
		// Test that elements were parsed correctly
		self::assertEquals(3, $document->getCount());
		
		// Test first element
		$firstItem = $document->getElementByTagName('item');
		self::assertNotNull($firstItem);
		self::assertEquals('1', $firstItem->getAttribute('id'));
		self::assertEquals('test', $firstItem->getAttribute('type'));
		self::assertEquals('Value 1', $firstItem->getValue());
	}
	
	/**
	 * Test with various encodings
	 */
	public function testVariousEncodings()
	{
		// Test ISO-8859-1 encoding
		$isoXml = '<?xml version="1.0" encoding="ISO-8859-1"?><root>Test with special characters: àáâãäåæ</root>';
		$document = new TXmlDocument();
		$result = $document->loadFromString($isoXml);
		self::assertTrue($result);
		self::assertEquals('1.0', $document->getVersion());
		self::assertEquals('ISO-8859-1', $document->getEncoding());
		
		// Test with no encoding specified
		$noEncXml = '<?xml version="1.0"?><root>Test without encoding</root>';
		$document2 = new TXmlDocument();
		$result2 = $document2->loadFromString($noEncXml);
		self::assertTrue($result2);
		self::assertEquals('1.0', $document2->getVersion());
		self::assertEquals('', $document2->getEncoding());
	}
}