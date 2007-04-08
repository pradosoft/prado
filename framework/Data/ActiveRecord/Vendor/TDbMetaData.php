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

	/**
	 * @var TMap column meta data.
	 */
	private $_columns;

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
		$this->_columns=new TMap($cols);
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

	public function getColumnNames()
	{
		return $this->_columns->getKeys();
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
	 * Construct a "pk IN ('key1', 'key2', ...)" criteria.
	 * @param TDbConnection database connection.
	 * @param array values for IN predicate
	 * @param string SQL string for primary keys IN a list.
	 */
	protected function getCompositeKeysCriteria($conn, $values)
	{
		$count = count($this->getPrimaryKeys());
		if($count===0)
			throw new TActiveRecordException('ar_no_primary_key_found',$this->getTableName());
		if(!is_array($values) || count($values) === 0)
			throw new TActiveRecordException('ar_missing_pk_values', $this->getTableName());
		if($count>1 && !is_array($values[0]))
			$values = array($values);
		if($count > 1 && count($values[0]) !== $count)
			throw new TActiveRecordException('ar_pk_value_count_mismatch', $this->getTableName());

		$columns = array();
		foreach($this->getPrimaryKeys() as $key)
			$columns[] = $this->getColumn($key)->getName();
		return '('.implode(', ',$columns).') IN '.$this->quoteTuple($conn, $values);
	}

	/**
	 * @param TDbConnection database connection.
	 * @param array values
	 * @return string quoted recursive tuple values, e.g. "('val1', 'val2')".
	 */
	protected function quoteTuple($conn, $array)
	{
		$data = array();
		foreach($array as $k=>$v)
			$data[] = is_array($v) ? $this->quoteTuple($conn, $v) : $conn->quoteString($v);
		return '('.implode(', ', $data).')';
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
			$value = array_key_exists($i,$values) ? $values[$i] : $values[$key];
			$this->bindValue($command, ':'.$key, $value);
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
	 * Missing properties are assumed to be null.
	 * @param TActiveRecord record object to be inserted.
	 * @return array name value pairs of fields to be added.
	 * @throws TActiveRecordException if property is null and table column is
	 * defined as not null unless primary key column.
	 */
	protected function getInsertableColumns($record)
	{
		$columns = array();
		foreach($this->getColumns() as $name=>$column)
		{
			try
			{
				$value = $record->{$name};
			}
			catch (TInvalidOperationException $e) //ignore missing properties
			{
				$value = null;
			}

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
	 * @param array name value pairs of columns for update.
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
					$this->bindValue($command, $name, $value);
			}
			else
			{
				$index=1;
				foreach($criteria->getParameters() as $value)
					$this->bindValue($command, $index++,$value);
			}
		}
		$command->prepare();
		return $command;
	}

	protected function bindValue($command, $name, $value)
	{
		if(is_bool($value))
			$command->bindValue($name,$value, PDO::PARAM_BOOL);
		else
			$command->bindValue($name,$value);
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
				$this->bindValue($command,$key,$value);
			else
				$this->bindValue($command, $index++,$value);
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
	 * @param string ordering column name
	 * @param string ordering direction
	 * @return string DESC or ASC
	 */
	protected function getOrdering($by, $direction)
	{
		$dir = strtolower($direction) == 'desc' ? 'DESC' : 'ASC';
		return $this->getColumn($by)->getName(). ' '.$dir;
	}
}
?>