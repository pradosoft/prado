<?php

use Prado\Web\UI\WebControls\TAside;
use Prado\Web\UI\WebControls\THtmlElement;
use PHPUnit\Framework\TestCase;

class TAsideTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testTagName()
	{
		$control = new TAside();
		$this->assertEquals('aside', $control->getTagName());
	}

	public function testRendersAsideTag()
	{
		$control = new TAside();
		$output = $this->render($control);
		$this->assertStringContainsString('<aside', $output);
		$this->assertStringContainsString('</aside>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TAside();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new TAside();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TAside();
		$this->assertEquals('aside', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new TAside();
		$control->setTagName('div');
		$this->assertEquals('div', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<div', $output);
		$this->assertStringNotContainsString('<aside', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new TAside();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new TAside();
		$control->setTagName('section');
		$this->assertTrue($control->getIsMutated());
	}

	public function testGetIsMutatedFalseAfterRestoringDefault()
	{
		$control = new TAside();
		$control->setTagName('div');
		$control->setTagName('aside');
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new TAside();
		$control->setCssClass('sidebar');
		$output = $this->render($control);
		$this->assertStringContainsString('class="sidebar"', $output);
		$this->assertStringContainsString('<aside', $output);
	}

	public function testRendersWithChildContent()
	{
		$control = new TAside();
		$control->getControls()->add('Side content');
		$output = $this->render($control);
		$this->assertStringContainsString('Side content', $output);
		$this->assertStringContainsString('<aside', $output);
	}
}
