<?php

/**
 * TSqlMapUpsert class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapUpsert corresponds to the <upsert> element.
 *
 * Supports optional updateColumns and conflictColumns attributes to control
 * which columns are updated on conflict and which columns identify the conflict.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TSqlMapUpsert extends TSqlMapInsert
{
	private ?array $_updateColumns = null;
	private ?array $_conflictColumns = null;

	/**
	 * @return null|array the columns to update on conflict, or null to use all non-PK columns.
	 */
	public function getUpdateColumns(): ?array
	{
		return $this->_updateColumns;
	}

	/**
	 * @param array|string $value column names as comma-separated string or array.
	 */
	public function setUpdateColumns($value): void
	{
		$this->_updateColumns = is_string($value) ? array_map('trim', explode(',', $value)) : $value;
	}

	/**
	 * @return null|array the conflict target columns, or null to use primary key columns.
	 */
	public function getConflictColumns(): ?array
	{
		return $this->_conflictColumns;
	}

	/**
	 * @param array|string $value column names as comma-separated string or array.
	 */
	public function setConflictColumns($value): void
	{
		$this->_conflictColumns = is_string($value) ? array_map('trim', explode(',', $value)) : $value;
	}
}
