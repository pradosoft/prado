<?php

namespace Prado\Test\Unit\Data\DbSpecific\Sqlite;

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

/**
 * TSqliteMetaData primary key detection over an in-memory database.
 *
 * `PRAGMA table_info` reports each key column's 1-based position in the primary
 * key as `pk`, so a composite key has `pk` values 1, 2, ... and every one of them
 * is a key column. Only a lone `INTEGER PRIMARY KEY` aliases the rowid and
 * auto-increments.
 */
class SqliteCompositeKeyTest extends \PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$this->_conn = new TDbConnection('sqlite::memory:');
		$this->_conn->setActive(true);
		$this->_conn->createCommand('
			CREATE TABLE department_sections (
				department_id INTEGER NOT NULL,
				section_id    INTEGER NOT NULL,
				sort_key      INTEGER NOT NULL DEFAULT 0,
				PRIMARY KEY (department_id, section_id)
			)
		')->execute();
		$this->_conn->createCommand('
			CREATE TABLE sections_by_department (
				section_id    INTEGER NOT NULL,
				department_id INTEGER NOT NULL,
				PRIMARY KEY (department_id, section_id)
			)
		')->execute();
		$this->_conn->createCommand('
			CREATE TABLE departments (
				department_id INTEGER PRIMARY KEY,
				name          VARCHAR(45) NOT NULL
			)
		')->execute();
		$this->_conn->createCommand(
			'INSERT INTO department_sections (department_id, section_id, sort_key) VALUES (1, 1, 3), (1, 2, 5), (2, 1, 7)'
		)->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn !== null) {
			$this->_conn->setActive(false);
			$this->_conn = null;
		}
	}

	public function testCompositeKeyListsEveryKeyColumn()
	{
		$table = (new TSqliteMetaData($this->_conn))->getTableInfo('department_sections');
		$columns = $table->getColumns();

		$this->assertSame(['department_id', 'section_id'], $table->getPrimaryKeys());
		$this->assertTrue($columns['department_id']->getIsPrimaryKey());
		$this->assertTrue($columns['section_id']->getIsPrimaryKey());
		$this->assertFalse($columns['sort_key']->getIsPrimaryKey());
		$this->assertFalse($columns['department_id']->getAutoIncrement());
		$this->assertFalse($columns['section_id']->getAutoIncrement());
		$this->assertFalse($columns['department_id']->hasSequence());
	}

	public function testCompositeKeyOrderFollowsTheKeyDeclaration()
	{
		$table = (new TSqliteMetaData($this->_conn))->getTableInfo('sections_by_department');

		$this->assertSame(['section_id', 'department_id'], array_keys($table->getColumns()->toArray()));
		$this->assertSame(['department_id', 'section_id'], $table->getPrimaryKeys());
	}

	public function testLoneIntegerKeyAutoIncrements()
	{
		$table = (new TSqliteMetaData($this->_conn))->getTableInfo('departments');
		$columns = $table->getColumns();

		$this->assertSame(['department_id'], $table->getPrimaryKeys());
		$this->assertTrue($columns['department_id']->getIsPrimaryKey());
		$this->assertTrue($columns['department_id']->getAutoIncrement());
		$this->assertTrue($columns['department_id']->hasSequence());
		$this->assertFalse($columns['name']->getIsPrimaryKey());
		$this->assertFalse($columns['name']->getAutoIncrement());
	}

	public function testGatewayFindsAndDeletesByCompositeKey()
	{
		$gateway = new TTableGateway('department_sections', $this->_conn);

		$row = $gateway->findByPk([1, 2]);
		$this->assertEquals(['department_id' => 1, 'section_id' => 2, 'sort_key' => 5], $row);

		$rows = $gateway->findAllByPks([1, 2], [2, 1])->readAll();
		$this->assertCount(2, $rows);
		$this->assertEquals(5, $rows[0]['sort_key']);
		$this->assertEquals(7, $rows[1]['sort_key']);

		$this->assertSame(1, $gateway->deleteByPk([1, 2]));
		$this->assertFalse($gateway->findByPk([1, 2]));
		$this->assertSame(2, $gateway->count());
	}
}
