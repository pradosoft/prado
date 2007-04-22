<?php

abstract class TActiveRecordRelation
{
}

class TActiveRecordHasMany extends TActiveRecordRelation
{
	private $source;
	private $dependent;
	private $property;
	private $criteria;

	private $fkeys;

	public function __construct($source,$criteria,$dependent,$property)
	{
		$this->source=$source;
		$this->criteria=$criteria;
		$this->dependent=$dependent;
		$this->property=$property;
	}

	public function __call($method,$args)
	{
		$results = call_user_func_array(array($this->source,$method),$args);
		$fkResults = $this->getForeignIndexResults($results);
		$this->matchResultCollection($results,$fkResults);
		return $results;
	}
	protected function getForeignIndexResults($results)
	{
		if(!is_array($results))
			$results = array($results);
		$fkeys = $this->getForeignKeys();
		$values = $this->getForeignKeyIndices($results, $fkeys);
		$fields = array_keys($fkeys);
		return $this->dependent->findAllByIndex($this->criteria, $fields, $values);
	}

	protected function matchResultCollection(&$results,&$fkResults)
	{
		$keys = $this->getForeignKeys();
		$collections=array();
		foreach($fkResults as $fkObject)
		{
			$objId=array();
			foreach($keys as $fkName=>$name)
				$objId[] = $fkObject->{$fkName};
			$collections[$this->getObjectId($objId)][]=$fkObject;
		}
		if(is_array($results))
		{
			for($i=0,$k=count($results);$i<$k;$i++)
			{
				$this->setFkObjectProperty($results[$i], $collections);
			}
		}
		else
		{
			$this->setFkObjectProperty($results, $collections);
		}
	}

	function setFKObjectProperty($source, &$collections)
	{
		$objId=array();
		foreach($this->getForeignKeys() as $fkName=>$name)
			$objId[] = $source->{$name};
		$key = $this->getObjectId($objId);
		$source->{$this->property} = isset($collections[$key]) ? $collections[$key] : array();
	}

	protected function getObjectId($objId)
	{
		return sprintf('%x',crc32(serialize($objId)));
	}

	protected function getForeignKeys()
	{
		if($this->fkeys===null)
		{
			$gateway = $this->dependent->getRecordGateway();
			$depTableInfo = $gateway->getRecordTableInfo($this->dependent);
			$fks = $depTableInfo->getForeignKeys();
			$sourceTable = $gateway->getRecordTableInfo($this->source)->getTableName();
			foreach($fks as $relation)
			{
				if($relation['table']===$sourceTable)
				{
					$this->fkeys=$relation['keys'];
					break;
				}
			}
			if(!$this->fkeys)
				throw new TActiveRecordException('no fk defined for '.$depTableInfo->getTableFullName());
		}
		return $this->fkeys;
	}

	protected function getForeignKeyIndices($results,$keys)
	{
		$values = array();
		foreach($results as $result)
		{
			$value = array();
			foreach($keys as $name)
				$value[] = $result->{$name};
			$values[] = $value;
		}
		return $values;
	}
}

class TActiveRecordHasOne extends TActiveRecordRelation
{
}

class TActiveRecordBelongsTo extends TActiveRecordRelation
{

}

?>