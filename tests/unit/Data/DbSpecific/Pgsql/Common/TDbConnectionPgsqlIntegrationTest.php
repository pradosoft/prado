<?php

namespace Prado\Test\Unit\Data\DbSpecific\Pgsql\Common;

use Prado\Data\TDbConnection;
use Prado\Test\Unit\PradoUnit;

/**
 * Live integration tests for TDbConnection on PostgreSQL.
 */
class TDbConnectionPgsqlIntegrationTest extends \PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	protected function setUp(): void
	{
		$conn = PradoUnit::setupPgsqlConnection('prado_unitest');
		if (is_array($conn)) {
			PradoUnit::failFirstThenSkip($this, $conn[0], null, $conn[1]);
		}
		$this->_conn = $conn;
		$this->_conn->createCommand('DROP TABLE IF EXISTS lastid_test')->execute();
		$this->_conn->createCommand('CREATE TABLE lastid_test (id SERIAL PRIMARY KEY, name VARCHAR(20))')->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			$this->_conn->createCommand('DROP TABLE IF EXISTS lastid_test')->execute();
			$this->_conn->setActive(false);
		}
		$this->_conn = null;
	}

	public function testGetLastInsertIdWithoutASequenceName(): void
	{
		$this->_conn->createCommand("INSERT INTO lastid_test (name) VALUES ('a')")->execute();
		$this->_conn->createCommand("INSERT INTO lastid_test (name) VALUES ('b')")->execute();
		$this->assertSame('2', $this->_conn->getLastInsertID());
		$this->assertSame('2', $this->_conn->getLastInsertID(null));
		$this->assertSame('2', $this->_conn->getLastInsertID('lastid_test_id_seq'));
	}
}
