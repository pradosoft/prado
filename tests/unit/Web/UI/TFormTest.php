<?php

use Prado\IO\ITextWriter;
use Prado\IO\TTextWriter;
use Prado\TComponent;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\TForm;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;

class MockHtmlWriter extends TComponent implements ITextWriter
{
	private $_output = '';
	private $_attributes = [];
	
	public function getOutput()
	{
		return $this->_output;
	}

	public function flush()
	{
		return $this->_output;
	}

	public function write($str)
	{
		$this->_output .= $str;
	}

	public function writeLine($str = '')
	{
		$this->write($str . "\n");
	}
	
	public function getWriter()
	{
		return $this;
	}
	
	public function addAttribute($name, $value)
	{
		$this->_attributes[$name] = $value;
		return $this;
	}
	
	public function addAttributes($attributes)
	{
		foreach ($attributes as $name => $value) {
			$this->_attributes[$name] = $value;
		}
		return $this;
	}
	
	public function renderBeginTag($tag)
	{
		$attrs = '';
		foreach ($this->_attributes as $name => $value) {
			$attrs .= ' ' . $name . '="' . $value . '"';
		}
		$this->write('<' . $tag . $attrs . '>');
		$this->_attributes = [];
		return $this;
	}
	
	public function renderEndTag($tag)
	{
		$this->write('</' . $tag . '>');
		return $this;
	}
	
	public function renderTag($tag, $content = '')
	{
		$this->write('<' . $tag . '>' . $content . '</' . $tag . '>');
		return $this;
	}
}

class TFormTest extends PHPUnit\Framework\TestCase
{
	public function testOnInit()
	{
		$page = new TPage();
		$form = new TForm();
		$form->setPage($page);
		$form->onInit(null);
		$this->assertSame($form, $page->getForm());
	}

	public function testRender()
	{
		$app = \Prado\Prado::getApplication();
		$originalService = $app->getService();
		$app->setService(new TPageService());

		try {
			$page = new TPage();
			$form = new TForm();
			$form->setID('TestForm');
			$page->getControls()->add($form);
			$form->onInit(null);

			$textWriter = new TTextWriter();
			$htmlWriter = new THtmlWriter($textWriter);
			$form->render($htmlWriter);
			$output = $textWriter->flush();

			$this->assertStringContainsString('<form', $output);
			$this->assertStringContainsString('id="TestForm"', $output);
			$this->assertStringContainsString('method="post"', $output);
			$this->assertStringContainsString('</form>', $output);
		} finally {
			$app->setService($originalService);
		}
	}

	public function testSetAndGetDefaultButton()
	{
		$form = new TForm();
		$this->assertEquals('', $form->getDefaultButton());
		
		$form->setDefaultButton('submitButton');
		$this->assertEquals('submitButton', $form->getDefaultButton());
	}

	public function testSetAndGetMethod()
	{
		$form = new TForm();
		$this->assertEquals('post', $form->getMethod());
		
		$form->setMethod('get');
		$this->assertEquals('get', $form->getMethod());
		
		$form->setMethod('post');
		$this->assertEquals('post', $form->getMethod());
	}

	public function testSetAndGetEnctype()
	{
		$form = new TForm();
		$this->assertEquals('', $form->getEnctype());
		
		$form->setEnctype('multipart/form-data');
		$this->assertEquals('multipart/form-data', $form->getEnctype());
	}

	public function testGetName()
	{
		$page = new TPage();
		$form = new TForm();
		$form->setID('myForm');
		$page->getControls()->add($form); // sets up naming container
		// getName() returns getUniqueID(); for a direct child of page, that equals getID()
		$this->assertEquals($form->getUniqueID(), $form->getName());
		$this->assertEquals('myForm', $form->getName());
	}
}