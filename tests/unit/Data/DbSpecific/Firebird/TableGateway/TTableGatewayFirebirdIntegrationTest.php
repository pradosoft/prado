<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TTableGateway — Firebird.
 *
 * Exercises insert, findByPk, find, findAll, findAllBySql, count, update,
 * deleteAll, deleteByPk, and TSqlCriteria against the real Firebird
 * prado_unitest database.
 *
 * Table schema (from initdb_firebird.sql):
 *   address (
 *     username    VARCHAR(128)     NOT NULL PK,
 *     phone       VARCHAR(40),
 *     field1_bool BOOLEAN,
 *     field2_date DATE,
 *     field3_dbl  DOUBLE PRECISION,
 *     field4_int  INTEGER,
 *     field5_text BLOB SUB_TYPE TEXT,
 *     field6_time TIME,
 *     field7_ts   TIMESTAMP,
 *     field8_dec  DECIMAL(19,4),
 *     field9_num  NUMERIC(10,4),
 *     int_fk1     INTEGER,
 *     int_fk2     INTEGER
 *   )
 *
 * Note: Firebird address field4_int has no FK constraint — it is a plain integer.
 * Username is the PK (string). Firebird uses double-quote identifiers.
 */
class TTableGatewayFirebirdIntegrationTest extends PHPUnit\Framework\TestCase
{
	private static ?TDbConnection $conn    = null;
	private static ?TTableGateway $gateway = null;

	public static function setUpBeforeClass(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$conn = PradoUnit::setupFirebirdConnection();
		if (!$conn instanceof TDbConnection) {
			return;
		}
		$tableException = PradoUnit::checkForTable($conn, 'address');
		if ($tableException !== null) {
			return;
		}
		self::$conn    = $conn;
		self::$gateway = new TTableGateway('address', $conn);
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$conn !== null && self::$conn->getActive()) {
			self::$conn->Active = false;
		}
		self::$conn    = null;
		self::$gateway = null;
	}

	protected function setUp(): void
	{
		if (self::$conn === null) {
			$this->markTestSkipped('Firebird not available or address table missing.');
		}
	}

	protected function tearDown(): void
	{
		if (self::$gateway !== null) {
			try {
				self::$gateway->deleteAll("username <> 'wei'");
			} catch (\Exception $e) {
			}
		}
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function insertRecord1(): void
	{
		self::$gateway->insert([
			'username'   => 'tgw_user1',
			'phone'      => '111111',
			'field1_bool' => false,
			'field2_date' => '2007-12-25',
			'field3_dbl'  => 121.1,
			'field4_int'  => 3,
			'field5_text' => 'hello firebird',
			'field6_time' => '12:40:00',
			'field7_ts'   => '2007-12-25 12:40:00',
			'field8_dec'  => '121.12',
			'field9_num'  => '9.8223',
			'int_fk1'     => 0,
			'int_fk2'     => 0,
		]);
	}

	private function insertRecord2(): void
	{
		self::$gateway->insert([
			'username'   => 'tgw_user2',
			'phone'      => '222222',
			'field1_bool' => false,
			'field2_date' => '2004-10-05',
			'field3_dbl'  => 1221.1,
			'field4_int'  => 2,
			'field5_text' => 'world firebird',
			'field6_time' => '22:40:00',
			'field7_ts'   => '2004-10-05 22:40:00',
			'field8_dec'  => '1121.12',
			'field9_num'  => '8.2213',
			'int_fk1'     => 0,
			'int_fk2'     => 0,
		]);
	}

	// -----------------------------------------------------------------------
	// insert()
	// -----------------------------------------------------------------------

	public function test_insert_creates_row(): void
	{
		$this->insertRecord1();
		$count = (int) self::$gateway->count("username = 'tgw_user1'");
		$this->assertSame(1, $count);
	}

	// -----------------------------------------------------------------------
	// findByPk()
	// -----------------------------------------------------------------------

	public function test_find_by_pk_returns_matching_row(): void
	{
		$this->insertRecord1();
		$row = self::$gateway->findByPk('tgw_user1');
		$this->assertIsArray($row);
		$username = $row['username'] ?? $row['USERNAME'] ?? null;
		$this->assertSame('tgw_user1', $username);
	}

	public function test_find_by_pk_returns_false_for_missing_pk(): void
	{
		$result = self::$gateway->findByPk('no_such_user_xyz');
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// find()
	// -----------------------------------------------------------------------

	public function test_find_with_positional_parameter(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$row = self::$gateway->find('username = ?', 'tgw_user1');
		$this->assertIsArray($row);
		$username = $row['username'] ?? $row['USERNAME'] ?? null;
		$this->assertSame('tgw_user1', $username);
	}

	public function test_find_with_named_parameter(): void
	{
		$this->insertRecord1();
		$row = self::$gateway->find('username = :name', [':name' => 'tgw_user1']);
		$this->assertIsArray($row);
		$username = $row['username'] ?? $row['USERNAME'] ?? null;
		$this->assertSame('tgw_user1', $username);
	}

	public function test_find_returns_false_when_no_match(): void
	{
		$result = self::$gateway->find('username = ?', 'no_such_user_xyz');
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// findAll() / findAllBySql()
	// -----------------------------------------------------------------------

	public function test_find_all_returns_inserted_rows(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$rows = self::$gateway->findAll("username LIKE 'tgw\\_%%' ESCAPE '\\'");
		$this->assertSame(2, count($rows->readAll()));
	}

	public function test_find_all_by_sql(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$result = self::$gateway->findAllBySql(
			'SELECT username FROM address WHERE phone = ?', '222222'
		)->read();
		$username = $result['username'] ?? $result['USERNAME'] ?? null;
		$this->assertSame('tgw_user2', $username);
	}

	// -----------------------------------------------------------------------
	// count()
	// -----------------------------------------------------------------------

	public function test_count_with_condition(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$this->assertSame(1, (int) self::$gateway->count('username = ?', 'tgw_user1'));
		$this->assertSame(1, (int) self::$gateway->count('username = ?', 'tgw_user2'));
	}

	// -----------------------------------------------------------------------
	// update()
	// -----------------------------------------------------------------------

	public function test_update_modifies_matching_rows(): void
	{
		$this->insertRecord1();
		$result = self::$gateway->update(['phone' => '999999'], 'username = ?', 'tgw_user1');
		$this->assertTrue((bool) $result);
		$row = self::$gateway->findByPk('tgw_user1');
		$this->assertIsArray($row);
		$phone = $row['phone'] ?? $row['PHONE'] ?? null;
		$this->assertSame('999999', trim((string) $phone));
	}

	public function test_update_with_no_match_affects_zero_rows(): void
	{
		$this->insertRecord1();
		$affected = self::$gateway->update(['phone' => '000000'], 'username = ?', 'no_such_user_xyz');
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// deleteAll()
	// -----------------------------------------------------------------------

	public function test_delete_all_removes_matching_rows(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		self::$gateway->deleteAll('username = ?', 'tgw_user2');
		$this->assertSame(0, (int) self::$gateway->count('username = ?', 'tgw_user2'));
		$this->assertSame(1, (int) self::$gateway->count('username = ?', 'tgw_user1'));
	}

	public function test_delete_all_with_no_match_affects_zero_rows(): void
	{
		$this->insertRecord1();
		$affected = self::$gateway->deleteAll('username = ?', 'no_such_user_xyz');
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// deleteByPk()
	// -----------------------------------------------------------------------

	public function test_delete_by_pk_removes_row(): void
	{
		$this->insertRecord1();
		self::$gateway->deleteByPk(['tgw_user1']);
		$this->assertFalse(self::$gateway->findByPk('tgw_user1'));
	}

	public function test_delete_by_pk_returns_one_for_existing_row(): void
	{
		$this->insertRecord1();
		$affected = self::$gateway->deleteByPk(['tgw_user1']);
		$this->assertSame(1, (int) $affected);
	}

	public function test_delete_by_pk_returns_zero_for_missing_pk(): void
	{
		$affected = self::$gateway->deleteByPk(['no_such_user_xyz']);
		$this->assertSame(0, (int) $affected);
	}

	// -----------------------------------------------------------------------
	// TSqlCriteria — ordering, limiting, conditions
	// -----------------------------------------------------------------------

	public function test_find_all_with_criteria_order_by(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria("username LIKE 'tgw\\_%%' ESCAPE '\\'");
		$criteria->OrdersBy = ['username' => 'asc'];
		$rows = self::$gateway->findAll($criteria)->readAll();
		$u0 = $rows[0]['username'] ?? $rows[0]['USERNAME'] ?? null;
		$u1 = $rows[1]['username'] ?? $rows[1]['USERNAME'] ?? null;
		$this->assertSame('tgw_user1', $u0);
		$this->assertSame('tgw_user2', $u1);
	}

	public function test_find_all_with_criteria_limit(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria("username LIKE 'tgw\\_%%' ESCAPE '\\'");
		$criteria->Limit = 1;
		$rows = self::$gateway->findAll($criteria)->readAll();
		$this->assertCount(1, $rows);
	}

	public function test_count_with_criteria(): void
	{
		$this->insertRecord1();
		$this->insertRecord2();
		$criteria = new TSqlCriteria("username = 'tgw_user2'");
		$count = (int) self::$gateway->count($criteria);
		$this->assertSame(1, $count);
	}
}
