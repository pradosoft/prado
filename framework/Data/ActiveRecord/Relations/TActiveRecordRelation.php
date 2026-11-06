<?php

/**
 * TActiveRecordRelation class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
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
	 * @return \Prado\Data\ActiveRecord\TActiveRecordCriteria
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
	 * Columns listed in $exclude are removed from each candidate foreign key, and
	 * a candidate left with no columns is skipped. A table with two foreign keys to
	 * the same table, such as the association table of a self-referencing
	 * MANY_TO_MANY relation, uses $exclude to pick the second key after the first.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord|\Prado\Data\Common\TDbTableInfo $from
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $matchesRecord
	 * @param bool $loose
	 * @param string[] $exclude column names of $from to leave out of the result.
	 *   Default is none. Parameter available since 4.3.3.
	 * @return array foreign keys with source column names as key and foreign column names as value.
	 */
	protected function findForeignKeys($from, $matchesRecord, $loose = false, $exclude = [])
	{
		$gateway = $matchesRecord->getRecordGateway();
		$recordTableInfo = $gateway->getRecordTableInfo($matchesRecord);
		$matchingTableName = strtolower($recordTableInfo->getTableName());
		$matchingFullTableName = strtolower($recordTableInfo->getTableFullName());
		$tableInfo = $from;
		if ($from instanceof TActiveRecord) {
			$tableInfo = $gateway->getRecordTableInfo($from);
		}
		$exclude = array_flip($exclude);
		//find first non-empty FK
		foreach ($tableInfo->getForeignKeys() as $fkeys) {
			$fkTable = strtolower($fkeys['table']);
			if ($fkTable === $matchingTableName || $fkTable === $matchingFullTableName) {
				foreach ($this->splitForeignKeys($fkeys['keys']) as $keys) {
					$hasFkField = !$loose && $this->getContext()->hasFkField();
					$key = $hasFkField ? $this->getFkFields($keys) : $keys;
					$key = array_diff_key($key, $exclude);
					if (!empty($key)) {
						return $key;
					}
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
	 * Splits one foreign key entry of the table metadata into its constraints.
	 * A single constraint references each foreign column once. The SQLite metadata
	 * groups every constraint to the same table into one entry, so a repeated foreign
	 * column starts the next constraint.
	 * @param array $keys source column names as key and foreign column names as value.
	 * @return array[] one array of keys per constraint, in metadata order.
	 * @since 4.3.3
	 */
	private function splitForeignKeys($keys)
	{
		$constraints = [];
		$current = [];
		foreach ($keys as $column => $foreignColumn) {
			if (in_array($foreignColumn, $current, true)) {
				$constraints[] = $current;
				$current = [];
			}
			$current[$column] = $foreignColumn;
		}
		$constraints[] = $current;
		return $constraints;
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
	 * Each call passes a copy of the relation criteria, because findAllByIndex() adds
	 * the key condition to the criteria it receives. Key tuples that hold a null are
	 * skipped, and no query runs when none remain.
	 * @param array $fields field names
	 * @param array $indexValues foreign key index values.
	 * @return TActiveRecord[] foreign objects.
	 */
	protected function findForeignObjects($fields, $indexValues)
	{
		$indexValues = $this->getNonNullIndexValues($indexValues);
		if (count($indexValues) === 0) {
			return [];
		}
		$finder = $this->getContext()->getForeignRecordFinder();
		return $finder->findAllByIndex(clone $this->getCriteria(), $fields, $indexValues);
	}

	/**
	 * Removes the key tuples that hold a null. A null key matches no row, so a
	 * record whose key is null has no foreign objects.
	 * @param array $indexValues key tuples, one array of values per record
	 * @return array the tuples without a null value, reindexed
	 * @since 4.3.3
	 */
	protected function getNonNullIndexValues($indexValues)
	{
		return array_values(array_filter($indexValues, fn ($tuple) => !in_array(null, $tuple, true)));
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
	 * @param array $results by reference, source results
	 * @param array $properties source property names
	 * @param array $fkObjects by reference, foreign objects
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
	 * @param array $results by reference
	 * @param array $collections by reference
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
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $source source object.
	 * @param array $properties source properties
	 * @param array $collections by reference, foreign objects.
	 */
	protected function setObjectProperty($source, $properties, &$collections)
	{
		$hash = $this->getObjectHash($source, $properties);
		$prop = $this->getContext()->getProperty();
		$source->$prop = $collections[$hash] ?? [];
	}

	/**
	 * Updates the associated foreign objects.
	 * @return bool true if all update are success (including if no update was required), false otherwise .
	 */
	abstract public function updateAssociatedRecords();
}
