<?php

use Prado\Web\UI\WebControls\TSection;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class TSectionTest extends TestCase
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
		$control = new TSection();
		$this->assertEquals('section', $control->getTagName());
	}

	public function testRendersSectionTag()
	{
		$control = new TSection();
		$output = $this->render($control);
		$this->assertStringContainsString('<section', $output);
		$this->assertStringContainsString('</section>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TSection();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new TSection();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TSection();
		$this->assertEquals('section', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new TSection();
		$control->setTagName('div');
		$this->assertEquals('div', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<div', $output);
		$this->assertStringNotContainsString('<section', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new TSection();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new TSection();
		$control->setTagName('article');
		$this->assertTrue($control->getIsMutated());
	}

	public function testGetIsMutatedFalseAfterRestoringDefault()
	{
		$control = new TSection();
		$control->setTagName('div');
		$control->setTagName('section');
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new TSection();
		$control->setCssClass('content-section');
		$output = $this->render($control);
		$this->assertStringContainsString('class="content-section"', $output);
		$this->assertStringContainsString('<section', $output);
	}

	public function testRendersWithChildContent()
	{
		$control = new TSection();
		$control->getControls()->add('Section content');
		$output = $this->render($control);
		$this->assertStringContainsString('Section content', $output);
		$this->assertStringContainsString('<section', $output);
	}
}
