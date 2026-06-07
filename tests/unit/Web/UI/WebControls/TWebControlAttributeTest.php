<?php

use Prado\Web\UI\WebControls\TWebControlAttribute;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see \Prado\Web\UI\WebControls\TWebControlAttribute}.
 *
 * @covers \Prado\Web\UI\WebControls\TWebControlAttribute
 */
class TWebControlAttributeTest extends TestCase
{
	/** @return array<string,int> map of non-All constant names to expected values */
	private static function expectedConstants(): array
	{
		return [
			'Id'             => (1 << 0),
			'ARIA'           => (1 << 1),
			'Dataset'        => (1 << 2),
			'CustomAttributes' => (1 << 3),
			'AccessKey'      => (1 << 4),
			'Role'           => (1 << 5),
			'Disabled'       => (1 << 6),
			'TabIndex'       => (1 << 7),
			'Title'          => (1 << 8),
			'Translate'      => (1 << 9),
			'Lang'           => (1 << 10),
			'Dir'            => (1 << 11),
			'Hidden'         => (1 << 12),
			'SpellCheck'     => (1 << 13),
			'Draggable'      => (1 << 14),
			'ContentEditable' => (1 << 15),
			'InputMode'      => (1 << 16),
			'EnterKeyHint'   => (1 << 17),
			'Inert'          => (1 << 18),
			'Popover'        => (1 << 19),
		];
	}

	public function testAllConstantsExistWithCorrectValues()
	{
		foreach (self::expectedConstants() as $name => $expected) {
			$this->assertSame($expected, constant(TWebControlAttribute::class . '::' . $name), "Constant $name");
		}
	}

	public function testAllIsNegativeOne()
	{
		$this->assertSame(-1, TWebControlAttribute::All);
	}

	public function testAllConstantsArePowersOfTwo()
	{
		foreach (self::expectedConstants() as $name => $value) {
			$this->assertGreaterThan(0, $value, "Constant $name must be positive");
			$this->assertSame(0, $value & ($value - 1), "Constant $name must be a power of two (value: $value)");
		}
	}

	public function testNoTwoConstantsShareBits()
	{
		$constants = array_values(self::expectedConstants());
		$count = count($constants);
		for ($i = 0; $i < $count; $i++) {
			for ($j = $i + 1; $j < $count; $j++) {
				$this->assertSame(0, $constants[$i] & $constants[$j], "Constants at index $i and $j share a bit");
			}
		}
	}

	public function testAllCoversBitsOfEveryConstant()
	{
		foreach (self::expectedConstants() as $name => $value) {
			$this->assertSame($value, TWebControlAttribute::All & $value, "All must cover bit for $name");
		}
	}

	public function testBitwiseOrCompositionWorks()
	{
		$combined = TWebControlAttribute::Id | TWebControlAttribute::ARIA | TWebControlAttribute::Dataset;
		$this->assertSame((1 << 0) | (1 << 1) | (1 << 2), $combined);
	}

	public function testBitwiseOrCompositionCanBeTested()
	{
		$mask = TWebControlAttribute::Role | TWebControlAttribute::ARIA;
		$this->assertNotSame(0, $mask & TWebControlAttribute::Role);
		$this->assertNotSame(0, $mask & TWebControlAttribute::ARIA);
		$this->assertSame(0, $mask & TWebControlAttribute::Dataset);
	}

	public function testExtendsEnumerable()
	{
		$this->assertTrue(is_a(TWebControlAttribute::class, \Prado\TEnumerable::class, true));
	}
}
