<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TNoScript;

class TNoScriptTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testDefaultTagName()
	{
		$control = new TNoScript();
		$this->assertInstanceOf(THtmlElement::class, $control);
		$this->assertSame('noscript', $control->getTagName());
	}

	public function testRendersChildContent()
	{
		$control = new TNoScript();
		$control->getControls()->add('<p>Needs JavaScript.</p>');
		$this->assertSame('<noscript><p>Needs JavaScript.</p></noscript>', $this->render($control));
	}
}
