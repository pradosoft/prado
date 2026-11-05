<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TBdi;

class TBdiTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TBdi();
	}

	protected function defaultTagName(): string
	{
		return 'bdi';
	}

	public function testDirRendersWhenSet()
	{
		$control = new TBdi();
		$this->assertSame('<bdi></bdi>', $this->render($control));
		$control->setDir('rtl');
		$this->assertSame('<bdi dir="rtl"></bdi>', $this->render($control));
	}
}
