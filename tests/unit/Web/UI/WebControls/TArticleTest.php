<?php

use Prado\Web\UI\WebControls\TArticle;
use Prado\Web\UI\WebControls\THtmlElement;
use PHPUnit\Framework\TestCase;

class TArticleTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testTagName()
	{
		$control = new TArticle();
		$this->assertEquals('article', $control->getTagName());
	}

	public function testRendersArticleTag()
	{
		$control = new TArticle();
		$output = $this->render($control);
		$this->assertStringContainsString('<article', $output);
		$this->assertStringContainsString('</article>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TArticle();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testExtendsTHtmlElement()
	{
		$control = new TArticle();
		$this->assertInstanceOf(THtmlElement::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TArticle();
		$this->assertEquals('article', $control->getDefaultTagName());
	}

	public function testSetTagNameOverridesDefault()
	{
		$control = new TArticle();
		$control->setTagName('div');
		$this->assertEquals('div', $control->getTagName());
		$output = $this->render($control);
		$this->assertStringContainsString('<div', $output);
		$this->assertStringNotContainsString('<article', $output);
	}

	public function testGetIsMutatedFalseByDefault()
	{
		$control = new TArticle();
		$this->assertFalse($control->getIsMutated());
	}

	public function testGetIsMutatedTrueAfterSetTagName()
	{
		$control = new TArticle();
		$control->setTagName('div');
		$this->assertTrue($control->getIsMutated());
	}

	public function testRendersWithAttributes()
	{
		$control = new TArticle();
		$control->setCssClass('hero-article');
		$output = $this->render($control);
		$this->assertStringContainsString('class="hero-article"', $output);
		$this->assertStringContainsString('<article', $output);
	}
}
