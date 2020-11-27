<?php


use Prado\IO\ITextWriter;
use Prado\TComponent;
use Prado\Web\THttpUtility;
use Prado\Web\UI\THtmlWriter;

/**
 * Implement a writer that flush the content to a variable, to simulate a real flush
 */

class TestWriter extends TComponent implements ITextWriter
{
	private $_str = '';
	private $_flushedContent;


	public function flush()
	{
		$this->_flushedContent = $this->_str;
		$this->_str = '';
		return $this->_flushedContent;
	}


	public function write($str)
	{
		$this->_str .= $str;
	}


	public function writeLine($str = '')
	{
		$this->write($str . "\n");
	}

	// Accessors to get value of private vars during tests
	public function getFlushedContent()
	{
		return $this->_flushedContent;
	}
	public function getStr()
	{
		return $this->_str;
	}
}


class THtmlWriterTest extends PHPUnit\Framework\TestCase
{
	private static $output = null;

	protected function setUp(): void
	{
		// We need an output writer, use a TestWriter for this
		if (self::$output === null) {
			self::$output = new TestWriter();
		}
	}

	protected function getPrivatePropertyValue($object, $property)
	{
		$reflectionClass = new ReflectionClass($object);
		$reflectionProperty = $reflectionClass->getProperty($property);
		$reflectionProperty->setAccessible(true);
		return $reflectionProperty->getValue($object);
	}

	public function testConstruct()
	{
		$writer = new THtmlWriter(self::$output);
		self::assertEquals(self::$output, $writer->getWriter());
	}

	public function testSetAndGetWriter()
	{
		$writer = new THtmlWriter(null);
		self::assertNull($writer->getWriter());
		$writer->setWriter(self::$output);
		self::assertEquals(self::$output, $writer->getWriter());
	}

	public function testAddAttributes()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->addAttributes(['type' => 'text', 'value' => 'Prado & Cie']);
		$result = $this->getPrivatePropertyValue($writer, '_attributes');
		self::assertEquals('text', $result['type']);
		self::assertEquals(THttpUtility::htmlEncode('Prado & Cie'), $result['value']);
	}

	public function testAddAttribute()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->addAttribute('type', 'text');
		$writer->addAttribute('value', 'Prado & Cie');
		$result = $this->getPrivatePropertyValue($writer, '_attributes');
		self::assertEquals('text', $result['type']);
		self::assertEquals(THttpUtility::htmlEncode('Prado & Cie'), $result['value']);
	}

	public function testRemoveAttribute()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->addAttribute('type', 'text');
		$writer->addAttribute('value', 'Prado & Cie');
		$writer->removeAttribute('value');
		$result = $this->getPrivatePropertyValue($writer, '_attributes');
		// 'type' should be present, 'value' not
		self::assertTrue(isset($result['type']));
		self::assertFalse(isset($result['value']));
	}

	public function testAddStyleAttributes()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->addStyleAttributes(['font-size' => '1em', 'background-image' => 'url(image.gif)']);
		$result = $this->getPrivatePropertyValue($writer, '_styles');
		self::assertEquals('1em', $result['font-size']);
		self::assertEquals(THttpUtility::htmlEncode('url(image.gif)'), $result['background-image']);
	}

	public function testAddStyleAttribute()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->addStyleAttribute('font-size', '1em');
		$writer->addStyleAttribute('background-image', 'url(image.gif)');
		$result = $this->getPrivatePropertyValue($writer, '_styles');
		self::assertEquals('1em', $result['font-size']);
		self::assertEquals(THttpUtility::htmlEncode('url(image.gif)'), $result['background-image']);
	}

	public function testRemoveStyleAttribute()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->addStyleAttribute('font-size', '1em');
		$writer->addStyleAttribute('background-image', 'url(image.gif)');
		$writer->removeStyleAttribute('font-size');
		$result = $this->getPrivatePropertyValue($writer, '_styles');
		self::assertTrue(isset($result['background-image']));
		self::assertFalse(isset($result['font-size']));
	}

	public function testFlush()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->write('Some Text');
		$writer->flush();
		self::assertEquals('Some Text', self::$output->getFlushedContent());
	}

	public function testWrite()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->write('Some Text');
		;
		self::assertEquals('Some Text', self::$output->flush());
	}

	public function testWriteLine()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->writeLine('Some Text');
		;
		self::assertEquals("Some Text\n", self::$output->flush());
	}

	public function testWriteBreak()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->writeBreak();
		self::assertEquals("<br/>", self::$output->flush());
	}

	public function testRenderBeginTag()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->addAttribute('type', 'text');
		$writer->addAttribute('value', 'Prado');
		$writer->addStyleAttribute('font-size', '1em');
		$writer->renderBeginTag('input');
		self::assertEquals('<input type="text" value="Prado" style="font-size:1em;" />', self::$output->flush());
	}

	public function testRenderEndTag()
	{
		$writer = new THtmlWriter(self::$output);
		$writer->renderBeginTag('div');
		$writer->write('Div Content');
		$writer->renderEndTag();
		self::assertEquals('<div>Div Content</div>', self::$output->flush());
	}
}
