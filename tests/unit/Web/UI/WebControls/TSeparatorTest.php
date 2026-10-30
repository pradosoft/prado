<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TSeparator;

class TSeparatorTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testDefaultTagName()
	{
		$control = new TSeparator();
		$this->assertInstanceOf(THtmlElement::class, $control);
		$this->assertSame('hr', $control->getTagName());
	}

	public function testRendersVoidHrWithoutRole()
	{
		$this->assertSame('<hr />', $this->render(new TSeparator()));
	}

	public function testChildrenDoNotRender()
	{
		$control = new TSeparator();
		$control->getControls()->add('text');
		$this->assertSame('<hr />', $this->render($control));
	}

	public function testRendersAttributes()
	{
		$control = new TSeparator();
		$control->setCssClass('chapter-break');
		$this->assertSame('<hr class="chapter-break" />', $this->render($control));
	}

	public function testOtherTagRendersSeparatorRole()
	{
		$control = new TSeparator();
		$control->setTagName('div');
		$this->assertSame('<div role="separator"></div>', $this->render($control));
	}

	public function testRoleOverridesSeparatorRole()
	{
		$control = new TSeparator();
		$control->setTagName('div');
		$control->setRole('presentation');
		$this->assertSame('<div role="presentation"></div>', $this->render($control));
	}
}
