<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TBdo;
use Prado\Exceptions\TInvalidDataValueException;

class TBdoTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TBdo();
	}

	protected function defaultTagName(): string
	{
		return 'bdo';
	}

	public function testDirDefaultsToLtr()
	{
		$control = new TBdo();
		$this->assertSame('ltr', $control->getDir());
		$this->assertSame('<bdo dir="ltr"></bdo>', $this->render($control));
	}

	public function testDirRtl()
	{
		$control = new TBdo();
		$control->setDir(' RTL ');
		$this->assertSame('rtl', $control->getDir());
		$this->assertSame('<bdo dir="rtl"></bdo>', $this->render($control));
	}

	public function testEmptyDirRestoresLtr()
	{
		$control = new TBdo();
		$control->setDir('rtl');
		$control->setDir('');
		$this->assertSame('ltr', $control->getDir());
	}

	public function testDirAutoThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TBdo())->setDir('auto');
	}
}
