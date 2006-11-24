<?php
/**
 * TDbDataReader class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id $
 * @package System.Data
 */

/**
 * TDbDataReader class.
 *
 * TDbDataReader represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data, call {@link read}. The method {@link readAll}
 * returns all the rows in a single array.
 *
 * One can also retrieve the rows of data in TDbDataReader by using foreach:
 * <code>
 * foreach($reader as $row)
 *     // $row represents a row of data
 * </code>
 * Since TDbDataReader is a forward-only stream, you can only traverse it once.
 *
 * It is possible to use a specific mode of data fetching by setting
 * {@link setFetchMode FetchMode}. See {@link http://www.php.net/manual/en/function.pdostatement-setfetchmode.php}
 * for more details.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id $
 * @package System.Data
 * @since 3.0
 */
class TDbDataReader extends TComponent implements Iterator
{
	private $_statement;
	private $_closed=false;
	private $_row;
	private $_index=-1;

	/**
	 * Constructor.
	 * @param TDbCommand the command generating the query result
	 */
	public function __construct(TDbCommand $command)
	{
		$this->_statement=$command->getPdoStatement();
		$this->_statement->setFetchMode(PDO::FETCH_ASSOC);
	}

	/**
	 * Binds a column to a PHP variable.
	 * When rows of data are being fetched, the corresponding column value
	 * will be set in the variable. Note, the fetch mode must include PDO::FETCH_BOUND.
	 * @param mixed Number of the column (1-indexed) or name of the column
	 * in the result set. If using the column name, be aware that the name
	 * should match the case of the column, as returned by the driver.
	 * @param mixed Name of the PHP variable to which the column will be bound.
	 * @param int Data type of the parameter
	 * @see http://www.php.net/manual/en/function.pdostatement-bindcolumn.php
	 */
	public function bindColumn($column, &$value, $dataType=null)
	{
		if($dataType===null)
			$this->_statement->bindColumn($column,$value);
		else
			$this->_statement->bindColumn($column,$value,$dataType);
	}

	/**
	 * @see http://www.php.net/manual/en/function.pdostatement-setfetchmode.php
	 */
	public function setFetchMode($mode)
	{
		$params=func_get_args();
		call_user_func_array(array($this->_statement,'setFetchMode'),$params);
	}

	/**
	 * Advances the reader to the next record in a result set.
	 * @return array|false the current record, false if no more row available
	 */
	public function read()
	{
		return $this->_statement->fetch();
	}

	/**
	 * Reads the whole result set into an array.
	 * @return array|false the result set (each array element represents a row of data), false if no data is available.
	 */
	public function readAll()
	{
		return $this->_statement->fetchAll();
	}

	/**
	 * Advances the reader to the next result when reading the results of a batch of statements.
	 * This method is only useful when there are multiple result sets
	 * returned by the query. Not all DBMS support this feature.
	 */
	public function nextResult()
	{
		return $this->_statement->nextRowset();
	}

	/**
	 * Closes the reader.
	 * Any further data reading will result in an exception.
	 */
	public function close()
	{
		$this->_statement->closeCursor();
		$this->_closed=true;
	}

	/**
	 * @return boolean whether the reader is closed or not.
	 */
	public function getIsClosed()
	{
		return $this->_closed;
	}

	/**
	 * @return boolean whether the result contains any row of data
	 */
	public function getHasRows()
	{
		return $this->getRowCount()>0;
	}

	/**
	 * @return int number of rows contained in the result.
	 * Note, some DBMS may not give a meaningful count.
	 */
	public function getRowCount()
	{
		return $this->_statement->rowCount();
	}

	/**
	 * @return int the number of columns in the result set, 0 if the result is empty
	 */
	public function getColumnCount()
	{
		return $this->_statement->columnCount();
	}

	/**
	 * Resets the iterator to the initial state.
	 * This method is required by the interface Iterator.
	 * @throws TDbException if this method is invoked twice
	 */
	public function rewind()
	{
		if($this->_index<0)
		{
			$this->_row=$this->_statement->fetch();
			$this->_index=0;
		}
		else
			throw new TDbException('dbdatareader_rewind_invalid');
	}

	/**
	 * Returns the index of the current row.
	 * This method is required by the interface Iterator.
	 * @return integer the index of the current row.
	 */
	public function key()
	{
		return $this->_index;
	}

	/**
	 * Returns the current row.
	 * This method is required by the interface Iterator.
	 * @return mixed the current row.
	 */
	public function current()
	{
		return $this->_row;
	}

	/**
	 * Moves the internal pointer to the next row.
	 * This method is required by the interface Iterator.
	 */
	public function next()
	{
		$this->_row=$this->_statement->fetch();
		$this->_index++;
	}

	/**
	 * Returns whether there is a row of data at current position.
	 * This method is required by the interface Iterator.
	 * @return boolean whether there is a row of data at current position.
	 */
	public function valid()
	{
		return $this->_row!==false;
	}
}

?>