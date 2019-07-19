<?php
/**
 * TDbMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common
 */

namespace Prado\Data\Common;

use Prado\Data\Common\Mssql\TMssqlMetaData;
use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\Common\Oracle\TOracleMetaData;
use Prado\Data\Common\Pgsql\TPgsqlMetaData;
use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TDbMetaData is the base class for retrieving metadata information, such as
 * table and columns information, from a database connection.
 *
 * Use the {@link getTableInfo} method to retrieve a table information.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common
 * @since 3.1
 */
abstract class TDbMetaData extends \Prado\TComponent
{
	private $_tableInfoCache = [];
	private $_connection;

	/**
	 * @var array
	 */
	protected static $delimiterIdentifier = ['[', ']', '"', '`', "'"];

	/**
	 * @param TDbConnection $conn database connection.
	 */
	public function __construct($conn)
	{
		$this->_connection = $conn;
	}

	/**
	 * @return TDbConnection database connection.
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * Obtain database specific TDbMetaData class using the driver name of the database connection.
	 * @param TDbConnection $conn database connection.
	 * @return TDbMetaData database specific TDbMetaData.
	 */
	public static function getInstance($conn)
	{
		$conn->setActive(true); //must be connected before retrieving driver name
		$driver = $conn->getDriverName();
		switch (strtolower($driver)) {
			case 'pgsql':
				return new TPgsqlMetaData($conn);
			case 'mysqli':
			case 'mysql':
				return new TMysqlMetaData($conn);
			case 'sqlite': //sqlite 3
			case 'sqlite2': //sqlite 2
				return new TSqliteMetaData($conn);
			case 'mssql': // Mssql driver on windows hosts
			case 'sqlsrv': // sqlsrv driver on windows hosts
			case 'dblib': // dblib drivers on linux (and maybe others os) hosts
				return new TMssqlMetaData($conn);
			case 'oci':
				return new TOracleMetaData($conn);
//			case 'ibm':
//				return new TIbmDb2MetaData($conn);
			default:
				throw new TDbException('ar_invalid_database_driver', $driver);
		}
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
			$tableInfo = $tableName === null ? new $class : $this->createTableInfo($tableName);
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
