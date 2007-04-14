<?php
/**
 * TActiveRecordGateway, TActiveRecordStatementType, TActiveRecordGatewayEventParameter classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord
 */

/**
 * TActiveRecordGateway excutes the SQL command queries and returns the data
 * record as arrays (for most finder methods).
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordGateway extends TComponent
{
	private $_manager;
	private $_tables=array(); //table cache
	private $_meta=array(); //meta data cache.
	private $_commandBuilders=array();
	/**
	 * Constant name for specifying optional table name in TActiveRecord.
	 */
	const TABLE_CONST='TABLE';

	/**
	 * Record gateway constructor.
	 * @param TActiveRecordManager $manager
	 */
	public function __construct(TActiveRecordManager $manager)
	{
		$this->_manager=$manager;
	}

	/**
	 * @return TActiveRecordManager record manager.
	 */
	protected function getManager()
	{
		return $this->_manager;
	}

	/**
	 * Gets the table name from the 'TABLE' constant of the active record
	 * class if defined, otherwise use the class name as table name.
	 * @param TActiveRecord active record instance
	 * @return string table name for the given record class.
	 */
	protected function getRecordTableName(TActiveRecord $record)
	{
		$class = new ReflectionClass($record);
		if($class->hasConstant(self::TABLE_CONST))
		{
			$value = $class->getConstant(self::TABLE_CONST);
			if(empty($value))
				throw new TActiveRecordException('ar_invalid_tablename_property',
					get_class($record),self::TABLE_CONST);
			return $value;
		}
		else
			return strtolower(get_class($record));
	}

	/**
	 * Returns table information, trys the application cache first.
	 * @param TActiveRecord $record
	 * @return TDbTableInfo table information.
	 */
	public function getRecordTableInfo(TActiveRecord $record)
	{
		$tableName = $this->getRecordTableName($record);
		return $this->getTableInfo($record->getDbConnection(), $tableName);
	}

	/**
	 * Returns table information for table in the database connection.
	 * @param TDbConnection database connection
	 * @param string table name
	 * @return TDbTableInfo table details.
	 */
	public function getTableInfo($connection, $tableName)
	{
		$connStr = $connection->getConnectionString();
		$key = $connStr.$tableName;
		if(!isset($this->_tables[$key]))
		{
			$tableInfo = null;
			if(($cache=$this->getManager()->getCache())!==null)
				$tableInfo = $cache->get($key);
			if($tableInfo===null)
			{
				if(!isset($this->_meta[$connStr]))
				{
					Prado::using('System.Data.Common.TDbMetaData');
					$this->_meta[$connStr] = TDbMetaData::getMetaData($connection);
				}
				$tableInfo = $this->_meta[$connStr]->getTableInfo($tableName);
			}
			$this->_tables[$key] = $tableInfo;
			if($cache!==null)
				$cache->set($key, $tableInfo);
		}
		return $this->_tables[$key];
	}

	/**
	 * @param TActiveRecord $record
	 * @return TDataGatewayCommand
	 */
	public function getCommand(TActiveRecord $record)
	{
		$conn = $record->getDbConnection();
		$connStr = $conn->getConnectionString();
		$tableInfo = $this->getRecordTableInfo($record);
		if(!isset($this->_commandBuilders[$connStr]))
		{
			$builder = $tableInfo->createCommandBuilder($record->getDbConnection());
			Prado::using('System.Data.DataGateway.TDataGatewayCommand');
			$this->_commandBuilders[$connStr] = new TDataGatewayCommand($builder);
		}
		$this->_commandBuilders[$connStr]->getBuilder()->setTableInfo($tableInfo);

		return $this->_commandBuilders[$connStr];
	}

	/**
	 * Returns record data matching the given primary key(s). If the table uses
	 * composite key, specify the name value pairs as an array.
	 * @param TActiveRecord active record instance.
	 * @param array primary name value pairs
	 * @return array record data
	 */
	public function findRecordByPK(TActiveRecord $record,$keys)
	{
		return $this->getCommand($record)->findByPk($keys);
	}

	/**
	 * Returns records matching the list of given primary keys.
	 * @param TActiveRecord active record instance.
	 * @param array list of primary name value pairs
	 * @return array matching data.
	 */
	public function findRecordsByPks(TActiveRecord $record, $keys)
	{
		return $this->getCommand($record)->findAllByPk($keys);
	}


	/**
	 * Returns record data matching the given critera. If $iterator is true, it will
	 * return multiple rows as TDbDataReader otherwise it returns the <b>first</b> row data.
	 * @param TActiveRecord active record finder instance.
	 * @param TActiveRecordCriteria search criteria.
	 * @param boolean true to return multiple rows as iterator, false returns first row.
	 * @return mixed matching data.
	 */
	public function findRecordsByCriteria(TActiveRecord $record, $criteria, $iterator=false)
	{
		if($iterator)
			return $this->getCommand($record)->findAll($criteria);
		else
			return $this->getCommand($record)->find($criteria);
	}

	/**
	 * Return record data from sql query.
	 * @param TActiveRecord active record finder instance.
	 * @param TActiveRecordCriteria sql query
	 * @return TDbDataReader result iterator.
	 */
	public function findRecordsBySql(TActiveRecord $record, $criteria)
	{
		return $this->getCommand($record)->findBySql($criteria);
	}

	/**
	 * Returns the number of records that match the given criteria.
	 * @param TActiveRecord active record finder instance.
	 * @param TActiveRecordCriteria search criteria
	 * @return int number of records.
	 */
	public function countRecords(TActiveRecord $record, $criteria)
	{
		return $this->getCommand($record)->count($criteria);
	}

	/**
	 * Insert a new record.
	 * @param TActiveRecord new record.
	 * @return int number of rows affected.
	 */
	public function insert(TActiveRecord $record)
	{
		$result = $this->getCommand($record)->insert($this->getInsertValues($record));
		if($result)
			$this->updatePostInsert($record);
		return $result;
	}

	protected function updatePostInsert($record)
	{
		$command = $this->getCommand($record);
		$tableInfo = $command->getTableInfo();
		foreach($tableInfo->getColumns() as $name => $column)
		{
			if($column->hasSequence())
				$record->{$name} = $command->getLastInsertID($column->getSequenceName());
		}
	}

	/**
	 * @param TActiveRecord record
	 * @return array insert values.
	 */
	protected function getInsertValues(TActiveRecord $record)
	{
		$values=array();
		$tableInfo = $this->getCommand($record)->getTableInfo();
		foreach($tableInfo->getColumns() as $name=>$column)
		{
			if($column->getIsExcluded())
				continue;
			$value = $record->{$name};
			if(!$column->getAllowNull() && $value===null && !$column->hasSequence())
			{
				throw new TActiveRecordException(
					'ar_value_must_not_be_null', get_class($record),
					$tableInfo->getTableFullName(), $name);
			}
			if($value!==null)
				$values[$name] = $value;
		}
		return $values;
	}

	/**
	 * Update the record.
	 * @param TActiveRecord dirty record.
	 * @return int number of rows affected.
	 */
	public function update(TActiveRecord $record)
	{
		list($data, $keys) = $this->getUpdateValues($record);
		return $this->getCommand($record)->updateByPk($data, $keys);
	}

	protected function getUpdateValues(TActiveRecord $record)
	{
		$values=array();
		$tableInfo = $this->getCommand($record)->getTableInfo();
		$primary=array();
		foreach($tableInfo->getColumns() as $name=>$column)
		{
			if($column->getIsExcluded())
				continue;
			$value = $record->{$name};
			if(!$column->getAllowNull() && $value===null)
			{
				throw new TActiveRecordException(
					'ar_value_must_not_be_null', get_class($record),
					$tableInfo->getTableFullName(), $name);
			}
			if($column->getIsPrimaryKey())
				$primary[] = $value;
			else
				$values[$name] = $value;
		}
		return array($values,$primary);
	}

	/**
	 * Delete the record.
	 * @param TActiveRecord record to be deleted.
	 * @return int number of rows affected.
	 */
	public function delete(TActiveRecord $record)
	{
		return $this->getCommand($record)->deleteByPk($this->getPrimaryKeyValues($record));
	}

	protected function getPrimaryKeyValues(TActiveRecord $record)
	{
		$tableInfo = $this->getCommand($record)->getTableInfo();
		$primary=array();
		foreach($tableInfo->getColumns() as $name=>$column)
		{
			if($column->getIsPrimaryKey())
				$primary[$name] = $record->{$name};
		}
		return $primary;
	}

	/**
	 * Delete multiple records using primary keys.
	 * @param TActiveRecord finder instance.
	 * @return int number of rows deleted.
	 */
	public function deleteRecordsByPk(TActiveRecord $record, $keys)
	{
		return $this->getCommand($record)->deleteByPk($keys);
	}

	/**
	 * Delete multiple records by criteria.
	 * @param TActiveRecord active record finder instance.
	 * @param TActiveRecordCriteria search criteria
	 * @return int number of records.
	 */
	public function deleteRecordsByCriteria(TActiveRecord $record, $criteria)
	{
		return $this->getCommand($record)->delete($criteria);
	}

	/**
	 * Raise the corresponding command event, insert, update, delete or select.
	 * @param string command type
	 * @param TDbCommand sql command to be executed.
	 * @param TActiveRecord active record
	 * @param mixed data for the command.
	 */
	protected function raiseCommandEvent($type,$command,$record=null,$data=null)
	{
		$param = new TActiveRecordGatewayEventParameter($type,$command,$record,$data);
		$manager = $record->getRecordManager();
		$event = 'on'.$type;
		if($data instanceof TActiveRecordCriteria)
			$data->{$event}($param);
		$manager->{$event}($param);
	}
}

/**
 * Command statement types.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordStatementType
{
	const Insert='Insert';
	const Update='Update';
	const Delete='Delete';
	const Select='Select';
}

/**
 * Active Record command event parameter.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordGatewayEventParameter extends TActiveRecordEventParameter
{
	private $_type;
	private $_command;
	private $_record;
	private $_data;

	/**
	 * New gateway command event parameter.
	 */
	public function __construct($type,$command,$record=null,$data=null)
	{
		$this->_type=$type;
		$this->_command=$command;
		$this->_data=$data;
		$this->_record=$record;
	}

	/**
	 * @return string TActiveRecordStateType
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @return TDbCommand command to be executed.
	 */
	public function getCommand()
	{
		return $this->_command;
	}

	/**
	 * @return TActiveRecord active record.
	 */
	public function getRecord()
	{
		return $this->_record;
	}

	/**
	 * @return mixed command data.
	 */
	public function getData()
	{
		return $this->_data;
	}
}

?>