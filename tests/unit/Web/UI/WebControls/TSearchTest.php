<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TSearch;
use Prado\Web\UI\WebControls\TWebControl;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;

class TSearchTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testExtendsTHtmlElement()
	{
		$control = new TSearch();
		$this->assertInstanceOf(THtmlElement::class, $control);
		$this->assertInstanceOf(TWebControl::class, $control);
	}

	public function testDefaultTagName()
	{
		$control = new TSearch();
		$this->assertSame('search', $control->getDefaultTagName());
		$this->assertSame('search', $control->getTagName());
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersSearchTagWithoutRole()
	{
		$this->assertSame('<search></search>', $this->render(new TSearch()));
	}

	public function testRendersChildContent()
	{
		$control = new TSearch();
		$control->getControls()->add('Search content');
		$this->assertSame('<search>Search content</search>', $this->render($control));
	}

	public function testRendersAttributes()
	{
		$control = new TSearch();
		$control->setCssClass('site-search');
		$output = $this->render($control);
		$this->assertStringContainsString('<search', $output);
		$this->assertStringContainsString('class="site-search"', $output);
	}

	public function testOtherTagRendersSearchRole()
	{
		$control = new TSearch();
		$control->setTagName('div');
		$this->assertTrue($control->getIsMutated());
		$this->assertSame('<div role="search"></div>', $this->render($control));
	}

	public function testTagCaseVariantRendersNoRole()
	{
		$control = new TSearch();
		$control->setTagName('SEARCH');
		$this->assertStringNotContainsString('role=', $this->render($control));
	}

	public function testRoleOverridesSearchRole()
	{
		$control = new TSearch();
		$control->setTagName('div');
		$control->setRole('region');
		$this->assertSame('<div role="region"></div>', $this->render($control));
	}

	public function testRoleRendersOnSearchTag()
	{
		$control = new TSearch();
		$control->setRole('none');
		$this->assertSame('<search role="none"></search>', $this->render($control));
	}

	public function testRestoredTagRendersNoRole()
	{
		$control = new TSearch();
		$control->setTagName('div');
		$control->setTagName('search');
		$this->assertSame('<search></search>', $this->render($control));
	}

	public function testAriaLabelSubProperty()
	{
		$control = new TSearch();
		$control->setSubProperty('Aria.label', 'Site');
		$this->assertSame('<search aria-label="Site"></search>', $this->render($control));
	}

	public function testIdRendered()
	{
		$control = new TSearch();
		$control->setID('finder');
		$this->assertStringContainsString('id="finder"', $this->render($control));
	}
}
