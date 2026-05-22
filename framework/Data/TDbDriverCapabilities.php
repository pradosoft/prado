<?php

/**
 * TDbDriverCapabilities class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use PDO;
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
 *  - **Unix socket DSN rewriting** — {@see getSocketDsnParam},
 *    {@see getSocketDsnConflictParam}, {@see getSocketDsnParamsToRemove}
 *  - **Post-connect PDO attribute setup** — {@see getPostConnectAttributes}
 *  - **Transaction flushing** (Firebird implicit-transaction management) —
 *    {@see requiresPreBeginTransactionFlush}, {@see requiresPostTransactionFlush}
 *  - **PDO attribute support** — {@see hasAutoCommitAttribute},
 *    {@see requiresUntypedParameters}
 *  - **Post-connect SQL setup** — {@see getPostConnectSql}
 *  - **MetaData factory** — {@see getMetaDataClass}
 *  - **Scaffold input factory** — {@see getScaffoldInputFile},
 *    {@see getScaffoldInputClass}, {@see createScaffoldInput}
 *
 * ## Extensibility via global fx events
 *
 * Two `fx` global events allow third-party code to extend the built-in driver
 * tables.  Both are raised on the connection with the driver name string as
 * the parameter, and the raising logic is fully encapsulated in this class so
 * callers never need to call `raiseEvent` themselves:
 *
 * - **`fxDataGetMetaDataClass`** — raised by {@see getMetaDataClass} when no
 *   built-in MetaData class is registered for the driver.  Sender is the
 *   connection; parameter is the driver name string.  Handlers must return a
 *   fully-qualified class name implementing {@see \Prado\Data\Common\IDataMetaData}.
 *   The last returned value wins.
 * - **`fxActiveRecordScaffoldInputClass`** — raised by {@see createScaffoldInput}
 *   when no built-in scaffold input file is registered for the driver.  Sender
 *   is the connection; parameter is the driver name string.  Handlers must
 *   return the **fully-qualified class name** of a class that implements
 *   {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\IScaffoldInput}.
 *   The first returned value wins.
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
	 * **sqlsrv limitation** — PDO_SQLSRV's `CharacterSet` DSN parameter only
	 * accepts `'UTF-8'` or `'SQLSRV_ENC_CHAR'` (the system ANSI code page).
	 * Non-UTF-8 charsets have no sqlsrv entry in the table and pass through
	 * unchanged; {@see TDbConnection::applyCharsetToDsn} guards against injecting
	 * an unacceptable value via {@see getDsnAcceptedCharsets}.
	 *
	 * **ibm** — IBM DB2 has no charset DSN parameter and is absent from all rows.
	 *
	 * @param string $charset the charset name as supplied by the caller (e.g. 'UTF-8')
	 * @param string $driver  PDO driver name (e.g. 'mysql', 'pgsql', 'firebird', 'oci')
	 * @return string the charset name appropriate for $driver
	 */
	public static function resolveCharset(string $charset, string $driver): string
	{
		static $driverAliases = [
			TDbDriver::DRIVER_INTERBASE => TDbDriver::DRIVER_FIREBIRD,
			TDbDriver::EXTENSION_MYSQLI => TDbDriver::DRIVER_MYSQL,
			TDbDriver::EXTENSION_MSSQL => TDbDriver::DRIVER_SQLSRV,
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
			// Drivers oci/dblib: DSN-parameter charset names.
			// Driver sqlsrv: PDO_SQLSRV only accepts 'UTF-8' or 'SQLSRV_ENC_CHAR'
			//   (system ANSI code page) as the CharacterSet DSN value.  Non-UTF-8
			//   charsets have no sqlsrv entry and pass through unchanged; DSN injection
			//   is guarded by getDsnAcceptedCharsets() in TDbConnection::applyCharsetToDsn.
			// Driver ibm: IBM DB2 has no charset DSN parameter; absent from all rows.
			// Drivers pgsql/dblib/sqlsrv: absent from UTF-16 — PostgreSQL does not
			//   support UTF-16 as a server encoding; FreeTDS and PDO_SQLSRV have no
			//   UTF-16 DSN charset option.
			'utf8' => TDataCharset::UTF8,			// canonical key alias
			'utf8mb4' => TDataCharset::UTF8,		// canonical key alias
			TDataCharset::UTF8 => [
				TDbDriver::DRIVER_FIREBIRD => 'UTF8',
				TDbDriver::DRIVER_MYSQL => 'utf8mb4',
				TDbDriver::DRIVER_OCI => 'AL32UTF8',
				TDbDriver::DRIVER_PGSQL => 'UTF8',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_SQLSRV => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'UTF-8',
			],

			'utf16' => TDataCharset::UTF16,		// canonical key alias
			TDataCharset::UTF16 => [
				// pgsql, sqlsrv, and dblib intentionally absent — see comment above.
				// UTF-16 resolves to the big-endian (or native-endian for SQLite) form
				// for drivers that distinguish endianness; use UTF16LE / UTF16BE for
				// explicit endianness control.
				TDbDriver::DRIVER_FIREBIRD => 'UTF16BE',
				TDbDriver::DRIVER_MYSQL => 'utf16',
				TDbDriver::DRIVER_OCI => 'AL16UTF16',
				TDbDriver::DRIVER_SQLITE => 'UTF-16',
			],

			'utf16le' => TDataCharset::UTF16LE,	// canonical key alias
			TDataCharset::UTF16LE => [
				// Only MySQL and SQLite expose explicit little-endian UTF-16.
				// Firebird UTF16BE-only; Oracle AL16UTF16 is big-endian only.
				// pgsql, sqlsrv, dblib, and ibm do not support UTF-16 at all.
				TDbDriver::DRIVER_MYSQL => 'utf16le',
				TDbDriver::DRIVER_SQLITE => 'UTF-16le',
			],

			'utf16be' => TDataCharset::UTF16BE,	// canonical key alias
			TDataCharset::UTF16BE => [
				// pgsql, sqlsrv, and dblib intentionally absent — see comment above.
				TDbDriver::DRIVER_FIREBIRD => 'UTF16BE',
				TDbDriver::DRIVER_MYSQL => 'utf16',
				TDbDriver::DRIVER_OCI => 'AL16UTF16',
				TDbDriver::DRIVER_SQLITE => 'UTF-16be',
			],

			'latin1' => TDataCharset::Latin1,	// canonical key alias
			'iso88591' => TDataCharset::Latin1,	// canonical key alias
			TDataCharset::Latin1 => [
				TDbDriver::DRIVER_FIREBIRD => 'ISO8859_1',
				TDbDriver::DRIVER_MYSQL => 'latin1',
				TDbDriver::DRIVER_OCI => 'WE8ISO8859P1',
				TDbDriver::DRIVER_PGSQL => 'LATIN1',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'ISO-8859-1',
			],

			'latin2' => TDataCharset::Latin2,	// canonical key alias
			'iso88592' => TDataCharset::Latin2,	// canonical key alias
			TDataCharset::Latin2 => [
				TDbDriver::DRIVER_FIREBIRD => 'ISO8859_2',
				TDbDriver::DRIVER_MYSQL => 'latin2',
				TDbDriver::DRIVER_OCI => 'EE8ISO8859P2',
				TDbDriver::DRIVER_PGSQL => 'LATIN2',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'ISO-8859-2',
			],

			'ascii' => TDataCharset::ASCII,		// canonical key alias ('ASCII' → 'ascii')
			'usascii' => TDataCharset::ASCII,	// canonical key alias ('US-ASCII' → 'usascii')
			TDataCharset::ASCII => [
				TDbDriver::DRIVER_FIREBIRD => 'ASCII',
				TDbDriver::DRIVER_MYSQL => 'ascii',
				TDbDriver::DRIVER_OCI => 'US7ASCII',
				TDbDriver::DRIVER_PGSQL => 'SQL_ASCII',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'ASCII',
			],

			'win1250' => TDataCharset::Win1250,	// canonical key alias
			'windows1250' => TDataCharset::Win1250,	// canonical key alias
			'cp1250' => TDataCharset::Win1250,	// canonical key alias
			TDataCharset::Win1250 => [
				TDbDriver::DRIVER_FIREBIRD => 'WIN1250',
				TDbDriver::DRIVER_MYSQL => 'cp1250',
				TDbDriver::DRIVER_OCI => 'EE8MSWIN1250',
				TDbDriver::DRIVER_PGSQL => 'WIN1250',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'CP1250',
			],

			'win1251' => TDataCharset::Win1251,	// canonical key alias
			'windows1251' => TDataCharset::Win1251,	// canonical key alias
			'cp1251' => TDataCharset::Win1251,	// canonical key alias
			TDataCharset::Win1251 => [
				TDbDriver::DRIVER_FIREBIRD => 'WIN1251',
				TDbDriver::DRIVER_MYSQL => 'cp1251',
				TDbDriver::DRIVER_OCI => 'CL8MSWIN1251',
				TDbDriver::DRIVER_PGSQL => 'WIN1251',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'CP1251',
			],

			'win1252' => TDataCharset::Win1252,	// canonical key alias
			'windows1252' => TDataCharset::Win1252,	// canonical key alias
			'cp1252' => TDataCharset::Win1252,	// canonical key alias
			TDataCharset::Win1252 => [
				TDbDriver::DRIVER_FIREBIRD => 'WIN1252',
				TDbDriver::DRIVER_MYSQL => 'cp1252',
				TDbDriver::DRIVER_OCI => 'WE8MSWIN1252',
				TDbDriver::DRIVER_PGSQL => 'WIN1252',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'CP1252',
			],

			'koi8r' => TDataCharset::KOI8R,	// canonical key alias
			TDataCharset::KOI8R => [
				TDbDriver::DRIVER_FIREBIRD => 'KOI8R',
				TDbDriver::DRIVER_MYSQL => 'koi8r',
				TDbDriver::DRIVER_OCI => 'CL8KOI8R',
				TDbDriver::DRIVER_PGSQL => 'KOI8R',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
				TDbDriver::DRIVER_DBLIB => 'KOI8-R',
			],

			'koi8u' => TDataCharset::KOI8U,	// canonical key alias
			TDataCharset::KOI8U => [
				TDbDriver::DRIVER_FIREBIRD => 'KOI8U',
				TDbDriver::DRIVER_MYSQL => 'koi8u',
				TDbDriver::DRIVER_OCI => 'CL8KOI8U',
				TDbDriver::DRIVER_PGSQL => 'KOI8U',
				TDbDriver::DRIVER_SQLITE => 'UTF-8',
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
			TDbDriver::EXTENSION_MYSQLI => TDbDriver::DRIVER_MYSQL,
			TDbDriver::EXTENSION_MSSQL => TDbDriver::DRIVER_SQLSRV,
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
			TDbDriver::DRIVER_FIREBIRD => [
				'UTF8' => TDataCharset::UTF8,
				'UTF16BE' => TDataCharset::UTF16BE,
				'ISO8859_1' => TDataCharset::Latin1,
				'ISO8859_2' => TDataCharset::Latin2,
				'ASCII' => TDataCharset::ASCII,
				'WIN1250' => TDataCharset::Win1250,
				'WIN1251' => TDataCharset::Win1251,
				'WIN1252' => TDataCharset::Win1252,
				'KOI8R' => TDataCharset::KOI8R,
				'KOI8U' => TDataCharset::KOI8U,
			],
			TDbDriver::DRIVER_MYSQL => [
				'utf8mb4' => TDataCharset::UTF8,
				'utf8' => TDataCharset::UTF8,
				'utf16' => TDataCharset::UTF16BE,
				'utf16le' => TDataCharset::UTF16LE,
				'latin1' => TDataCharset::Latin1,
				'latin2' => TDataCharset::Latin2,
				'ascii' => TDataCharset::ASCII,
				'cp1250' => TDataCharset::Win1250,
				'cp1251' => TDataCharset::Win1251,
				'cp1252' => TDataCharset::Win1252,
				'koi8r' => TDataCharset::KOI8R,
				'koi8u' => TDataCharset::KOI8U,
			],
			TDbDriver::DRIVER_OCI => [
				'AL32UTF8' => TDataCharset::UTF8,
				'AL16UTF16' => TDataCharset::UTF16BE,
				'WE8ISO8859P1' => TDataCharset::Latin1,
				'EE8ISO8859P2' => TDataCharset::Latin2,
				'US7ASCII' => TDataCharset::ASCII,
				'EE8MSWIN1250' => TDataCharset::Win1250,
				'CL8MSWIN1251' => TDataCharset::Win1251,
				'WE8MSWIN1252' => TDataCharset::Win1252,
				'CL8KOI8R' => TDataCharset::KOI8R,
				'CL8KOI8U' => TDataCharset::KOI8U,
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
			TDbDriver::DRIVER_SQLITE => [
				'UTF-8' => TDataCharset::UTF8,
				// PRAGMA encoding = 'UTF-16' stores native-endian; the query
				// always returns the specific endian form, never the bare 'UTF-16' token.
				// Map to the explicit LE/BE constants for precise round-tripping.
				'UTF-16' => TDataCharset::UTF16,
				'UTF-16le' => TDataCharset::UTF16LE,
				'UTF-16be' => TDataCharset::UTF16BE,
			],
			// PDO_SQLSRV's CharacterSet DSN param only accepts 'UTF-8' or
			// 'SQLSRV_ENC_CHAR'; getCharsetQuerySql() returns null so this
			// table is only reached by external callers.  'SQLSRV_ENC_CHAR'
			// cannot be unresolved to a specific charset (system-dependent),
			// so it is omitted and will fall through to the raw value.
			TDbDriver::DRIVER_SQLSRV => [
				'UTF-8' => TDataCharset::UTF8,
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
	 * Returns the SQL template used to set the client charset on an already-open
	 * connection, or null when runtime charset switching is not supported via a
	 * SQL command for the given driver.
	 *
	 * The returned string uses one of two formats depending on the driver:
	 *
	 * - A single positional `?` placeholder (MySQL) — intended for use with a
	 *   PDO prepared statement: `$pdo->prepare($sql)->execute([$charset])`.
	 * - A `%s` sprintf slot (PostgreSQL) — PostgreSQL's `SET` command does not
	 *   accept bind parameters in prepared statements; use exec with a quoted
	 *   value: `$pdo->exec(sprintf($sql, $pdo->quote($charset)))`.
	 *
	 * SQLite uses `PRAGMA encoding = <quoted>` which does not accept prepared-
	 * statement parameters; use {@see getCharsetPragmaSql} for that case.
	 *
	 * @param string $driver PDO driver name
	 * @return ?string SQL template with a `?` or `%s` slot, or null
	 */
	public static function getCharsetSetSql(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => 'SET NAMES ?',
			// PostgreSQL SET does not accept bind parameters; %s is filled via
			// PDO::quote() and exec() in TDbConnection::setConnectionCharset().
			TDbDriver::DRIVER_PGSQL => 'SET client_encoding TO %s',
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
	 * @return ?string SQL template with a `%s` slot, or null
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
	 * SQLite also falls into this category: it has no DSN charset parameter and
	 * applies its encoding via `PRAGMA encoding` ({@see getCharsetPragmaSql}).
	 * The PRAGMA only takes effect on a brand-new database with no tables; on
	 * existing databases it is silently ignored and the stored encoding is
	 * preserved.  Callers must follow up with {@see requiresPostConnectCharsetReadback}
	 * to sync the connection's charset property to the database's actual encoding.
	 *
	 * All other supported drivers that accept a charset receive it through the
	 * DSN before the connection opens ({@see getCharsetDsnParam} — MySQL,
	 * Firebird, Oracle, sqlsrv, dblib).
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

	/**
	 * Returns true when the driver's post-connect charset setup requires a
	 * subsequent read-back query to synchronise the connection's charset
	 * property to the database's actual encoding.
	 *
	 * This is needed for SQLite: `PRAGMA encoding` is silently ignored when
	 * tables already exist (the encoding was fixed at database creation time),
	 * so the property must be updated to reflect reality rather than the
	 * originally requested value.  The read-back uses {@see getCharsetQuerySql}.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 */
	public static function requiresPostConnectCharsetReadback(string $driver): bool
	{
		return $driver === TDbDriver::DRIVER_SQLITE;
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
	 * @return ?string e.g. 'charset', 'CharacterSet', or null
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
	 * @return ?string case-insensitive regex, e.g. '/[;?]charset\s*=\s*([^;]+)/i', or null
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

	/**
	 * Returns the set of charset values that are valid in the DSN `CharacterSet=`
	 * parameter for the given driver, or null when the driver accepts any resolved
	 * charset value (i.e. no allowlist is needed).
	 *
	 * For pdo_sqlsrv the `CharacterSet` DSN parameter only accepts `'UTF-8'` or
	 * `'SQLSRV_ENC_CHAR'` (the Windows system default encoding).  Any other value
	 * will cause the connection to fail, so {@see TDbConnection::applyCharsetToDsn}
	 * must skip injection when the resolved charset is not in this list.
	 *
	 * For all other drivers that accept a charset DSN parameter the driver maps
	 * whatever charset name is returned by {@see resolveCharset}, so no allowlist
	 * is required and null is returned.
	 *
	 * @param string $driver PDO driver name
	 * @return ?array<string> allowlisted DSN charset values, or null if unrestricted
	 */
	public static function getDsnAcceptedCharsets(string $driver): ?array
	{
		return match ($driver) {
			TDbDriver::DRIVER_SQLSRV => ['UTF-8', 'SQLSRV_ENC_CHAR'],
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
	 * Drivers that configure charset via the DSN at connection time (Oracle, SQL Server,
	 * IBM DB2) cannot be queried cheaply at runtime; null is returned for
	 * those drivers and callers should fall back to the resolved charset property.
	 *
	 * The Firebird query joins MON$ATTACHMENTS with RDB$CHARACTER_SETS and requires
	 * the MONITOR privilege; callers should catch any exception and fall back to
	 * the resolved charset property when the privilege is absent.
	 *
	 * @param string $driver PDO driver name
	 * @return ?string SQL query string, or null
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
	 * @return ?string SQL query string, or null
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

	/**
	 * Returns true when the driver requires that all parameter bindings omit an
	 * explicit PDO type token (i.e. the type argument to
	 * {@see \PDOStatement::bindValue} / {@see \PDOStatement::bindParam} must be
	 * left as `null`).
	 *
	 * pdo_ibm only accepts {@see \PDO::PARAM_STR} reliably.  Passing
	 * {@see \PDO::PARAM_INT}, {@see \PDO::PARAM_BOOL}, or {@see \PDO::PARAM_NULL}
	 * raises "Unknown parameter type" errors.  When this method returns true,
	 * {@see \Prado\Data\TDbCommand::getColumnTypeFromValue()} returns `null` as a
	 * signal to callers that the `$type` argument to
	 * {@see \PDOStatement::bindValue()} must be omitted entirely, allowing PDO to
	 * fall back to its default of {@see \PDO::PARAM_STR}.  Callers must check for
	 * a `null` return and branch accordingly; passing `null` directly as the `int
	 * $type` argument coerces to `0` (= {@see \PDO::PARAM_NULL}) and triggers a
	 * deprecation notice in PHP 8.1+.
	 *
	 * @param string $driver PDO driver name
	 * @return bool
	 * @since 4.3.3
	 */
	public static function requiresUntypedParameters(string $driver): bool
	{
		return $driver === TDbDriver::DRIVER_IBM;
	}

	// =========================================================================
	//  Unix socket — DSN rewriting
	// =========================================================================

	/**
	 * Returns the DSN parameter name used to specify a Unix domain socket path
	 * for the given driver, or null when the driver does not support Unix socket
	 * connections via the PDO DSN.
	 *
	 * For MySQL the parameter is injected fresh (prepended after removing the TCP
	 * parameters listed by {@see getSocketDsnParamsToRemove}).  For PostgreSQL the
	 * parameter replaces the existing `host=` value in-place (libpq treats any
	 * `host=` path that starts with `/` as a socket directory).
	 *
	 * Drivers that have no PDO DSN socket parameter (sqlite, oci, sqlsrv, dblib,
	 * firebird, ibm) return null and {@see TDbConnection::applySocketToDsn} leaves
	 * the DSN unchanged.
	 *
	 * @param string $driver PDO driver name
	 * @return ?string the DSN parameter name (e.g. `'unix_socket'`, `'host'`),
	 *   or null when the driver does not support a DSN socket path.
	 * @since 4.3.3
	 */
	public static function getSocketDsnParam(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => 'unix_socket',
			TDbDriver::DRIVER_PGSQL => 'host',
			default => null,
		};
	}

	/**
	 * Returns the DSN parameter name whose presence means the caller has already
	 * embedded a socket directive and the DSN must not be modified (DSN wins),
	 * or null when no such guard is needed for the given driver.
	 *
	 * For MySQL, if the DSN already contains `unix_socket=`, the Socket property
	 * is ignored and the caller-supplied value takes precedence.
	 *
	 * PostgreSQL always replaces (or injects) `host=` regardless of whether a
	 * `host=` is already present, so no conflict guard is needed and null is
	 * returned.
	 *
	 * @param string $driver PDO driver name
	 * @return ?string the parameter name to check (e.g. `'unix_socket'`),
	 *   or null when the DSN should always be rewritten for this driver.
	 * @since 4.3.3
	 */
	public static function getSocketDsnConflictParam(string $driver): ?string
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => 'unix_socket',
			default => null,
		};
	}

	/**
	 * Returns the list of DSN parameter names that must be removed from the DSN
	 * when a Unix socket path is applied, or an empty array when no parameters
	 * need to be stripped.
	 *
	 * For MySQL, `host=` and `port=` are incompatible with `unix_socket=` and
	 * must be removed before the socket parameter is injected.
	 *
	 * For PostgreSQL, `host=` is the socket parameter itself and is replaced
	 * in-place rather than removed, so no extra stripping is needed.
	 *
	 * @param string $driver PDO driver name
	 * @return string[] parameter names to strip (lowercase, no trailing `=`)
	 * @since 4.3.3
	 */
	public static function getSocketDsnParamsToRemove(string $driver): array
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => ['host', 'port'],
			default => [],
		};
	}

	// =========================================================================
	//  Post-connect PDO attribute setup
	// =========================================================================

	/**
	 * Returns the PDO attributes that must be set on the connection object
	 * immediately after it opens, as a map of `PDO::ATTR_*` constant to value.
	 *
	 * **MySQL:** Two attributes are required together for correct behaviour.
	 *
	 * `PDO::ATTR_EMULATE_PREPARES = true` — use the text protocol instead of
	 * MySQL's native binary prepared statements.  The binary protocol strips
	 * ZEROFILL padding server-side and misidentifies ENUM/SET values as
	 * integers, silently corrupting data that PHP code expects to be strings.
	 *
	 * `PDO::ATTR_STRINGIFY_FETCHES = true` — PHP 8.1 broke emulated prepares
	 * by returning native `int`/`float` instead of strings
	 * (see php-src migration81.incompatible).  Without this flag, three column
	 * types silently corrupt data on PHP 8.1+:
	 *   - ZEROFILL: `INT(8)` value 42 returns as `int 42`, not `"00000042"`.
	 *   - BIGINT signed on 32-bit PHP: overflows `PHP_INT_MAX` (2³¹−1).
	 *   - BIGINT UNSIGNED: max 2⁶⁴−1 exceeds `PHP_INT_MAX` (2⁶³−1) on any
	 *     platform.
	 *
	 * Known caveat (php-src #11587, PHP 8.2+): DECIMAL/FLOAT trailing
	 * fractional zeros may still be lost (`"3.60"` → `"3.6"`); this is a PHP
	 * engine bug and cannot be worked around at the PDO layer.
	 *
	 * All other supported drivers require no post-connect attribute setup and
	 * return an empty array.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @return array<int, mixed> map of `PDO::ATTR_*` constant to value
	 * @since 4.3.3
	 */
	public static function getPostConnectAttributes(string $driver): array
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => [
				PDO::ATTR_EMULATE_PREPARES => true,
				PDO::ATTR_STRINGIFY_FETCHES => true,
			],
			default => [],
		};
	}

	// =========================================================================
	//  Session — post-connect SQL setup
	// =========================================================================

	/**
	 * Returns an ordered list of SQL statements that must be executed
	 * immediately after the connection opens to configure the session for
	 * correct behaviour.
	 *
	 * **Oracle (pdo_oci):** PDO passes PHP date strings as plain quoted string
	 * literals. Oracle's default `NLS_DATE_FORMAT` is locale-dependent
	 * (commonly `'DD-MON-RR'`) and rejects ISO 8601 strings such as
	 * `'2005-05-20'` with `ORA-01843` (not a valid month). Setting
	 * `NLS_DATE_FORMAT`, `NLS_TIMESTAMP_FORMAT`, and
	 * `NLS_TIMESTAMP_TZ_FORMAT` to ISO 8601 variants at session level aligns
	 * Oracle's expectation with PHP's standard date representation without
	 * requiring `TO_DATE()` wrappers in every query. `NLS_TIMESTAMP_TZ_FORMAT`
	 * covers `TIMESTAMP WITH TIME ZONE` and `TIMESTAMP WITH LOCAL TIME ZONE`
	 * columns, which the standard Prado data model may use in the future.
	 *
	 * **SQLite:** Foreign key constraint enforcement is disabled by default in
	 * SQLite. `PRAGMA foreign_keys = ON` enables it for this connection, making
	 * FK violations raise an error rather than silently succeeding.  The PRAGMA
	 * must be re-issued on every connection (it is not persisted in the database
	 * file).
	 *
	 * All other supported drivers either enforce foreign keys and use ISO 8601
	 * dates natively (MySQL, PostgreSQL, IBM DB2) or handle them at the driver
	 * level (SQL Server), and return an empty array.
	 *
	 * @param string $driver PDO driver name (lowercase)
	 * @return array<string> SQL statements to execute in order, or empty array if none.
	 */
	public static function getPostConnectSql(string $driver): array
	{
		return match ($driver) {
			TDbDriver::DRIVER_OCI => [
				"ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'",
				"ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'",
				"ALTER SESSION SET NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS TZH:TZM'",
			],
			TDbDriver::DRIVER_SQLITE2,
			TDbDriver::DRIVER_SQLITE => [
				'PRAGMA foreign_keys = ON',
			],
			default => [],
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
	 * built-in class exists and a {@see TDbConnection} is passed, the
	 * **`fxDataGetMetaDataClass`** global event is raised on the connection
	 * with the driver name string as the parameter.  Event handlers must return
	 * a fully-qualified class name implementing
	 * {@see \Prado\Data\Common\IDataMetaData}.  The last value in the event
	 * result array is used.
	 *
	 * When a plain driver-name string is passed and the driver is unknown,
	 * `null` is returned so the caller can decide whether to throw or fall back.
	 *
	 * This method fully encapsulates the `fxDataGetMetaDataClass` event so
	 * callers never need to call `raiseEvent` themselves.
	 *
	 * @param string|TDbConnection $connection the active connection (driver is
	 *   derived via {@see TDbConnection::getDriverName()}), or a bare PDO driver
	 *   name string when only a static lookup is needed (no event fallback).
	 * @throws TDbException if the driver is unknown, a connection is provided,
	 *   and no event handler supplies a class name.
	 * @return ?string fully-qualified class name, or null when a driver string
	 *   was given and the driver is unknown.
	 */
	public static function getMetaDataClass(TDbConnection|string $connection): ?string
	{
		if ($connection instanceof TDbConnection) {
			$driver = strtolower($connection->getDriverName());
		} else {
			$driver = $connection;
			$connection = null;
		}

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
	 * @return ?string e.g. '/TMysqlScaffoldInput.php', or null
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
	 * @return ?string e.g. 'TMysqlScaffoldInput', or null
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
	 * Creates and returns a scaffold input builder instance for the given connection.
	 *
	 * The driver is derived from `$connection->getDriverName()`.  For built-in
	 * drivers, the appropriate file is loaded via `require_once` and a new
	 * instance of the driver-specific class is returned directly.
	 *
	 * For unknown drivers, the **`fxActiveRecordScaffoldInputClass`** global
	 * event is raised on `$connection` with the driver name as the parameter.
	 * Event handlers must return the **fully-qualified class name** of a class
	 * that implements {@see IScaffoldInput}.  The first value in the event
	 * result array is used.
	 *
	 * This method fully encapsulates the `fxActiveRecordScaffoldInputClass`
	 * event so that callers (e.g.
	 * {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase::createInputBuilder})
	 * never need to call `raiseEvent` themselves.
	 *
	 * @param TDbConnection $connection the active connection; the driver name is
	 *   derived via {@see TDbConnection::getDriverName()}.
	 * @throws TConfigurationException if the driver is unknown and no event
	 *   handler provides a class name, or if a handler returns an
	 *   {@see IScaffoldInput} instance instead of a class name string.
	 * @return IScaffoldInput the scaffold input builder instance.
	 */
	public static function createScaffoldInput(TDbConnection $connection): IScaffoldInput
	{
		$driver = strtolower($connection->getDriverName());
		$file = static::getScaffoldInputFile($driver);
		$class = static::getScaffoldInputClass($driver);
		if ($file !== null && $class !== null) {
			require_once(__DIR__ . '/ActiveRecord/Scaffold/InputBuilder' . $file);
			return new $class();
		}
		$inputClasses = $connection->raiseEvent('fxActiveRecordScaffoldInputClass', $connection, $driver);
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
