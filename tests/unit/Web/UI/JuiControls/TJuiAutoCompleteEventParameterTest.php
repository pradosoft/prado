<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\JuiControls\TJuiAutoCompleteEventParameter;

/**
 * Unit tests for {@see TJuiAutoCompleteEventParameter}.
 */
class TJuiAutoCompleteEventParameterTest extends TestCase
{
	public function testTokenIsTheCallbackParameter(): void
	{
		$param = new TJuiAutoCompleteEventParameter(null, 'ab');
		$this->assertSame('ab', $param->getToken());
		$this->assertSame(-1, $param->getSelectedIndex());
	}

	public function testSelectedIndex(): void
	{
		$param = new TJuiAutoCompleteEventParameter(null, null, 2);
		$this->assertSame(2, $param->getSelectedIndex());
		$this->assertNull($param->getToken());
	}
}
