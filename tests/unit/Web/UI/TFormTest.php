<?php

namespace Prado\Test\Unit\Web\UI;

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

class TFormTest extends \PHPUnit\Framework\TestCase
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
		$obLevel = ob_get_level();
		$originalService = $app->getService();
		$originalResponse = $app->getResponse();
		$app->setService(new TPageService());
		// the form renders the CSRF token, which writes its cookie to the response
		$app->setResponse(new \Prado\Test\Unit\Harness\Web\TTestCookieResponse());

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
			$this->assertStringContainsString('name="PRADO_CSRF_TOKEN"', $output);
		} finally {
			$app->setService($originalService);
			$app->setResponse($originalResponse);
			// the first getResponse() of the run bootstraps the response, which starts a buffer
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
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

	/**
	 * Renders a form whose properties the callable sets, with the page service and a
	 * response that takes the CSRF cookie.
	 */
	private function renderForm(callable $setup): string
	{
		$app = \Prado\Prado::getApplication();
		$obLevel = ob_get_level();
		$originalService = $app->getService();
		$originalResponse = $app->getResponse();
		$app->setService(new TPageService());
		$app->setResponse(new \Prado\Test\Unit\Harness\Web\TTestCookieResponse());
		try {
			$page = new TPage();
			$form = new TForm();
			$form->setID('TestForm');
			$page->getControls()->add($form);
			$form->onInit(null);
			$setup($form);
			$textWriter = new TTextWriter();
			$form->render(new THtmlWriter($textWriter));
			return $textWriter->flush();
		} finally {
			$app->setService($originalService);
			$app->setResponse($originalResponse);
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	public function testSubmissionAttributeDefaults()
	{
		$form = new TForm();
		$this->assertFalse($form->getNoValidate());
		$this->assertNull($form->getAutoComplete());
		$this->assertSame('', $form->getTarget());
		$this->assertSame('', $form->getAcceptCharset());
		$this->assertSame('', $form->getRel());
		$output = $this->renderForm(fn ($form) => null);
		$formTag = substr($output, 0, strpos($output, '>') + 1);
		foreach (['novalidate', 'autocomplete', 'target=', 'accept-charset', 'rel='] as $attribute) {
			$this->assertStringNotContainsString($attribute, $formTag);
		}
	}

	public function testSubmissionAttributesRender()
	{
		$output = $this->renderForm(function ($form) {
			$form->setNoValidate('true');
			$form->setAutoComplete(false);
			$form->setTarget(' _blank ');
			$form->setAcceptCharset('UTF-8');
			$form->setRel('noopener noreferrer');
		});
		$this->assertStringContainsString('novalidate="novalidate"', $output);
		$this->assertStringContainsString('autocomplete="off"', $output);
		$this->assertStringContainsString('target="_blank"', $output);
		$this->assertStringContainsString('accept-charset="UTF-8"', $output);
		$this->assertStringContainsString('rel="noopener noreferrer"', $output);
	}

	public function testAutoCompleteStates()
	{
		$form = new TForm();
		$form->setAutoComplete('true');
		$this->assertTrue($form->getAutoComplete());
		$form->setAutoComplete('');
		$this->assertNull($form->getAutoComplete());
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