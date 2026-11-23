<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDataTypeValidator;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TValidationDataType;

class TDataTypeValidatorTest extends TestCase
{
	private TTextBox $box;

	/**
	 * Builds a page with a text box and a validator of the data type.
	 */
	private function createValidator(string $dataType, string $dateFormat = ''): TDataTypeValidator
	{
		$page = new TPage();
		$this->box = new TTextBox();
		$this->box->setID('Box');
		$page->getControls()->add($this->box);
		$validator = new TDataTypeValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Box');
		$validator->setDataType($dataType);
		$validator->setDateFormat($dateFormat);
		$page->getControls()->add($validator);
		return $validator;
	}

	public static function provideValues(): array
	{
		return [
			'integer' => [TValidationDataType::Integer, '', ' -42 ', true],
			'integer, fraction' => [TValidationDataType::Integer, '', '4.2', false],
			'float' => [TValidationDataType::Float, '', '+.5', true],
			'float, exponent' => [TValidationDataType::Float, '', '1e5', false],
			'float, trailing point' => [TValidationDataType::Float, '', '5.', false],
			'date, before 1970' => [TValidationDataType::Date, '', '1969-12-31', true],
			'date, epoch' => [TValidationDataType::Date, '', '1970-01-01 00:00:00 UTC', true],
			'date, not a date' => [TValidationDataType::Date, '', 'not a date', false],
			'date format, before 1970' => [TValidationDataType::Date, 'yyyy-MM-dd', '1969-12-31', true],
			'date format, wrong format' => [TValidationDataType::Date, 'yyyy-MM-dd', '31/12/1969', false],
			'string' => [TValidationDataType::String, '', 'anything', true],
			'empty' => [TValidationDataType::Integer, '', '', true],
		];
	}

	#[DataProvider('provideValues')]
	public function testChecksWithTheClientRules(string $dataType, string $dateFormat, string $value, bool $expected)
	{
		$validator = $this->createValidator($dataType, $dateFormat);
		$this->box->setText($value);
		$this->assertSame($expected, $validator->evaluateIsValid());
	}

	public function testChecksADatePickerTimestamp()
	{
		$page = new TPage();
		$picker = new TDatePicker();
		$picker->setID('Picker');
		$picker->setDateFormat('d/M/yyyy');
		$picker->setText('14/4/2024');
		$page->getControls()->add($picker);
		$validator = new TDataTypeValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Picker');
		$validator->setDataType(TValidationDataType::Date);
		$validator->setDateFormat('d/M/yyyy');
		$page->getControls()->add($validator);

		$this->assertIsInt($picker->getValidationPropertyValue());
		$this->assertTrue($validator->evaluateIsValid());
	}

	public function testConvert()
	{
		$this->assertSame(1713052800, TValidationDataType::convert(TValidationDataType::Date, 1713052800, 'd/M/yyyy'));
		$this->assertSame(-86400, TValidationDataType::convert(TValidationDataType::Date, -86400.5));
		$this->assertSame(42, TValidationDataType::convert(TValidationDataType::Integer, 42));
		$this->assertSame('42', TValidationDataType::convert(TValidationDataType::String, 42));
		$this->assertSame(-42, TValidationDataType::convert(TValidationDataType::Integer, ' -42 '));
		$this->assertNull(TValidationDataType::convert(TValidationDataType::Integer, '1e3'));
		$this->assertSame(0.5, TValidationDataType::convert(TValidationDataType::Float, '.5'));
		$this->assertNull(TValidationDataType::convert(TValidationDataType::Float, ''));
		$this->assertSame(-86400, TValidationDataType::convert(TValidationDataType::Date, '1969-12-31 00:00:00 UTC'));
		$this->assertNull(TValidationDataType::convert(TValidationDataType::Date, 'not a date'));
		$this->assertNull(TValidationDataType::convert(TValidationDataType::Date, 'x', 'yyyy-MM-dd'));
		$this->assertSame('1e3', TValidationDataType::convert(TValidationDataType::String, '1e3'));
	}
}
