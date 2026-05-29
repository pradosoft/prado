<?php

/**
 * PradoUnitInitialStateTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/PradoUnitRequires.php';

/**
 * Unit tests for the session-baseline capture surface on {@see PradoUnit}.
 *
 * Covers: {@see PradoUnit::captureInitialState()} populating every documented
 * slice; {@see PradoUnit::getInitialState()} key-or-whole-map access;
 * {@see PradoUnit::restoreInitialState()} round-trip for each kind of mutated
 * state plus its safe-no-op contract when no snapshot has been taken.
 *
 * The bootstrap already runs {@see PradoUnit::captureInitialState()} before
 * any test, so every test in this file inherits a populated snapshot.
 *
 */
class PradoUnitInitialStateTest extends PHPUnit\Framework\TestCase
{
	/** @var array<string, mixed> Bootstrap-captured baseline, stashed across each test. */
	private array $_bootstrapSnapshot;

	protected function setUp(): void
	{
		// Stash the bootstrap-captured snapshot so tests that re-capture (and
		// therefore overwrite the singleton) can put it back in tearDown.
		// Without this, later unit tests in the full suite see a polluted
		// $_GET / $_SERVER if a re-capture happened mid-test.
		$this->_bootstrapSnapshot = PradoUnit::getInitialState();
	}

	protected function tearDown(): void
	{
		// Any test that mutated a captured slice must leave the process clean.
		PradoUnit::restoreInitialState();
		// Put the bootstrap baseline back in case a test re-captured.
		PradoUnit::setStaticProp(PradoUnit::class, '_initialState', $this->_bootstrapSnapshot);
		PradoUnit::restoreInitialState();
	}

	// =========================================================================
	// captureInitialState — coverage of every documented slice
	// =========================================================================

	public function testCaptureInitialState_populatesEveryDocumentedSlice(): void
	{
		$state = PradoUnit::getInitialState();

		foreach (
			['_SERVER', '_GET', '_POST', '_REQUEST', '_COOKIE', '_FILES', '_ENV',
				'cwd', 'timezone', 'error_reporting', 'include_path'] as $key
		) {
			$this->assertArrayHasKey($key, $state, "missing slice '{$key}'");
		}
	}

	public function testCaptureInitialState_serverSliceMatchesAtCaptureTime(): void
	{
		// Take a fresh snapshot and verify it equals the live $_SERVER right
		// after the capture call.
		PradoUnit::captureInitialState();
		$this->assertSame($_SERVER, PradoUnit::getInitialState('_SERVER'));
	}

	public function testCaptureInitialState_overwritesPreviousSnapshot(): void
	{
		// Snapshots are not stacked — the most recent capture wins.
		$_GET['__pradounit_marker__'] = 'first';
		PradoUnit::captureInitialState();
		$this->assertArrayHasKey('__pradounit_marker__', PradoUnit::getInitialState('_GET'));

		$_GET['__pradounit_marker__'] = 'second';
		PradoUnit::captureInitialState();
		$this->assertSame('second', PradoUnit::getInitialState('_GET')['__pradounit_marker__']);

		unset($_GET['__pradounit_marker__']);
	}

	// =========================================================================
	// getInitialState — key access
	// =========================================================================

	public function testGetInitialState_noKey_returnsWholeMap(): void
	{
		$state = PradoUnit::getInitialState();
		$this->assertIsArray($state);
		$this->assertNotEmpty($state);
	}

	public function testGetInitialState_keyMiss_returnsNull(): void
	{
		$this->assertNull(PradoUnit::getInitialState('not-a-real-slice'));
	}

	// =========================================================================
	// restoreInitialState — per-slice round-trip
	// =========================================================================

	public function testRestoreInitialState_restoresServer(): void
	{
		$baseline = PradoUnit::getInitialState('_SERVER');
		$_SERVER['__pradounit_mut__'] = 'x';
		PradoUnit::restoreInitialState();
		$this->assertSame($baseline, $_SERVER);
	}

	public function testRestoreInitialState_restoresGetPostRequestCookie(): void
	{
		$_GET['m'] = $_POST['m'] = $_REQUEST['m'] = $_COOKIE['m'] = 'x';
		PradoUnit::restoreInitialState();

		$this->assertArrayNotHasKey('m', $_GET);
		$this->assertArrayNotHasKey('m', $_POST);
		$this->assertArrayNotHasKey('m', $_REQUEST);
		$this->assertArrayNotHasKey('m', $_COOKIE);
	}

	public function testRestoreInitialState_restoresErrorReporting(): void
	{
		$baseline = PradoUnit::getInitialState('error_reporting');
		error_reporting(0);
		PradoUnit::restoreInitialState();
		$this->assertSame($baseline, error_reporting());
	}

	public function testRestoreInitialState_restoresTimezone(): void
	{
		$baseline = PradoUnit::getInitialState('timezone');
		date_default_timezone_set('Asia/Tokyo');
		PradoUnit::restoreInitialState();
		$this->assertSame($baseline, date_default_timezone_get());
	}

	public function testRestoreInitialState_restoresCwd(): void
	{
		$baseline = PradoUnit::getInitialState('cwd');
		chdir(sys_get_temp_dir());
		PradoUnit::restoreInitialState();
		$this->assertSame($baseline, getcwd());
	}

	public function testRestoreInitialState_restoresIncludePath(): void
	{
		$baseline = PradoUnit::getInitialState('include_path');
		set_include_path(get_include_path() . PATH_SEPARATOR . '/tmp/pradounit-fake');
		PradoUnit::restoreInitialState();
		$this->assertSame($baseline, get_include_path());
	}

	// =========================================================================
	// restoreInitialState — safe no-op when uncaptured
	// =========================================================================

	public function testRestoreInitialState_isNoOp_whenNoSnapshotPresent(): void
	{
		// Stash & clear the singleton baseline, then verify restore() does
		// not crash and does not mutate the live process.
		$snapshot = PradoUnit::getInitialState();
		PradoUnit::setStaticProp(PradoUnit::class, '_initialState', []);
		try {
			$_GET['m'] = 'persists';
			PradoUnit::restoreInitialState();
			$this->assertSame('persists', $_GET['m']);
		} finally {
			PradoUnit::setStaticProp(PradoUnit::class, '_initialState', $snapshot);
			unset($_GET['m']);
		}
	}
}
