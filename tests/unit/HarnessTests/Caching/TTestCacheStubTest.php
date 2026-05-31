<?php

/**
 * TTestCacheStubTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICache;

/**
 * Tests for {@see TTestCacheStub}, the recording {@see ICache} stub.
 *
 * @package System.Harness.Caching
 */
class TTestCacheStubTest extends PHPUnit\Framework\TestCase
{
	public function testImplementsICache(): void
	{
		$this->assertInstanceOf(ICache::class, new TTestCacheStub());
	}

	public function testGet_returnsConfiguredValueAndRecordsCall(): void
	{
		$stub = new TTestCacheStub();
		$stub->getReturn = ['a' => 1];

		$this->assertSame(['a' => 1], $stub->get('key1'));
		$this->assertSame(['a' => 1], $stub->get('key2'));
		$this->assertSame([['key1'], ['key2']], $stub->getCollectedCalls('get'));
	}

	public function testGet_defaultsToFalseMiss(): void
	{
		$this->assertFalse((new TTestCacheStub())->get('any'));
	}

	public function testGet_closureReturnIsInvokedWithId(): void
	{
		$stub = new TTestCacheStub();
		$stub->getReturn = fn($id) => 'value-for-' . $id;

		$this->assertSame('value-for-alpha', $stub->get('alpha'));
		$this->assertSame('value-for-beta', $stub->get('beta'));
	}

	public function testSet_recordsPositionalArguments(): void
	{
		$stub = new TTestCacheStub();
		$dependency = new \stdClass();

		$this->assertTrue($stub->set('id', ['v'], 42, $dependency));
		$this->assertSame(1, $stub->getCollectedCallCount('set'));
		$this->assertSame(['id', ['v'], 42, $dependency], $stub->getCollectedCalls('set')[0]);
	}

	public function testSet_recordsOnlyPassedArguments(): void
	{
		// debug_backtrace captures the arguments actually passed; omitted
		// defaults (expire, dependency) are not recorded.
		$stub = new TTestCacheStub();
		$stub->set('id', 'v');
		$this->assertSame(['id', 'v'], $stub->getCollectedCalls('set')[0]);
	}

	public function testAddDeleteFlush_areNoOpTrueAndRecorded(): void
	{
		$stub = new TTestCacheStub();
		$this->assertTrue($stub->add('id', 'v'));
		$this->assertTrue($stub->delete('id'));
		$this->assertTrue($stub->flush());

		$this->assertSame(1, $stub->getCollectedCallCount('add'));
		$this->assertSame(1, $stub->getCollectedCallCount('delete'));
		$this->assertSame(1, $stub->getCollectedCallCount('flush'));
	}

	public function testCollectedCallLog_isSequentialAcrossMethods(): void
	{
		$stub = new TTestCacheStub();
		$stub->get('g');
		$stub->set('s', 1);

		$log = $stub->getCollectedCalls();
		$this->assertSame('get', $log[0]['method']);
		$this->assertSame(['g'], $log[0]['args']);
		$this->assertSame('set', $log[1]['method']);
	}
}
