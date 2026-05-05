<?php

/**
 * TDbDriverCapabilities class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TDbException;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\IScaffoldInput;
use Prado\Data\Common\Firebird\TFirebirdMetaData;
use Prado\Data\Common\Ibm\TIbmMetaData;
use Prado\Data\Common\IDataMetaData;
use Prado\Data\Common\SqlSrv\TSqlSrvMetaData;
use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\Common\Oracle\TOracleDbCommand;
use Prado\Data\Common\Oracle\TOracleMetaData;
use Prado\Data\Common\Pgsql\TPgsqlMetaData;
use Prado\Data\Common\Sqlite\TSqliteMetaData;

/**
 * TDbDriverCapabilities class
 *
 * TDbDriverCapabilities centralizes all driver-specific knowledge for the PDO
 * database drivers supported by Prado.
 *
 * All methods are static; the class carries no instance state. Driver string
 * constants are defined in {@see TDbDriver}.
 *
 * This class replaces the driver-branching logic that was previously scattered
 * across {@see TDbConnection}, {@see TDbTransaction},
 * {@see \Prado\Data\Common\TDbMetaData}, and
 * {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase}.
 *
 * Capability groups:
 *  - **Charset resolution** — {@see resolveCharset}, {@see getCharsetSetSql},
 *    {@see getCharsetPragmaSql}, {@see supportsRuntimeCharsetSet},
 *    {@see getCharsetDsnParam}, {@see getCharsetDsnPattern},
 *    {@see getCharsetQuerySql}
 *  - **Transaction flushing** (Firebird implicit-transaction management) —
 *    {@see requiresPreBeginTransactionFlush}, {@see requiresPostTransactionFlush}
 *  - **PDO attribute support** — {@see hasAutoCommitAttribute}
 *  - **MetaData factory** — {@see getMetaDataClass}
 *  - **Scaffold input factory** — {@see getScaffoldInputFile},
 *    {@see getScaffoldInputClass}, {@see createScaffoldInput}
 *
 * ## Extensibility via global fx events
 *
 * Two `fx` global events allow third-party code to extend the built-in driver
 * tables.  Both are raised on the {@see TDbConnection} passed by the caller,
 * but the raising logic is fully encapsulated in this class so callers never
 * need to call `raiseEvent` themselves:
 *
 * - **`fxDataGetMetaDataClass`** — raised by {@see getMetaDataClass} when no
 *   built-in MetaData class is registered for the driver.  Handlers must return
 *   a fully-qualified class name implementing {@see \Prado\Data\Common\IDataMetaData}.
 * - **`fxActiveRecordScaffoldInputClass`** — raised by {@see createScaffoldInput}
 *   when no built-in scaffold input file is registered for the driver.  Handlers
 *   must return the **fully-qualified class name** of a class that implements
 *   {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\IScaffoldInput}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbDriverCapabilities
{
	// =========================================================================
	//  Charset — resolution
	// =========================================================================

	/**
	 * Resolves a charset name to its driver-specific equivalent, allowing callers
	 * to use standard PHP charset names (e.g. 'UTF-8', 'ISO-8859-1') regardless
	 * of the underlying database driver.
	 *
	 * The lookup accepts both the standard PHP charset name (e.g., 'UTF-8') and
	 * the canonical key format (e.g., 'utf8') by normalizing the input. This allows
	 * both {@see \Prado\Data\TDataCharset} constants and raw strings to be used.
	 *
	 * The same table is shared by both SQL-level charset commands
	 * ({@see getCharsetSetSql}) and DSN-parameter injection
	 * ({@see getCharsetDsnParam}), so driver columns for oci, sqlsrv, and dblib
	 * resolve to their DSN-appropriate charset values.
	 *
	 * @param string $charset the charset name as supplied by the caller (e.g. 'UTF-8')
	 * @param string $driver  PDO driver name (e.g. 'mysql', 'pgsql', 'firebird', 'oci')
	 * @return string the charset name appropriate for $driver
	 */
	public static function resolveCharset(string $charset, string $driver): string
	{
		static $driverAliases = [
			TDbDriver::DRIVER_INTERBASE => TDbDriver::DRIVER_FIREBIRD,
		];

		if (isset($driverAliases[$driver])) {
			$driver = $driverAliases[$driver];
		}

		static $aliases = [
			// php_charset => [driver => resolved_name, ...]
			// Key = standard PHP charset name (e.g., 'UTF-8', 'ISO-8859-1').
			// Also supports canonical key lookup via normalization.
			// Drivers mysql/pgsql/firebird: SQL-level charset names.
			// Drivers sqlite: PRAGMA encoding values (only UTF-8 and UTF-16 variants
			//   are valid; unsupported values are passed through and silently ignored).
			// Drivers oci/sqlsrv/dblib: DSN-parameter charset names.
			'utf8' => TDataCharset::UTF8,			// canonical key alias
			'utf8mb4' => TDataCharset::UTF8,		// canonical key alias
			TDataCharset::UTF8 => [
				TDbDriver::DRIVER_MYSQL => 'utf8mb4',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'UTF8',
				TDbDriver::DRIVER_FIREBIRD => 'UTF8',
				TDbDriver::DRIVER_OCI => 'AL32UTF8',
				TDbDriver::DRIVER_SQLSRV => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'UTF-8',
			],

			'utf16' => TDataCharset::UTF16,	// canonical key alias
			TDataCharset::UTF16 => [
				TDbDriver::DRIVER_MYSQL => 'utf16',
				TDbDriver::DRIVER_SQLITE => 'UTF-16',
				TDbDriver::DRIVER_FIREBIRD => 'UTF16BE',
				TDbDriver::DRIVER_OCI => 'AL16UTF16',
			],

			'latin1' => TDataCharset::Latin1,	// canonical key alias
			'iso88591' => TDataCharset::Latin1,	// canonical key alias
			TDataCharset::Latin1 => [
				TDbDriver::DRIVER_MYSQL => 'latin1',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				// sqlite: PRAGMA encoding does not support latin1; value is passed
				// through and silently ignored (SQLite stores all text in UTF-8/16).
				TDbDriver::DRIVER_PGSQL => 'LATIN1',
				TDbDriver::DRIVER_FIREBIRD => 'ISO8859_1',
				TDbDriver::DRIVER_OCI => 'WE8ISO8859P1',
				TDbDriver::DRIVER_DBLIB => 'ISO-8859-1',
			],

			'latin2' => TDataCharset::Latin2,	// canonical key alias
			'iso88592' => TDataCharset::Latin2,	// canonical key alias
			TDataCharset::Latin2 => [
				TDbDriver::DRIVER_MYSQL => 'latin2',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'LATIN2',
				TDbDriver::DRIVER_FIREBIRD => 'ISO8859_2',
				TDbDriver::DRIVER_OCI => 'EE8ISO8859P2',
				TDbDriver::DRIVER_DBLIB => 'ISO-8859-2',
			],

			'ascii' => TDataCharset::ASCII,	// canonical key alias
			TDataCharset::ASCII => [
				TDbDriver::DRIVER_MYSQL => 'ascii',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'SQL_ASCII',
				TDbDriver::DRIVER_FIREBIRD => 'ASCII',
				TDbDriver::DRIVER_OCI => 'US7ASCII',
				TDbDriver::DRIVER_DBLIB => 'ASCII',
			],

			'win1250' => TDataCharset::Win1250,	// canonical key alias
			'windows1250' => TDataCharset::Win1250,	// canonical key alias
			'cp1250' => TDataCharset::Win1250,	// canonical key alias
			TDataCharset::Win1250 => [
				TDbDriver::DRIVER_MYSQL => 'cp1250',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'WIN1250',
				TDbDriver::DRIVER_FIREBIRD => 'WIN1250',
				TDbDriver::DRIVER_OCI => 'EE8MSWIN1250',
				TDbDriver::DRIVER_DBLIB => 'CP1250',
			],

			'win1251' => TDataCharset::Win1251,	// canonical key alias
			'windows1251' => TDataCharset::Win1251,	// canonical key alias
			'cp1251' => TDataCharset::Win1251,	// canonical key alias
			TDataCharset::Win1251 => [
				TDbDriver::DRIVER_MYSQL => 'cp1251',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'WIN1251',
				TDbDriver::DRIVER_FIREBIRD => 'WIN1251',
				TDbDriver::DRIVER_OCI => 'CL8MSWIN1251',
				TDbDriver::DRIVER_DBLIB => 'CP1251',
			],

			'win1252' => TDataCharset::Win1252,	// canonical key alias
			'windows1252' => TDataCharset::Win1252,	// canonical key alias
			'cp1252' => TDataCharset::Win1252,	// canonical key alias
			TDataCharset::Win1252 => [
				TDbDriver::DRIVER_MYSQL => 'cp1252',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'WIN1252',
				TDbDriver::DRIVER_FIREBIRD => 'WIN1252',
				TDbDriver::DRIVER_OCI => 'WE8MSWIN1252',
				TDbDriver::DRIVER_DBLIB => 'CP1252',
			],

			'koi8r' => TDataCharset::KOI8R,	// canonical key alias
			TDataCharset::KOI8R => [
				TDbDriver::DRIVER_MYSQL => 'koi8r',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'KOI8R',
				TDbDriver::DRIVER_FIREBIRD => 'KOI8R',
				TDbDriver::DRIVER_OCI => 'CL8KOI8R',
				TDbDriver::DRIVER_DBLIB => 'KOI8-R',
			],

			'koi8u' => TDataCharset::KOI8U,	// canonical key alias
			TDataCharset::KOI8U => [
				TDbDriver::DRIVER_MYSQL => 'koi8u',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'KOI8U',
				TDbDriver::DRIVER_FIREBIRD => 'KOI8U',
				TDbDriver::DRIVER_OCI => 'CL8KOI8U',
				TDbDriver::DRIVER_DBLIB => 'KOI8-U',
			],
		];

		// Try direct match first (PHP standard charset name)
		if (isset($aliases[$charset])) {
			$key = $aliases[$charset];
			if (is_string($key)) {
				$charset = $key;
			} else {
				return $key[$driver] ?? $charset;
			}
		}

		// Try canonical key format (lowercase, no hyphens/underscores/spaces)
		$key = static::canonicalizeCharset($charset);
		if (isset($aliases[$key])) {
			$charset = is_string($aliases[$key]) ? $aliases[$key] : $key;
		}

		return $aliases[$charset][$driver] ?? $charset;
	}

	/**
	 * Canonicalization involves removing the dashes, underscores, and spaces,
	 * then making the text lower case.  This makes charset values more universal.
	 * @param string $charset The value to canonicalize.
	 * @return string Canonicalized version of the input charset
	 */
	public static function canonicalizeCharset($charset)
	{
		return strtolower(preg_replace('/[-_ ]+/', '', $charset));
	}

	/**
	 * Unresolves a driver-specific charset name back to the standard PHP charset
	 * name used by PRADO (e.g., 'UTF-8', 'ISO-8859-1').
	 *
	 * This is the reciprocal operation of {@see resolveCharset}. It takes a
	 * database-specific charset (e.g., 'utf8mb4' from MySQL, 'AL32UTF8' from Oracle)
	 * and returns the corresponding standard PHP charset name.
	 *
	 * This is useful when the charset is set via DSN and needs to be reflected
	 * back into the {@see \Prado\Data\TDbConnection::getCharset} property.
	 *
	 * @param string $dbCharset the driver-specific charset name (e.g. 'utf8mb4')
	 * @param string $driver    PDO driver name (e.g. 'mysql', 'pgsql', 'oci')
	 * @return string the standard PHP charset name (e.g. 'UTF-8'), or $dbCharset
	 *               if no mapping exists
	 */
	public static function unresolveCharset(string $dbCharset, string $driver): string
	{
		static $driverAliases = [
			TDbDriver::DRIVER_INTERBASE => TDbDriver::DRIVER_FIREBIRD,
		];

		if (isset($driverAliases[$driver])) {
			$driver = $driverAliases[$driver];
		}

		// Build reverse map with TDataCharset constant values
		// Cannot use static variable with class constants in some PHP versions
		$reverseMap = [
			// driver => [db_charset => php_charset, ...]
			// Keys are database-specific charset names
			// Values are TDataCharset constant values (which equal the standard PHP charset name)
			TDbDriver::DRIVER_MYSQL => [
				'utf8mb4' => TDataCharset::UTF8,
				'utf8' => TDataCharset::UTF8,
				'utf16' => TDataCharset::UTF16,
				'latin1' => TDataCharset::Latin1,
				'latin2' => TDataCharset::Latin2,
				'ascii' => TDataCharset::ASCII,
				'cp1250' => TDataCharset::Win1250,
				'cp1251' => TDataCharset::Win1251,
				'cp1252' => TDataCharset::Win1252,
				'koi8r' => TDataCharset::KOI8R,
				'koi8u' => TDataCharset::KOI8U,
			],
			TDbDriver::DRIVER_SQLITE => [
				'UTF-8' => TDataCharset::UTF8,
				'UTF-16' => TDataCharset::UTF16,
			],
			TDbDriver::DRIVER_PGSQL => [
				'UTF8' => TDataCharset::UTF8,
				'UTF16' => TDataCharset::UTF16,
				'LATIN1' => TDataCharset::Latin1,
				'LATIN2' => TDataCharset::Latin2,
				'SQL_ASCII' => TDataCharset::ASCII,
				'WIN1250' => TDataCharset::Win1250,
				'WIN1251' => TDataCharset::Win1251,
				'WIN1252' => TDataCharset::Win1252,
				'KOI8R' => TDataCharset::KOI8R,
				'KOI8U' => TDataCharset::KOI8U,
			],
			TDbDriver::DRIVER_FIREBIRD => [
				'UTF8' => TDataCharset::UTF8,
				'UTF16BE' => TDataCharset::UTF16,
				'ISO8859_1' => TDataCharset::Latin1,
				'ISO8859_2' => TDataCharset::Latin2,
				'ASCII' => TDataCharset::ASCII,
				'WIN1250' => TDataCharset::Win1250,
				'WIN1251' => TDataCharset::Win1251,
				'WIN1252' => TDataCharset::Win1252,
				'KOI8R' => TDataCharset::KOI8R,
				'KOI8U' => TDataCharset::KOI8U,
			],
			TDbDriver::DRIVER_OCI => [
				'AL32UTF8' => TDataCharset::UTF8,
				'AL16UTF16' => TDataCharset::UTF16,
				'WE8ISO8859P1' => TDataCharset::Latin1,
				'EE8ISO8859P2' => TDataCharset::Latin2,
				'US7ASCII' => TDataCharset::ASCII,
				'EE8MSWIN1250' => TDataCharset::Win1250,
				'CL8MSWIN1251' => TDataCharset::Win1251,
				'WE8MSWIN1252' => TDataCharset::Win1252,
				'CL8KOI8R' => TDataCharset::KOI8R,
				'CL8KOI8U' => TDataCharset::KOI8U,
			],
			TDbDriver::DRIVER_SQLSRV => [
				'UTF-8' => TDataCharset::UTF8,
				'ISO-8859-1' => TDataCharset::Latin1,
				'ISO-8859-2' => TDataCharset::Latin2,
				'ASCII' => TDataCharset::ASCII,
				'CP1250' => TDataCharset::Win1250,
				'CP1251' => TDataCharset::Win1251,
				'CP1252' => TDataCharset::Win1252,
				'KOI8-R' => TDataCharset::KOI8R,
				'KOI8-U' => TDataCharset::KOI8U,
			],
			TDbDriver::DRIVER_DBLIB => [
				'UTF-8' => TDataCharset::UTF8,
				'ISO-8859-1' => TDataCharset::Latin1,
				'ISO-8859-2' => TDataCharset::Latin2,
				'ASCII' => TDataCharset::ASCII,
				'CP1250' => TDataCharset::Win1250,
				'CP1251' => TDataCharset::Win1251,
				'CP1252' => TDataCharset::Win1252,
				'KOI8-R' => TDataCharset::KOI8R,
				'KOI8-U' => TDataCharset::KOI8U,
			],
		];

		return $reverseMap[$driver][$dbCharset] ?? $dbCharset;
	}

	// =========================================================================
	//  Charset — runtime SQL command
	// =========================================================================

	/**
	 * Returns the parameterised SQL statement used to set the client charset on
	 * an already-open connection, or null when runtime charset switching is not
	 * supported via a prepared-statement SQL command for the given driver.
	 *
	 * The returned string contains a single positional `?` placeholder for the
	 * resolved charset name and is intended for use with a prepared statement.
	 *
	 * SQLite uses `PRAGMA encoding = <quoted>` which does not accept prepared-
	 * statement parameters; use {@see getCharsetPragmaSql} for that case.
	 *
	 * @param string $driver PDO driver name
	 * @return null|string SQL template with a `?` placeholder, or null
	 */
	public static function getCharsetSetSql(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => 'SET NAMES ?',
			TDbDriver::DRIVER_PGSQL => 'SET client_encoding TO ?',
			default => null,
		};
	}

	/**
	 * Returns the PRAGMA SQL template for setting SQLite's internal encoding, or
	 * null for all other drivers.
	 *
	 * Unlike {@see getCharsetSetSql}, the PRAGMA value cannot use a prepared-
	 * statement placeholder and must be injected via PDO::quote. The returned
	 * string contains a `%s` slot for the already-quoted charset value.
	 *
	 * Note: `PRAGMA encoding` only takes effect before any tables are created;
	 * errors are silently ignored so it is safe to call on any SQLite connection.
	 *
	 * @param string $driver PDO driver name
	 * @return null|string SQL template with a `%s` slot, or null
	 */
	public static function getCharsetPragmaSql(string $driver): ?string
	{
		return $driver === TDbDriver::DRIVER_SQLITE ? 'PRAGMA encoding = %s' : null;
	}

	/**
	 * Returns true when the driver supports changing the connection charset at
	 * runtime (after the connection has been opened).
	 *
	 * MySQL and PostgreSQL accept a SQL command ({@see getCharsetSetSql}).
	 * SQLite accepts `PRAGMA encoding` ({@see getCharsetPragmaSql}) but only
	 * before any tables exist; errors are silently ignored.
	 * All other drivers require the charset to be embedded in the DSN before the
	 * connection is opened ({@see getCharsetDsnParam}).
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function supportsRuntimeCharsetSet(string $driver): bool
	{
		return in_array($driver, [
			TDbDriver::DRIVER_MYSQL,
			TDbDriver::DRIVER_SQLITE,
			TDbDriver::DRIVER_PGSQL,
		], true);
	}

	/**
	 * Returns true when the driver requires a SQL command to be issued
	 * immediately after the connection opens in order to apply the requested
	 * charset.
	 *
	 * PostgreSQL has no DSN charset parameter; its charset can only be set via
	 * {@see getCharsetSetSql} (`SET client_encoding TO ?`) after the connection
	 * is established.
	 *
	 * All other supported drivers that accept a charset either receive it through
	 * the DSN before the connection opens ({@see getCharsetDsnParam} — MySQL,
	 * Firebird, Oracle, sqlsrv, dblib) or handle it implicitly.  SQLite's
	 * `PRAGMA encoding` is an edge-case-only operation that only works on a
	 * brand-new empty database and is not required at open time.
	 *
	 * This method is distinct from {@see supportsRuntimeCharsetSet}, which answers
	 * the broader question of whether the charset can be changed mid-connection.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function requiresPostConnectCharset(string $driver): bool
	{
		return $driver === TDbDriver::DRIVER_PGSQL;
	}

	// =========================================================================
	//  Charset — DSN injection
	// =========================================================================

	/**
	 * Returns the DSN parameter name used to specify the charset for the given
	 * driver, or null when the driver does not accept a charset parameter in the
	 * DSN.
	 *
	 * Drivers that do not support a DSN charset parameter:
	 *   pgsql  — charset is applied after the connection opens via SQL command.
	 *   sqlite — always UTF-8 internally; charset is set via PRAGMA.
	 *   ibm    — IBM DB2 has no charset support via DSN.
	 *
	 * @param string $driver PDO driver name
	 * @return null|string e.g. 'charset', 'CharacterSet', or null
	 */
	public static function getCharsetDsnParam(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL,
			TDbDriver::DRIVER_FIREBIRD,
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_OCI,
			TDbDriver::DRIVER_DBLIB => 'charset',
			TDbDriver::DRIVER_SQLSRV => 'CharacterSet',
			default => null,
		};
	}

	/**
	 * Returns a regex pattern that detects an existing charset directive already
	 * present in a DSN string for the given driver, or null when the driver has
	 * no DSN charset parameter.
	 *
	 * Intended for use with preg_match to avoid injecting a duplicate directive
	 * when the caller has already embedded one in the DSN. The regex does need to
	 * capture the value in the first capture group.
	 *
	 * @param string $driver PDO driver name
	 * @return null|string case-insensitive regex, e.g. '/[;?]charset\s*=\s*([^;]+)/i', or null
	 */
	public static function getCharsetDsnPattern(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL,
			TDbDriver::DRIVER_FIREBIRD,
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_OCI,
			TDbDriver::DRIVER_DBLIB => '/[;?]charset\s*=\s*([^;]+)/i',
			TDbDriver::DRIVER_SQLSRV => '/[;?]CharacterSet\s*=\s*([^;]+)/i',
			default => null,
		};
	}

	// =========================================================================
	//  Charset — discovery query
	// =========================================================================

	/**
	 * Returns the SQL statement that retrieves the charset currently in use on
	 * an active connection, or null when the driver does not support such a query.
	 *
	 * Drivers that configure charset via the DSN at connection time (Oracle, MSSQL
	 * family, IBM DB2) cannot be queried cheaply at runtime; null is returned for
	 * those drivers and callers should fall back to the resolved charset property.
	 *
	 * The Firebird query joins MON$ATTACHMENTS with RDB$CHARACTER_SETS and requires
	 * the MONITOR privilege; callers should catch any exception and fall back to
	 * the resolved charset property when the privilege is absent.
	 *
	 * @param string $driver PDO driver name
	 * @return null|string SQL query string, or null
	 */
	public static function getCharsetQuerySql(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => 'SELECT @@character_set_connection',
			TDbDriver::DRIVER_SQLITE => 'PRAGMA encoding',
			TDbDriver::DRIVER_PGSQL => 'SELECT pg_client_encoding()',
			TDbDriver::DRIVER_FIREBIRD =>
				'SELECT TRIM(c.RDB$CHARACTER_SET_NAME)' .
				'  FROM MON$ATTACHMENTS a' .
				'  JOIN RDB$CHARACTER_SETS c' .
				'    ON c.RDB$CHARACTER_SET_ID = a.MON$CHARACTER_SET_ID' .
				' WHERE a.MON$ATTACHMENT_ID = CURRENT_CONNECTION',
			default => null,
		};
	}

	// =========================================================================
	//  Transaction — Firebird implicit-transaction management
	// =========================================================================

	/**
	 * Returns true when the driver requires that any implicit transaction be
	 * flushed (committed) before {@see TDbConnection::beginTransaction()} calls
	 * PDO::beginTransaction().
	 *
	 * pdo_firebird keeps an implicit transaction alive in autocommit mode. Calling
	 * PDO::beginTransaction() while it is active raises "There is already an
	 * active transaction". Committing it first is the only way to start an
	 * explicit one cleanly. This ensures that the snapshot is current.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function requiresPreBeginTransactionFlush(string $driver): bool
	{
		return $driver === TDbDriver::DRIVER_FIREBIRD;
	}

	/**
	 * Returns true when the driver requires that the implicit transaction started
	 * automatically after a commit or rollback be flushed (committed) immediately,
	 * before the next read is issued on the same connection.
	 *
	 * pdo_firebird starts a new implicit transaction inside isc_commit_transaction
	 * and isc_rollback_transaction before Firebird's Transaction Inventory Page is
	 * fully updated. That implicit transaction's MVCC snapshot can therefore see
	 * stale data. Committing it right away forces pdo_firebird to open a fresh one
	 * whose snapshot correctly reflects the completed operation.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function requiresPostTransactionFlush(string $driver): bool
	{
		return $driver === TDbDriver::DRIVER_FIREBIRD;
	}

	// =========================================================================
	//  ActiveRecord — table enumeration
	// =========================================================================

	/**
	 * Returns the SQL statement that lists all user-defined table names for the
	 * given driver, or null when the driver is not supported.
	 *
	 * The query must return a result set whose first column contains the table
	 * name. Used by the ActiveRecord code-generation action
	 * ({@see \Prado\Shell\Actions\TActiveRecordAction}).
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @return null|string SQL query string, or null
	 */
	public static function getListTablesSql(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => 'SHOW TABLES',
			TDbDriver::DRIVER_SQLITE2,
			TDbDriver::DRIVER_SQLITE => "SELECT DISTINCT tbl_name FROM sqlite_master WHERE tbl_name<>'sqlite_sequence'",
			TDbDriver::DRIVER_PGSQL => "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'",
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_FIREBIRD => "SELECT TRIM(RDB\$RELATION_NAME) AS tbl_name FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL ORDER BY RDB\$RELATION_NAME",
			TDbDriver::DRIVER_DBLIB,
			TDbDriver::DRIVER_SQLSRV => "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'",
			TDbDriver::DRIVER_OCI => 'SELECT table_name FROM user_tables',
			TDbDriver::DRIVER_IBM => "SELECT TABNAME FROM SYSCAT.TABLES WHERE TABSCHEMA = CURRENT SCHEMA AND TYPE = 'T' ORDER BY TABNAME",
			default => null,
		};
	}

	// =========================================================================
	//  PDO attribute support
	// =========================================================================

	/**
	 * Returns true when the driver has any charset support — either runtime SQL
	 * commands ({@see getCharsetSetSql}, {@see getCharsetPragmaSql}) or a DSN
	 * charset parameter ({@see getCharsetDsnParam}).
	 *
	 * IBM DB2 (ibm) has no charset support of any kind and returns false.
	 * All other supported drivers return true.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function supportsCharset(string $driver): bool
	{
		return $driver !== TDbDriver::DRIVER_IBM;
	}

	/**
	 * Returns true when the driver exposes a meaningful PDO::ATTR_AUTOCOMMIT
	 * attribute that can be read and written.
	 *
	 * SQLite does not implement this attribute. Reading or writing it on a SQLite
	 * connection has no effect and should be avoided.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function hasAutoCommitAttribute(string $driver): bool
	{
		return match ($driver) {
			TDbDriver::DRIVER_SQLITE,
			TDbDriver::DRIVER_PGSQL,
			TDbDriver::DRIVER_SQLSRV,
			TDbDriver::DRIVER_DBLIB => false,
			default => true,
		};
	}

	// =========================================================================
	//  Command factory
	// =========================================================================

	/**
	 * Returns the fully-qualified {@see TDbCommand} subclass name appropriate
	 * for the given driver.
	 *
	 * Most drivers use the base {@see TDbCommand} class.  Oracle (pdo_oci) uses
	 * {@see TOracleDbCommand}, which works around the PHP 8.2 pdo_oci segfault
	 * in the prepared-statement path by accumulating bound values and
	 * substituting them via {@see \PDO::quote()} at execution time.
	 *
	 * {@see \Prado\Data\TDbConnection::createCommand()} delegates to this
	 * method to select the right class.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @return string fully-qualified class name
	 */
	public static function getCommandClass(string $driver): string
	{
		return $driver === TDbDriver::DRIVER_OCI ? TOracleDbCommand::class : TDbCommand::class;
	}

	// =========================================================================
	//  MetaData factory
	// =========================================================================

	/**
	 * Returns the fully-qualified class name of the {@see \Prado\Data\Common\TDbMetaData}
	 * subclass appropriate for the given driver.
	 *
	 * For built-in drivers the class name is returned immediately.  When no
	 * built-in class exists and a `$connection` is provided, the
	 * **`fxDataGetMetaDataClass`** global event is raised on `$connection`.
	 * Event handlers must return a fully-qualified class name implementing
	 * {@see \Prado\Data\Common\IDataMetaData}.  The last value in the event
	 * result array is used.
	 *
	 * When no `$connection` is provided and the driver is unknown, `null` is
	 * returned so the caller can decide whether to throw or fall back.
	 *
	 * This method fully encapsulates the `fxDataGetMetaDataClass` event so
	 * callers never need to call `raiseEvent` themselves.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @param ?TDbConnection $connection the active connection; required for the
	 *   event fallback for unknown drivers.
	 * @throws TDbException if the driver is unknown, a connection is provided,
	 *   and no event handler supplies a class name.
	 * @return null|string fully-qualified class name, or null when no connection
	 *   was given and the driver is unknown.
	 */
	public static function getMetaDataClass(string $driver, ?TDbConnection $connection = null): ?string
	{
		$class = match ($driver) {
			TDbDriver::DRIVER_MYSQL => TMysqlMetaData::class,
			TDbDriver::DRIVER_SQLITE2,
			TDbDriver::DRIVER_SQLITE => TSqliteMetaData::class,
			TDbDriver::DRIVER_PGSQL => TPgsqlMetaData::class,
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_FIREBIRD => TFirebirdMetaData::class,
			TDbDriver::DRIVER_DBLIB,
			TDbDriver::DRIVER_SQLSRV => TSqlSrvMetaData::class,
			TDbDriver::DRIVER_OCI => TOracleMetaData::class,
			TDbDriver::DRIVER_IBM => TIbmMetaData::class,
			default => null,
		};

		if ($class !== null || !$connection) {
			return $class;
		}

		$driverClasses = $connection->raiseEvent('fxDataGetMetaDataClass', $connection, $driver);
		if (empty($driverClasses)) {
			throw new TDbException('dbmetadata_invalid_database_driver', $driver);
		}
		$class = array_pop($driverClasses);
		if (!is_string($class) || !is_a($class, IDataMetaData::class, true)) {
			throw new TDbException('dbmetadata_not_meta_data', is_string($class) ? $class : $class::class, IDataMetaData::class);
		}
		return $class;
	}

	// =========================================================================
	//  Scaffold input factory
	// =========================================================================

	/**
	 * Returns the relative file path (relative to the InputBuilder directory) for
	 * the scaffold input class appropriate for the given driver, or null when no
	 * built-in handler exists.
	 *
	 * These files are loaded via `require_once` rather than PSR-4 autoloading.
	 * {@see createScaffoldInput} uses this path together with
	 * {@see getScaffoldInputClass} to load and instantiate the driver-specific
	 * class without going through the `fxActiveRecordScaffoldInputClass` event.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @return null|string e.g. '/TMysqlScaffoldInput.php', or null
	 */
	public static function getScaffoldInputFile(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => '/TMysqlScaffoldInput.php',
			TDbDriver::DRIVER_SQLITE2,
			TDbDriver::DRIVER_SQLITE => '/TSqliteScaffoldInput.php',
			TDbDriver::DRIVER_PGSQL => '/TPgsqlScaffoldInput.php',
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_FIREBIRD => '/TFirebirdScaffoldInput.php',
			TDbDriver::DRIVER_DBLIB,
			TDbDriver::DRIVER_SQLSRV => '/TSqlSrvScaffoldInput.php',
			TDbDriver::DRIVER_OCI => '/TOracleScaffoldInput.php',
			TDbDriver::DRIVER_IBM => '/TIbmScaffoldInput.php',
			default => null,
		};
	}

	/**
	 * Returns the unqualified class name of the scaffold input builder appropriate
	 * for the given driver, or null when no built-in handler exists.
	 *
	 * Use {@see createScaffoldInput} to get a complete scaffold input instance,
	 * including the `fxActiveRecordScaffoldInputClass` event fallback for
	 * unknown drivers.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @return null|string e.g. 'TMysqlScaffoldInput', or null
	 */
	public static function getScaffoldInputClass(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => 'TMysqlScaffoldInput',
			TDbDriver::DRIVER_SQLITE2,
			TDbDriver::DRIVER_SQLITE => 'TSqliteScaffoldInput',
			TDbDriver::DRIVER_PGSQL => 'TPgsqlScaffoldInput',
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_FIREBIRD => 'TFirebirdScaffoldInput',
			TDbDriver::DRIVER_DBLIB,
			TDbDriver::DRIVER_SQLSRV => 'TSqlSrvScaffoldInput',
			TDbDriver::DRIVER_OCI => 'TOracleScaffoldInput',
			TDbDriver::DRIVER_IBM => 'TIbmScaffoldInput',
			default => null,
		};
	}

	/**
	 * Creates and returns a scaffold input builder instance for the given driver.
	 *
	 * For built-in drivers, the appropriate file is loaded via `require_once`
	 * and a new instance of the driver-specific class is returned directly.
	 *
	 * For unknown drivers, the **`fxActiveRecordScaffoldInputClass`** global
	 * event is raised on `$connection`.  Event handlers must return the
	 * **fully-qualified class name** of a class that implements
	 * {@see IScaffoldInput}.  The first value in the event result array is used.
	 *
	 * This method fully encapsulates the `fxActiveRecordScaffoldInputClass`
	 * event so that callers (e.g.
	 * {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase::createInputBuilder})
	 * never need to call `raiseEvent` themselves.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @param TDbConnection $connection the active connection (used when the
	 *   driver is unknown, to raise the extensibility event)
	 * @param string $callerClass passed as the `$sender` argument of the event
	 *   so handlers can identify the originator (typically `static::class`)
	 * @throws TConfigurationException if the driver is unknown and no event
	 *   handler provides a class name, or if a handler returns an
	 *   {@see IScaffoldInput} instance instead of a class name string.
	 * @return IScaffoldInput the scaffold input builder instance.
	 */
	public static function createScaffoldInput(string $driver, TDbConnection $connection, string $callerClass): IScaffoldInput
	{
		$file = static::getScaffoldInputFile($driver);
		$class = static::getScaffoldInputClass($driver);
		if ($file !== null && $class !== null) {
			require_once(__DIR__ . '/ActiveRecord/Scaffold/InputBuilder' . $file);
			return new $class();
		}
		$inputClasses = $connection->raiseEvent('fxActiveRecordScaffoldInputClass', $callerClass, $connection);
		if (empty($inputClasses)) {
			// @todo v4.4 TActiveRecordConfigurationException, move message
			throw new TConfigurationException('ar_invalid_database_driver', $driver);
		}
		$class = $inputClasses[0];
		if (!is_string($class) || !is_a($class, IScaffoldInput::class, true)) {
			// @todo v4.4 TActiveRecordConfigurationException, move message
			throw new TConfigurationException('ar_not_input_base', is_string($class) ? $class : $class::class, IScaffoldInput::class);
		}
		return new $class();
	}
}
