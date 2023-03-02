<?php

use Prado\Util\TSimpleDateFormatter;

class TSimpleDateFormatterTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TSimpleDateFormatter('');
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\Prado\Util\TSimpleDateFormatter', $this->obj);
	}

	public function testPattern()
	{
		$pattern = 'dd-mm-YY';
		$this->obj->setPattern($pattern);
		$this->assertSame($this->obj->getPattern(), $pattern);
	}

	public function testCharset()
	{
		$charset = 'en_US';
		$this->obj->setCharset($charset);
		$this->assertSame($this->obj->getCharset(), $charset);
	}

	public function testParse()
	{
		// test timestamp
		$ts = time();
		$this->assertSame($ts, $this->obj->parse($ts));

		// test not string
		$this->expectException('\Prado\Exceptions\TInvalidDataValueException');
		$this->obj->parse(['test', 'array']);

		// test empty pattern
		$this->obj->setPattern('');
		$ts = time();
		$this->assertSame($ts, $this->obj->parse('123456'));

		// test empty value
		$this->obj->setPattern('d-m-Y');
		$this->assertSame($ts, $this->obj->parse('', true));
		$this->assertNull($this->obj->parse('', false));

		// test zero time
		$this->obj->setPattern('d-m-Y');
		$this->assertSame('00:00:00', date('H:i:s', $this->obj->parse("15-12-1982")));
	}

	public function testParseYearPattern()
	{
		// default to current date = false
		$this->obj->setPattern('yyyy');
		$this->assertSame(date('2008-01-01'), date('Y-m-d', $this->obj->parse("2008", false)));

		$this->obj->setPattern('yy');
		$this->assertSame(date('2008-01-01'), date('Y-m-d', $this->obj->parse("08", false)));

		$this->obj->setPattern('y');
		$this->assertSame(date('2008-01-01'), date('Y-m-d', $this->obj->parse("08", false)));
		$this->assertSame(date('2008-01-01'), date('Y-m-d', $this->obj->parse("2008", false)));

		// test 2 digit year conversion
		$this->obj->setPattern('yy');
		$this->assertSame(date('2070-01-01'), date('Y-m-d', $this->obj->parse("70", false)));
		$this->assertSame(date('1971-01-01'), date('Y-m-d', $this->obj->parse("71", false)));

		// test wrong year
		$this->obj->setPattern('yyyy');
		$this->assertNull($this->obj->parse('aaaa', false));

		// default to current date = true
		$this->obj->setPattern('yyyy');
		$this->assertSame(date('2008-m-d'), date('Y-m-d', $this->obj->parse("2008", true)));

		$this->obj->setPattern('yy');
		$this->assertSame(date('2008-m-d'), date('Y-m-d', $this->obj->parse("08", true)));

		$this->obj->setPattern('y');
		$this->assertSame(date('2008-m-d'), date('Y-m-d', $this->obj->parse("08", true)));
		$this->assertSame(date('2008-m-d'), date('Y-m-d', $this->obj->parse("2008", true)));

		// test 2 digit year conversion
		$this->obj->setPattern('yy');
		$this->assertSame(date('2070-m-d'), date('Y-m-d', $this->obj->parse("70", true)));
		$this->assertSame(date('1971-m-d'), date('Y-m-d', $this->obj->parse("71", true)));

		// test wrong year
		$this->obj->setPattern('yyyy');
		$this->assertNull($this->obj->parse('aaaa', true));

		// test missing year
		$this->obj->setPattern("MM/dd");
		$this->assertSame(date("Y-10-22"), date('Y-m-d', $this->obj->parse('10/22', true)));
		$this->assertSame(date("Y-10-22"), date('Y-m-d', $this->obj->parse('10/22', false)));
		
		$this->assertSame(date("Y-10-01"), date('Y-m-d', $this->obj->parse('09/31', false)));
	}

	public function testParseMonthPattern()
	{
		// default to current date = false
		$this->obj->setPattern('MM');
		$this->assertSame(date('Y-09-01'), date('Y-m-d', $this->obj->parse('09', false)));

		$this->obj->setPattern('M');
		$this->assertSame(date('Y-09-01'), date('Y-m-d', $this->obj->parse("9", false)));
		$this->assertSame(date('Y-09-01'), date('Y-m-d', $this->obj->parse("09", false)));

		// test wrong month
		$this->obj->setPattern('MM');
		$this->assertNull($this->obj->parse('13', false));
		$this->assertNull($this->obj->parse('0', false));

		// default to current date = true
		$this->obj->setPattern('MM');
		$this->assertSame(date('Y-08-d'), date('Y-m-d', $this->obj->parse('08', true)));

		$this->obj->setPattern('M');
		$this->assertSame(date('Y-08-d'), date('Y-m-d', $this->obj->parse("8", true)));
		$this->assertSame(date('Y-08-d'), date('Y-m-d', $this->obj->parse("08", true)));

		// test wrong month
		$this->assertNull($this->obj->parse('13', true));
		$this->assertNull($this->obj->parse('0', true));

		// test missing month
		$this->obj->setPattern("yy/dd");
		$this->assertSame(date("2019-01-22"), date('Y-m-d', $this->obj->parse("19/22", false)));
		$this->assertSame(date("2019-m-22"), date('Y-m-d', $this->obj->parse("19/22", true)));
	}

	public function testParseDayPattern()
	{
		// default to current date = false
		$this->obj->setPattern('dd');
		$this->assertSame(date('Y-01-09'), date('Y-m-d', $this->obj->parse('09', false)));

		$this->obj->setPattern('d');
		$this->assertSame(date('Y-01-09'), date('Y-m-d', $this->obj->parse("9", false)));
		$this->assertSame(date('Y-01-09'), date('Y-m-d', $this->obj->parse("09", false)));

		// test wrong day
		$this->obj->setPattern('dd');
		$this->assertNull($this->obj->parse('32', false));
		$this->assertNull($this->obj->parse('0', false));

		// default to current date = true
		$this->obj->setPattern('dd');
		$this->assertSame(date('Y-m-09'), date('Y-m-d', $this->obj->parse('09', true)));

		$this->obj->setPattern('d');
		$this->assertSame(date('Y-m-09'), date('Y-m-d', $this->obj->parse("9", true)));
		$this->assertSame(date('Y-m-09'), date('Y-m-d', $this->obj->parse("09", true)));

		// test wrong day
		$this->obj->setPattern('dd');
		$this->assertNull($this->obj->parse('32', true));
		$this->assertNull($this->obj->parse('0', true));

		// test missing month
		$this->obj->setPattern("yy/MM");
		$this->assertSame(date("2019-08-01"), date('Y-m-d', $this->obj->parse("19/08", false)));
		$this->assertSame(date("2019-08-d"), date('Y-m-d', $this->obj->parse("19/08", true)));
	}

	public function testIsValidDate()
	{
		$this->obj->setPattern('d-M-y');
		$this->assertTrue($this->obj->isValidDate('15-12-1982'));
		$this->assertFalse($this->obj->isValidDate('32-02-2019'));
		$this->assertFalse($this->obj->isValidDate('15-13-2019'));
	}

	public function testDayMonthYearOrdering()
	{
		$this->obj->setPattern('d-M-yy');
		$this->assertSame(['day', 'month', 'year'], $this->obj->getDayMonthYearOrdering());

		$this->obj->setPattern('M-d-yy');
		$this->assertSame(['month', 'day', 'year'], $this->obj->getDayMonthYearOrdering());

		$this->obj->setPattern('yyyy-M-d');
		$this->assertSame(['year', 'month', 'day'], $this->obj->getDayMonthYearOrdering());
	}

	public function testFormat()
	{
		$step = 100000000;
		$max = 3000000000;
		for($ts = 0; $ts < $max; $ts += $step)
		{
			$this->obj->setPattern('d-dd-M-MM-yy-yyyy');
			$this->assertSame(date('j-d-n-m-y-Y', $ts), $this->obj->format($ts));
		}
	}
}
