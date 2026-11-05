<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TRangeValidationDataType;
use Prado\Web\UI\WebControls\TRangeValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TRangeValidatorTest extends TestCase
{
	private TTextBox $input;
	private TRangeValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TRangeValidator();
		$this->validator->setControlToValidate('input');
		$page->getControls()->add($this->input);
		$page->getControls()->add($this->validator);
	}

	private function valid(string $value): bool
	{
		$this->input->setText($value);
		return PradoUnit::invoke($this->validator, 'evaluateIsValid');
	}

	private function configure(string $dataType, string $min = '', string $max = '', string $step = ''): void
	{
		$this->validator->setDataType($dataType);
		$this->validator->setMinValue($min);
		$this->validator->setMaxValue($max);
		$this->validator->setStep($step);
	}

	public function testStepDefaultsToEmpty()
	{
		$this->assertSame('', $this->validator->getStep());
		$this->validator->setStep(' 0.5 ');
		$this->assertSame('0.5', $this->validator->getStep());
	}

	public function testEmptyValueIsValid()
	{
		$this->configure(TRangeValidationDataType::Week, '2026-W01', '2026-W10', '2');
		$this->assertTrue($this->valid(''));
	}

	public function testIntegerStep()
	{
		$this->configure(TRangeValidationDataType::Integer, '1', '20', '3');
		$this->assertTrue($this->valid('1'));
		$this->assertTrue($this->valid('7'));
		$this->assertFalse($this->valid('8'));
		$this->assertFalse($this->valid('22'));
	}

	public function testStepWithoutMinCountsFromZero()
	{
		$this->configure(TRangeValidationDataType::Integer, '', '', '5');
		$this->assertTrue($this->valid('-10'));
		$this->assertFalse($this->valid('12'));
	}

	public function testFloatStep()
	{
		$this->configure(TRangeValidationDataType::Float, '0', '1', '0.1');
		$this->assertTrue($this->valid('0.3'));
		$this->assertTrue($this->valid('0.7'));
		$this->assertFalse($this->valid('0.35'));
	}

	public function testStepAnyOrInvalidChecksNoStep()
	{
		foreach (['any', '0', '-1', 'x'] as $step) {
			$this->configure(TRangeValidationDataType::Integer, '0', '10', $step);
			$this->assertTrue($this->valid('3'), $step);
		}
	}

	public function testDateStepInDays()
	{
		$this->configure(TRangeValidationDataType::Date, '2026-01-01', '2026-12-31', '7');
		$this->validator->setDateFormat('yyyy-MM-dd');
		$this->assertTrue($this->valid('2026-01-08'));
		$this->assertTrue($this->valid('2026-03-12'));
		$this->assertFalse($this->valid('2026-01-09'));
	}

	public function testTime()
	{
		$this->configure(TRangeValidationDataType::Time, '09:00', '17:00', '1800');
		$this->assertTrue($this->valid('09:30'));
		$this->assertTrue($this->valid('17:00:00'));
		$this->assertFalse($this->valid('09:15'));
		$this->assertFalse($this->valid('08:30'));
		$this->assertFalse($this->valid('17:30'));
		$this->assertFalse($this->valid('9:30'));
		$this->assertFalse($this->valid('24:00'));
	}

	public function testTimeFractionalSeconds()
	{
		$this->configure(TRangeValidationDataType::Time, '', '', '0.5');
		$this->assertTrue($this->valid('10:00:01.500'));
		$this->assertFalse($this->valid('10:00:01.250'));
	}

	public function testMonth()
	{
		$this->configure(TRangeValidationDataType::Month, '2026-01', '2026-12', '3');
		$this->assertTrue($this->valid('2026-04'));
		$this->assertFalse($this->valid('2026-05'));
		$this->assertFalse($this->valid('2027-01'));
		$this->assertFalse($this->valid('2026-13'));
		$this->assertFalse($this->valid('2026-4'));
	}

	public function testWeek()
	{
		$this->configure(TRangeValidationDataType::Week, '2026-W01', '2026-W53', '2');
		$this->assertTrue($this->valid('2026-W03'));
		$this->assertTrue($this->valid('2026-W53'));
		$this->assertFalse($this->valid('2026-W04'));
		$this->configure(TRangeValidationDataType::Week);
		$this->assertFalse($this->valid('2025-W53'));
		$this->assertTrue($this->valid('2020-W53'));
		$this->assertFalse($this->valid('2026-W00'));
	}

	public function testWeekStepCountsFrom1970W01()
	{
		$this->configure(TRangeValidationDataType::Week, '', '', '2');
		$this->assertTrue($this->valid('1970-W03'));
		$this->assertFalse($this->valid('1970-W02'));
	}

	public function testDateTimeLocal()
	{
		$this->configure(TRangeValidationDataType::DateTimeLocal, '2026-10-01T08:00', '2026-10-31T18:00', '900');
		$this->assertTrue($this->valid('2026-10-15T09:15'));
		$this->assertTrue($this->valid('2026-10-15 09:15:00'));
		$this->assertFalse($this->valid('2026-10-15T09:20'));
		$this->assertFalse($this->valid('2026-11-01T09:00'));
		$this->assertFalse($this->valid('2026-02-30T09:00'));
		$this->assertFalse($this->valid('2026-10-15'));
	}

	public function testStrictComparisonWithHtmlType()
	{
		$this->configure(TRangeValidationDataType::Month, '2026-01', '2026-03');
		$this->validator->setStrictComparison(true);
		$this->assertFalse($this->valid('2026-01'));
		$this->assertTrue($this->valid('2026-02'));
	}

	public function testInvalidRangeBoundIsInvalid()
	{
		$this->configure(TRangeValidationDataType::Time, 'noon');
		$this->assertFalse($this->valid('12:00'));
	}
}
