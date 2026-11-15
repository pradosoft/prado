<?php

namespace Prado\Test\Unit\Data\DataGateway;

use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

/**
 * TTableGateway over an in-memory SQLite database.
 */
class TTableGatewayTest extends \PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$this->_conn = new TDbConnection('sqlite::memory:');
		$this->_conn->setActive(true);
		$this->_conn->createCommand('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT NOT NULL, kind TEXT)')->execute();
		$this->_conn->createCommand("INSERT INTO items VALUES (1, 'a', 'x'), (2, 'b', 'x'), (3, 'c', 'y')")->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn !== null) {
			$this->_conn->setActive(false);
			$this->_conn = null;
		}
	}

	private function gateway(): TTableGateway
	{
		return new TTableGateway('items', $this->_conn);
	}

	public function testDynamicFinderWithoutAColumnThrows()
	{
		foreach (['findBy', 'findAllBy', 'deleteBy', 'deleteAllBy', 'findBy_'] as $method) {
			try {
				$this->gateway()->$method('a');
				$this->fail("Expected TDbException for {$method}()");
			} catch (TDbException $e) {
				$this->assertSame('dbtablegateway_mismatch_column_name', $e->getErrorCode(), $method);
			}
		}
		$this->assertSame(3, $this->gateway()->count());
	}
}
