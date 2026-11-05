<?php

/**
 * THttpHeaderRangeTest
 *
 * Unit tests for {@see \Prado\Web\HttpHeaders\THttpHeaderRange}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\HttpHeaders;

use Prado\Web\HttpHeaders\THttpHeaderRange;
use Prado\Web\THttpHeaderName;

class THttpHeaderRangeTest extends \PHPUnit\Framework\TestCase
{
	private function range(?string $value): THttpHeaderRange
	{
		$range = new THttpHeaderRange();
		$range->setHeaderValue($value);
		return $range;
	}

	// -----------------------------------------------------------------------
	// Name and defaults
	// -----------------------------------------------------------------------

	public function testGetHeaderNameReturnsRange(): void
	{
		self::assertSame(THttpHeaderName::Range, (new THttpHeaderRange())->getHeaderName());
	}

	public function testDefaultIsInvalidAndEmpty(): void
	{
		$range = new THttpHeaderRange();
		self::assertFalse($range->getIsValid());
		self::assertSame('', $range->getUnit());
		self::assertSame([], $range->getRanges());
		self::assertSame('', $range->getHeaderValue());
		self::assertNull($range->resolve(100));
	}

	// -----------------------------------------------------------------------
	// Parsing
	// -----------------------------------------------------------------------

	public static function validProvider(): array
	{
		return [
			'first-last' => ['bytes=0-499', 'bytes', [[0, 499]]],
			'open end' => ['bytes=500-', 'bytes', [[500, null]]],
			'suffix' => ['bytes=-500', 'bytes', [[null, 500]]],
			'single byte' => ['bytes=7-7', 'bytes', [[7, 7]]],
			'unit case' => ['BYTES=1-2', 'bytes', [[1, 2]]],
			'whitespace' => ["  bytes= 1-2 ,\t-3 ", 'bytes', [[1, 2], [null, 3]]],
			'empty elements' => ['bytes=,1-2,,', 'bytes', [[1, 2]]],
			'leading zeros' => ['bytes=007-010', 'bytes', [[7, 10]]],
			'multiple' => ['bytes=0-1,5-', 'bytes', [[0, 1], [5, null]]],
			'other unit' => ['items=0-4', 'items', [[0, 4]]],
			'saturates' => ['bytes=0-99999999999999999999999', 'bytes', [[0, PHP_INT_MAX]]],
		];
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testSetHeaderValueParses(string $value, string $unit, array $ranges): void
	{
		$range = $this->range($value);
		self::assertTrue($range->getIsValid());
		self::assertSame($unit, $range->getUnit());
		self::assertSame($ranges, $range->getRanges());
	}

	public static function invalidProvider(): array
	{
		return [
			'empty' => [''],
			'no unit' => ['0-499'],
			'no ranges' => ['bytes='],
			'only commas' => ['bytes=,,'],
			'bare dash' => ['bytes=-'],
			'reversed' => ['bytes=500-499'],
			'negative' => ['bytes=--5'],
			'letters' => ['bytes=a-b'],
			'one bad of two' => ['bytes=0-1,x'],
			'space in range' => ['bytes=0 - 1'],
			'decimal' => ['bytes=1.5-2'],
		];
	}

	/**
	 * @dataProvider invalidProvider
	 */
	public function testSetHeaderValueRejects(string $value): void
	{
		$range = $this->range($value);
		self::assertFalse($range->getIsValid());
		self::assertSame('', $range->getUnit());
		self::assertNull($range->resolve(1000));
	}

	public function testSetHeaderValueNullClears(): void
	{
		$range = $this->range('bytes=0-1');
		$range->setHeaderValue(null);
		self::assertFalse($range->getIsValid());
	}

	public function testGetHeaderValueRenders(): void
	{
		self::assertSame('bytes=0-499,500-,-20', $this->range(' Bytes=0-499, 500-, -20 ')->getHeaderValue());
		self::assertSame('Range: bytes=5-', (string) $this->range('bytes=5-'));
	}

	// -----------------------------------------------------------------------
	// resolve
	// -----------------------------------------------------------------------

	public static function resolveProvider(): array
	{
		return [
			'first-last' => ['bytes=0-499', 1000, [0, 499]],
			'last clamped' => ['bytes=900-2000', 1000, [900, 999]],
			'open end' => ['bytes=500-', 1000, [500, 999]],
			'last byte' => ['bytes=999-', 1000, [999, 999]],
			'suffix' => ['bytes=-100', 1000, [900, 999]],
			'suffix whole' => ['bytes=-5000', 1000, [0, 999]],
			'saturated last' => ['bytes=1-99999999999999999999999', 10, [1, 9]],
			'first past end' => ['bytes=1000-', 1000, false],
			'first far past end' => ['bytes=99999999999999999999999-', 1000, false],
			'suffix zero' => ['bytes=-0', 1000, false],
			'empty first' => ['bytes=0-', 0, false],
			'empty suffix' => ['bytes=-1', 0, false],
			'disjoint' => ['bytes=0-1,5-6', 1000, null],
			'adjoining' => ['bytes=0-99,100-199', 1000, [0, 199]],
			'overlapping' => ['bytes=0-150,100-199', 1000, [0, 199]],
			'contained' => ['bytes=0-199,50-60', 1000, [0, 199]],
			'out of order' => ['bytes=100-199,0-99', 1000, [0, 199]],
			'same range twice' => ['bytes=5-9,5-9', 1000, [5, 9]],
			'open end absorbs' => ['bytes=500-,600-700', 1000, [500, 999]],
			'suffix adjoins' => ['bytes=0-899,-100', 1000, [0, 999]],
			'chain of three' => ['bytes=0-9,20-29,10-19', 1000, [0, 29]],
			'gap of one byte' => ['bytes=0-9,11-19', 1000, null],
			'unsatisfiable dropped' => ['bytes=0-9,5000-', 1000, [0, 9]],
			'all unsatisfiable' => ['bytes=1000-,-0', 1000, false],
			'clamped then merged' => ['bytes=990-2000,980-989', 1000, [980, 999]],
			'other unit' => ['items=0-4', 1000, null],
		];
	}

	/**
	 * @dataProvider resolveProvider
	 */
	public function testResolve(string $value, int $size, array|false|null $expected): void
	{
		self::assertSame($expected, $this->range($value)->resolve($size));
	}
}
