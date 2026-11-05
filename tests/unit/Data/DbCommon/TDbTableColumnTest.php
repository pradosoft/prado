<?php

namespace Prado\Test\Unit\Data\DbCommon;

use Prado\Data\Common\TDbTableColumn;
use Prado\Data\Common\Mysql\TMysqlTableColumn;
use Prado\Data\Common\Sqlite\TSqliteTableColumn;
use Prado\Data\TDbConnection;
use Prado\Test\Unit\PradoUnit;

/**
 * Unit tests for TDbTableColumn — the base column-metadata value object.
 *
 * All behaviour tested here is implemented directly in TDbTableColumn; no
 * database connection is required.
 */
class TDbTableColumnTest extends \PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Constant
	// -----------------------------------------------------------------------

	public function test_undefined_value_is_inf()
	{
		$this->assertSame(INF, TDbTableColumn::UNDEFINED_VALUE);
	}

	// -----------------------------------------------------------------------
	// Defaults when constructed with an empty info array
	// -----------------------------------------------------------------------

	public function test_defaults_when_info_empty()
	{
		$col = new TDbTableColumn([]);

		$this->assertNull($col->getColumnName());
		$this->assertNull($col->getColumnId());
		$this->assertNull($col->getColumnSize());
		$this->assertNull($col->getColumnIndex());
		$this->assertNull($col->getDbType());
		$this->assertFalse($col->getAllowNull());          // default false
		$this->assertSame(TDbTableColumn::UNDEFINED_VALUE, $col->getDefaultValue());
		$this->assertNull($col->getNumericPrecision());
		$this->assertNull($col->getNumericScale());
		$this->assertFalse($col->getIsPrimaryKey());       // default false
		$this->assertFalse($col->getIsForeignKey());       // default false
		$this->assertNull($col->getSequenceName());
		$this->assertFalse($col->hasSequence());
		$this->assertFalse($col->getIsExcluded());         // always false in base class
	}

	// -----------------------------------------------------------------------
	// Getters reflect the info array passed to the constructor
	// -----------------------------------------------------------------------

	public function test_getters_return_info_values()
	{
		$info = [
			'ColumnName'       => '"myCol"',
			'ColumnId'         => 'myCol',
			'ColumnSize'       => 255,
			'ColumnIndex'      => 3,
			'DbType'           => 'varchar',
			'AllowNull'        => true,
			'DefaultValue'     => 'hello',
			'NumericPrecision' => 10,
			'NumericScale'     => 4,
			'IsPrimaryKey'     => true,
			'IsForeignKey'     => true,
			'SequenceName'     => 'mySeq',
		];
		$col = new TDbTableColumn($info);

		$this->assertEquals('"myCol"', $col->getColumnName());
		$this->assertEquals('myCol', $col->getColumnId());
		$this->assertEquals(255, $col->getColumnSize());
		$this->assertEquals(3, $col->getColumnIndex());
		$this->assertEquals('varchar', $col->getDbType());
		$this->assertTrue($col->getAllowNull());
		$this->assertEquals('hello', $col->getDefaultValue());
		$this->assertEquals(10, $col->getNumericPrecision());
		$this->assertEquals(4, $col->getNumericScale());
		$this->assertTrue($col->getIsPrimaryKey());
		$this->assertTrue($col->getIsForeignKey());
		$this->assertEquals('mySeq', $col->getSequenceName());
		$this->assertTrue($col->hasSequence());
	}

	// -----------------------------------------------------------------------
	// PHPType / PdoType
	// -----------------------------------------------------------------------

	public function test_get_php_type_returns_string_by_default()
	{
		$col = new TDbTableColumn([]);
		$this->assertEquals('string', $col->getPHPType());
	}

	public function test_get_pdo_type_returns_param_str_for_string_php_type()
	{
		$col = new TDbTableColumn([]);
		$this->assertEquals(\PDO::PARAM_STR, $col->getPdoType());
	}

	// -----------------------------------------------------------------------
	// getMaxiumNumericConstraint
	// -----------------------------------------------------------------------

	public function test_maximum_numeric_constraint_precision_only()
	{
		// precision=5, scale=null → 10^5
		$col = new TDbTableColumn(['NumericPrecision' => 5]);
		$this->assertEquals(pow(10, 5), $col->getMaxiumNumericConstraint());
	}

	public function test_maximum_numeric_constraint_with_scale()
	{
		// precision=10, scale=4 → 10^(10-4) = 10^6
		$col = new TDbTableColumn(['NumericPrecision' => 10, 'NumericScale' => 4]);
		$this->assertEquals(pow(10, 6), $col->getMaxiumNumericConstraint());
	}

	public function test_maximum_numeric_constraint_no_precision_returns_null()
	{
		$col = new TDbTableColumn([]);
		$this->assertNull($col->getMaxiumNumericConstraint());
	}

	// -----------------------------------------------------------------------
	// Property access via magic __get (TComponent)
	// -----------------------------------------------------------------------

	public function test_magic_property_access()
	{
		$col = new TDbTableColumn([
			'ColumnName'  => '"col"',
			'IsPrimaryKey' => true,
		]);

		$this->assertEquals('"col"', $col->ColumnName);
		$this->assertTrue($col->IsPrimaryKey);
	}

	public function test_set_info_stores_a_value_read_by_its_getter()
	{
		$col = new TDbTableColumn(['ColumnName' => '"a"']);
		PradoUnit::invoke($col, 'setInfo', 'ColumnName', '"b"');
		$this->assertSame('"b"', $col->getColumnName());
	}

	public function test_param_type_follows_the_php_type()
	{
		$this->assertSame(TDbConnection::PARAM_BOOL, (new TSqliteTableColumn(['DbType' => 'boolean']))->getParamType());
		$this->assertSame(TDbConnection::PARAM_INT, (new TSqliteTableColumn(['DbType' => 'integer']))->getParamType());
		$this->assertSame(TDbConnection::PARAM_STR, (new TSqliteTableColumn(['DbType' => 'text']))->getParamType());
		$this->assertSame(TDbConnection::PARAM_STR, (new TSqliteTableColumn(['DbType' => 'real']))->getParamType());
	}

	public function test_mysql_tinyint_is_boolean_by_size_or_raw_type()
	{
		$this->assertSame('boolean', (new TMysqlTableColumn(['DbType' => 'tinyint', 'ColumnSize' => 1]))->getPHPType());
		$this->assertSame('boolean', (new TMysqlTableColumn(['DbType' => 'tinyint', 'ColumnType' => 'TINYINT(1)']))->getPHPType());
		$this->assertSame('integer', (new TMysqlTableColumn(['DbType' => 'tinyint unsigned', 'ColumnSize' => 4, 'ColumnType' => 'tinyint(4) unsigned']))->getPHPType());
	}
}
