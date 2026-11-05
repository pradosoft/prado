<?php

/**
 * IDataCriteria interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\DataGateway;

/**
 * IDataCriteria interface
 *
 * Driver-agnostic contract for a query criteria value-object.  Exposes only the
 * portable fields (parameters, ordering, limit, offset, select) common to any
 * data store; driver-specific predicates (SQL WHERE text, Mongo filter doc,
 * etc.) live on the concrete subclass and are consumed by the matching
 * driver-specific {@see \Prado\Data\Common\IDataCommandBuilder}.
 *
 * Implemented by {@see TSqlCriteria} (SQL) and by non-SQL driver criteria.
 *
 * Required for portability — see {@see \Prado\Data\IDataConnection} for the full driver-portability roadmap.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataCriteria
{
	/**
	 * @return \Prado\Collections\TAttributeCollection name → value pairs to bind.
	 */
	public function getParameters();

	/**
	 * @return \Prado\Collections\TAttributeCollection column → direction (`'asc'|'desc'`) pairs.
	 */
	public function getOrdersBy();

	/**
	 * @return int maximum rows to return; -1 (the default) means no limit.
	 */
	public function getLimit();

	/**
	 * @return int rows to skip; -1 (the default) means no offset.
	 */
	public function getOffset();

	/**
	 * @return mixed the select-field specification (driver-defined: SQL string,
	 *   array of column names, projection document, etc.).
	 */
	public function getSelect();
}
