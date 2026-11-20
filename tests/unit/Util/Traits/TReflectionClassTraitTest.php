<?php

/**
 * TReflectionClassTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Traits;

use Prado\Util\Traits\TReflectionClassTrait;

/**
 * Consumer of the trait.
 */
class TReflectionClassTraitConsumer
{
	use TReflectionClassTrait;
}

/**
 * Subclass that inherits the trait.
 */
class TReflectionClassTraitSubConsumer extends TReflectionClassTraitConsumer
{
}

/**
 * Unit tests for {@see \Prado\Util\Traits\TReflectionClassTrait}.
 */
class TReflectionClassTraitTest extends \PHPUnit\Framework\TestCase
{
	public function testReturnsTheCachedReflectionOfTheCallingClass()
	{
		$reflection = TReflectionClassTraitConsumer::getReflectionClass();

		$this->assertSame(TReflectionClassTraitConsumer::class, $reflection->getName());
		$this->assertSame($reflection, TReflectionClassTraitConsumer::getReflectionClass());
	}

	public function testSubclassReceivesItsOwnReflection()
	{
		$reflection = TReflectionClassTraitSubConsumer::getReflectionClass();

		$this->assertSame(TReflectionClassTraitSubConsumer::class, $reflection->getName());
		$this->assertNotSame(TReflectionClassTraitConsumer::getReflectionClass(), $reflection);
	}
}
