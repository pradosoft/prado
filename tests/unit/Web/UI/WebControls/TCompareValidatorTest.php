<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCompareValidator;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TValidationCompareOperator;
use Prado\Web\UI\WebControls\TValidationDataType;

class TCompareValidatorTest extends TestCase
{
	private TTextBox $box;
	private TTextBox $other;

	/**
	 * Builds a page with two text boxes and a validator that compares the first.
	 */
	private function createValidator(string $dataType, string $operator = TValidationCompareOperator::Equal): TCompareValidator
	{
		$page = new TPage();
		$this->box = new TTextBox();
		$this->box->setID('Box');
		$page->getControls()->add($this->box);
		$this->other = new TTextBox();
		$this->other->setID('Other');
		$page->getControls()->add($this->other);
		$validator = new TCompareValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Box');
		$validator->setDataType($dataType);
		$validator->setOperator($operator);
		$page->getControls()->add($validator);
		return $validator;
	}

	private function isValid(TCompareValidator $validator, string $value, string $compareTo): bool
	{
		$this->box->setText($value);
		$validator->setValueToCompare($compareTo);
		return $validator->evaluateIsValid();
	}

	public static function provideComparisons(): array
	{
		return [
			'string, numeric strings differ' => [TValidationDataType::String, TValidationCompareOperator::Equal, '1e3', '1000', false],
			'string, leading zero differs' => [TValidationDataType::String, TValidationCompareOperator::Equal, '01', '1', false],
			'string, equal' => [TValidationDataType::String, TValidationCompareOperator::Equal, 'abc', 'abc', true],
			'string, not equal' => [TValidationDataType::String, TValidationCompareOperator::NotEqual, '1e3', '1000', true],
			'string, byte order not numeric' => [TValidationDataType::String, TValidationCompareOperator::GreaterThan, '9', '10', true],
			'string, less than' => [TValidationDataType::String, TValidationCompareOperator::LessThan, 'abc', 'abd', true],
			'string, less than or equal' => [TValidationDataType::String, TValidationCompareOperator::LessThanEqual, 'abc', 'abc', true],
			'string, greater than or equal' => [TValidationDataType::String, TValidationCompareOperator::GreaterThanEqual, 'abc', 'abd', false],
			'integer, equal' => [TValidationDataType::Integer, TValidationCompareOperator::Equal, ' 42 ', '42', true],
			'integer, greater than' => [TValidationDataType::Integer, TValidationCompareOperator::GreaterThan, '10', '9', true],
			'integer, input not an integer' => [TValidationDataType::Integer, TValidationCompareOperator::Equal, 'abc', '0', false],
			'integer, value to compare not an integer' => [TValidationDataType::Integer, TValidationCompareOperator::Equal, '42', 'abc', true],
			'float, equal' => [TValidationDataType::Float, TValidationCompareOperator::Equal, '1.50', '1.5', true],
			'float, exponent is not a float' => [TValidationDataType::Float, TValidationCompareOperator::Equal, '1e3', '1000', false],
			'date, before 1970' => [TValidationDataType::Date, TValidationCompareOperator::LessThan, '1969-12-31', '2024-01-01', true],
			'date, epoch' => [TValidationDataType::Date, TValidationCompareOperator::Equal, '1970-01-01 00:00:00 UTC', '1970-01-01 00:00:00 UTC', true],
			'date, input not a date' => [TValidationDataType::Date, TValidationCompareOperator::Equal, 'not a date', 'also not', false],
			'date, value to compare not a date' => [TValidationDataType::Date, TValidationCompareOperator::Equal, '2024-01-01', 'not a date', true],
		];
	}

	#[DataProvider('provideComparisons')]
	public function testComparesWithTheClientRules(string $dataType, string $operator, string $value, string $compareTo, bool $expected)
	{
		$this->assertSame($expected, $this->isValid($this->createValidator($dataType, $operator), $value, $compareTo));
	}

	public function testDateFormatRejectsAnInvalidDate()
	{
		$validator = $this->createValidator(TValidationDataType::Date, TValidationCompareOperator::LessThan);
		$validator->setDateFormat('yyyy-MM-dd');
		$this->assertTrue($this->isValid($validator, '1969-12-31', '2024-01-01'));
		$this->assertFalse($this->isValid($validator, '31/12/1969', '2024-01-01'));
		$this->assertTrue($this->isValid($validator, '2024-01-01', 'tomorrow'));
	}

	public function testEmptyControlToCompareFollowsTheDataType()
	{
		$validator = $this->createValidator(TValidationDataType::Integer);
		$validator->setControlToCompare('Other');
		$this->box->setText('42');
		$this->other->setText('');
		$this->assertTrue($validator->evaluateIsValid());

		$validator->setDataType(TValidationDataType::String);
		$this->assertFalse($validator->evaluateIsValid());
		$this->other->setText('42');
		$this->assertTrue($validator->evaluateIsValid());
	}

	public function testComparesDatePickerTimestamps()
	{
		$page = new TPage();
		$pickers = [];
		foreach (['Date1' => '14/4/2024', 'Date2' => '29/4/2024'] as $id => $text) {
			$pickers[$id] = new TDatePicker();
			$pickers[$id]->setID($id);
			$pickers[$id]->setDateFormat('d/M/yyyy');
			$pickers[$id]->setText($text);
			$page->getControls()->add($pickers[$id]);
		}
		$validator = new TCompareValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Date2');
		$validator->setControlToCompare('Date1');
		$validator->setOperator(TValidationCompareOperator::GreaterThan);
		$validator->setDataType(TValidationDataType::Date);
		$validator->setDateFormat('d/M/yyyy');
		$page->getControls()->add($validator);

		$this->assertIsInt($pickers['Date2']->getValidationPropertyValue());
		$this->assertTrue($validator->evaluateIsValid());
		$pickers['Date2']->setText('7/4/2024');
		$this->assertFalse($validator->evaluateIsValid());
	}

	public function testEmptyInputIsValid()
	{
		$this->assertTrue($this->isValid($this->createValidator(TValidationDataType::Integer), '', 'abc'));
	}
}
