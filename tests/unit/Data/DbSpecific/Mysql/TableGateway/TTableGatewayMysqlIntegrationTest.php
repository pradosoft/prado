<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TTableGateway — MySQL.
 *
 * Exercises the full CRUD surface (insert, find, findAll, findByPk, findAllBySql,
 * update, deleteAll, deleteByPk, count) plus TSqlCriteria, magic calls, table-exists,
 * and table-info introspection against a real MySQL prado_unitest database.
 *
 * Uses the `address` table (username VARCHAR PK) and `department_sections`
 * (composite PK: department_id, section_id) from initdb_mysql.sql.
 *
 * MySQL address schema (relevant columns):
 *   username        VARCHAR(255) PK
 *   phone           VARCHAR(255)
 *   field1_boolean  TINYINT(1)
 *   field2_date     DATE
 *   field3_double   DOUBLE
 *   field4_integer  INT
 *   field5_text     TEXT
 *   field6_time     TIME
 *   field7_timestamp TIMESTAMP
 *   field8_money    DECIMAL(19,4)
 *   field9_numeric  NUMERIC
 *   int_fk1         INT
 *   int_fk2         INT
 *
 * Note: MySQL address table uses username as PK (not an id SERIAL).
 * department_sections has a composite PK (department_id, section_id) and
 * pre-seeded rows.
 */
class TTableGatewayMysqlIntegrationTest extends PHPUnit\Framework\TestCase
{
	private static ?TDbConnection $conn   = null;
	private static ?TTableGateway $gateway  = null;  // address table
	private static ?TTableGateway $gateway2 = null;  // department_sections table

	public static function setUpBeforeClass(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$conn = PradoUnit::setupMysqlConnection('prado_unitest');
		if (!$conn instanceof TDbConnection) {
			return; // tests will skip individually via setUp()
		}
		foreach (['address', 'department_sections'] as $tbl) {
			$tableException = PradoUnit::checkForTable($conn, $tbl);
			if ($tableException !== null) {
				return; // tests will skip individually via setUp()
			}
		}
		self::$conn     = $conn;
		self::$gateway  = new TTableGateway('address', $conn);
		self::$gateway2 = new TTableGateway('department_sections', $conn);
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$conn !== null && self::$conn->getActive()) {
			self::$conn->Active = false;
		}
		self::$conn     = null;
		self::$gateway  = null;
		self::$gateway2 = null;
	}

	protected function setUp(): void
	{
		if (self::$conn === null) {
			$this->markTestSkipped('MySQL not available or required tables missing.');
		}
		// Clear address rows before each test. initdb_mysql.sql pre-seeds two rows
		// ('wei', 'fabio') and other test classes may leave residual rows; without
		// this tearDown only covers cleanup *after* a test, leaving the first test
		// in this class with a non-empty table.
		self::$gateway->deleteAll('1=1');
	}

	protected function tearDown(): void
	{
		// Clean address table after each test to avoid cross-test contamination.
		if (self::$gateway !== null) {
			try {
				self::$gateway->deleteAll('1=1');
			} catch (\Exception $e) {
			}
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function insertRecord1(): void
	{
		$result = self::$gateway->insert($this->getRecord1());
		$this->assertTrue((int) $result > 0 || $result !== false,
			'insert() should return a truthy result for record1'
		);
	}

	private function insertRecord2(): void
	{
		$result = self::$gateway->insert($this->getRecord2());
		$this->assertTrue((int) $result > 0 || $result !== false,
			'insert() should return a truthy result for record2'
		);
	}

	private function getRecord1(): array
	{
		return [
			'username'         => 'Username',
			'phone'            => '121987',
			'field1_boolean'   => 1,
			'field2_date'      => '2007-12-25',
			'field3_double'    => 121.1,
			'field4_integer'   => 3,
			'field5_text'      => 'asdasd',
			'field6_time'      => '12:40:00',
			'field7_timestamp' => '2007-12-25 12:40:00',
			'field8_money'     => '121.12',
			'field9_numeric'   => 98.2232,
			'int_fk1'          => 1,
			'int_fk2'          => 1,
		];
	}

	private function getRecord2(): array
	{
		return [
			'username'         => 'record2',
			'phone'            => '45233',
			'field1_boolean'   => 0,
			'field2_date'      => '2004-10-05',
			'field3_double'    => 1221.1,
			'field4_integer'   => 2,
			'field5_text'      => 'hello world',
			'field6_time'      => '22:40:00',
			'field7_timestamp' => '2004-10-05 22:40:00',
			'field8_money'     => '1121.12',
			'field9_numeric'   => 8.2213,
			'int_fk1'          => 1,
			'int_fk2'          => 1,
		];
	}

	// -----------------------------------------------------------------------
	// insert()
	// -----------------------------------------------------------------------

	public function test_insert_creates_row(): void
	{
		$this->insertRecord1();
		$this->assertSame(1, (int) self::$gateway->count());
	}

	public function test_insert_second_record(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$this->assertSame(2, (int) self::$gateway->count());
	}

	public function test_inserted_data_matches_input(): void
	{
		$this->insertRecord1();
		$row = self::$gateway->findByPk('Username');
		$this->assertIsArray($row);
		$this->assertSame('Username', $row['username']);
		$this->assertSame('121987', $row['phone']);
		$this->assertSame('asdasd', $row['field5_text']);
	}

	// -----------------------------------------------------------------------
	// findByPk()
	// -----------------------------------------------------------------------

	public function test_find_by_pk_returns_matching_row(): void
	{
		$this->insertRecord1();
		$row = self::$gateway->findByPk('Username');
		$this->assertIsArray($row);
		$this->assertSame('Username', $row['username']);
	}

	public function test_find_by_pk_returns_false_for_missing_pk(): void
	{
		$result = self::$gateway->findByPk('NoSuchUser');
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// find() — positional and named parameters
	// -----------------------------------------------------------------------

	public function test_find_with_positional_parameter(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$row = self::$gateway->find('username = ?', 'Username');
		$this->assertIsArray($row);
		$this->assertSame('Username', $row['username']);
		$this->assertSame('asdasd', $row['field5_text']);
	}

	public function test_find_with_named_parameter(): void
	{
		$this->insertRecord1();
		$row = self::$gateway->find('username = :name', [':name' => 'Username']);
		$this->assertIsArray($row);
		$this->assertSame('Username', $row['username']);
	}

	public function test_find_returns_false_when_no_match(): void
	{
		$this->insertRecord1();
		$result = self::$gateway->find('username = ?', 'NoSuchUser');
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// findAll() / findAllBySql()
	// -----------------------------------------------------------------------

	public function test_find_all_returns_all_rows(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$results = self::$gateway->findAll('1=1')->readAll();
		$this->assertSame(2, count($results));
	}

	public function test_find_all_returns_empty_array_when_table_is_empty(): void
	{
		$rows = self::$gateway->findAll('1=1')->readAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	public function test_find_all_by_sql(): void
	{
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
		$this->assertSame(0, (int) self::$gateway->count());
	}

	public function test_count_increments_with_inserts(): void
	{
		$this->assertSame(0, (int) self::$gateway->count());
		$this->insertRecord1();
		$this->assertSame(1, (int) self::$gateway->count());
		$this->insertRecord2();
		$this->assertSame(2, (int) self::$gateway->count());
	}

	public function test_count_with_condition(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$this->assertSame(1, (int) self::$gateway->count('username = ?', 'Username'));
		$this->assertSame(1, (int) self::$gateway->count('username = ?', 'record2'));
	}

	public function test_count_department_sections(): void
	{
		// department_sections is pre-seeded with 5 rows in initdb_mysql.sql
		$result = self::$gateway2->count();
		$this->assertEquals(5, $result);

		$result = self::$gateway2->count('department_id = ?', 1);
		$this->assertEquals(2, $result);
	}

	// -----------------------------------------------------------------------
	// update()
	// -----------------------------------------------------------------------

	public function test_update_modifies_matching_rows(): void
	{
		$this->insertRecord1();
		$newData = ['phone' => '999999', 'field5_text' => 'updated'];
		$result = self::$gateway->update($newData, 'username = ?', 'Username');
		$this->assertTrue((bool) $result);
		$row = self::$gateway->findByPk('Username');
		$this->assertIsArray($row);
		$this->assertSame('999999', $row['phone']);
		$this->assertSame('updated', $row['field5_text']);
	}

	public function test_update_with_named_parameter(): void
	{
		$this->insertRecord1();
		$newData = ['phone' => '777777'];
		$result = self::$gateway->update($newData, 'username = :name', [':name' => 'Username']);
		$this->assertTrue((bool) $result);
		$row = self::$gateway->find('username = :name', [':name' => 'Username']);
		$this->assertIsArray($row);
		$this->assertSame('777777', $row['phone']);
	}

	public function test_update_returns_affected_row_count(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$affected = self::$gateway->update(['int_fk1' => 99], '1=1');
		$this->assertSame(2, (int) $affected);
	}

	public function test_update_with_no_match_affects_zero_rows(): void
	{
		$this->insertRecord1();
		$affected = self::$gateway->update(['phone' => '000000'], 'username = ?', 'NoSuchUser');
		$this->assertSame(0, (int) $affected);
	}

	public function test_update_boolean_field(): void
	{
		$this->insertRecord1(); // field1_boolean = 1
		$result = self::$gateway->update(['field1_boolean' => 0], 'username = ?', 'Username');
		$this->assertTrue((bool) $result);
		$row = self::$gateway->findByPk('Username');
		$this->assertIsArray($row);
		// MySQL TINYINT(1) comes back as '0' or 0.
		$this->assertTrue(
			$row['field1_boolean'] == 0,
			'field1_boolean should be falsy after updating to 0'
		);
	}

	// -----------------------------------------------------------------------
	// deleteAll()
	// -----------------------------------------------------------------------

	public function test_delete_all_removes_matching_rows(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		self::$gateway->deleteAll('username = ?', 'record2');
		$this->assertSame(1, (int) self::$gateway->count());
	}

	public function test_delete_all_returns_affected_count(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$affected = self::$gateway->deleteAll('1=1');
		$this->assertSame(2, (int) $affected);
	}

	public function test_delete_all_with_no_match_affects_zero_rows(): void
	{
		$this->insertRecord1();
		$affected = self::$gateway->deleteAll('username = ?', 'NoSuchUser');
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// deleteByPk()
	// -----------------------------------------------------------------------

	public function test_delete_by_pk_removes_row(): void
	{
		$this->insertRecord1();
		self::$gateway->deleteByPk(['Username']);
		$this->assertFalse(self::$gateway->findByPk('Username'));
	}

	public function test_delete_by_pk_returns_one_for_existing_row(): void
	{
		$this->insertRecord1();
		$affected = self::$gateway->deleteByPk(['Username']);
		$this->assertSame(1, (int) $affected);
	}

	public function test_delete_by_pk_returns_zero_for_missing_pk(): void
	{
		$affected = self::$gateway->deleteByPk(['NoSuchUser']);
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// Magic calls (findByXxx, findAllByXxx_OR_Yyy)
	// -----------------------------------------------------------------------

	public function test_magic_find_by_column(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$result = self::$gateway->findByUsername('record2');
		$this->assertIsArray($result);
		$this->assertSame('record2', $result['username']);
	}

	public function test_magic_find_all_combined_or(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$result = self::$gateway->findAllByUsername_OR_phone('Username', '45233')->readAll();
		$this->assertSame(2, count($result));
	}

	public function test_magic_find_all_combined_and_no_result(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		// 'Username' has phone '121987', not '45233'.
		$result = self::$gateway->findAllByUsername_AND_phone('Username', '45233')->readAll();
		$this->assertSame(0, count($result));
	}

	// -----------------------------------------------------------------------
	// Composite PK findByPk / findAllByPks (department_sections)
	// -----------------------------------------------------------------------

	public function test_find_by_composite_pk(): void
	{
		$result = self::$gateway2->findByPk(1, 1);
		$this->assertIsArray($result);
		$expect = ['department_id' => 1, 'section_id' => 1, 'order' => 1];
		// Cast to int for comparison since PDO may return strings.
		$result['department_id'] = (int) $result['department_id'];
		$result['section_id']    = (int) $result['section_id'];
		$result['order']         = (int) $result['order'];
		$this->assertEquals($expect, $result);
	}

	public function test_find_all_by_pks(): void
	{
		// Seeded rows from initdb_mysql.sql: (1,1), (1,2), (2,3), (2,4), (2,5)
		$result = self::$gateway2->findAllByPks([1, 1], [2, 3])->readAll();
		$this->assertCount(2, $result);
		$keys = array_map(fn($r) => (int) $r['department_id'] . '-' . (int) $r['section_id'], $result);
		$this->assertContains('1-1', $keys);
		$this->assertContains('2-3', $keys);
	}

	// -----------------------------------------------------------------------
	// Table-exists (getTableExists)
	// -----------------------------------------------------------------------

	public function test_get_table_exists_returns_true_for_address(): void
	{
		$this->assertTrue(self::$gateway->getTableExists());
	}

	public function test_get_table_exists_returns_true_for_department_sections(): void
	{
		$this->assertTrue(self::$gateway2->getTableExists());
	}

	public function test_get_table_exists_returns_false_for_dropped_table(): void
	{
		// Create a temp table, build a gateway from TDbTableInfo, then drop it.
		self::$conn->createCommand(
			'CREATE TABLE IF NOT EXISTS `tbl_exists_probe_mysql` (`id` INT NOT NULL PRIMARY KEY)'
		)->execute();
		$info    = TDbMetaData::getInstance(self::$conn)->getTableInfo('tbl_exists_probe_mysql');
		$gateway = new TTableGateway($info, self::$conn);
		$this->assertTrue($gateway->getTableExists(), 'pre-condition: table must exist');
		self::$conn->createCommand('DROP TABLE `tbl_exists_probe_mysql`')->execute();
		$this->assertFalse($gateway->getTableExists());
	}

	// -----------------------------------------------------------------------
	// Table-info (TDbMetaData / TDbTableInfo)
	// -----------------------------------------------------------------------

	public function test_table_info_gateway_finds_rows(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$info    = TDbMetaData::getInstance(self::$conn)->getTableInfo('address');
		$gwViaInfo = new TTableGateway($info, self::$conn);
		$this->assertSame(2, count($gwViaInfo->findAll()->readAll()));
	}

	// -----------------------------------------------------------------------
	// TSqlCriteria — ordering, limiting, conditions
	// -----------------------------------------------------------------------

	public function test_find_all_with_criteria_order_by(): void
	{
		$this->insertRecord1(); // Username
		$this->insertRecord2(); // record2
		$criteria = new TSqlCriteria('1=1');
		$criteria->OrdersBy = ['username' => 'asc'];
		$rows = self::$gateway->findAll($criteria)->readAll();
		// MySQL uses case-insensitive collation (utf8mb4_general_ci) by default,
		// so 'record2' (r) sorts before 'Username' (u) — opposite of ASCII order.
		$this->assertSame('record2', $rows[0]['username']);
		$this->assertSame('Username', $rows[1]['username']);
	}

	public function test_find_all_with_criteria_limit(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria();
		$criteria->Limit = 1;
		$rows = self::$gateway->findAll($criteria)->readAll();
		$this->assertCount(1, $rows);
	}

	public function test_find_all_with_criteria_condition(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria('username = \'Username\'');
		$rows = self::$gateway->findAll($criteria)->readAll();
		$this->assertCount(1, $rows);
		$this->assertSame('Username', $rows[0]['username']);
	}

	public function test_count_with_criteria(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria('username = \'record2\'');
		$count = (int) self::$gateway->count($criteria);
		$this->assertSame(1, $count);
	}
}
