<?php
/**
 * TActiveRecordRelationContext class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 */

/**
 * TActiveRecordRelationContext holds information regarding record relationships
 * such as record relation property name, query criteria and foreign object record
 * class names.
 *
 * This class is use internally by passing a context to the TActiveRecordRelation
 * constructor.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 * @since 3.1
 */
class TActiveRecordRelationContext
{
	/**
	 * static property name in TActiveRecord that defines the record relationships.
	 */
	const RELATIONS_CONST = 'RELATIONS';

	private $_property;
	private $_sourceRecord;
	private $_criteria;
	private $_relation;

	public function __construct($source, $property=null, $criteria=null)
	{
		$this->_sourceRecord=$source;
		$this->_criteria=$criteria;
		if($property!==null)
			list($this->_property, $this->_relation) = $this->getSourceRecordRelation($property);
	}

	/**
	 * Uses ReflectionClass to obtain the relation details array of a given
	 * property from the $RELATIONS static property in TActiveRecord.
	 * @param string relation property name
	 * @return array array($propertyName, $relation) relation definition.
	 * @throws TActiveRecordException if property is not defined or missing.
	 */
	protected function getSourceRecordRelation($property)
	{
		$property = strtolower($property);
		foreach($this->getRecordRelationships() as $name => $relation)
		{
			if(strtolower($name)===$property)
				return array($name, $relation);
		}
		throw new TActiveRecordException('ar_undefined_relation_prop',
			$property, get_class($this->_sourceRecord), self::RELATIONS_CONST);
	}

	/**
	 * @return array the key and values of TActiveRecord::$RELATIONS
	 */
	public function getRecordRelationships()
	{
		$class = new ReflectionClass($this->_sourceRecord);
		$statics = $class->getStaticProperties();
		if(isset($statics[self::RELATIONS_CONST]))
			return $statics[self::RELATIONS_CONST];
		else
			return array();
	}

	public function getPropertyValue()
	{
		$obj = $this->getSourceRecord();
		return $obj->{$this->getProperty()};
	}

	/**
	 * @return string name of the record property that the relationship results will be assigned to.
	 */
	public function getProperty()
	{
		return $this->_property;
	}

	/**
	 * @return TActiveRecordCriteria sql query criteria for fetching the related record.
	 */
	public function getCriteria()
	{
		return $this->_criteria;
	}

	/**
	 * @return TActiveRecord the active record instance that queried for its related records.
	 */
	public function getSourceRecord()
	{
		return $this->_sourceRecord;
	}

	/**
	 * @return string foreign record class name.
	 */
	public function getForeignRecordClass()
	{
		return $this->_relation[1];
	}

	/**
	 * @return string HAS_MANY, HAS_ONE, or BELONGS_TO
	 */
	public function getRelationType()
	{
		return $this->_relation[0];
	}

	/**
	 * @return string the M-N relationship association table name.
	 */
	public function getAssociationTable()
	{
		return $this->_relation[2];
	}

	/**
	 * @return boolean true if the relationship is HAS_MANY and requires an association table.
	 */
	public function hasAssociationTable()
	{
		return isset($this->_relation[2]);
	}

	/**
	 * @return TActiveRecord corresponding relationship foreign object finder instance.
	 */
	public function getForeignRecordFinder()
	{
		return TActiveRecord::finder($this->getForeignRecordClass());
	}

	/**
	 * Creates and return the TActiveRecordRelation handler for specific relationships.
	 * An instance of TActiveRecordHasOne, TActiveRecordBelongsTo, TActiveRecordHasMany,
	 * or TActiveRecordHasManyAssocation will be returned.
	 * @return TActiveRecordRelation record relationship handler instnace.
	 */
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
				throw new TActiveRecordException('ar_invalid_relationship');
		}
	}

	/**
	 * @return TActiveRecordRelationCommand
	 */
	public function updateAssociatedRecords($updateBelongsTo=false)
	{
		Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordRelationCommand');
		$success=true;
		foreach($this->getRecordRelationships() as $property=>$relation)
		{
			$belongsTo = $relation[0]==TActiveRecord::BELONGS_TO;
			if(($updateBelongsTo && $belongsTo) || (!$updateBelongsTo && !$belongsTo))
			{
				$obj = $this->getSourceRecord();
				if(!empty($obj->{$property}))
				{
					$context = new self($this->getSourceRecord(),$property);
					$success = $success && $context->getRelationHandler()->updateAssociatedRecords();
				}
			}
		}
		return $success;
	}
}

?>