<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TXmlTransform;

class TXmlTransformTest extends \PHPUnit\Framework\TestCase
{
	private $documentContent;
	private $transformContent;
	private $documentPath;
	private $transformPath;
	private $_obLevel;

	protected function setUp(): void
	{
		$this->_obLevel = ob_get_level();
		$this->documentPath = __DIR__ . '/data/hello.xml';
		$this->documentContent = file_get_contents($this->documentPath);
		$this->transformPath = __DIR__ . '/data/hello.xsl';
		$this->transformContent = file_get_contents($this->transformPath);
	}

	protected function tearDown(): void
	{
		// the first getResponse() of the run bootstraps the response, which starts a buffer
		while (ob_get_level() > $this->_obLevel) {
			ob_end_clean();
		}
	}

	public function testSetDocumentContent()
	{
		$expected = $this->documentContent;
		$transform = new TXmlTransform();
		$transform->setDocumentContent($expected);
		$this->assertEquals($expected, $transform->getDocumentContent());
	}

	public function testSetDocumentPathAsFile()
	{
		$expected = $this->documentPath;
		$transform = new TXmlTransform();
		$transform->setDocumentPath($expected);
		$this->assertEquals($expected, $transform->getDocumentPath());
	}

	public function testSetDocumentPathAsNamespace()
	{
		if (Prado::getPathOfAlias('UnitTest') === null) {
			Prado::setPathOfAlias('UnitTest', __DIR__ . '/data');
		}
		$expected = $this->documentPath;
		$transform = new TXmlTransform();
		$transform->setDocumentPath('UnitTest.hello');
		// Normalize separators so the comparison is cross-platform (Windows may use backslashes).
		$this->assertEquals(str_replace('\\', '/', $expected), str_replace('\\', '/', (string) $transform->getDocumentPath()));
	}

	public function testSetTransformContent()
	{
		$expected = $this->transformContent;
		$transform = new TXmlTransform();
		$transform->setTransformContent($expected);
		$this->assertEquals($expected, $transform->getTransformContent());
	}

	public function testSetTransformPathAsFile()
	{
		$expected = $this->transformPath;
		$transform = new TXmlTransform();
		$transform->setTransformPath($expected);
		$this->assertEquals($expected, $transform->getTransformPath());
	}

	public function testSetTransformPathAsNamespace()
	{
		if (Prado::getPathOfAlias('UnitTest') === null) {
			Prado::setPathOfAlias('UnitTest', __DIR__ . '/data');
		}
		$expected = $this->transformPath;
		$transform = new TXmlTransform();
		$transform->setTransformPath('UnitTest.hello');
		// Normalize separators so the comparison is cross-platform (Windows may use backslashes).
		$this->assertEquals(str_replace('\\', '/', $expected), str_replace('\\', '/', (string) $transform->getTransformPath()));
	}

	public function testAddParameter()
	{
		$transform = new TXmlTransform();
		$parameters = $transform->getParameters();
		$parameters->add('param1', 'value1');
		$parameters->add('param2', 'value2');

		$this->assertEquals('value1', $transform->getParameters()->itemAt('param1'));
		$this->assertEquals('value2', $transform->getParameters()->itemAt('param2'));
		$this->assertCount(2, $transform->getParameters());
	}

	public function testRenderWithDocumentContentAndTransformContent()
	{
		$expected = "<b>Hello World!</b>\n";
		$transform = new TXmlTransform();
		$transform->setDocumentContent($this->documentContent);
		$transform->setTransformContent($this->transformContent);
		$textWriter = new TTextWriter();
		$htmlWriter = new THtmlWriter($textWriter);
		$transform->render($htmlWriter);
		$actual = $textWriter->flush();
		self::assertEquals($expected, $actual);
	}

	public function testRenderWithDocumentPathAndTransformContent()
	{
		$expected = "<b>Hello World!</b>\n";
		$transform = new TXmlTransform();
		$transform->setDocumentPath($this->documentPath);
		$transform->setTransformContent($this->transformContent);
		$textWriter = new TTextWriter();
		$htmlWriter = new THtmlWriter($textWriter);
		$transform->render($htmlWriter);
		$actual = $textWriter->flush();
		self::assertEquals($expected, $actual);
	}

	public function testRenderWithDocumentContentAndTransformPath()
	{
		$expected = "<b>Hello World!</b>\n";
		$transform = new TXmlTransform();
		$transform->setDocumentContent($this->documentContent);
		$transform->setTransformPath($this->transformPath);
		$textWriter = new TTextWriter();
		$htmlWriter = new THtmlWriter($textWriter);
		$transform->render($htmlWriter);
		$actual = $textWriter->flush();
		self::assertEquals($expected, $actual);
	}

	public function testRenderWithDocumentPathAndTransformPath()
	{
		$expected = "<b>Hello World!</b>\n";
		$transform = new TXmlTransform();
		$transform->setDocumentPath($this->documentPath);
		$transform->setTransformPath($this->transformPath);
		$textWriter = new TTextWriter();
		$htmlWriter = new THtmlWriter($textWriter);
		$transform->render($htmlWriter);
		$actual = $textWriter->flush();
		self::assertEquals($expected, $actual);
	}

	public function testRenderWithBodyAsDocumentAndTransformPath()
	{
		// No document source: the body renders as the document, and an empty body is not loadable XML.
		$transform = new TXmlTransform();
		$transform->setTransformPath($this->transformPath);
		$transform->getParameters()->add('testParam', 'testValue');
		$this->assertEquals('testValue', $transform->getParameters()->itemAt('testParam'));

		$textWriter = new TTextWriter();
		$htmlWriter = new THtmlWriter($textWriter);
		try {
			$transform->render($htmlWriter);
			$this->fail('render() should throw \\ValueError for an empty body document');
		} catch (\ValueError) {
		}
		$this->assertEquals('', $textWriter->flush());
	}
}
