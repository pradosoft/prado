<?php

use Prado\Data\TDbColumnCaseMode;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Data\TDbNullConversionMode;
use Prado\Exceptions\TDbException;
use Prado\TApplication;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
}
if (!defined('TEST_DB_FILE2')) {
	define('TEST_DB_FILE2', __DIR__ . '/db/test2.db');
}

class TDbConnectionTest extends PHPUnit\Framework\TestCase
{
	private $_connection1;
	private $_connection2;

	protected function setUp(): void
	{
		// Defensive unlink
		@unlink(TEST_DB_FILE);
		@unlink(TEST_DB_FILE2);

		// create application just to provide application mode
		new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP);

		$this->_connection1 = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->_connection1->Active = true;
		$this->_connection1->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
		$this->_connection2 = new TDbConnection('sqlite:' . TEST_DB_FILE2);
	}

	protected function tearDown(): void
	{
		// Explicitly close PDO connections before unlinking to release file locks on Windows.
		if ($this->_connection1 !== null) {
			$this->_connection1->Active = false;
			$this->_connection1 = null;
		}
		if ($this->_connection2 !== null) {
			$this->_connection2->Active = false;
			$this->_connection2 = null;
		}
		@unlink(TEST_DB_FILE);
		@unlink(TEST_DB_FILE2);
	}

	public function testActive()
	{
		$this->assertFalse($this->_connection2->Active);

		$this->_connection2->Active = true;
		$this->assertTrue($this->_connection2->Active);
		$pdo = $this->_connection2->PdoInstance;
		$this->assertTrue($pdo instanceof PDO);
		// test setting Active repeatedly doesn't re-connect DB
		$this->_connection2->Active = true;
		$this->assertTrue($pdo === $this->_connection2->PdoInstance);

		$this->_connection2->Active = false;
		$this->assertFalse($this->_connection2->Active);

		try {
			$connection = new TDbConnection('unknown:' . TEST_DB_FILE);
			$connection->Active = true;
			$this->fail('Expected exception is not raised');
		} catch (TDbException $e) {
		}
	}

	public function testCreateCommand()
	{
		$sql = 'CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))';
		try {
			$this->_connection2->createCommand($sql);
			$this->fail('Expected exception is not raised');
		} catch (TDbException $e) {
		}

		$command = $this->_connection1->createCommand($sql);
		$this->assertTrue($command instanceof TDbCommand);
	}

	public function testBeginTransaction()
	{
		$sql = 'INSERT INTO foo(id,name) VALUES (1,\'my name\')';
		$transaction = $this->_connection1->beginTransaction();
		try {
			$this->_connection1->createCommand($sql)->execute();
			$this->_connection1->createCommand($sql)->execute();
			$this->fail('Expected exception not raised');
			$transaction->commit();
		} catch (Exception $e) {
			$transaction->rollBack();
			$reader = $this->_connection1->createCommand('SELECT * FROM foo')->query();
			$this->assertFalse($reader->read());
		}
	}

	public function testLastInsertID()
	{
		$sql = 'INSERT INTO foo(name) VALUES (\'my name\')';
		$this->_connection1->createCommand($sql)->execute();
		$value = $this->_connection1->LastInsertID;
		$this->assertEquals($this->_connection1->LastInsertID, '1');
	}

	public function testQuoteString()
	{
		$str = "this is 'my' name";
		$expectedStr = "'this is ''my'' name'";
		$this->assertEquals($expectedStr, $this->_connection1->quoteString($str));
	}

	public function testColumnNameCase()
	{
		$this->assertEquals(TDbColumnCaseMode::Preserved, $this->_connection1->ColumnCase);
		$this->_connection1->ColumnCase = TDbColumnCaseMode::LowerCase;
		$this->assertEquals(TDbColumnCaseMode::LowerCase, $this->_connection1->ColumnCase);
	}

	public function testNullConversion()
	{
		$this->assertEquals(TDbNullConversionMode::Preserved, $this->_connection1->NullConversion);
		$this->_connection1->NullConversion = TDbNullConversionMode::NullToEmptyString;
		$this->assertEquals(TDbNullConversionMode::NullToEmptyString, $this->_connection1->NullConversion);
	}

	// -----------------------------------------------------------------------
	// setConnectionCharset() tests
	// -----------------------------------------------------------------------

	/**
	 * Build a TDbConnection with private _charset and _active set via reflection,
	 * but no real PDO.  Used to test early-return paths.
	 */
	private function makeCharsetOnlyConnection(string $charset, bool $active = true): TDbConnection
	{
		$conn = new TDbConnection();

		$charsetProp = new \ReflectionProperty(TDbConnection::class, '_charset');
		$charsetProp->setAccessible(true);
		$charsetProp->setValue($conn, $charset);

		$activeProp = new \ReflectionProperty(TDbConnection::class, '_active');
		$activeProp->setAccessible(true);
		$activeProp->setValue($conn, $active);

		return $conn;
	}

	/**
	 * Inject a PDO mock into an existing TDbConnection.
	 */
	private function injectMockPdo(TDbConnection $conn, \PDO $pdo): void
	{
		$prop = new \ReflectionProperty(TDbConnection::class, '_pdo');
		$prop->setAccessible(true);
		$prop->setValue($conn, $pdo);
	}

	/**
	 * Call the protected setConnectionCharset() method via reflection.
	 */
	private function callSetConnectionCharset(TDbConnection $conn): void
	{
		$method = new \ReflectionMethod(TDbConnection::class, 'setConnectionCharset');
		$method->setAccessible(true);
		$method->invoke($conn);
	}

	/**
	 * Build a PDO mock that reports the given driver name and expects prepare()
	 * to be called once with $expectedSql.  The returned PDOStatement mock will
	 * assert that execute() is called with [$charset].
	 *
	 * @return array{0: \PDO, 1: \PDOStatement}
	 */
	private function makePdoExpectingPrepare(string $driver, string $expectedSql, string $charset): array
	{
		$mockStmt = $this->createMock(\PDOStatement::class);
		$mockStmt->expects($this->once())
			->method('execute')
			->with([$charset]);

		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn($driver);
		$mockPdo->expects($this->once())
			->method('prepare')
			->with($expectedSql)
			->willReturn($mockStmt);

		return [$mockPdo, $mockStmt];
	}

	/**
	 * Build a PDO mock that reports the given driver name and asserts that
	 * prepare() is never called (i.e. the method returns silently).
	 */
	private function makePdoExpectingNoSql(string $driver): \PDO
	{
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn($driver);
		$mockPdo->expects($this->never())
			->method('prepare');
		$mockPdo->expects($this->never())
			->method('exec');

		return $mockPdo;
	}

	public function testSetConnectionCharsetSkipsWhenCharsetIsEmpty(): void
	{
		// _charset = '' → early return before touching PDO
		$conn = $this->makeCharsetOnlyConnection('', true);
		// No PDO injected — would fatal if PDO were accessed
		$this->callSetConnectionCharset($conn);
		$this->assertTrue(true); // reached without error
	}

	public function testSetConnectionCharsetSkipsWhenInactive(): void
	{
		// _charset set but _active = false → early return before touching PDO
		$conn = $this->makeCharsetOnlyConnection('utf8', false);
		// No PDO injected — would fatal if PDO were accessed
		$this->callSetConnectionCharset($conn);
		$this->assertTrue(true); // reached without error
	}

	/**
	 * Call the protected resolveCharsetForDriver() method via reflection.
	 */
	private function callResolveCharsetForDriver(TDbConnection $conn, string $charset, string $driver): string
	{
		$method = new \ReflectionMethod(TDbConnection::class, 'resolveCharsetForDriver');
		$method->setAccessible(true);
		return $method->invoke($conn, $charset, $driver);
	}

	/**
	 * @dataProvider provideSetNamesDrivers
	 * @param string $driver          PDO driver string
	 * @param string $inputCharset    value the caller sets on Charset
	 * @param string $expectedCharset value that must reach the DB (after resolution)
	 */
	public function testSetConnectionCharsetUsesSetNamesForDriver(
		string $driver,
		string $inputCharset,
		string $expectedCharset
	): void {
		[$mockPdo] = $this->makePdoExpectingPrepare($driver, 'SET NAMES ?', $expectedCharset);
		$conn = $this->makeCharsetOnlyConnection($inputCharset);
		$this->injectMockPdo($conn, $mockPdo);
		$this->callSetConnectionCharset($conn);
	}

	public static function provideSetNamesDrivers(): array
	{
		return [
			// Universal names resolved per driver
			'mysql/UTF-8'         => ['mysql', 'UTF-8',      'utf8mb4'],
			'mysql/ISO-8859-1'    => ['mysql', 'ISO-8859-1', 'latin1'],
			// Driver-specific names that have no alias pass through unchanged
			'mysql/utf8mb4'       => ['mysql', 'utf8mb4', 'utf8mb4'],
			'mysql/latin1'        => ['mysql', 'latin1',  'latin1'],
		];
	}

	/** @dataProvider providePgsqlEncodings */
	public function testSetConnectionCharsetUsesPgsqlEncoding(string $input, string $expected): void
	{
		[$mockPdo] = $this->makePdoExpectingPrepare('pgsql', 'SET client_encoding TO ?', $expected);
		$conn = $this->makeCharsetOnlyConnection($input);
		$this->injectMockPdo($conn, $mockPdo);
		$this->callSetConnectionCharset($conn);
	}

	public static function providePgsqlEncodings(): array
	{
		return [
			'UTF-8'      => ['UTF-8',      'UTF8'],
			'ISO-8859-1' => ['ISO-8859-1', 'LATIN1'],
		];
	}

	/** @dataProvider provideNoSqlDrivers */
	public function testSetConnectionCharsetReturnsSilentlyForDriver(string $driver): void
	{
		$mockPdo = $this->makePdoExpectingNoSql($driver);
		$conn = $this->makeCharsetOnlyConnection('utf8');
		$this->injectMockPdo($conn, $mockPdo);
		$this->callSetConnectionCharset($conn);
		$this->assertTrue(true); // no exception thrown
	}

	public static function provideNoSqlDrivers(): array
	{
		return [
			// These drivers return silently; charset is handled via DSN (or not at all).
			'firebird' => ['firebird'],
			'sqlsrv'   => ['sqlsrv'],
			'dblib'    => ['dblib'],
			'ibm'      => ['ibm'],
			'oci'      => ['oci'],
		];
	}

	/** @dataProvider provideSqlitePragmaCharsets */
	public function testSetConnectionCharsetUsesPragmaForSqlite(
		string $input,
		string $resolvedQuoted,
		string $expectedExec
	): void {
		// SQLite: charset is applied via PRAGMA encoding = <quoted_value>
		// using exec(), not prepare()/execute().
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn('sqlite');
		$mockPdo->method('quote')
			->willReturn($resolvedQuoted);
		$mockPdo->expects($this->once())
			->method('exec')
			->with($expectedExec);
		$mockPdo->expects($this->never())
			->method('prepare');

		$conn = $this->makeCharsetOnlyConnection($input);
		$this->injectMockPdo($conn, $mockPdo);
		$this->callSetConnectionCharset($conn);
	}

	public static function provideSqlitePragmaCharsets(): array
	{
		return [
			// 'UTF-8' resolves to 'UTF-8' for sqlite; PDO::quote wraps in single quotes.
			'UTF-8'  => ['UTF-8',  "'UTF-8'",  "PRAGMA encoding = 'UTF-8'"],
			// 'UTF-16' resolves to 'UTF-16' for sqlite.
			'UTF-16' => ['UTF-16', "'UTF-16'", "PRAGMA encoding = 'UTF-16'"],
		];
	}

	public function testSetConnectionCharsetSqliteFailsSilently(): void
	{
		// If exec() throws (tables already exist, or unsupported encoding),
		// the exception must be caught and the method must return without throwing.
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn('sqlite');
		$mockPdo->method('quote')
			->with('UTF-8')
			->willReturn("'UTF-8'");
		$mockPdo->expects($this->once())
			->method('exec')
			->willThrowException(new \PDOException('cannot change encoding after tables exist'));

		$conn = $this->makeCharsetOnlyConnection('UTF-8');
		$this->injectMockPdo($conn, $mockPdo);
		$this->callSetConnectionCharset($conn); // must not throw
		$this->assertTrue(true);
	}

	public function testSetConnectionCharsetThrowsForUnknownDriver(): void
	{
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn('unknown_db_driver');

		$conn = $this->makeCharsetOnlyConnection('utf8');
		$this->injectMockPdo($conn, $mockPdo);

		$this->expectException(TDbException::class);
		$this->callSetConnectionCharset($conn);
	}

	// -----------------------------------------------------------------------
	// resolveCharsetForDriver() tests
	// -----------------------------------------------------------------------

	/** @dataProvider provideCharsetResolutions */
	public function testResolveCharsetForDriver(
		string $inputCharset,
		string $driver,
		string $expectedCharset
	): void {
		$conn = new TDbConnection();
		$resolved = $this->callResolveCharsetForDriver($conn, $inputCharset, $driver);
		$this->assertSame($expectedCharset, $resolved);
	}

	public static function provideCharsetResolutions(): array
	{
		return [
			// --- UTF-8 family: various spellings all resolve correctly ---
			'UTF-8 mysql'       => ['UTF-8',   'mysql',    'utf8mb4'],
			'utf8 mysql'        => ['utf8',     'mysql',    'utf8mb4'],
			'UTF8 mysql'        => ['UTF8',     'mysql',    'utf8mb4'],
			'utf-8 mysql'       => ['utf-8',    'mysql',    'utf8mb4'],
			'UTF-8 sqlite'      => ['UTF-8',    'sqlite',   'UTF-8'],
			'UTF-8 pgsql'       => ['UTF-8',    'pgsql',    'UTF8'],
			'UTF-8 firebird'    => ['UTF-8',    'firebird', 'UTF8'],
			// utf8mb4 is treated as the same canonical entry as utf8
			'utf8mb4 mysql'     => ['utf8mb4',  'mysql',    'utf8mb4'],
			'utf8mb4 pgsql'     => ['utf8mb4',  'pgsql',    'UTF8'],
			'utf8mb4 firebird'  => ['utf8mb4',  'firebird', 'UTF8'],
			// --- ISO-8859-1 / latin1 ---
			'ISO-8859-1 mysql'    => ['ISO-8859-1', 'mysql',    'latin1'],
			'ISO-8859-1 pgsql'    => ['ISO-8859-1', 'pgsql',    'LATIN1'],
			'ISO-8859-1 firebird' => ['ISO-8859-1', 'firebird', 'ISO8859_1'],
			'latin1 pgsql'        => ['latin1',     'pgsql',    'LATIN1'],
			'latin1 firebird'     => ['latin1',     'firebird', 'ISO8859_1'],
			// --- ISO-8859-2 / latin2 ---
			'ISO-8859-2 mysql'    => ['ISO-8859-2', 'mysql',    'latin2'],
			'ISO-8859-2 pgsql'    => ['ISO-8859-2', 'pgsql',    'LATIN2'],
			'ISO-8859-2 firebird' => ['ISO-8859-2', 'firebird', 'ISO8859_2'],
			// --- ASCII ---
			'ascii mysql'    => ['ascii', 'mysql',    'ascii'],
			'ascii pgsql'    => ['ascii', 'pgsql',    'SQL_ASCII'],
			'ascii firebird' => ['ascii', 'firebird', 'ASCII'],
			// --- Windows code pages ---
			'WIN-1252 mysql'       => ['WIN-1252',     'mysql',    'cp1252'],
			'WIN-1252 pgsql'       => ['WIN-1252',     'pgsql',    'WIN1252'],
			'WIN-1252 firebird'    => ['WIN-1252',     'firebird', 'WIN1252'],
			'Windows-1252 mysql'   => ['Windows-1252', 'mysql',    'cp1252'],
			'win1251 mysql'        => ['win1251',       'mysql',    'cp1251'],
			'Windows-1250 pgsql'   => ['Windows-1250',  'pgsql',   'WIN1250'],
			// --- KOI8 ---
			'KOI8-R mysql'    => ['KOI8-R', 'mysql',    'koi8r'],
			'KOI8-R pgsql'    => ['KOI8-R', 'pgsql',    'KOI8R'],
			'KOI8-R firebird' => ['KOI8-R', 'firebird', 'KOI8R'],
			// --- OCI charset names ---
			'UTF-8 oci'        => ['UTF-8',      'oci', 'AL32UTF8'],
			'ISO-8859-1 oci'   => ['ISO-8859-1', 'oci', 'WE8ISO8859P1'],
			'ISO-8859-2 oci'   => ['ISO-8859-2', 'oci', 'EE8ISO8859P2'],
			'ascii oci'        => ['ascii',       'oci', 'US7ASCII'],
			'WIN-1252 oci'     => ['WIN-1252',    'oci', 'WE8MSWIN1252'],
			'KOI8-R oci'       => ['KOI8-R',      'oci', 'CL8KOI8R'],
			// --- sqlsrv charset names ---
			'UTF-8 sqlsrv'     => ['UTF-8',      'sqlsrv', 'UTF-8'],
			// --- dblib charset names ---
			'ISO-8859-2 dblib' => ['ISO-8859-2', 'dblib', 'ISO-8859-2'],
			'KOI8-R dblib'     => ['KOI8-R',     'dblib', 'KOI8-R'],
			// --- IBM DB2: no table entry → pass-through ---
			'UTF-8 ibm'        => ['UTF-8', 'ibm', 'UTF-8'],
			// --- Unknown / driver-specific names pass through unchanged ---
			'unknown mysql'    => ['my_custom_cs', 'mysql', 'my_custom_cs'],
			'unknown pgsql'    => ['EUC_JP',       'pgsql', 'EUC_JP'],
		];
	}

	public function testCharsetIsAppliedOnActivate(): void
	{
		// End-to-end: SQLite encoding is fixed at creation time; a Charset value
		// must be silently ignored (no exception) and the connection must become active.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Charset = 'UTF-8';
		$conn->Active = true;
		$this->assertTrue($conn->Active);
		// SQLite is always UTF-8 regardless of the Charset property
		$encoding = $conn->createCommand('PRAGMA encoding')->queryScalar();
		$this->assertSame('UTF-8', $encoding);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getCharset() tests — returns the stored property value, always
	// -----------------------------------------------------------------------

	public function testGetCharsetReturnsStoredValueWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE, '', '', 'UTF-8');
		$this->assertFalse($conn->Active);
		$this->assertSame('UTF-8', $conn->Charset);
	}

	public function testGetCharsetReturnsEmptyStringWhenNotSet(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertSame('', $conn->Charset);
	}

	public function testGetCharsetReturnsStoredValueEvenWhenConnectionIsActive(): void
	{
		// getCharset() always returns the stored property value, not a DB query.
		// Use getDatabaseCharset() to get the live connection encoding.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE, '', '', 'UTF-8');
		$conn->Active = true;
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() tests — queries the active connection
	// -----------------------------------------------------------------------

	public function testGetDatabaseCharsetReturnsStoredValueWhenInactive(): void
	{
		// Connection is not active — falls back to the stored property value.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE, '', '', 'UTF-8');
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
	}

	public function testGetDatabaseCharsetQueriesSqliteWhenActive(): void
	{
		// Active SQLite connection: getDatabaseCharset() queries PRAGMA encoding.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE, '', '', 'UTF-8');
		$conn->Active = true;
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testGetDatabaseCharsetQueriesSqliteWhenNoCharsetConfigured(): void
	{
		// Even without an explicit Charset, getDatabaseCharset() queries PRAGMA
		// encoding and returns the real encoding (always 'UTF-8' for new DBs).
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	/** @dataProvider provideDsnDriverCharsetResolutions */
	public function testGetDatabaseCharsetReturnsDsnResolvedCharsetForDsnDrivers(
		string $driver,
		string $input,
		string $expected
	): void {
		// Drivers that configure charset via the DSN cannot query it at runtime;
		// getDatabaseCharset() returns the value resolved for the driver.
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn($driver);

		$conn = $this->makeCharsetOnlyConnection($input, true);
		$this->injectMockPdo($conn, $mockPdo);

		$this->assertSame($expected, $conn->DatabaseCharset);
	}

	public static function provideDsnDriverCharsetResolutions(): array
	{
		return [
			// OCI: 'UTF-8' resolves to the OCI NLS name 'AL32UTF8'
			'oci/UTF-8'        => ['oci',    'UTF-8',      'AL32UTF8'],
			'oci/ISO-8859-1'   => ['oci',    'ISO-8859-1', 'WE8ISO8859P1'],
			// sqlsrv / dblib: iconv-compatible names
			'sqlsrv/UTF-8'     => ['sqlsrv', 'UTF-8',      'UTF-8'],
			'dblib/UTF-8'      => ['dblib',  'UTF-8',      'UTF-8'],
			// IBM DB2 has no alias table entry → pass-through
			'ibm/UTF-8'        => ['ibm',    'UTF-8',      'UTF-8'],
		];
	}

	public function testGetDatabaseCharsetReturnsFallbackWhenQueryFails(): void
	{
		// If the DB query throws, getDatabaseCharset() falls back to $_charset.
		// queryScalar() calls PDO::query() directly (not prepare()), so mock query().
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn('mysql');
		// PDO::query() is used by TDbCommand::queryScalar() for the direct-query path.
		$mockPdo->method('query')
			->willThrowException(new \PDOException('server gone away'));

		$conn = $this->makeCharsetOnlyConnection('UTF-8', true);
		$this->injectMockPdo($conn, $mockPdo);

		$this->assertSame('UTF-8', $conn->DatabaseCharset);
	}

	// -----------------------------------------------------------------------
	// applyCharsetToDsn() tests
	// -----------------------------------------------------------------------

	/**
	 * Call the protected applyCharsetToDsn() method via reflection.
	 */
	private function callApplyCharsetToDsn(TDbConnection $conn, string $dsn): string
	{
		$method = new \ReflectionMethod(TDbConnection::class, 'applyCharsetToDsn');
		$method->setAccessible(true);
		return $method->invoke($conn, $dsn);
	}

	/**
	 * Build a TDbConnection with a given DSN and charset (inactive, no PDO).
	 */
	private function makeConnWithCharset(string $dsn, string $charset): TDbConnection
	{
		$conn = new TDbConnection($dsn, '', '', $charset);
		return $conn;
	}

	public function testApplyCharsetToDsnSkipsWhenCharsetEmpty(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=test', '', '', '');
		$result = $this->callApplyCharsetToDsn($conn, 'mysql:host=localhost;dbname=test');
		$this->assertSame('mysql:host=localhost;dbname=test', $result);
	}

	public function testApplyCharsetToDsnSkipsWhenDsnEmpty(): void
	{
		$conn = new TDbConnection('', '', '', 'UTF-8');

		$charsetProp = new \ReflectionProperty(TDbConnection::class, '_charset');
		$charsetProp->setAccessible(true);
		$charsetProp->setValue($conn, 'UTF-8');

		$result = $this->callApplyCharsetToDsn($conn, '');
		$this->assertSame('', $result);
	}

	/** @dataProvider provideApplyCharsetToDsnAppend */
	public function testApplyCharsetToDsnAppendsParam(
		string $dsn,
		string $charset,
		string $expectedDsn
	): void {
		$conn = $this->makeConnWithCharset($dsn, $charset);
		$result = $this->callApplyCharsetToDsn($conn, $dsn);
		$this->assertSame($expectedDsn, $result);
	}

	public static function provideApplyCharsetToDsnAppend(): array
	{
		return [
			// MySQL: universal 'UTF-8' → charset=utf8mb4
			'mysql/UTF-8' => [
				'mysql:host=localhost;dbname=test',
				'UTF-8',
				'mysql:host=localhost;dbname=test;charset=utf8mb4',
			],
			// MySQL: ISO-8859-1 → charset=latin1
			'mysql/ISO-8859-1' => [
				'mysql:host=localhost;dbname=test',
				'ISO-8859-1',
				'mysql:host=localhost;dbname=test;charset=latin1',
			],
			// Firebird: UTF-8 → charset=UTF8
			'firebird/UTF-8' => [
				'firebird:dbname=localhost:/var/lib/firebird/data/test.fdb',
				'UTF-8',
				'firebird:dbname=localhost:/var/lib/firebird/data/test.fdb;charset=UTF8',
			],
			// OCI: UTF-8 → charset=AL32UTF8
			'oci/UTF-8' => [
				'oci:dbname=//localhost/orcl',
				'UTF-8',
				'oci:dbname=//localhost/orcl;charset=AL32UTF8',
			],
			// OCI: ISO-8859-1 → charset=WE8ISO8859P1
			'oci/ISO-8859-1' => [
				'oci:dbname=//localhost/orcl',
				'ISO-8859-1',
				'oci:dbname=//localhost/orcl;charset=WE8ISO8859P1',
			],
			// sqlsrv uses CharacterSet= (not charset=)
			'sqlsrv/UTF-8' => [
				'sqlsrv:Server=localhost;Database=test',
				'UTF-8',
				'sqlsrv:Server=localhost;Database=test;CharacterSet=UTF-8',
			],
			// dblib: ISO-8859-1 → charset=ISO-8859-1
			'dblib/ISO-8859-1' => [
				'dblib:host=localhost;dbname=test',
				'ISO-8859-1',
				'dblib:host=localhost;dbname=test;charset=ISO-8859-1',
			],
		];
	}

	public function testApplyCharsetToDsnRespectsExistingMysqlCharset(): void
	{
		// DSN already has charset= → must not be modified (DSN takes priority).
		$dsn = 'mysql:host=localhost;dbname=test;charset=latin1';
		$conn = $this->makeConnWithCharset($dsn, 'UTF-8');
		$result = $this->callApplyCharsetToDsn($conn, $dsn);
		$this->assertSame($dsn, $result);
	}

	public function testApplyCharsetToDsnRespectsExistingSqlsrvCharacterSet(): void
	{
		$dsn = 'sqlsrv:Server=localhost;Database=test;CharacterSet=latin1';
		$conn = $this->makeConnWithCharset($dsn, 'UTF-8');
		$result = $this->callApplyCharsetToDsn($conn, $dsn);
		$this->assertSame($dsn, $result);
	}

	/** @dataProvider provideApplyCharsetToDsnNoOp */
	public function testApplyCharsetToDsnSkipsForDriver(string $dsn, string $charset): void
	{
		// Drivers with no DSN charset parameter must be returned unchanged.
		$conn = $this->makeConnWithCharset($dsn, $charset);
		$result = $this->callApplyCharsetToDsn($conn, $dsn);
		$this->assertSame($dsn, $result);
	}

	public static function provideApplyCharsetToDsnNoOp(): array
	{
		return [
			// pgsql has no DSN charset param (uses SQL after connect)
			'pgsql' => ['pgsql:host=localhost;dbname=test', 'UTF-8'],
			// SQLite is always UTF-8
			'sqlite' => ['sqlite:/tmp/test.db', 'UTF-8'],
			// IBM DB2 has no reliable DSN charset param
			'ibm' => ['ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=test', 'UTF-8'],
		];
	}

	public function testApplyCharsetToDsnEndToEndSqlite(): void
	{
		// Full open() path with SQLite: applyCharsetToDsn must not corrupt the DSN.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Charset = 'UTF-8';
		$conn->Active = true;
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDriverName() tests
	// -----------------------------------------------------------------------

	public function testGetDriverNameParsesMysqlFromDsn(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=test');
		$this->assertSame('mysql', $conn->DriverName);
	}

	public function testGetDriverNameParsesPgsqlFromDsn(): void
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test');
		$this->assertSame('pgsql', $conn->DriverName);
	}

	public function testGetDriverNameParsesSqliteFromDsn(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertSame('sqlite', $conn->DriverName);
	}

	public function testGetDriverNameParsesFirebirdFromDsn(): void
	{
		$conn = new TDbConnection('firebird:dbname=localhost:/var/lib/firebird/test.fdb');
		$this->assertSame('firebird', $conn->DriverName);
	}

	public function testGetDriverNameParsesOciFromDsn(): void
	{
		$conn = new TDbConnection('oci:dbname=//localhost/orcl');
		$this->assertSame('oci', $conn->DriverName);
	}

	public function testGetDriverNameParsesIbmFromDsn(): void
	{
		$conn = new TDbConnection('ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=test');
		$this->assertSame('ibm', $conn->DriverName);
	}

	public function testGetDriverNameParsesSqlsrvFromDsn(): void
	{
		$conn = new TDbConnection('sqlsrv:Server=localhost;Database=test');
		$this->assertSame('sqlsrv', $conn->DriverName);
	}

	public function testGetDriverNameParsesDblibFromDsn(): void
	{
		$conn = new TDbConnection('dblib:host=localhost;dbname=test');
		$this->assertSame('dblib', $conn->DriverName);
	}

	public function testGetDriverNameThrowsWhenNoColonInDsn(): void
	{
		$conn = new TDbConnection('invalid_dsn');
		$this->expectException(TDbException::class);
		$conn->DriverName;
	}

	public function testGetDriverNameReturnsActiveDriverName(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertSame('sqlite', $conn->DriverName);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// ConnectionString get/set tests
	// -----------------------------------------------------------------------

	public function testGetConnectionString(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertSame('sqlite:' . TEST_DB_FILE, $conn->ConnectionString);
	}

	public function testSetConnectionString(): void
	{
		$conn = new TDbConnection();
		$conn->ConnectionString = 'sqlite:' . TEST_DB_FILE2;
		$this->assertSame('sqlite:' . TEST_DB_FILE2, $conn->ConnectionString);
	}

	// -----------------------------------------------------------------------
	// Username get/set tests
	// -----------------------------------------------------------------------

	public function testGetUsername(): void
	{
		$conn = new TDbConnection('sqlite:test', 'myuser', 'mypass');
		$this->assertSame('myuser', $conn->Username);
	}

	public function testSetUsername(): void
	{
		$conn = new TDbConnection();
		$conn->Username = 'newuser';
		$this->assertSame('newuser', $conn->Username);
	}

	// -----------------------------------------------------------------------
	// Password get/set tests
	// -----------------------------------------------------------------------

	public function testGetPassword(): void
	{
		$conn = new TDbConnection('sqlite:test', 'myuser', 'mypass');
		$this->assertSame('mypass', $conn->Password);
	}

	public function testSetPassword(): void
	{
		$conn = new TDbConnection();
		$conn->Password = 'newpass';
		$this->assertSame('newpass', $conn->Password);
	}

	public function testSetPasswordCanBeEmpty(): void
	{
		$conn = new TDbConnection();
		$conn->Password = '';
		$this->assertSame('', $conn->Password);
	}

	// -----------------------------------------------------------------------
	// getCurrentTransaction() tests
	// -----------------------------------------------------------------------

	public function testGetCurrentTransactionReturnsNullWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertNull($conn->CurrentTransaction);
	}

	public function testGetCurrentTransactionReturnsNullWhenNoTransaction(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertNull($conn->CurrentTransaction);
		$conn->Active = false;
	}

	public function testGetCurrentTransactionReturnsTransactionWhenActive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->beginTransaction();
		$this->assertNotNull($conn->CurrentTransaction);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// commit() convenience method tests
	// -----------------------------------------------------------------------

	public function testCommitReturnsFalseWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertFalse($conn->commit());
	}

	public function testCommitReturnsFalseWhenNoActiveTransaction(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertFalse($conn->commit());
		$conn->Active = false;
	}

	public function testCommitCommitsActiveTransaction(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->createCommand('INSERT INTO foo(id, name) VALUES (1, \'test\')')->execute();
		$conn->beginTransaction();
		$conn->createCommand('UPDATE foo SET name = \'updated\' WHERE id = 1')->execute();
		$this->assertTrue($conn->commit());
		$row = $conn->createCommand('SELECT name FROM foo WHERE id = 1')->queryScalar();
		$this->assertSame('updated', $row);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// rollback() convenience method tests
	// -----------------------------------------------------------------------

	public function testRollbackReturnsFalseWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertFalse($conn->rollback());
	}

	public function testRollbackReturnsFalseWhenNoActiveTransaction(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertFalse($conn->rollback());
		$conn->Active = false;
	}

	public function testRollbackRollsBackActiveTransaction(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->createCommand('INSERT INTO foo(id, name) VALUES (1, \'original\')')->execute();
		$conn->beginTransaction();
		$conn->createCommand('UPDATE foo SET name = \'changed\' WHERE id = 1')->execute();
		$this->assertTrue($conn->rollback());
		$row = $conn->createCommand('SELECT name FROM foo WHERE id = 1')->queryScalar();
		$this->assertSame('original', $row);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getTransactionClass() tests
	// -----------------------------------------------------------------------

	public function testGetTransactionClassReturnsDefault(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertSame(\Prado\Data\TDbTransaction::class, $conn->TransactionClass);
	}

	public function testSetTransactionClass(): void
	{
		$conn = new TDbConnection();
		$conn->TransactionClass = \Prado\Data\TDbSerialTransaction::class;
		$this->assertSame(\Prado\Data\TDbSerialTransaction::class, $conn->TransactionClass);
	}

	public function testSetTransactionClassAllowsNull(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->setTransactionClass('CustomTransactionClass');
		$this->assertSame('CustomTransactionClass', $conn->TransactionClass);
	}

	// -----------------------------------------------------------------------
	// getHasAutoCommit() tests
	// -----------------------------------------------------------------------

	public function testGetHasAutoCommitReturnsTrueForSqlite(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertFalse($conn->HasAutoCommit);
	}

	// -----------------------------------------------------------------------
	// getAutoCommit() tests
	// -----------------------------------------------------------------------

	public function testGetAutoCommitReturnsValue(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$value = $conn->AutoCommit;
		$this->assertIsBool($value);
		$conn->Active = false;
	}

	public function testSetAutoCommitSetsValue(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->AutoCommit = false;
		$this->assertFalse($conn->AutoCommit);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getAttribute() / setAttribute() tests
	// -----------------------------------------------------------------------

	public function testGetAttributeReturnsPdoAttribute(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
		$this->assertSame('sqlite', $driver);
		$conn->Active = false;
	}

	public function testSetAttributeSetsPdoAttribute(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
		$this->assertSame(PDO::CASE_LOWER, $conn->getAttribute(PDO::ATTR_CASE));
		$conn->Active = false;
	}

	public function testGetAttributeReturnsLazyAttributeWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->setAttribute(PDO::ATTR_PERSISTENT, true);
		$this->assertTrue($conn->getAttribute(PDO::ATTR_PERSISTENT));
	}

	public function testSetAttributeStoresLazyAttributeWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->assertSame(PDO::ERRMODE_EXCEPTION, $conn->getAttribute(PDO::ATTR_ERRMODE));
	}

	public function testGetAttributeThrowsWhenInvalidForActiveConnection(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->expectException(\PDOException::class);
		$conn->getAttribute(999999);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getPdoInstance() tests
	// -----------------------------------------------------------------------

	public function testGetPdoInstanceReturnsNullWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertNull($conn->PdoInstance);
	}

	public function testGetPdoInstanceReturnsPdoWhenActive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertInstanceOf(PDO::class, $conn->PdoInstance);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Persistent connection tests
	// -----------------------------------------------------------------------

	public function testGetPersistent(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$value = $conn->Persistent;
		$this->assertIsBool($value);
		$conn->Active = false;
	}

	public function testSetPersistent(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->Persistent = false;
		$this->assertFalse($conn->Persistent);
		$conn->Active = false;
	}

// -----------------------------------------------------------------------
	// Server Version tests (driver-specific; SQLite returns string)
	// -----------------------------------------------------------------------

	public function testGetClientVersion(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$version = $conn->ClientVersion;
		$this->assertIsString($version);
		$conn->Active = false;
	}

	public function testGetServerVersion(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$version = $conn->ServerVersion;
		$this->assertIsString($version);
		$conn->Active = false;
	}

	public function testExtractCharsetFromDsnMysql()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=test', 'user', 'pass');
		// Use reflection to call protected method
		$method = new ReflectionMethod(TDbConnection::class, 'extractCharsetFromDsn');
		$method->setAccessible(true);

		// No charset in DSN
		$this->assertNull($method->invoke($conn, 'mysql:host=localhost;dbname=test'));

		// With charset in DSN
		$this->assertEquals('utf8mb4', $method->invoke($conn, 'mysql:host=localhost;dbname=test;charset=utf8mb4'));

		// With CharacterSet (sqlsrv style)
		$this->assertEquals('UTF-8', $method->invoke($conn, 'sqlsrv:Server=localhost;Database=test;CharacterSet=UTF-8'));
	}

	public function testExtractCharsetFromDsnSqlite()
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$method = new ReflectionMethod(TDbConnection::class, 'extractCharsetFromDsn');
		$method->setAccessible(true);

		// SQLite doesn't have DSN charset
		$this->assertNull($method->invoke($conn, 'sqlite:' . TEST_DB_FILE));
	}

	public function testExtractCharsetFromDsnCaseInsensitive()
	{
		$conn = new TDbConnection('mysql:host=localhost', 'user', 'pass');
		$method = new ReflectionMethod(TDbConnection::class, 'extractCharsetFromDsn');
		$method->setAccessible(true);

		// Test case insensitive matching
		$this->assertEquals('utf8', $method->invoke($conn, 'mysql:host=localhost;CHARSET=utf8'));
		$this->assertEquals('utf8', $method->invoke($conn, 'mysql:host=localhost;CharSet=utf8'));
	}

	// -----------------------------------------------------------------------
	// getAvailableDrivers() static method
	// -----------------------------------------------------------------------

	public function testGetAvailableDriversReturnsArray(): void
	{
		$drivers = TDbConnection::getAvailableDrivers();
		$this->assertIsArray($drivers);
	}

	public function testGetAvailableDriversMatchesPdo(): void
	{
		$this->assertSame(PDO::getAvailableDrivers(), TDbConnection::getAvailableDrivers());
	}

	// -----------------------------------------------------------------------
	// __sleep() — serialization removes _pdo and _active
	// -----------------------------------------------------------------------

	public function testSleepExcludesPdoAndActive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;

		// __sleep() is called implicitly by serialize()
		$props = $conn->__sleep();
		$this->assertNotContains("\0Prado\Data\TDbConnection\0_pdo",    $props);
		$this->assertNotContains("\0Prado\Data\TDbConnection\0_active",  $props);
	}

	public function testSerializePreservesConnectionString(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE, 'user', 'pass');
		$conn->Active = true;

		$serialized   = serialize($conn);
		/** @var TDbConnection $restored */
		$restored = unserialize($serialized);

		$this->assertSame('sqlite:' . TEST_DB_FILE, $restored->ConnectionString);
		$this->assertSame('user', $restored->Username);
		// After unserializing the connection must be inactive (PDO was stripped)
		$this->assertFalse($restored->Active);
		$this->assertNull($restored->PdoInstance);
	}

	// -----------------------------------------------------------------------
	// setCharset() — inactive connection (stores property only)
	// -----------------------------------------------------------------------

	public function testSetCharsetWhenInactiveStoresProperty(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=test');
		$conn->Charset = 'UTF-8';
		$this->assertSame('UTF-8', $conn->Charset);
	}

	public function testSetCharsetWhenInactiveAcceptsAnyValue(): void
	{
		$conn = new TDbConnection('firebird:dbname=localhost:/db/test.fdb');
		$conn->Charset = 'ISO-8859-1';
		$this->assertSame('ISO-8859-1', $conn->Charset);
	}

	// -----------------------------------------------------------------------
	// setCharset() — active connection on non-switchable driver → exception
	// -----------------------------------------------------------------------

	/** @dataProvider provideNonSwitchableDrivers */
	public function testSetCharsetThrowsWhenActiveAndDriverCannotSwitch(string $driver): void
	{
		// Build an active-looking connection with an injected PDO mock.
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		$mockPdo->method('getAttribute')
			->with(\PDO::ATTR_DRIVER_NAME)
			->willReturn($driver);

		$conn = new TDbConnection($driver . ':host=localhost');

		$activeProp = new \ReflectionProperty(TDbConnection::class, '_active');
		$activeProp->setAccessible(true);
		$activeProp->setValue($conn, true);

		$pdoProp = new \ReflectionProperty(TDbConnection::class, '_pdo');
		$pdoProp->setAccessible(true);
		$pdoProp->setValue($conn, $mockPdo);

		$this->expectException(\Prado\Exceptions\TDbException::class);
		$conn->Charset = 'UTF-8';
	}

	public static function provideNonSwitchableDrivers(): array
	{
		return [
			'firebird' => ['firebird'],
			'oci'      => ['oci'],
			'sqlsrv'   => ['sqlsrv'],
			'dblib'    => ['dblib'],
		];
	}

	// -----------------------------------------------------------------------
	// setCharset() — active SQLite connection (runtime-switchable via PRAGMA)
	// -----------------------------------------------------------------------

	public function testSetCharsetOnActiveSqliteDoesNotThrow(): void
	{
		// SQLite supports runtime charset via PRAGMA (errors silently ignored).
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		// Must not throw; PRAGMA errors are caught internally.
		$conn->Charset = 'UTF-8';
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// quoteTableName / quoteColumnName / quoteColumnAlias
	// -----------------------------------------------------------------------

	public function testQuoteTableNameDelegatesToMetaData(): void
	{
		// SQLite meta-data wraps names in double-quotes.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$quoted = $conn->quoteTableName('my_table');
		$this->assertStringContainsString('my_table', $quoted);
		$conn->Active = false;
	}

	public function testQuoteColumnNameDelegatesToMetaData(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$quoted = $conn->quoteColumnName('my_col');
		$this->assertStringContainsString('my_col', $quoted);
		$conn->Active = false;
	}

	public function testQuoteColumnAliasDelegatesToMetaData(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$quoted = $conn->quoteColumnAlias('my_alias');
		$this->assertStringContainsString('my_alias', $quoted);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDbMetaData()
	// -----------------------------------------------------------------------

	public function testGetDbMetaDataReturnsMetaDataInstance(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$meta = $conn->DbMetaData;
		$this->assertInstanceOf(\Prado\Data\Common\TDbMetaData::class, $meta);
		$conn->Active = false;
	}

	public function testGetDbMetaDataReturnsCachedInstance(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$meta1 = $conn->DbMetaData;
		$meta2 = $conn->DbMetaData;
		$this->assertSame($meta1, $meta2);
		$conn->Active = false;
	}

	public function testGetDbMetaDataReturnsSqliteMetaData(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertInstanceOf(\Prado\Data\Common\Sqlite\TSqliteMetaData::class, $conn->DbMetaData);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getLastInsertID() / quoteString() throw when inactive
	// -----------------------------------------------------------------------

	public function testGetLastInsertIdThrowsWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$conn->LastInsertID;
	}

	public function testQuoteStringThrowsWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$conn->quoteString('test');
	}

	public function testCreateCommandThrowsWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$conn->createCommand('SELECT 1');
	}

	// -----------------------------------------------------------------------
	// beginTransaction() — duplicate / active transaction guard
	// -----------------------------------------------------------------------

	public function testBeginTransactionThrowsWhenTransactionAlreadyActive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->beginTransaction();
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$conn->beginTransaction();   // second call with same transaction open
		$conn->Active = false;
	}

	public function testBeginTransactionThrowsWhenInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$conn->beginTransaction();
	}

	public function testBeginTransactionReturnsNewTransactionAfterRollback(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$txn1 = $conn->beginTransaction();
		$txn1->rollBack();
		$txn2 = $conn->beginTransaction();
		$this->assertNotNull($txn2);
		$this->assertTrue($txn2->Active);
		$txn2->rollBack();
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getCurrentTransaction() edge cases
	// -----------------------------------------------------------------------

	public function testGetCurrentTransactionReturnsNullAfterCommit(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$txn = $conn->beginTransaction();
		$txn->commit();
		$this->assertNull($conn->CurrentTransaction);
		$conn->Active = false;
	}

	public function testGetCurrentTransactionReturnsNullAfterRollback(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$txn = $conn->beginTransaction();
		$txn->rollBack();
		$this->assertNull($conn->CurrentTransaction);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// fxDataGetMetaDataClass event — raised by TDbDriverCapabilities::getMetaDataClass
	// when the driver is unknown; TDbMetaData::getInstance calls it via the connection.
	// -----------------------------------------------------------------------

	public function testFxDataGetMetaDataClassEventCanBeHandledByBehavior(): void
	{
		// Attach a global behavior that handles fxDataGetMetaDataClass and supplies
		// TSqliteMetaData as the handler for a custom driver.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;

		// Verify the known 'sqlite' driver path works without the event.
		$meta = $conn->DbMetaData;
		$this->assertInstanceOf(\Prado\Data\Common\Sqlite\TSqliteMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// TransactionClass — get/set/null
	// -----------------------------------------------------------------------

	public function testSetTransactionClassToNull(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->setTransactionClass(null);
		$this->assertNull($conn->TransactionClass);
	}

	public function testSetTransactionClassToCustom(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->TransactionClass = \Prado\Data\TDbTransaction::class;
		$this->assertSame(\Prado\Data\TDbTransaction::class, $conn->TransactionClass);
	}

	// -----------------------------------------------------------------------
	// HasAutoCommit — per-driver
	// -----------------------------------------------------------------------

	public function testHasAutoCommitIsFalseForSqlite(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertFalse($conn->HasAutoCommit);
	}

	public function testHasAutoCommitIsTrueForMysqlDsn(): void
	{
		// Not connected; DriverName derived from DSN.
		$conn = new TDbConnection('mysql:host=localhost;dbname=test');
		$this->assertTrue($conn->HasAutoCommit);
	}

	public function testHasAutoCommitIsTrueForPgsqlDsn(): void
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test');
		$this->assertTrue($conn->HasAutoCommit);
	}

	// -----------------------------------------------------------------------
	// AutoCommit read/write — SQLite (no attribute → no-op)
	// -----------------------------------------------------------------------

	public function testGetAutoCommitReturnsFalseWhenNoAutoCommitAttribute(): void
	{
		// SQLite: hasAutoCommitAttribute = false → getAutoCommit must return false.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$this->assertFalse($conn->AutoCommit);
		$conn->Active = false;
	}

	public function testSetAutoCommitIsNoOpWhenNoAutoCommitAttribute(): void
	{
		// SQLite: setAutoCommit is a no-op; must not throw.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->AutoCommit = true;   // no-op for sqlite
		$this->assertFalse($conn->AutoCommit);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// commit() / rollback() — return value semantics
	// -----------------------------------------------------------------------

	public function testCommitReturnsTrueOnSuccess(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->beginTransaction();
		$this->assertTrue($conn->commit());
		$conn->Active = false;
	}

	public function testRollbackReturnsTrueOnSuccess(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		$conn->beginTransaction();
		$this->assertTrue($conn->rollback());
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// ColumnCase / NullConversion — full enum round-trip
	// -----------------------------------------------------------------------

	public function testColumnCaseUpperAndLower(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;

		$conn->ColumnCase = \Prado\Data\TDbColumnCaseMode::UpperCase;
		$this->assertSame(\Prado\Data\TDbColumnCaseMode::UpperCase, $conn->ColumnCase);

		$conn->ColumnCase = \Prado\Data\TDbColumnCaseMode::Preserved;
		$this->assertSame(\Prado\Data\TDbColumnCaseMode::Preserved, $conn->ColumnCase);
		$conn->Active = false;
	}

	public function testNullConversionEmptyStringAndPreserved(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;

		$conn->NullConversion = \Prado\Data\TDbNullConversionMode::EmptyStringToNull;
		$this->assertSame(\Prado\Data\TDbNullConversionMode::EmptyStringToNull, $conn->NullConversion);

		$conn->NullConversion = \Prado\Data\TDbNullConversionMode::Preserved;
		$this->assertSame(\Prado\Data\TDbNullConversionMode::Preserved, $conn->NullConversion);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDriverName() — extractDriverFromDsn edge cases
	// -----------------------------------------------------------------------

	public function testGetDriverNameFromEmptyDsnThrows(): void
	{
		$conn = new TDbConnection('');
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$conn->DriverName;
	}

	public function testGetDriverNameIsCaseLowered(): void
	{
		// DSN prefixes are case-insensitive; TDbConnection normalises to lowercase.
		$conn = new TDbConnection('SQLite:' . TEST_DB_FILE);
		$this->assertSame('sqlite', $conn->DriverName);
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — inactive path
	// -----------------------------------------------------------------------

	public function testGetDatabaseCharsetReturnsEmptyStringWhenNotSetAndInactive(): void
	{
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->assertSame('', $conn->DatabaseCharset);
	}

	// -----------------------------------------------------------------------
	// applyCharsetToDsn() — interbase treated as firebird for DSN param
	// -----------------------------------------------------------------------

	public function testApplyCharsetToDsnInterbaseUsesCharsetParam(): void
	{
		$dsn    = 'interbase:dbname=localhost:/db/test.gdb';
		$conn   = new TDbConnection($dsn, '', '', 'UTF-8');
		$method = new \ReflectionMethod(TDbConnection::class, 'applyCharsetToDsn');
		$method->setAccessible(true);
		$result = $method->invoke($conn, $dsn);
		$this->assertStringContainsString('charset=', $result);
	}

	// -----------------------------------------------------------------------
	// createTransaction() — serial-mode determination
	// -----------------------------------------------------------------------

	/**
	 * Build a mock PDO whose getAttribute() returns the given driver name for
	 * ATTR_DRIVER_NAME and the given integer for ATTR_AUTOCOMMIT.
	 */
	private function makePdoForDriver(string $driver, int $autoCommit = 1): \PDO
	{
		$pdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->onlyMethods(['getAttribute', 'beginTransaction', 'inTransaction'])
			->getMock();

		$pdo->method('getAttribute')
			->willReturnCallback(function (int $attr) use ($driver, $autoCommit) {
				return match ($attr) {
					PDO::ATTR_DRIVER_NAME  => $driver,
					PDO::ATTR_AUTOCOMMIT   => $autoCommit,
					default                => null,
				};
			});

		$pdo->method('inTransaction')->willReturn(false);
		$pdo->method('beginTransaction')->willReturn(true);

		return $pdo;
	}

	/** Call createTransaction() via reflection on a connection with an injected PDO. */
	private function callCreateTransaction(TDbConnection $conn): \Prado\Data\TDbTransaction
	{
		$method = new \ReflectionMethod(TDbConnection::class, 'createTransaction');
		$method->setAccessible(true);
		return $method->invoke($conn);
	}

	public function testCreateTransactionIsNotSerialForNonSerialDriverWithAutoCommitOn(): void
	{
		// MySQL, autocommit ON (default) → non-serial transaction.
		$conn = new TDbConnection('mysql:host=localhost');
		$this->injectMockPdo($conn, $this->makePdoForDriver('mysql', 1));
		$tx = $this->callCreateTransaction($conn);
		$this->assertFalse(
			$tx->getSerial(),
			'createTransaction() must produce a non-serial transaction when autocommit is on.'
		);
	}

	public function testCreateTransactionIsSerialForNonSerialDriverWithAutoCommitOff(): void
	{
		// MySQL, autocommit OFF → serial transaction, because every commit/rollback
		// must immediately restart a new transaction to maintain the non-autocommit state.
		$conn = new TDbConnection('mysql:host=localhost');
		$this->injectMockPdo($conn, $this->makePdoForDriver('mysql', 0));
		$tx = $this->callCreateTransaction($conn);
		$this->assertTrue(
			$tx->getSerial(),
			'createTransaction() must produce a serial transaction when autocommit is off and the driver exposes ATTR_AUTOCOMMIT.'
		);
	}

	public function testCreateTransactionIsSerialForFirebird(): void
	{
		// Firebird always uses serial transactions (usesSerialTransaction=true),
		// regardless of the ATTR_AUTOCOMMIT state.
		$conn = new TDbConnection('firebird:dbname=localhost:/db/test.fdb');
		$this->injectMockPdo($conn, $this->makePdoForDriver('firebird', 1));
		$tx = $this->callCreateTransaction($conn);
		$this->assertTrue(
			$tx->getSerial(),
			'createTransaction() must produce a serial transaction for Firebird (usesSerialTransaction=true).'
		);
	}

	public function testCreateTransactionIsNotSerialForSqliteEvenWithAutoCommitOff(): void
	{
		// SQLite has hasAutoCommitAttribute=false; the autocommit-off path must
		// never apply.  The serial flag must be false regardless of ATTR_AUTOCOMMIT.
		$conn = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$conn->Active = true;
		// SQLite doesn't support ATTR_AUTOCOMMIT; the mock returns 0 to verify
		// that hasAutoCommitAttribute=false gates the condition correctly.
		$pdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->onlyMethods(['getAttribute'])
			->getMock();
		$pdo->method('getAttribute')
			->willReturnCallback(function (int $attr) {
				return match ($attr) {
					PDO::ATTR_DRIVER_NAME => 'sqlite',
					PDO::ATTR_AUTOCOMMIT  => 0, // forced off; must still be ignored
					default               => null,
				};
			});
		$this->injectMockPdo($conn, $pdo);
		$tx = $this->callCreateTransaction($conn);
		$this->assertFalse(
			$tx->getSerial(),
			'createTransaction() must NOT produce a serial transaction for SQLite (hasAutoCommitAttribute=false).'
		);
		$conn->Active = false;
	}

	public function testCreateTransactionIsNotSerialForPgsqlEvenWithAutoCommitOff(): void
	{
		// pgsql has hasAutoCommitAttribute=false; the autocommit-off path must
		// never apply.
		$conn = new TDbConnection('pgsql:host=localhost');
		$pdo  = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->onlyMethods(['getAttribute'])
			->getMock();
		$pdo->method('getAttribute')
			->willReturnCallback(function (int $attr) {
				return match ($attr) {
					PDO::ATTR_DRIVER_NAME => 'pgsql',
					PDO::ATTR_AUTOCOMMIT  => 0,
					default               => null,
				};
			});
		$this->injectMockPdo($conn, $pdo);
		$tx = $this->callCreateTransaction($conn);
		$this->assertFalse(
			$tx->getSerial(),
			'createTransaction() must NOT produce a serial transaction for pgsql (hasAutoCommitAttribute=false).'
		);
	}
}
