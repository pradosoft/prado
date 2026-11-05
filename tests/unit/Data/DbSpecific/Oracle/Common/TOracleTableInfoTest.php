<?php

namespace Prado\Test\Unit\Data\DbSpecific\Oracle\Common;

use Prado\Data\Common\IDataHasSchema;
use Prado\Data\Common\Oracle\TOracleCommandBuilder;
use Prado\Data\Common\Oracle\TOracleTableColumn;
use Prado\Data\Common\Oracle\TOracleTableInfo;
use Prado\Data\TDbConnection;

/**
 * TOracleTableInfo and TOracleTableColumn need no server.
 */
class TOracleTableInfoTest extends \PHPUnit\Framework\TestCase
{
	public function test_full_name_is_schema_qualified()
	{
		$info = new TOracleTableInfo(['SchemaName' => 'PRADO', 'TableName' => 'table1']);

		$this->assertInstanceOf(IDataHasSchema::class, $info);
		$this->assertSame('PRADO.table1', $info->getTableFullName());
	}

	public function test_command_builder_is_oracle()
	{
		$info = new TOracleTableInfo(['SchemaName' => 'PRADO', 'TableName' => 'table1']);
		$conn = new TDbConnection('sqlite::memory:');

		$builder = $info->createCommandBuilder($conn);

		$this->assertInstanceOf(TOracleCommandBuilder::class, $builder);
		$this->assertSame($conn, $builder->getDbConnection());
		$this->assertSame($info, $builder->getTableInfo());
	}

	public function test_column_php_type()
	{
		$types = [];
		foreach (['NUMERIC', 'numeric', 'NUMBER', 'VARCHAR2', 'DATE'] as $dbType) {
			$types[$dbType] = (new TOracleTableColumn(['DbType' => $dbType]))->getPHPType();
		}

		$this->assertSame(['NUMERIC' => 'numeric', 'numeric' => 'numeric', 'NUMBER' => 'string', 'VARCHAR2' => 'string', 'DATE' => 'string'], $types);
	}
}
