<?php

/**
 * PradoUnitSuiteFailTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Stub that mimics a TException subclass by exposing getErrorCode().
 * Used to exercise the TException branch in normalizeErrorKey() without
 * requiring a real messages.txt entry.
 */
class PradoUnitStubTException extends \RuntimeException
{
	private string $_errorCode;

	public function __construct(string $message, string $errorCode)
	{
		parent::__construct($message);
		$this->_errorCode = $errorCode;
	}

	public function getErrorCode(): string
	{
		return $this->_errorCode;
	}
}

/**
 * Tests for the suite-failure deduplication API on {@see PradoUnit}:
 * {@see PradoUnit::normalizeErrorKey()}, {@see PradoUnit::failFirstThenSkip()},
 * {@see PradoUnit::getSuiteFailEmittedCount()}, and
 * {@see PradoUnit::$suiteFailEmitted}.
 *
 * Each test saves and restores `$suiteFailEmitted` so the dedup state does not
 * bleed across tests within this class or into the broader suite.
 *
 */
class PradoUnitSuiteFailTest extends PHPUnit\Framework\TestCase
{
	/** @var array<string, int> */
	private array $_suiteFailEmittedSnap = [];

	protected function setUp(): void
	{
		$this->_suiteFailEmittedSnap = PradoUnit::$suiteFailEmitted;
		PradoUnit::$suiteFailEmitted = [];
	}

	protected function tearDown(): void
	{
		PradoUnit::$suiteFailEmitted = $this->_suiteFailEmittedSnap;
	}

	// -----------------------------------------------------------------------
	// normalizeErrorKey
	// -----------------------------------------------------------------------

	public function testNormalizeErrorKey_returnsStringAsIs(): void
	{
		$this->assertSame('my.error.key', PradoUnit::normalizeErrorKey('my.error.key'));
	}

	public function testNormalizeErrorKey_returnsEmptyString_forEmptyString(): void
	{
		$this->assertSame('', PradoUnit::normalizeErrorKey(''));
	}

	public function testNormalizeErrorKey_returnsTExceptionErrorCode(): void
	{
		$e = new PradoUnitStubTException('human message', 'stub.error.code');

		$this->assertSame('stub.error.code', PradoUnit::normalizeErrorKey($e));
	}

	public function testNormalizeErrorKey_ignoresMessageForTExceptionAlike(): void
	{
		// The message must not appear in the key when getErrorCode() is present.
		$e = new PradoUnitStubTException('human message', 'stub.error.code');

		$this->assertStringNotContainsString('human message', PradoUnit::normalizeErrorKey($e));
	}

	public function testNormalizeErrorKey_returnsClassColonMessage_forPlainException(): void
	{
		$e = new \RuntimeException('something went wrong');

		$this->assertSame(\RuntimeException::class . ':something went wrong', PradoUnit::normalizeErrorKey($e));
	}

	public function testNormalizeErrorKey_returnsClassColonMessage_forPDOException(): void
	{
		// PDOException is a common case — two distinct PDOExceptions share the same
		// class, so only the class+message combination is stable enough to deduplicate.
		$e = new \PDOException('SQLSTATE[42000]: syntax error');

		$this->assertSame(\PDOException::class . ':SQLSTATE[42000]: syntax error', PradoUnit::normalizeErrorKey($e));
	}

	// -----------------------------------------------------------------------
	// getSuiteFailEmittedCount
	// -----------------------------------------------------------------------

	public function testGetSuiteFailEmittedCount_returnsEmptyArray_whenMapEmpty(): void
	{
		$this->assertSame([], PradoUnit::getSuiteFailEmittedCount());
	}

	public function testGetSuiteFailEmittedCount_returnsFullMap_whenNull(): void
	{
		PradoUnit::$suiteFailEmitted = ['sqlite' => 3, 'mysql' => 1];

		$this->assertSame(['sqlite' => 3, 'mysql' => 1], PradoUnit::getSuiteFailEmittedCount(null));
	}

	public function testGetSuiteFailEmittedCount_returnsZero_forUnknownStringKey(): void
	{
		$this->assertSame(0, PradoUnit::getSuiteFailEmittedCount('no.such.key'));
	}

	public function testGetSuiteFailEmittedCount_returnsCount_forKnownStringKey(): void
	{
		PradoUnit::$suiteFailEmitted['sqlite'] = 5;

		$this->assertSame(5, PradoUnit::getSuiteFailEmittedCount('sqlite'));
	}

	public function testGetSuiteFailEmittedCount_normalizesThrowable_forTExceptionAlike(): void
	{
		$e = new PradoUnitStubTException('human message', 'stub.error.code');
		PradoUnit::$suiteFailEmitted['stub.error.code'] = 2;

		$this->assertSame(2, PradoUnit::getSuiteFailEmittedCount($e));
	}

	public function testGetSuiteFailEmittedCount_normalizesThrowable_forPlainException(): void
	{
		$e = new \RuntimeException('something went wrong');
		$key = \RuntimeException::class . ':something went wrong';
		PradoUnit::$suiteFailEmitted[$key] = 4;

		$this->assertSame(4, PradoUnit::getSuiteFailEmittedCount($e));
	}

	public function testGetSuiteFailEmittedCount_returnsZero_forUnknownThrowable(): void
	{
		$e = new \RuntimeException('not in the map');

		$this->assertSame(0, PradoUnit::getSuiteFailEmittedCount($e));
	}

	// -----------------------------------------------------------------------
	// failFirstThenSkip
	// -----------------------------------------------------------------------

	public function testFailFirstThenSkip_doesNothing_whenBothNull(): void
	{
		// No key and no exception — condition satisfied, nothing to report.
		PradoUnit::failFirstThenSkip($this, null);

		$this->assertSame([], PradoUnit::$suiteFailEmitted);
	}

	public function testFailFirstThenSkip_doesNothing_whenKeyNullExceptionNull(): void
	{
		PradoUnit::failFirstThenSkip($this, null, null, 'explicit reason');

		$this->assertSame([], PradoUnit::$suiteFailEmitted);
	}

	public function testFailFirstThenSkip_throwsAssertionFailedError_onFirstCall(): void
	{
		$this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

		PradoUnit::failFirstThenSkip($this, 'sqlite');
	}

	public function testFailFirstThenSkip_recordsCountOne_afterFirstCall(): void
	{
		try {
			PradoUnit::failFirstThenSkip($this, 'sqlite');
		} catch (\PHPUnit\Framework\AssertionFailedError $ignored) {
		}

		$this->assertSame(1, PradoUnit::$suiteFailEmitted['sqlite']);
	}

	public function testFailFirstThenSkip_throwsSkipped_onSecondCall(): void
	{
		// Seed the first call.
		try {
			PradoUnit::failFirstThenSkip($this, 'sqlite');
		} catch (\PHPUnit\Framework\AssertionFailedError $ignored) {
		}

		$this->expectException(\PHPUnit\Framework\SkippedWithMessageException::class);
		PradoUnit::failFirstThenSkip($this, 'sqlite');
	}

	public function testFailFirstThenSkip_incrementsCount_onEachCall(): void
	{
		for ($i = 1; $i <= 4; $i++) {
			try {
				PradoUnit::failFirstThenSkip($this, 'mysql');
			} catch (\Throwable $ignored) {
			}
			$this->assertSame($i, PradoUnit::$suiteFailEmitted['mysql']);
		}
	}

	public function testFailFirstThenSkip_usesErrorKeyAsMessage_whenNoReasonNoException(): void
	{
		$caught = null;
		try {
			PradoUnit::failFirstThenSkip($this, 'my.key');
		} catch (\PHPUnit\Framework\AssertionFailedError $e) {
			$caught = $e;
		}

		$this->assertNotNull($caught);
		$this->assertSame('my.key', $caught->getMessage());
	}

	public function testFailFirstThenSkip_usesExceptionMessage_whenNoReason(): void
	{
		$cause = new \RuntimeException('connection refused');
		$caught = null;
		try {
			PradoUnit::failFirstThenSkip($this, 'pgsql', $cause);
		} catch (\PHPUnit\Framework\AssertionFailedError $e) {
			$caught = $e;
		}

		$this->assertNotNull($caught);
		$this->assertSame('connection refused', $caught->getMessage());
	}

	public function testFailFirstThenSkip_usesReason_overExceptionMessage(): void
	{
		$cause = new \RuntimeException('raw exception message');
		$caught = null;
		try {
			PradoUnit::failFirstThenSkip($this, 'pgsql', $cause, 'human-friendly reason');
		} catch (\PHPUnit\Framework\AssertionFailedError $e) {
			$caught = $e;
		}

		$this->assertNotNull($caught);
		$this->assertSame('human-friendly reason', $caught->getMessage());
	}

	public function testFailFirstThenSkip_derivesKeyFromException_whenErrorKeyNull(): void
	{
		$e = new \RuntimeException('db unavailable');
		$expectedKey = \RuntimeException::class . ':db unavailable';

		try {
			PradoUnit::failFirstThenSkip($this, null, $e);
		} catch (\PHPUnit\Framework\AssertionFailedError $ignored) {
		}

		$this->assertSame(1, PradoUnit::$suiteFailEmitted[$expectedKey] ?? 0);
	}

	public function testFailFirstThenSkip_derivesKeyFromTExceptionAlike_whenErrorKeyNull(): void
	{
		$e = new PradoUnitStubTException('human message', 'stub.error.code');

		try {
			PradoUnit::failFirstThenSkip($this, null, $e);
		} catch (\PHPUnit\Framework\AssertionFailedError $ignored) {
		}

		$this->assertSame(1, PradoUnit::$suiteFailEmitted['stub.error.code'] ?? 0);
	}

	public function testFailFirstThenSkip_skipMessageMatchesFailMessage(): void
	{
		// The human message on skip must be the same as on the original fail.
		$failMsg = null;
		$skipMsg = null;

		try {
			PradoUnit::failFirstThenSkip($this, 'firebird', null, 'Firebird unavailable');
		} catch (\PHPUnit\Framework\AssertionFailedError $e) {
			$failMsg = $e->getMessage();
		}

		try {
			PradoUnit::failFirstThenSkip($this, 'firebird', null, 'Firebird unavailable');
		} catch (\PHPUnit\Framework\SkippedWithMessageException $e) {
			$skipMsg = $e->getMessage();
		}

		$this->assertSame($failMsg, $skipMsg);
	}

	public function testFailFirstThenSkip_keysAreIndependent(): void
	{
		// Failing on one key must not affect the count of a different key.
		try {
			PradoUnit::failFirstThenSkip($this, 'sqlite');
		} catch (\Throwable $ignored) {
		}

		$this->assertSame(0, PradoUnit::getSuiteFailEmittedCount('mysql'));
	}
}
