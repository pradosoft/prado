<?php

use Prado\Web\UI\WebControls\THeader;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class THeaderTest extends TestCase
{
	private function render($control)
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	public function testTagName()
	{
		$control = new THeader();
		$this->assertEquals('header', $control->getTagName());
	}

	public function testRendersHeaderTag()
	{
		$control = new THeader();
		$output = $this->render($control);
		$this->assertStringContainsString('<header', $output);
		$this->assertStringContainsString('</header>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new THeader();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new THeader();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new THeader();
		$this->assertEquals('header', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new THeader();
		$control->setTagName('div');
		$this->assertEquals('div', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<div', $output);
		$this->assertStringNotContainsString('<header', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new THeader();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new THeader();
		$control->setTagName('section');
		$this->assertTrue($control->getIsMutated());
	}

	public function testGetIsMutatedFalseAfterRestoringDefault()
	{
		$control = new THeader();
		$control->setTagName('div');
		$control->setTagName('header');
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new THeader();
		$control->setCssClass('site-header');
		$output = $this->render($control);
		$this->assertStringContainsString('class="site-header"', $output);
		$this->assertStringContainsString('<header', $output);
	}

	public function testRendersWithChildContent()
	{
		$control = new THeader();
		$control->getControls()->add('Header content');
		$output = $this->render($control);
		$this->assertStringContainsString('Header content', $output);
		$this->assertStringContainsString('<header', $output);
	}
}
