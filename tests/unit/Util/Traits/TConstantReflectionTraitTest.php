<?php

/**
 * TConstantReflectionTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Traits;

use Prado\Util\Traits\TArrayCopyIteratorTrait;
use Prado\Util\Traits\TConstantReflectionTrait;

/**
 * Consumer with constants that share prefixes, suffixes, and values.
 */
class TConstantReflectionTraitConsumer implements \Iterator
{
	use TArrayCopyIteratorTrait;
	use TConstantReflectionTrait;

	public const RedApple = 'red';
	public const GreenApple = 'green';
	public const RedCherry = 'cherry';
	public const Banana = 'Yellow';
	public const Item0 = 'zero';
}

/**
 * Subclass adding a constant.
 */
class TConstantReflectionTraitSubConsumer extends TConstantReflectionTraitConsumer
{
	public const Extra = 'extra';
}

/**
 * Unit tests for {@see \Prado\Util\Traits\TConstantReflectionTrait}.
 */
class TConstantReflectionTraitTest extends \PHPUnit\Framework\TestCase
{
	public function testHasConstant()
	{
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('RedApple'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('redapple'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('redapple', false));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('Missing', false));
	}

	public function testHasConstant_affix()
	{
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('RedApple', ''));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('RedApple', 'Red'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('RedApple', 'Green'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('RedApple', 'red'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('redapple', 'red', false));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('RedApple', '*Apple'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('RedApple', '-Apple'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('RedApple', '-apple', false));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('RedApple', '*Cherry'));
	}

	public function testAffixZeroFilters()
	{
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('RedApple', '0'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('RedApple', '*0'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstant('Item0', '*0'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstantValue('red', '*0'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstantValue('zero', '*0'));
		$this->assertNull(TConstantReflectionTraitConsumer::valueOfConstant('RedApple', '*0'));
		$this->assertSame('zero', TConstantReflectionTraitConsumer::valueOfConstant('Item0', '*0'));
		$this->assertNull(TConstantReflectionTraitConsumer::constantOfValue('red', '*0'));
		$this->assertSame('Item0', TConstantReflectionTraitConsumer::constantOfValue('zero', '*0'));
	}

	public function testHasConstantValue()
	{
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstantValue('red'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstantValue('YELLOW'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstantValue('YELLOW', false));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstantValue('purple', false));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstantValue('cherry', 'Red'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstantValue('green', 'Red'));
		$this->assertTrue(TConstantReflectionTraitConsumer::hasConstantValue('green', '*Apple'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstantValue('cherry', '*Apple'));
	}

	public function testValueOfConstant()
	{
		$this->assertSame('red', TConstantReflectionTraitConsumer::valueOfConstant('RedApple'));
		$this->assertNull(TConstantReflectionTraitConsumer::valueOfConstant('banana'));
		$this->assertSame('Yellow', TConstantReflectionTraitConsumer::valueOfConstant('banana', false));
		$this->assertNull(TConstantReflectionTraitConsumer::valueOfConstant('Missing'));
		$this->assertSame('cherry', TConstantReflectionTraitConsumer::valueOfConstant('RedCherry', 'Red'));
		$this->assertNull(TConstantReflectionTraitConsumer::valueOfConstant('GreenApple', 'Red'));
		$this->assertSame('green', TConstantReflectionTraitConsumer::valueOfConstant('greenapple', '*apple', false));
	}

	public function testConstantOfValue()
	{
		$this->assertSame('RedApple', TConstantReflectionTraitConsumer::constantOfValue('red'));
		$this->assertNull(TConstantReflectionTraitConsumer::constantOfValue('yellow'));
		$this->assertSame('Banana', TConstantReflectionTraitConsumer::constantOfValue('yellow', false));
		$this->assertNull(TConstantReflectionTraitConsumer::constantOfValue('purple'));
		$this->assertSame('RedCherry', TConstantReflectionTraitConsumer::constantOfValue('cherry', 'Red'));
		$this->assertNull(TConstantReflectionTraitConsumer::constantOfValue('green', 'Red'));
		$this->assertSame('GreenApple', TConstantReflectionTraitConsumer::constantOfValue('green', '-Apple'));
	}

	public function testSubclassReflectsItsOwnConstants()
	{
		$this->assertTrue(TConstantReflectionTraitSubConsumer::hasConstant('Extra'));
		$this->assertTrue(TConstantReflectionTraitSubConsumer::hasConstant('RedApple'));
		$this->assertFalse(TConstantReflectionTraitConsumer::hasConstant('Extra'));
		$this->assertSame('Extra', TConstantReflectionTraitSubConsumer::constantOfValue('extra'));
	}

	public function testIteratesTheConstants()
	{
		$this->assertSame(
			['RedApple' => 'red', 'GreenApple' => 'green', 'RedCherry' => 'cherry', 'Banana' => 'Yellow', 'Item0' => 'zero'],
			iterator_to_array(new TConstantReflectionTraitConsumer())
		);
		$this->assertArrayHasKey('Extra', iterator_to_array(new TConstantReflectionTraitSubConsumer()));
	}
}
