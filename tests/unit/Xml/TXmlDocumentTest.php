<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Xml.TXmlDocument');

/**
 * @package System.Xml
 */
class TXmlDocumentTest extends PHPUnit_Framework_TestCase {

	public function testConstruct() {
		$xmldoc=new TXmlDocument ('1.0', 'utf-8');
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('utf-8', $xmldoc->getEncoding());
	}

	public function testSetVersion() {
		$xmldoc=new TXmlDocument ('1.0', 'utf-8');
		self::assertEquals('1.0', $xmldoc->getVersion());
		$xmldoc->setVersion('2.0');
		self::assertEquals('2.0', $xmldoc->getVersion());
	}

	public function testSetEncoding() {
		$xmldoc=new TXmlDocument ('1.0', 'utf-8');
		self::assertEquals('utf-8', $xmldoc->getEncoding());
		$xmldoc->setEncoding('iso8859-1');
		self::assertEquals('iso8859-1', $xmldoc->getEncoding());
	}

	public function testLoadFromFile() {
		$file=dirname(__FILE__).'/data/test.xml';
		$xmldoc=new TXmlDocument();
		try {
			$xmldoc->loadFromFile('unexistentXmlFile.xml');
			self::fail('Expected TIOException not thrown');
		} catch (TIOException $e) {}
		
		self::assertTrue($xmldoc->loadFromFile($file));
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('UTF-8',$xmldoc->getEncoding());
	}

	public function testLoadFromString() {
		$xmlStr='<?xml version="1.0" encoding="UTF-8"?><rootNode><node id="node1" param="attribute1"/><node id="node2" param="attribute2"/></rootNode>';
		$xmldoc=new TXmlDocument();
		self::assertTrue($xmldoc->loadFromString($xmlStr));
		self::assertEquals('1.0', $xmldoc->getVersion());
		self::assertEquals('UTF-8',$xmldoc->getEncoding());
	}

	public function testSaveToString() {
		$xmldoc=new TXmlDocument('1.0','utf-8');
		$xmldoc->setTagName('root');
		$node=new TXmlElement('node');
		$node->setAttribute('param','attribute1');
		$xmldoc->getElements()->add($node);
		$xmlString=$xmldoc->saveToString();
		// test magic method
		$magicString=(string)$xmldoc;
		self::assertEquals($magicString,$xmlString);
		// Result string should be :
		$resultString="<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root>\n    <node param=\"attribute1\"\n</root>";
		self::assertEquals($xmlString, $magicString);
		
	}
	
	public function testSaveToFile() {
		$file=dirname(__FILE__).'/data/tmp.xml';
		if (!is_writable(dirname($file))) self::markTestSkipped(dirname($file).' must be writable for this test');
		$xmldoc=new TXmlDocument('1.0','utf-8');
		$xmldoc->setTagName('root');
		$node=new TXmlElement('node');
		$node->setAttribute('param','attribute1');
		$xmldoc->getElements()->add($node);
		$xmldoc->saveToFile($file);
		self::assertTrue(is_file($file));
		if (is_file($file)) unlink ($file);
	}
}
?>
