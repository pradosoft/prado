<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\JuiControls\TJuiControlOptions;
use Prado\Web\UI\JuiControls\TJuiDraggable;

/**
 * Unit tests for {@see TJuiControlOptions}.
 */
class TJuiControlOptionsTest extends TestCase
{
	private function newDraggable(): TJuiDraggable
	{
		$control = new TJuiDraggable();
		$control->setID('Drag');
		return $control;
	}

	/**
	 * A value set from PHP that is not a string is stored as is.
	 */
	public static function nonStringValueProvider(): array
	{
		return [
			'null' => [null],
			'array' => [['top' => 0, 'left' => 10]],
			'integer' => [7],
			'boolean' => [false],
			'literal' => [new TJavaScriptLiteral('function() {}')],
		];
	}

	/**
	 * @dataProvider nonStringValueProvider
	 * @param mixed $value
	 */
	public function testSetKeepsANonStringValue(mixed $value): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$options->cursorAt = $value;
		$this->assertSame(['cursorAt' => $value], $options->toArray());
	}
}
