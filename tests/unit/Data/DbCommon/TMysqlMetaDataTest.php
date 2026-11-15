<?php

namespace Prado\Test\Unit\Data\DbCommon;

use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\TDbCommand;
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

/**
 * Command that answers its query from a fixed result instead of preparing its SQL.
 */
class TMysqlMetaDataTestScriptedCommand extends TDbCommand
{
	public function __construct($connection, $text, private mixed $result)
	{
		parent::__construct($connection, $text);
	}

	public function bindValue($name, $value, $dataType = null)
	{
	}

	private function answer(): mixed
	{
		if ($this->result instanceof \Throwable) {
			throw $this->result;
		}
		return $this->result;
	}

	public function query()
	{
		return new \ArrayIterator($this->answer());
	}

	public function queryRow($fetchAssociative = true)
	{
		return $this->answer();
	}

	public function queryColumn()
	{
		return $this->answer();
	}
}

/**
 * SQLite connection whose commands answer by the first word sequence of their SQL.
 */
class TMysqlMetaDataTestScriptedConnection extends TDbConnection
{
	/** @var array<string, mixed> SQL prefix => result */
	public array $results = [];
	public array $sql = [];

	public function createCommand($sql)
	{
		$this->sql[] = $sql;
		foreach ($this->results as $prefix => $result) {
			if (str_starts_with(ltrim($sql), $prefix)) {
				return new TMysqlMetaDataTestScriptedCommand($this, $sql, $result);
			}
		}
		throw new TDbException('dbconnection_connection_inactive');
	}
}

/**
 * TMysqlMetaData with a server version set by the test.
 */
class TMysqlMetaDataTestVersionMetaData extends TMysqlMetaData
{
	public float $version = 8.0;

	protected function getServerVersion()
	{
		return $this->version;
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

	private function scripted(array $results, float $version = 8.0): TMysqlMetaDataTestVersionMetaData
	{
		$conn = new TMysqlMetaDataTestScriptedConnection('sqlite::memory:');
		$conn->setActive(true);
		$conn->results = $results;
		$meta = new TMysqlMetaDataTestVersionMetaData($conn);
		$meta->version = $version;
		return $meta;
	}

	public static function constraintKeysProvider(): array
	{
		$primary = [['Key_name' => 'PRIMARY', 'Column_name' => 'id']];
		$index = [['Key_name' => 'name_idx', 'Column_name' => 'name']];
		$keys = [];
		foreach ([null, 'db'] as $schema) {
			foreach (['primary' => $primary, 'index' => $index, 'none' => []] as $rowsName => $rows) {
				foreach ([8.0, 5.0] as $version) {
					$keys[($schema ?? 'no schema') . ", {$rowsName}, {$version}"] = [$schema, $rows, $version, $rowsName === 'primary' ? ['id'] : []];
				}
			}
		}
		return $keys;
	}

	/**
	 * @dataProvider constraintKeysProvider
	 */
	public function testConstraintKeysReadThePrimaryKeyAndTheForeignKeysOfEitherServerVersion(?string $schema, array $rows, float $version, array $expectedPrimary): void
	{
		$meta = $this->scripted([
			'SHOW INDEX' => $rows,
			'SHOW CREATE TABLE' => ['Create Table' => 'CREATE TABLE `t` (`p` int, FOREIGN KEY (`p`) REFERENCES `parent` (`id`))'],
			'SELECT' => [['con' => 't_ibfk_1', 'col' => 'p', 'fkschema' => 'db', 'fktable' => 'parent', 'fkcol' => 'id']],
		], $version);

		[$primary, $foreign] = PradoUnit::invoke($meta, 'getConstraintKeys', $schema, 't');

		$this->assertSame($expectedPrimary, $primary);
		$this->assertSame([['keys' => ['p' => 'id'], 'table' => 'parent']], $foreign);
	}

	public function testIsViewOfAMissingTableIsFalse(): void
	{
		$meta = $this->scripted(['SHOW FULL TABLES' => false]);

		$this->assertFalse(PradoUnit::invoke($meta, 'getIsView', 'db', 'missing'));
		$this->assertFalse(PradoUnit::invoke($meta, 'getIsView', null, 'missing'));
	}

	public function testIsViewNamesTheTableWhenTheQueryFails(): void
	{
		$meta = $this->scripted(['SHOW FULL TABLES' => new TDbException('dbcommand_query_failed', 'No database selected', 'SHOW FULL TABLES')]);

		try {
			PradoUnit::invoke($meta, 'getIsView', null, 'items');
			$this->fail('Expected TDbException for a failed SHOW FULL TABLES');
		} catch (TDbException $e) {
			$this->assertSame('dbcommon_invalid_table_name', $e->getErrorCode());
			$this->assertStringContainsString('items', $e->getMessage());
		}
	}

	public function testIsViewOfAServerBeforeViewsIsFalse(): void
	{
		$meta = $this->scripted([], 5.0);

		$this->assertFalse(PradoUnit::invoke($meta, 'getIsView', null, 'items'));
	}

	public function testTableNamesOfAnEmptySchema(): void
	{
		$meta = $this->scripted(['SHOW TABLES FROM' => []]);

		$this->assertSame([], $meta->findTableNames('empty_db'));
	}
}
