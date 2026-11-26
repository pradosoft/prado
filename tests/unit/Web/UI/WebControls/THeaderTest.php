<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\THeader;
use Prado\Web\UI\WebControls\THtmlElement;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;

class THeaderTest extends TestCase
{
	use TWebControlRenderTrait;

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

	public static function headingLevelProvider(): array
	{
		return [
			'h1' => [\Prado\Web\UI\WebControls\THeader1::class, 'h1'],
			'h2' => [\Prado\Web\UI\WebControls\THeader2::class, 'h2'],
			'h3' => [\Prado\Web\UI\WebControls\THeader3::class, 'h3'],
			'h4' => [\Prado\Web\UI\WebControls\THeader4::class, 'h4'],
			'h5' => [\Prado\Web\UI\WebControls\THeader5::class, 'h5'],
			'h6' => [\Prado\Web\UI\WebControls\THeader6::class, 'h6'],
		];
	}

	/**
	 * @dataProvider headingLevelProvider
	 */
	public function testHeadingLevelsRenderTheirTag(string $class, string $tag)
	{
		$control = new $class();
		$this->assertInstanceOf(THtmlElement::class, $control);
		$this->assertSame($tag, $control->getDefaultTagName());
		$control->getControls()->add('Title');
		$this->assertSame("<$tag>Title</$tag>", $this->render($control));
	}
}
