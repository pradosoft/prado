<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TAddress;

class TAddressTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TAddress();
	}

	protected function defaultTagName(): string
	{
		return 'address';
	}
}
