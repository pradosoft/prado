<?php

use Prado\Web\UI\WebControls\TMain;
use Prado\Web\UI\WebControls\THtmlElement;
use PHPUnit\Framework\TestCase;

class TMainTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testTagName()
	{
		$control = new TMain();
		$this->assertEquals('main', $control->getTagName());
	}

	public function testRendersMainTag()
	{
		$control = new TMain();
		$output = $this->render($control);
		$this->assertStringContainsString('<main', $output);
		$this->assertStringContainsString('</main>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TMain();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new TMain();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TMain();
		$this->assertEquals('main', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new TMain();
		$control->setTagName('div');
		$this->assertEquals('div', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<div', $output);
		$this->assertStringNotContainsString('<main', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new TMain();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new TMain();
		$control->setTagName('section');
		$this->assertTrue($control->getIsMutated());
	}

	public function testGetIsMutatedFalseAfterRestoringDefault()
	{
		$control = new TMain();
		$control->setTagName('div');
		$control->setTagName('main');
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new TMain();
		$control->setCssClass('page-main');
		$output = $this->render($control);
		$this->assertStringContainsString('class="page-main"', $output);
		$this->assertStringContainsString('<main', $output);
	}

	public function testRendersWithChildContent()
	{
		$control = new TMain();
		$control->getControls()->add('Main content');
		$output = $this->render($control);
		$this->assertStringContainsString('Main content', $output);
		$this->assertStringContainsString('<main', $output);
	}
}
