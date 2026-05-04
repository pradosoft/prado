<?php

use Prado\Web\UI\WebControls\TMark;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class TMarkTest extends TestCase
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
		$control = new TMark();
		$this->assertEquals('mark', $control->getTagName());
	}

	public function testRendersMarkTag()
	{
		$control = new TMark();
		$output = $this->render($control);
		$this->assertStringContainsString('<mark', $output);
		$this->assertStringContainsString('</mark>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TMark();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new TMark();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TMark();
		$this->assertEquals('mark', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new TMark();
		$control->setTagName('span');
		$this->assertEquals('span', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<span', $output);
		$this->assertStringNotContainsString('<mark', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new TMark();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new TMark();
		$control->setTagName('span');
		$this->assertTrue($control->getIsMutated());
	}

	public function testGetIsMutatedFalseAfterRestoringDefault()
	{
		$control = new TMark();
		$control->setTagName('span');
		$control->setTagName('mark');
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new TMark();
		$control->setCssClass('highlight');
		$output = $this->render($control);
		$this->assertStringContainsString('class="highlight"', $output);
		$this->assertStringContainsString('<mark', $output);
	}

	public function testRendersWithChildContent()
	{
		$control = new TMark();
		$control->getControls()->add('highlighted text');
		$output = $this->render($control);
		$this->assertStringContainsString('highlighted text', $output);
		$this->assertStringContainsString('<mark', $output);
	}
}
