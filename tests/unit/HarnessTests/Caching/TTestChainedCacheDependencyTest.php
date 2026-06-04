<?php

/**
 * TTestChainedCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TCacheDependencyList;
use Prado\Caching\TChainedCacheDependency;

/**
 * Tests for {@see TTestChainedCacheDependency}: the dependency-list factory and `*Direct`
 * accessor seams.
 *
 * @package System.Harness.Caching
 */
class TTestChainedCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	public function testIsAChainedCacheDependency(): void
	{
		$this->assertInstanceOf(TChainedCacheDependency::class, new TTestChainedCacheDependency());
	}

	public function testNewCacheDependencyListReturnsList(): void
	{
		$dep = new TTestChainedCacheDependency();
		$this->assertInstanceOf(TCacheDependencyList::class, $dep->pubNewCacheDependencyList());
	}

	public function testDependenciesDirectRoundTrips(): void
	{
		$dep = new TTestChainedCacheDependency();
		$this->assertNull($dep->pubGetDependenciesDirect());

		$list = $dep->pubNewCacheDependencyList();
		$dep->pubSetDependenciesDirect($list);
		$this->assertSame($list, $dep->pubGetDependenciesDirect());

		$dep->pubSetDependenciesDirect(null);
		$this->assertNull($dep->pubGetDependenciesDirect());
	}
}
