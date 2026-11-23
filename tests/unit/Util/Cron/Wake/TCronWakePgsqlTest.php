<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\Data\IDataConnection;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;
use Prado\Test\Unit\Harness\Util\Mutex\TTestLockConnection;
use Prado\Test\Unit\PradoUnit;
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

		// Two notifications from two transactions queue two; one wait takes both.
		self::assertTrue($t->signal($now));
		self::assertTrue($t->signal($now));
		usleep(20000);
		$start = microtime(true);
		self::assertTrue($t->wait($start + 1.0, $start));
		$start = microtime(true);
		self::assertFalse($t->wait($start + 0.05, $start), 'the second notification was taken with the first');
		$t->close();
	}

	public function testAClosedListenerIsReopened()
	{
		$connection = $this->pgsqlConnection();
		$t = (new TCronWakePgsql($connection))->setChannel('prado_unit_cronwake_' . bin2hex(random_bytes(3)));
		$now = microtime(true);
		self::assertFalse($t->wait($now, $now));
		$listener = PradoUnit::getProp($t, '_listener');
		$listener->setActive(false);

		self::assertFalse($t->wait($now, $now), 'the wait opens a new listener');
		self::assertNotSame($listener, PradoUnit::getProp($t, '_listener'));
		self::assertTrue($t->signal($now));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 5.0, $start), 'the new listener listens on the channel');
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

	public function testInitConnectionIDAndUnavailable()
	{
		$t = new TCronWakePgsql();
		$t->init(null);
		self::assertSame(TCronWakePgsql::DEFAULT_CHANNEL, $t->getChannel());
		$t->setConnectionID('noSuchPgsqlModule');
		try {
			$t->getDbConnection();
			self::fail('an unknown connection module was accepted');
		} catch (TConfigurationException $e) {
			self::assertStringContainsString('noSuchPgsqlModule', $e->getMessage());
		}

		$connection = $this->createMock(\Prado\Data\IDataConnection::class);
		$connection->method('getActive')->willReturn(true);
		$connection->method('getDriverName')->willReturn('pgsql');
		self::assertSame($t, $t->setDbConnection($connection));
		self::assertSame($connection, $t->getDbConnection());
		try {
			\Prado\Test\Unit\PradoUnit::invoke($t, 'getListener');
			self::fail('a connection that is no TDbConnection was listened on');
		} catch (TConfigurationException $e) {
		}

		$t = new class () extends TCronWakePgsql {
			protected function getIsAvailable(): bool
			{
				return false;
			}
		};
		$now = microtime(true);
		self::assertFalse($t->signal($now));
		self::assertNull($t->wait($now + 1, $now));
		self::assertFalse($t->watch(TAsync::getLoop(), static fn () => null));
	}

	public function testCloseWithoutAListener()
	{
		$t = new TCronWakePgsql(new TTestLockConnection('pgsql'));
		$t->close();
		self::assertNull(PradoUnit::getProp($t, '_listener'));
	}

	public function testGetDbConnectionChecksTheDriverOfEachSource()
	{
		$connection = new TTestLockConnection('pgsql');
		$connection->setActive(false);
		$t = new TCronWakePgsql($connection);
		self::assertSame($connection, $t->getDbConnection());
		self::assertTrue($connection->getActive(), 'an inactive connection is activated');

		$sqlite = new TDbConnection('sqlite::memory:');
		$sqlite->setActive(true);
		try {
			(new TCronWakePgsql($sqlite))->getDbConnection();
			self::fail('an active SQLite connection was accepted');
		} catch (TConfigurationException $e) {
		}

		$app = Prado::getApplication();
		$modules = PradoUnit::getProp($app, '_modules');
		try {
			$config = new TDataSourceConfig();
			PradoUnit::setProp($config, '_conn', $pgsql = new TTestLockConnection('pgsql'));
			$app->setModule('cronWakePgsqlSource', $config);
			$t = new TCronWakePgsql();
			$t->setConnectionID('cronWakePgsqlSource');
			self::assertSame($pgsql, $t->getDbConnection(), 'the ConnectionID connection');

			$config = new TDataSourceConfig();
			PradoUnit::setProp($config, '_conn', new TDbConnection('sqlite::memory:'));
			$app->setModule('cronWakeSqliteSource', $config);
			$t = new TCronWakePgsql();
			$t->setConnectionID('cronWakeSqliteSource');
			self::expectException(TConfigurationException::class);
			$t->getDbConnection();
		} finally {
			PradoUnit::setProp($app, '_modules', $modules);
		}
	}

	public function testAClosedListenerOverASourceThatIsNoTDbConnection()
	{
		$source = $this->createMock(IDataConnection::class);
		$source->method('getActive')->willReturn(true);
		$source->method('getDriverName')->willReturn('pgsql');
		$t = new TCronWakePgsql($source);
		PradoUnit::setProp($t, '_listener', new TDbConnection('sqlite::memory:'));
		self::expectException(TConfigurationException::class);
		PradoUnit::invoke($t, 'getListener');
	}
}
