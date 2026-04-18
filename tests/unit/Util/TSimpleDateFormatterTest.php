<?php

use Prado\Util\TSimpleDateFormatter;

class TSimpleDateFormatterTest extends PHPUnit\Framework\TestCase
{
	private $formatter;

	protected function setUp(): void
	{
		$this->formatter = new TSimpleDateFormatter('yyyy-MM-dd');
	}

	protected function tearDown(): void
	{
		$this->formatter = null;
	}

	public function test_instance_of_class(): void
	{
		$this->assertInstanceOf(TSimpleDateFormatter::class, $this->formatter);
	}

	public function test_constructor_with_charset(): void
	{
		$f = new TSimpleDateFormatter('dd/MM/yyyy', 'ISO-8859-1');
		$this->assertSame('dd/MM/yyyy', $f->getPattern());
		$this->assertSame('ISO-8859-1', $f->getCharset());
	}

	public function test_set_pattern(): void
	{
		$this->formatter->setPattern('MM-dd-yyyy');
		$this->assertSame('MM-dd-yyyy', $this->formatter->getPattern());
	}

	public function test_set_charset(): void
	{
		$this->formatter->setCharset('UTF-16');
		$this->assertSame('UTF-16', $this->formatter->getCharset());
	}

	public function test_parse_integer_timestamp_returns_same(): void
	{
		$ts = 1713367800;
		$result = $this->formatter->parse($ts);
		$this->assertSame($ts, $result);
	}

	public function test_parse_float_timestamp_returns_rounded(): void
	{
		$ts = 1713367800.5;
		$result = $this->formatter->parse($ts);
		$this->assertSame(1713367800, $result);
	}

	public function test_parse_empty_string_returns_current_time_when_enabled(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$before = time();
		$result = $this->formatter->parse('', true);
		$after = time();
		$this->assertThat($result, $this->greaterThanOrEqual($before));
		$this->assertThat($result, $this->lessThanOrEqual($after + 1));
	}

	public function test_parse_empty_string_returns_null_when_disabled(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('', false);
		$this->assertNull($result);
	}

	public function test_parse_non_string_throws_exception(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->formatter->parse(['array']);
	}

	public function test_parse_object_throws_exception(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->formatter->parse(new stdClass());
	}

	public function test_format_year_yyyy_returns_four_digits(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('yyyy');
		$this->assertSame('2026', $this->formatter->format($ts));
	}

	public function test_format_year_yy_returns_two_digits(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('yy');
		$this->assertSame('26', $this->formatter->format($ts));
	}

	public function test_format_year_y_single_returns_four_digits(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('y');
		$this->assertSame('2026', $this->formatter->format($ts));
	}

	public function test_format_month_MMMM_returns_full_name(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('MMMM');
		$this->assertSame('April', $this->formatter->format($ts));
	}

	public function test_format_month_MMM_returns_abbrev(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('MMM');
		$this->assertSame('Apr', $this->formatter->format($ts));
	}

	public function test_format_month_MM_returns_zero_padded(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('MM');
		$this->assertSame('04', $this->formatter->format($ts));
	}

	public function test_format_month_M_returns_no_padding(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('M');
		$this->assertSame('4', $this->formatter->format($ts));
	}

	public function test_format_day_dd_returns_zero_padded(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('dd');
		$this->assertSame('17', $this->formatter->format($ts));
	}

	public function test_format_day_d_returns_no_padding(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('d');
		$this->assertSame('17', $this->formatter->format($ts));
	}

	public function test_format_hour_HH_returns_zero_padded(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('HH');
		$this->assertSame('14', $this->formatter->format($ts));
	}

	public function test_format_hour_H_returns_no_padding(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('H');
		$this->assertSame('14', $this->formatter->format($ts));
	}

	public function test_format_hour_HH_midnight_is_zero(): void
	{
		$ts = strtotime('2026-04-17 00:30:00');
		$this->formatter->setPattern('HH');
		$this->assertSame('00', $this->formatter->format($ts));
	}

	public function test_format_hour_H_midnight_is_zero(): void
	{
		$ts = strtotime('2026-04-17 00:30:00');
		$this->formatter->setPattern('H');
		$this->assertSame('0', $this->formatter->format($ts));
	}

	public function test_format_hour_hh_returns_12hour(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('hh');
		$this->assertSame('02', $this->formatter->format($ts));
	}

	public function test_format_hour_h_returns_12hour_no_padding(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('h');
		$this->assertSame('2', $this->formatter->format($ts));
	}

	public function test_format_hour_hh_midnight_is_twelve(): void
	{
		$ts = strtotime('2026-04-17 00:30:00');
		$this->formatter->setPattern('hh');
		$this->assertSame('12', $this->formatter->format($ts));
	}

	public function test_format_hour_h_noon_is_twelve(): void
	{
		$ts = strtotime('2026-04-17 12:00:00');
		$this->formatter->setPattern('h');
		$this->assertSame('12', $this->formatter->format($ts));
	}

	public function test_format_hour_kk_returns_1_to_24(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('kk');
		$this->assertSame('14', $this->formatter->format($ts));
	}

	public function test_format_hour_k_returns_1_to_24_no_padding(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('k');
		$this->assertSame('14', $this->formatter->format($ts));
	}

	public function test_format_hour_kk_midnight_is_01(): void
	{
		$ts = strtotime('2026-04-17 00:30:00');
		$this->formatter->setPattern('kk');
		$this->assertSame('01', $this->formatter->format($ts));
	}

	public function test_format_hour_k_midnight_is_1(): void
	{
		$ts = strtotime('2026-04-17 00:30:00');
		$this->formatter->setPattern('k');
		$this->assertSame('1', $this->formatter->format($ts));
	}

	public function test_format_hour_KK_returns_0_to_11(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('KK');
		$this->assertSame('02', $this->formatter->format($ts));
	}

	public function test_format_hour_K_returns_0_to_11_no_padding(): void
	{
		$ts = strtotime('2026-04-17 14:30:00');
		$this->formatter->setPattern('K');
		$this->assertSame('2', $this->formatter->format($ts));
	}

	public function test_format_hour_KK_midnight_is_00(): void
	{
		$ts = strtotime('2026-04-17 00:30:00');
		$this->formatter->setPattern('KK');
		$this->assertSame('00', $this->formatter->format($ts));
	}

	public function test_format_minute_mm_returns_padded(): void
	{
		$ts = strtotime('2026-04-17 14:05:00');
		$this->formatter->setPattern('mm');
		$this->assertSame('05', $this->formatter->format($ts));
	}

	public function test_format_minute_m_returns_no_padding(): void
	{
		$ts = strtotime('2026-04-17 14:05:00');
		$this->formatter->setPattern('m');
		$this->assertSame('5', $this->formatter->format($ts));
	}

	public function test_format_second_ss_returns_padded(): void
	{
		$ts = strtotime('2026-04-17 14:30:09');
		$this->formatter->setPattern('ss');
		$this->assertSame('09', $this->formatter->format($ts));
	}

	public function test_format_second_s_returns_no_padding(): void
	{
		$ts = strtotime('2026-04-17 14:30:09');
		$this->formatter->setPattern('s');
		$this->assertSame('9', $this->formatter->format($ts));
	}

	public function test_format_a_AM_returns_uppercase(): void
	{
		$ts = strtotime('2026-04-17 09:30:00');
		$this->formatter->setPattern('a');
		$this->assertSame('AM', $this->formatter->format($ts));
	}

	public function test_format_a_PM_returns_uppercase(): void
	{
		$ts = strtotime('2026-04-17 15:30:00');
		$this->formatter->setPattern('a');
		$this->assertSame('PM', $this->formatter->format($ts));
	}

	public function test_format_EEEE_returns_full_day(): void
	{
		$ts = strtotime('2026-04-10');
		$this->formatter->setPattern('EEEE');
		$this->assertSame('Friday', $this->formatter->format($ts));
	}

	public function test_format_E_returns_abbrev_day(): void
	{
		$ts = strtotime('2026-04-10');
		$this->formatter->setPattern('E');
		$this->assertSame('Fri', $this->formatter->format($ts));
	}

	public function test_format_E_all_days(): void
	{
		$this->formatter->setPattern('E');
		$this->assertSame('Sun', $this->formatter->format(strtotime('2026-04-05')));
		$this->assertSame('Mon', $this->formatter->format(strtotime('2026-04-06')));
		$this->assertSame('Sat', $this->formatter->format(strtotime('2026-04-11')));
	}

	public function test_parse_yyyy_returns_correct(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-04-17', false);
		$this->assertSame('2026-04-17', date('Y-m-d', $result));
	}

	public function test_parse_yy_returns_correct(): void
	{
		$this->formatter->setPattern('yy-MM-dd');
		$result = $this->formatter->parse('26-04-17', false);
		$this->assertSame('2026-04-17', date('Y-m-d', $result));
	}

	public function test_parse_yy_69_becomes_2069(): void
	{
		$this->formatter->setPattern('yy-MM-dd');
		$result = $this->formatter->parse('69-04-17', false);
		$this->assertSame('2069-04-17', date('Y-m-d', $result));
	}

	public function test_parse_yy_70_becomes_2070(): void
	{
		$this->formatter->setPattern('yy-MM-dd');
		$result = $this->formatter->parse('70-04-17', false);
		$this->assertSame('2070-04-17', date('Y-m-d', $result));
	}

	public function test_parse_yy_00_becomes_2000(): void
	{
		$this->formatter->setPattern('yy-MM-dd');
		$result = $this->formatter->parse('00-01-01', false);
		$this->assertSame('2000-01-01', date('Y-m-d', $result));
	}

	public function test_parse_y_with_four_digits(): void
	{
		$this->formatter->setPattern('y-MM-dd');
		$result = $this->formatter->parse('2026-04-17', false);
		$this->assertSame('2026-04-17', date('Y-m-d', $result));
	}

	public function test_parse_y_with_two_digits(): void
	{
		$this->formatter->setPattern('y-MM-dd');
		$result = $this->formatter->parse('26-04-17', false);
		$this->assertSame('2026-04-17', date('Y-m-d', $result));
	}

	public function test_parse_MM_returns_month(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-01-15', false);
		$this->assertSame('2026-01-15', date('Y-m-d', $result));

		$result = $this->formatter->parse('2026-12-15', false);
		$this->assertSame('2026-12-15', date('Y-m-d', $result));
	}

	public function test_parse_M_returns_month(): void
	{
		$this->formatter->setPattern('yyyy-M-d');
		$result = $this->formatter->parse('2026-1-15', false);
		$this->assertSame('2026-01-15', date('Y-m-d', $result));

		$result = $this->formatter->parse('2026-12-15', false);
		$this->assertSame('2026-12-15', date('Y-m-d', $result));
	}

	public function test_parse_invalid_month_zero_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-00-15', false);
		$this->assertNull($result);
	}

	public function test_parse_invalid_month_13_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-13-15', false);
		$this->assertNull($result);
	}

	public function test_parse_dd_returns_day(): void
	{
		$this->formatter->setPattern('yyyy-dd');
		$result = $this->formatter->parse('2026-17', false);
		$this->assertSame('2026-01-17', date('Y-m-d', $result));
	}

	public function test_parse_d_returns_day(): void
	{
		$this->formatter->setPattern('yyyy-d');
		$result = $this->formatter->parse('2026-5', false);
		$this->assertSame('2026-01-05', date('Y-m-d', $result));
	}

	public function test_parse_invalid_day_zero_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-dd');
		$result = $this->formatter->parse('2026-00', false);
		$this->assertNull($result);
	}

	public function test_parse_invalid_day_32_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-dd');
		$result = $this->formatter->parse('2026-32', false);
		$this->assertNull($result);
	}

	public function test_parse_invalid_april_31_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-04-31', false);
		$this->assertNull($result);
	}

	public function test_parse_feb_29_leap_year_valid(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2024-02-29', false);
		$this->assertNotNull($result);
		$this->assertSame('2024-02-29', date('Y-m-d', $result));
	}

	public function test_parse_feb_29_non_leap_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2023-02-29', false);
		$this->assertNull($result);
	}

	public function test_parse_HH_hour(): void
	{
		$this->formatter->setPattern('HH:mm');
		$result = $this->formatter->parse('14:30', false);
		$this->assertSame('14:30', date('H:i', $result));
	}

	public function test_parse_H_hour(): void
	{
		$this->formatter->setPattern('H:mm');
		$result = $this->formatter->parse('5:30', false);
		$this->assertSame('05:30', date('H:i', $result));
	}

	public function test_parse_invalid_hour_24_returns_null(): void
	{
		$this->formatter->setPattern('HH:mm');
		$result = $this->formatter->parse('24:00', false);
		$this->assertNull($result);
	}

	public function test_parse_HH_midnight(): void
	{
		$this->formatter->setPattern('HH:mm');
		$result = $this->formatter->parse('00:00', false);
		$this->assertSame('00:00', date('H:i', $result));
	}

	public function test_parse_hh_AM(): void
	{
		$this->formatter->setPattern('hh:mm a');
		$result = $this->formatter->parse('12:30 AM', false);
		$this->assertSame('00:30', date('H:i', $result));
	}

	public function test_parse_hh_PM(): void
	{
		$this->formatter->setPattern('hh:mm a');
		$result = $this->formatter->parse('12:30 PM', false);
		$this->assertSame('12:30', date('H:i', $result));
	}

	public function test_parse_hh_AM_noon(): void
	{
		$this->formatter->setPattern('hh:mm a');
		$result = $this->formatter->parse('12:00 AM', false);
		$this->assertSame('00:00', date('H:i', $result));
	}

	public function test_parse_hh_PM_noon(): void
	{
		$this->formatter->setPattern('hh:mm a');
		$result = $this->formatter->parse('12:00 PM', false);
		$this->assertSame('12:00', date('H:i', $result));
	}

	public function test_parse_h_AM(): void
	{
		$this->formatter->setPattern('h:mm a');
		$result = $this->formatter->parse('5:30 AM', false);
		$this->assertSame('05:30', date('H:i', $result));
	}

	public function test_parse_k_hour(): void
	{
		$this->formatter->setPattern('k:mm');
		$result = $this->formatter->parse('1:30', false);
		$this->assertSame('00:30', date('H:i', $result));

		$result = $this->formatter->parse('24:00', false);
		$this->assertSame('23:00', date('H:i', $result));
	}

	public function test_parse_invalid_k_hour_zero_returns_null(): void
	{
		$this->formatter->setPattern('k:mm');
		$result = $this->formatter->parse('0:30', false);
		$this->assertNull($result);
	}

	public function test_parse_K_hour_0_to_11(): void
	{
		$this->formatter->setPattern('K:mm');
		$result = $this->formatter->parse('0:30', false);
		$this->assertSame('00:30', date('H:i', $result));

		$result = $this->formatter->parse('11:30', false);
		$this->assertSame('11:30', date('H:i', $result));
	}

	public function test_parse_invalid_K_hour_12_returns_null(): void
	{
		$this->formatter->setPattern('K:mm');
		$result = $this->formatter->parse('12:30', false);
		$this->assertNull($result);
	}

	public function test_parse_mm_minute(): void
	{
		$this->formatter->setPattern('HH:mm');
		$result = $this->formatter->parse('12:05', false);
		$this->assertSame('12:05', date('H:i', $result));
	}

	public function test_parse_invalid_minute_60_returns_null(): void
	{
		$this->formatter->setPattern('HH:mm');
		$result = $this->formatter->parse('12:60', false);
		$this->assertNull($result);
	}

	public function test_parse_ss_second(): void
	{
		$this->formatter->setPattern('HH:mm:ss');
		$result = $this->formatter->parse('12:30:45', false);
		$this->assertSame('12:30:45', date('H:i:s', $result));
	}

	public function test_parse_invalid_second_60_returns_null(): void
	{
		$this->formatter->setPattern('HH:mm:ss');
		$result = $this->formatter->parse('12:30:60', false);
		$this->assertNull($result);
	}

	public function test_parse_input_too_long_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-04-17 extra', false);
		$this->assertNull($result);
	}

	public function test_parse_input_too_short_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-04', false);
		$this->assertNull($result);
	}

	public function test_parse_garbage_returns_null(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-ab-17', false);
		$this->assertNull($result);
	}

	public function test_parse_default_year_to_current(): void
	{
		$this->formatter->setPattern('yyyy');
		$result = $this->formatter->parse('2026', true);
		$expected = '2026' . date('-m-d');
		$this->assertSame($expected, date('Y-m-d', $result));
	}

	public function test_parse_default_month_to_current(): void
	{
		$this->formatter->setPattern('MM');
		$currentYear = date('Y');
		$result = $this->formatter->parse('04', true);
		$expected = "$currentYear-04" . date('-d');
		$this->assertSame($expected, date('Y-m-d', $result));
	}

	public function test_parse_default_day_to_current(): void
	{
		$this->formatter->setPattern('dd');
		$currentYearMonth = date('Y-m');
		$result = $this->formatter->parse('17', true);
		$this->assertSame("$currentYearMonth-17", date('Y-m-d', $result));
	}

	public function test_parse_a_PM_afternoon(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd hh:mm a');
		$result = $this->formatter->parse('2026-04-17 02:30 PM', false);
		$this->assertSame('2026-04-17 14:30:00', date('Y-m-d H:i:s', $result));
	}

	public function test_format_full_datetime(): void
	{
		$ts = strtotime('2026-04-17 14:30:45');
		$this->formatter->setPattern('yyyy-MM-dd HH:mm:ss');
		$this->assertSame('2026-04-17 14:30:45', $this->formatter->format($ts));
	}

	public function test_format_with_ampm(): void
	{
		$ts = strtotime('2026-04-17 14:30:45');
		$this->formatter->setPattern('MM/dd/yyyy hh:mm a');
		$this->assertSame('04/17/2026 02:30 PM', $this->formatter->format($ts));
	}

	public function test_format_EEEE_comma_MMMM_d_comma_yyyy(): void
	{
		$ts = strtotime('2026-04-10');
		$this->formatter->setPattern('EEEE, MMMM d, yyyy');
		$this->assertSame('Friday, April 10, 2026', $this->formatter->format($ts));
	}

	public function test_format_with_literal_strings(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern("yyyy 'Year' MM 'Month' dd 'Day'");
		$this->assertSame('2026 Year 04 Month 17 Day', $this->formatter->format($ts));
	}

	public function test_parse_with_literal_strings(): void
	{
		$this->formatter->setPattern("yyyy 'Year' MM 'Month' dd 'Day'");
		$result = $this->formatter->parse('2026 Year 04 Month 17 Day', false);
		$this->assertSame('2026-04-17', date('Y-m-d', $result));
	}

	public function test_round_trip_format_parse(): void
	{
		$original = strtotime('2026-04-17 14:30:45');
		$this->formatter->setPattern('yyyy-MM-dd HH:mm:ss');
		$formatted = $this->formatter->format($original);
		$parsed = $this->formatter->parse($formatted, false);
		$this->assertSame($formatted, date('Y-m-d H:i:s', $parsed));
	}

	public function test_round_trip_multiple_patterns(): void
	{
		$ts = strtotime('2026-04-17');
		$patterns = [
			'dd/MM/yy' => '17/04/26',
			'MMM d, yyyy' => 'Apr 17, 2026',
			'MMMM d, yyyy' => 'April 17, 2026',
			'dd-MM-yyyy' => '17-04-2026',
			'yyyy/MM/dd' => '2026/04/17',
		];
		foreach ($patterns as $pattern => $expected) {
			$this->formatter->setPattern($pattern);
			$this->assertSame($expected, $this->formatter->format($ts), "Failed for pattern: $pattern");
		}
	}

	public function test_is_valid_date_returns_true(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$this->assertTrue($this->formatter->isValidDate('2026-04-17'));
	}

	public function test_is_valid_date_invalid_month_returns_false(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$this->assertFalse($this->formatter->isValidDate('2026-13-17'));
	}

	public function test_is_valid_date_invalid_day_returns_false(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$this->assertFalse($this->formatter->isValidDate('2026-04-32'));
	}

	public function test_is_valid_date_feb_29_leap_returns_true(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$this->assertTrue($this->formatter->isValidDate('2024-02-29'));
	}

	public function test_is_valid_date_feb_29_non_leap_returns_false(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$this->assertFalse($this->formatter->isValidDate('2023-02-29'));
	}

	public function test_get_day_month_year_ordering_DMY(): void
	{
		$this->formatter->setPattern('dd-MM-yyyy');
		$ordering = $this->formatter->getDayMonthYearOrdering();
		$this->assertSame(['day', 'month', 'year'], $ordering);
	}

	public function test_get_day_month_year_ordering_MDY(): void
	{
		$this->formatter->setPattern('MM-dd-yyyy');
		$ordering = $this->formatter->getDayMonthYearOrdering();
		$this->assertSame(['month', 'day', 'year'], $ordering);
	}

	public function test_get_day_month_year_ordering_YMD(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$ordering = $this->formatter->getDayMonthYearOrdering();
		$this->assertSame(['year', 'month', 'day'], $ordering);
	}

	public function test_edge_case_midnight(): void
	{
		$ts = strtotime('2026-04-17 00:00:00');
		$this->formatter->setPattern('HH:mm:ss');
		$this->assertSame('00:00:00', $this->formatter->format($ts));
	}

	public function test_edge_case_noon(): void
	{
		$ts = strtotime('2026-04-17 12:00:00');
		$this->formatter->setPattern('HH:mm:ss');
		$this->assertSame('12:00:00', $this->formatter->format($ts));
	}

	public function test_edge_case_end_of_day(): void
	{
		$ts = strtotime('2026-04-17 23:59:59');
		$this->formatter->setPattern('HH:mm:ss');
		$this->assertSame('23:59:59', $this->formatter->format($ts));
	}

	public function test_format_leading_zeros(): void
	{
		$ts = strtotime('2026-01-01 01:01:01');
		$this->formatter->setPattern('yyyy-MM-dd HH:mm:ss');
		$this->assertSame('2026-01-01 01:01:01', $this->formatter->format($ts));
	}

	public function test_parse_january(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-01-15', false);
		$this->assertSame('2026-01-15', date('Y-m-d', $result));
	}

	public function test_parse_december(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-12-15', false);
		$this->assertSame('2026-12-15', date('Y-m-d', $result));
	}

	public function test_parse_all_months(): void
	{
		$this->formatter->setPattern('yyyy-M-d');
		for ($m = 1; $m <= 12; $m++) {
			$result = $this->formatter->parse("2026-$m-15", false);
			$this->assertNotNull($result, "Month $m should be valid");
			$this->assertSame("2026-" . sprintf('%02d', $m) . "-15", date('Y-m-d', $result));
		}
	}

	public function test_parse_all_days_in_month(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-01-31', false);
		$this->assertSame('2026-01-31', date('Y-m-d', $result));

		$result = $this->formatter->parse('2026-04-30', false);
		$this->assertSame('2026-04-30', date('Y-m-d', $result));
	}

	public function test_parse_february_28(): void
	{
		$this->formatter->setPattern('yyyy-MM-dd');
		$result = $this->formatter->parse('2026-02-28', false);
		$this->assertSame('2026-02-28', date('Y-m-d', $result));
	}

	public function test_parse_single_digit_day(): void
	{
		$this->formatter->setPattern('yyyy-M-d');
		$result = $this->formatter->parse('2026-4-5', false);
		$this->assertSame('2026-04-05', date('Y-m-d', $result));
	}

	public function test_parse_missing_year_with_pattern(): void
	{
		$this->formatter->setPattern('MM/dd');
		$this->assertNotNull($this->formatter->parse('10/22', true));
		$this->assertNotNull($this->formatter->parse('10/22', false));
	}

	public function test_parse_invalid_september_31_returns_null(): void
	{
		$this->formatter->setPattern('MM/dd');
		$this->assertNull($this->formatter->parse('09/31', false));
	}

	public function test_format_various_separators(): void
	{
		$ts = strtotime('2026-04-17');
		$this->formatter->setPattern('yyyy.MM.dd');
		$this->assertSame('2026.04.17', $this->formatter->format($ts));

		$this->formatter->setPattern('yyyy/MM/dd');
		$this->assertSame('2026/04/17', $this->formatter->format($ts));

		$this->formatter->setPattern('yyyy-MM-dd');
		$this->assertSame('2026-04-17', $this->formatter->format($ts));
	}
}