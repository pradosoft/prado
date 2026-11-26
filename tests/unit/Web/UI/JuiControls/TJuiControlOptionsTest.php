<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\JuiControls\TJuiControlOptions;
use Prado\Web\UI\JuiControls\TJuiDatePicker;
use Prado\Web\UI\JuiControls\TJuiDraggable;
use Prado\Web\UI\JuiControls\TJuiEventParameter;
use Prado\Web\UI\JuiControls\TJuiProgressbar;
use Prado\Web\UI\WebControls\TPanel;

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

	public function testConstructorRejectsAControlWithoutJuiOptions(): void
	{
		$panel = new TPanel();
		$panel->setID('Plain');
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('Plain');
		new TJuiControlOptions($panel);
	}

	public function testSetControlRebindsTheOptions(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$other = new TJuiDraggable();
		$other->setID('Other');
		$other->attachEventHandler('OnStop', fn () => null);
		$options->setControl($other);
		$this->assertArrayHasKey('stop', $options->toArray());
	}

	/**
	 * A string value, as a template gives it, is converted to the JavaScript type it spells.
	 */
	public static function stringValueProvider(): array
	{
		return [
			'null' => ['NULL', null],
			'true' => ['True', true],
			'false' => ['false', false],
			'integer' => ['12', 12],
			'float' => ['0.5', 0.5],
			'plain string' => ['parent', 'parent'],
		];
	}

	/**
	 * @dataProvider stringValueProvider
	 * @param string $value
	 * @param mixed $expected
	 */
	public function testSetConvertsAStringValue(string $value, mixed $expected): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$options->containment = $value;
		$this->assertSame(['containment' => $expected], $options->toArray());
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

	public function testSetMakesAFunctionStringALiteral(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$options->helper = 'Function() { return 1; }';
		$helper = $options->toArray()['helper'];
		$this->assertInstanceOf(TJavaScriptLiteral::class, $helper);
		$this->assertSame('Function() { return 1; }', (string) $helper);
	}

	public function testSetMatchesTheOptionNameWithoutCase(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$options->ZINDEX = '5';
		$this->assertSame(['zIndex' => 5], $options->toArray());
	}

	public function testSetRejectsAnUnknownOption(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage("'bogus' is not a valid option for control 'Drag'");
		$options->bogus = '1';
	}

	public function testGetReturnsNullForAnOptionNotSet(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$this->assertNull($options->axis);
		$this->assertNull($options->bogus);
		$this->assertSame([], $options->toArray());
	}

	public function testGetMatchesTheOptionNameWithoutCase(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$options->axis = 'x';
		$this->assertSame('x', $options->AXIS);
	}

	/**
	 * Progress bar options are `disabled`, `max`, and `value`: the first, a middle, and the last valid option.
	 */
	public static function getProvider(): array
	{
		return [
			'none set, first' => [[], 'disabled', null],
			'none set, last' => [[], 'value', null],
			'none set, unknown' => [[], 'bogus', null],
			'first set, first' => [['disabled' => false], 'disabled', false],
			'first set, last' => [['disabled' => true], 'value', null],
			'last set, last' => [['value' => 3], 'value', 3],
			'last set, middle' => [['value' => 3], 'max', null],
			'set, unknown' => [['max' => 9], 'bogus', null],
		];
	}

	/**
	 * @dataProvider getProvider
	 * @param array $set
	 * @param string $name
	 * @param mixed $expected
	 */
	public function testGetFindsTheOptionSet(array $set, string $name, mixed $expected): void
	{
		$progressbar = new TJuiProgressbar();
		$options = new TJuiControlOptions($progressbar);
		foreach ($set as $option => $value) {
			$options->$option = $value;
		}
		$this->assertSame($expected, $options->$name);
	}

	/**
	 * A control without events, such as the date picker, has only its options.
	 */
	public function testToArrayOfAControlWithoutEvents(): void
	{
		$options = new TJuiControlOptions(new TJuiDatePicker());
		$this->assertSame([], $options->toArray());
		$options->dateFormat = 'yy-mm-dd';
		$this->assertSame(['dateFormat' => 'yy-mm-dd'], $options->toArray());
	}

	public function testToArrayWithoutHandlersHasOnlyTheOptions(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$this->assertSame([], $options->toArray());
		$options->axis = 'y';
		$this->assertSame(['axis' => 'y'], $options->toArray());
	}

	public function testToArrayWithAHandlerAndNoOptions(): void
	{
		$control = $this->newDraggable();
		$control->attachEventHandler('OnCreate', fn () => null);
		$this->assertSame(['create'], array_keys((new TJuiControlOptions($control))->toArray()));
	}

	public function testToArrayWithAHandlerForEveryEvent(): void
	{
		$control = $this->newDraggable();
		foreach ($control->getValidEvents() as $event) {
			$control->attachEventHandler('On' . $event, fn () => null);
		}
		$options = new TJuiControlOptions($control);
		$this->assertSame(['create', 'drag', 'start', 'stop'], array_keys($options->toArray()));
		$options->axis = 'x';
		$this->assertSame(['axis', 'create', 'drag', 'start', 'stop'], array_keys($options->toArray()));
	}

	public function testSleepKeepsOnlyTheOptions(): void
	{
		$options = new TJuiControlOptions($this->newDraggable());
		$options->axis = 'y';
		$this->assertSame(['_options'], $options->__sleep());
		$copy = unserialize(serialize($options));
		$copy->setControl($this->newDraggable());
		$this->assertSame(['axis' => 'y'], $copy->toArray());
	}

	public function testToArrayAddsACallbackForEachHandledEvent(): void
	{
		$control = $this->newDraggable();
		$control->attachEventHandler('OnStart', fn () => null);
		$control->attachEventHandler('OnStop', fn () => null);
		$options = new TJuiControlOptions($control);
		$options->axis = 'x';
		$array = $options->toArray();
		$this->assertSame(['axis', 'start', 'stop'], array_keys($array));
		$this->assertSame(
			'function( event, ui ) { Prado.JuiCallback("Drag", "stop", event, ui, this); }',
			(string) $array['stop']
		);
	}

	private function callbackParameter(mixed $data): TCallbackEventParameter
	{
		return new TCallbackEventParameter(null, $data);
	}

	public function testRaiseCallbackEventRaisesTheNamedEvent(): void
	{
		$control = $this->newDraggable();
		$received = [];
		$control->attachEventHandler('OnStop', function ($sender, $param) use (&$received) {
			$received[] = $param;
		});
		$options = new TJuiControlOptions($control);
		$options->raiseCallbackEvent($this->callbackParameter((object) ['event' => 'stop', 'ui' => (object) ['top' => 3]]));
		$this->assertCount(1, $received);
		$this->assertInstanceOf(TJuiEventParameter::class, $received[0]);
		$this->assertEquals((object) ['top' => 3], $received[0]->getCallbackParameter());
	}

	public function testRaiseCallbackEventPassesNullWithoutUi(): void
	{
		$control = $this->newDraggable();
		$received = [];
		$control->attachEventHandler('OnStop', function ($sender, $param) use (&$received) {
			$received[] = $param->getCallbackParameter();
		});
		(new TJuiControlOptions($control))->raiseCallbackEvent($this->callbackParameter((object) ['event' => 'stop']));
		$this->assertSame([null], $received);
	}

	public function testRaiseCallbackEventIgnoresAnEventWithoutHandler(): void
	{
		$control = $this->newDraggable();
		$received = [];
		$control->attachEventHandler('OnStop', function () use (&$received) {
			$received[] = 'stop';
		});
		$options = new TJuiControlOptions($control);
		$options->raiseCallbackEvent($this->callbackParameter((object) ['event' => 'drag']));
		$options->raiseCallbackEvent($this->callbackParameter((object) ['ui' => null]));
		$options->raiseCallbackEvent($this->callbackParameter('stop'));
		$this->assertSame([], $received);
	}
}
