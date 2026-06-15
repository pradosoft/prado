<?php

use Prado\Web\UI\WebControls\TTimeFormat;
use PHPUnit\Framework\TestCase;

class TTimeFormatTest extends TestCase
{
	public function testAllConstantsExist()
	{
		// Time-only
		$this->assertEquals('TimeShort', TTimeFormat::TimeShort);
		$this->assertEquals('Time', TTimeFormat::Time);
		$this->assertEquals('TimePrecise', TTimeFormat::TimePrecise);

		// Date-only
		$this->assertEquals('Date', TTimeFormat::Date);
		$this->assertEquals('Month', TTimeFormat::Month);
		$this->assertEquals('Week', TTimeFormat::Week);
		$this->assertEquals('YearlessDate', TTimeFormat::YearlessDate);
		$this->assertEquals('Year', TTimeFormat::Year);

		// DateTime space-separated
		$this->assertEquals('DateTimeShort', TTimeFormat::DateTimeShort);
		$this->assertEquals('DateTime', TTimeFormat::DateTime);
		$this->assertEquals('DateTimePrecise', TTimeFormat::DateTimePrecise);

		// DateTime T-separated
		$this->assertEquals('HtmlDateTimeShort', TTimeFormat::HtmlDateTimeShort);
		$this->assertEquals('HtmlDateTime', TTimeFormat::HtmlDateTime);
		$this->assertEquals('HtmlDateTimePrecise', TTimeFormat::HtmlDateTimePrecise);

		// DateTime with timezone space-separated
		$this->assertEquals('DateTimeShortTimezone', TTimeFormat::DateTimeShortTimezone);
		$this->assertEquals('DateTimeTimezone', TTimeFormat::DateTimeTimezone);
		$this->assertEquals('DateTimePreciseTimezone', TTimeFormat::DateTimePreciseTimezone);

		// DateTime with timezone T-separated
		$this->assertEquals('HtmlDateTimeShortTimezone', TTimeFormat::HtmlDateTimeShortTimezone);
		$this->assertEquals('HtmlDateTimeTimezone', TTimeFormat::HtmlDateTimeTimezone);
		$this->assertEquals('HtmlDateTimePreciseTimezone', TTimeFormat::HtmlDateTimePreciseTimezone);
	}

	public function testAllValuesUnique()
	{
		$values = [
			TTimeFormat::TimeShort,
			TTimeFormat::Time,
			TTimeFormat::TimePrecise,
			TTimeFormat::Date,
			TTimeFormat::Month,
			TTimeFormat::Week,
			TTimeFormat::YearlessDate,
			TTimeFormat::Year,
			TTimeFormat::DateTimeShort,
			TTimeFormat::DateTime,
			TTimeFormat::DateTimePrecise,
			TTimeFormat::HtmlDateTimeShort,
			TTimeFormat::HtmlDateTime,
			TTimeFormat::HtmlDateTimePrecise,
			TTimeFormat::DateTimeShortTimezone,
			TTimeFormat::DateTimeTimezone,
			TTimeFormat::DateTimePreciseTimezone,
			TTimeFormat::HtmlDateTimeShortTimezone,
			TTimeFormat::HtmlDateTimeTimezone,
			TTimeFormat::HtmlDateTimePreciseTimezone,
		];
		$this->assertCount(20, $values, 'Expected 20 TTimeFormat constants');
		$this->assertCount(20, array_unique($values), 'All TTimeFormat values must be unique');
	}

	public function testExtendsEnumerable()
	{
		$this->assertTrue(is_a(TTimeFormat::class, \Prado\TEnumerable::class, true));
	}

	public function testHtmlVariantsDistinctFromSpaceVariants()
	{
		// Html variants must have different values from their space-separated counterparts
		$this->assertNotEquals(TTimeFormat::HtmlDateTimeShort, TTimeFormat::DateTimeShort);
		$this->assertNotEquals(TTimeFormat::HtmlDateTime, TTimeFormat::DateTime);
		$this->assertNotEquals(TTimeFormat::HtmlDateTimePrecise, TTimeFormat::DateTimePrecise);
		$this->assertNotEquals(TTimeFormat::HtmlDateTimeShortTimezone, TTimeFormat::DateTimeShortTimezone);
		$this->assertNotEquals(TTimeFormat::HtmlDateTimeTimezone, TTimeFormat::DateTimeTimezone);
		$this->assertNotEquals(TTimeFormat::HtmlDateTimePreciseTimezone, TTimeFormat::DateTimePreciseTimezone);
	}
}
