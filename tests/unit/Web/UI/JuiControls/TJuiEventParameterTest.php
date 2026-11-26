<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\JuiControls\TJuiEventParameter;

/**
 * Unit tests for {@see TJuiEventParameter}.
 */
class TJuiEventParameterTest extends TestCase
{
	/**
	 * A property name without the `Control` suffix reads the component property.
	 */
	public function testPropertyWithoutControlSuffixReadsTheComponentProperty(): void
	{
		$data = (object) ['draggable' => 'Drag'];
		$param = new TJuiEventParameter(null, $data);
		$this->assertSame($data, $param->CallbackParameter);
	}
}
