<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TFormPageTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TMultibyteString;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TRangeValidationDataType;
use Prado\Web\UI\WebControls\TRangeValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TRangeValidatorTest extends TestCase
{
	use TFormPageTrait;

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

	public function testStringLengthCountsCharactersWithAndWithoutMbstring()
	{
		$this->configure(TRangeValidationDataType::StringLength, '4', '6');
		$mbstring = PradoUnit::getStaticProp(TMultibyteString::class, '_mbstring');
		try {
			foreach ([true, false] as $flag) {
				PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $flag);
				$this->assertTrue($this->valid('héllo'));
				$this->assertTrue($this->valid('こんにちは'));
				$this->assertFalse($this->valid('こんにちは世界'));
				$this->assertFalse($this->valid('héé'));
			}
			$this->validator->setCharset('ISO-8859-1');
			$this->assertTrue($this->valid("h\xe9llo"));
		} finally {
			PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		}
	}

	public function testStringLengthOfAnotherCharsetWithAndWithoutIconv()
	{
		$this->configure(TRangeValidationDataType::StringLength, '4', '5');
		$this->validator->setCharset('ISO-8859-1');
		$static = PradoUnit::snapshotStatic(\Prado\Util\TUtf8Converter::class, ['_iconv']);
		try {
			foreach ([null, false] as $iconv) {
				PradoUnit::setStaticProp(\Prado\Util\TUtf8Converter::class, '_iconv', $iconv);
				$this->assertTrue($this->valid("h\xe9llo"));
				$this->assertFalse($this->valid("h\xe9ll\xe9s"));
				$this->assertFalse($this->valid("\xe9t\xe9"));
			}
		} finally {
			PradoUnit::restoreStatic(\Prado\Util\TUtf8Converter::class, $static);
		}
	}

	public function testStringLengthUsesTheGlobalizationCharset()
	{
		$this->configure(TRangeValidationDataType::StringLength, '4', '5');
		$this->validator->setCharset('');
		$app = \Prado::getApplication();
		$original = PradoUnit::getProp($app, '_globalization');
		try {
			$g = new \Prado\I18N\TGlobalization();
			$g->setCharset('ISO-8859-1');
			PradoUnit::setProp($app, '_globalization', $g);
			$this->assertTrue($this->valid("h\xe9llo"), 'The application charset ISO-8859-1 counts the byte as one character.');

			$g->setCharset('');
			$this->assertTrue($this->valid('héllo'), 'An empty application charset is UTF-8.');
		} finally {
			PradoUnit::setProp($app, '_globalization', $original);
		}
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

	public function testDateRejectsAValueThatIsNotADate()
	{
		$this->configure(TRangeValidationDataType::Date, '', '2030-01-01');
		$this->assertFalse($this->valid('not a date'));
		$this->assertTrue($this->valid('2024-06-15'));

		$this->validator->setDateFormat('d/M/yyyy');
		$this->configure(TRangeValidationDataType::Date, '', '1/1/2030');
		$this->assertFalse($this->valid('not a date'));
		$this->assertFalse($this->valid('2024-06-15'));
		$this->assertTrue($this->valid('15/6/2024'));
	}

	public function testDateWithoutBoundsChecksTheValue()
	{
		$this->configure(TRangeValidationDataType::Date);
		$this->assertTrue($this->valid('2024-06-15'));
		$this->assertFalse($this->valid('not a date'));
	}

	public function testDateIgnoresABoundThatIsNotADate()
	{
		$this->configure(TRangeValidationDataType::Date, 'not a date', '2030-01-01');
		$this->assertTrue($this->valid('2024-06-15'));
		$this->assertFalse($this->valid('2031-01-01'));

		$this->configure(TRangeValidationDataType::Date, '2020-01-01', 'not a date');
		$this->assertTrue($this->valid('2031-01-01'));
		$this->assertFalse($this->valid('2019-01-01'));
	}

	public function testDateWithoutDateFormatBefore1970()
	{
		$this->configure(TRangeValidationDataType::Date, '1960-01-01', '1969-12-31');
		$this->assertTrue($this->valid('1965-06-15'));
		$this->assertFalse($this->valid('1970-01-02'));
		$this->assertFalse($this->valid('1959-12-31'));
	}

	public function testDateStrictComparison()
	{
		$this->configure(TRangeValidationDataType::Date, '2024-01-01', '2024-12-31');
		$this->validator->setStrictComparison(true);
		$this->assertFalse($this->valid('2024-01-01'));
		$this->assertFalse($this->valid('2024-12-31'));
		$this->assertTrue($this->valid('2024-06-15'));
	}

	public function testDateOfADatePicker()
	{
		$page = new TPage();
		$picker = new TDatePicker();
		$picker->setID('picker');
		$picker->setDateFormat('d/M/yyyy');
		$page->getControls()->add($picker);
		$validator = new TRangeValidator();
		$validator->setControlToValidate('picker');
		$validator->setDataType(TRangeValidationDataType::Date);
		$validator->setDateFormat('d/M/yyyy');
		$validator->setMinValue('1/4/2024');
		$validator->setMaxValue('30/4/2024');
		$validator->setStep('7');
		$page->getControls()->add($validator);

		$picker->setText('15/4/2024');
		$this->assertIsInt($picker->getValidationPropertyValue());
		$this->assertTrue(PradoUnit::invoke($validator, 'evaluateIsValid'));
		$picker->setText('16/4/2024');
		$this->assertFalse(PradoUnit::invoke($validator, 'evaluateIsValid'), 'The step counts from MinValue.');
		$picker->setText('6/5/2024');
		$this->assertFalse(PradoUnit::invoke($validator, 'evaluateIsValid'));
	}

	public function testStringComparesInByteOrder()
	{
		$this->configure(TRangeValidationDataType::String, 'b', 'd');
		$this->assertTrue($this->valid('b'));
		$this->assertTrue($this->valid('cat'));
		$this->assertTrue($this->valid('d'));
		$this->assertFalse($this->valid('a'));
		$this->assertFalse($this->valid('dog'));
		$this->assertFalse($this->valid('B'), 'Uppercase sorts before lowercase.');

		$this->validator->setStrictComparison(true);
		$this->assertFalse($this->valid('b'));
		$this->assertFalse($this->valid('d'));

		$this->configure(TRangeValidationDataType::String);
		$this->assertTrue($this->valid('anything'));
	}

	public function testStringHasNoSteps()
	{
		$this->configure(TRangeValidationDataType::String, 'a', 'z', '2');
		$this->assertTrue($this->valid('m'));
		$this->assertNull(PradoUnit::invoke($this->validator, 'convertStepValue', 'm'));
	}

	public function testStepOfAValueThatDoesNotConvertIsInvalid()
	{
		$this->configure(TRangeValidationDataType::Integer, '', '', '2');
		$this->assertFalse(PradoUnit::invoke($this->validator, 'isValidStep', 'abc'));
		$this->assertTrue(PradoUnit::invoke($this->validator, 'isValidStep', '4'));
	}

	public function testStringLengthWithoutGlobalizationIsUtf8()
	{
		$validator = new class () extends TRangeValidator {
			public function getApplication()
			{
				return new class () {
					public function getGlobalization()
					{
						return null;
					}
				};
			}
		};
		$validator->setDataType(TRangeValidationDataType::StringLength);
		$validator->setMinValue('5');
		$validator->setMaxValue('5');
		$this->assertTrue(PradoUnit::invoke($validator, 'isValidStringLength', 'héllo'));
		$this->assertFalse(PradoUnit::invoke($validator, 'isValidStringLength', 'héllo!'));
	}

	public function testClientScriptOptions()
	{
		$this->createFormPage($input = new TTextBox(), $validator = new TRangeValidator());
		$input->setID('input');
		$validator->setID('range');
		$validator->setControlToValidate('input');
		$validator->setDataType(TRangeValidationDataType::Date);
		$validator->setMinValue('2024-01-01');
		$validator->setMaxValue('2024-12-31');

		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('2024-01-01', $options['MinValue']);
		$this->assertSame('2024-12-31', $options['MaxValue']);
		$this->assertSame(TRangeValidationDataType::Date, $options['DataType']);
		$this->assertFalse($options['StrictComparison']);
		$this->assertArrayNotHasKey('DateFormat', $options);
		$this->assertArrayNotHasKey('Step', $options);

		$validator->setDateFormat('yyyy-MM-dd');
		$validator->setStep('7');
		$validator->setStrictComparison(true);
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('yyyy-MM-dd', $options['DateFormat']);
		$this->assertSame('7', $options['Step']);
		$this->assertTrue($options['StrictComparison']);
		$this->assertSame('Prado.WebUI.TRangeValidator', PradoUnit::invoke($validator, 'getClientClassName'));
	}

	public function testDateStepWithAMinValueThatIsNotADateCountsFrom1970()
	{
		$this->configure(TRangeValidationDataType::Date, 'not a date', '', '2');
		$this->assertTrue($this->valid('1970-01-03'));
		$this->assertFalse($this->valid('1970-01-04'));
	}

	public static function provideBounds(): array
	{
		$cases = [
			TRangeValidationDataType::Integer => ['5', '0', '10', 'x', '1', '9'],
			TRangeValidationDataType::Float => ['5.5', '0.5', '9.5', 'x', '1.5', '9'],
			TRangeValidationDataType::String => ['m', 'a', 'z', null, 'b', 'y'],
			TRangeValidationDataType::Time => ['12:00', '09:00', '14:00', 'noon', '10:00', '13:00'],
		];
		$data = [];
		foreach ($cases as $type => [$in, $below, $above, $malformed, $min, $max]) {
			$rows = [
				'no bounds' => ['', '', $in, true],
				'min, in range' => [$min, '', $in, true],
				'min, below' => [$min, '', $below, false],
				'max, in range' => ['', $max, $in, true],
				'max, above' => ['', $max, $above, false],
				'both, in range' => [$min, $max, $in, true],
				'both, below' => [$min, $max, $below, false],
				'both, above' => [$min, $max, $above, false],
			];
			if ($malformed !== null) {
				$rows += [
					'no bounds, malformed' => ['', '', $malformed, false],
					'min, malformed' => [$min, '', $malformed, false],
					'max, malformed' => ['', $max, $malformed, false],
					'both, malformed' => [$min, $max, $malformed, false],
				];
			}
			foreach ($rows as $name => $row) {
				$data["$type, $name"] = [$type, ...$row];
			}
		}
		$data['Time, min malformed'] = [TRangeValidationDataType::Time, 'noon', '', '12:00', false];
		$data['Time, max malformed'] = [TRangeValidationDataType::Time, '', 'noon', '12:00', false];
		$data['Time, min malformed, max'] = [TRangeValidationDataType::Time, 'noon', '13:00', '12:00', false];
		$data['Time, min, max malformed'] = [TRangeValidationDataType::Time, '10:00', 'noon', '12:00', false];
		return $data;
	}

	/**
	 * @param string $dataType the data type.
	 * @param string $min the MinValue.
	 * @param string $max the MaxValue.
	 * @param string $value the input.
	 * @param bool $expected whether the input is valid.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideBounds')]
	public function testBounds(string $dataType, string $min, string $max, string $value, bool $expected)
	{
		$this->configure($dataType, $min, $max);
		$this->assertSame($expected, $this->valid($value));
	}

	public function testClientScriptOptionsWithDateFormatOrStep()
	{
		$this->createFormPage($input = new TTextBox(), $validator = new TRangeValidator());
		$input->setID('input');
		$validator->setControlToValidate('input');
		$validator->setDateFormat('yyyy-MM-dd');
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('yyyy-MM-dd', $options['DateFormat']);
		$this->assertArrayNotHasKey('Step', $options);

		$validator->setDateFormat('');
		$validator->setStep('2');
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertArrayNotHasKey('DateFormat', $options);
		$this->assertSame('2', $options['Step']);
	}
}
