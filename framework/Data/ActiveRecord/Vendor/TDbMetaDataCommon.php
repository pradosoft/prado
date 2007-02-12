<?php
/**
 * TDbMetaDataCommon class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

Prado::using('System.Data.ActiveRecord.Vendor.TDbMetaData');

/**
 * Common database command: insert, update, select and delete.
 *
 * Base class for database specific insert, update, select and delete command builder.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
abstract class TDbMetaDataCommon extends TDbMetaData
{
	/**
	 * SQL database command for finding the record by primary keys.
	 * @param TDbConnection database connection.
	 * @param array primary keys name value pairs.
	 * @return TDbCommand find by primary key command.
	 */
	public function getFindByPkCommand($conn,$keys)
	{
		$columns = $this->getSelectionColumns();
		$primaryKeys = $this->getPrimaryKeyCriteria();
		$table = $this->getTableName();
		$sql = "SELECT {$columns} FROM {$table} WHERE {$primaryKeys}";
		$command = $this->createBindedCommand($conn, $sql, $this->getPrimaryKeys(), $keys);
		return $command;
	}

	/**
	 * SQL database command for finding records by a list of primary keys.
	 * @param TDbConnection database connection.
	 * @param array list of primary keys to match.
	 * @return TDbCommand find by list of primary keys command.
	 */
	public function getFindInPksCommand($conn, $keys)
	{
		$conn->setActive(true);
		$columns = $this->getSelectionColumns();
		$table = $this->getTableName();
		$criteria = $this->getCompositeKeysCriteria($conn,$keys);
		$sql = "SELECT {$columns} FROM {$table} WHERE {$criteria}";
		$command = $conn->createCommand($sql);
		$command->prepare();
		return $command;
	}

	/**
	 * SQL database command for finding records using a criteria object.
	 * @param TDbConnection database connection.
	 * @param TActiveRecordCriteria criteria object
	 * @return TDbCommand find by criteria command.
	 */
	public function getFindByCriteriaCommand($conn, $criteria=null)
	{
		$columns = $this->getSelectionColumns();
		$conditions = $this->getSqlFromCriteria($conn,$criteria);
		$table = $this->getTableName();
		$sql = "SELECT {$columns} FROM {$table} {$conditions}";
		return $this->createCriteriaBindedCommand($conn,$sql, $criteria);
	}

	/**
	 * Command to count the number of record matching the criteria.
	 * @param TDbConnection database connection.
	 * @param TActiveRecordCriteria criteria object
	 * @return TDbCommand count command.
	 * 	 */
	public function getCountRecordsCommand($conn, $criteria)
	{
		$columns = $this->getSelectionColumns();
		$conditions = $this->getSqlFromCriteria($conn,$criteria);
		$table = $this->getTableName();
		$sql = "SELECT count(*) FROM {$table} {$conditions}";
		return $this->createCriteriaBindedCommand($conn,$sql, $criteria);
	}

	abstract protected function getSqlFromCriteria($conn, $criteria);

	/**
	 * Sql command with parameters binded.
	 * @param TDbConnection database connection.
	 * @param string sql query.
	 * @param array parameters to be bound
	 * @return TDbCommand sql command.
	 */
	public function getFindBySqlCommand($conn,$sql,$parameters)
	{
		$conn->setActive(true);
		$command = $conn->createCommand($sql);
		$this->bindParameterValues($conn,$command,$parameters);
		return $command;
	}

	/**
	 * SQL database command for insert a new record.
	 * @param TDbConnection database connection.
	 * @param TActiveRecord new record to be inserted.
	 * @return TDbCommand active record insert command
	 */
	public function getInsertCommand($conn, $record)
	{
		$columns = $this->getInsertableColumns($record);
		$fields = $this->getInsertColumNames($columns);
		$inserts = $this->getInsertColumnValues($columns);
		$table = $this->getTableName();
		$sql = "INSERT INTO {$table} ({$fields}) VALUES ({$inserts})";
		return $this->createBindedCommand($conn, $sql, array_keys($columns), $columns);
	}

	/**
	 * Update the record object's sequence values after insert.
	 * @param TDbConnection database connection.
	 * @param TActiveRecord record object.
	 */
	public function updatePostInsert($conn, $record)
	{
		foreach($this->getColumns() as $name => $column)
		{
			if($column->hasSequence())
				$record->{$name} = $conn->getLastInsertID($column->getSequenceName());
		}
	}

	/**
	 * SQL database command to update an active record.
	 * @param TDbConnection database connection.
	 * @param TActiveRecord record for update.
	 * @return TDbCommand update command.
	 */
	public function getUpdateCommand($conn,$record)
	{
		$primaryKeys = $this->getPrimaryKeyCriteria();
		$columns = $this->getUpdatableColumns($record);
		$updates = $this->getUpdateBindings($columns);
		$table = $this->getTableName();
		$sql = "UPDATE {$table} SET {$updates} WHERE {$primaryKeys}";
		$primaryKeyValues = $this->getObjectKeyValues($this->getPrimaryKeys(), $record);
		$values = array_merge($columns, $primaryKeyValues);
		return $this->createBindedCommand($conn, $sql, array_keys($values), $values);
	}

	/**
	 * SQL database command to delete an active record.
	 * @param TDbConnection database connection.
	 * @param TActiveRecord record for deletion.
	 * @return TDbCommand delete command.
	 */
	public function getDeleteCommand($conn,$record)
	{
		$primaryKeys = $this->getPrimaryKeyCriteria();
		$table = $this->getTableName();
		$sql = "DELETE FROM {$table} WHERE {$primaryKeys}";
		$keys = $this->getPrimaryKeys();
		$values = $this->getObjectKeyValues($keys, $record);
		return $this->createBindedCommand($conn,$sql, $keys, $values);
	}

	/**
	 * SQL command to delete records by primary keys.
	 * @param TDbConnection database connection.
	 * @param array list of primary keys
	 * @return TDbCommand delete command.
	 */
	public function getDeleteByPkCommand($conn,$keys)
	{
		$conn->setActive(true);
		$table = $this->getTableName();
		$criteria = $this->getCompositeKeysCriteria($conn, $keys);
		$sql = "DELETE FROM {$table} WHERE {$criteria}";
		$command = $conn->createCommand($sql);
		$command->prepare();
		return $command;
	}


	/**
	 * SQL command to delete records by criteria
	 * @param TDbConnection database connection.
	 * @param TActiveRecordCriteria criteria object.
	 * @return TDbCommand delete command.
	 */
	public function getDeleteByCriteriaCommand($conn, $criteria)
	{
		$conditions = $this->getSqlFromCriteria($conn,$criteria);
		$table = $this->getTableName();
		$sql = "DELETE FROM {$table} {$conditions}";
		return $this->createCriteriaBindedCommand($conn,$sql, $criteria);
	}
}

?>