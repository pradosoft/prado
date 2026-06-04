<?php

/**
 * TCacheDependencyListTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICacheDependency;
use Prado\Caching\TCacheDependencyList;
use Prado\Exceptions\TInvalidDataTypeException;

/**
 * Unit tests for {@see \Prado\Caching\TCacheDependencyList}.
 */
class TCacheDependencyListTest extends PHPUnit\Framework\TestCase
{
	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function dep(): ICacheDependency
	{
		return $this->createMock(ICacheDependency::class);
	}

	// -------------------------------------------------------------------------
	// Type enforcement
	// -------------------------------------------------------------------------

	public function testInsertValidDependencySucceeds(): void
	{
		$list = new TCacheDependencyList();
		$list->add($this->dep());
		$this->assertSame(1, $list->getCount());
	}

	public function testInsertNullThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$list = new TCacheDependencyList();
		$list->add(null);
	}

	public function testInsertStringThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$list = new TCacheDependencyList();
		$list->add('not-a-dependency');
	}

	public function testInsertIntThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$list = new TCacheDependencyList();
		$list->add(42);
	}

	public function testInsertPlainObjectThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$list = new TCacheDependencyList();
		$list->add(new stdClass());
	}

	public function testInsertAtValidPositionSucceeds(): void
	{
		$list = new TCacheDependencyList();
		$a = $this->dep();
		$b = $this->dep();
		$c = $this->dep();

		$list->add($a);
		$list->add($c);
		$list->insertAt(1, $b); // insert b between a and c

		$this->assertSame(3, $list->getCount());
		$this->assertSame($a, $list->itemAt(0));
		$this->assertSame($b, $list->itemAt(1));
		$this->assertSame($c, $list->itemAt(2));
	}

	public function testInsertAtWithInvalidItemThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$list = new TCacheDependencyList();
		$list->insertAt(0, 'bad');
	}

	// -------------------------------------------------------------------------
	// Count
	// -------------------------------------------------------------------------

	public function testCountStartsAtZero(): void
	{
		$list = new TCacheDependencyList();
		$this->assertSame(0, $list->getCount());
	}

	public function testCountIncrementsOnAdd(): void
	{
		$list = new TCacheDependencyList();
		$list->add($this->dep());
		$list->add($this->dep());
		$this->assertSame(2, $list->getCount());
	}

	// -------------------------------------------------------------------------
	// Iteration and ordering
	// -------------------------------------------------------------------------

	public function testIterationPreservesFifoOrder(): void
	{
		$list = new TCacheDependencyList();
		$a = $this->dep();
		$b = $this->dep();
		$c = $this->dep();

		$list->add($a);
		$list->add($b);
		$list->add($c);

		$items = [];
		foreach ($list as $item) {
			$items[] = $item;
		}
		$this->assertSame([$a, $b, $c], $items);
	}

	// -------------------------------------------------------------------------
	// Remove / contains
	// -------------------------------------------------------------------------

	public function testContainsReturnsTrueForAddedItem(): void
	{
		$list = new TCacheDependencyList();
		$dep  = $this->dep();
		$list->add($dep);
		$this->assertTrue($list->contains($dep));
	}

	public function testContainsReturnsFalseForUnaddedItem(): void
	{
		$list = new TCacheDependencyList();
		$list->add($this->dep());
		$this->assertFalse($list->contains($this->dep()));
	}

	public function testRemoveDecrementsCount(): void
	{
		$list = new TCacheDependencyList();
		$dep  = $this->dep();
		$list->add($dep);
		$list->add($this->dep());
		$list->remove($dep);
		$this->assertSame(1, $list->getCount());
	}

	public function testRemoveExcludesItemFromContains(): void
	{
		$list = new TCacheDependencyList();
		$dep  = $this->dep();
		$list->add($dep);
		$list->remove($dep);
		$this->assertFalse($list->contains($dep));
	}

	// -------------------------------------------------------------------------
	// Clear
	// -------------------------------------------------------------------------

	public function testClearEmptiesList(): void
	{
		$list = new TCacheDependencyList();
		$list->add($this->dep());
		$list->add($this->dep());
		$list->clear();
		$this->assertSame(0, $list->getCount());
	}

	// -------------------------------------------------------------------------
	// Multiple valid dependencies
	// -------------------------------------------------------------------------

	public function testMultipleValidDependenciesAccepted(): void
	{
		$list = new TCacheDependencyList();
		for ($i = 0; $i < 5; $i++) {
			$list->add($this->dep());
		}
		$this->assertSame(5, $list->getCount());
	}
}
