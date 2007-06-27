<?php
require_once dirname(__FILE__).'/../../phpunit.php';

Prado::using('System.Web.UI.THtmlWriter');

/**
 * Implement a writer that flush the content to a variable, to simulate a real flush
 */

class TestWriter extends TComponent implements ITextWriter
{
	private $_str='';
	private $_flushedContent=null;


	public function flush()
	{
		$this->_flushedContent=$this->_str;
		$this->_str='';
		return $this->_flushedContent;
	}


	public function write($str)
	{
		$this->_str.=$str;
	}


	public function writeLine($str='')
	{
		$this->write($str."\n");
	}
	
	// Accessors to get value of private vars during tests
	public function getFlushedContent() { return $this->_flushedContent; }
	public function getStr() { return $this->_str; }
}


/**
 * @package System.Web.UI
 */
class THtmlWriterTest extends PHPUnit_Framework_TestCase {

	private static $output=null;

	public function setUp () {
		// We need an output writer, use a TestWriter for this
		if (self::$output===null) self::$output=new TestWriter();
	}

	public function testConstruct() {
		$writer=new THtmlWriter(self::$output);
		self::assertEquals(self::$output, $writer->getWriter());
	}

	public function testSetAndGetWriter() {
		$writer=new THtmlWriter(null);
		self::assertNull($writer->getWriter());
		$writer->setWriter(self::$output);
		self::assertEquals(self::$output, $writer->getWriter());
	}

	public function testAddAttributes() {
		$writer=new THtmlWriter(self::$output);
		$writer->addAttributes(array ('type' => 'text', 'value' => 'Prado & Cie'));
		// get the private var _attributes
		$result=self::getAttribute($writer, '_attributes');
		self::assertEquals('text',$result['type']);
		self::assertEquals(THttpUtility::htmlEncode('Prado & Cie'), $result['value']);
	}

	public function testAddAttribute() {
		$writer=new THtmlWriter(self::$output);
		$writer->addAttribute('type','text');
		$writer->addAttribute('value','Prado & Cie');
		$result=self::getAttribute($writer, '_attributes');
		self::assertEquals('text',$result['type']);
		self::assertEquals(THttpUtility::htmlEncode('Prado & Cie'), $result['value']);		
	}

	public function testRemoveAttribute() {
		$writer=new THtmlWriter(self::$output);
		$writer->addAttribute('type','text');
		$writer->addAttribute('value','Prado & Cie');
		$writer->removeAttribute('value');
		$result=self::getAttribute($writer, '_attributes');
		// 'type' should be present, 'value' not
		self::assertTrue(isset($result['type']));
		self::assertFalse(isset($result['value']));
	}

	public function testAddStyleAttributes() {
		$writer=new THtmlWriter(self::$output);
		$writer->addStyleAttributes(array ('font-size' => '1em', 'background-image'=>'url(image.gif)'));
		$result=self::getAttribute($writer, '_styles');
		self::assertEquals('1em', $result['font-size']);
		self::assertEquals(THttpUtility::htmlEncode('url(image.gif)'), $result['background-image']);
	}

	public function testAddStyleAttribute() {
		$writer=new THtmlWriter(self::$output);
		$writer->addStyleAttribute('font-size','1em');
		$writer->addStyleAttribute('background-image','url(image.gif)');
		$result=self::getAttribute($writer, '_styles');
		self::assertEquals('1em', $result['font-size']);
		self::assertEquals(THttpUtility::htmlEncode('url(image.gif)'), $result['background-image']);
	}

	public function testRemoveStyleAttribute() {
		$writer=new THtmlWriter(self::$output);
		$writer->addStyleAttribute('font-size','1em');
		$writer->addStyleAttribute('background-image','url(image.gif)');
		$writer->removeStyleAttribute('font-size');
		$result=self::getAttribute($writer, '_styles');
		self::assertTrue(isset($result['background-image']));
		self::assertFalse(isset($result['font-size']));
	}

	public function testFlush() {
		$writer=new THtmlWriter(self::$output);
		$writer->write('Some Text');
		$writer->flush();
		self::assertEquals('Some Text', self::$output->getFlushedContent());
	}

	public function testWrite() {
		$writer=new THtmlWriter(self::$output);
		$writer->write('Some Text');;
		self::assertEquals('Some Text', self::$output->flush());
		
	}

	public function testWriteLine() {
		$writer=new THtmlWriter(self::$output);
		$writer->writeLine('Some Text');;
		self::assertEquals("Some Text\n", self::$output->flush());
		
	}

	public function testWriteBreak() {
		$writer=new THtmlWriter(self::$output);
		$writer->writeBreak();
		self::assertEquals("<br/>", self::$output->flush());
		
	}

	public function testRenderBeginTag() {
		$writer=new THtmlWriter(self::$output);
		$writer->addAttribute('type','text');
		$writer->addAttribute('value','Prado');
		$writer->addStyleAttribute('font-size','1em');
		$writer->renderBeginTag('input');
		self::assertEquals('<input type="text" value="Prado" style="font-size:1em;" />', self::$output->flush());
	}

	public function testRenderEndTag() {
		$writer=new THtmlWriter(self::$output);
		$writer->renderBeginTag('div');
		$writer->write('Div Content');
		$writer->renderEndTag();
		self::assertEquals('<div>Div Content</div>', self::$output->flush());
	}

}
?>