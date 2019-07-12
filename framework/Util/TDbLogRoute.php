<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

use Exception;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TDbLogRoute class
 *
 * TDbLogRoute stores log messages in a database table.
 * To specify the database table, set {@link setConnectionID ConnectionID} to be
 * the ID of a {@link TDataSourceConfig} module and {@link setLogTableName LogTableName}.
 * If they are not setting, an SQLite3 database named 'sqlite3.log' will be created and used
 * under the runtime directory.
 *
 * By default, the database table name is 'pradolog'. It has the following structure:
 * <code>
 *	CREATE TABLE pradolog
 *  (
 *		log_id INTEGER NOT NULL PRIMARY KEY,
 *		level INTEGER,
 *		category VARCHAR(128),
 *		logtime VARCHAR(20),
 *		message VARCHAR(255)
 *   );
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Util
 * @since 3.1.2
 */
class TDbLogRoute extends TLogRoute
{
	/**
	 * @var string the ID of TDataSourceConfig module
	 */
	private $_connID = '';
	/**
	 * @var TDbConnection the DB connection instance
	 */
	private $_db;
	/**
	 * @var string name of the DB log table
	 */
	private $_logTable = 'pradolog';
	/**
	 * @var bool whether the log DB table should be created automatically
	 */
	private $_autoCreate = true;

	/**
	 * Destructor.
	 * Disconnect the db connection.
	 */
	public function __destruct()
	{
		if ($this->_db !== null) {
			$this->_db->setActive(false);
		}
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * It initializes the database for logging purpose.
	 * @param TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if the DB table does not exist.
	 */
	public function init($config)
	{
		$db = $this->getDbConnection();
		$db->setActive(true);

		$sql = 'SELECT * FROM ' . $this->_logTable . ' WHERE 0=1';
		try {
			$db->createCommand($sql)->query()->close();
		} catch (Exception $e) {
			// DB table not exists
			if ($this->_autoCreate) {
				$this->createDbTable();
			} else {
				throw new TConfigurationException('db_logtable_inexistent', $this->_logTable);
			}
		}

		parent::init($config);
	}

	/**
	 * Stores log messages into database.
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs)
	{
		$sql = 'INSERT INTO ' . $this->_logTable . '(level, category, logtime, message) VALUES (:level, :category, :logtime, :message)';
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($logs as $log) {
			$command->bindValue(':message', $log[0]);
			$command->bindValue(':level', $log[1]);
			$command->bindValue(':category', $log[2]);
			$command->bindValue(':logtime', $log[3]);
			$command->execute();
		}
	}

	/**
	 * Creates the DB table for storing log messages.
	 * @todo create sequence for PostgreSQL
	 */
	protected function createDbTable()
	{
		$db = $this->getDbConnection();
		$driver = $db->getDriverName();
		$autoidAttributes = '';
		if ($driver === 'mysql') {
			$autoidAttributes = 'AUTO_INCREMENT';
		}

		$sql = 'CREATE TABLE ' . $this->_logTable . ' (
			log_id INTEGER NOT NULL PRIMARY KEY ' . $autoidAttributes . ',
			level INTEGER,
			category VARCHAR(128),
			logtime VARCHAR(20),
			message VARCHAR(255))';
		$db->createCommand($sql)->execute();
	}

	/**
	 * Creates the DB connection.
	 * @throws TConfigurationException if module ID is invalid or empty
	 * @return TDbConnection the created DB connection
	 */
	protected function createDbConnection()
	{
		if ($this->_connID !== '') {
			$config = $this->getApplication()->getModule($this->_connID);
			if ($config instanceof TDataSourceConfig) {
				return $config->getDbConnection();
			} else {
				throw new TConfigurationException('dblogroute_connectionid_invalid', $this->_connID);
			}
		} else {
			$db = new TDbConnection;
			// default to SQLite3 database
			$dbFile = $this->getApplication()->getRuntimePath() . '/sqlite3.log';
			$db->setConnectionString('sqlite:' . $dbFile);
			return $db;
		}
	}

	/**
	 * @return TDbConnection the DB connection instance
	 */
	public function getDbConnection()
	{
		if ($this->_db === null) {
			$this->_db = $this->createDbConnection();
		}
		return $this->_db;
	}

	/**
	 * @return string the ID of a {@link TDataSourceConfig} module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection for this log route.
	 * @param string $value ID of the {@link TDataSourceConfig} module
	 */
	public function setConnectionID($value)
	{
		$this->_connID = $value;
	}

	/**
	 * @return string the name of the DB table to store log content. Defaults to 'pradolog'.
	 * @see setAutoCreateLogTable
	 */
	public function getLogTableName()
	{
		return $this->_logTable;
	}

	/**
	 * Sets the name of the DB table to store log content.
	 * Note, if {@link setAutoCreateLogTable AutoCreateLogTable} is false
	 * and you want to create the DB table manually by yourself,
	 * you need to make sure the DB table is of the following structure:
	 * (key CHAR(128) PRIMARY KEY, value BLOB, expire INT)
	 * @param string $value the name of the DB table to store log content
	 * @see setAutoCreateLogTable
	 */
	public function setLogTableName($value)
	{
		$this->_logTable = $value;
	}

	/**
	 * @return bool whether the log DB table should be automatically created if not exists. Defaults to true.
	 * @see setAutoCreateLogTable
	 */
	public function getAutoCreateLogTable()
	{
		return $this->_autoCreate;
	}

	/**
	 * @param bool $value whether the log DB table should be automatically created if not exists.
	 * @see setLogTableName
	 */
	public function setAutoCreateLogTable($value)
	{
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
	}
}
