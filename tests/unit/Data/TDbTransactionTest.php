<?php

use Prado\Data\TDbConnection;
use Prado\Data\TDbTransaction;
use Prado\TApplication;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
}

/**
 * Hand-rolled PDO stub that pretends to be a Firebird connection.
 *
 * PHPUnit mocks of native C-extension classes (like PDO) are unreliable because
 * PHP requires the native constructor to have run before any C-level method can
 * be invoked — even on a stub object.  Overriding __construct() in a subclass
 * prevents the C-level initialisation and keeps all method calls at the PHP level.
 */
class FirebirdPdoStub extends PDO
{
	/** @var string[] SQL statements passed to exec() */
	public array $execCalls = [];
	public bool $rollBackCalled = false;

	/** Suppress native PDO constructor so no C-level state is required. */
	public function __construct() {}

	public function getAttribute(int $attribute): mixed
	{
		return $attribute === PDO::ATTR_DRIVER_NAME ? 'firebird' : null;
	}

	public function exec(string $statement): int|false
	{
		$this->execCalls[] = $statement;
		return 0;
	}

	public function rollBack(): bool
	{
		$this->rollBackCalled = true;
		return true;
	}

	public bool $commitCalled = false;

	public function commit(): bool
	{
		$this->commitCalled = true;
		return true;
	}
}

class TDbTransactionTest extends PHPUnit\Framework\TestCase
{
	private $_connection;

	protected function setUp(): void
	{
		// Remove any stale DB file from a previous test run (guards against Windows
		// file-lock failures leaving the file behind after tearDown).
		@unlink(TEST_DB_FILE);

		// create application just to provide application mode
		new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP);

		$this->_connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->_connection->Active = true;
		$this->_connection->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
	}

	protected function tearDown(): void
	{
		// Explicitly close the PDO connection before unlinking to release the file
		// lock on Windows (where an open handle prevents unlink from succeeding).
		if ($this->_connection !== null) {
			$this->_connection->Active = false;
			$this->_connection = null;
		}
		@unlink(TEST_DB_FILE);
	}

	public function testRollBack()
	{
		$sql = 'INSERT INTO foo(id,name) VALUES (1,\'my name\')';
		$transaction = $this->_connection->beginTransaction();
		try {
			$this->_connection->createCommand($sql)->execute();
			$this->_connection->createCommand($sql)->execute();
			$this->fail('Expected exception not raised');
			$transaction->commit();
		} catch (Exception $e) {
			$this->assertTrue($transaction->Active);
			$transaction->rollBack();
			$this->assertFalse($transaction->Active);
			$reader = $this->_connection->createCommand('SELECT * FROM foo')->query();
			$this->assertFalse($reader->read());
		}
	}

	public function testCommit()
	{
		$sql1 = 'INSERT INTO foo(id,name) VALUES (1,\'my name\')';
		$sql2 = 'INSERT INTO foo(id,name) VALUES (2,\'my name\')';
		$transaction = $this->_connection->beginTransaction();
		try {
			$this->_connection->createCommand($sql1)->execute();
			$this->_connection->createCommand($sql2)->execute();
			$this->assertTrue($transaction->Active);
			$transaction->commit();
			$this->assertFalse($transaction->Active);
		} catch (Exception $e) {
			$transaction->rollBack();
			$this->fail('Unexpected exception');
		}
		$result = $this->_connection->createCommand('SELECT * FROM foo')->query()->readAll();
		$this->assertEquals(count($result), 2);
	}

	public function testRollBackFlushesImplicitTransactionForFirebird()
	{
		// Use FirebirdPdoStub (defined above) rather than a PHPUnit mock of PDO.
		// PHPUnit mocks of native extension classes fail with "PDO object is not
		// initialized, constructor was not called" when C-level methods are invoked,
		// because the C-level constructor was suppressed.  The stub subclass
		// overrides __construct() and the relevant methods at the PHP level,
		// so no C-level state is needed.
		$pdo = new FirebirdPdoStub();

		$conn = $this->createMock(TDbConnection::class);
		$conn->method('getActive')->willReturn(true);
		$conn->method('getPdoInstance')->willReturn($pdo);

		$transaction = new TDbTransaction($conn);
		$transaction->rollBack();

		self::assertTrue(
			$pdo->rollBackCalled,
			'PDO::rollBack() must be called to roll back the Firebird transaction.'
		);
		self::assertTrue(
			$pdo->commitCalled,
			'PDO::commit() must be called after rollback to flush the implicit transaction ' .
			'that pdo_firebird opens automatically (requiresPostTransactionFlush).'
		);
		self::assertFalse($transaction->Active, 'Transaction must be inactive after rollback.');
	}
}
