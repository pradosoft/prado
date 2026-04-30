<?php

/**
 * TDbMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TDbMetaData is the base class for retrieving metadata information, such as
 * table and columns information, from a database connection.
 *
 * This class provides the foundation for database-specific metadata implementations
 * (e.g., TMysqlMetaData, TSqliteMetaData, TPgsqlMetaData, etc.) that retrieve
 * table and column information from the database.
 *
 * The metadata instances are created via the static {@see getInstance} method which
 * determines the appropriate metadata handler based on the database driver. When no built-in driver
 * matches, the {@see fxDataGetMetaDataInstance()} global event is raised to allow
 * for extensibility through custom implementations.
 *
 * Example usage:
 * ```php
 * $metaData = TDbMetaData::getInstance($connection);
 * $tableInfo = $metaData->getTableInfo('my_table');
 * ```
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
	 * @param \Prado\Data\TDbConnection $conn database connection.
	 */
	public function __construct($conn)
	{
		$this->_connection = $conn;
		parent::__construct();
	}

	/**
	 * @return \Prado\Data\TDbConnection database connection.
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * Obtains a database-specific TDbMetaData class based on the database connection driver.
	 *
	 * This method determines the appropriate metadata handler for the given database driver.
	 * If no built-in driver is found, the {@see fxDataGetMetaDataInstance} global event
	 * is raised to allow custom implementations to provide a metadata handler.
	 *
	 * @param \Prado\Data\TDbConnection $conn database connection.
	 * @throws TDbException if no metadata handler can be created for the driver.
	 * @return TDbMetaData database-specific TDbMetaData.
	 */
	// cubrid, odbc
	public static function getInstance($conn)
	{
		$conn->setActive(true); //must be connected before retrieving driver name
		$driver = strtolower($conn->getDriverName());
		$class = TDbDriverCapabilities::getMetaDataClass($driver, $conn);
		if ($class === null) {
			return null;
		}
		return new $class($conn);
	}

	/**
	 * Obtains table meta data information for the current connection and given table name.
	 * @param null|string $tableName table or view name
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
	 * @param null|string $tableName table name.
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
