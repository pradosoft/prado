<?php
/**
 * TActiveRecordRelationContext class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Relations
 */

namespace Prado\Data\ActiveRecord\Relations;

use Prado\Collections\TList;
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Prado;

/**
 * TActiveRecordRelationContext holds information regarding record relationships
 * such as record relation property name, query criteria and foreign object record
 * class names.
 *
 * This class is use internally by passing a context to the TActiveRecordRelation
 * constructor.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord\Relations
 * @since 3.1
 */
class TActiveRecordRelationContext
{
	private $_property;
	private $_record;
	private $_relation; //data from an entry of TActiveRecord::$RELATION
	private $_fkeys;

	public function __construct($record, $property = null, $relation = null)
	{
		$this->_record = $record;
		$this->_property = $property;
		$this->_relation = $relation;
	}

	/**
	 * @return bool true if the relation is defined in TActiveRecord::$RELATIONS
	 * @since 3.1.2
	 */
	public function hasRecordRelation()
	{
		return $this->_relation !== null;
	}

	public function getPropertyValue()
	{
		$obj = $this->getSourceRecord();
		return $obj->getColumnValue($this->getProperty());
	}

	/**
	 * @return string name of the record property that the relationship results will be assigned to.
	 */
	public function getProperty()
	{
		return $this->_property;
	}

	/**
	 * @return TActiveRecord the active record instance that queried for its related records.
	 */
	public function getSourceRecord()
	{
		return $this->_record;
	}

	/**
	 * @return array foreign key of this relations, the keys is dependent on the
	 * relationship type.
	 * @since 3.1.2
	 */
	public function getRelationForeignKeys()
	{
		if ($this->_fkeys === null) {
			$this->_fkeys = $this->getRelationHandler()->getRelationForeignKeys();
		}
		return $this->_fkeys;
	}

	/**
	 * @return string HAS_MANY, HAS_ONE, or BELONGS_TO
	 */
	public function getRelationType()
	{
		return $this->_relation[0];
	}

	/**
	 * @return string foreign record class name.
	 */
	public function getForeignRecordClass()
	{
		return $this->_relation[1];
	}

	/**
	 * @return string foreign key field names, comma delimited.
	 * @since 3.1.2
	 */
	public function getFkField()
	{
		return $this->_relation[2];
	}

	/**
	 * @return string the query condition for the relation as specified in RELATIONS
	 * @since 3.1.2
	 */
	public function getCondition()
	{
		return isset($this->_relation[3]) ? $this->_relation[3] : null;
	}

	/**
	 * @return array the query parameters for the relation as specified in RELATIONS
	 * @since 3.1.2
	 */
	public function getParameters()
	{
		return isset($this->_relation[4]) ? $this->_relation[4] : [];
	}

	/**
	 * @return bool true if the 3rd element of an TActiveRecord::$RELATION entry is set.
	 * @since 3.1.2
	 */
	public function hasFkField()
	{
		$notManyToMany = $this->getRelationType() !== TActiveRecord::MANY_TO_MANY;
		return $notManyToMany && isset($this->_relation[2]) && !empty($this->_relation[2]);
	}

	/**
	 * @return string the M-N relationship association table name.
	 */
	public function getAssociationTable()
	{
		return $this->_relation[2];
	}

	/**
	 * @return bool true if the relationship is HAS_MANY and requires an association table.
	 */
	public function hasAssociationTable()
	{
		$isManyToMany = $this->getRelationType() === TActiveRecord::MANY_TO_MANY;
		return $isManyToMany && isset($this->_relation[2]) && !empty($this->_relation[2]);
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
	 * @param null|TActiveRecordCriteria $criteria search criteria
	 * @throws TActiveRecordException if property is not defined or missing.
	 * @return TActiveRecordRelation record relationship handler instnace.
	 */
	public function getRelationHandler($criteria = null)
	{
		if (!$this->hasRecordRelation()) {
			throw new TActiveRecordException(
				'ar_undefined_relation_prop',
				$this->_property,
				get_class($this->_record),
				'RELATIONS'
			);
		}
		if ($criteria === null) {
			$criteria = new TActiveRecordCriteria($this->getCondition(), $this->getParameters());
		}
		switch ($this->getRelationType()) {
			case TActiveRecord::HAS_MANY:
				return new TActiveRecordHasMany($this, $criteria);
			case TActiveRecord::MANY_TO_MANY:
				return new TActiveRecordHasManyAssociation($this, $criteria);
			case TActiveRecord::HAS_ONE:
				return new TActiveRecordHasOne($this, $criteria);
			case TActiveRecord::BELONGS_TO:
				return new TActiveRecordBelongsTo($this, $criteria);
			default:
				throw new TActiveRecordException('ar_invalid_relationship');
		}
	}

	/**
	 * @param mixed $updateBelongsTo
	 * @return TActiveRecordRelationCommand
	 */
	public function updateAssociatedRecords($updateBelongsTo = false)
	{
		$success = true;
		foreach ($this->_record->getRecordRelations() as $data) {
			[$property, $relation] = $data;
			$belongsTo = $relation[0] == TActiveRecord::BELONGS_TO;
			if (($updateBelongsTo && $belongsTo) || (!$updateBelongsTo && !$belongsTo)) {
				$obj = $this->getSourceRecord();
				if (!$this->isEmptyFkObject($obj->getColumnValue($property))) {
					$context = new TActiveRecordRelationContext($this->getSourceRecord(), $property, $relation);
					$success = $context->getRelationHandler()->updateAssociatedRecords() && $success;
				}
			}
		}
		return $success;
	}

	protected function isEmptyFkObject($obj)
	{
		if (is_object($obj)) {
			return $obj instanceof TList ? $obj->count() === 0 : false;
		} elseif (is_array($obj)) {
			return count($obj) === 0;
		} else {
			return empty($obj);
		}
	}
}
