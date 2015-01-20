<?php
/**
 * TDataGatewayCommand, TDataGatewayEventParameter and TDataGatewayResultEventParameter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.DataGateway
 */

/**
 * TDataGatewayCommand is command builder and executor class for
 * TTableGateway and TActiveRecordGateway.
 *
 * TDataGatewayCommand builds the TDbCommand for TTableGateway
 * and TActiveRecordGateway commands such as find(), update(), insert(), etc,
 * using the TDbCommandBuilder classes (database specific TDbCommandBuilder
 * classes are used).
 *
 * Once the command is built and the query parameters are binded, the
 * {@link OnCreateCommand} event is raised. Event handlers for the OnCreateCommand
 * event should not alter the Command property nor the Criteria property of the
 * TDataGatewayEventParameter.
 *
 * TDataGatewayCommand excutes the TDbCommands and returns the result obtained from the
 * database (returned value depends on the method executed). The
 * {@link OnExecuteCommand} event is raised after the command is executed and resulting
 * data is set in the TDataGatewayResultEventParameter object's Result property.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.DataGateway
 * @since 3.1
 */
class TDataGatewayCommand extends TComponent
{
	private $_builder;

	/**
	 * @param TDbCommandBuilder database specific database command builder.
	 */
	public function __construct($builder)
	{
		$this->_builder = $builder;
	}

	/**
	 * @return TDbTableInfo
	 */
	public function getTableInfo()
	{
		return $this->_builder->getTableInfo();
	}

	/**
	 * @return TDbConnection
	 */
	public function getDbConnection()
	{
		return $this->_builder->getDbConnection();
	}

	/**
	 * @return TDbCommandBuilder
	 */
	public function getBuilder()
	{
		return $this->_builder;
	}

	/**
	 * Executes a delete command.
	 * @param TSqlCriteria delete conditions and parameters.
	 * @return integer number of records affected.
	 */
	public function delete($criteria)
	{
		$where = $criteria->getCondition();
		$parameters = $criteria->getParameters()->toArray();
		$command = $this->getBuilder()->createDeleteCommand($where, $parameters);
		$this->onCreateCommand($command,$criteria);
		$command->prepare();
		return $command->execute();
	}

	/**
	 * Updates the table with new data.
	 * @param array date for update.
	 * @param TSqlCriteria update conditions and parameters.
	 * @return integer number of records affected.
	 */
	public function update($data, $criteria)
	{
		$where = $criteria->getCondition();
		$parameters = $criteria->getParameters()->toArray();
		$command = $this->getBuilder()->createUpdateCommand($data,$where, $parameters);
		$this->onCreateCommand($command,$criteria);
		$command->prepare();
		return $this->onExecuteCommand($command, $command->execute());
	}

	/**
	 * @param array update for update
	 * @param array primary key-value name pairs.
	 * @return integer number of records affected.
	 */
	public function updateByPk($data, $keys)
	{
		list($where, $parameters) = $this->getPrimaryKeyCondition((array)$keys);
		return $this->update($data, new TSqlCriteria($where, $parameters));
	}

	/**
	 * Find one record matching the critera.
	 * @param TSqlCriteria find conditions and parameters.
	 * @return array matching record.
	 */
	public function find($criteria)
	{
		$command = $this->getFindCommand($criteria);
		return $this->onExecuteCommand($command, $command->queryRow());
	}

	/**
	 * Find one or more matching records.
	 * @param TSqlCriteria $criteria
	 * @return TDbDataReader record reader.
	 */
	public function findAll($criteria)
	{
		$command = $this->getFindCommand($criteria);
		return $this->onExecuteCommand($command, $command->query());
	}

	/**
	 * Build the find command from the criteria. Limit, Offset and Ordering are applied if applicable.
	 * @param TSqlCriteria $criteria
	 * @return TDbCommand.
	 */
	protected function getFindCommand($criteria)
	{
		if($criteria===null)
			return $this->getBuilder()->createFindCommand();
		$where = $criteria->getCondition();
		$parameters = $criteria->getParameters()->toArray();
		$ordering = $criteria->getOrdersBy();
		$limit = $criteria->getLimit();
		$offset = $criteria->getOffset();
		$select = $criteria->getSelect();
		$command = $this->getBuilder()->createFindCommand($where,$parameters,$ordering,$limit,$offset,$select);
		$this->onCreateCommand($command, $criteria);
		return $command;
	}

	/**
	 * @param mixed primary key value, or composite key values as array.
	 * @return array matching record.
	 */
	public function findByPk($keys)
	{
		list($where, $parameters) = $this->getPrimaryKeyCondition((array)$keys);
		$command = $this->getBuilder()->createFindCommand($where, $parameters);
		$this->onCreateCommand($command, new TSqlCriteria($where,$parameters));
		return $this->onExecuteCommand($command, $command->queryRow());
	}

	/**
	 * @param array multiple primary key values or composite value arrays
	 * @return TDbDataReader record reader.
	 */
	public function findAllByPk($keys)
	{
		$where = $this->getCompositeKeyCondition((array)$keys);
		$command = $this->getBuilder()->createFindCommand($where);
		$this->onCreateCommand($command, new TSqlCriteria($where,$keys));
		return $this->onExecuteCommand($command,$command->query());
	}

	public function findAllByIndex($criteria,$fields,$values)
	{
		$index = $this->getIndexKeyCondition($this->getTableInfo(),$fields,$values);
		if(strlen($where = $criteria->getCondition())>0)
			$criteria->setCondition("({$index}) AND ({$where}