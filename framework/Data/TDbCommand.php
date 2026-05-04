<?php

/**
 * TDbCommand class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
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
 * TDbCommand represents a PHP PDO SQL statement to execute against a database.
 * It is usually created by calling {@see \Prado\Data\TDbConnection::createCommand}.
 * The SQL statement to be executed may be set via {@see setText Text}.
 *
 * To execute a non-query SQL (such as insert, delete, update), call
 * {@see execute}. To execute an SQL statement that returns result data set
 * (such as select), use {@see query} or its convenient versions {@see queryRow}
 * and {@see queryScalar}.
 *
 * If an SQL statement returns results (such as a SELECT SQL), the results
 * can be accessed via the returned {@see \Prado\Data\TDbDataReader}.
 *
 * TDbCommand supports SQL statment preparation and parameter binding.
 * Call {@see bindParameter} to bind a PHP variable to a parameter in SQL.
 * Call {@see bindValue} to bind a value to an SQL parameter.
 * When binding a parameter, the SQL statement is automatically prepared.
 * You may also call {@see prepare} to explicitly prepare an SQL statement.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDbCommand extends \Prado\TComponent implements IDataCommand
{
	/** @var TDbConnection The connection of the command. */
	private $_connection;
	/** @var string The sql command. */
	private $_text = '';
	/** @var ?PDOStatement The command statement. */
	private $_statement;

	/**
	 * Constructor.
	 * @param \Prado\Data\TDbConnection $connection the database connection
	 * @param string $text the SQL statement to be executed
	 */
	public function __construct(TDbConnection $connection, $text)
	{
		$this->setConnection($connection);
		$this->setText($text);
		parent::__construct();
	}

	/**
	 * Excludes the prepared {@see PDOStatement} from serialization.
	 * The statement is not serializable and will be recreated on demand
	 * by {@see prepare()} after deserialization.
	 * @param array $exprops by reference, list of property names to exclude.
	 * @since 4.3.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . TDbCommand::class . "\0_statement";
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
	 * @param \Prado\Data\TDbConnection $value the connection associated with this command
	 * @since 4.3.3
	 */
	protected function setConnection($value)
	{
		$this->_connection = $value;
	}

	/**
	 * @return ?PDOStatement the underlying PDOStatement for this command
	 * It could be null if the statement is not prepared yet.
	 */
	public function getPdoStatement()
	{
		return $this->_statement;
	}

	/**
	 * @param ?PDOStatement  $value the underlying PDOStatement for this command
	 * @since 4.3.3
	 */
	protected function setPdoStatement($value)
	{
		$this->_statement = $value;
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
		if ($this->getPdoStatement() == null) {
			try {
				$statement = $this->getConnection()->getPdoInstance()->prepare($this->getText());
				$this->setPdoStatement($statement);
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
		$this->setPdoStatement(null);
	}

	/**
	 * Binds a parameter to the SQL statement to be executed.
	 * @param mixed $name Parameter identifier. For a prepared statement
	 * using named placeholders, this will be a parameter name of
	 * the form :name. For a prepared statement using question mark
	 * placeholders, this will be the 1-indexed position of the parameter.
	 * Unlike {@see bindValue}, the variable is bound as a reference and will
	 * only be evaluated at the time that {@see execute} or {@see query} is called.
	 * @param mixed $value The value to bind to the parameter
	 * @param null|int $dataType SQL data type of the parameter
	 * @param null|int $length length of the data type
	 * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
	 */
	public function bindParameter($name, &$value, $dataType = null, $length = null)
	{
		$this->prepare();
		if ($dataType === null) {
			$this->getPdoStatement()->bindParam($name, $value);
		} elseif ($length === null) {
			$this->getPdoStatement()->bindParam($name, $value, $dataType);
		} else {
			$this->getPdoStatement()->bindParam($name, $value, $dataType, $length);
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
			$this->getPdoStatement()->bindValue($name, $value);
		} else {
			$this->getPdoStatement()->bindValue($name, $value, $dataType);
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
			$statement = $this->getPdoStatement();
			// Do not trace because it will remain even in Performance mode
			// Prado::trace('Execute Command: '.$this->getDebugStatementText(), TDbCommand::class);
			if ($statement instanceof PDOStatement) {
				$statement->execute();
				return $statement->rowCount();
			} else {
				return $this->getConnection()->getPdoInstance()->exec($this->getText());
			}
		} catch (Exception $e) {
			throw new TDbException('dbcommand_execute_failed', $e->getMessage(), $this->getDebugStatementText());
		}
	}

	/**
	 * @return string prepared SQL text for debugging purposes.
	 */
	public function getDebugStatementText()
	{
		$statement = $this->getPdoStatement();
		//if(Prado::getApplication()->getMode() === TApplicationMode::Debug)
		return $statement instanceof PDOStatement ?
				$statement->queryString
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
			$statement = $this->getPdoStatement();
			// Prado::trace('Query: '.$this->getDebugStatementText(), TDbCommand::class);
			if ($statement instanceof PDOStatement) {
				$statement->execute();
			} else {
				$statement = $this->getConnection()->getPdoInstance()->query($this->getText());
				$this->setPdoStatement($statement);
			}
			return new TDbDataReader($this);
		} catch (Exception $e) {
			throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
		}
	}

	/**
	 * Executes the SQL statement and returns the first row of the result.
	 * This is a convenient method of {@see query} when only the first row of data is needed.
	 * @param bool $fetchAssociative whether the row should be returned as an associated array with
	 * column names as the keys or the array keys are column indexes (0-based).
	 * @throws TDbException execution failed
	 * @return array the first row of the query result, false if no result.
	 */
	public function queryRow($fetchAssociative = true)
	{
		try {
			$statement = $this->getPdoStatement();
			// Prado::trace('Query Row: '.$this->getDebugStatementText(), TDbCommand::class);
			if ($statement instanceof PDOStatement) {
				$statement->execute();
			} else {
				$statement = $this->getConnection()->getPdoInstance()->query($this->getText());
				$this->setPdoStatement($statement);
			}
			$result = $statement->fetch($fetchAssociative ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
			$statement->closeCursor();
			return $result;
		} catch (Exception $e) {
			throw new TDbException('dbcommand_query_failed', $e->getMessage(), $this->getDebugStatementText());
		}
	}

	/**
	 * Executes the SQL statement and returns the value of the first column in the first row of data.
	 * This is a convenient method of {@see query} when only a single scalar
	 * value is needed (e.g. obtaining the count of the records).
	 * @throws TDbException execution failed
	 * @return mixed the value of the first column in the first row of the query result. False is returned if there is no value.
	 */
	public function queryScalar()
	{
		try {
			$statement = $this->getPdoStatement();
			// Prado::trace('Query Scalar: '.$this->getDebugStatementText(), TDbCommand::class);
			if ($statement instanceof PDOStatement) {
				$statement->execute();
			} else {
				$statement = $this->getConnection()->getPdoInstance()->query($this->getText());
				$this->setPdoStatement($statement);
			}
			$result = $statement->fetchColumn();
			$statement->closeCursor();
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
	 * This is a convenient method of {@see query} when only the first column of data is needed.
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

	/**
	 * Executes the SQL statement and returns all rows as an array.
	 * This is a convenience method equivalent to calling query()->readAll().
	 * @throws TDbException execution failed
	 * @return array the query result as an array of rows
	 * @since 4.3.3
	 */
	public function queryAll()
	{
		return $this->query()->readAll();
	}
}
