<?php
/**
 * TDbMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

/**
 * Table meta data for Active Record.
 *
 * TDbMetaData is the base class for database vendor specific that builds
 * the appropriate database commands for active record finder and commit methods.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
abstract class TDbMetaData extends TComponent
{
	private $_primaryKeys=array();
	private $_foreignKeys=array();
	private $_columns=array();
	private $_table;
	private $_isView=false;

	/**
	 * Initialize the meta data.
	 * @param string table name
	 * @param array name value pair of column meta data in the table
	 * @param array primary key field names
	 * @param array foriegn key field meta data.
	 */
	public function __construct($table, $cols, $pk, $fk=array(),$view=false)
	{
		$this->_table=$table;
		$this->_columns=$cols;
		$this->_primaryKeys=$pk;
		$this->_foreignKeys=$fk;
		$this->_isView=$view;
	}

	public function getIsView()
	{
		return $this->_isView;
	}

	/**
	 * @return string table name
	 */
	public function getTableName()
	{
		return $this->_table;
	}

	/**
	 * @return array primary key field names.
	 */
	public function getPrimaryKeys()
	{
		return $this->_primaryKeys;
	}

	/**
	 * @return array foreign key meta data.
	 */
	public function getForeignKeys()
	{
		return $this->_foreignKeys;
	}

	/**
	 * @return array name value pair column meta data
	 */
	public function getColumns()
	{
		return $this->_columns;
	}

	/**
	 * @param unknown_type $name
	 */
	public function getColumn($name)
	{
		return $this->_columns[$name];
	}

	/**
	 * Post process the rows after returning from a 1 row query.
	 * @param mixed row data, may be null.
	 * @return mixed processed rows.
	 */
	public function postQueryRow($row)
	{
		return $row;
	}

	/**
	 * Post process the rows after returning from a 1 row query.
	 * @param TDbDataReader multiple row data
	 * @return array post processed data.
	 */
	public function postQuery($rows)
	{
		return $rows;
	}

	/**
	 * @return string command separated list of all fields in the table, field names are quoted.
	 */
	protected function getSelectionColumns()
	{
		$columns = array();
		foreach($this->getColumns() as $column)
			$columns[] = $column->getName();
		return implode(', ', $columns);
	}

	/**
	 * Construct search criteria using primary key names
	 * @return string SQL string for used after WHERE statement.
	 */
	protected function getPrimaryKeyCriteria()
	{
		if(count($this->getPrimaryKeys())===0)
			throw new TActiveRecordException('ar_no_primary_key_found',$this->getTableName());
		$criteria=array();
		foreach($this->getPrimaryKeys() as $key)
			$criteria[] = $this->getColumn($key)->getName(). ' = :'.$key;
		return implode(' AND ', $criteria);
	}

	/**
	 * Bind a list of variables in the command. The named parameters is taken
	 * from the values of the $keys parameter. The bind value is taken from the
	 * $values parameter using the index taken from the each value of $keys array.
	 * @param TDbCommand SQL database command
	 * @param array named parameters
	 * @param array binding values (index should match that of $keys)
	 */
	protected function bindArrayKeyValues($command, $keys, $values)
	{
		if(!is_array($values)) $values = array($values);
		foreach($keys as $i => $key)
		{
			$value = isset($values[$i]) ? $values[$i] : $values[$key];
			$command->bindValue(':'.$key, $value);
		}
		$command->prepare();
	}

	/**
	 * Returns a list of name value pairs from the object.
	 * @param array named parameters
	 * @param TActiveRecord record object
	 * @return array name value pairs.
	 */
	protected function getObjectKeyValues($keys, $object)
	{
		$properties = array();
		foreach($keys as $key)
			$properties[$key] = $object->{$key};
		return $properties;
	}

	/**
	 * Gets the columns that can be inserted into the database.
	 * @param TActiveRecord record object to be inserted.
	 * @return array name value pairs of fields to be added.
	 */
	protected function getInsertableColumns($record)
	{
		$columns = array();
		foreach($this->getColumns() as $name=>$column)
		{
			$value = $record->{$name};
			if($column->getNotNull() && $value===null && !$column->getIsPrimaryKey())
			{
				throw new TActiveRecordException(
					'ar_value_must_not_be_null', get_class($record),
					$this->getTableName(), $name);
			}
			if($value!==null)
				$columns[$name] = $value;
		}
		return $columns;
	}

	/**
	 * Gets the columns that will be updated, it exculdes primary key columns
	 * and record properties that are null.
	 * @param TActiveRecord record object with new data for update.
	 * @return array name value pairs of fields to be updated.
	 */
	protected function getUpdatableColumns($record)
	{
		$columns = array();
		foreach($this->getColumns() as $name => $column)
		{
			$value = $record->{$name};
			if(!$column->getIsPrimaryKey() && $value !== null)
				$columns[$name] = $value;
		}
		return $columns;
	}

	/**
	 * Gets a comma delimited string of name parameters for update.
x	 * @param array name value pairs of columns for update.
	 * @return string update named parameter string.
	 */
	protected function getUpdateBindings($columns)
	{
		$fields = array();
		foreach($columns as $name=>$value)
			$fields[] = $this->getColumn($name)->getName(). '= :'.$name;
		return implode(', ', $fields);
	}

	/**
	 * Create a new database command based on the given $sql and bind the
	 * named parameters given by $names with values corresponding in $values.
	 * @param TDbConnection database connection.
	 * @param string SQL string.
	 * @param array named parameters
	 * @param array matching named parameter values
	 * @return TDbCommand binded command, ready for execution.
	 */
	protected function createBindedCommand($conn, $sql, $names,$values)
	{
		$conn->setActive(true);
		$command = $conn->createCommand($sql);
		$this->bindArrayKeyValues($command,$names,$values);
		return $command;
	}

	/**
	 * Creates a new database command and bind the values from the criteria object.
	 *
	 * @param TDbConnection database connection.
	 * @param string SQL string.
	 * @param TActiveRecordCriteria search criteria
	 * @return TDbCommand binded command.
	 */
	protected function createCriteriaBindedCommand($conn,$sql,$criteria)
	{
		$conn->setActive(true);
		$command = $conn->createCommand($sql);
		if($criteria!==null)
		{
			if($criteria->getIsNamedParameters())
			{
				foreach($criteria->getParameters() as $name=>$value)
					$command->bindValue($name,$value);
			}
			else
			{
				$index=1;
				foreach($criteria->getParameters() as $value)
					$command->bindValue($index++,$value);
			}
		}
		$command->prepare();
		return $command;
	}

	/**
	 * Bind parameter values.
	 */
	protected function bindParameterValues($conn,$command,$parameters)
	{
		$index=1;
		foreach($parameters as $key=>$value)
		{
			if(is_string($key))
				$command->bindValue($key,$value);
			else
				$command->bindValue($index++,$value);
		}
		$command->prepare();
	}

	/**
	 * Gets the comma delimited string of fields name for insert command.
	 */
	protected function getInsertColumNames($columns)
	{
		$fields = array();
		foreach($columns as $name=>$column)
			$fields[] = $this->getColumn($name)->getName();
		return implode(', ', $fields);
	}

	/**
	 * Gets the comma delimited string of name bindings for insert command.
	 */
	protected function getInsertColumnValues($columns)
	{
		$fields = array();
		foreach(array_keys($columns) as $column)
			$fields[] = ':'.$column;
		return implode(', ', $fields);
	}

	/**
	 * @param TDbConnection database connection
	 * @param array primary key values.
	 * @return string delete criteria for multiple scalar primary keys.
	 */
	protected function getDeleteInPkCriteria($conn, $keys)
	{
		$pk = $this->getPrimaryKeys();
		$column = $this->getColumn($pk[0])->getName();
		$values = array();
		foreach($keys as $key)
		{
			if(is_array($key))
			{
				throw new TActiveRecordException('ar_primary_key_is_scalar',
					$this->getTableName(),$column,'array('.implode(', ',$key).')');
			}
			$values[] = $conn->quoteString($key);
		}
		$pks = implode(', ', $values);
		return "$column IN ($pks)";
	}

	/**
	 * @param TDbConnection database connection
	 * @param array primary key values.
	 * @return string delete criteria for multiple composite primary keys.
	 */
	protected function getDeleteMultiplePkCriteria($conn,$pks)
	{
		//check for 1 set composite keys
		if(count($pks)>0 && !is_array($pks[0]))
			$pks = array($pks);
		$conditions=array();
		foreach($pks as $keys)
			$conditions[] = $this->getDeleteCompositeKeyCondition($conn,$keys);
		return implode(' OR ', $conditions);
	}

	/**
	 * @return string delete criteria for 1 composite key.
	 */
	protected function getDeleteCompositeKeyCondition($conn,$keys)
	{
		$condition=array();
		$index = 0;
		foreach($this->getPrimarykeys() as $pk)
		{
			$name = $this->getColumn($pk)->getName();
			$value = isset($keys[$pk]) ? $keys[$pk] : $keys[$index];
			$condition[] = "$name = ".$conn->quoteString($value);
			$index++;
		}
		return '('.implode(' AND ', $condition).')';
	}
}
?>