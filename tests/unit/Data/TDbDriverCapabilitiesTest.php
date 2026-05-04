<?php

/**
 * TDbDriverCapabilitiesTest class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Data\TDataCharset;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Data\TDbDriverCapabilities;
use Prado\Data\Common\Firebird\TFirebirdMetaData;
use Prado\Data\Common\Ibm\TIbmMetaData;
use Prado\Data\Common\IDataMetaData;
use Prado\Data\Common\Mssql\TMssqlMetaData;
use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\Common\Oracle\TOracleMetaData;
use Prado\Data\Common\Pgsql\TPgsqlMetaData;
use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Exceptions\TDbException;

/**
 * Comprehensive unit tests for {@see TDbDriverCapabilities}.
 *
 * All methods under test are static and require no live database connection.
 * Coverage:
 *  - canonicalizeCharset
 *  - resolveCharset (all charsets × all drivers, aliases, interbase, pass-through)
 *  - unresolveCharset (all charsets × all drivers, interbase, unknown pass-through)
 *  - getCharsetSetSql
 *  - getCharsetPragmaSql
 *  - supportsRuntimeCharsetSet
 *  - requiresPostConnectCharset
 *  - getCharsetDsnParam
 *  - getCharsetDsnPattern (includes live regex verification)
 *  - getCharsetQuerySql
 *  - requiresPreBeginTransactionFlush
 *  - requiresPostTransactionFlush
 *  - getListTablesSql
 *  - supportsCharset
 *  - hasAutoCommitAttribute
 *  - getMetaDataClass (all drivers + fxDataGetMetaDataClass event)
 *  - getScaffoldInputFile
 *  - getScaffoldInputClass
 *  - createScaffoldInput (all drivers + fxActiveRecordCreateScaffoldInput event)
 */
class TDbDriverCapabilitiesTest extends PHPUnit\Framework\TestCase
{
	// =========================================================================
	//  canonicalizeCharset
	// =========================================================================

	/** @dataProvider provideCanonicalizeCharset */
	public function testCanonicalizeCharset(string $input, string $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::canonicalizeCharset($input));
	}

	public static function provideCanonicalizeCharset(): array
	{
		return [
			'UTF-8'         => ['UTF-8',        'utf8'],
			'utf-8'         => ['utf-8',         'utf8'],
			'UTF8'          => ['UTF8',          'utf8'],
			'utf8'          => ['utf8',          'utf8'],
			'UTF 8'         => ['UTF 8',         'utf8'],
			'UTF_8'         => ['UTF_8',         'utf8'],
			'UTF-16'        => ['UTF-16',        'utf16'],
			'utf16'         => ['utf16',         'utf16'],
			'ISO-8859-1'    => ['ISO-8859-1',    'iso88591'],
			'iso88591'      => ['iso88591',      'iso88591'],
			'ISO_8859_1'    => ['ISO_8859_1',    'iso88591'],
			'ISO-8859-2'    => ['ISO-8859-2',    'iso88592'],
			'ASCII'         => ['ASCII',         'ascii'],
			'ascii'         => ['ascii',         'ascii'],
			'Windows-1250'  => ['Windows-1250',  'windows1250'],
			'windows1250'   => ['windows1250',   'windows1250'],
			'win1250'       => ['win1250',       'win1250'],
			'CP1250'        => ['CP1250',        'cp1250'],
			'Windows-1251'  => ['Windows-1251',  'windows1251'],
			'Windows-1252'  => ['Windows-1252',  'windows1252'],
			'KOI8-R'        => ['KOI8-R',        'koi8r'],
			'koi8r'         => ['koi8r',         'koi8r'],
			'KOI8_R'        => ['KOI8_R',        'koi8r'],
			'KOI8-U'        => ['KOI8-U',        'koi8u'],
			'utf8mb4'       => ['utf8mb4',       'utf8mb4'],
			'latin1'        => ['latin1',        'latin1'],
			'empty'         => ['',              ''],
			'already-lower' => ['alreadylower',  'alreadylower'],
		];
	}

	// =========================================================================
	//  resolveCharset — comprehensive matrix
	// =========================================================================

	/** @dataProvider provideResolveCharset */
	public function testResolveCharset(string $charset, string $driver, string $expected): void
	{
		$this->assertSame(
			$expected,
			TDbDriverCapabilities::resolveCharset($charset, $driver),
			"resolveCharset('$charset', '$driver') expected '$expected'"
		);
	}

	public static function provideResolveCharset(): array
	{
		return [
			// --- UTF-8 (TDataCharset::UTF8 = 'UTF-8') ---
			'UTF-8/mysql'     => ['UTF-8',  TDbDriver::DRIVER_MYSQL,    'utf8mb4'],
			'UTF-8/pgsql'     => ['UTF-8',  TDbDriver::DRIVER_PGSQL,    'UTF8'],
			'UTF-8/sqlite'    => ['UTF-8',  TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'UTF-8/sqlite2'   => ['UTF-8',  TDbDriver::DRIVER_SQLITE2,  'UTF-8'],
			'UTF-8/firebird'  => ['UTF-8',  TDbDriver::DRIVER_FIREBIRD, 'UTF8'],
			'UTF-8/interbase' => ['UTF-8',  TDbDriver::DRIVER_INTERBASE,'UTF8'],
			'UTF-8/oci'       => ['UTF-8',  TDbDriver::DRIVER_OCI,      'AL32UTF8'],
			'UTF-8/sqlsrv'    => ['UTF-8',  TDbDriver::DRIVER_SQLSRV,   'UTF-8'],
			'UTF-8/dblib'     => ['UTF-8',  TDbDriver::DRIVER_DBLIB,    'UTF-8'],
			'UTF-8/ibm'       => ['UTF-8',  TDbDriver::DRIVER_IBM,      'UTF-8'],  // pass-through (no entry)

			// --- UTF-16 (TDataCharset::UTF16 = 'UTF-16') ---
			'UTF-16/mysql'     => ['UTF-16', TDbDriver::DRIVER_MYSQL,    'utf16'],
			'UTF-16/pgsql'     => ['UTF-16', TDbDriver::DRIVER_PGSQL,    'UTF-16'],  // no pgsql entry → pass-through
			'UTF-16/sqlite'    => ['UTF-16', TDbDriver::DRIVER_SQLITE,   'UTF-16'],
			'UTF-16/firebird'  => ['UTF-16', TDbDriver::DRIVER_FIREBIRD, 'UTF16BE'],
			'UTF-16/interbase' => ['UTF-16', TDbDriver::DRIVER_INTERBASE,'UTF16BE'],
			'UTF-16/oci'       => ['UTF-16', TDbDriver::DRIVER_OCI,      'AL16UTF16'],
			'UTF-16/sqlsrv'    => ['UTF-16', TDbDriver::DRIVER_SQLSRV,   'UTF-16'],  // no entry → pass-through
			'UTF-16/ibm'       => ['UTF-16', TDbDriver::DRIVER_IBM,      'UTF-16'],  // no entry → pass-through

			// --- ISO-8859-1 / Latin1 ---
			'ISO-8859-1/mysql'     => ['ISO-8859-1', TDbDriver::DRIVER_MYSQL,    'latin1'],
			'ISO-8859-1/pgsql'     => ['ISO-8859-1', TDbDriver::DRIVER_PGSQL,    'LATIN1'],
			'ISO-8859-1/sqlite'    => ['ISO-8859-1', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'ISO-8859-1/firebird'  => ['ISO-8859-1', TDbDriver::DRIVER_FIREBIRD, 'ISO8859_1'],
			'ISO-8859-1/interbase' => ['ISO-8859-1', TDbDriver::DRIVER_INTERBASE,'ISO8859_1'],
			'ISO-8859-1/oci'       => ['ISO-8859-1', TDbDriver::DRIVER_OCI,      'WE8ISO8859P1'],
			'ISO-8859-1/sqlsrv'    => ['ISO-8859-1', TDbDriver::DRIVER_SQLSRV,   'ISO-8859-1'],  // no entry → pass-through
			'ISO-8859-1/dblib'     => ['ISO-8859-1', TDbDriver::DRIVER_DBLIB,    'ISO-8859-1'],
			'ISO-8859-1/ibm'       => ['ISO-8859-1', TDbDriver::DRIVER_IBM,      'ISO-8859-1'],  // no entry → pass-through

			// --- ISO-8859-2 / Latin2 ---
			'ISO-8859-2/mysql'    => ['ISO-8859-2', TDbDriver::DRIVER_MYSQL,    'latin2'],
			'ISO-8859-2/pgsql'    => ['ISO-8859-2', TDbDriver::DRIVER_PGSQL,    'LATIN2'],
			'ISO-8859-2/sqlite'   => ['ISO-8859-2', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'ISO-8859-2/firebird' => ['ISO-8859-2', TDbDriver::DRIVER_FIREBIRD, 'ISO8859_2'],
			'ISO-8859-2/oci'      => ['ISO-8859-2', TDbDriver::DRIVER_OCI,      'EE8ISO8859P2'],
			'ISO-8859-2/dblib'    => ['ISO-8859-2', TDbDriver::DRIVER_DBLIB,    'ISO-8859-2'],
			'ISO-8859-2/ibm'      => ['ISO-8859-2', TDbDriver::DRIVER_IBM,      'ISO-8859-2'],

			// --- ASCII ---
			'ASCII/mysql'    => ['ASCII', TDbDriver::DRIVER_MYSQL,    'ascii'],
			'ASCII/pgsql'    => ['ASCII', TDbDriver::DRIVER_PGSQL,    'SQL_ASCII'],
			'ASCII/sqlite'   => ['ASCII', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'ASCII/firebird' => ['ASCII', TDbDriver::DRIVER_FIREBIRD, 'ASCII'],
			'ASCII/oci'      => ['ASCII', TDbDriver::DRIVER_OCI,      'US7ASCII'],
			'ASCII/dblib'    => ['ASCII', TDbDriver::DRIVER_DBLIB,    'ASCII'],
			'ASCII/ibm'      => ['ASCII', TDbDriver::DRIVER_IBM,      'ASCII'],

			// --- Windows-1250 ---
			'Windows-1250/mysql'    => ['Windows-1250', TDbDriver::DRIVER_MYSQL,    'cp1250'],
			'Windows-1250/pgsql'    => ['Windows-1250', TDbDriver::DRIVER_PGSQL,    'WIN1250'],
			'Windows-1250/sqlite'   => ['Windows-1250', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'Windows-1250/firebird' => ['Windows-1250', TDbDriver::DRIVER_FIREBIRD, 'WIN1250'],
			'Windows-1250/oci'      => ['Windows-1250', TDbDriver::DRIVER_OCI,      'EE8MSWIN1250'],
			'Windows-1250/dblib'    => ['Windows-1250', TDbDriver::DRIVER_DBLIB,    'CP1250'],

			// --- Windows-1251 ---
			'Windows-1251/mysql'    => ['Windows-1251', TDbDriver::DRIVER_MYSQL,    'cp1251'],
			'Windows-1251/pgsql'    => ['Windows-1251', TDbDriver::DRIVER_PGSQL,    'WIN1251'],
			'Windows-1251/sqlite'   => ['Windows-1251', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'Windows-1251/firebird' => ['Windows-1251', TDbDriver::DRIVER_FIREBIRD, 'WIN1251'],
			'Windows-1251/oci'      => ['Windows-1251', TDbDriver::DRIVER_OCI,      'CL8MSWIN1251'],
			'Windows-1251/dblib'    => ['Windows-1251', TDbDriver::DRIVER_DBLIB,    'CP1251'],

			// --- Windows-1252 ---
			'Windows-1252/mysql'    => ['Windows-1252', TDbDriver::DRIVER_MYSQL,    'cp1252'],
			'Windows-1252/pgsql'    => ['Windows-1252', TDbDriver::DRIVER_PGSQL,    'WIN1252'],
			'Windows-1252/sqlite'   => ['Windows-1252', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'Windows-1252/firebird' => ['Windows-1252', TDbDriver::DRIVER_FIREBIRD, 'WIN1252'],
			'Windows-1252/oci'      => ['Windows-1252', TDbDriver::DRIVER_OCI,      'WE8MSWIN1252'],
			'Windows-1252/dblib'    => ['Windows-1252', TDbDriver::DRIVER_DBLIB,    'CP1252'],

			// --- KOI8-R ---
			'KOI8-R/mysql'    => ['KOI8-R', TDbDriver::DRIVER_MYSQL,    'koi8r'],
			'KOI8-R/pgsql'    => ['KOI8-R', TDbDriver::DRIVER_PGSQL,    'KOI8R'],
			'KOI8-R/sqlite'   => ['KOI8-R', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'KOI8-R/firebird' => ['KOI8-R', TDbDriver::DRIVER_FIREBIRD, 'KOI8R'],
			'KOI8-R/oci'      => ['KOI8-R', TDbDriver::DRIVER_OCI,      'CL8KOI8R'],
			'KOI8-R/dblib'    => ['KOI8-R', TDbDriver::DRIVER_DBLIB,    'KOI8-R'],

			// --- KOI8-U ---
			'KOI8-U/mysql'    => ['KOI8-U', TDbDriver::DRIVER_MYSQL,    'koi8u'],
			'KOI8-U/pgsql'    => ['KOI8-U', TDbDriver::DRIVER_PGSQL,    'KOI8U'],
			'KOI8-U/sqlite'   => ['KOI8-U', TDbDriver::DRIVER_SQLITE,   'UTF-8'],
			'KOI8-U/firebird' => ['KOI8-U', TDbDriver::DRIVER_FIREBIRD, 'KOI8U'],
			'KOI8-U/oci'      => ['KOI8-U', TDbDriver::DRIVER_OCI,      'CL8KOI8U'],
			'KOI8-U/dblib'    => ['KOI8-U', TDbDriver::DRIVER_DBLIB,    'KOI8-U'],

			// --- Canonical key aliases ---
			'utf8/mysql'      => ['utf8',    TDbDriver::DRIVER_MYSQL, 'utf8mb4'],   // canonical alias
			'utf8mb4/mysql'   => ['utf8mb4', TDbDriver::DRIVER_MYSQL, 'utf8mb4'],   // canonical alias
			'utf8mb4/pgsql'   => ['utf8mb4', TDbDriver::DRIVER_PGSQL, 'UTF8'],
			'latin1/mysql'    => ['latin1',  TDbDriver::DRIVER_MYSQL, 'latin1'],    // canonical alias
			'latin1/pgsql'    => ['latin1',  TDbDriver::DRIVER_PGSQL, 'LATIN1'],
			'latin2/mysql'    => ['latin2',  TDbDriver::DRIVER_MYSQL, 'latin2'],
			'iso88591/mysql'  => ['iso88591',TDbDriver::DRIVER_MYSQL, 'latin1'],    // canonical alias
			'ascii/mysql'     => ['ascii',   TDbDriver::DRIVER_MYSQL, 'ascii'],     // canonical alias
			'win1250/mysql'   => ['win1250', TDbDriver::DRIVER_MYSQL, 'cp1250'],    // canonical alias
			'cp1250/pgsql'    => ['cp1250',  TDbDriver::DRIVER_PGSQL, 'WIN1250'],
			'koi8r/mysql'     => ['koi8r',   TDbDriver::DRIVER_MYSQL, 'koi8r'],     // canonical alias
			'koi8u/pgsql'     => ['koi8u',   TDbDriver::DRIVER_PGSQL, 'KOI8U'],
			'utf16/sqlite'    => ['utf16',   TDbDriver::DRIVER_SQLITE,'UTF-16'],    // canonical alias

			// --- Case/punctuation variants resolve via canonicalization ---
			'UTF-8 variants/mysql'     => ['utf-8',       TDbDriver::DRIVER_MYSQL, 'utf8mb4'],
			'UTF8 variants/mysql'      => ['UTF8',         TDbDriver::DRIVER_MYSQL, 'utf8mb4'],
			'iso-8859-1 variant/mysql' => ['iso-8859-1',   TDbDriver::DRIVER_MYSQL, 'latin1'],
			'ISO_8859_1 variant/mysql' => ['ISO_8859_1',   TDbDriver::DRIVER_MYSQL, 'latin1'],
			'windows1252/mysql'        => ['windows1252',  TDbDriver::DRIVER_MYSQL, 'cp1252'],
			'WIN-1252/pgsql'           => ['WIN-1252',     TDbDriver::DRIVER_PGSQL, 'WIN1252'],
			'win_1251/firebird'        => ['win_1251',     TDbDriver::DRIVER_FIREBIRD,'WIN1251'],
			'KOI8_R/pgsql'             => ['KOI8_R',       TDbDriver::DRIVER_PGSQL, 'KOI8R'],

			// --- Unknown charset: pass-through ---
			'unknown/mysql'  => ['my_custom_cs', TDbDriver::DRIVER_MYSQL,  'my_custom_cs'],
			'unknown/pgsql'  => ['EUC_JP',       TDbDriver::DRIVER_PGSQL,  'EUC_JP'],
			'unknown/sqlite' => ['EXOTIC',       TDbDriver::DRIVER_SQLITE, 'EXOTIC'],

			// --- Unknown driver: pass-through ---
			// Note: 'latin1' is a canonical alias for 'ISO-8859-1' in the first
			// lookup step (alias → TDataCharset::Latin1 → canonical key), so the
			// output for an unknown driver is 'ISO-8859-1', not the input 'latin1'.
			'UTF-8/unknown' => ['UTF-8', 'unknown_db', 'UTF-8'],
			'latin1/mongo'  => ['latin1', TDbDriver::DRIVER_MONGO, 'ISO-8859-1'],
		];
	}

	// =========================================================================
	//  unresolveCharset — comprehensive matrix
	// =========================================================================

	/** @dataProvider provideUnresolveCharset */
	public function testUnresolveCharset(string $dbCharset, string $driver, string $expected): void
	{
		$this->assertSame(
			$expected,
			TDbDriverCapabilities::unresolveCharset($dbCharset, $driver),
			"unresolveCharset('$dbCharset', '$driver') expected '$expected'"
		);
	}

	public static function provideUnresolveCharset(): array
	{
		return [
			// --- MySQL ---
			'mysql/utf8mb4'  => ['utf8mb4', TDbDriver::DRIVER_MYSQL, TDataCharset::UTF8],
			'mysql/utf8'     => ['utf8',    TDbDriver::DRIVER_MYSQL, TDataCharset::UTF8],
			'mysql/utf16'    => ['utf16',   TDbDriver::DRIVER_MYSQL, TDataCharset::UTF16],
			'mysql/latin1'   => ['latin1',  TDbDriver::DRIVER_MYSQL, TDataCharset::Latin1],
			'mysql/latin2'   => ['latin2',  TDbDriver::DRIVER_MYSQL, TDataCharset::Latin2],
			'mysql/ascii'    => ['ascii',   TDbDriver::DRIVER_MYSQL, TDataCharset::ASCII],
			'mysql/cp1250'   => ['cp1250',  TDbDriver::DRIVER_MYSQL, TDataCharset::Win1250],
			'mysql/cp1251'   => ['cp1251',  TDbDriver::DRIVER_MYSQL, TDataCharset::Win1251],
			'mysql/cp1252'   => ['cp1252',  TDbDriver::DRIVER_MYSQL, TDataCharset::Win1252],
			'mysql/koi8r'    => ['koi8r',   TDbDriver::DRIVER_MYSQL, TDataCharset::KOI8R],
			'mysql/koi8u'    => ['koi8u',   TDbDriver::DRIVER_MYSQL, TDataCharset::KOI8U],

			// --- SQLite ---
			'sqlite/UTF-8'   => ['UTF-8',  TDbDriver::DRIVER_SQLITE, TDataCharset::UTF8],
			'sqlite/UTF-16'  => ['UTF-16', TDbDriver::DRIVER_SQLITE, TDataCharset::UTF16],

			// --- PostgreSQL ---
			'pgsql/UTF8'     => ['UTF8',      TDbDriver::DRIVER_PGSQL, TDataCharset::UTF8],
			'pgsql/UTF16'    => ['UTF16',     TDbDriver::DRIVER_PGSQL, TDataCharset::UTF16],
			'pgsql/LATIN1'   => ['LATIN1',    TDbDriver::DRIVER_PGSQL, TDataCharset::Latin1],
			'pgsql/LATIN2'   => ['LATIN2',    TDbDriver::DRIVER_PGSQL, TDataCharset::Latin2],
			'pgsql/SQL_ASCII'=> ['SQL_ASCII', TDbDriver::DRIVER_PGSQL, TDataCharset::ASCII],
			'pgsql/WIN1250'  => ['WIN1250',   TDbDriver::DRIVER_PGSQL, TDataCharset::Win1250],
			'pgsql/WIN1251'  => ['WIN1251',   TDbDriver::DRIVER_PGSQL, TDataCharset::Win1251],
			'pgsql/WIN1252'  => ['WIN1252',   TDbDriver::DRIVER_PGSQL, TDataCharset::Win1252],
			'pgsql/KOI8R'    => ['KOI8R',     TDbDriver::DRIVER_PGSQL, TDataCharset::KOI8R],
			'pgsql/KOI8U'    => ['KOI8U',     TDbDriver::DRIVER_PGSQL, TDataCharset::KOI8U],

			// --- Firebird ---
			'firebird/UTF8'     => ['UTF8',     TDbDriver::DRIVER_FIREBIRD, TDataCharset::UTF8],
			'firebird/UTF16BE'  => ['UTF16BE',  TDbDriver::DRIVER_FIREBIRD, TDataCharset::UTF16],
			'firebird/ISO8859_1'=> ['ISO8859_1',TDbDriver::DRIVER_FIREBIRD, TDataCharset::Latin1],
			'firebird/ISO8859_2'=> ['ISO8859_2',TDbDriver::DRIVER_FIREBIRD, TDataCharset::Latin2],
			'firebird/ASCII'    => ['ASCII',    TDbDriver::DRIVER_FIREBIRD, TDataCharset::ASCII],
			'firebird/WIN1250'  => ['WIN1250',  TDbDriver::DRIVER_FIREBIRD, TDataCharset::Win1250],
			'firebird/WIN1251'  => ['WIN1251',  TDbDriver::DRIVER_FIREBIRD, TDataCharset::Win1251],
			'firebird/WIN1252'  => ['WIN1252',  TDbDriver::DRIVER_FIREBIRD, TDataCharset::Win1252],
			'firebird/KOI8R'    => ['KOI8R',    TDbDriver::DRIVER_FIREBIRD, TDataCharset::KOI8R],
			'firebird/KOI8U'    => ['KOI8U',    TDbDriver::DRIVER_FIREBIRD, TDataCharset::KOI8U],

			// --- Interbase alias → same as firebird ---
			'interbase/UTF8'    => ['UTF8',     TDbDriver::DRIVER_INTERBASE, TDataCharset::UTF8],
			'interbase/ISO8859_1'=>['ISO8859_1',TDbDriver::DRIVER_INTERBASE, TDataCharset::Latin1],

			// --- Oracle ---
			'oci/AL32UTF8'     => ['AL32UTF8',     TDbDriver::DRIVER_OCI, TDataCharset::UTF8],
			'oci/AL16UTF16'    => ['AL16UTF16',    TDbDriver::DRIVER_OCI, TDataCharset::UTF16],
			'oci/WE8ISO8859P1' => ['WE8ISO8859P1', TDbDriver::DRIVER_OCI, TDataCharset::Latin1],
			'oci/EE8ISO8859P2' => ['EE8ISO8859P2', TDbDriver::DRIVER_OCI, TDataCharset::Latin2],
			'oci/US7ASCII'     => ['US7ASCII',     TDbDriver::DRIVER_OCI, TDataCharset::ASCII],
			'oci/EE8MSWIN1250' => ['EE8MSWIN1250', TDbDriver::DRIVER_OCI, TDataCharset::Win1250],
			'oci/CL8MSWIN1251' => ['CL8MSWIN1251', TDbDriver::DRIVER_OCI, TDataCharset::Win1251],
			'oci/WE8MSWIN1252' => ['WE8MSWIN1252', TDbDriver::DRIVER_OCI, TDataCharset::Win1252],
			'oci/CL8KOI8R'     => ['CL8KOI8R',     TDbDriver::DRIVER_OCI, TDataCharset::KOI8R],
			'oci/CL8KOI8U'     => ['CL8KOI8U',     TDbDriver::DRIVER_OCI, TDataCharset::KOI8U],

			// --- SQLSRV ---
			'sqlsrv/UTF-8'     => ['UTF-8',     TDbDriver::DRIVER_SQLSRV, TDataCharset::UTF8],
			'sqlsrv/ISO-8859-1'=> ['ISO-8859-1',TDbDriver::DRIVER_SQLSRV, TDataCharset::Latin1],
			'sqlsrv/ISO-8859-2'=> ['ISO-8859-2',TDbDriver::DRIVER_SQLSRV, TDataCharset::Latin2],
			'sqlsrv/ASCII'     => ['ASCII',     TDbDriver::DRIVER_SQLSRV, TDataCharset::ASCII],
			'sqlsrv/CP1250'    => ['CP1250',    TDbDriver::DRIVER_SQLSRV, TDataCharset::Win1250],
			'sqlsrv/CP1251'    => ['CP1251',    TDbDriver::DRIVER_SQLSRV, TDataCharset::Win1251],
			'sqlsrv/CP1252'    => ['CP1252',    TDbDriver::DRIVER_SQLSRV, TDataCharset::Win1252],
			'sqlsrv/KOI8-R'    => ['KOI8-R',    TDbDriver::DRIVER_SQLSRV, TDataCharset::KOI8R],
			'sqlsrv/KOI8-U'    => ['KOI8-U',    TDbDriver::DRIVER_SQLSRV, TDataCharset::KOI8U],

			// --- DBLIB ---
			'dblib/UTF-8'      => ['UTF-8',     TDbDriver::DRIVER_DBLIB, TDataCharset::UTF8],
			'dblib/ISO-8859-1' => ['ISO-8859-1',TDbDriver::DRIVER_DBLIB, TDataCharset::Latin1],
			'dblib/ISO-8859-2' => ['ISO-8859-2',TDbDriver::DRIVER_DBLIB, TDataCharset::Latin2],
			'dblib/ASCII'      => ['ASCII',     TDbDriver::DRIVER_DBLIB, TDataCharset::ASCII],
			'dblib/CP1250'     => ['CP1250',    TDbDriver::DRIVER_DBLIB, TDataCharset::Win1250],
			'dblib/CP1251'     => ['CP1251',    TDbDriver::DRIVER_DBLIB, TDataCharset::Win1251],
			'dblib/CP1252'     => ['CP1252',    TDbDriver::DRIVER_DBLIB, TDataCharset::Win1252],
			'dblib/KOI8-R'     => ['KOI8-R',    TDbDriver::DRIVER_DBLIB, TDataCharset::KOI8R],
			'dblib/KOI8-U'     => ['KOI8-U',    TDbDriver::DRIVER_DBLIB, TDataCharset::KOI8U],

			// --- Unknown charset: pass-through ---
			'unknown/mysql'    => ['UNKNOWN_CHARSET', TDbDriver::DRIVER_MYSQL,  'UNKNOWN_CHARSET'],
			'unknown/pgsql'    => ['SOME_VALUE',      TDbDriver::DRIVER_PGSQL,  'SOME_VALUE'],
			'unknown/sqlite'   => ['exotic-enc',      TDbDriver::DRIVER_SQLITE, 'exotic-enc'],

			// --- IBM: no table → pass-through ---
			'ibm/UTF-8'        => ['UTF-8', TDbDriver::DRIVER_IBM, 'UTF-8'],
			'ibm/anything'     => ['anything', TDbDriver::DRIVER_IBM, 'anything'],

			// --- Unknown driver: pass-through ---
			'unknown_driver/x' => ['utf8mb4', 'unknown_db', 'utf8mb4'],
		];
	}

	// =========================================================================
	//  Round-trip: resolveCharset ∘ unresolveCharset = identity
	// =========================================================================

	/** @dataProvider provideRoundTrip */
	public function testResolveUnresolveRoundTrip(string $phpCharset, string $driver): void
	{
		$dbCharset   = TDbDriverCapabilities::resolveCharset($phpCharset, $driver);
		$unresolved  = TDbDriverCapabilities::unresolveCharset($dbCharset, $driver);
		$this->assertSame(
			$phpCharset,
			$unresolved,
			"Round-trip '$phpCharset' via '$driver' returned '$unresolved' (db='$dbCharset')"
		);
	}

	public static function provideRoundTrip(): array
	{
		$charsets = [
			TDataCharset::UTF8,
			TDataCharset::UTF16,
			TDataCharset::Latin1,
			TDataCharset::Latin2,
			TDataCharset::ASCII,
			TDataCharset::Win1250,
			TDataCharset::Win1251,
			TDataCharset::Win1252,
			TDataCharset::KOI8R,
			TDataCharset::KOI8U,
		];
		$drivers = [
			TDbDriver::DRIVER_MYSQL,
			TDbDriver::DRIVER_PGSQL,
			TDbDriver::DRIVER_FIREBIRD,
			TDbDriver::DRIVER_OCI,
			TDbDriver::DRIVER_SQLSRV,
			TDbDriver::DRIVER_DBLIB,
		];
		// SQLite only has UTF-8 and UTF-16 in its unresolve table;
		// other charsets resolve to 'UTF-8' but unresolve('UTF-8', sqlite) = 'UTF-8' ≠ original.
		$sqliteCharsets = [TDataCharset::UTF8, TDataCharset::UTF16];

		$cases = [];
		foreach ($drivers as $driver) {
			foreach ($charsets as $cs) {
				$cases["$cs/$driver"] = [$cs, $driver];
			}
		}
		foreach ($sqliteCharsets as $cs) {
			$cases["$cs/sqlite"] = [$cs, TDbDriver::DRIVER_SQLITE];
		}
		// interbase aliases firebird → same round-trip
		$cases['UTF-8/interbase'] = [TDataCharset::UTF8,   TDbDriver::DRIVER_INTERBASE];
		$cases['KOI8-R/interbase']= [TDataCharset::KOI8R,  TDbDriver::DRIVER_INTERBASE];
		return $cases;
	}

	// =========================================================================
	//  getCharsetSetSql
	// =========================================================================

	/** @dataProvider provideCharsetSetSql */
	public function testGetCharsetSetSql(string $driver, ?string $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::getCharsetSetSql($driver));
	}

	public static function provideCharsetSetSql(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    'SET NAMES ?'],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    'SET client_encoding TO ?'],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   null],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  null],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, null],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,null],
			'oci'       => [TDbDriver::DRIVER_OCI,      null],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   null],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    null],
			'ibm'       => [TDbDriver::DRIVER_IBM,      null],
			'unknown'   => ['unknown_driver',            null],
		];
	}

	// =========================================================================
	//  getCharsetPragmaSql
	// =========================================================================

	/** @dataProvider provideCharsetPragmaSql */
	public function testGetCharsetPragmaSql(string $driver, ?string $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::getCharsetPragmaSql($driver));
	}

	public static function provideCharsetPragmaSql(): array
	{
		return [
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   'PRAGMA encoding = %s'],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  null],
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    null],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    null],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, null],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,null],
			'oci'       => [TDbDriver::DRIVER_OCI,      null],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   null],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    null],
			'ibm'       => [TDbDriver::DRIVER_IBM,      null],
			'unknown'   => ['unknown_driver',            null],
		];
	}

	public function testGetCharsetPragmaSqlContainsFormatSlot(): void
	{
		$sql = TDbDriverCapabilities::getCharsetPragmaSql(TDbDriver::DRIVER_SQLITE);
		$this->assertNotNull($sql);
		$this->assertStringContainsString('%s', $sql);
		// Verify sprintf formatting works
		$formatted = sprintf($sql, "'UTF-8'");
		$this->assertSame("PRAGMA encoding = 'UTF-8'", $formatted);
	}

	// =========================================================================
	//  supportsRuntimeCharsetSet
	// =========================================================================

	/** @dataProvider provideSupportsRuntimeCharsetSet */
	public function testSupportsRuntimeCharsetSet(string $driver, bool $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::supportsRuntimeCharsetSet($driver));
	}

	public static function provideSupportsRuntimeCharsetSet(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    true],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    true],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   true],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  false],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, false],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,false],
			'oci'       => [TDbDriver::DRIVER_OCI,      false],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   false],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    false],
			'ibm'       => [TDbDriver::DRIVER_IBM,      false],
			'unknown'   => ['unknown_driver',            false],
		];
	}

	// =========================================================================
	//  requiresPostConnectCharset
	// =========================================================================

	/** @dataProvider provideRequiresPostConnectCharset */
	public function testRequiresPostConnectCharset(string $driver, bool $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::requiresPostConnectCharset($driver));
	}

	public static function provideRequiresPostConnectCharset(): array
	{
		return [
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    true],
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    false],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   false],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  false],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, false],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,false],
			'oci'       => [TDbDriver::DRIVER_OCI,      false],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   false],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    false],
			'ibm'       => [TDbDriver::DRIVER_IBM,      false],
			'unknown'   => ['unknown_driver',            false],
		];
	}

	// =========================================================================
	//  getCharsetDsnParam
	// =========================================================================

	/** @dataProvider provideCharsetDsnParam */
	public function testGetCharsetDsnParam(string $driver, ?string $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::getCharsetDsnParam($driver));
	}

	public static function provideCharsetDsnParam(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    'charset'],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, 'charset'],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,'charset'],
			'oci'       => [TDbDriver::DRIVER_OCI,      'charset'],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    'charset'],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   'CharacterSet'],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    null],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   null],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  null],
			'ibm'       => [TDbDriver::DRIVER_IBM,      null],
			'unknown'   => ['unknown_driver',            null],
		];
	}

	// =========================================================================
	//  getCharsetDsnPattern — value & regex verification
	// =========================================================================

	/** @dataProvider provideCharsetDsnPattern */
	public function testGetCharsetDsnPatternValue(string $driver, ?string $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::getCharsetDsnPattern($driver));
	}

	public static function provideCharsetDsnPattern(): array
	{
		$stdPattern  = '/[;?]charset\s*=\s*([^;]+)/i';
		$srvPattern  = '/[;?]CharacterSet\s*=\s*([^;]+)/i';
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    $stdPattern],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, $stdPattern],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,$stdPattern],
			'oci'       => [TDbDriver::DRIVER_OCI,      $stdPattern],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    $stdPattern],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   $srvPattern],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    null],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   null],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  null],
			'ibm'       => [TDbDriver::DRIVER_IBM,      null],
			'unknown'   => ['unknown_driver',            null],
		];
	}

	public function testCharsetDsnPatternMysqlMatchesCharset(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_MYSQL);
		$this->assertNotNull($pattern);
		$this->assertSame(1, preg_match($pattern, 'mysql:host=localhost;charset=utf8mb4', $m));
		$this->assertSame('utf8mb4', trim($m[1]));
	}

	public function testCharsetDsnPatternMysqlMatchesWithSpaces(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_MYSQL);
		$this->assertSame(1, preg_match($pattern, 'mysql:host=localhost;charset = utf8mb4', $m));
		$this->assertSame('utf8mb4', trim($m[1]));
	}

	public function testCharsetDsnPatternMysqlIsCaseInsensitive(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_MYSQL);
		// Upper-case CHARSET
		$this->assertSame(1, preg_match($pattern, 'mysql:host=localhost;CHARSET=latin1', $m));
		$this->assertSame('latin1', trim($m[1]));
	}

	public function testCharsetDsnPatternMysqlDoesNotMatchAbsent(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_MYSQL);
		$this->assertSame(0, preg_match($pattern, 'mysql:host=localhost;dbname=test'));
	}

	public function testCharsetDsnPatternFirebirdMatchesCharset(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_FIREBIRD);
		$this->assertSame(
			1,
			preg_match($pattern, 'firebird:dbname=localhost:/path/to/db.fdb;charset=UTF8', $m)
		);
		$this->assertSame('UTF8', trim($m[1]));
	}

	public function testCharsetDsnPatternSqlsrvMatchesCharacterSet(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_SQLSRV);
		$this->assertSame(
			1,
			preg_match($pattern, 'sqlsrv:Server=localhost;Database=db;CharacterSet=UTF-8', $m)
		);
		$this->assertSame('UTF-8', trim($m[1]));
	}

	public function testCharsetDsnPatternSqlsrvDoesNotMatchLowercaseCharset(): void
	{
		// sqlsrv uses 'CharacterSet' not 'charset'; but the pattern is case-insensitive (flag /i)
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_SQLSRV);
		$this->assertSame(
			1,
			preg_match($pattern, 'sqlsrv:Server=localhost;characterset=UTF-8', $m)
		);
	}

	public function testCharsetDsnPatternInterbaseMatchesCharset(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_INTERBASE);
		$this->assertSame(
			1,
			preg_match($pattern, 'interbase:dbname=localhost:/db/file.gdb;charset=WIN1250', $m)
		);
		$this->assertSame('WIN1250', trim($m[1]));
	}

	public function testCharsetDsnPatternOciMatchesCharset(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_OCI);
		$this->assertSame(
			1,
			preg_match($pattern, 'oci:dbname=//localhost/orcl;charset=AL32UTF8', $m)
		);
		$this->assertSame('AL32UTF8', trim($m[1]));
	}

	public function testCharsetDsnPatternStopsAtSemicolon(): void
	{
		// The captured group [^;]+ must not cross a semicolon boundary
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_MYSQL);
		$this->assertSame(
			1,
			preg_match($pattern, 'mysql:host=localhost;charset=utf8mb4;other=val', $m)
		);
		$this->assertSame('utf8mb4', trim($m[1]));
	}

	// =========================================================================
	//  getCharsetQuerySql
	// =========================================================================

	/** @dataProvider provideCharsetQuerySql */
	public function testGetCharsetQuerySql(string $driver, ?string $expected): void
	{
		$actual = TDbDriverCapabilities::getCharsetQuerySql($driver);
		if ($expected === null) {
			$this->assertNull($actual);
		} else {
			$this->assertSame($expected, $actual);
		}
	}

	public static function provideCharsetQuerySql(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    'SELECT @@character_set_connection'],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   'PRAGMA encoding'],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    'SELECT pg_client_encoding()'],
			// 'firebird' is excluded here — its non-null MON$ATTACHMENTS query is
			// verified separately by testGetCharsetQuerySqlFirebirdContainsMonAttachments.
			'interbase' => [TDbDriver::DRIVER_INTERBASE,null],
			'oci'       => [TDbDriver::DRIVER_OCI,      null],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   null],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    null],
			'ibm'       => [TDbDriver::DRIVER_IBM,      null],
			'unknown'   => ['unknown_driver',            null],
		];
	}

	public function testGetCharsetQuerySqlFirebirdContainsMonAttachments(): void
	{
		$sql = TDbDriverCapabilities::getCharsetQuerySql(TDbDriver::DRIVER_FIREBIRD);
		$this->assertNotNull($sql);
		$this->assertStringContainsString('MON$ATTACHMENTS', $sql);
		$this->assertStringContainsString('RDB$CHARACTER_SETS', $sql);
		$this->assertStringContainsString('CURRENT_CONNECTION', $sql);
	}

	// =========================================================================
	//  requiresPreBeginTransactionFlush
	// =========================================================================

	/** @dataProvider provideRequiresPreBeginTransactionFlush */
	public function testRequiresPreBeginTransactionFlush(string $driver, bool $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::requiresPreBeginTransactionFlush($driver));
	}

	public static function provideRequiresPreBeginTransactionFlush(): array
	{
		return [
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, true],
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    false],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    false],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   false],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  false],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,false],
			'oci'       => [TDbDriver::DRIVER_OCI,      false],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   false],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    false],
			'ibm'       => [TDbDriver::DRIVER_IBM,      false],
			'unknown'   => ['unknown_driver',            false],
		];
	}

	// =========================================================================
	//  requiresPostTransactionFlush
	// =========================================================================

	/** @dataProvider provideRequiresPostTransactionFlush */
	public function testRequiresPostTransactionFlush(string $driver, bool $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::requiresPostTransactionFlush($driver));
	}

	public static function provideRequiresPostTransactionFlush(): array
	{
		return [
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, true],
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    false],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    false],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   false],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  false],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,false],
			'oci'       => [TDbDriver::DRIVER_OCI,      false],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   false],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    false],
			'ibm'       => [TDbDriver::DRIVER_IBM,      false],
			'unknown'   => ['unknown_driver',            false],
		];
	}

	public function testPreAndPostFlushAreConsistent(): void
	{
		// Both flags must be true for the same driver (Firebird) and false for all others.
		$drivers = [
			TDbDriver::DRIVER_MYSQL, TDbDriver::DRIVER_PGSQL, TDbDriver::DRIVER_SQLITE,
			TDbDriver::DRIVER_FIREBIRD, TDbDriver::DRIVER_OCI, TDbDriver::DRIVER_SQLSRV,
		];
		foreach ($drivers as $driver) {
			$pre  = TDbDriverCapabilities::requiresPreBeginTransactionFlush($driver);
			$post = TDbDriverCapabilities::requiresPostTransactionFlush($driver);
			$this->assertSame($pre, $post, "Pre/post flush inconsistency for '$driver'");
		}
	}

	// =========================================================================
	//  getListTablesSql
	// =========================================================================

	/** @dataProvider provideListTablesSql */
	public function testGetListTablesSql(string $driver, bool $isNull): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql($driver);
		if ($isNull) {
			$this->assertNull($sql);
		} else {
			$this->assertIsString($sql);
			$this->assertNotEmpty($sql);
		}
	}

	public static function provideListTablesSql(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    false],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    false],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   false],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  false],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, false],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,false],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   false],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    false],
			'oci'       => [TDbDriver::DRIVER_OCI,      false],
			'ibm'       => [TDbDriver::DRIVER_IBM,      false],
			'unknown'   => ['unknown_driver',            true],
			'odbc'      => [TDbDriver::DRIVER_ODBC,     true],
		];
	}

	public function testGetListTablesSqlMysqlIsShowTables(): void
	{
		$this->assertSame('SHOW TABLES', TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_MYSQL));
	}

	public function testGetListTablesSqlSqliteReferencesSqliteMaster(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_SQLITE);
		$this->assertStringContainsString('sqlite_master', $sql);
	}

	public function testGetListTablesSqlSqlite2SameAsSqlite3(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_SQLITE),
			TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_SQLITE2)
		);
	}

	public function testGetListTablesSqlPgsqlUsesInformationSchema(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_PGSQL);
		$this->assertStringContainsString('information_schema', $sql);
	}

	public function testGetListTablesSqlFirebirdUsesRdbRelations(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_FIREBIRD);
		$this->assertStringContainsString('RDB$RELATIONS', $sql);
	}

	public function testGetListTablesSqlInterbaseSameAsFirebird(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_FIREBIRD),
			TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_INTERBASE)
		);
	}

	public function testGetListTablesSqlMssqlUsesInformationSchema(): void
	{
		$sqlsrv = TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_SQLSRV);
		$dblib  = TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_DBLIB);
		$this->assertStringContainsString('INFORMATION_SCHEMA', $sqlsrv);
		$this->assertSame($sqlsrv, $dblib);
	}

	public function testGetListTablesSqlOciUsesUserTables(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_OCI);
		$this->assertStringContainsString('user_tables', $sql);
	}

	public function testGetListTablesSqlIbmUsesSyscatTables(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql(TDbDriver::DRIVER_IBM);
		$this->assertStringContainsString('SYSCAT.TABLES', $sql);
	}

	// =========================================================================
	//  supportsCharset
	// =========================================================================

	/** @dataProvider provideSupportsCharset */
	public function testSupportsCharset(string $driver, bool $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::supportsCharset($driver));
	}

	public static function provideSupportsCharset(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    true],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    true],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   true],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  true],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, true],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,true],
			'oci'       => [TDbDriver::DRIVER_OCI,      true],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   true],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    true],
			'ibm'       => [TDbDriver::DRIVER_IBM,      false], // sole exception
			'unknown'   => ['unknown_driver',            true],
		];
	}

	// =========================================================================
	//  hasAutoCommitAttribute
	// =========================================================================

	/** @dataProvider provideHasAutoCommitAttribute */
	public function testHasAutoCommitAttribute(string $driver, bool $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::hasAutoCommitAttribute($driver));
	}

	public static function provideHasAutoCommitAttribute(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    true],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    false], // pgsql does not expose ATTR_AUTOCOMMIT
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   false],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  true],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, true],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,true],
			'oci'       => [TDbDriver::DRIVER_OCI,      true],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   false], // sqlsrv does not expose ATTR_AUTOCOMMIT
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    false], // dblib does not expose ATTR_AUTOCOMMIT
			'ibm'       => [TDbDriver::DRIVER_IBM,      true],
			'unknown'   => ['unknown_driver',            true],
		];
	}

	// =========================================================================
	//  getMetaDataClass — known drivers
	// =========================================================================

	/** @dataProvider provideMetaDataClass */
	public function testGetMetaDataClassKnownDriver(string $driver, string $expectedClass): void
	{
		$result = TDbDriverCapabilities::getMetaDataClass($driver);
		$this->assertSame($expectedClass, $result);
	}

	public static function provideMetaDataClass(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    TMysqlMetaData::class],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    TPgsqlMetaData::class],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   TSqliteMetaData::class],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  TSqliteMetaData::class],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, TFirebirdMetaData::class],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,TFirebirdMetaData::class],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   TMssqlMetaData::class],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    TMssqlMetaData::class],
			'oci'       => [TDbDriver::DRIVER_OCI,      TOracleMetaData::class],
			'ibm'       => [TDbDriver::DRIVER_IBM,      TIbmMetaData::class],
		];
	}

	public function testGetMetaDataClassUnknownDriverNullConnectionReturnsNull(): void
	{
		// No connection → no event raising; returns null.
		$result = TDbDriverCapabilities::getMetaDataClass('unknown_driver');
		$this->assertNull($result);
	}

	public function testGetMetaDataClassUnknownDriverNullConnectionPassedExplicitly(): void
	{
		$result = TDbDriverCapabilities::getMetaDataClass('unknown_driver', null);
		$this->assertNull($result);
	}

	public function testGetMetaDataClassUnknownDriverThrowsWhenNoEventHandlers(): void
	{
		// Connection present but raiseEvent returns empty → TDbException.
		$conn = $this->createMock(TDbConnection::class);
		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxDataGetMetaDataClass', $conn, 'unknown_driver')
			->willReturn([]);

		$this->expectException(TDbException::class);
		TDbDriverCapabilities::getMetaDataClass('unknown_driver', $conn);
	}

	public function testGetMetaDataClassFxEventRaisedWithCorrectParameters(): void
	{
		// The event is raised with (connection, driver) parameters.
		$driver = 'my_custom_driver';
		$conn = $this->createMock(TDbConnection::class);
		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxDataGetMetaDataClass', $conn, $driver)
			->willReturn(['Prado\Data\Common\Sqlite\TSqliteMetaData']);

		$result = TDbDriverCapabilities::getMetaDataClass($driver, $conn);
		$this->assertSame('Prado\Data\Common\Sqlite\TSqliteMetaData', $result);
	}

	public function testGetMetaDataClassFxEventReturnedClassNameIsUsed(): void
	{
		// A handler returns a fully-qualified class name → that value is returned.
		$conn = $this->createMock(TDbConnection::class);
		$conn->method('raiseEvent')->willReturn([TMysqlMetaData::class]);

		$result = TDbDriverCapabilities::getMetaDataClass('custom_driver', $conn);
		$this->assertSame(TMysqlMetaData::class, $result);
	}

	public function testGetMetaDataClassFxEventLastHandlerWins(): void
	{
		// array_pop takes the last value from the event result array.
		$conn = $this->createMock(TDbConnection::class);
		$conn->method('raiseEvent')->willReturn([
			TMysqlMetaData::class,
			TPgsqlMetaData::class,   // last → wins
		]);

		$result = TDbDriverCapabilities::getMetaDataClass('custom_driver', $conn);
		$this->assertSame(TPgsqlMetaData::class, $result);
	}

	public function testGetMetaDataClassFxEventReturningObjectThrowsTdbException(): void
	{
		// If a handler accidentally returns an IDataMetaData instance instead of a
		// class-name string, the method must throw to signal the incorrect usage.
		$badReturn = $this->createMock(IDataMetaData::class);

		$conn = $this->createMock(TDbConnection::class);
		$conn->method('raiseEvent')->willReturn([$badReturn]);

		$this->expectException(TDbException::class);
		TDbDriverCapabilities::getMetaDataClass('custom_driver', $conn);
	}

	public function testGetMetaDataClassFxEventReturningNonImplementingClassThrowsTdbException(): void
	{
		// If a handler returns a class name that does not implement IDataMetaData,
		// getMetaDataClass must throw rather than returning the bad class name to
		// the caller.
		$conn = $this->createMock(TDbConnection::class);
		$conn->method('raiseEvent')->willReturn([\stdClass::class]);

		$this->expectException(TDbException::class);
		TDbDriverCapabilities::getMetaDataClass('custom_driver', $conn);
	}

	public function testGetMetaDataClassKnownDriverIgnoresConnection(): void
	{
		// For known drivers, the connection is never consulted.
		$conn = $this->createMock(TDbConnection::class);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbDriverCapabilities::getMetaDataClass(TDbDriver::DRIVER_MYSQL, $conn);
		$this->assertSame(TMysqlMetaData::class, $result);
	}

	// =========================================================================
	//  getScaffoldInputFile
	// =========================================================================

	/** @dataProvider provideScaffoldInputFile */
	public function testGetScaffoldInputFile(string $driver, ?string $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::getScaffoldInputFile($driver));
	}

	public static function provideScaffoldInputFile(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    '/TMysqlScaffoldInput.php'],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    '/TPgsqlScaffoldInput.php'],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   '/TSqliteScaffoldInput.php'],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  '/TSqliteScaffoldInput.php'],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, '/TFirebirdScaffoldInput.php'],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,'/TFirebirdScaffoldInput.php'],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   '/TMssqlScaffoldInput.php'],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    '/TMssqlScaffoldInput.php'],
			'oci'       => [TDbDriver::DRIVER_OCI,      '/TOracleScaffoldInput.php'],
			'ibm'       => [TDbDriver::DRIVER_IBM,      '/TIbmScaffoldInput.php'],
			'unknown'   => ['unknown_driver',            null],
			'odbc'      => [TDbDriver::DRIVER_ODBC,     null],
		];
	}

	public function testGetScaffoldInputFileSqlite2SameAsSqlite3(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getScaffoldInputFile(TDbDriver::DRIVER_SQLITE),
			TDbDriverCapabilities::getScaffoldInputFile(TDbDriver::DRIVER_SQLITE2)
		);
	}

	public function testGetScaffoldInputFileInterbaseSameAsFirebird(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getScaffoldInputFile(TDbDriver::DRIVER_FIREBIRD),
			TDbDriverCapabilities::getScaffoldInputFile(TDbDriver::DRIVER_INTERBASE)
		);
	}

	public function testGetScaffoldInputFileSqlsrvSameAsDblib(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getScaffoldInputFile(TDbDriver::DRIVER_SQLSRV),
			TDbDriverCapabilities::getScaffoldInputFile(TDbDriver::DRIVER_DBLIB)
		);
	}

	public function testGetScaffoldInputFileHasPhpExtension(): void
	{
		$knownDrivers = [
			TDbDriver::DRIVER_MYSQL, TDbDriver::DRIVER_PGSQL, TDbDriver::DRIVER_SQLITE,
			TDbDriver::DRIVER_FIREBIRD, TDbDriver::DRIVER_SQLSRV, TDbDriver::DRIVER_OCI,
			TDbDriver::DRIVER_IBM,
		];
		foreach ($knownDrivers as $driver) {
			$file = TDbDriverCapabilities::getScaffoldInputFile($driver);
			$this->assertStringEndsWith('.php', $file, "File for '$driver' must end with .php");
			$this->assertStringStartsWith('/', $file, "File for '$driver' must start with /");
		}
	}

	// =========================================================================
	//  getScaffoldInputClass
	// =========================================================================

	/** @dataProvider provideScaffoldInputClass */
	public function testGetScaffoldInputClass(string $driver, ?string $expected): void
	{
		$this->assertSame($expected, TDbDriverCapabilities::getScaffoldInputClass($driver));
	}

	public static function provideScaffoldInputClass(): array
	{
		return [
			'mysql'     => [TDbDriver::DRIVER_MYSQL,    'TMysqlScaffoldInput'],
			'pgsql'     => [TDbDriver::DRIVER_PGSQL,    'TPgsqlScaffoldInput'],
			'sqlite'    => [TDbDriver::DRIVER_SQLITE,   'TSqliteScaffoldInput'],
			'sqlite2'   => [TDbDriver::DRIVER_SQLITE2,  'TSqliteScaffoldInput'],
			'firebird'  => [TDbDriver::DRIVER_FIREBIRD, 'TFirebirdScaffoldInput'],
			'interbase' => [TDbDriver::DRIVER_INTERBASE,'TFirebirdScaffoldInput'],
			'sqlsrv'    => [TDbDriver::DRIVER_SQLSRV,   'TMssqlScaffoldInput'],
			'dblib'     => [TDbDriver::DRIVER_DBLIB,    'TMssqlScaffoldInput'],
			'oci'       => [TDbDriver::DRIVER_OCI,      'TOracleScaffoldInput'],
			'ibm'       => [TDbDriver::DRIVER_IBM,      'TIbmScaffoldInput'],
			'unknown'   => ['unknown_driver',            null],
			'odbc'      => [TDbDriver::DRIVER_ODBC,     null],
		];
	}

	public function testGetScaffoldInputClassMatchesFileBasename(): void
	{
		// Class name must be the filename without leading / and .php extension.
		$knownDrivers = [
			TDbDriver::DRIVER_MYSQL, TDbDriver::DRIVER_PGSQL, TDbDriver::DRIVER_SQLITE,
			TDbDriver::DRIVER_FIREBIRD, TDbDriver::DRIVER_SQLSRV, TDbDriver::DRIVER_OCI,
			TDbDriver::DRIVER_IBM,
		];
		foreach ($knownDrivers as $driver) {
			$file  = TDbDriverCapabilities::getScaffoldInputFile($driver);
			$class = TDbDriverCapabilities::getScaffoldInputClass($driver);
			$this->assertNotNull($class);
			$this->assertSame($class . '.php', ltrim($file, '/'),
				"Class/file mismatch for '$driver'");
		}
	}

	// =========================================================================
	//  createScaffoldInput
	// =========================================================================

	/** @dataProvider provideScaffoldInputClass */
	public function testCreateScaffoldInputBuiltInDriverReturnsInstance(string $driver, ?string $expected): void
	{
		if ($expected === null) {
			$this->markTestSkipped('Unknown driver — tested separately via event path.');
		}
		$conn = $this->createMock(TDbConnection::class);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbDriverCapabilities::createScaffoldInput($driver, $conn, self::class);
		$this->assertInstanceOf($expected, $result);
	}

	public function testCreateScaffoldInputUnknownDriverThrowsWhenNoEventHandlers(): void
	{
		// Connection present but raiseEvent returns empty → TConfigurationException.
		$conn = $this->createMock(TDbConnection::class);
		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxActiveRecordCreateScaffoldInput', self::class, $conn)
			->willReturn([]);

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		TDbDriverCapabilities::createScaffoldInput('unknown_driver', $conn, self::class);
	}

	public function testCreateScaffoldInputFxEventRaisedWithCorrectParameters(): void
	{
		// The event must be raised on $connection with ($callerClass, $connection).
		$driver = 'my_custom_driver';
		$conn = $this->createMock(TDbConnection::class);
		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxActiveRecordCreateScaffoldInput', self::class, $conn)
			->willReturn([]);

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		TDbDriverCapabilities::createScaffoldInput($driver, $conn, self::class);
	}

	public function testCreateScaffoldInputFxEventFirstHandlerWins(): void
	{
		// createScaffoldInput uses $instances[0] — the first event result (class name string).
		// Using 'sqlite' as a stand-in: it's a known class with no require_once needed here
		// because TDbDriverCapabilities::createScaffoldInput will instantiate the returned string.
		$conn = $this->createMock(TDbConnection::class);
		$conn->method('raiseEvent')->willReturn([
			\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class,
			\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput::class,
		]);

		$result = TDbDriverCapabilities::createScaffoldInput('custom_driver', $conn, self::class);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class, $result);
	}

	public function testCreateScaffoldInputFxEventReturningObjectThrowsTConfigurationException(): void
	{
		// If a handler accidentally returns an IScaffoldInput instance instead of a class name
		// string, createScaffoldInput must throw to signal the incorrect usage.
		$badReturn = $this->createMock(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\IScaffoldInput::class);

		$conn = $this->createMock(TDbConnection::class);
		$conn->method('raiseEvent')->willReturn([$badReturn]);

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		TDbDriverCapabilities::createScaffoldInput('custom_driver', $conn, self::class);
	}

	// =========================================================================
	//  Cross-method consistency assertions
	// =========================================================================

	public function testSupportsCharsetConsistencyWithOtherMethods(): void
	{
		// IBM has no charset support at all; every charset method must return null/false for ibm.
		$this->assertFalse(TDbDriverCapabilities::supportsCharset(TDbDriver::DRIVER_IBM));
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnParam(TDbDriver::DRIVER_IBM));
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_IBM));
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql(TDbDriver::DRIVER_IBM));
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql(TDbDriver::DRIVER_IBM));
		$this->assertNull(TDbDriverCapabilities::getCharsetQuerySql(TDbDriver::DRIVER_IBM));
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet(TDbDriver::DRIVER_IBM));
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset(TDbDriver::DRIVER_IBM));
	}

	public function testPgsqlCharsetIsPostConnectOnly(): void
	{
		// pgsql: charset is applied after connect via SQL, not via DSN.
		$this->assertTrue(TDbDriverCapabilities::requiresPostConnectCharset(TDbDriver::DRIVER_PGSQL));
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnParam(TDbDriver::DRIVER_PGSQL));
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnPattern(TDbDriver::DRIVER_PGSQL));
		$this->assertNotNull(TDbDriverCapabilities::getCharsetSetSql(TDbDriver::DRIVER_PGSQL));
	}

	public function testFirebirdIsDsnCharsetOnly(): void
	{
		// Firebird: charset is DSN-only; no runtime SQL switching.
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset(TDbDriver::DRIVER_FIREBIRD));
		$this->assertNotNull(TDbDriverCapabilities::getCharsetDsnParam(TDbDriver::DRIVER_FIREBIRD));
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql(TDbDriver::DRIVER_FIREBIRD));
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql(TDbDriver::DRIVER_FIREBIRD));
		$this->assertTrue(TDbDriverCapabilities::supportsCharset(TDbDriver::DRIVER_FIREBIRD));
	}

	public function testSqliteCharsetIsPragmaOnly(): void
	{
		// SQLite: charset via PRAGMA only, no DSN param, no SET NAMES.
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnParam(TDbDriver::DRIVER_SQLITE));
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql(TDbDriver::DRIVER_SQLITE));
		$this->assertNotNull(TDbDriverCapabilities::getCharsetPragmaSql(TDbDriver::DRIVER_SQLITE));
		$this->assertTrue(TDbDriverCapabilities::supportsRuntimeCharsetSet(TDbDriver::DRIVER_SQLITE));
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset(TDbDriver::DRIVER_SQLITE));
	}

	public function testMysqlCharsetIsBothDsnAndRuntime(): void
	{
		// MySQL: charset injected into DSN AND can be changed at runtime via SET NAMES.
		$this->assertNotNull(TDbDriverCapabilities::getCharsetDsnParam(TDbDriver::DRIVER_MYSQL));
		$this->assertNotNull(TDbDriverCapabilities::getCharsetSetSql(TDbDriver::DRIVER_MYSQL));
		$this->assertTrue(TDbDriverCapabilities::supportsRuntimeCharsetSet(TDbDriver::DRIVER_MYSQL));
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset(TDbDriver::DRIVER_MYSQL));
	}

	public function testFirebirdTransactionFlagsConsistency(): void
	{
		// Firebird requires pre/post flush; interbase is NOT aliased for these flags.
		$this->assertTrue(TDbDriverCapabilities::requiresPreBeginTransactionFlush(TDbDriver::DRIVER_FIREBIRD));
		$this->assertTrue(TDbDriverCapabilities::requiresPostTransactionFlush(TDbDriver::DRIVER_FIREBIRD));
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush(TDbDriver::DRIVER_INTERBASE));
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush(TDbDriver::DRIVER_INTERBASE));
	}
}
