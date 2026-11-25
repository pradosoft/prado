<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TWebControl;

class THtmlElementTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testExtendsTWebControl()
	{
		$this->assertInstanceOf(TWebControl::class, new THtmlElement());
	}

	public function testDefaultTagNameIsSpan()
	{
		$element = new THtmlElement();
		$this->assertSame('span', $element->getDefaultTagName());
		$this->assertSame('span', $element->getTagName());
		$this->assertFalse($element->getIsMutated());
	}

	public function testFreshElementIsNotMutated()
	{
		$this->assertFalse((new THtmlElement())->getIsMutated());
	}

	public function testSetTagNameMutatesTheElement()
	{
		$element = new THtmlElement();
		$element->setTagName('section');
		$this->assertSame('section', $element->getTagName());
		$this->assertTrue($element->getIsMutated());
	}

	public function testSetTagNameToTheDefaultIsNotMutated()
	{
		$element = new THtmlElement();
		$element->setTagName('span');
		$this->assertFalse($element->getIsMutated());
	}

	public function testRendersTheTagWithContent()
	{
		$element = new THtmlElement();
		$element->setID('el');
		$element->getControls()->add('content');
		$this->assertSame('<span id="el">content</span>', $this->render($element));

		$element->setTagName('em');
		$this->assertSame('<em id="el">content</em>', $this->render($element));
	}
}
