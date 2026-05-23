<?php

/**
 * TDbConnection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\TEnumerable;

/**
 * TDbDriver class
 *
 * TDbDriver is a static enumeration class that defines PDO database driver constants
 * used throughout the PRADO framework for database connectivity.
 *
 * This class provides standardized string identifiers for all supported PDO database
 * drivers, ensuring consistency across the framework. The constants are used by:
 * - {@see TDbConnection} for establishing database connections
 * - {@see TDbDriverCapabilities} for driver-specific capability lookups
 * - {@see \Prado\Data\Common\TDbMetaData} for metadata handler resolution
 * - {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase} for scaffold generation
 *
 * Each constant value matches the driver name expected by PHP's PDO extension.
 * The class extends {@see TEnumerable} to allow iteration over all driver constants.
 *
 * Supported drivers:
 * - **MySQL/MariaDB**: {@see DRIVER_MYSQL}
 * - **PostgreSQL**: {@see DRIVER_PGSQL}
 * - **SQLite**: {@see DRIVER_SQLITE}, {@see DRIVER_SQLITE2}
 * - **Microsoft SQL Server**: {@see DRIVER_SQLSRV}, {@see DRIVER_DBLIB}
 * - **Oracle**: {@see DRIVER_OCI}
 * - **IBM DB2**: {@see DRIVER_IBM}
 * - **Firebird/Interbase**: {@see DRIVER_FIREBIRD}, {@see DRIVER_INTERBASE}
 * - **MongoDB** (external extension): {@see DRIVER_MONGO}
 *
 * Unsupported drivers (listed for reference): {@see DRIVER_ODBC},
 * {@see DRIVER_CUBRID}, {@see DRIVER_INFORMIX}
 *
 * Unsupported database PHP extensions (listed for reference): {@see EXTENSION_MYSQLI},
 * {@see EXTENSION_MSSQL}
 *
 * Example usage:
 * ```php
 * // Get all driver constants
 * foreach (TDbDriver::getValues() as $driver) {
 *     echo $driver . "\n";
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbDriver extends TEnumerable
{
	public const DRIVER_MYSQL = 'mysql';		// MySQL / MariaDB
	public const DRIVER_PGSQL = 'pgsql';		// PostgreSQL (charset after connection is started)
	public const DRIVER_SQLITE = 'sqlite';		// SQLite 3 (UTF-8, UTF-16, set charset without tables)
	public const DRIVER_SQLITE2 = 'sqlite2';	// SQLite 2
	public const DRIVER_SQLSRV = 'sqlsrv';		// Microsoft SQL Server
	public const DRIVER_DBLIB = 'dblib';		// SQL Server / Sybase (via FreeTDS)
	public const DRIVER_OCI = 'oci';		// Oracle
	public const DRIVER_IBM = 'ibm';		// IBM DB2 (no charset)
	public const DRIVER_FIREBIRD = 'firebird';	// Firebird
	public const DRIVER_INTERBASE = 'interbase';	// Interbase

	// Unsupported, as of 4.3.3
	public const DRIVER_ODBC = 'odbc';		// Generic ODBC (various databases)
	public const DRIVER_CUBRID = 'cubrid';		// CUBRID database
	public const DRIVER_INFORMIX = 'informix';	//

	//	Common
	public const DRIVER_MONGO = 'mongo';		// {@see https://github.com/belisoful/prado-mongo }

	// non-PDO PHP Extensions, included for sql determination.
	public const EXTENSION_MYSQLI = 'mysqli';
	public const EXTENSION_MSSQL = 'mssql';
}
