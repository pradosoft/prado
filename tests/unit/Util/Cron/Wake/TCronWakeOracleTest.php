<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\Data\IDataConnection;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Test\Unit\Harness\Util\Mutex\TTestLockConnection;
use Prado\Util\Cron\Wake\TCronWakeOracle;

/**
 * A TCronWakeOracle whose own connections are {@see TTestLockConnection}s.
 */
class TCronWakeOracleTestTransport extends TCronWakeOracle
{
	public array $connections = [];

	protected function getIsAvailable(): bool
	{
		return true;
	}

	protected function newConnection(): IDataConnection
	{
		return $this->connections[] = new TTestLockConnection('oci');
	}
}

/**
 * A TCronWakeOracle whose own connection is one given {@see TTestLockConnection}, for scripting
 * the listener's statuses.
 */
class TCronWakeOracleTestListening extends TCronWakeOracle
{
	public function __construct(public TTestLockConnection $listener)
	{
		parent::__construct(new TTestLockConnection('oci'));
	}

	protected function getIsAvailable(): bool
	{
		return true;
	}

	protected function newConnection(): IDataConnection
	{
		return $this->listener;
	}
}

/**
 * Tests for {@see TCronWakeOracle}: the properties and driver check, and the `DBMS_ALERT` blocks
 * over the {@see TTestLockConnection} harness.  The round trip on a live server is
 * {@see \Prado\Test\Unit\Data\DbSpecific\Oracle\TCronWakeOracleIntegrationTest} in the db-oracle suite.
 */
class TCronWakeOracleTest extends \PHPUnit\Framework\TestCase
{
	public function testProperties()
	{
		$t = new TCronWakeOracle();
		self::assertSame(TCronWakeOracle::DEFAULT_ALERT, $t->getAlert());
		self::assertSame(TCronWakeOracle::DEFAULT_POLL_INTERVAL, $t->getPollInterval());
		self::assertSame($t, $t->setAlert('my_alert'));
		self::assertSame('my_alert', $t->getAlert());
		self::assertSame($t, $t->setPollInterval('0.5'));
		self::assertSame(0.5, $t->getPollInterval());
		try {
			$t->setAlert('bad alert; DROP');
			self::fail('the alert is an SQL identifier');
		} catch (TInvalidDataValueException $e) {
		}
		try {
			$t->setAlert(str_repeat('a', TCronWakeOracle::MAX_ALERT_LENGTH + 1));
			self::fail('the alert is at most 30 characters');
		} catch (TInvalidDataValueException $e) {
		}
		self::expectException(TInvalidDataValueException::class);
		$t->setPollInterval(0);
	}

	public function testRejectsANonOracleConnection()
	{
		$sqlite = new TDbConnection('sqlite::memory:');
		$t = new TCronWakeOracle($sqlite);
		self::expectException(TConfigurationException::class);
		$t->getDbConnection();
	}

	public function testDbmsAlertBlocks()
	{
		$t = new TCronWakeOracleTestTransport(new TTestLockConnection('oci'));
		$t->setAlert('unit_wake');
		$now = 1700000000.5;
		self::assertTrue($t->signal($now));
		$signaler = $t->connections[0];
		self::assertCount(1, $signaler->commands);
		self::assertSame('BEGIN DBMS_ALERT.SIGNAL(:name, :message); COMMIT; END;', $signaler->commands[0]->sql);
		self::assertSame(['unit_wake', '1700000000.5'], [$signaler->commands[0]->bound[':name'], $signaler->commands[0]->bound[':message']]);
		self::assertTrue($t->signal($now), 'the signaling connection is reused');
		self::assertCount(1, $t->connections);

		// the listener registers once, then WAITONE with the whole seconds remaining; status 1 is a timeout, 0 a signal
		$listener = new TTestLockConnection('oci');
		$listener->results = [[null, null], [null, 1], [null, 0], [null, 0], [null, 1]];
		$t3 = new TCronWakeOracleTestListening($listener);
		$t3->setAlert('unit_wake');
		self::assertFalse($t3->wait($now + 2.4, $now), 'status 1 is a timeout');
		self::assertSame('BEGIN DBMS_ALERT.REGISTER(:name); END;', $listener->commands[0]->sql);
		self::assertStringContainsString('DBMS_ALERT.WAITONE(:name, message, status, :timeout)', $listener->commands[1]->sql);
		self::assertSame(3, $listener->commands[1]->bound[':timeout'], 'the remaining time rounds up to whole seconds');
		self::assertTrue($t3->wait($now + 1, $now), 'status 0 is a signal');
		self::assertCount(3, $listener->commands, 'the registration is not repeated');
		self::assertTrue($t3->wait($now, $now + 5), 'a passed deadline polls with a zero timeout');
		self::assertSame(0, $listener->commands[3]->bound[':timeout']);
		self::assertFalse($t3->wait($now + 0.2, $now));
		$t3->close();
		self::assertSame('BEGIN DBMS_ALERT.REMOVE(:name); END;', end($listener->commands)->sql);
		self::assertFalse($listener->getActive());
	}

	public function testInitConnectionIDAndUnavailable()
	{
		$t = new TCronWakeOracle();
		$t->init(null);
		self::assertSame(TCronWakeOracle::DEFAULT_ALERT, $t->getAlert());
		self::assertSame(TCronWakeOracle::DEFAULT_POLL_INTERVAL, $t->getPollInterval());
		self::assertSame(\extension_loaded('pdo_oci'), \Prado\Test\Unit\PradoUnit::invoke($t, 'getIsAvailable'));
		$t->setConnectionID('noSuchOracleModule');
		try {
			$t->getDbConnection();
			self::fail('an unknown connection module was accepted');
		} catch (TConfigurationException $e) {
			self::assertStringContainsString('noSuchOracleModule', $e->getMessage());
		}
		self::assertSame('cronwakeoracle_connectionid_invalid', \Prado\Test\Unit\PradoUnit::invoke($t, 'getConnectionInvalidExceptionKey'));

		$t = new class () extends TCronWakeOracle {
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

	public function testGetDbConnectionActivatesTheConnectionSetDirectly()
	{
		$connection = new TTestLockConnection('oci');
		$connection->setActive(false);
		$t = new TCronWakeOracle();
		self::assertSame($t, $t->setDbConnection($connection));
		self::assertSame($connection, $t->getDbConnection());
		self::assertTrue($connection->getActive(), 'an inactive connection is activated');
		self::assertSame($connection, $t->getDbConnection(), 'an active connection is used as it is');
	}

	public function testNewConnectionTakesTheCredentialsOfTheSource()
	{
		$source = new class ('sqlite::memory:', 'user', 'secret') extends TDbConnection {
			public function getDriverName()
			{
				return 'oci';
			}
		};
		$t = new TCronWakeOracle($source);
		$connection = \Prado\Test\Unit\PradoUnit::invoke($t, 'newConnection');
		self::assertInstanceOf(TDbConnection::class, $connection);
		self::assertNotSame($source, $connection, 'the transport opens a connection of its own');
		self::assertTrue($connection->getActive());
		self::assertSame(['sqlite::memory:', 'user', 'secret', $source->getCharset()], [$connection->getConnectionString(), $connection->getUsername(), $connection->getPassword(), $connection->getCharset()]);

		$t = new TCronWakeOracle(new TTestLockConnection('oci'));
		self::expectException(TConfigurationException::class);
		\Prado\Test\Unit\PradoUnit::invoke($t, 'newConnection');
	}

	public function testCloseWithASignalerOnlyAndAnInactiveListener()
	{
		$t = new TCronWakeOracleTestTransport(new TTestLockConnection('oci'));
		self::assertTrue($t->signal(1.0));
		$signaler = $t->connections[0];
		$t->close();
		self::assertFalse($signaler->getActive(), 'the signaling connection is closed');

		$listener = new TTestLockConnection('oci');
		$listener->results = [[null, null], [null, 1]];
		$t = new TCronWakeOracleTestListening($listener);
		self::assertFalse($t->wait(1.0, 1.0));
		$listener->setActive(false);
		$count = count($listener->commands);
		$t->close();
		self::assertCount($count, $listener->commands, 'an inactive listener is not unregistered');
		$t->close();
	}

	public function testWatchPollsTheAlertOnTheLoop()
	{
		$listener = new TTestLockConnection('oci');
		$listener->results = [[null, null], [null, 1], [null, 0], [null, 1], [null, 1], [null, 1], [null, 1]];
		$t = new TCronWakeOracleTestListening($listener);
		$t->setPollInterval(0.01);
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
		$timeout = $loop->delay(2.0, $resume);
		$suspension->suspend();
		$loop->cancel($timeout);
		$t->close();
		self::assertSame(1, $wakes, 'the second poll saw status 0');
		self::assertGreaterThanOrEqual(3, count($listener->commands));
		self::assertSame(0, $listener->commands[1]->bound[':timeout'], 'the loop polls with a zero timeout');
	}

	public function testClosedConnectionsAreReopened()
	{
		$t = new TCronWakeOracleTestTransport(new TTestLockConnection('oci'));
		self::assertTrue($t->signal(1.0));
		self::assertFalse($t->wait(1.0, 1.0));
		[$signaler, $listener] = $t->connections;
		$signaler->setActive(false);
		$listener->setActive(false);

		self::assertTrue($t->signal(1.0), 'a closed signaling connection is reopened');
		self::assertFalse($t->wait(1.0, 1.0), 'a closed listening connection is reopened');
		self::assertCount(4, $t->connections);
		[, , $signaler, $listener] = $t->connections;
		self::assertSame('BEGIN DBMS_ALERT.REGISTER(:name); END;', $listener->commands[0]->sql, 'the new listener registers for the alert');
		$t->close();
		self::assertSame('BEGIN DBMS_ALERT.REMOVE(:name); END;', end($listener->commands)->sql);
		self::assertFalse($listener->getActive());
		self::assertFalse($signaler->getActive());
	}

	public function testCloseWithAnInactiveListenerClosesTheSignaler()
	{
		$t = new TCronWakeOracleTestTransport(new TTestLockConnection('oci'));
		self::assertTrue($t->signal(1.0));
		self::assertFalse($t->wait(1.0, 1.0));
		[$signaler, $listener] = $t->connections;
		$listener->setActive(false);
		$count = count($listener->commands);
		$t->close();
		self::assertCount($count, $listener->commands, 'an inactive listener is not unregistered');
		self::assertFalse($signaler->getActive(), 'the signaling connection is closed');
	}

	public function testGetDbConnectionChecksTheDriverOfEachSource()
	{
		$sqlite = new TDbConnection('sqlite::memory:');
		$sqlite->setActive(true);
		try {
			(new TCronWakeOracle($sqlite))->getDbConnection();
			self::fail('an active SQLite connection was accepted');
		} catch (TConfigurationException $e) {
		}

		$app = Prado::getApplication();
		$modules = PradoUnit::getProp($app, '_modules');
		try {
			$config = new TDataSourceConfig();
			PradoUnit::setProp($config, '_conn', $oci = new TTestLockConnection('oci'));
			$app->setModule('cronWakeOracleSource', $config);
			$t = new TCronWakeOracle();
			$t->setConnectionID('cronWakeOracleSource');
			self::assertSame($oci, $t->getDbConnection(), 'the ConnectionID connection');

			$config = new TDataSourceConfig();
			PradoUnit::setProp($config, '_conn', new TDbConnection('sqlite::memory:'));
			$app->setModule('cronWakeOracleSqliteSource', $config);
			$t = new TCronWakeOracle();
			$t->setConnectionID('cronWakeOracleSqliteSource');
			self::expectException(TConfigurationException::class);
			$t->getDbConnection();
		} finally {
			PradoUnit::setProp($app, '_modules', $modules);
		}
	}
}
