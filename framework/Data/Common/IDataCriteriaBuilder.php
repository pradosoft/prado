<?php

/**
 * IDataCriteriaBuilder interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\DataGateway\IDataCriteria;

/**
 * IDataCriteriaBuilder interface
 *
 * Builds commands and criteria from a portable {@see IDataCriteria}, so the
 * DataGateway and relation loading work the same over SQL and document drivers.
 * {@see IDataCommandBuilder} extends this interface; a driver's command builder
 * implements these methods through it.
 *
 * | Method | Builds |
 * |---|---|
 * | {@see createFindCommandFromCriteria()} | a select command |
 * | {@see createCountCommandFromCriteria()} | a count command |
 * | {@see createUpdateCommandFromCriteria()} | an update command |
 * | {@see createDeleteCommandFromCriteria()} | a delete command |
 * | {@see createPrimaryKeyCriteria()} | a criteria matching primary-key values |
 * | {@see createIndexCriteria()} | a criteria matching index-value tuples |
 * | {@see createIndexedFindCommand()} | a select command for a criteria AND index tuples |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IDataCriteriaBuilder
{
	/**
	 * Builds a SELECT command from a portable criteria.
	 * @param IDataCriteria $criteria
	 */
	public function createFindCommandFromCriteria(IDataCriteria $criteria);
	/**
	 * Builds a COUNT command from a portable criteria.
	 * @param IDataCriteria $criteria
	 */
	public function createCountCommandFromCriteria(IDataCriteria $criteria);
	/**
	 * Builds an UPDATE command for `$data` against rows matched by `$criteria`.
	 * @param array $data
	 * @param IDataCriteria $criteria
	 */
	public function createUpdateCommandFromCriteria(array $data, IDataCriteria $criteria);
	/**
	 * Builds a DELETE command for rows matched by `$criteria`.
	 * @param IDataCriteria $criteria
	 */
	public function createDeleteCommandFromCriteria(IDataCriteria $criteria);
	/**
	 * Builds a criteria that selects rows by primary-key value(s).  Single PK
	 * accepts a scalar or single-element array; composite PK accepts an
	 * associative `column => value` row or a list of such rows.
	 * @param mixed $keyValues primary-key value(s).
	 */
	public function createPrimaryKeyCriteria($keyValues);
	/**
	 * Builds a criteria for relation batch-loading: matches rows whose
	 * `$fields` columns are in the `$values` tuples.  SQL drivers emit an
	 * `IN` list for one column and an `OR` of `AND`-joined equalities for
	 * several; document drivers emit a `$in` filter.
	 * @param array $fields column names to match.
	 * @param array $values list of value-tuples (one per matching row).
	 */
	public function createIndexCriteria(array $fields, array $values);
	/**
	 * Builds a SELECT command that matches `$criteria` AND an index criteria
	 * (`$fields` IN `$values`).  Used by relation batch-loading.  SQL drivers
	 * AND-combine the conditions; document drivers merge filter docs.
	 * @param IDataCriteria $criteria
	 * @param array $fields
	 * @param array $values
	 */
	public function createIndexedFindCommand(IDataCriteria $criteria, array $fields, array $values);
}
