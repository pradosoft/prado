<?php
/**
 * TDbMetaDataInspector class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

/**
 * Base class for database meta data inspectors.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
abstract class TDbMetaDataInspector
{
	private $_connection;

	public function __construct($conn)
	{
		$this->setDbConnection($conn);
	}

	/**
	 * @param TDbConnection database connection.
	 */
	public function setDbConnection($conn)
	{
		$this->_connection=$conn;
	}

	/**
	 * @return TDbConnection database connection.
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * @param string table name
	 * @return TDbMetaData table meta data.
	 */
	public function getTableMetaData($table)
	{
		$keys = $this->getConstraintKeys($table);
		$columns = $this->getColumnDefinitions($table);
		return $this->createMetaData($table,$columns,$keys['primary'], $keys['foreign']);
	}

	/**
	 * Get the column definitions for given table.
	 * @param string table name.
	 * @return array column name value pairs of column meta data.
	 */
	abstract protected function getColumnDefinitions($table);

	/**
	 * Gets the primary and foreign key details for the given table.
	 * @param string table name.
	 * @return array key value pairs with keys 'primary' and 'foreign'.
	 */
	abstract protected function getConstraintKeys($table);

	/**
	 * Create a new instance of meta data.
	 * @param string table name
	 * @param array column meta data
	 * @param array primary key meta data
	 * @param array foreign key meta data.
	 * @return TDbMetaData table meta data.
	 */
	abstract protected function createMetaData($table, $columns, $primary, $foreign);
}

?>