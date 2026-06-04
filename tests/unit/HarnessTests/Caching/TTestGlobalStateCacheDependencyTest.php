<?php

/**
 * TTestGlobalStateCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TGlobalStateCacheDependency;

/**
 * Tests for {@see TTestGlobalStateCacheDependency}: the state-name `*Direct` seams.
 *
 * @package System.Harness.Caching
 */
class TTestGlobalStateCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	public function testIsAGlobalStateCacheDependency(): void
	{
		$dep = new TTestGlobalStateCacheDependency('MyState');
		$this->assertInstanceOf(TGlobalStateCacheDependency::class, $dep);
	}

	public function testStateNameDirectRoundTrips(): void
	{
		$dep = new TTestGlobalStateCacheDependency('MyState');
		$this->assertSame('MyState', $dep->pubGetStateNameDirect());
		$dep->pubSetStateNameDirect('Other');
		$this->assertSame('Other', $dep->pubGetStateNameDirect());
		$this->assertSame('Other', $dep->getStateName());
	}
}
