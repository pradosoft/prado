<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TDataValue;

class TDataValueTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TDataValue();
	}

	protected function defaultTagName(): string
	{
		return 'data';
	}

	public function testValueAlwaysRenders()
	{
		$control = new TDataValue();
		$this->assertSame('', $control->getValue());
		$this->assertSame('<data value=""></data>', $this->render($control));
	}

	public function testValueRenders()
	{
		$control = new TDataValue();
		$control->setValue(398);
		$control->getControls()->add('Mini Ketchup');
		$this->assertSame('398', $control->getValue());
		$this->assertSame('<data value="398">Mini Ketchup</data>', $this->render($control));
	}
}
