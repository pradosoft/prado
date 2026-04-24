<?php

/**
 * TDbDriverCapabilities class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Data\Common\Firebird\TFirebirdMetaData;
use Prado\Data\Common\Ibm\TIbmMetaData;
use Prado\Data\Common\Mssql\TMssqlMetaData;
use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\Common\Oracle\TOracleMetaData;
use Prado\Data\Common\Pgsql\TPgsqlMetaData;
use Prado\Data\Common\Sqlite\TSqliteMetaData;

/**
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
 *    {@see getScaffoldInputClass}
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
	 * to use universal IANA-style names (e.g. 'UTF-8', 'ISO-8859-1') regardless
	 * of the underlying database driver.
	 *
	 * The lookup key is derived by lowercasing $charset and stripping all hyphens,
	 * underscores, and spaces, so 'UTF-8', 'utf8', 'UTF_8', and 'Utf 8' all
	 * resolve to the same entry. If no mapping exists the original $charset string
	 * is returned unchanged, preserving backward compatibility with driver-specific
	 * names already in use.
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
			// canonical_key => [driver => resolved_name, ...]
			// Key = charset lowercased with hyphens, underscores, and spaces removed.
			// Drivers mysql/pgsql/firebird: SQL-level charset names.
			// Drivers sqlite: PRAGMA encoding values (only UTF-8 and UTF-16 variants
			//   are valid; unsupported values are passed through and silently ignored).
			// Drivers oci/sqlsrv/dblib: DSN-parameter charset names.
			'utf8' => [
				TDbDriver::DRIVER_MYSQL => 'utf8mb4',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'UTF8',
				TDbDriver::DRIVER_FIREBIRD => 'UTF8',
				TDbDriver::DRIVER_OCI => 'AL32UTF8',
				TDbDriver::DRIVER_SQLSRV => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'UTF-8',
			],
			'utf8mb4' => [
				TDbDriver::DRIVER_MYSQL => 'utf8mb4',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'UTF8',
				TDbDriver::DRIVER_FIREBIRD => 'UTF8',
				TDbDriver::DRIVER_OCI => 'AL32UTF8',
				TDbDriver::DRIVER_SQLSRV => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'UTF-8',
			],
			'utf16' => [
				TDbDriver::DRIVER_MYSQL => 'utf16',
				TDbDriver::DRIVER_SQLITE => 'UTF-16',
				TDbDriver::DRIVER_FIREBIRD => 'UTF16BE',
				TDbDriver::DRIVER_OCI => 'AL16UTF16',
			],
			'latin1' => [
				TDbDriver::DRIVER_MYSQL => 'latin1',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				// sqlite: PRAGMA encoding does not support latin1; value is passed
				// through and silently ignored (SQLite stores all text in UTF-8/16).
				TDbDriver::DRIVER_PGSQL => 'LATIN1',
				TDbDriver::DRIVER_FIREBIRD => 'ISO8859_1',
				TDbDriver::DRIVER_OCI => 'WE8ISO8859P1',
				TDbDriver::DRIVER_DBLIB => 'ISO-8859-1',
			],
			'iso88591' => 'latin1',
			'latin2' => [
				TDbDriver::DRIVER_MYSQL => 'latin2',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'LATIN2',
				TDbDriver::DRIVER_FIREBIRD => 'ISO8859_2',
				TDbDriver::DRIVER_OCI => 'EE8ISO8859P2',
				TDbDriver::DRIVER_DBLIB => 'ISO-8859-2',
			],
			'iso88592' => 'latin2',
			'ascii' => [
				TDbDriver::DRIVER_MYSQL => 'ascii',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'SQL_ASCII',
				TDbDriver::DRIVER_FIREBIRD => 'ASCII',
				TDbDriver::DRIVER_OCI => 'US7ASCII',
				TDbDriver::DRIVER_DBLIB => 'ASCII',
			],
			'win1250' => [
				TDbDriver::DRIVER_MYSQL => 'cp1250',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'WIN1250',
				TDbDriver::DRIVER_FIREBIRD => 'WIN1250',
				TDbDriver::DRIVER_OCI => 'EE8MSWIN1250',
				TDbDriver::DRIVER_DBLIB => 'CP1250',
			],
			'windows1250' => 'win1250',
			'cp1250' => 'win1250',
			'win1251' => [
				TDbDriver::DRIVER_MYSQL => 'cp1251',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'WIN1251',
				TDbDriver::DRIVER_FIREBIRD => 'WIN1251',
				TDbDriver::DRIVER_OCI => 'CL8MSWIN1251',
				TDbDriver::DRIVER_DBLIB => 'CP1251',
			],
			'windows1251' => 'win1251',
			'cp1251' => 'win1251',
			'win1252' => [
				TDbDriver::DRIVER_MYSQL => 'cp1252',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'WIN1252',
				TDbDriver::DRIVER_FIREBIRD => 'WIN1252',
				TDbDriver::DRIVER_OCI => 'WE8MSWIN1252',
				TDbDriver::DRIVER_DBLIB => 'CP1252',
			],
			'windows1252' => 'win1252',
			'cp1252' => 'win1252',
			'koi8r' => [
				TDbDriver::DRIVER_MYSQL => 'koi8r',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'KOI8R',
				TDbDriver::DRIVER_FIREBIRD => 'KOI8R',
				TDbDriver::DRIVER_OCI => 'CL8KOI8R',
				TDbDriver::DRIVER_DBLIB => 'KOI8-R',
			],
			'koi8u' => [
				TDbDriver::DRIVER_MYSQL => 'koi8u',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_PGSQL => 'KOI8U',
				TDbDriver::DRIVER_FIREBIRD => 'KOI8U',
				TDbDriver::DRIVER_OCI => 'CL8KOI8U',
				TDbDriver::DRIVER_DBLIB => 'KOI8-U',
			],
		];

		$key = strtolower(preg_replace('/[-_ ]+/', '', $charset));

		if (isset($aliases[$key]) && is_string($aliases[$key])) {
			$key = $aliases[$key];
		}

		return $aliases[$key][$driver] ?? $charset;
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
	 * @since 4.3.3
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
	 * when the caller has already embedded one in the DSN.
	 *
	 * @param string $driver PDO driver name
	 * @return null|string case-insensitive regex, e.g. '/[;?]charset\s*=/i', or null
	 */
	public static function getCharsetDsnPattern(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL,
			TDbDriver::DRIVER_FIREBIRD,
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_OCI,
			TDbDriver::DRIVER_DBLIB => '/[;?]charset\s*=/i',
			TDbDriver::DRIVER_SQLSRV => '/[;?]CharacterSet\s*=/i',
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
	//  Transaction model
	// =========================================================================

	/**
	 * Returns true when the driver operates in a "continuing transaction" mode —
	 * meaning the PDO layer always keeps an implicit transaction alive and the
	 * connection never returns to a fully transaction-free state.
	 *
	 * For these drivers, TDbTransaction with Serial=true is appropriate:
	 * it remains valid and ready for re-use after each commit or rollback
	 * rather than becoming inactive.
	 *
	 * pdo_firebird is the canonical example: isc_commit_transaction and
	 * isc_rollback_transaction immediately start a new implicit transaction
	 * before returning, so the connection is always inside a transaction.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function usesSerialTransaction(string $driver): bool
	{
		return $driver === TDbDriver::DRIVER_FIREBIRD || $driver === TDbDriver::DRIVER_INTERBASE;
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
		return $driver !== TDbDriver::DRIVER_SQLITE;
	}

	// =========================================================================
	//  MetaData factory
	// =========================================================================

	/**
	 * Returns the fully-qualified class name of the {@see \Prado\Data\Common\TDbMetaData}
	 * subclass appropriate for the given driver, or null when no built-in handler
	 * exists.
	 *
	 * When null is returned the caller should raise the fxDataGetMetaDataInstance
	 * global event to allow third-party implementations to provide a handler.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @return null|string fully-qualified class name, or null
	 */
	public static function getMetaDataClass(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => TMysqlMetaData::class,
			TDbDriver::DRIVER_SQLITE2,
			TDbDriver::DRIVER_SQLITE => TSqliteMetaData::class,
			TDbDriver::DRIVER_PGSQL => TPgsqlMetaData::class,
			TDbDriver::DRIVER_INTERBASE,
			TDbDriver::DRIVER_FIREBIRD => TFirebirdMetaData::class,
			TDbDriver::DRIVER_DBLIB,
			TDbDriver::DRIVER_SQLSRV => TMssqlMetaData::class,
			TDbDriver::DRIVER_OCI => TOracleMetaData::class,
			TDbDriver::DRIVER_IBM => TIbmMetaData::class,
			default => null,
		};
	}

	// =========================================================================
	//  Scaffold input factory
	// =========================================================================

	/**
	 * Returns the relative file path (relative to the InputBuilder directory) for
	 * the scaffold input class appropriate for the given driver, or null when no
	 * built-in handler exists.
	 *
	 * These files are loaded via require_once rather than PSR-4 autoloading; the
	 * returned path is intended to be appended to __DIR__ inside
	 * {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase}.
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
			TDbDriver::DRIVER_SQLSRV => '/TMssqlScaffoldInput.php',
			TDbDriver::DRIVER_OCI => '/TOracleScaffoldInput.php',
			TDbDriver::DRIVER_IBM => '/TIbmScaffoldInput.php',
			default => null,
		};
	}

	/**
	 * Returns the unqualified class name of the scaffold input builder appropriate
	 * for the given driver, or null when no built-in handler exists.
	 *
	 * When null is returned the caller should raise the
	 * fxActiveRecordCreateScaffoldInput global event to allow third-party
	 * implementations to provide a builder.
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
			TDbDriver::DRIVER_SQLSRV => 'TMssqlScaffoldInput',
			TDbDriver::DRIVER_OCI => 'TOracleScaffoldInput',
			TDbDriver::DRIVER_IBM => 'TIbmScaffoldInput',
			default => null,
		};
	}
}
