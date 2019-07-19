<?php
/**
 * TActiveRecordRelation class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Relations
 */

namespace Prado\Data\ActiveRecord\Relations;

/**
 * Load active record relationship context.
 */
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Prado;

/**
 * Base class for active record relationships.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord\Relations
 * @since 3.1
 */
abstract class TActiveRecordRelation
{
	private $_context;
	private $_criteria;

	public function __construct(TActiveRecordRelationContext $context, $criteria)
	{
		$this->_context = $context;
		$this->_criteria = $criteria;
	}

	/**
	 * @return TActiveRecordRelationContext
	 */
	protected function getContext()
	{
		return $this->_context;
	}

	/**
	 * @return TActiveRecordCriteria
	 */
	protected function getCriteria()
	{
		return $this->_criteria;
	}

	/**
	 * @return TActiveRecord
	 */
	protected function getSourceRecord()
	{
		return $this->getContext()->getSourceRecord();
	}

	abstract protected function collectForeignObjects(&$results);

	/**
	 * Dispatch the method calls to the source record finder object. When
	 * an instance of TActiveRecord or an array of TActiveRecord is returned
	 * the corresponding foreign objects are also fetched and assigned.
	 *
	 * Multiple relationship calls can be chain together.
	 *
	 * @param string $method method name called
	 * @param array $args method arguments
	 * @return mixed TActiveRecord or array of TActiveRecord results depending on the method called.
	 */
	public function __call($method, $args)
	{
		static $stack = [];

		$results = call_user_func_array([$this->getSourceRecord(), $method], $args);
		$validArray = is_array($results) && count($results) > 0;
		if ($validArray || $results instanceof \ArrayAccess || $results instanceof TActiveRecord) {
			$this->collectForeignObjects($results);
			while ($obj = array_pop($stack)) {
				$obj->collectForeignObjects($results);
			}
		} elseif ($results instanceof TActiveRecordRelation) {
			$stack[] = $this;
		} //call it later
		elseif ($results === null || !$validArray) {
			$stack = [];
		}
		return $results;
	}

	/**
	 * Fetch results for current relationship.
	 * @param mixed $obj
	 * @return bool always true.
	 */
	public function fetchResultsInto($obj)
	{
		$this->collectForeignObjects($obj);
		return true;
	}

	/**
	 * Returns foreign keys in $from with source column names as key
	 * and foreign column names in the corresponding $matchesRecord as value.
	 * The method returns the first matching foreign key between these 2 records.
	 * @param TActiveRecord $from
	 * @param TActiveRecord $matchesRecord
	 * @param bool $loose
	 * @return array foreign keys with source column names as key and foreign column names as value.
	 */
	protected function findForeignKeys($from, $matchesRecord, $loose = false)
	{
		$gateway = $matchesRecord->getRecordGateway();
		$recordTableInfo = $gateway->getRecordTableInfo($matchesRecord);
		$matchingTableName = strtolower($recordTableInfo->getTableName());
		$matchingFullTableName = strtolower($recordTableInfo->getTableFullName());
		$tableInfo = $from;
		if ($from instanceof TActiveRecord) {
			$tableInfo = $gateway->getRecordTableInfo($from);
		}
		//find first non-empty FK
		foreach ($tableInfo->getForeignKeys() as $fkeys) {
			$fkTable = strtolower($fkeys['table']);
			if ($fkTable === $matchingTableName || $fkTable === $matchingFullTableName) {
				$hasFkField = !$loose && $this->getContext()->hasFkField();
				$key = $hasFkField ? $this->getFkFields($fkeys['keys']) : $fkeys['keys'];
				if (!empty($key)) {
					return $key;
				}
			}
		}

		//none found
		$matching = $gateway->getRecordTableInfo($matchesRecord)->getTableFullName();
		throw new TActiveRecordException(
			'ar_relations_missing_fk',
			$tableInfo->getTableFullName(),
			$matching
		);
	}

	/**
	 * @return array foreign key field names as key and object properties as value.
	 * @since 3.1.2
	 */
	abstract public function getRelationForeignKeys();

	/**
	 * Find matching foreign key fields from the 3rd element of an entry in TActiveRecord::$RELATION.
	 * Assume field names consist of [\w-] character sets. Prefix to the field names ending with a dot
	 * are ignored.
	 * @param mixed $fkeys
	 */
	private function getFkFields($fkeys)
	{
		$matching = [];
		preg_match_all('/\s*(\S+\.)?([\w-]+)\s*/', $this->getContext()->getFkField(), $matching);
		$fields = [];
		foreach ($fkeys as $fkName => $field) {
			if (in_array($fkName, $matching[2])) {
				$fields[$fkName] = $field;
			}
		}
		return $fields;
	}

	/**
	 * @param mixed $obj object or array to be hashed
	 * @param array $properties name of property for hashing the properties.
	 * @return string object hash using crc32 and serialize.
	 */
	protected function getObjectHash($obj, $properties)
	{
		$ids = [];
		foreach ($properties as $property) {
			$ids[] = is_object($obj) ? (string) $obj->getColumnValue($property) : (string) $obj[$property];
		}
		return serialize($ids);
	}

	/**
	 * Fetches the foreign objects using TActiveRecord::findAllByIndex()
	 * @param array $fields field names
	 * @param array $indexValues foreign key index values.
	 * @return TActiveRecord[] foreign objects.
	 */
	protected function findForeignObjects($fields, $indexValues)
	{
		$finder = $this->getContext()->getForeignRecordFinder();
		return $finder->findAllByIndex($this->_criteria, $fields, $indexValues);
	}

	/**
	 * Obtain the foreign key index values from the results.
	 * @param array $keys property names
	 * @param array $results TActiveRecord results
	 * @return array foreign key index values.
	 */
	protected function getIndexValues($keys, $results)
	{
		if (!is_array($results) && !$results instanceof \ArrayAccess) {
			$results = [$results];
		}
		$values = [];
		foreach ($results as $result) {
			$value = [];
			foreach ($keys as $name) {
				$value[] = $result->getColumnValue($name);
			}
			$values[] = $value;
		}
		return $values;
	}

	/**
	 * Populate the results with the foreign objects found.
	 * @param array &$results source results
	 * @param array $properties source property names
	 * @param array &$fkObjects foreign objects
	 * @param array $fields foreign object field names.
	 */
	protected function populateResult(&$results, $properties, &$fkObjects, $fields)
	{
		$collections = [];
		foreach ($fkObjects as $fkObject) {
			$collections[$this->getObjectHash($fkObject, $fields)][] = $fkObject;
		}
		$this->setResultCollection($results, $collections, $properties);
	}

	/**
	 * Populates the result array with foreign objects (matched using foreign key hashed property values).
	 * @param array &$results
	 * @param array &$collections
	 * @param array $properties property names
	 */
	protected function setResultCollection(&$results, &$collections, $properties)
	{
		if (is_array($results) || $results instanceof \ArrayAccess) {
			for ($i = 0, $k = count($results); $i < $k; $i++) {
				$this->setObjectProperty($results[$i], $properties, $collections);
			}
		} else {
			$this->setObjectProperty($results, $properties, $collections);
		}
	}

	/**
	 * Sets the foreign objects to the given property on the source object.
	 * @param TActiveRecord $source source object.
	 * @param array $properties source properties
	 * @param array &$collections foreign objects.
	 */
	protected function setObjectProperty($source, $properties, &$collections)
	{
		$hash = $this->getObjectHash($source, $properties);
		$prop = $this->getContext()->getProperty();
		$source->$prop = $collections[$hash] ?? [];
	}
}
