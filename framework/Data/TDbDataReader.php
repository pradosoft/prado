<?php

/**
 * TDbDataReader class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use PDO;
use PDOStatement;
use Prado\Exceptions\TDbException;

/**
 * TDbDataReader class.
 *
 * TDbDataReader represents a forward-only stream of rows from a query result
 * set.  It implements both {@see IDataReader} and PHP's `Iterator` interface,
 * so rows can be consumed either with the fetch methods or in a `foreach` loop.
 *
 * **Fetch methods:**
 * ```php
 * while ($row = $reader->read()) {
 *     // process $row
 * }
 * // or all at once:
 * $rows = $reader->readAll();
 * ```
 *
 * **Iterator (`foreach`) usage:**
 * ```php
 * foreach ($reader as $index => $row) {
 *     // $index is the 0-based row number, $row is an associative array
 * }
 * ```
 *
 * TDbDataReader is a **forward-only** cursor; it can be iterated only once.
 * Calling `rewind()` (or starting a second `foreach`) after the first row has
 * been fetched throws a {@see TDbException}.
 *
 * The default fetch mode is `PDO::FETCH_ASSOC`.  Use
 * {@see setFetchMode FetchMode} to change it before reading.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDbDataReader extends \Prado\TComponent implements IDataReader
{
	/** @var PDOStatement The PDO statement this reader is consuming. */
	private $_statement;
	/** @var bool Whether the reader has been closed. */
	private $_closed = false;
	/** @var array|false The current row fetched for the Iterator interface. */
	private $_row;
	/** @var int The 0-based index of the current Iterator position; -1 before rewind. */
	private $_index = -1;

	/**
	 * Constructor.
	 * @param TDbCommand $command the command whose result set this reader wraps.
	 */
	public function __construct(TDbCommand $command)
	{
		$statement = $command->getPdoStatement();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		$this->setStatement($statement);
		parent::__construct();
	}

	/**
	 * Excludes the non-serialisable {@see PDOStatement} from serialization.
	 * The statement is not reconstructable after deserialization; the reader
	 * should not be serialized while data is being consumed.
	 * @param array $exprops by reference, list of property names to exclude.
	 * @since 4.3.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . self::class . "\0_statement";
	}

	/**
	 * Returns the underlying PDO statement.
	 *
	 * @return PDOStatement the active PDO statement.
	 * @since 4.3.3
	 */
	public function getStatement(): PDOStatement
	{
		return $this->_statement;
	}

	/**
	 * Sets the underlying PDO statement.
	 *
	 * Called once by the constructor; not intended for external use.
	 *
	 * @param null|PDOStatement $statement the PDO statement to wrap.
	 * @return static
	 * @since 4.3.3
	 */
	protected function setStatement(?PDOStatement $statement): static
	{
		$this->_statement = $statement;
		return $this;
	}

	/**
	 * Binds a column in the result set to a PHP variable.
	 *
	 * On each subsequent call to {@see read}, the bound variable is updated
	 * with the column value.  The active fetch mode must include
	 * `PDO::FETCH_BOUND` for binding to take effect.
	 *
	 * @param int|string $column 1-indexed column number or column name.
	 *   Column names are case-sensitive as returned by the driver.
	 * @param mixed $value the PHP variable to bind.
	 * @param null|int $dataType PDO data type constant for the column.
	 * @see https://www.php.net/manual/en/pdostatement.bindcolumn.php
	 */
	public function bindColumn($column, &$value, $dataType = null)
	{
		if ($dataType === null) {
			$this->getStatement()->bindColumn($column, $value);
		} else {
			$this->getStatement()->bindColumn($column, $value, $dataType);
		}
	}

	/**
	 * Sets the fetch mode for subsequent reads.
	 *
	 * All arguments are forwarded directly to `PDOStatement::setFetchMode`.
	 * The default fetch mode is `PDO::FETCH_ASSOC`, set by the constructor.
	 *
	 * @param mixed ...$args arguments forwarded to PDOStatement::setFetchMode.
	 * @see https://www.php.net/manual/en/pdostatement.setfetchmode.php
	 */
	public function setFetchMode(...$args)
	{
		$this->getStatement()->setFetchMode(...$args);
	}

	/**
	 * Advances the reader to the next row in a result set.
	 * @return array|false the current row as an associative array, or false
	 *   when no more rows are available.
	 */
	public function read()
	{
		return $this->getStatement()->fetch();
	}

	/**
	 * Returns a single column value from the next row of a result set.
	 * @param int $columnIndex 0-based column index.
	 * @return false|mixed the column value, or false when no more rows are available.
	 */
	public function readColumn($columnIndex)
	{
		return $this->getStatement()->fetchColumn($columnIndex);
	}

	/**
	 * Fetches the next row as an object of the given class.
	 *
	 * The column values are mapped to public properties of the class.  Any
	 * columns that do not correspond to a property are silently discarded.
	 *
	 * @param string $className fully-qualified class name to instantiate.
	 * @param array $fields constructor arguments passed to the class constructor
	 *   before the column properties are populated.
	 * @return false|object a populated object of type `$className`, or false
	 *   when no more rows are available.
	 */
	public function readObject($className, $fields)
	{
		return $this->getStatement()->fetchObject($className, $fields);
	}

	/**
	 * Reads all remaining rows into an array.
	 * @return array all remaining rows, each as an associative array.  An
	 *   empty array is returned when no rows remain.
	 */
	public function readAll()
	{
		return $this->getStatement()->fetchAll();
	}

	/**
	 * Advances the reader to the next result set in a multi-statement batch.
	 *
	 * Only useful when the query returned multiple result sets.  Not all
	 * database drivers support this feature.
	 *
	 * @return bool true if there is another result set, false otherwise.
	 */
	public function nextResult()
	{
		return $this->getStatement()->nextRowset();
	}

	/**
	 * Closes the reader and releases the database cursor.
	 *
	 * Any further read calls after closing will return false.
	 */
	public function close()
	{
		$this->getStatement()->closeCursor();
		$this->setIsClosed(true);
	}

	/**
	 * @return bool whether the reader has been closed.
	 */
	public function getIsClosed()
	{
		return $this->_closed;
	}

	/**
	 * Marks the reader as closed or open.
	 *
	 * Managed internally by {@see close()}; not intended for external use.
	 *
	 * @param bool $value true to mark closed, false to mark open.
	 * @since 4.3.3
	 */
	protected function setIsClosed(bool $value): void
	{
		$this->_closed = $value;
	}

	/**
	 * @return int number of rows affected by the last DML statement, or the
	 *   number of rows in the result set for SELECT statements (driver-dependent).
	 *   Note: most drivers do not give a reliable count for SELECT results.
	 *   Use `SELECT COUNT(*) FROM tableName` to obtain an accurate row count.
	 */
	public function getRowCount()
	{
		return $this->getStatement()->rowCount();
	}

	/**
	 * @return int the number of columns in the result set.  Accurate even
	 *   before any rows are fetched.
	 */
	public function getColumnCount()
	{
		return $this->getStatement()->columnCount();
	}

	/**
	 * Initialises the Iterator by fetching the first row.
	 *
	 * This method is required by the `Iterator` interface.  Because
	 * TDbDataReader is a forward-only cursor, `rewind()` may only be called
	 * once.  Calling it a second time (or starting a second `foreach`) throws
	 * a {@see TDbException}.
	 *
	 * @throws TDbException if the reader has already been rewound.
	 */
	public function rewind(): void
	{
		if ($this->_index < 0) {
			$this->_row = $this->getStatement()->fetch();
			$this->_index = 0;
		} else {
			throw new TDbException('dbdatareader_rewind_invalid');
		}
	}

	/**
	 * Returns the 0-based index of the current row.
	 *
	 * This method is required by the `Iterator` interface.
	 *
	 * @return int the current row index.
	 */
	#[\ReturnTypeWillChange]
	public function key()
	{
		return $this->_index;
	}

	/**
	 * Returns the current row.
	 *
	 * This method is required by the `Iterator` interface.
	 *
	 * @return array|false the current row, or false when exhausted.
	 */
	#[\ReturnTypeWillChange]
	public function current()
	{
		return $this->_row;
	}

	/**
	 * Advances the internal cursor to the next row.
	 *
	 * This method is required by the `Iterator` interface.
	 */
	public function next(): void
	{
		$this->_row = $this->getStatement()->fetch();
		$this->_index++;
	}

	/**
	 * Returns whether the current position holds a valid row.
	 *
	 * This method is required by the `Iterator` interface.
	 *
	 * @return bool true while rows remain, false when the result set is
	 *   exhausted.
	 */
	public function valid(): bool
	{
		return $this->_row !== false;
	}
}
