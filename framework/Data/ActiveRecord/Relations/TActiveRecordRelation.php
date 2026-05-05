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
 * TActiveRecordRelation class
 *
 * Abstract base class for all Active Record relationship handlers.
 *
 * Active Record relationships are declared via the static `$RELATIONS` array on
 * each {@see TActiveRecord} subclass. Each entry maps a property name to an
 * array whose first element is one of the four relationship constants and whose
 * second element is the foreign record class name:
 *
 * ```php
 * public static $RELATIONS = [
 *     'profile'    => [self::HAS_ONE,              'ProfileRecord'],
 *     'orders'     => [self::HAS_MANY,             'OrderRecord'],
 *     'team'       => [self::BELONGS_TO,           'TeamRecord'],
 *     'categories' => [self::HAS_MANY_ASSOC,       'CategoryRecord', 'Article_Category'],
 * ];
 * ```
 *
 * The four relationship types are:
 *
 * - **HAS_ONE** ({@see TActiveRecordHasOne}) — the foreign table carries a foreign key
 *   that points back to this record's primary key. The related property is a single
 *   object (or `null`).
 * - **HAS_MANY** ({@see TActiveRecordHasMany}) — same foreign-key direction as HAS_ONE,
 *   but the related property is a collection (array) of foreign objects.
 * - **BELONGS_TO** ({@see TActiveRecordBelongsTo}) — this record's table carries the
 *   foreign key that points to the related record's primary key. The related
 *   property is a single object (or `null`).
 * - **HAS_MANY_ASSOC** ({@see TActiveRecordHasManyAssociation}) — a many-to-many
 *   relationship resolved through an intermediate association table. A third
 *   element in the `$RELATIONS` entry names the association table.
 *
 * ## Fetching related objects
 *
 * Related objects are fetched lazily by chaining a relationship call onto a
 * finder method call:
 *
 * ```php
 * // Fetch all teams with their players eagerly loaded.
 * $teams = TeamRecord::finder()->withPlayers()->findAll();
 *
 * // Chain multiple relationships.
 * $articles = ArticleRecord::finder()->withCategories()->withAuthor()->findAll();
 * ```
 *
 * The {@see __call()} method intercepts `with<Property>()` calls, delegates the
 * underlying finder call to the source record, then calls
 * {@see collectForeignObjects()} to populate each result's relationship property.
 * Multiple chained `with*()` calls are queued via a static stack so that all
 * relationships are applied to the same result set.
 *
 * ## Implementing a new relationship type
 *
 * Subclasses must implement:
 * - {@see collectForeignObjects()} — given the source results, fetch and assign
 *   the corresponding foreign objects to each source record's relationship
 *   property.
 * - {@see getRelationForeignKeys()} — return the foreign key mapping (FK field
 *   names as keys, source property names as values) used by this relationship.
 * - {@see updateAssociatedRecords()} — persist any changes to the associated
 *   foreign objects (e.g. insert/delete rows in an association table).
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
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $from
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $matchesRecord
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
