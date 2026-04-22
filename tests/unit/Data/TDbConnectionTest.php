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
		$this->_connection1 = null;
		$this->_connection2 = null;
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
			'mssql'    => ['mssql'],
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
			// --- mssql / dblib charset names ---
			'UTF-8 mssql'      => ['UTF-8',      'mssql', 'UTF-8'],
			'ISO-8859-1 mssql' => ['ISO-8859-1', 'mssql', 'ISO-8859-1'],
			'ISO-8859-2 dblib' => ['ISO-8859-2', 'dblib', 'ISO-8859-2'],
			'WIN-1252 mssql'   => ['WIN-1252',   'mssql', 'CP1252'],
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
			// MSSQL / sqlsrv / dblib: iconv-compatible names
			'mssql/UTF-8'      => ['mssql',  'UTF-8',      'UTF-8'],
			'mssql/ISO-8859-1' => ['mssql',  'ISO-8859-1', 'ISO-8859-1'],
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
			// mssql: UTF-8 → charset=UTF-8
			'mssql/UTF-8' => [
				'mssql:host=localhost;dbname=test',
				'UTF-8',
				'mssql:host=localhost;dbname=test;charset=UTF-8',
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
}
