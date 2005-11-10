<?php

require_once(dirname(__FILE__).'/../common.php');

class utXmlDocument extends UnitTestCase
{
	public function setUp()
	{
	}

	public function tearDown()
	{
	}

	public function testLoadAndSave()
	{
		$dir=dirname(__FILE__).'/xml';

		// a regular XML file
		$doc=new TXmlDocument;
		$doc->loadFromFile($dir.'/data1.xml');
		$doc->saveToFile($dir.'/data1.xml.tmp');
		$this->assertTrue($this->compareFiles($dir.'/data1.xml.tmp',$dir.'/data1.xml.out'));
		@unlink($dir.'/data1.xml.tmp');

		// an XML file with Chinese characters
		$doc->loadFromFile($dir.'/data2.xml');
		$doc->saveToFile($dir.'/data2.xml.tmp');
		$this->assertTrue($this->compareFiles($dir.'/data2.xml.tmp',$dir.'/data2.xml.out'));
		@unlink($dir.'/data2.xml.tmp');

		// a typical Prado Application configuration file
		$doc=new TXmlDocument;
		$doc->loadFromFile($dir.'/data3.xml');
		$doc->saveToFile($dir.'/data3.xml.tmp');
		$this->assertTrue($this->compareFiles($dir.'/data3.xml.tmp',$dir.'/data3.xml.out'));
		@unlink($dir.'/data3.xml.tmp');
	}

	protected function compareFiles($file1,$file2)
	{
		return file_get_contents($file1)===file_get_contents($file2);
	}

	public function testAccessDomTree()
	{
		$dir=dirname(__FILE__).'/xml';
		$doc=new TXmlDocument;
		$doc->loadFromFile($dir.'/data1.xml');
		$this->assertTrue($doc->getVersion()==='1.0');
		$this->assertTrue($doc->getEncoding()==='utf-8');
		$this->assertTrue($doc->getElements()->getCount()===2);
		$this->assertTrue($doc->getElements()->itemAt(0)->getTagName()==='title');
		$this->assertTrue($doc->getElements()->itemAt(0)->getValue()==='My lists');
		$this->assertTrue($doc->getElements()->itemAt(1)->getTagName()==='chapter');
		$this->assertTrue($doc->getElements()->itemAt(1)->getAttribute('id')==='books');
	}

	public function testUpdateDomTree()
	{
	}

	public function testComposeDomTree()
	{
	}
}

?>