<?php

namespace Prado\Test\Unit\Data\DbCommon;

use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * SQLite connection that records the SQL of each command and fails it.
 */
class TMysqlMetaDataTestConnection extends TDbConnection
{
	public array $sql = [];

	public function createCommand($sql)
	{
		$this->sql[] = $sql;
		throw new TDbException('dbconnection_connection_inactive');
	}
}

/**
 * TMysqlMetaData with a fixed server version, so no MySQL server is needed.
 */
class TMysqlMetaDataTestMetaData extends TMysqlMetaData
{
	protected function getServerVersion()
	{
		return 8.0;
	}
}

class TMysqlMetaDataTest extends \PHPUnit\Framework\TestCase
{
	public function testGetIsViewQuotesTheTableName(): void
	{
		$conn = new TMysqlMetaDataTestConnection('sqlite::memory:');
		$conn->setActive(true);
		$meta = new TMysqlMetaDataTestMetaData($conn);

		foreach ([[null, "x' OR '1'='1"], ['db', 'my_table%']] as [$schema, $table]) {
			try {
				PradoUnit::invoke($meta, 'getIsView', $schema, $table);
				$this->fail('getIsView() did not run the recording command.');
			} catch (TDbException $e) {
			}
		}

		$this->assertSame("SHOW FULL TABLES LIKE " . $conn->quoteString("x' OR '1'='1"), $conn->sql[0]);
		$this->assertSame("SHOW FULL TABLES FROM `db` LIKE " . $conn->quoteString('my\_table\%'), $conn->sql[1]);
	}
}
