<?php

use Prado\IO\Stream\TFnStreamMethod;
use Psr\Http\Message\StreamInterface;

/**
 * Unit tests for {@see \Prado\IO\Stream\TFnStreamMethod}, the enumeration of PSR-7
 * {@see StreamInterface} method names used as {@see \Prado\IO\Stream\TFnStream} map keys.
 */
class TFnStreamMethodTest extends PHPUnit\Framework\TestCase
{
	private function enumValues(): array
	{
		return array_values((new \ReflectionClass(TFnStreamMethod::class))->getConstants());
	}

	public function testEveryConstantIsARealInterfaceMethod()
	{
		foreach ($this->enumValues() as $value) {
			self::assertTrue(
				method_exists(StreamInterface::class, $value),
				"TFnStreamMethod::* value '{$value}' is not a StreamInterface method."
			);
		}
	}

	public function testCoversTheWholeInterface()
	{
		$enum = $this->enumValues();
		sort($enum);
		$actual = get_class_methods(StreamInterface::class);
		sort($actual);
		self::assertSame($actual, $enum, 'The enum must list every StreamInterface method, and no extras.');
	}

	public function testValueLookupHelpers()
	{
		self::assertSame('read', TFnStreamMethod::Read);
		self::assertTrue(TFnStreamMethod::hasConstantValue('getMetadata'));
		self::assertFalse(TFnStreamMethod::hasConstantValue('notAMethod'));
		self::assertSame('Read', TFnStreamMethod::constantOfValue('read'));
	}
}
