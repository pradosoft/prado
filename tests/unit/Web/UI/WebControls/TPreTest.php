<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TPre;

class TPreTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TPre();
	}

	protected function defaultTagName(): string
	{
		return 'pre';
	}

	public function testKeepsWhitespace()
	{
		$control = new TPre();
		$control->getControls()->add("  a\n\tb  ");
		$this->assertSame("<pre>  a\n\tb  </pre>", $this->render($control));
	}

	public function testTextArtRendersImageRoleAndLabel()
	{
		$control = new TPre();
		$control->setRole('img');
		$control->getAria()->add('aria-label', 'A cat');
		$control->getControls()->add('=^.^=');
		$this->assertSame('<pre role="img" aria-label="A cat">=^.^=</pre>', $this->render($control));
	}
}
