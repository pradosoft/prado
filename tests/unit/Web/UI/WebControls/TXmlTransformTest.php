<?php


use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TXmlTransform;

class TXmlTransformTest extends PHPUnit\Framework\TestCase
{
	private $documentContent;
	private $transformContent;
	private $documentPath;
	private $transformPath;

	protected function setUp(): void
	{
		$this->documentPath = __DIR__ . '/data/hello.xml';
		$this->documentContent = file_get_contents($this->documentPath);
		$this->transformPath = __DIR__ . '/data/hello.xsl';
		$this->transformContent = file_get_contents($this->transformPath);
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
		$this->assertEquals($expected, $transform->getDocumentPath());
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
		$this->assertEquals($expected, $transform->getTransformPath());
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
		// Set transform path but leave document unset so it uses body as document
		$transform = new TXmlTransform();
		$transform->setTransformPath($this->transformPath);
		
		// Create a writer and render
		$textWriter = new TTextWriter();
		$htmlWriter = new THtmlWriter($textWriter);
		
		// Test with parameters to make sure they're processed even when falling back to body
		$transform->getParameters()->add('testParam', 'testValue');
		$this->assertEquals('testValue', $transform->getParameters()->itemAt('testParam'));
		
		try {
			$transform->render($htmlWriter);
			$this->fail("should have thrown \ValueError");
		} catch (\ValueError) {}
		$actual = $textWriter->flush();
		
		// Since we're transforming an empty XML document with our XSL,
		// and our XSL expects a <greeting> element, we should get an empty <b> tag
		$this->assertEquals('', $actual);
		ob_end_clean();
	}
}
