<?php

/**
 * TInitializedTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Traits;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Traits\TInitializedTrait;

/**
 * Consumer of the trait.
 */
class TInitializedTraitConsumer
{
	use TInitializedTrait;
}

/**
 * Consumer that overrides the default exception keys.
 */
class TInitializedTraitKeyConsumer
{
	use TInitializedTrait;

	protected function getIsInitializedExceptionKey(): string
	{
		return 'component_property_readonly';
	}

	protected function getIsNotInitializedExceptionKey(): string
	{
		return 'component_property_undefined';
	}
}

/**
 * Unit tests for {@see \Prado\Util\Traits\TInitializedTrait}.
 */
class TInitializedTraitTest extends \PHPUnit\Framework\TestCase
{
	private function assertState(object $consumer, bool $uninitialized, bool $initializing, bool $initialized): void
	{
		$this->assertSame($uninitialized, $consumer->getIsUninitialized());
		$this->assertSame($initializing, $consumer->getIsInitializing());
		$this->assertSame($initialized, $consumer->getIsInitialized());
	}

	public function testStateTransitions()
	{
		$consumer = new TInitializedTraitConsumer();
		$this->assertState($consumer, true, false, false);

		PradoUnit::invoke($consumer, 'markStartInitialize');
		$this->assertState($consumer, false, true, false);

		PradoUnit::invoke($consumer, 'markInitialized');
		$this->assertState($consumer, false, false, true);

		PradoUnit::invoke($consumer, 'resetInitialized');
		$this->assertState($consumer, true, false, false);
	}

	public function testMarkStartInitializeKeepsTheInitializedState()
	{
		$consumer = new TInitializedTraitConsumer();
		PradoUnit::invoke($consumer, 'markInitialized');

		PradoUnit::invoke($consumer, 'markStartInitialize');

		$this->assertState($consumer, false, false, true);
	}

	public function testAssertUninitialized()
	{
		$consumer = new TInitializedTraitConsumer();
		PradoUnit::invoke($consumer, 'assertUninitialized', 'Path');

		PradoUnit::invoke($consumer, 'markStartInitialize');
		try {
			PradoUnit::invoke($consumer, 'assertUninitialized', 'Path');
			$this->fail('assertUninitialized should throw while initializing.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('initialized_property_unchangeable', $e->getErrorCode());
			$this->assertStringContainsString('TInitializedTraitConsumer.Path', $e->getMessage());
		}
	}

	public function testAssertUninitialized_customKey()
	{
		$consumer = new TInitializedTraitConsumer();
		PradoUnit::invoke($consumer, 'markInitialized');

		try {
			PradoUnit::invoke($consumer, 'assertUninitialized', 'Path', 'component_property_readonly');
			$this->fail('assertUninitialized should throw after initialization.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('component_property_readonly', $e->getErrorCode());
		}
	}

	public function testAssertInitialized()
	{
		$consumer = new TInitializedTraitConsumer();
		foreach (['resetInitialized', 'markStartInitialize'] as $mark) {
			PradoUnit::invoke($consumer, $mark);
			try {
				PradoUnit::invoke($consumer, 'assertInitialized', 'run');
				$this->fail('assertInitialized should throw before initialization completes.');
			} catch (TInvalidOperationException $e) {
				$this->assertSame('initialized_requires_initialization', $e->getErrorCode());
				$this->assertStringContainsString('TInitializedTraitConsumer.run', $e->getMessage());
			}
		}

		PradoUnit::invoke($consumer, 'markInitialized');
		PradoUnit::invoke($consumer, 'assertInitialized', 'run');
		$this->assertTrue($consumer->getIsInitialized());
	}

	public function testOverriddenExceptionKeys()
	{
		$consumer = new TInitializedTraitKeyConsumer();
		try {
			PradoUnit::invoke($consumer, 'assertInitialized', 'run');
			$this->fail('assertInitialized should throw before initialization.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('component_property_undefined', $e->getErrorCode());
		}

		PradoUnit::invoke($consumer, 'markInitialized');
		try {
			PradoUnit::invoke($consumer, 'assertUninitialized', 'Path');
			$this->fail('assertUninitialized should throw after initialization.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('component_property_readonly', $e->getErrorCode());
		}
	}
}
