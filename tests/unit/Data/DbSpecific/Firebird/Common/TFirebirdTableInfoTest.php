<?php

namespace Prado\Test\Unit\Data\DbSpecific\Firebird\Common;

use Prado\Data\Common\Firebird\TFirebirdCommandBuilder;
use Prado\Data\Common\Firebird\TFirebirdTableColumn;
use Prado\Data\Common\Firebird\TFirebirdTableInfo;
use Prado\Data\TDbConnection;

/**
 * TFirebirdTableInfo and TFirebirdTableColumn need no server.
 */
class TFirebirdTableInfoTest extends \PHPUnit\Framework\TestCase
{
	public function test_full_name_is_upper_case_and_quoted()
	{
		$info = new TFirebirdTableInfo(['TableName' => 'table1']);

		$this->assertSame('"TABLE1"', $info->getTableFullName());
	}

	public function test_command_builder_is_firebird()
	{
		$info = new TFirebirdTableInfo(['TableName' => 'table1']);
		$conn = new TDbConnection('sqlite::memory:');

		$builder = $info->createCommandBuilder($conn);

		$this->assertInstanceOf(TFirebirdCommandBuilder::class, $builder);
		$this->assertSame($conn, $builder->getDbConnection());
		$this->assertSame($info, $builder->getTableInfo());
	}

	public function test_column_php_type()
	{
		$types = [];
		foreach (['SMALLINT', ' bigint ', 'BOOLEAN', 'NUMERIC', 'DECFLOAT(16)', 'VARCHAR', 'TIMESTAMP'] as $dbType) {
			$types[$dbType] = (new TFirebirdTableColumn(['DbType' => $dbType]))->getPHPType();
		}

		$this->assertSame(['SMALLINT' => 'integer', ' bigint ' => 'integer', 'BOOLEAN' => 'boolean', 'NUMERIC' => 'float', 'DECFLOAT(16)' => 'float', 'VARCHAR' => 'string', 'TIMESTAMP' => 'string'], $types);
	}

	public function test_column_identity()
	{
		$identity = new TFirebirdTableColumn(['AutoIncrement' => true]);
		$plain = new TFirebirdTableColumn([]);

		$this->assertTrue($identity->getAutoIncrement());
		$this->assertTrue($identity->hasSequence());
		$this->assertFalse($plain->getAutoIncrement());
		$this->assertFalse($plain->hasSequence());
	}
}
