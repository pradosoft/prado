<?php

class TActiveRecordRelationContext
{
	const RELATIONS_CONST = 'RELATIONS';

	private $_property;
	private $_sourceRecord;
	private $_criteria;
	private $_relation;

	public function __construct($source, $property, $criteria)
	{
		$this->_sourceRecord=$source;
		$this->_property=$property;
		$this->_criteria=$criteria;
		$this->_relation = $this->getSourceRecordRelation($property);
	}
	/**
	 * @param string relation property name
	 * @return array relation definition.
	 */
	protected function getSourceRecordRelation($property)
	{
		$class = new ReflectionClass($this->_sourceRecord);
		$statics = $class->getStaticProperties();
		if(!isset($statics[self::RELATIONS_CONST]))
			throw new TActiveRecordException('ar_relations_undefined',
				get_class($this->_sourceRecord), self::RELATIONS_CONST);
		if(isset($statics[self::RELATIONS_CONST][$property]))
			return $statics[self::RELATIONS_CONST][$property];
		else
			throw new TActiveRecordException('ar_undefined_relation_prop',
				$property, get_class($this->_sourceRecord), self::RELATIONS_CONST);
	}

	public function getProperty()
	{
		return $this->_property;
	}

	public function getCriteria()
	{
		return $this->_criteria;
	}

	public function getSourceRecord()
	{
		return $this->_sourceRecord;
	}

	public function getForeignRecordClass()
	{
		return $this->_relation[1];
	}

	public function getRelationType()
	{
		return $this->_relation[0];
	}

	public function getAssociationTable()
	{
		return $this->_relation[2];
	}

	public function hasAssociationTable()
	{
		return isset($this->_relation[2]);
	}

	public function getForeignRecordFinder()
	{
		return TActiveRecord::finder($this->getForeignRecordClass());
	}

	public function getRelationHandler()
	{
		switch($this->getRelationType())
		{
			case TActiveRecord::HAS_MANY:
				if(!$this->hasAssociationTable())
				{
					Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordHasMany');
					return new TActiveRecordHasMany($this);
				}
				else
				{
					Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordHasManyAssociation');
					return new TActiveRecordHasManyAssociation($this);
				}
			case TActiveRecord::HAS_ONE:
				Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordHasOne');
				return new TActiveRecordHasOne($this);
			case TActiveRecord::BELONGS_TO:
				Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordBelongsTo');
				return new TActiveRecordBelongsTo($this);
			default:
				throw new TException('Not done yet');
		}
	}
}

?>