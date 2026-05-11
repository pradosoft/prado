<?php

/**
 * TDbMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\IDataConnection;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TDbMetaData class
 *
 * TDbMetaData is the abstract base class for all driver-specific database
 * metadata handlers.
 *
 * A metadata handler interrogates a live {@see IDataConnection} and returns
 * structured {@see TDbTableInfo} objects that describe tables, views, and their
 * columns.  It also provides identifier-quoting helpers and a factory for
 * {@see TDbCommandBuilder} instances.
 *
 * ## Driver selection
 *
 * {@see getInstance()} is the normal entry point.  It activates the connection,
 * reads the driver name, and delegates to
 * {@see TDbDriverCapabilities::getMetaDataClass()} to resolve the matching
 * concrete class.  Built-in drivers and their metadata classes:
 *
 * | PDO driver       | Metadata class         |
 * |------------------|------------------------|
 * | `mysql`          | `TMysqlMetaData`       |
 * | `sqlite`         | `TSqliteMetaData`      |
 * | `pgsql`          | `TPgsqlMetaData`       |
 * | `sqlsrv`, `dblib`| `TSqlSrvMetaData`      |
 * | `oci`            | `TOracleMetaData`      |
 * | `ibm`/`db2`      | `TIbmMetaData`         |
 * | `firebird`       | `TFirebirdMetaData`    |
 *
 * When no built-in driver matches, the **`fxDataGetMetaDataClass`** global event
 * is raised on the connection so that third-party extensions can supply a
 * custom handler class.
 *
 * ## Table-info caching
 *
 * {@see getTableInfo()} caches each resolved {@see TDbTableInfo} in a
 * per-instance array for the lifetime of the metadata object, keyed by table
 * name.  Passing `null` as the table name uses the connection string as the
 * cache key and returns an empty table-info object (used in schema-less
 * introspection scenarios).
 *
 * ## Identifier quoting
 *
 * {@see quoteTableName()}, {@see quoteColumnName()}, and
 * {@see quoteColumnAlias()} strip any pre-existing quote characters from the
 * `$delimiterIdentifier` set (`` ` ``, `"`, `'`, `[`, `]`) before wrapping the
 * name in the driver-specific delimiters.  Subclasses pass their delimiter pair
 * as the second and third arguments; the base signatures receive them via
 * `func_get_args()` for backward compatibility.
 *
 * ## Subclass contract
 *
 * Concrete subclasses must implement:
 * - {@see createTableInfo()} — query the live schema and build a fully
 *   populated {@see TDbTableInfo} with all column objects added.
 * - {@see findTableNames()} — return all table names for a given schema.
 *
 * They may also override {@see getTableInfoClass()} to return their driver's
 * {@see TDbTableInfo} subclass name, which {@see getTableInfo()} instantiates
 * when called with `null`.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
abstract class TDbMetaData extends \Prado\TComponent implements IDataMetaData
{
	private $_tableInfoCache = [];
	private $_connection;

	/**
	 * @var array
	 */
	protected static $delimiterIdentifier = ['[', ']', '"', '`', "'"];

	/**
	 * @param \Prado\Data\IDataConnection $connection database connection.
	 */
	public function __construct($connection)
	{
		$this->_connection = $connection;
		parent::__construct();
	}

	/**
	 * @return \Prado\Data\IDataConnection database connection.
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * Obtains a driver-specific TDbMetaData instance for the given connection.
	 *
	 * This method activates the connection, resolves the driver name, and delegates to
	 * {@see TDbDriverCapabilities::getMetaDataClass()} to find the matching handler class.
	 * If no built-in driver is found, the **`fxDataGetMetaDataClass`** global event is
	 * raised on the connection (with the driver name as the parameter) to allow
	 * third-party extensions to supply a custom metadata handler class.
	 *
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @throws TDbException if no metadata handler can be created for the driver.
	 * @return TDbMetaData database-specific TDbMetaData.
	 */
	public static function getInstance($connection)
	{
		$connection->setActive(true); //must be connected before retrieving driver name
		$class = TDbDriverCapabilities::getMetaDataClass($connection);
		if ($class === null) {
			return null;
		}
		$instance = new $class($connection);
		if (!($instance instanceof IDataMetaData)) {
			throw new TDbException('dbmetadata_not_meta_data', $class, IDataMetaData::class);
		}
		return $instance;
	}

	/**
	 * Obtains table meta data information for the current connection and given table name.
	 * @param ?string $tableName table or view name
	 * @return TDbTableInfo table information.
	 */
	public function getTableInfo($tableName = null)
	{
		$key = $tableName === null ? $this->getDbConnection()->getConnectionString() : $tableName;
		if (!isset($this->_tableInfoCache[$key])) {
			$class = $this->getTableInfoClass();
			$class = Prado::usingClass($class);
			if (!is_string($class)) {
				throw new TDbException('dbmetadata_tableinfo_class_invalid', $this->getTableInfoClass());
			}
			$tableInfo = $tableName === null ? new $class() : $this->createTableInfo($tableName);
			$this->_tableInfoCache[$key] = $tableInfo;
		}
		return $this->_tableInfoCache[$key];
	}

	/**
	 * Creates a command builder for a given table name.
	 * @param ?string $tableName table name.
	 * @return TDbCommandBuilder command builder instance for the given table.
	 */
	public function createCommandBuilder($tableName = null)
	{
		return $this->getTableInfo($tableName)->createCommandBuilder($this->getDbConnection());
	}

	/**
	 * This method should be implemented by decendent classes.
	 * @param mixed $tableName
	 * @return TDbTableInfo driver dependent create builder.
	 */
	abstract protected function createTableInfo($tableName);

	/**
	 * @return string TDbTableInfo class name.
	 */
	protected function getTableInfoClass()
	{
		return 'TDbTableInfo';
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string $name table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		$name = str_replace(self::$delimiterIdentifier, '', $name);

		$args = func_get_args();
		$rgt = $lft = $args[1] ?? '';
		$rgt = $args[2] ?? $rgt;

		if (strpos($name, '.') === false) {
			return $lft . $name . $rgt;
		}
		$names = explode('.', $name);
		foreach ($names as &$n) {
			$n = $lft . $n . $rgt;
		}
		return implode('.', $names);
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string $name column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		$args = func_get_args();
		$rgt = $lft = $args[1] ?? '';
		$rgt = $args[2] ?? $rgt;

		return $lft . str_replace(self::$delimiterIdentifier, '', $name) . $rgt;
	}

	/**
	 * Quotes a column alias for use in a query.
	 * @param string $name column alias
	 * @return string the properly quoted column alias
	 */
	public function quoteColumnAlias($name)
	{
		$args = func_get_args();
		$rgt = $lft = $args[1] ?? '';
		$rgt = $args[2] ?? $rgt;

		return $lft . str_replace(self::$delimiterIdentifier, '', $name) . $rgt;
	}

	/**
	 * Returns all table names in the database.
	 * This method should be overridden by child classes in order to support this feature
	 * because the default implementation simply throws an exception.
	 * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
	 * If not empty, the returned table names will be prefixed with the schema name.
	 * @return array all table names in the database.
	 */
	abstract public function findTableNames($schema = '');
}
