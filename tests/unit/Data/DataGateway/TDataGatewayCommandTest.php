<?php

namespace Prado\Test\Unit\Data\DataGateway;

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Collections\TAttributeCollection;
use Prado\Data\DataGateway\IDataCriteria;
use Prado\Data\DataGateway\TDataGatewayCommand;
use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

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
		$this->_conn->createCommand('CREATE TABLE loose (a INTEGER, b TEXT)')->execute();
		$this->_conn->createCommand('CREATE TABLE pairs (a INTEGER, b TEXT, val TEXT, PRIMARY KEY (a, b))')->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn !== null) {
			$this->_conn->setActive(false);
			$this->_conn = null;
		}
	}

	private function command(string $table = 'items'): TDataGatewayCommand
	{
		return new TDataGatewayCommand((new TSqliteMetaData($this->_conn))->createCommandBuilder($table));
	}

	private function names(): array
	{
		return $this->_conn->createCommand('SELECT name FROM items ORDER BY id')->queryColumn();
	}

	private function assertDbErrorCode(string $code, callable $call): void
	{
		try {
			$call();
			$this->fail("Expected TDbException {$code}");
		} catch (TDbException $e) {
			$this->assertSame($code, $e->getErrorCode());
		}
	}

	public function testIndexKeyConditionListsTheValues()
	{
		$command = $this->command();
		$condition = $command->getIndexKeyCondition($command->getTableInfo(), ['id', 'kind'], [[1, 'x'], [3, 'y']]);

		$this->assertSame("(\"items\".\"id\", \"items\".\"kind\") IN (('1', 'x'), ('3', 'y'))", $condition);
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

	public function testUpdateByPkUpdatesTheKeyedRow()
	{
		$this->assertSame(1, $this->command()->updateByPk(['name' => 'z'], 2));
		$this->assertSame(['a', 'z', 'c'], $this->names());
	}

	public function testFindByPkOfNullIsNull()
	{
		$this->assertNull($this->command()->findByPk(null));
	}

	public function testDeleteByPkWithoutKeysDeletesNothing()
	{
		$this->assertSame(0, $this->command()->deleteByPk(null));
		$this->assertSame(0, $this->command()->deleteByPk([]));
		$this->assertSame(['a', 'b', 'c'], $this->names());
	}

	public function testCompositeKeyConditionOfASingleKeyTable()
	{
		$condition = PradoUnit::invoke($this->command(), 'getCompositeKeyCondition', [1, 3]);
		$this->assertSame("(\"items\".\"id\") IN ('1', '3')", $condition);
	}

	public function testCompositeKeyConditionOfACompositeKeyTable()
	{
		$condition = PradoUnit::invoke($this->command('pairs'), 'getCompositeKeyCondition', [1, 'x']);
		$this->assertSame("(\"pairs\".\"a\", \"pairs\".\"b\") IN (('1', 'x'))", $condition);
	}

	public function testNormalizeCompositeKeyValuesErrors()
	{
		$this->assertDbErrorCode('dbtablegateway_no_primary_key_found', fn () => PradoUnit::invoke($this->command('loose'), 'normalizeCompositeKeyValues', [], [1]));
		$this->assertDbErrorCode('dbtablegateway_missing_pk_values', fn () => PradoUnit::invoke($this->command(), 'normalizeCompositeKeyValues', ['id'], []));
		$this->assertDbErrorCode('dbtablegateway_pk_value_count_mismatch', fn () => PradoUnit::invoke($this->command('pairs'), 'normalizeCompositeKeyValues', ['a', 'b'], [1]));
	}

	public function testNormalizeCompositeKeyValuesWrapsASingleCompositeKey()
	{
		$this->assertSame([[1, 'x']], PradoUnit::invoke($this->command('pairs'), 'normalizeCompositeKeyValues', ['a', 'b'], [1, 'x']));
		$this->assertSame([[1, 'x'], [2, 'y']], PradoUnit::invoke($this->command('pairs'), 'normalizeCompositeKeyValues', ['a', 'b'], [[1, 'x'], [2, 'y']]));
	}

	public function testPrimaryKeyConditionByPositionAndByName()
	{
		$this->assertSame(['"id" = :id', ['id' => 5]], PradoUnit::invoke($this->command(), 'getPrimaryKeyCondition', [5]));
		$this->assertSame(
			['"a" = :a AND "b" = :b', ['a' => 1, 'b' => 'x']],
			PradoUnit::invoke($this->command('pairs'), 'getPrimaryKeyCondition', ['b' => 'x', 0 => 1])
		);
	}

	public function testPrimaryKeyConditionRequiresAPrimaryKey()
	{
		$this->assertDbErrorCode('dbtablegateway_no_primary_key_found', fn () => PradoUnit::invoke($this->command('loose'), 'getPrimaryKeyCondition', [1]));
	}

	public function testFindBySqlReturnsTheFirstRow()
	{
		$row = $this->command()->findBySql(new TSqlCriteria('SELECT name FROM items WHERE kind = :kind', [':kind' => 'x']));
		$this->assertSame(['name' => 'a'], $row);
	}

	public function testFindAllBySqlAppliesOrderingAndLimit()
	{
		$criteria = new TSqlCriteria('SELECT name FROM items');
		$criteria->setOrdersBy('id DESC');
		$criteria->setLimit(2);
		$rows = $this->command()->findAllBySql($criteria)->readAll();
		$this->assertSame(['c', 'b'], array_column($rows, 'name'));
	}

	public function testInsertIsFalseWhenAHandlerReportsNoRowsAffected()
	{
		$command = $this->command();
		$command->attachEventHandler('OnExecuteCommand', function ($sender, $param) {
			$param->setResult(0);
		});
		$this->assertFalse($command->insert(['id' => 4, 'name' => 'd']));
		$this->assertSame(['a', 'b', 'c', 'd'], $this->names());
	}

	public function testCreateCriteriaFromStringRequiresAnArgumentPerField()
	{
		$this->assertDbErrorCode('dbtablegateway_mismatch_args_exception', fn () => $this->command()->createCriteriaFromString('findByNameAndKind', 'NameAndKind', ['a']));
	}

	public function testCreateCriteriaFromStringRequiresAKnownColumn()
	{
		$this->assertDbErrorCode('dbtablegateway_mismatch_column_name', fn () => $this->command()->createCriteriaFromString('findByColour', 'Colour', ['red']));
	}

	public function testCreateCriteriaFromStringJoinsTheFields()
	{
		$criteria = $this->command()->createCriteriaFromString('findByNameOrKind', 'NameOrKind', ['a', 'y']);
		$this->assertSame('"name" = ? OR "kind" = ?', $criteria->getCondition());
		$this->assertSame(['a', 'y'], $criteria->getParameters()->toArray());
	}

	public function testFindAllByIndexKeepsTheCriteriaOrderingAndLimit()
	{
		$criteria = new TSqlCriteria();
		$criteria->setOrdersBy('id DESC');
		$criteria->setLimit(2);
		$rows = $this->command()->findAllByIndex($criteria, ['id'], [1, 2, 3])->readAll();
		$this->assertSame(['c', 'b'], array_column($rows, 'name'));
	}

	public function testCreateCriteriaFromStringAcceptsTheArgumentsAsOneArray()
	{
		$criteria = $this->command()->createCriteriaFromString('findByNameAndKind', 'NameAndKind', [['a', 'x']]);
		$this->assertSame('"name" = ? AND "kind" = ?', $criteria->getCondition());
		$this->assertSame(['a', 'x'], $criteria->getParameters()->toArray());
	}

	public function testFindAllBySqlAppliesAnOffsetWithoutALimit()
	{
		$criteria = new TSqlCriteria('SELECT name FROM items ORDER BY id');
		$criteria->setOffset(1);
		$rows = $this->command()->findAllBySql($criteria)->readAll();
		$this->assertSame(['b', 'c'], array_column($rows, 'name'));
	}

	public function testInsertOrIgnoreWithoutASequenceReturnsTrueThenFalse()
	{
		$command = $this->command('pairs');
		$this->assertTrue($command->insertOrIgnore(['a' => 1, 'b' => 'x', 'val' => 'v']));
		$this->assertFalse($command->insertOrIgnore(['a' => 1, 'b' => 'x', 'val' => 'other']));
		$this->assertSame('v', $this->_conn->createCommand('SELECT val FROM pairs')->queryScalar());
	}

	public function testUpsertWithoutASequenceReturnsTrue()
	{
		$command = $this->command('pairs');
		$this->assertTrue($command->upsert(['a' => 1, 'b' => 'x', 'val' => 'v']));
		$this->assertTrue($command->upsert(['a' => 1, 'b' => 'x', 'val' => 'w']));
		$this->assertSame([['a' => 1, 'b' => 'x', 'val' => 'w']], $this->_conn->createCommand('SELECT a, b, val FROM pairs')->queryAll());
	}

	public function testUpsertIsFalseWhenAHandlerReportsNoRowsAffected()
	{
		$command = $this->command('pairs');
		$command->attachEventHandler('OnExecuteCommand', function ($sender, $param) {
			$param->setResult(0);
		});
		$this->assertFalse($command->upsert(['a' => 2, 'b' => 'y', 'val' => 'v']));
	}

	public function testFindAllWithANonSqlCriteriaMatchesEveryRow()
	{
		$criteria = new class () implements IDataCriteria {
			private TAttributeCollection $_parameters;
			private TAttributeCollection $_orders;

			public function __construct()
			{
				$this->_parameters = new TAttributeCollection();
				$this->_orders = new TAttributeCollection(['id' => 'desc']);
			}

			public function getParameters()
			{
				return $this->_parameters;
			}

			public function getOrdersBy()
			{
				return $this->_orders;
			}

			public function getLimit()
			{
				return -1;
			}

			public function getOffset()
			{
				return -1;
			}

			public function getSelect()
			{
				return 'name';
			}
		};
		$rows = $this->command()->findAll($criteria)->readAll();
		$this->assertSame(['c', 'b', 'a'], array_column($rows, 'name'));
	}

	public function testFindAllBySqlWithoutLimitOrOffsetReturnsEveryRow()
	{
		$criteria = new TSqlCriteria('SELECT name FROM items ORDER BY id');
		$criteria->setLimit(-1);
		$criteria->setOffset(-1);
		$command = $this->command();
		$this->assertSame(['a', 'b', 'c'], array_column($command->findAllBySql($criteria)->readAll(), 'name'));
	}
}
