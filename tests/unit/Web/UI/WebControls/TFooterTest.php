<?php

use Prado\Web\UI\WebControls\TFooter;
use Prado\Web\UI\WebControls\THtmlElement;
use PHPUnit\Framework\TestCase;

class TFooterTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testTagName()
	{
		$control = new TFooter();
		$this->assertEquals('footer', $control->getTagName());
	}

	public function testRendersFooterTag()
	{
		$control = new TFooter();
		$output = $this->render($control);
		$this->assertStringContainsString('<footer', $output);
		$this->assertStringContainsString('</footer>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TFooter();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new TFooter();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TFooter();
		$this->assertEquals('footer', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new TFooter();
		$control->setTagName('div');
		$this->assertEquals('div', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<div', $output);
		$this->assertStringNotContainsString('<footer', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new TFooter();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new TFooter();
		$control->setTagName('section');
		$this->assertTrue($control->getIsMutated());
	}

	public function testGetIsMutatedFalseAfterRestoringDefault()
	{
		$control = new TFooter();
		$control->setTagName('div');
		$control->setTagName('footer');
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new TFooter();
		$control->setCssClass('site-footer');
		$output = $this->render($control);
		$this->assertStringContainsString('class="site-footer"', $output);
		$this->assertStringContainsString('<footer', $output);
	}

	public function testRendersWithChildContent()
	{
		$control = new TFooter();
		$control->getControls()->add('Footer content');
		$output = $this->render($control);
		$this->assertStringContainsString('Footer content', $output);
		$this->assertStringContainsString('<footer', $output);
	}
}
