<?php

/**
 * TSqlMapInsertOrIgnoreConfigTest — unit tests for the SqlMap insertOrIgnore and upsert
 * configuration classes and mapped statement classes.
 *
 * These tests are pure unit tests requiring no database connection.
 * They cover:
 *  - TSqlMapInsertOrIgnore: inheritance from TSqlMapInsert
 *  - TSqlMapUpsert: updateColumns / conflictColumns property parsing
 *  - TInsertOrIgnoreMappedStatement: inheritance from TInsertMappedStatement
 *  - TUpsertMappedStatement: inheritance from TInsertMappedStatement
 *
 * @since 4.3.3
 */

use Prado\Data\SqlMap\Configuration\TSqlMapInsert;
use Prado\Data\SqlMap\Configuration\TSqlMapInsertOrIgnore;
use Prado\Data\SqlMap\Configuration\TSqlMapUpsert;
use Prado\Data\SqlMap\Statements\TInsertMappedStatement;
use Prado\Data\SqlMap\Statements\TInsertOrIgnoreMappedStatement;
use Prado\Data\SqlMap\Statements\TUpsertMappedStatement;

class TSqlMapInsertOrIgnoreConfigTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// TSqlMapInsertOrIgnore
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_extends_TSqlMapInsert(): void
	{
		$obj = new TSqlMapInsertOrIgnore();
		$this->assertInstanceOf(TSqlMapInsert::class, $obj);
	}

	public function test_insertOrIgnore_is_distinct_class(): void
	{
		$this->assertNotEquals(TSqlMapInsert::class, TSqlMapInsertOrIgnore::class);
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — inheritance
	// -----------------------------------------------------------------------

	public function test_upsert_extends_TSqlMapInsert(): void
	{
		$obj = new TSqlMapUpsert();
		$this->assertInstanceOf(TSqlMapInsert::class, $obj);
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — updateColumns default
	// -----------------------------------------------------------------------

	public function test_updateColumns_defaults_to_null(): void
	{
		$upsert = new TSqlMapUpsert();
		$this->assertNull($upsert->getUpdateColumns());
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — updateColumns setter: string input
	// -----------------------------------------------------------------------

	public function test_setUpdateColumns_single_column_string(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setUpdateColumns('score');
		$this->assertSame(['score'], $upsert->getUpdateColumns());
	}

	public function test_setUpdateColumns_comma_separated_string(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setUpdateColumns('score,age');
		$this->assertSame(['score', 'age'], $upsert->getUpdateColumns());
	}

	public function test_setUpdateColumns_trims_whitespace_around_commas(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setUpdateColumns(' score , age , email ');
		$this->assertSame(['score', 'age', 'email'], $upsert->getUpdateColumns());
	}

	public function test_setUpdateColumns_trims_whitespace_single_value(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setUpdateColumns('  score  ');
		$this->assertSame(['score'], $upsert->getUpdateColumns());
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — updateColumns setter: array input
	// -----------------------------------------------------------------------

	public function test_setUpdateColumns_from_array(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setUpdateColumns(['score', 'age']);
		$this->assertSame(['score', 'age'], $upsert->getUpdateColumns());
	}

	public function test_setUpdateColumns_from_single_element_array(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setUpdateColumns(['score']);
		$this->assertSame(['score'], $upsert->getUpdateColumns());
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — conflictColumns default
	// -----------------------------------------------------------------------

	public function test_conflictColumns_defaults_to_null(): void
	{
		$upsert = new TSqlMapUpsert();
		$this->assertNull($upsert->getConflictColumns());
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — conflictColumns setter: string input
	// -----------------------------------------------------------------------

	public function test_setConflictColumns_single_column_string(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setConflictColumns('username');
		$this->assertSame(['username'], $upsert->getConflictColumns());
	}

	public function test_setConflictColumns_comma_separated_string(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setConflictColumns('tenant_id,username');
		$this->assertSame(['tenant_id', 'username'], $upsert->getConflictColumns());
	}

	public function test_setConflictColumns_trims_whitespace(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setConflictColumns(' tenant_id , username ');
		$this->assertSame(['tenant_id', 'username'], $upsert->getConflictColumns());
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — conflictColumns setter: array input
	// -----------------------------------------------------------------------

	public function test_setConflictColumns_from_array(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setConflictColumns(['tenant_id', 'username']);
		$this->assertSame(['tenant_id', 'username'], $upsert->getConflictColumns());
	}

	public function test_setConflictColumns_from_single_element_array(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setConflictColumns(['id']);
		$this->assertSame(['id'], $upsert->getConflictColumns());
	}

	// -----------------------------------------------------------------------
	// TSqlMapUpsert — updateColumns and conflictColumns are independent
	// -----------------------------------------------------------------------

	public function test_updateColumns_and_conflictColumns_are_independent(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setUpdateColumns('score');
		$upsert->setConflictColumns('username');

		$this->assertSame(['score'], $upsert->getUpdateColumns());
		$this->assertSame(['username'], $upsert->getConflictColumns());
	}

	public function test_updateColumns_null_does_not_affect_conflictColumns(): void
	{
		$upsert = new TSqlMapUpsert();
		$upsert->setConflictColumns('id');

		$this->assertNull($upsert->getUpdateColumns());
		$this->assertSame(['id'], $upsert->getConflictColumns());
	}

	// -----------------------------------------------------------------------
	// TInsertOrIgnoreMappedStatement — inheritance
	// -----------------------------------------------------------------------

	public function test_insertOrIgnoreMappedStatement_extends_TInsertMappedStatement(): void
	{
		// Verify the class hierarchy without instantiation (constructor needs a manager)
		$this->assertTrue(
			is_subclass_of(TInsertOrIgnoreMappedStatement::class, TInsertMappedStatement::class)
		);
	}

	public function test_insertOrIgnoreMappedStatement_is_distinct_class(): void
	{
		$this->assertNotEquals(TInsertMappedStatement::class, TInsertOrIgnoreMappedStatement::class);
	}

	// -----------------------------------------------------------------------
	// TUpsertMappedStatement — inheritance
	// -----------------------------------------------------------------------

	public function test_upsertMappedStatement_extends_TInsertMappedStatement(): void
	{
		$this->assertTrue(
			is_subclass_of(TUpsertMappedStatement::class, TInsertMappedStatement::class)
		);
	}

	public function test_upsertMappedStatement_is_distinct_from_insertOrIgnoreMappedStatement(): void
	{
		$this->assertNotEquals(TInsertOrIgnoreMappedStatement::class, TUpsertMappedStatement::class);
	}
}
