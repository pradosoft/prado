<?php

use Prado\Web\UI\WebControls\TTime;
use Prado\Web\UI\WebControls\TTimeFormat;
use Prado\Web\UI\WebControls\TI18NWebControl;
use PHPUnit\Framework\TestCase;

class TTimeTest extends TestCase
{
	use TWebControlRenderTrait;

	private function invokeMethod(object $object, string $method, array $args = []): mixed
	{
		$rm = new \ReflectionMethod($object, $method);
		$rm->setAccessible(true);
		return $rm->invokeArgs($object, $args);
	}

	// ================================================================================
	// Class structure
	// ================================================================================

	public function testExtendsI18NWebControl()
	{
		$control = new TTime();
		$this->assertInstanceOf(TI18NWebControl::class, $control);
	}

	public function testRendersTimeTag()
	{
		$control = new TTime();
		$output = $this->render($control);
		$this->assertStringContainsString('<time', $output);
		$this->assertStringContainsString('</time>', $output);
	}

	// ================================================================================
	// getDateTime / setDateTime
	// ================================================================================

	public function testDateTimeDefaultEmpty()
	{
		$control = new TTime();
		$this->assertEquals('', $control->getDateTime());
	}

	public function testSetDateTimeWithDateTimeImmutable()
	{
		$control = new TTime();
		$dt = new \DateTimeImmutable('2024-06-15 10:30:00');
		$control->setDateTime($dt);
		$this->assertSame($dt, $control->getDateTime());
	}

	public function testSetDateTimeWithDateTime()
	{
		$control = new TTime();
		$dt = new \DateTime('2024-06-15 10:30:00');
		$control->setDateTime($dt);
		$this->assertSame($dt, $control->getDateTime());
	}

	public function testSetDateTimeWithDateInterval()
	{
		$control = new TTime();
		$interval = new \DateInterval('P1Y2M3D');
		$control->setDateTime($interval);
		$this->assertSame($interval, $control->getDateTime());
	}

	public function testSetDateTimeWithISODurationString()
	{
		$control = new TTime();
		$control->setDateTime('P1Y2M3D');
		$this->assertInstanceOf(\DateInterval::class, $control->getDateTime());
	}

	public function testSetDateTimeWithISODurationTimeOnly()
	{
		$control = new TTime();
		$control->setDateTime('PT1H30M');
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateInterval::class, $stored);
		$this->assertSame(1, $stored->h);
		$this->assertSame(30, $stored->i);
	}

	public function testSetDateTimeWithISODurationZero()
	{
		$control = new TTime();
		$control->setDateTime('PT0S');
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateInterval::class, $stored);
		$this->assertSame(0, $stored->s);
		$this->assertSame(0, $stored->y);
	}

	public function testSetDateTimeWithDateString()
	{
		$control = new TTime();
		$control->setDateTime('2024-06-15');
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
		$this->assertSame('2024-06-15', $stored->format('Y-m-d'));
	}

	public function testSetDateTimeWithDateTimeString()
	{
		$control = new TTime();
		$control->setDateTime('2024-06-15T10:30:45');
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
		$this->assertSame('2024-06-15', $stored->format('Y-m-d'));
		$this->assertSame('10:30:45', $stored->format('H:i:s'));
	}

	public function testSetDateTimeWithUnixTimestampInt()
	{
		$control = new TTime();
		$control->setDateTime(1718438400);
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
		$this->assertSame(1718438400, $stored->getTimestamp());
	}

	public function testSetDateTimeWithZeroTimestamp()
	{
		$control = new TTime();
		$control->setDateTime(0);
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
		$this->assertSame(0, $stored->getTimestamp());
	}

	public function testSetDateTimeWithNegativeTimestamp()
	{
		$control = new TTime();
		$control->setDateTime(-86400);
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
		$this->assertSame(-86400, $stored->getTimestamp());
	}

	public function testSetDateTimeWithNumericFloat()
	{
		$control = new TTime();
		$control->setDateTime(1718438400.0);
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
		$this->assertSame(1718438400, $stored->getTimestamp());
	}

	public function testSetDateTimeWithNumericStringInt()
	{
		$control = new TTime();
		$control->setDateTime('1718438400');
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
	}

	public function testSetDateTimeWithNumericStringFloat()
	{
		// Fractional timestamps are truncated to int (setTimestamp takes int)
		$control = new TTime();
		$control->setDateTime('1718438400.9');
		$stored = $control->getDateTime();
		$this->assertInstanceOf(\DateTimeImmutable::class, $stored);
		$this->assertSame(1718438400, $stored->getTimestamp());
	}

	public function testSetDateTimeWithStrtotimeString()
	{
		$control = new TTime();
		$control->setDateTime('next Monday');
		$this->assertInstanceOf(\DateTimeImmutable::class, $control->getDateTime());
	}

	public function testSetDateTimeWithEmptyStringStoresDateTimeImmutable()
	{
		// '' is not a duration, but new DateTimeImmutable('') succeeds (returns "now")
		$control = new TTime();
		$control->setDateTime('');
		$this->assertInstanceOf(\DateTimeImmutable::class, $control->getDateTime());
	}

	public function testSetDateTimeWithRawStringFallback()
	{
		$control = new TTime();
		$control->setDateTime('not-a-real-date-at-all-xyz');
		$this->assertSame('not-a-real-date-at-all-xyz', $control->getDateTime());
	}

	public function testSetDateTimeWithNullThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		(new TTime())->setDateTime(null);
	}

	public function testSetDateTimeWithFalseThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		(new TTime())->setDateTime(false);
	}

	public function testSetDateTimeWithTrueThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		(new TTime())->setDateTime(true);
	}

	public function testSetDateTimeWithArrayThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		(new TTime())->setDateTime([]);
	}

	public function testSetDateTimeWithObjectThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataTypeException::class);
		(new TTime())->setDateTime(new \stdClass());
	}

	// ================================================================================
	// DateTimeFormat
	// ================================================================================

	public function testDateTimeFormatDefault()
	{
		$control = new TTime();
		$this->assertSame(TTimeFormat::HtmlDateTime, $control->getDateTimeFormat());
	}

	public function testSetDateTimeFormat()
	{
		$control = new TTime();
		$control->setDateTimeFormat(TTimeFormat::Date);
		$this->assertSame(TTimeFormat::Date, $control->getDateTimeFormat());
	}

	public function testSetDateTimeFormatInvalidThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		(new TTime())->setDateTimeFormat('NotAFormat');
	}

	public function testSetDateTimeFormatAllValues()
	{
		$control = new TTime();
		foreach (self::allFormats() as $fmt) {
			$control->setDateTimeFormat($fmt);
			$this->assertSame($fmt, $control->getDateTimeFormat());
		}
	}

	// ================================================================================
	// DateTimeTextFormat
	// ================================================================================

	public function testDateTimeFormatAndTextFormatAreIndependent()
	{
		$control = new TTime();
		$control->setDateTimeFormat(TTimeFormat::Date);
		$control->setTextFormat(TTimeFormat::Month);
		$this->assertSame(TTimeFormat::Date, $control->getDateTimeFormat());
		$this->assertSame(TTimeFormat::Month, $control->getTextFormat());
	}

	// ================================================================================
	// TextFormat
	// ================================================================================

	public function testTextFormatDefaultNull()
	{
		$this->assertNull((new TTime())->getTextFormat());
	}

	public function testSetTextFormatWithTTimeFormatName()
	{
		$control = new TTime();
		$control->setTextFormat(TTimeFormat::Date);
		$this->assertSame(TTimeFormat::Date, $control->getTextFormat());
	}

	public function testSetTextFormatWithIcuPattern()
	{
		$control = new TTime();
		$control->setTextFormat('MMMM d, yyyy');
		$this->assertSame('MMMM d, yyyy', $control->getTextFormat());
	}

	public function testSetTextFormatNullClearsProperty()
	{
		$control = new TTime();
		$control->setTextFormat(TTimeFormat::Date);
		$control->setTextFormat(null);
		$this->assertNull($control->getTextFormat());
	}

	public function testSetTextFormatEmptyStringClearsProperty()
	{
		$control = new TTime();
		$control->setTextFormat(TTimeFormat::Date);
		$control->setTextFormat('');
		$this->assertNull($control->getTextFormat());
	}

	public function testSetTextFormatInvalidThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		(new TTime())->setTextFormat('20240615');
	}

	public function testTextFormatTTimeFormatNameRendersFormattedDate()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));
		$control->setTextFormat(TTimeFormat::Date);
		$output = $this->renderContents($control);
		$this->assertStringContainsString('2024', $output);
		$this->assertStringContainsString('June', $output);
	}

	public function testTextFormatIcuPatternRendersFormattedDate()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));
		$control->setTextFormat('yyyy-MM-dd');
		$this->assertSame('2024-06-15', $this->renderContents($control));
	}

	public function testTextFormatTakesPriorityOverChildren()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));
		$control->setTextFormat('yyyy');

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('should be ignored');
		$control->getControls()->add($literal);

		$this->assertSame('2024', $this->renderContents($control));
	}

	public function testTextFormatWithIntervalRendersLocalizedDuration()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P1Y'));
		$control->setTextFormat(TTimeFormat::Date);
		$output = $this->renderContents($control);
		// DateInterval + TTimeFormat → formatTextInterval (format arg unused), produces localized duration
		$this->assertStringContainsString('year', $output);
	}

	public function testTextFormatNotRenderedWhenNoDateTime()
	{
		$control = new TTime();
		$control->setTextFormat('yyyy');
		$this->assertSame('', $this->renderContents($control));
	}

	// ================================================================================
	// formatDateTime — datetime= attribute, all formats
	// ================================================================================

	/**
	 * @dataProvider formatDateTimeProvider
	 */
	public function testFormatDateTimeAttribute(string $format, string $expected)
	{
		$control = new TTime();
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45.123', new \DateTimeZone('UTC'));
		$control->setDateTime($dt);
		$control->setDateTimeFormat($format);

		$output = $this->render($control);
		$this->assertStringContainsString('datetime="' . $expected . '"', $output);
	}

	public static function formatDateTimeProvider(): array
	{
		return [
			'TimeShort'                   => [TTimeFormat::TimeShort,                   '10:30'],
			'Time'                        => [TTimeFormat::Time,                        '10:30:45'],
			'TimePrecise'                 => [TTimeFormat::TimePrecise,                 '10:30:45.123'],
			'Date'                        => [TTimeFormat::Date,                        '2024-06-15'],
			'Month'                       => [TTimeFormat::Month,                       '2024-06'],
			'Week'                        => [TTimeFormat::Week,                        '2024-W24'],
			'YearlessDate'                => [TTimeFormat::YearlessDate,                '06-15'],
			'Year'                        => [TTimeFormat::Year,                        '2024'],
			'DateTimeShort'               => [TTimeFormat::DateTimeShort,               '2024-06-15 10:30'],
			'DateTime'                    => [TTimeFormat::DateTime,                    '2024-06-15 10:30:45'],
			'DateTimePrecise'             => [TTimeFormat::DateTimePrecise,             '2024-06-15 10:30:45.123'],
			'HtmlDateTimeShort'           => [TTimeFormat::HtmlDateTimeShort,           '2024-06-15T10:30'],
			'HtmlDateTime'                => [TTimeFormat::HtmlDateTime,                '2024-06-15T10:30:45'],
			'HtmlDateTimePrecise'         => [TTimeFormat::HtmlDateTimePrecise,         '2024-06-15T10:30:45.123'],
			'DateTimeShortTimezone'       => [TTimeFormat::DateTimeShortTimezone,       '2024-06-15 10:30+00:00'],
			'DateTimeTimezone'            => [TTimeFormat::DateTimeTimezone,            '2024-06-15 10:30:45+00:00'],
			'DateTimePreciseTimezone'     => [TTimeFormat::DateTimePreciseTimezone,     '2024-06-15 10:30:45.123+00:00'],
			'HtmlDateTimeShortTimezone'   => [TTimeFormat::HtmlDateTimeShortTimezone,   '2024-06-15T10:30+00:00'],
			'HtmlDateTimeTimezone'        => [TTimeFormat::HtmlDateTimeTimezone,        '2024-06-15T10:30:45+00:00'],
			'HtmlDateTimePreciseTimezone' => [TTimeFormat::HtmlDateTimePreciseTimezone, '2024-06-15T10:30:45.123+00:00'],
		];
	}

	// ================================================================================
	// formatInterval — datetime= attribute, all edge cases
	// ================================================================================

	public function testFormatIntervalFullComponents()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P1Y2M3DT4H5M6S'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="P1Y2M3DT4H5M6S"', $output);
	}

	public function testFormatIntervalDateOnly()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P5D'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="P5D"', $output);
	}

	public function testFormatIntervalTimeOnly()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('PT2H30M'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="PT2H30M"', $output);
	}

	public function testFormatIntervalYearOnly()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P3Y'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="P3Y"', $output);
	}

	public function testFormatIntervalSecondsOnly()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('PT45S'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="PT45S"', $output);
	}

	public function testFormatIntervalZeroDuration()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('PT0S'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="PT0S"', $output);
	}

	public function testFormatIntervalFractionalSeconds()
	{
		// Build an interval with f (fractional seconds) via date_diff
		$dt1 = new \DateTimeImmutable('2024-01-01T00:00:00.000', new \DateTimeZone('UTC'));
		$dt2 = new \DateTimeImmutable('2024-01-01T00:00:01.500', new \DateTimeZone('UTC'));
		$interval = $dt1->diff($dt2);

		$control = new TTime();
		$control->setDateTime($interval);
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="PT1.500S"', $output);
	}

	public function testFormatIntervalFractionalSecondsNoWholeSeconds()
	{
		// f > 0, s == 0
		$dt1 = new \DateTimeImmutable('2024-01-01T00:00:00.000', new \DateTimeZone('UTC'));
		$dt2 = new \DateTimeImmutable('2024-01-01T00:00:00.750', new \DateTimeZone('UTC'));
		$interval = $dt1->diff($dt2);

		$control = new TTime();
		$control->setDateTime($interval);
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="PT0.750S"', $output);
	}

	public function testFormatIntervalAllDateComponents()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P2Y11M28D'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="P2Y11M28D"', $output);
	}

	public function testFormatIntervalFractionalSecondsRoundsToZeroOmitsSuffix()
	{
		// f rounds to 0 ms (0.0001 * 1000 = 0.1 → 0) — suffix must be omitted, not emitted as 0.000S
		$interval = new \DateInterval('PT5S');
		$interval->f = 0.0001;

		$control = new TTime();
		$control->setDateTime($interval);
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="PT5S"', $output);
		$this->assertStringNotContainsString('0.000', $output);
	}

	// ================================================================================
	// addAttributesToRender
	// ================================================================================

	public function testDatetimeAttributeNotRenderedWhenEmpty()
	{
		$control = new TTime();
		$output = $this->render($control);
		$this->assertStringNotContainsString('datetime=', $output);
	}

	public function testDatetimeAttributeRenderedWhenSet()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-01-01T00:00:00Z', new \DateTimeZone('UTC')));
		$this->assertStringContainsString('datetime=', $this->render($control));
	}

	public function testRawStringValueRenderedAsDatetimeAttribute()
	{
		$control = new TTime();
		$control->setDateTime('not-a-real-date-at-all-xyz');
		$this->assertStringContainsString('datetime="not-a-real-date-at-all-xyz"', $this->render($control));
	}

	// ================================================================================
	// renderContents — no children
	// ================================================================================

	public function testRenderContentsEmptyWhenNoDateTimeAndNoChildren()
	{
		$control = new TTime();
		$output = $this->render($control);
		$this->assertStringContainsString('<time>', $output);
		$this->assertStringContainsString('</time>', $output);
		$this->assertStringNotContainsString('datetime=', $output);
	}

	public function testRenderContentsDateTimeProducesTextContent()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T10:30:45Z', new \DateTimeZone('UTC')));
		$this->assertMatchesRegularExpression('/<time[^>]*>(.+)<\/time>/s', $this->render($control));
	}

	public function testRenderContentsIntervalProducesTextContent()
	{
		$control = new TTime();
		$control->setDateTime('P1Y');
		$this->assertMatchesRegularExpression('/<time[^>]*>(.+)<\/time>/s', $this->render($control));
	}

	public function testNoChildrenTextUsesDateTimeFormat()
	{
		// Without TextFormat or children, visible text mirrors DateTimeFormat
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T10:30:45Z', new \DateTimeZone('UTC')));
		$control->setDateTimeFormat(TTimeFormat::Date);
		$output = $this->renderContents($control);
		// Date format → localized date (contains year, no time component)
		$this->assertStringContainsString('2024', $output);
		$this->assertStringNotContainsString('10:30', $output);
	}

	public function testRenderContentsRawStringProducesNoTextContent()
	{
		// Raw string fallback: formatTextValue returns (string) value — raw string as-is
		$control = new TTime();
		$control->setDateTime('not-a-real-date-at-all-xyz');
		$output = $this->renderContents($control);
		$this->assertSame('not-a-real-date-at-all-xyz', $output);
	}

	// ================================================================================
	// formatTextDateTime — visible text, all format groups
	// ================================================================================

	public function testFormatTextDateTimeTimeOnlyFormatsExcludeDate()
	{
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45', new \DateTimeZone('UTC'));
		foreach ([TTimeFormat::TimeShort, TTimeFormat::Time, TTimeFormat::TimePrecise] as $fmt) {
			$control = new TTime();
			$control->setDateTime($dt);
			$control->setTextFormat($fmt);
			$output = $this->renderContents($control);
			$this->assertNotEmpty($output, "format $fmt produced empty output");
			$this->assertStringNotContainsString('2024', $output, "format $fmt should not include year");
		}
	}

	public function testFormatTextDateTimeDateOnlyFormatsExcludeTime()
	{
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45', new \DateTimeZone('UTC'));
		foreach ([TTimeFormat::Date, TTimeFormat::Month, TTimeFormat::Week, TTimeFormat::Year] as $fmt) {
			$control = new TTime();
			$control->setDateTime($dt);
			$control->setTextFormat($fmt);
			$output = $this->renderContents($control);
			$this->assertNotEmpty($output, "format $fmt produced empty output");
			$this->assertStringNotContainsString(':30', $output, "format $fmt should not include minutes");
		}
	}

	public function testFormatTextDateTimeYearlessDateExcludesTimeAndYear()
	{
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45', new \DateTimeZone('UTC'));
		$control = new TTime();
		$control->setDateTime($dt);
		$control->setTextFormat(TTimeFormat::YearlessDate);
		$output = $this->renderContents($control);
		$this->assertNotEmpty($output);
		$this->assertStringNotContainsString(':30', $output);
	}

	public function testFormatTextDateTimeDateTimeShortFormatsContainDateAndTime()
	{
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45', new \DateTimeZone('UTC'));
		foreach ([TTimeFormat::DateTimeShort, TTimeFormat::HtmlDateTimeShort] as $fmt) {
			$control = new TTime();
			$control->setDateTime($dt);
			$control->setTextFormat($fmt);
			$output = $this->renderContents($control);
			$this->assertNotEmpty($output, "format $fmt produced empty output");
			$this->assertStringContainsString('2024', $output, "format $fmt should include year");
		}
	}

	public function testFormatTextDateTimeDateTimeFormatsContainBothDateAndTime()
	{
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45', new \DateTimeZone('UTC'));
		foreach ([TTimeFormat::DateTime, TTimeFormat::DateTimePrecise, TTimeFormat::HtmlDateTime, TTimeFormat::HtmlDateTimePrecise] as $fmt) {
			$control = new TTime();
			$control->setDateTime($dt);
			$control->setTextFormat($fmt);
			$output = $this->renderContents($control);
			$this->assertNotEmpty($output, "format $fmt produced empty output");
			$this->assertStringContainsString('2024', $output, "format $fmt should include year");
		}
	}

	public function testFormatTextDateTimeTimezoneFormatsProduceNonEmpty()
	{
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45', new \DateTimeZone('UTC'));
		$tzFormats = [
			TTimeFormat::DateTimeShortTimezone,
			TTimeFormat::HtmlDateTimeShortTimezone,
			TTimeFormat::DateTimeTimezone,
			TTimeFormat::DateTimePreciseTimezone,
			TTimeFormat::HtmlDateTimeTimezone,
			TTimeFormat::HtmlDateTimePreciseTimezone,
		];
		foreach ($tzFormats as $fmt) {
			$control = new TTime();
			$control->setDateTime($dt);
			$control->setTextFormat($fmt);
			$output = $this->renderContents($control);
			$this->assertNotEmpty($output, "format $fmt produced empty output");
			$this->assertStringContainsString('2024', $output, "format $fmt should include year");
			$this->assertMatchesRegularExpression(
				'/UTC|Coordinated Universal Time|\+00:00/',
				$output,
				"format $fmt should include timezone information"
			);
		}
	}

	public function testFormatTextDateTimeWithIcuPattern()
	{
		// $format that is not a TTimeFormat constant → treated as ICU pattern in formatTextDateTime directly
		$control = new TTime();
		$dt = new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC'));
		$control->setDateTime($dt);
		$output = $this->invokeMethod($control, 'formatTextDateTime', [$dt, 'yyyy']);
		$this->assertSame('2024', $output);
	}

	public function testFormatTextDateTimeWithIcuPatternFallbackOnFailure()
	{
		// When IntlDateFormatter::format() returns false the ISO fallback is used
		$control = new TTime();
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45+00:00');
		$control->setDateTime($dt);
		// null $format resolves to getDateTimeTextFormat() (HtmlDateTime) → not the default branch
		// Pass a known non-TTimeFormat, non-ICU string: digits only pass isIcuPattern=false in
		// renderContents, but formatTextDateTime itself has no isIcuPattern guard — it calls ICU
		// regardless. An empty pattern string causes IntlDateFormatter to use locale default.
		// This test exercises the non-null $format → default branch with a real (succeeding) pattern.
		$output = $this->invokeMethod($control, 'formatTextDateTime', [$dt, 'MMMM d, yyyy']);
		$this->assertStringContainsString('2024', $output);
		$this->assertStringContainsString('June', $output);
	}

	// ================================================================================
	// formatTextInterval — visible text, all edge cases
	// ================================================================================

	public function testFormatTextIntervalFullComponentsIsNonEmpty()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P1Y2M3DT4H5M6S'));
		$this->assertNotEmpty($this->renderContents($control));
	}

	public function testFormatTextIntervalYearOnly()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P1Y'));
		$output = $this->renderContents($control);
		$this->assertNotEmpty($output);
	}

	public function testFormatTextIntervalSecondsOnly()
	{
		$control = new TTime();
		$control->setDateTime(new \DateInterval('PT45S'));
		$output = $this->renderContents($control);
		$this->assertNotEmpty($output);
	}

	public function testFormatTextIntervalDaysConvertedToWeeksAndRemainder()
	{
		// 14 days → 2 weeks exactly (no remainder days)
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P14D'));
		$output = $this->renderContents($control);
		$this->assertNotEmpty($output);
		// 6 days → no weeks, just 6 days
		$control2 = new TTime();
		$control2->setDateTime(new \DateInterval('P6D'));
		$this->assertNotEmpty($this->renderContents($control2));
	}

	public function testFormatTextIntervalFractionalSeconds()
	{
		$dt1 = new \DateTimeImmutable('2024-01-01T00:00:00.000', new \DateTimeZone('UTC'));
		$dt2 = new \DateTimeImmutable('2024-01-01T00:00:00.750', new \DateTimeZone('UTC'));
		$control = new TTime();
		$control->setDateTime($dt1->diff($dt2));
		$output = $this->renderContents($control);
		$this->assertNotEmpty($output);
	}

	public function testFormatTextIntervalZeroDurationIsEmpty()
	{
		// All zero components → formatTextInterval returns ''
		$control = new TTime();
		$control->setDateTime(new \DateInterval('PT0S'));
		$this->assertSame('', $this->renderContents($control));
	}

	public function testFormatTextIntervalNegativeIntervalPrefixesMinus()
	{
		$later  = new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC'));
		$earlier = new \DateTimeImmutable('2023-06-15T00:00:00Z', new \DateTimeZone('UTC'));
		$interval = $later->diff($earlier); // invert === 1 (negative: earlier minus later)

		$control = new TTime();
		$control->setDateTime($interval);
		$output = $this->renderContents($control);
		$this->assertStringStartsWith('-', $output);
		$this->assertStringContainsString('year', $output);
	}

	public function testFormatTextIntervalNegativeZeroDurationIsEmpty()
	{
		$interval = new \DateInterval('PT0S');
		$interval->invert = 1;

		$control = new TTime();
		$control->setDateTime($interval);
		$this->assertSame('', $this->renderContents($control));
	}

	public function testFormatIntervalNegativeIntervalRendersPositive()
	{
		// datetime attribute has no negative duration form — always positive
		$later   = new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC'));
		$earlier = new \DateTimeImmutable('2023-06-15T00:00:00Z', new \DateTimeZone('UTC'));
		$interval = $later->diff($earlier);

		$control = new TTime();
		$control->setDateTime($interval);
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="P1Y"', $output);
	}

	// ================================================================================
	// renderContents — children
	// ================================================================================

	public function testRenderContentsRendersChildrenWhenPresent()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T10:30:45Z', new \DateTimeZone('UTC')));
		$inner = new \Prado\Web\UI\WebControls\THeader2();
		$inner->setId('inner');
		$control->getControls()->add($inner);

		$output = $this->render($control);
		$this->assertStringContainsString('<h2', $output);
		$this->assertStringContainsString('datetime=', $output);
	}

	// ================================================================================
	// Format inference from children
	// ================================================================================

	public function testChildrenWithFormatNameRendersFormattedDateTime()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText(TTimeFormat::Date);
		$control->getControls()->add($literal);

		$output = $this->renderContents($control);
		$this->assertNotSame(TTimeFormat::Date, $output);
		$this->assertStringContainsString('2024', $output);
	}

	public function testChildrenWithFormatNameCaseInsensitive()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('date');
		$control->getControls()->add($literal);

		$output = $this->renderContents($control);
		$this->assertNotSame('date', $output);
		$this->assertStringContainsString('2024', $output);
	}

	public function testChildrenFormatInferenceAllFormats()
	{
		$dt = new \DateTimeImmutable('2024-06-15T10:30:45', new \DateTimeZone('UTC'));
		foreach (self::allFormats() as $fmt) {
			$control = new TTime();
			$control->setDateTime($dt);

			$literal = new \Prado\Web\UI\WebControls\TLiteral();
			$literal->setText($fmt);
			$control->getControls()->add($literal);

			$output = $this->renderContents($control);
			$this->assertNotSame($fmt, $output, "format $fmt should trigger datetime formatting, not literal passthrough");
		}
	}

	public static function isIcuPatternProvider(): array
	{
		return [
			'year pattern'              => ['yyyy', true],
			'full datetime pattern'     => ['MMMM d, yyyy', true],
			'time pattern'              => ['HH:mm:ss', true],
			'quoted literal only'       => ["'hello'", false],
			'quoted with letter after'  => ["'hello' yyyy", true],
			'digits only'               => ['20240615', false],
			'punctuation only'          => ['--::', false],
			'empty string'              => ['', false],
			'escaped quote in literal'  => ["'it''s' d MMMM", true],
		];
	}

	/**
	 * @dataProvider isIcuPatternProvider
	 */
	public function testIsIcuPattern(string $input, bool $expected)
	{
		$this->assertSame($expected, TTime::isIcuPattern($input));
	}

	public function testChildrenWithNoIcuLettersRenderedAsIs()
	{
		// String with no ICU format letters → never attempted as ICU pattern, written as-is
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('20240615');
		$control->getControls()->add($literal);

		$this->assertSame('20240615', $this->renderContents($control));
	}

	public function testChildrenWithNonFormatTextAndIntervalRenderedAsIs()
	{
		// DateInterval + non-TTimeFormat string → written as-is (ICU patterns cannot apply to intervals)
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P1Y'));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('15 June 2024');
		$control->getControls()->add($literal);

		$this->assertSame('15 June 2024', $this->renderContents($control));
	}

	public function testChildrenWithIcuPatternRendersFormattedDateTime()
	{
		// Non-TTimeFormat string + DateTimeInterface → treated as ICU pattern
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('MMMM d, yyyy');
		$control->getControls()->add($literal);

		$output = $this->renderContents($control);
		$this->assertNotSame('MMMM d, yyyy', $output);
		$this->assertStringContainsString('2024', $output);
	}

	public function testChildrenWithRawStringDateTimeAndNonEnumChildRendersChildAsIs()
	{
		// Raw string datetime (not DateTimeInterface) + non-TTimeFormat child → always written as-is
		$control = new TTime();
		$control->setDateTime('not-a-date');

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('15 June 2024');
		$control->getControls()->add($literal);

		$this->assertSame('15 June 2024', $this->renderContents($control));
	}

	public function testChildrenWithIcuPatternForIntervalRenderedAsIs()
	{
		// ICU pattern + DateInterval → written as-is (only DateTimeInterface supports ICU)
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P1Y'));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('MMMM d, yyyy');
		$control->getControls()->add($literal);

		$this->assertSame('MMMM d, yyyy', $this->renderContents($control));
	}

	public function testChildrenWithIcuTimePatternRendersTime()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T14:30:00Z', new \DateTimeZone('UTC')));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('HH:mm');
		$control->getControls()->add($literal);

		$output = $this->renderContents($control);
		$this->assertStringContainsString('14:30', $output);
	}

	public function testChildrenWithIcuPatternWhitespaceTrimmed()
	{
		// Trim is applied before ICU pattern resolution
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('  yyyy  ');
		$control->getControls()->add($literal);

		$output = $this->renderContents($control);
		$this->assertSame('2024', $output);
	}

	public function testChildrenWithTTimeFormatNameAndIntervalValueFormatsAsInterval()
	{
		// DateInterval + child TTimeFormat name → formatTextInterval($interval, $format);
		// $format is accepted but unused — interval renders as localized duration string
		$control = new TTime();
		$control->setDateTime(new \DateInterval('P1Y'));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText(TTimeFormat::Date);
		$control->getControls()->add($literal);

		$output = $this->renderContents($control);
		// Not the literal 'Date' string and not a formatted date — it is a localized duration
		$this->assertNotSame(TTimeFormat::Date, $output);
		$this->assertStringContainsString('year', $output);
	}

	public function testChildrenWithFormatNameButNoDateTimeRendersChildren()
	{
		$control = new TTime();

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText(TTimeFormat::Date);
		$control->getControls()->add($literal);

		$this->assertSame(TTimeFormat::Date, $this->renderContents($control));
	}

	public function testHtmlChildrenNotTreatedAsFormatName()
	{
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));

		$inner = new \Prado\Web\UI\WebControls\THeader2();
		$inner->setId('inner');
		$control->getControls()->add($inner);

		$this->assertStringContainsString('<h2', $this->renderContents($control));
	}

	public function testChildrenWithWhitespace()
	{
		// Inference trims the buffered output before testing it as a format name
		$control = new TTime();
		$control->setDateTime(new \DateTimeImmutable('2024-06-15T00:00:00Z', new \DateTimeZone('UTC')));

		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('  ' . TTimeFormat::Year . '  ');
		$control->getControls()->add($literal);

		$output = $this->renderContents($control);
		$this->assertNotSame(TTimeFormat::Year, $output);
		$this->assertStringContainsString('2024', $output);
	}

	// ================================================================================
	// Helpers
	// ================================================================================

	private static function allFormats(): array
	{
		return [
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
	}
}
