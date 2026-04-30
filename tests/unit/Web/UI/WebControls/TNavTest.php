<?php

use Prado\Web\UI\WebControls\TNav;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class TNavTest extends TestCase
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
		$control = new TNav();
		$this->assertEquals('nav', $control->getTagName());
	}

	public function testRendersNavTag()
	{
		$control = new TNav();
		$output = $this->render($control);
		$this->assertStringContainsString('<nav', $output);
		$this->assertStringContainsString('</nav>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TNav();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new TNav();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TNav();
		$this->assertEquals('nav', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new TNav();
		$control->setTagName('div');
		$this->assertEquals('div', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<div', $output);
		$this->assertStringNotContainsString('<nav', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new TNav();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new TNav();
		$control->setTagName('ul');
		$this->assertTrue($control->getIsMutated());
	}

	public function testGetIsMutatedFalseAfterRestoringDefault()
	{
		$control = new TNav();
		$control->setTagName('div');
		$control->setTagName('nav');
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new TNav();
		$control->setCssClass('main-nav');
		$output = $this->render($control);
		$this->assertStringContainsString('class="main-nav"', $output);
		$this->assertStringContainsString('<nav', $output);
	}

	public function testRendersWithChildContent()
	{
		$control = new TNav();
		$control->getControls()->add('Nav content');
		$output = $this->render($control);
		$this->assertStringContainsString('Nav content', $output);
		$this->assertStringContainsString('<nav', $output);
	}
}
