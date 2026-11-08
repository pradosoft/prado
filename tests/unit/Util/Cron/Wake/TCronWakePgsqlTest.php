<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;
use Prado\Util\Cron\Wake\TCronWakePgsql;

/**
 * Tests for {@see TCronWakePgsql}: the properties and driver check over SQLite, and the
 * LISTEN/NOTIFY round trip on a live PostgreSQL server (skipped without one).
 */
class TCronWakePgsqlTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function setUp(): void
	{
	}

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_PGSQL;
	}

	protected function getTestTables(): array
	{
		return [];
	}

	private function pgsqlConnection(): TDbConnection
	{
		$connection = $this->setUpConnection();
		if ($connection === null) {
			self::markTestSkipped('No PostgreSQL connection.');
		}
		return $connection;
	}

	public function testProperties()
	{
		$t = new TCronWakePgsql();
		self::assertSame(TCronWakePgsql::DEFAULT_CHANNEL, $t->getChannel());
		self::assertSame(TCronWakePgsql::DEFAULT_POLL_INTERVAL, $t->getPollInterval());
		self::assertSame($t, $t->setChannel('my_channel'));
		self::assertSame('my_channel', $t->getChannel());
		self::assertSame($t, $t->setPollInterval('0.5'));
		self::assertSame(0.5, $t->getPollInterval());
		try {
			$t->setChannel('bad channel; DROP');
			self::fail('the channel is an SQL identifier');
		} catch (TInvalidDataValueException $e) {
		}
		self::expectException(TInvalidDataValueException::class);
		$t->setPollInterval(0);
	}

	public function testRejectsANonPostgreSqlConnection()
	{
		$sqlite = new TDbConnection('sqlite::memory:');
		$t = new TCronWakePgsql($sqlite);
		self::expectException(TConfigurationException::class);
		$t->getDbConnection();
	}

	public function testNotifyWakesTheListener()
	{
		$connection = $this->pgsqlConnection();
		$t = (new TCronWakePgsql($connection))->setChannel('prado_unit_cronwake_' . bin2hex(random_bytes(3)));
		$now = microtime(true);
		self::assertFalse($t->wait($now + 0.05, $now), 'no notification: the wait times out');

		self::assertTrue($t->signal($now));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start), 'a notification sent while busy is received by the next wait');
		self::assertLessThan(2.0, microtime(true) - $start);
		$t->close();
	}

	public function testWatchPollsTheNotifications()
	{
		$connection = $this->pgsqlConnection();
		$t = (new TCronWakePgsql($connection))->setChannel('prado_unit_cronwake_' . bin2hex(random_bytes(3)))->setPollInterval(0.02);
		$loop = TAsync::getLoop();
		$wakes = 0;
		$suspension = $loop->suspension();
		$resumed = false;
		$resume = static function () use ($suspension, &$resumed) {
			if (!$resumed) {
				$resumed = true;
				$suspension->resume();
			}
		};
		self::assertTrue($t->watch($loop, static function () use (&$wakes, $resume) {
			$wakes++;
			$resume();
		}));
		$loop->delay(0.03, static fn () => $t->signal(microtime(true)));
		$timeout = $loop->delay(3.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$t->close();
		self::assertSame(1, $wakes);
	}
}
