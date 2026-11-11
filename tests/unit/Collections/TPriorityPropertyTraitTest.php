<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TPriorityPropertyTrait;

class TPriorityPropertyTraitFixture
{
	use TPriorityPropertyTrait;

	public function getZappableProps(): array
	{
		$exprops = [];
		$this->_priorityItemZappableSleepProps($exprops);
		return $exprops;
	}
}

class TPriorityPropertyTraitTest extends \PHPUnit\Framework\TestCase
{
	public function testPriorityDefaultsToNull()
	{
		$item = new TPriorityPropertyTraitFixture();
		self::assertNull($item->getPriority());
	}

	public function testSetPriorityConvertsNumericToFloat()
	{
		$item = new TPriorityPropertyTraitFixture();
		self::assertSame($item, $item->setPriority(5));
		self::assertSame(5.0, $item->getPriority());
		$item->setPriority('7.5');
		self::assertSame(7.5, $item->getPriority());
	}

	public function testSetPriorityEmptyStringOrNullIsNull()
	{
		$item = new TPriorityPropertyTraitFixture();
		$item->setPriority(5);
		$item->setPriority('');
		self::assertNull($item->getPriority());
		$item->setPriority(5);
		$item->setPriority(null);
		self::assertNull($item->getPriority());
	}

	public function testZappableSleepPropsExcludeOnlyUnsetPriority()
	{
		$item = new TPriorityPropertyTraitFixture();
		self::assertSame(["\0" . TPriorityPropertyTraitFixture::class . "\0_priority"], $item->getZappableProps());
		$item->setPriority(3);
		self::assertSame([], $item->getZappableProps());
	}
}
