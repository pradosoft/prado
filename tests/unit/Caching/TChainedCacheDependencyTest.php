<?php

/**
 * TChainedCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICacheDependency;
use Prado\Caching\TChainedCacheDependency;
use Prado\Caching\TCacheDependencyList;

/**
 * Unit tests for {@see \Prado\Caching\TChainedCacheDependency}.
 */
class TChainedCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/** Returns a mock ICacheDependency that will report getHasChanged() = $changed. */
	private function dep(bool $changed): ICacheDependency
	{
		$mock = $this->createMock(ICacheDependency::class);
		$mock->method('getHasChanged')->willReturn($changed);
		return $mock;
	}

	// -------------------------------------------------------------------------
	// getDependencies — lazy initialization
	// -------------------------------------------------------------------------

	public function testGetDependenciesCreatesListOnFirstAccess(): void
	{
		$chain = new TChainedCacheDependency();
		$list  = $chain->getDependencies();
		$this->assertInstanceOf(TCacheDependencyList::class, $list);
	}

	public function testGetDependenciesReturnsSameInstanceOnRepeatCalls(): void
	{
		$chain = new TChainedCacheDependency();
		$this->assertSame($chain->getDependencies(), $chain->getDependencies());
	}

	public function testGetDependenciesListStartsEmpty(): void
	{
		$chain = new TChainedCacheDependency();
		$this->assertSame(0, $chain->getDependencies()->getCount());
	}

	// -------------------------------------------------------------------------
	// newCacheDependencyList override
	// -------------------------------------------------------------------------

	public function testNewCacheDependencyListCanBeOverridden(): void
	{
		$customList = new TCacheDependencyList();

		$chain = new class($customList) extends TChainedCacheDependency {
			private TCacheDependencyList $_custom;
			public function __construct(TCacheDependencyList $list)
			{
				$this->_custom = $list;
				parent::__construct();
			}
			protected function newCacheDependencyList(): TCacheDependencyList
			{
				return $this->_custom;
			}
		};

		$this->assertSame($customList, $chain->getDependencies());
	}

	// -------------------------------------------------------------------------
	// getHasChanged
	// -------------------------------------------------------------------------

	public function testGetHasChangedFalseWhenChainIsEmpty(): void
	{
		$chain = new TChainedCacheDependency();
		$this->assertFalse($chain->getHasChanged());
	}

	public function testGetHasChangedFalseWhenAllUnchanged(): void
	{
		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add($this->dep(false));
		$chain->getDependencies()->add($this->dep(false));
		$chain->getDependencies()->add($this->dep(false));
		$this->assertFalse($chain->getHasChanged());
	}

	public function testGetHasChangedTrueWhenOneChanged(): void
	{
		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add($this->dep(false));
		$chain->getDependencies()->add($this->dep(true));
		$chain->getDependencies()->add($this->dep(false));
		$this->assertTrue($chain->getHasChanged());
	}

	public function testGetHasChangedTrueWhenFirstChanged(): void
	{
		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add($this->dep(true));
		$chain->getDependencies()->add($this->dep(false));
		$this->assertTrue($chain->getHasChanged());
	}

	public function testGetHasChangedTrueWhenLastChanged(): void
	{
		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add($this->dep(false));
		$chain->getDependencies()->add($this->dep(true));
		$this->assertTrue($chain->getHasChanged());
	}

	public function testGetHasChangedTrueWhenAllChanged(): void
	{
		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add($this->dep(true));
		$chain->getDependencies()->add($this->dep(true));
		$this->assertTrue($chain->getHasChanged());
	}

	public function testGetHasChangedShortCircuitsOnFirstChanged(): void
	{
		// The second dependency must not be evaluated once the first reports changed.
		$first  = $this->createMock(ICacheDependency::class);
		$second = $this->createMock(ICacheDependency::class);

		$first->expects($this->once())->method('getHasChanged')->willReturn(true);
		$second->expects($this->never())->method('getHasChanged');

		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add($first);
		$chain->getDependencies()->add($second);

		$this->assertTrue($chain->getHasChanged());
	}

	public function testGetHasChangedBeforeListIsCreated(): void
	{
		// When the internal list is never created (getDependencies never called),
		// getHasChanged must still return false gracefully.
		$chain = new TChainedCacheDependency();
		$this->assertFalse($chain->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// Serialization round-trip
	// -------------------------------------------------------------------------

	public function testSerializationPreservesDependencies(): void
	{
		// Use a concrete, serializable dependency for the round-trip.
		$file = __DIR__ . '/temp/TChainedCacheDep_serial.txt';
		file_put_contents($file, 'x');
		clearstatcache();

		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add(new \Prado\Caching\TFileCacheDependency($file));

		$restored = unserialize(serialize($chain));

		$this->assertSame(1, $restored->getDependencies()->getCount());
		$this->assertFalse($restored->getHasChanged());

		unlink($file);
	}

	public function testGetHasChangedAfterSerializationWhenDependencyChanged(): void
	{
		$file = __DIR__ . '/temp/TChainedCacheDep_changed.txt';
		file_put_contents($file, 'x');
		clearstatcache();

		$chain = new TChainedCacheDependency();
		$chain->getDependencies()->add(new \Prado\Caching\TFileCacheDependency($file));
		$serialized = serialize($chain);

		touch($file, filemtime($file) - 30);
		clearstatcache();

		$restored = unserialize($serialized);
		$this->assertTrue($restored->getHasChanged());

		unlink($file);
	}
}
