<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TTableGateway — PostgreSQL.
 *
 * Exercises the full CRUD surface (insert, find, findAll, findByPk, findAllBySql,
 * update, delete, deleteByPk, count) plus TSqlCriteria filtering, ordering, and
 * limiting against the real PostgreSQL address table.
 *
 * Table schema (from initdb_pgsql.sql):
 *   address (
 *     id              SERIAL PRIMARY KEY,
 *     username        VARCHAR(128) NOT NULL,
 *     phone           CHAR(40) NOT NULL DEFAULT 'hello',
 *     field1_boolean  BOOLEAN NOT NULL,
 *     field2_date     DATE NOT NULL,
 *     field3_double   FLOAT8 NOT NULL,
 *     field4_integer  INT NOT NULL DEFAULT 1 REFERENCES address(id),
 *     field5_text     TEXT NOT NULL,
 *     field6_time     TIME NOT NULL,
 *     field7_timestamp TIMESTAMP(6) NOT NULL,
 *     field8_money    MONEY NOT NULL,
 *     field9_numeric  NUMERIC(6,4) NOT NULL,
 *     int_fk1         INT NOT NULL,
 *     int_fk2         INT NOT NULL
 *   )
 *
 * Note: field4_integer is a self-referential FK to address(id).
 * We reset the sequence to 0 before each test so the first insert always gets id=1
 * and the self-reference (field4_integer=1) is always satisfiable.
 */
class TTableGatewayPgsqlIntegrationTest extends PHPUnit\Framework\TestCase
{
	private static ?TDbConnection $conn = null;
	private static ?TTableGateway $gateway = null;

	public static function setUpBeforeClass(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$conn = PradoUnit::setupPgsqlConnection('prado_unitest');
		if (!$conn instanceof TDbConnection) {
			return; // tests will skip individually via setUp()
		}
		$tableException = PradoUnit::checkForTable($conn, 'address');
		if ($tableException !== null) {
			return; // tests will skip individually via setUp()
		}
		self::$conn    = $conn;
		self::$gateway = new TTableGateway('address', $conn);
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$conn && self::$conn->getActive()) {
			self::$conn->Active = false;
		}
		self::$conn    = null;
		self::$gateway = null;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function deleteAll(): void
	{
		self::$gateway->deleteAll('1=1');
		// Reset the SERIAL sequence so the next insert always gets id=1,
		// satisfying the self-referential FK (field4_integer=1 REFERENCES address(id)).
		// setval(seq, 1, false): "not yet called", so the next INSERT will get id=1.
		// PostgreSQL sequences have a minimum of 1; setval(seq, 0, ...) is out of range.
		self::$conn->createCommand("SELECT setval('address_id_seq', 1, false)")->execute();
	}

	private function insertRecord1(): int
	{
		return (int) self::$gateway->insert([
			'username'         => 'Username',
			'phone'            => '121987',
			'field1_boolean'   => true,
			'field2_date'      => '2007-12-25',
			'field3_double'    => 121.1,
			'field4_integer'   => 1,
			'field5_text'      => 'asdasd',
			'field6_time'      => '12:40:00',
			'field7_timestamp' => '2007-12-25 12:40:00',
			'field8_money'     => '121.12',
			'field9_numeric'   => 9.8223,
			'int_fk1'          => 1,
			'int_fk2'          => 1,
		]);
	}

	private function insertRecord2(): int
	{
		return (int) self::$gateway->insert([
			'username'         => 'record2',
			'phone'            => '45233',
			'field1_boolean'   => false,
			'field2_date'      => '2004-10-05',
			'field3_double'    => 1221.1,
			'field4_integer'   => 1,
			'field5_text'      => 'hello world',
			'field6_time'      => '22:40:00',
			'field7_timestamp' => '2004-10-05 22:40:00',
			'field8_money'     => '1121.12',
			'field9_numeric'   => 8.2213,
			'int_fk1'          => 1,
			'int_fk2'          => 1,
		]);
	}

	protected function setUp(): void
	{
		if (self::$conn === null) {
			$this->markTestSkipped('PostgreSQL not available or address table missing.');
		}
	}

	// -----------------------------------------------------------------------
	// PDO::quote(null) known bug documentation
	// -----------------------------------------------------------------------

	/**
	 * Documents the known PDO::quote(null) bug.
	 *
	 * PHP 8.1+ changed PDO::quote(null) to return '' (empty string) for some
	 * drivers instead of the SQL literal 'NULL'. TTableGateway::update() builds
	 * SET clauses by quoting every value, so null-valued columns produce broken SQL.
	 *
	 * If this assertion ever fails it means the bug is fixed and the update tests
	 * should be extended to cover null-valued columns again.
	 */
	public function test_pdo_quote_null_documents_known_bug(): void
	{
		$pdo = self::$gateway->getDbConnection()->getPdoInstance();
		$quoted = $pdo->quote(null);
		$this->assertNotEquals("'NULL'", $quoted,
			'PDO::quote(null) now returns NULL literal — the bug is fixed! ' .
			'Update update tests to cover null-valued columns.'
		);
	}

	// -----------------------------------------------------------------------
	// insert()
	// -----------------------------------------------------------------------

	public function test_insert_returns_last_insert_id(): void
	{
		$this->deleteAll();
		$id = $this->insertRecord1();
		$this->assertGreaterThan(0, $id);
	}

	public function test_insert_creates_row(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$count = (int) self::$gateway->count();
		$this->assertSame(1, $count);
	}

	public function test_insert_second_record(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$id2 = $this->insertRecord2();
		$this->assertGreaterThan(1, $id2);
		$this->assertSame(2, (int) self::$gateway->count());
	}

	// -----------------------------------------------------------------------
	// findByPk()
	// -----------------------------------------------------------------------

	public function test_find_by_pk_returns_matching_row(): void
	{
		$this->deleteAll();
		$id = $this->insertRecord1();
		$row = self::$gateway->findByPk($id);
		$this->assertIsArray($row);
		$this->assertSame('Username', $row['username']);
	}

	public function test_find_by_pk_returns_false_for_missing_pk(): void
	{
		$this->deleteAll();
		$result = self::$gateway->findByPk(99999);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// find() — positional and named parameters
	// -----------------------------------------------------------------------

	public function test_find_with_positional_parameter(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$row = self::$gateway->find('username = ?', 'Username');
		$this->assertIsArray($row);
		$this->assertSame('Username', $row['username']);
		$this->assertSame('asdasd', $row['field5_text']);
	}

	public function test_find_with_named_parameter(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$row = self::$gateway->find('username = :name', [':name' => 'Username']);
		$this->assertIsArray($row);
		$this->assertSame('Username', $row['username']);
	}

	public function test_find_returns_false_when_no_match(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$result = self::$gateway->find('username = ?', 'NoSuchUser');
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// findAll() / findAllBySql()
	// -----------------------------------------------------------------------

	public function test_find_all_returns_all_rows(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$results = self::$gateway->findAll('true')->readAll();
		$this->assertSame(2, count($results));
	}

	public function test_find_all_returns_empty_when_no_rows(): void
	{
		$this->deleteAll();
		$rows = self::$gateway->findAll('true')->readAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	public function test_find_all_by_sql(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$result = self::$gateway->findAllBySql('SELECT username FROM address WHERE phone = ?', '45233')->read();
		$this->assertSame('record2', $result['username']);
	}

	// -----------------------------------------------------------------------
	// count()
	// -----------------------------------------------------------------------

	public function test_count_returns_zero_for_empty_table(): void
	{
		$this->deleteAll();
		$this->assertSame(0, (int) self::$gateway->count());
	}

	public function test_count_increments_with_inserts(): void
	{
		$this->deleteAll();
		$this->assertSame(0, (int) self::$gateway->count());
		$this->insertRecord1();
		$this->assertSame(1, (int) self::$gateway->count());
		$this->insertRecord2();
		$this->assertSame(2, (int) self::$gateway->count());
	}

	public function test_count_with_condition(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$this->assertSame(1, (int) self::$gateway->count('username = ?', 'Username'));
		$this->assertSame(1, (int) self::$gateway->count('username = ?', 'record2'));
	}

	// -----------------------------------------------------------------------
	// update() — positional and named parameters
	// -----------------------------------------------------------------------

	public function test_update_with_positional_parameter(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$newData = ['username' => 'tester_updated', 'field5_text' => 'updated text'];
		$result = self::$gateway->update($newData, 'username = ?', 'Username');
		$this->assertTrue((bool) $result);
		$row = self::$gateway->find('username = ?', 'tester_updated');
		$this->assertIsArray($row);
		$this->assertSame('tester_updated', $row['username']);
		$this->assertSame('updated text', $row['field5_text']);
		self::$gateway->deleteAll('username = ?', 'tester_updated');
	}

	public function test_update_with_named_parameter(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$newData = ['username' => 'tester_named', 'field5_text' => 'named update'];
		$result = self::$gateway->update($newData, 'username = :name', [':name' => 'Username']);
		$this->assertTrue((bool) $result);
		$row = self::$gateway->find('username = :name', [':name' => 'tester_named']);
		$this->assertIsArray($row);
		$this->assertSame('tester_named', $row['username']);
		$this->assertSame('named update', $row['field5_text']);
		self::$gateway->deleteAll('username = :name', [':name' => 'tester_named']);
	}

	public function test_update_returns_affected_row_count(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$affected = self::$gateway->update(['field5_text' => 'bulk update'], 'int_fk1 = ?', 1);
		$this->assertSame(2, (int) $affected);
	}

	public function test_update_with_no_match_affects_zero_rows(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$affected = self::$gateway->update(['field5_text' => 'noop'], 'username = ?', 'NoSuchUser');
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// update() — boolean column
	// -----------------------------------------------------------------------

	public function test_update_boolean_field(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$result = self::$gateway->update(['field1_boolean' => false], 'username = ?', 'Username');
		$this->assertTrue((bool) $result);
		$row = self::$gateway->find('username = ?', 'Username');
		$this->assertIsArray($row);
		$boolVal = $row['field1_boolean'];
		// PostgreSQL PDO may return 'f', false, '0', or 0 for a false boolean.
		$this->assertTrue(
			$boolVal === false || $boolVal === 'f' || $boolVal === '0' || $boolVal === 0,
			'field1_boolean should be falsy after updating to false'
		);
	}

	// -----------------------------------------------------------------------
	// Type verification
	// -----------------------------------------------------------------------

	public function test_find_returns_correct_types(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$row = self::$gateway->find('username = ?', 'Username');
		$this->assertIsArray($row);
		// Boolean: PostgreSQL PDO driver returns 't' or 'f' as strings.
		$bool = $row['field1_boolean'];
		$this->assertTrue(
			$bool === true || $bool === 't' || $bool === '1' || $bool === 1,
			'field1_boolean should be truthy for the true-inserted row'
		);
		// Double / float.
		$this->assertIsNumeric($row['field3_double']);
		$this->assertEquals(121.1, (float) $row['field3_double'], '', 0.001);
		// Numeric.
		$this->assertIsNumeric($row['field9_numeric']);
	}

	// -----------------------------------------------------------------------
	// deleteAll()
	// -----------------------------------------------------------------------

	public function test_delete_all_removes_matching_rows(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		self::$gateway->deleteAll('username = ?', 'record2');
		$this->assertSame(1, (int) self::$gateway->count());
	}

	public function test_delete_all_returns_affected_count(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$affected = self::$gateway->deleteAll('1=1');
		$this->assertSame(2, (int) $affected);
	}

	public function test_delete_all_with_no_match_affects_zero_rows(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$affected = self::$gateway->deleteAll('username = ?', 'NoSuchUser');
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// deleteByPk()
	// -----------------------------------------------------------------------

	public function test_delete_by_pk_removes_row(): void
	{
		$this->deleteAll();
		$id = $this->insertRecord1();
		self::$gateway->deleteByPk([$id]);
		$this->assertFalse(self::$gateway->findByPk($id));
	}

	public function test_delete_by_pk_returns_one_for_existing_row(): void
	{
		$this->deleteAll();
		$id = $this->insertRecord1();
		$affected = self::$gateway->deleteByPk([$id]);
		$this->assertSame(1, (int) $affected);
	}

	public function test_delete_by_pk_returns_zero_for_missing_pk(): void
	{
		$this->deleteAll();
		$affected = self::$gateway->deleteByPk([99999]);
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// TSqlCriteria — ordering, limiting, conditions
	// -----------------------------------------------------------------------

	public function test_find_all_with_criteria_order_by(): void
	{
		$this->deleteAll();
		$this->insertRecord1(); // Username
		$this->insertRecord2(); // record2
		$criteria = new TSqlCriteria('true');
		$criteria->OrdersBy = ['username' => 'asc'];
		$rows = self::$gateway->findAll($criteria)->readAll();
		$this->assertSame('Username', $rows[0]['username']);
		$this->assertSame('record2', $rows[1]['username']);
	}

	public function test_find_all_with_criteria_limit(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria();
		$criteria->Limit = 1;
		$rows = self::$gateway->findAll($criteria)->readAll();
		$this->assertCount(1, $rows);
	}

	public function test_find_all_with_criteria_condition(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria('username = \'Username\'');
		$rows = self::$gateway->findAll($criteria)->readAll();
		$this->assertCount(1, $rows);
		$this->assertSame('Username', $rows[0]['username']);
	}

	public function test_count_with_criteria(): void
	{
		$this->deleteAll();
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria('username = \'record2\'');
		$count = (int) self::$gateway->count($criteria);
		$this->assertSame(1, $count);
	}
}
