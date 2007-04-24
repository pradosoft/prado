<?php

/**
 * Loads base active record relations class.
 */
Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordRelation');

class TActiveRecordHasManyAssociation extends TActiveRecordRelation
{
	private $_association;
	private $_sourceTable;
	private $_foreignTable;

	protected function collectForeignObjects(&$results)
	{
		$association = $this->getAssociationTable();
		$sourceKeys = $this->findForeignKeys($association, $this->getSourceRecord());

		$properties = array_values($sourceKeys);

		$indexValues = $this->getIndexValues($properties, $results);

		$fkObject = $this->getContext()->getForeignRecordFinder();
		$foreignKeys = $this->findForeignKeys($association, $fkObject);

		$this->fetchForeignObjects($results, $foreignKeys,$indexValues,$sourceKeys);
	}

	protected function getAssociationTable()
	{
		if($this->_association===null)
		{
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$conn = $this->getSourceRecord()->getDbConnection();
			$table = $this->getContext()->getAssociationTable();
			$this->_association = $gateway->getTableInfo($conn, $table);
		}
		return $this->_association;
	}

	protected function getSourceTable()
	{
		if($this->_sourceTable===null)
		{
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$this->_sourceTable = $gateway->getRecordTableInfo($this->getSourceRecord());
		}
		return $this->_sourceTable;
	}

	protected function getForeignTable()
	{
		if($this->_foreignTable===null)
		{
			$gateway = $this->getSourceRecord()->getRecordGateway();
			$fkObject = $this->getContext()->getForeignRecordFinder();
			$this->_foreignTable = $gateway->getRecordTableInfo($fkObject);
		}
		return $this->_foreignTable;
	}

	protected function getCommandBuilder()
	{
		return $this->getSourceRecord()->getRecordGateway()->getCommand($this->getSourceRecord());
	}

	/**
	 * Fetches the foreign objects using TActiveRecord::findAllByIndex()
	 * @param array field names
	 * @param array foreign key index values.
	 */
	protected function fetchForeignObjects(&$results,$foreignKeys,$indexValues,$sourceKeys)
	{
		$criteria = $this->getContext()->getCriteria();
		$finder = $this->getContext()->getForeignRecordFinder();
		$command = $this->createCommand($criteria, $foreignKeys,$indexValues,$sourceKeys);
		$srcProps = array_keys($sourceKeys);
		$type = get_class($finder);
		$collections=array();
		foreach($command->query() as $row)
		{
			$hash = $this->getObjectHash($row, $srcProps);
			foreach($srcProps as $column)
				unset($row[$column]);
			$collections[$hash][] = $finder->populateObject($type,$row);
		}

		$this->setResultCollection($results, $collections, array_values($sourceKeys));
	}

	/**
	 * @param TSqlCriteria
	 * @param TTableInfo association table info
	 * @param array field names
	 * @param array field values
	 */
	public function createCommand($criteria, $foreignKeys,$indexValues,$sourceKeys)
	{
		$innerJoin = $this->getAssociationJoin($foreignKeys,$indexValues,$sourceKeys);
		$fkTable = $this->getForeignTable()->getTableFullName();
		$srcColumns = $this->getSourceColumns($sourceKeys);
		if(($where=$criteria->getCondition())===null)
			$where='1=1';
		$sql = "SELECT {$fkTable}.*, {$srcColumns} FROM {$fkTable} {$innerJoin} WHERE {$where}";

		$parameters = $criteria->getParameters()->toArray();
		$ordering = $criteria->getOrdersBy();
		$limit = $criteria->getLimit();
		$offset = $criteria->getOffset();

		$builder = $this->getCommandBuilder()->getBuilder();
		$command = $builder->applyCriterias($sql,$parameters,$ordering,$limit,$offset);
		$this->getCommandBuilder()->onCreateCommand($command, $criteria);
		return $command;
	}

	protected function getSourceColumns($sourceKeys)
	{
		$columns=array();
		$table = $this->getAssociationTable();
		$tableName = $table->getTableFullName();
		foreach($sourceKeys as $name=>$fkName)
			$columns[] = $tableName.'.'.$table->getColumn($name)->getColumnName();
		return implode(', ', $columns);
	}

	protected function getAssociationJoin($foreignKeys,$indexValues,$sourceKeys)
	{
		$refInfo= $this->getAssociationTable();
		$fkInfo = $this->getForeignTable();

		$refTable = $refInfo->getTableFullName();
		$fkTable = $fkInfo->getTableFullName();

		$joins = array();
		foreach($foreignKeys as $ref=>$fk)
		{
			$refField = $refInfo->getColumn($ref)->getColumnName();
			$fkField = $fkInfo->getColumn($fk)->getColumnName();
			$joins[] = "{$fkTable}.{$fkField} = {$refTable}.{$refField}";
		}
		$joinCondition = implode(' AND ', $joins);

		$index = $this->getCommandBuilder()->getIndexKeyCondition($refInfo,array_keys($sourceKeys), $indexValues);

		return "INNER JOIN {$refTable} ON ({$joinCondition}) AND {$index}";
	}
}
?>