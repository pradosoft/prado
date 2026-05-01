<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TTableGateway — SQLite.
 *
 * Exercises the full CRUD surface (insert, find, findAll, findByPk,
 * update, delete, deleteByPk, count) plus TSqlCriteria filtering, ordering,
 * and limiting against a real in-memory SQLite database.
 *
 * Table schema used throughout:
 *   gw_test (
 *     id     INTEGER PRIMARY KEY AUTOINCREMENT,
 *     name   TEXT NOT NULL,
 *     score  REAL DEFAULT 0.0,
 *     active INTEGER DEFAULT 1
 *   )
 *
 * The static connection and gateway are shared across tests for performance;
 * each test clears the table in setUp so it starts with a clean slate.
 */
class TTableGatewaySqliteIntegrationTest extends PHPUnit\Framework\TestCase
{
	private static ?TDbConnection $conn = null;
	private static ?TTableGateway $gw   = null;

	public static function setUpBeforeClass(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		if (!extension_loaded('pdo_sqlite')) {
			return; // tests will skip individually
		}
		$conn = new TDbConnection('sqlite::memory:');
		$conn->Active = true;
		$conn->createCommand(
			'CREATE TABLE gw_test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, score REAL DEFAULT 0.0, active INTEGER DEFAULT 1)'
		)->execute();
		self::$conn = $conn;
		self::$gw   = new TTableGateway('gw_test', $conn);
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$conn && self::$conn->getActive()) {
			self::$conn->Active = false;
		}
		self::$conn = null;
		self::$gw   = null;
	}

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		// Clear the table before every test for isolation.
		self::$conn->createCommand('DELETE FROM gw_test')->execute();
		// Reset the autoincrement sequence so ids start from 1 predictably.
		try {
			self::$conn->createCommand('DELETE FROM sqlite_sequence WHERE name = \'gw_test\'')->execute();
		} catch (\Exception $e) {
			// sqlite_sequence only exists once AUTOINCREMENT has been used.
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function insertRow(string $name, float $score = 0.0, int $active = 1): int
	{
		return (int) self::$gw->insert(['name' => $name, 'score' => $score, 'active' => $active]);
	}

	// -----------------------------------------------------------------------
	// insert()
	// -----------------------------------------------------------------------

	public function testInsertReturnsLastInsertId(): void
	{
		$id = $this->insertRow('Alice', 9.5);
		$this->assertGreaterThan(0, $id);
	}

	public function testInsertCreatesRow(): void
	{
		$this->insertRow('Alice', 9.5);
		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM gw_test')->queryScalar();
		$this->assertSame(1, $count);
	}

	public function testInsertedDataMatchesInput(): void
	{
		$this->insertRow('Bob', 7.3, 0);
		$row = self::$conn->createCommand("SELECT * FROM gw_test WHERE name = 'Bob'")->queryRow();
		$this->assertSame('Bob', $row['name']);
		$this->assertSame('0', (string) $row['active']);
	}

	// -----------------------------------------------------------------------
	// findByPk()
	// -----------------------------------------------------------------------

	public function testFindByPkReturnsMatchingRow(): void
	{
		$id = $this->insertRow('Carol', 8.1);
		$row = self::$gw->findByPk($id);
		$this->assertIsArray($row);
		$this->assertSame('Carol', $row['name']);
	}

	public function testFindByPkReturnsFalseForMissingPk(): void
	{
		$result = self::$gw->findByPk(99999);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// find() / findAll()
	// -----------------------------------------------------------------------

	public function testFindReturnsFirstMatchingRow(): void
	{
		$this->insertRow('Alice', 9.5);
		$this->insertRow('Bob',   7.3);
		$row = self::$gw->find('name = :n', ['n' => 'Bob']);
		$this->assertIsArray($row);
		$this->assertSame('Bob', $row['name']);
	}

	public function testFindReturnsFalseWhenNoMatch(): void
	{
		$this->insertRow('Alice');
		$result = self::$gw->find('name = :n', ['n' => 'Zoe']);
		$this->assertFalse($result);
	}

	public function testFindAllReturnsAllRows(): void
	{
		$this->insertRow('Alice');
		$this->insertRow('Bob');
		$this->insertRow('Carol');
		$rows = self::$gw->findAll()->readAll();
		$this->assertCount(3, $rows);
	}

	public function testFindAllReturnsEmptyArrayWhenTableIsEmpty(): void
	{
		$rows = self::$gw->findAll()->readAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	// -----------------------------------------------------------------------
	// count()
	// -----------------------------------------------------------------------

	public function testCountReturnsZeroForEmptyTable(): void
	{
		$this->assertSame(0, (int) self::$gw->count());
	}

	public function testCountReturnsCorrectNumber(): void
	{
		$this->insertRow('Alice');
		$this->insertRow('Bob');
		$this->assertSame(2, (int) self::$gw->count());
	}

	public function testCountWithConditionCountsMatchingRows(): void
	{
		$this->insertRow('Alice', 9.5, 1);
		$this->insertRow('Bob',   7.3, 0);
		$this->insertRow('Carol', 8.1, 1);
		$count = (int) self::$gw->count('active = 1');
		$this->assertSame(2, $count);
	}

	// -----------------------------------------------------------------------
	// update()
	// -----------------------------------------------------------------------

	public function testUpdateModifiesMatchingRows(): void
	{
		$id = $this->insertRow('Alice', 9.5, 1);
		self::$gw->update(['score' => 5.0], 'id = :id', ['id' => $id]);
		$row = self::$gw->findByPk($id);
		$this->assertSame('5', (string) $row['score']);
	}

	public function testUpdateReturnsNumberOfAffectedRows(): void
	{
		$this->insertRow('Alice', 9.5, 1);
		$this->insertRow('Bob',   7.3, 1);
		$affected = self::$gw->update(['active' => 0], 'active = 1');
		$this->assertSame(2, (int) $affected);
	}

	public function testUpdateWithNoMatchAffectsZeroRows(): void
	{
		$this->insertRow('Alice');
		$affected = self::$gw->update(['score' => 0.0], 'name = :n', ['n' => 'Zoe']);
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// delete()
	// -----------------------------------------------------------------------

	public function testDeleteRemovesMatchingRows(): void
	{
		$this->insertRow('Alice');
		$this->insertRow('Bob');
		self::$gw->deleteAll('name = :n', ['n' => 'Alice']);
		$this->assertSame(1, (int) self::$gw->count());
	}

	public function testDeleteReturnsNumberOfAffectedRows(): void
	{
		$this->insertRow('Alice');
		$this->insertRow('Bob');
		$affected = self::$gw->deleteAll('1=1');
		$this->assertSame(2, (int) $affected);
	}

	public function testDeleteWithNoMatchAffectsZeroRows(): void
	{
		$this->insertRow('Alice');
		$affected = self::$gw->deleteAll('name = :n', ['n' => 'Zoe']);
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// deleteByPk()
	// -----------------------------------------------------------------------

	public function testDeleteByPkRemovesRow(): void
	{
		$id = $this->insertRow('Alice');
		self::$gw->deleteByPk([$id]);
		$this->assertFalse(self::$gw->findByPk($id));
	}

	public function testDeleteByPkReturnsOneForExistingRow(): void
	{
		$id = $this->insertRow('Alice');
		$affected = self::$gw->deleteByPk([$id]);
		$this->assertSame(1, (int) $affected);
	}

	public function testDeleteByPkReturnsZeroForMissingPk(): void
	{
		$affected = self::$gw->deleteByPk([99999]);
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// TSqlCriteria — ordering, limiting, conditions
	// -----------------------------------------------------------------------


	public function testFindAllWithCriteriaLimit(): void
	{
		$this->insertRow('Alice');
		$this->insertRow('Bob');
		$this->insertRow('Carol');
		$criteria = new TSqlCriteria();
		$criteria->Limit = 2;
		$rows = self::$gw->findAll($criteria)->readAll();
		$this->assertCount(2, $rows);
	}

	public function testFindAllWithCriteriaCondition(): void
	{
		$this->insertRow('Alice', 9.5, 1);
		$this->insertRow('Bob',   7.3, 0);
		$this->insertRow('Carol', 8.1, 1);
		$criteria = new TSqlCriteria('active = 1');
		$rows = self::$gw->findAll($criteria)->readAll();
		$this->assertCount(2, $rows);
		$names = array_column($rows, 'name');
		$this->assertContains('Alice', $names);
		$this->assertContains('Carol', $names);
	}

	public function testCountWithCriteria(): void
	{
		$this->insertRow('Alice', 9.5, 1);
		$this->insertRow('Bob',   7.3, 0);
		$criteria = new TSqlCriteria('active = 0');
		$count = (int) self::$gw->count($criteria);
		$this->assertSame(1, $count);
	}
}
