<?php

use Prado\Exceptions\TNotSupportedException;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TDatePickerInputMode;
use Prado\Web\UI\WebControls\TDatePickerMode;
use Prado\Web\UI\WebControls\TDatePickerPositionMode;
use Prado\Web\UI\WebControls\TTextBox;
use PHPUnit\Framework\TestCase;

class TDatePickerTest extends TestCase
{
	// ================================================================================
	// Class structure
	// ================================================================================

	public function testExtendsTTextBox()
	{
		$picker = new TDatePicker();
		$this->assertInstanceOf(TTextBox::class, $picker);
	}

	// ================================================================================
	// AutoPostBack
	// ================================================================================

	public function testSetAutoPostBackThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(TNotSupportedException::class);
		$picker->setAutoPostBack(true);
	}

	// ================================================================================
	// DateFormat
	// ================================================================================

	public function testDateFormatDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('dd-MM-yyyy', $picker->getDateFormat());
	}

	public function testSetDateFormat()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('MM/dd/yyyy');
		$this->assertEquals('MM/dd/yyyy', $picker->getDateFormat());
	}

	// ================================================================================
	// ShowCalendar
	// ================================================================================

	public function testShowCalendarDefault()
	{
		$picker = new TDatePicker();
		$this->assertTrue($picker->getShowCalendar());
	}

	public function testSetShowCalendarFalse()
	{
		$picker = new TDatePicker();
		$picker->setShowCalendar(false);
		$this->assertFalse($picker->getShowCalendar());
	}

	public function testSetShowCalendarStringFalse()
	{
		$picker = new TDatePicker();
		$picker->setShowCalendar('false');
		$this->assertFalse($picker->getShowCalendar());
	}

	// ================================================================================
	// Culture
	// ================================================================================

	public function testCultureDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getCulture());
	}

	public function testSetCulture()
	{
		$picker = new TDatePicker();
		$picker->setCulture('en_AU');
		$this->assertEquals('en_AU', $picker->getCulture());
	}

	// ================================================================================
	// DateInputMode
	// ================================================================================

	public function testDateInputModeDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(TDatePickerInputMode::TextBox, $picker->getDateInputMode());
	}

	public function testSetDateInputModeDropDownList()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$this->assertEquals(TDatePickerInputMode::DropDownList, $picker->getDateInputMode());
	}

	public function testSetDateInputModeTextBox()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setDateInputMode(TDatePickerInputMode::TextBox);
		$this->assertEquals(TDatePickerInputMode::TextBox, $picker->getDateInputMode());
	}

	public function testSetDateInputModeInvalidThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$picker->setDateInputMode('Invalid');
	}

	// ================================================================================
	// Mode
	// ================================================================================

	public function testModeDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(TDatePickerMode::Basic, $picker->getMode());
	}

	public function testSetModeButton()
	{
		$picker = new TDatePicker();
		$picker->setMode(TDatePickerMode::Button);
		$this->assertEquals(TDatePickerMode::Button, $picker->getMode());
	}

	public function testSetModeClickable()
	{
		$picker = new TDatePicker();
		$picker->setMode(TDatePickerMode::Clickable);
		$this->assertEquals(TDatePickerMode::Clickable, $picker->getMode());
	}

	public function testSetModeImageButton()
	{
		$picker = new TDatePicker();
		$picker->setMode(TDatePickerMode::ImageButton);
		$this->assertEquals(TDatePickerMode::ImageButton, $picker->getMode());
	}

	public function testSetModeInvalidThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$picker->setMode('Invalid');
	}

	// ================================================================================
	// ButtonImageUrl
	// ================================================================================

	public function testButtonImageUrlDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getButtonImageUrl());
	}

	public function testSetButtonImageUrl()
	{
		$picker = new TDatePicker();
		$picker->setButtonImageUrl('/images/calendar.png');
		$this->assertEquals('/images/calendar.png', $picker->getButtonImageUrl());
	}

	// ================================================================================
	// CalendarStyle
	// ================================================================================

	public function testCalendarStyleDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('default', $picker->getCalendarStyle());
	}

	public function testSetCalendarStyle()
	{
		$picker = new TDatePicker();
		$picker->setCalendarStyle('custom');
		$this->assertEquals('custom', $picker->getCalendarStyle());
	}

	// ================================================================================
	// DropDownCssClass
	// ================================================================================

	public function testDropDownCssClassDefault()
	{
		$picker = new TDatePicker();
		$this->assertNull($picker->getDropDownCssClass());
	}

	public function testSetDropDownCssClass()
	{
		$picker = new TDatePicker();
		$picker->setDropDownCssClass('my-dropdown');
		$this->assertEquals('my-dropdown', $picker->getDropDownCssClass());
	}

	// ================================================================================
	// FirstDayOfWeek
	// ================================================================================

	public function testFirstDayOfWeekDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(1, $picker->getFirstDayOfWeek());
	}

	public function testSetFirstDayOfWeekSunday()
	{
		$picker = new TDatePicker();
		$picker->setFirstDayOfWeek(0);
		$this->assertEquals(0, $picker->getFirstDayOfWeek());
	}

	public function testSetFirstDayOfWeekSaturday()
	{
		$picker = new TDatePicker();
		$picker->setFirstDayOfWeek(6);
		$this->assertEquals(6, $picker->getFirstDayOfWeek());
	}

	// ================================================================================
	// ButtonText
	// ================================================================================

	public function testButtonTextDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('...', $picker->getButtonText());
	}

	public function testSetButtonText()
	{
		$picker = new TDatePicker();
		$picker->setButtonText('Pick');
		$this->assertEquals('Pick', $picker->getButtonText());
	}

	// ================================================================================
	// FromYear / UpToYear
	// ================================================================================

	public function testSetFromYear()
	{
		$picker = new TDatePicker();
		$picker->setFromYear(2000);
		$this->assertEquals(2000, $picker->getFromYear());
	}

	public function testSetUpToYear()
	{
		$picker = new TDatePicker();
		$picker->setUpToYear(2050);
		$this->assertEquals(2050, $picker->getUpToYear());
	}

	// ================================================================================
	// PositionMode
	// ================================================================================

	public function testPositionModeDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(TDatePickerPositionMode::Bottom, $picker->getPositionMode());
	}

	public function testSetPositionModeTop()
	{
		$picker = new TDatePicker();
		$picker->setPositionMode(TDatePickerPositionMode::Top);
		$this->assertEquals(TDatePickerPositionMode::Top, $picker->getPositionMode());
	}

	public function testSetPositionModeInvalidThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$picker->setPositionMode('Invalid');
	}

	// ================================================================================
	// Date / getText alias
	// ================================================================================

	public function testDateDefaultEmpty()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getDate());
	}

	public function testSetDate()
	{
		$picker = new TDatePicker();
		$picker->setDate('25-12-2024');
		$this->assertEquals('25-12-2024', $picker->getDate());
	}

	// ================================================================================
	// TimeStamp
	// ================================================================================

	public function testTimeStampNullWhenEmpty()
	{
		$picker = new TDatePicker();
		$this->assertNull($picker->getTimeStamp());
	}

	public function testSetTimeStampNullClearsText()
	{
		$picker = new TDatePicker();
		$picker->setDate('25-12-2024');
		$picker->setTimeStamp(null);
		$this->assertEquals('', $picker->getText());
	}

	public function testSetTimeStampEmptyStringClearsText()
	{
		$picker = new TDatePicker();
		$picker->setDate('25-12-2024');
		$picker->setTimeStamp('');
		$this->assertEquals('', $picker->getText());
	}

	public function testSetTimeStampFormatsDate()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 12, 25, 2024);
		$picker->setTimeStamp($ts);
		$this->assertEquals('25-12-2024', $picker->getText());
	}

	public function testGetTimeStampRoundTrip()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 6, 15, 2023);
		$picker->setTimeStamp($ts);
		$retrieved = $picker->getTimeStamp();
		$this->assertEquals(date('Y-m-d', $ts), date('Y-m-d', $retrieved));
	}

	// ================================================================================
	// Data (IDataRenderer alias)
	// ================================================================================

	public function testGetDataEqualsGetTimeStamp()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 3, 1, 2020);
		$picker->setTimeStamp($ts);
		$this->assertEquals($picker->getTimeStamp(), $picker->getData());
	}

	public function testSetDataEqualsSetTimeStamp()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 3, 1, 2020);
		$picker->setData($ts);
		$this->assertEquals('01-03-2020', $picker->getText());
	}

	// ================================================================================
	// ValidationPropertyValue
	// ================================================================================

	public function testValidationPropertyValueEmptyText()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getValidationPropertyValue());
	}

	public function testValidationPropertyValueValidDate()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$picker->setDate('25-12-2024');
		$result = $picker->getValidationPropertyValue();
		$this->assertIsInt($result);
		$this->assertEquals('25-12-2024', date('d-m-Y', $result));
	}

	public function testValidationPropertyValueInvalidDateReturnsText()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$picker->setDate('not-a-date');
		$result = $picker->getValidationPropertyValue();
		$this->assertEquals('not-a-date', $result);
	}
}
