<?php

/**
 * TDbConnection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\TEnumerable;

/**
 * TDbDrivers class
 *
 * @author Brad Anderson <belisoful@icloud.com> Charset.
 * @since 4.3.3
 */
class TDbDriver extends TEnumerable
{
	public const DRIVER_MYSQL = 'mysql';		// MySQL / MariaDB
	//public const DRIVER_MYSQLI 	= 'mysqli';		// separate non-PDO extension
	public const DRIVER_PGSQL = 'pgsql';		// PostgreSQL (charset after connection is started)
	public const DRIVER_SQLITE = 'sqlite';		// SQLite 3 (UTF-8, UTF-16, set charset without tables)
	public const DRIVER_SQLITE2 = 'sqlite2';	// SQLite 2
	//public const DRIVER_MSSQL 	= 'mssql'; 		// separate non-PDO extension
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
	public const DRIVER_MONGO = 'mongo';		// {@see https://github.com/belisoful/prado-mongo }
}
