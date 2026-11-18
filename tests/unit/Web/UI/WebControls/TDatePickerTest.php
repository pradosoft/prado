<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TDatePickerInputMode;

/**
 * Tests {@see TDatePicker::getDateInputMode()} and its deprecated `InputMode` name.
 */
class TDatePickerTest extends \PHPUnit\Framework\TestCase
{
	public function testDateInputModeDefaultsToTextBox(): void
	{
		$picker = new TDatePicker();
		$this->assertSame(TDatePickerInputMode::TextBox, $picker->getDateInputMode());
	}

	public function testSetDateInputMode(): void
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$this->assertSame(TDatePickerInputMode::DropDownList, $picker->getDateInputMode());
	}

	public function testSetDateInputModeRejectsInvalidValue(): void
	{
		$picker = new TDatePicker();
		$this->expectException(TInvalidDataValueException::class);
		$picker->setDateInputMode('Calendar');
	}

	public function testInputModeIsAliasOfDateInputMode(): void
	{
		$picker = new TDatePicker();
		$picker->setInputMode(TDatePickerInputMode::DropDownList);
		$this->assertSame(TDatePickerInputMode::DropDownList, $picker->getDateInputMode());

		$picker->setDateInputMode(TDatePickerInputMode::TextBox);
		$this->assertSame(TDatePickerInputMode::TextBox, $picker->getInputMode());
	}

	public function testPropertyNamesShareState(): void
	{
		$picker = new TDatePicker();
		$picker->DateInputMode = TDatePickerInputMode::DropDownList;
		$this->assertSame(TDatePickerInputMode::DropDownList, $picker->InputMode);
	}
}
