<?php

namespace Prado\Test\Unit\Data\DataGateway;

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\DataGateway\TDataGatewayCommand;
use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\TDbConnection;

/**
 * TDataGatewayCommand over an in-memory SQLite database.
 */
class TDataGatewayCommandTest extends \PHPUnit\Framework\TestCase
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

	private function command(): TDataGatewayCommand
	{
		return new TDataGatewayCommand((new TSqliteMetaData($this->_conn))->createCommandBuilder('items'));
	}

	public function testIndexKeyConditionListsTheValues()
	{
		$command = $this->command();
		$condition = $command->getIndexKeyCondition($command->getTableInfo(), ['id', 'kind'], [[1, 'x'], [3, 'y']]);

		$this->assertSame("('items'.\"id\", 'items'.\"kind\") IN (('1', 'x'), ('3', 'y'))", $condition);
	}

	public function testIndexKeyConditionWithoutValuesMatchesNoRow()
	{
		$command = $this->command();
		$condition = $command->getIndexKeyCondition($command->getTableInfo(), ['id'], []);

		$this->assertSame('1=0', $condition);
		$this->assertSame([], $command->findAllByIndex(new TSqlCriteria(), ['id'], [])->readAll());
		$this->assertSame([], $command->findAllByIndex(new TSqlCriteria("kind = 'x'"), ['id'], [])->readAll());
	}

	public function testFindAllByIndexCombinesTheCriteriaCondition()
	{
		$rows = $this->command()->findAllByIndex(new TSqlCriteria("kind = 'x'"), ['id'], [1, 3])->readAll();

		$this->assertSame([1], array_map('intval', array_column($rows, 'id')));
	}
}
