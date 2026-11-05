<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TKbd;

class TKbdTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TKbd();
	}

	protected function defaultTagName(): string
	{
		return 'kbd';
	}

	public function testRendersNestedKeyCombination()
	{
		$control = new TKbd();
		$ctrl = new TKbd();
		$ctrl->getControls()->add('Ctrl');
		$c = new TKbd();
		$c->getControls()->add('C');
		$control->getControls()->add($ctrl);
		$control->getControls()->add('+');
		$control->getControls()->add($c);
		$this->assertSame('<kbd><kbd>Ctrl</kbd>+<kbd>C</kbd></kbd>', $this->render($control));
	}
}
