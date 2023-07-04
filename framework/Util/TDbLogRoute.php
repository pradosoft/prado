<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Exception;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TLogException;
use Prado\TPropertyValue;

/**
 * TDbLogRoute class
 *
 * TDbLogRoute stores log messages in a database table.
 * To specify the database table, set {@see setConnectionID ConnectionID} to be
 * the ID of a {@see \Prado\Data\TDataSourceConfig} module and {@see setLogTableName LogTableName}.
 * If they are not setting, an SQLite3 database named 'sqlite3.log' will be created and used
 * under the runtime directory.
 *
 * By default, the database table name is 'pradolog'. It has the following structure:
 * ```sql
 *	CREATE TABLE pradolog
 *  (
 *		log_id INTEGER NOT NULL PRIMARY KEY,
 *		level INTEGER,
 *		category VARCHAR(128),
 *		prefix VARCHAR(128),
 *		logtime VARCHAR(20),
 *		message VARCHAR(255)
 *   );
 * ```
 *
 * 4.2.3 Notes: Add the `prefix` to the log table:
 * `ALTER TABLE pradolog ADD COLUMN prefix VARCHAR(128) AFTER category;`
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com>
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
	private bool $_autoCreate = true;
	/**
	 * @var ?float The number of seconds of the log to retain.  Default null for logs are
	 *   not deleted.
	 * @since 4.2.3
	 */
	private ?float $_retainPeriod = null;

	/**
	 * Destructor.
	 * Disconnect the db connection.
	 */
	public function __destruct()
	{
		if ($this->_db !== null) {
			$this->_db->setActive(false);
		}
		parent::__destruct();
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * It initializes the database for logging purpose.
	 * @param \Prado\Xml\TXmlElement $config configuration for this module, can be null
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
				throw new TConfigurationException('dblogroute_table_nonexistent', $this->_logTable);
			}
		}

		parent::init($config);
	}

	/**
	 * Stores log messages into database.
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 * @throws TLogException when the DB insert fails.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$sql = 'INSERT INTO ' . $this->_logTable . '(level, category, prefix, logtime, message) VALUES (:level, :category, :prefix, :logtime, :message)';
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($logs as $log) {
			$command->bindValue(':message', (string) $log[TLogger::LOG_MESSAGE]);
			$command->bindValue(':level', $log[TLogger::LOG_LEVEL]);
			$command->bindValue(':category', $log[TLogger::LOG_CATEGORY]);
			$command->bindValue(':prefix', $this->getLogPrefix($log));
			$command->bindValue(':logtime', sprintf('%F', $log[TLogger::LOG_TIME]));
			if(!$command->execute()) {
				throw new TLogException('dblogroute_insert_failed', $this->_logTable);
			}
		}
		if (!empty($seconds = $this->getRetainPeriod())) {
			$this->deleteDbLog(null, null, null, microtime(true) - $seconds);
		}
	}

	/**
	 * Computes the where SQL clause based upon level, categories, minimum time and maximum time.
	 * @param ?int $level  The bit mask of log levels to search for
	 * @param null|null|array|string $categories The categories to search for.  Strings
	 *   are exploded with ','.
	 * @param ?float $minTime All logs after this time are found
	 * @param ?float $maxTime All logs before this time are found
	 * @param mixed $values the values to fill in.
	 * @return string The where clause for the various SQL statements.
	 * @since 4.2.3
	 */
	protected function getLogWhere(?int $level, null|string|array $categories, ?float $minTime, ?float $maxTime, &$values): string
	{
		$where = '';
		$values = [];
		if ($level !== null) {
			$where .= '((level & :level) > 0)';
			$values[':level'] = $level;
		}
		if ($categories !== null) {
			if(is_string($categories)) {
				$categories = array_map('trim', explode(',', $categories));
			}
			$i = 0;
			$or = '';
			foreach($categories as $category) {
				$c = $category[0] ?? 0;
				if ($c === '!' || $c === '~') {
					if ($where) {
						$where .= ' AND ';
					}
					$category = substr($category, 1);
					$where .= "(category NOT LIKE :category{$i})";
				} else {
					if ($or) {
						$or .= ' OR ';
					}
					$or .= "(category LIKE :category{$i})";
				}
				$category = str_replace('*', '%', $category);
				$values[':category' . ($i++)] = $category;
			}
			if ($or) {
				if ($where) {
					$where .= ' AND ';
				}
				$where .= '(' . $or . ')';
			}
		}
		if ($minTime !== null) {
			if ($where) {
				$where .= ' AND ';
			}
			$where .= 'logtime >= :mintime';
			$values[':mintime'] = sprintf('%F', $minTime);
		}
		if ($maxTime !== null) {
			if ($where) {
				$where .= ' AND ';
			}
			$where .= 'logtime < :maxtime';
			$values[':maxtime'] = sprintf('%F', $maxTime);
		}
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		return $where;
	}

	/**
	 * Gets the number of logs in the database fitting the provided criteria.
	 * @param ?int $level  The bit mask of log levels to search for
	 * @param null|null|array|string $categories The categories to search for.  Strings
	 *   are exploded with ','.
	 * @param ?float $minTime All logs after this time are found
	 * @param ?float $maxTime All logs before this time are found
	 * @return string The where clause for the various SQL statements..
	 * @since 4.2.3
	 */
	public function getDBLogCount(?int $level = null, null|string|array $categories = null, ?float $minTime = null, ?float $maxTime = null)
	{
		$values = [];
		$where = $this->getLogWhere($level, $categories, $minTime, $maxTime, $values);
		$sql = 'SELECT COUNT(*) FROM ' . $this->_logTable . $where;
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($values as $key => $value) {
			$command->bindValue($key, $value);
		}
		return $command->queryScalar();
	}

	/**
	 * Gets the number of logs in the database fitting the provided criteria.
	 * @param ?int $level  The bit mask of log levels to search for
	 * @param null|null|array|string $categories The categories to search for.  Strings
	 *   are exploded with ','.
	 * @param ?float $minTime All logs after this time are found
	 * @param ?float $maxTime All logs before this time are found
	 * @param string $order The order statement.
	 * @param string $limit The limit statement.
	 * @return \Prado\Data\TDbDataReader the logs from the database.
	 * @since 4.2.3
	 */
	public function getDBLogs(?int $level = null, null|string|array $categories = null, ?float $minTime = null, ?float $maxTime = null, string $order = '', string $limit = '')
	{
		$values = [];
		if ($order) {
			$order .= ' ORDER BY ' . $order;
		}
		if ($limit) {
			$limit .= ' LIMIT ' . $limit;
		}
		$where = $this->getLogWhere($level, $categories, $minTime, $maxTime, $values);
		$sql = 'SELECT * FROM ' . $this->_logTable . $where . $order . $limit;
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($values as $key => $value) {
			$command->bindValue($key, $value);
		}
		return $command->query();
	}

	/**
	 * Deletes log items from the database that match the criteria.
	 * @param ?int $level  The bit mask of log levels to search for
	 * @param null|null|array|string $categories The categories to search for.  Strings
	 *   are exploded with ','.
	 * @param ?float $minTime All logs after this time are found
	 * @param ?float $maxTime All logs before this time are found
	 * @return int the number of logs in the database.
	 * @since 4.2.3
	 */
	public function deleteDBLog(?int $level = null, null|string|array $categories = null, ?float $minTime = null, ?float $maxTime = null)
	{
		$values = [];
		$where = $this->getLogWhere($level, $categories, $minTime, $maxTime, $values);
		$sql = 'DELETE FROM ' . $this->_logTable . $where;
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($values as $key => $value) {
			$command->bindValue($key, $value);
		}
		return $command->execute();
	}

	/**
	 * Creates the DB table for storing log messages.
	 */
	protected function createDbTable()
	{
		$db = $this->getDbConnection();
		$driver = $db->getDriverName();
		$autoidAttributes = '';
		if ($driver === 'mysql') {
			$autoidAttributes = 'AUTO_INCREMENT';
		}
		if ($driver === 'pgsql') {
			$param = 'SERIAL';
		} else {
			$param = 'INTEGER NOT NULL';
		}

		$sql = 'CREATE TABLE ' . $this->_logTable . ' (
			log_id ' . $param . ' PRIMARY KEY ' . $autoidAttributes . ',
			level INTEGER,
			category VARCHAR(128),
			prefix VARCHAR(128),
			logtime VARCHAR(20),
			message VARCHAR(255))';
		$db->createCommand($sql)->execute();
	}

	/**
	 * Creates the DB connection.
	 * @throws TConfigurationException if module ID is invalid or empty
	 * @return \Prado\Data\TDbConnection the created DB connection
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
			$db = new TDbConnection();
			// default to SQLite3 database
			$dbFile = $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'sqlite3.log';
			$db->setConnectionString('sqlite:' . $dbFile);
			return $db;
		}
	}

	/**
	 * @return \Prado\Data\TDbConnection the DB connection instance
	 */
	public function getDbConnection()
	{
		if ($this->_db === null) {
			$this->_db = $this->createDbConnection();
		}
		return $this->_db;
	}

	/**
	 * @return string the ID of a {@see \Prado\Data\TDataSourceConfig} module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection for this log route.
	 * @param string $value ID of the {@see \Prado\Data\TDataSourceConfig} module
	 * @return static The current object.
	 */
	public function setConnectionID($value): static
	{
		$this->_connID = $value;

		return $this;
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
	 * Note, if {@see setAutoCreateLogTable AutoCreateLogTable} is false
	 * and you want to create the DB table manually by yourself,
	 * you need to make sure the DB table is of the following structure:
	 * (key CHAR(128) PRIMARY KEY, value BLOB, expire INT)
	 * @param string $value the name of the DB table to store log content
	 * @return static The current object.
	 * @see setAutoCreateLogTable
	 */
	public function setLogTableName($value): static
	{
		$this->_logTable = $value;

		return $this;
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
	 * @return static The current object.
	 * @see setLogTableName
	 */
	public function setAutoCreateLogTable($value): static
	{
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);

		return $this;
	}

	/**
	 * @return ?float The seconds to retain.  Null is no end.
	 * @since 4.2.3
	 */
	public function getRetainPeriod(): ?float
	{
		return $this->_retainPeriod;
	}

	/**
	 * @param null|int|string $value Number of seconds or "PT" period time.
	 * @throws TConfigurationException when the time span is not a valid "PT" string.
	 * @return static The current object.
	 * @since 4.2.3
	 */
	public function setRetainPeriod($value): static
	{
		if (is_numeric($value)) {
			$value = (float) $value;
			if ($value === 0.0) {
				$value = null;
			}
			$this->_retainPeriod = $value;
			return $this;
		}
		if (!($value = TPropertyValue::ensureString($value))) {
			$value = null;
		}
		$seconds = false;
		if ($value && ($seconds = static::timespanToSeconds($value)) === false) {
			throw new TConfigurationException('dblogroute_bad_retain_period', $value);
		}

		$this->_retainPeriod = ($seconds !== false) ? $seconds : $value;

		return $this;
	}

	/**
	 * @param string $timespan The time span to compute the number of seconds.
	 * @retutrn ?int the number of seconds of the time span.
	 * @since 4.2.3
	 */
	public static function timespanToSeconds(string $timespan): ?int
	{
		if (($interval = new \DateInterval($timespan)) === false) {
			return null;
		}

		$datetime1 = new \DateTime();
		$datetime2 = clone $datetime1;
		$datetime2->add($interval);
		$diff = $datetime2->getTimestamp() - $datetime1->getTimestamp();
		return $diff;
	}

}
