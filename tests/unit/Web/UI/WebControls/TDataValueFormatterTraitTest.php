<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TBehavior;
use Prado\Web\UI\WebControls\TBoundColumn;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\THyperLinkColumn;

class TDataValueFormatterTraitTestRecorder extends TBehavior
{
	public array $expressions = [];

	public function dyEvaluateExpressionFilter($expression, $chain)
	{
		$this->expressions[] = $expression;
		return $chain->dyEvaluateExpressionFilter($expression);
	}
}

class TDataValueFormatterTraitTestFormatter
{
	public function __invoke($value)
	{
		return "[$value]";
	}

	public function upper($value)
	{
		return strtoupper($value);
	}
}

class TDataValueFormatterTraitTest extends TestCase
{
	private function format($component, $format, $value)
	{
		return PradoUnit::invoke($component, 'formatDataValue', $format, $value);
	}

	public function testFormatStrings()
	{
		foreach ([new TDropDownList(), new TBoundColumn()] as $component) {
			$this->assertSame('12', $this->format($component, '', 12));
			$this->assertSame('Item: 12', $this->format($component, 'Item: %s', 12));
			$this->assertSame('strtoupper', $this->format($component, 'strtoupper', 'abc'));
			$this->assertSame(24, $this->format($component, '#{0} * 2', 12));
			$this->assertSame('ABC', $this->format($component, '#strtoupper($value)', 'abc'));
			$this->assertSame($component::class, $this->format($component, '#$this::class', 'abc'));
		}
	}

	public function testExpressionPassesThroughEvaluateExpression()
	{
		$list = new TDropDownList();
		$recorder = new TDataValueFormatterTraitTestRecorder();
		$list->attachBehavior('recorder', $recorder);
		$this->assertSame('ABC', $this->format($list, '#strtoupper({0})', 'abc'));
		$this->assertSame(['fn ($value) => strtoupper($value)'], $recorder->expressions);
	}

	public function testInvalidExpression()
	{
		foreach ([[new TDropDownList(), 'listcontrol_expression_invalid'], [new TBoundColumn(), 'datagridcolumn_expression_invalid']] as [$component, $code]) {
			foreach (['#{0} +', '#undefined_function_xyz({0})'] as $format) {
				try {
					$this->format($component, $format, 1);
					$this->fail("Expected TInvalidDataValueException for $format");
				} catch (TInvalidDataValueException $e) {
					$this->assertSame($code, $e->getErrorCode());
					$this->assertInstanceOf(\Throwable::class, $e->getPrevious());
				}
			}
		}
	}

	public function testCallableFormats()
	{
		$formatter = new TDataValueFormatterTraitTestFormatter();
		foreach ([new TDropDownList(), new TBoundColumn()] as $component) {
			$this->assertSame(13, $this->format($component, fn ($value) => $value + 1, 12));
			$this->assertSame('[12]', $this->format($component, $formatter, 12));
			$this->assertSame('ABC', $this->format($component, [$formatter, 'upper'], 'abc'));
		}
	}

	public function testCallableIsNotKeptInViewState()
	{
		$closure = fn ($value) => "<$value>";

		$list = new TDropDownList();
		$list->setDataTextFormatString($closure);
		$this->assertSame($closure, $list->getDataTextFormatString());
		$this->assertSame('', $list->getViewState('DataTextFormatString', ''));
		$list->setDataTextFormatString('%s!');
		$this->assertSame('%s!', $list->getDataTextFormatString());

		$column = new THyperLinkColumn();
		$column->setDataTextFormatString($closure);
		$column->setDataNavigateUrlFormatString('/item/%s');
		$this->assertSame($closure, $column->getDataTextFormatString());
		$this->assertSame('/item/%s', $column->getDataNavigateUrlFormatString());
		$this->assertSame(['DataNavigateUrlFormatString' => '/item/%s'], $column->saveState());
		$column->setDataTextFormatString('');
		$this->assertSame('', $column->getDataTextFormatString());
	}

	public function testListControlBindsWithCallable()
	{
		$list = new TDropDownList();
		$list->setDataTextFormatString(fn ($value) => strtoupper($value));
		$list->setDataSource(['a' => 'apple', 'b' => 'banana']);
		$list->dataBind();
		$this->assertSame('APPLE', $list->getItems()->itemAt(0)->getText());
		$this->assertSame('BANANA', $list->getItems()->itemAt(1)->getText());
	}
}
