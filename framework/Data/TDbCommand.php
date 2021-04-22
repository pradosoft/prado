<?php
/**
 * TDbCommand class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data
 */

namespace Prado\Data;

use Exception;
use PDO;
use PDOStatement;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TDbCommand class.
 *
 * TDbCommand represents an SQL statement to execute against a database.
 * It is usually created by calling {@link TDbConnection::createCommand}.
 * The SQL statement to be executed may be set via {@link setText Text}.
 *
 * To execute a non-query SQL (such as insert, delete, update), call
 * {@link execute}. To execute an SQL statement that returns result data set
 * (such as select), use {@link query} or its convenient versions {@link queryRow}
 * and {@link queryScalar}.
 *
 * If an SQL statement returns results (such as a SELECT SQL), the results
 * can be accessed via the returned {@link TDbDataReader}.
 *
 * TDbCommand supports SQL statment preparation and parameter binding.
 * Call {@link bindParameter} to bind a PHP variable to a parameter in SQL.
 * Call {@link bindValue} to bind a value to an SQL parameter.
 * When binding a parameter, the SQL statement is automatically prepared.
 * You may also call {@link prepare} to explicitly prepare an SQL statement.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Data
 * @since 3.0
 */
class TDbCommand extends \Prado\TComponent
{
	private $_connection;
	private $_text = '';
	private $_statement;

	/**
	 * Constructor.
	 * @param \Prado\Data\TDbConnection $connection the database connection
	 * @param string $text the SQL statement to be executed
	 */
	public function __construct(TDbConnection $connection, $text)
	{
		$this->_connection = $connection;
		$this->setText($text);
		parent::__construct();
	}

	/**
	 * Set the statement to null when serializing.
	 */
	public function __sleep()
	{
		return array_diff(parent::__sleep(), ["\0TDbCommand\0_statement"]);
	}

	/**
	 * @return string the SQL statement to be executed
	 */
	public function getText()
	{
		return $this->_text;
	}

	/**
	 * Specifies the SQL statement to be executed.
	 * Any previous execution will be terminated or cancel.
	 * @param string $value the SQL statement to be executed
	 */
	public function setText($value)
	{
		$this->_text = $value;
		$this->cancel();
	}

	/**
	 * @return \Prado\Data\TDbConnection the connection associated with this command
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return PDOStatement the underlying PDOStatement for this command
	 * It could be null if the statement is not prepared yet.
	 */
	public function getPdoStatement()
	{
		return $this->_statement;
	}

	/**
	 * Prepares the SQL statement to be executed.
	 * For complex SQL statement that is to be executed multiple times,
	 * this may improve performance.
	 * For SQL statement with binding parameters, this method is invoked
	 * automatically.
	 */
	public function prepare()
	{
		if ($this->_statement == null) {
			try {
				$this->_statement = $this->getConnection()->getPdoInstance()->prepare($this->getText());
			} catch (Exception $e) {
				throw new TDbException('dbcommand_prepare_failed', $e->getMessage(), $this->getText());
			}
		}
	}

	/**
	 * Cancels the execution of the SQL statement.
	 */
	public function cancel()
	{
		$this->_statement = null;
	}

	/**
	 * Binds a parameter to the SQL statement to be executed.
	 * @param mixed $name Parameter identifier. For a prepared statement
	 * using named placeholders, this will be a parameter name of
	 * the form :name. For a prepared statement using question mark
	 * placeholders, this will be the 1-indexed position of the parameter.
	 * Unlike {@link bindValue}, the variable is bound as a reference and will
	 * only be evaluated at the time that {@link execute} or {@link query} is called.
	 * @param mixed $value The value to bind to the parameter
	 * @param null|int $dataType SQL data type of the parameter
	 * @param null|int $length length of the data type
	 * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
	 */
	public function bindParameter($name, &$value, $dataType = null, $length = null)
	{
		$this->prepare();
		if ($dataType === null) {
			$this->_statement->bindParam($name, $value);
		} elseif ($length === null) {
			$this->_statement->bindParam($name, $value, $dataType);
		} else {
			$this->_statement->bindParam($name, $value, $dataType, $length);
		}
	}

	/**
	 * Binds a value to a parameter.
	 * @param mixed $name Parameter identifier. For a prepared statement
	 * using named placeholders, this will be a parameter name of
	 * the form :name. For a prepared statement using question mark
	 * placeholders, this will be the 1-indexed position of the parameter.
	 * @param mixed $value The value to bind to the parameter
	 * @param null|int $dataType SQL data type of the parameter
	 * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
	 */
	public function bindValue($name, $value, $dataType = null)
	{
		$this->prepare();
		if ($dataType === null) {
			$this->_statement->bindValue($name, $value);
		} else {
			$this->_statement->bindValue($name, $value, $dataType);
		}
	}

	/**
	 * Executes the SQL statement.
	 * This method is meant only for executing non-query SQL statement.
	 * No result set will be returned.
	 * @throws TDbException execution failed
	 * @return int number of rows affected by the execution.
	 */
	public function execute()
	{
		try {
			// Do not trace because it will remain even in Performance mode
			// Prado::trace('Execute Command: '.$this->getDebugStatementText(), 'Prado\Data');
			if ($this->_statement instanceof PDOStatement) {
				$this->_statement->execute();
				return $this->_statement->rowCount();
			} else {
				return $this->getConnection()->getPdoInstance()->exec($this->getText());
			}
		} catch (Exception $e) {
			throw new TDbException('dbcommand_execute_failed', $e->getMessage(), $this->getDebugStatementText());
		}
	}

	/**
	 * @return String prepared SQL text for debugging purposes.
	 */
	public function getDebugStatementText()
	{
		//if(Prado::getApplication()->getMode() === TApplicationMode::Debug)
		return $this->_statement instanceof PDOStatement ?
				$this->_statement->queryString
				: $this->getText();
	}

	/**
	 * Executes the SQL statement and returns query result.
	 * This method is for executing an SQL query that returns result set.
	 * @throws TDbException execution failed
	 * @return TDbDataReader the reader object for fetching the query result
	 */
	public function query()
	{
		try {
			// Prado::trace('Query: '.$this->getDebugStatementText(), 'Prado\Data');
			if ($this->_statement instanceof PDOStatement) {
				$this->_statement->execute();
			} else {
				$this->_statement = $this->getConnection()->getPdoInstance()->query($this->getText());
			}
			return new TDbDataReader($this);
		} catch (Exception $e) {
			throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
		}
	}

	/**
	 * Executes the SQL statement and returns the first row of the result.
	 * This is a convenient method of {@link query} when only the first row of data is needed.
	 * @param bool $fetchAssociative whether the row should be returned as an associated array with
	 * column names as the keys or the array keys are column indexes (0-based).
	 * @throws TDbException execution failed
	 * @return array the first row of the query result, false if no result.
	 */
	public function queryRow($fetchAssociative = true)
	{
		try {
			// Prado::trace('Query Row: '.$this->getDebugStatementText(), 'Prado\Data');
			if ($this->_statement instanceof PDOStatement) {
				$this->_statement->execute();
			} else {
				$this->_statement = $this->getConnection()->getPdoInstance()->query($this->getText());
			}
			$result = $this->_statement->fetch($fetchAssociative ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
			$this->_statement->closeCursor();
			return $result;
		} catch (Exception $e) {
			throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
		}
	}

	/**
	 * Executes the SQL statement and returns the value of the first column in the first row of data.
	 * This is a convenient method of {@link query} when only a single scalar
	 * value is needed (e.g. obtaining the count of the records).
	 * @throws TDbException execution failed
	 * @return mixed the value of the first column in the first row of the query result. False is returned if there is no value.
	 */
	public function queryScalar()
	{
		try {
			// Prado::trace('Query Scalar: '.$this->getDebugStatementText(), 'Prado\Data');
			if ($this->_statement instanceof PDOStatement) {
				$this->_statement->execute();
			} else {
				$this->_statement = $this->getConnection()->getPdoInstance()->query($this->getText());
			}
			$result = $this->_statement->fetchColumn();
			$this->_statement->closeCursor();
			if (is_resource($result) && get_resource_type($result) === 'stream') {
				return stream_get_contents($result);
			} else {
				return $result;
			}
		} catch (Exception $e) {
			throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
		}
	}

	/**
	 * Executes the SQL statement and returns the first column of the result.
	 * This is a convenient method of {@link query} when only the first column of data is needed.
	 * Note, the column returned will contain the first element in each row of result.
	 * @throws TDbException execution failed
	 * @return array the first column of the query result. Empty array if no result.
	 * @since 3.1.2
	 */
	public function queryColumn()
	{
		$rows = $this->query()->readAll();
		$column = [];
		foreach ($rows as $row) {
			$column[] = current($row);
		}
		return $column;
	}
}
