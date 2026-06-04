<?php

/**
 * TGlobalStateCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/../PradoUnitRequires.php';

use Prado\Caching\TGlobalStateCacheDependency;
use Prado\Prado;

/**
 * Unit tests for {@see \Prado\Caching\TGlobalStateCacheDependency}.
 *
 * The test application started by the phpunit bootstrap provides
 * `Prado::getApplication()`, which these tests use to read and write global state.
 * All global state keys used here are prefixed with `TGlobalStateCacheDependencyTest_`
 * to avoid collisions with other tests.
 */
class TGlobalStateCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	private static string $_prefix = 'TGlobalStateCacheDependencyTest_';

	protected function setUp(): void
	{
		// Reset all state keys used by this suite.
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', null);
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key2', null);
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', null);
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key2', null);
	}

	// -------------------------------------------------------------------------
	// Construction and property access
	// -------------------------------------------------------------------------

	public function testConstructorSetsStateName(): void
	{
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');
		$this->assertSame(self::$_prefix . 'key', $dep->getStateName());
	}

	public function testConstructorCapturesCurrentValue(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'initial');
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');

		// Dependency should report unchanged immediately after construction.
		$this->assertFalse($dep->getHasChanged());
	}

	public function testConstructorCapturesNullForUnsetState(): void
	{
		// Key has not been set; state value is null.
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');
		$this->assertFalse($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// setStateName
	// -------------------------------------------------------------------------

	public function testSetStateNameUpdatesNameAndRecapturesValue(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'v1');
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key2', 'v2');

		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');
		$dep->setStateName(self::$_prefix . 'key2');

		$this->assertSame(self::$_prefix . 'key2', $dep->getStateName());
		// Value for key2 has been captured; state is unchanged.
		$this->assertFalse($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// getHasChanged
	// -------------------------------------------------------------------------

	public function testGetHasChangedFalseWhenStateUnchanged(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'same');
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');
		$this->assertFalse($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenStateValueChanges(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'before');
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');

		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'after');
		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenStateSetToNull(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'has-value');
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');

		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', null);
		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenStateSetFromNull(): void
	{
		// State starts as null (not set).
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');

		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'now-set');
		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedDetectsTypeChange(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 1);
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');

		// Strict comparison: 1 !== '1'
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', '1');
		$this->assertTrue($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// updateStateValue
	// -------------------------------------------------------------------------

	public function testUpdateStateValueRefreshesBaseline(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'original');
		$dep = new TGlobalStateCacheDependency(self::$_prefix . 'key');

		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'changed');
		$this->assertTrue($dep->getHasChanged());

		// Re-capture the new value as the baseline.
		$dep->updateStateValue();
		$this->assertFalse($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// Serialization round-trip
	// -------------------------------------------------------------------------

	public function testSerializationPreservesStateName(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'snap');
		$dep      = new TGlobalStateCacheDependency(self::$_prefix . 'key');
		$restored = unserialize(serialize($dep));

		$this->assertSame($dep->getStateName(), $restored->getStateName());
	}

	public function testGetHasChangedAfterSerializationWhenUnchanged(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'stable');
		$dep      = new TGlobalStateCacheDependency(self::$_prefix . 'key');
		$restored = unserialize(serialize($dep));

		$this->assertFalse($restored->getHasChanged());
	}

	public function testGetHasChangedAfterSerializationWhenChanged(): void
	{
		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'before');
		$dep        = new TGlobalStateCacheDependency(self::$_prefix . 'key');
		$serialized = serialize($dep);

		Prado::getApplication()->setGlobalState(self::$_prefix . 'key', 'after');
		$restored = unserialize($serialized);

		$this->assertTrue($restored->getHasChanged());
	}
}
