<?php

/**
 * TApplicationStateCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/../PradoUnitRequires.php';

use Prado\Caching\TApplicationStateCacheDependency;
use Prado\Prado;
use Prado\TApplicationMode;

/**
 * TApplicationStateCacheDependencyTest class
 *
 * Unit tests for {@see \Prado\Caching\TApplicationStateCacheDependency}.
 *
 * The dependency reports `false` (unchanged) only in Performance mode; all
 * other modes cause it to report `true` (changed).
 *
 * Each test saves the current application mode in setUp() and restores it in
 * tearDown() so this suite does not affect other tests.
 */
class TApplicationStateCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	private string $_originalMode;

	protected function setUp(): void
	{
		$this->_originalMode = Prado::getApplication()->getMode();
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setMode($this->_originalMode);
	}

	// -------------------------------------------------------------------------
	// getHasChanged by mode
	// -------------------------------------------------------------------------

	public function testGetHasChangedFalseInPerformanceMode(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Performance);
		$dep = new TApplicationStateCacheDependency();
		$this->assertFalse($dep->getHasChanged());
	}

	public function testGetHasChangedTrueInNormalMode(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Normal);
		$dep = new TApplicationStateCacheDependency();
		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueInDebugMode(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Debug);
		$dep = new TApplicationStateCacheDependency();
		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueInOffMode(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Off);
		$dep = new TApplicationStateCacheDependency();
		$this->assertTrue($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// Mode changes after construction
	// -------------------------------------------------------------------------

	public function testGetHasChangedReflectsCurrentModeNotConstructionMode(): void
	{
		// Dependency is stateless — it always reads the live application mode.
		Prado::getApplication()->setMode(TApplicationMode::Normal);
		$dep = new TApplicationStateCacheDependency();
		$this->assertTrue($dep->getHasChanged());

		Prado::getApplication()->setMode(TApplicationMode::Performance);
		$this->assertFalse($dep->getHasChanged());

		Prado::getApplication()->setMode(TApplicationMode::Debug);
		$this->assertTrue($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// Serialization round-trip
	// -------------------------------------------------------------------------

	public function testSerializationAndDeserializationWork(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Performance);
		$dep      = new TApplicationStateCacheDependency();
		$restored = unserialize(serialize($dep));

		$this->assertInstanceOf(TApplicationStateCacheDependency::class, $restored);
		$this->assertFalse($restored->getHasChanged());
	}

	public function testGetHasChangedAfterSerializationReflectsCurrentMode(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Performance);
		$dep        = new TApplicationStateCacheDependency();
		$serialized = serialize($dep);

		Prado::getApplication()->setMode(TApplicationMode::Normal);
		$restored = unserialize($serialized);

		$this->assertTrue($restored->getHasChanged());
	}
}
