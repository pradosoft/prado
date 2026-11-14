<?php

namespace Prado\Test\Unit\Data\DbSpecific\Oracle;

use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;
use Prado\Util\Cron\Wake\TCronWakeOracle;

/**
 * The {@see TCronWakeOracle} round trip on a live Oracle server: `DBMS_ALERT.SIGNAL` on one
 * connection wakes `WAITONE` on the listening connection.  Skipped when the user lacks `EXECUTE`
 * on `DBMS_ALERT`.
 */
class TCronWakeOracleIntegrationTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function setUp(): void
	{
	}

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_OCI;
	}

	protected function getTestTables(): array
	{
		return [];
	}

	private function oracleConnection(): TDbConnection
	{
		$connection = $this->setUpConnection();
		if ($connection === null) {
			self::markTestSkipped('No Oracle connection.');
		}
		return $connection;
	}

	public function testSignalWakesTheListener()
	{
		$connection = $this->oracleConnection();
		$t = (new TCronWakeOracle($connection))->setAlert('PRADO_UNIT_WAKE_' . strtoupper(bin2hex(random_bytes(3))));
		$now = microtime(true);
		try {
			self::assertFalse($t->wait($now + 0.5, $now), 'no signal: the wait times out');
		} catch (TDbException $e) {
			if (str_contains($e->getMessage(), 'PLS-00201') || str_contains($e->getMessage(), 'ORA-06550')) {
				self::markTestSkipped('The user lacks EXECUTE on DBMS_ALERT.');
			}
			throw $e;
		}
		self::assertTrue($t->signal($now));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start), 'a signal sent while busy is received by the next wait');
		self::assertLessThan(3.0, microtime(true) - $start);
		$t->signal($start);
		$t->signal($start);
		usleep(100000);
		self::assertTrue($t->wait($start + 2.0, microtime(true)), 'signals coalesce into one wake');
		$t->close();
	}
}
