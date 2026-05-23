<?php

/**
 * UpsertTestRecord — ActiveRecord fixture for insertOrIgnore and upsert integration tests (SQL Server).
 *
 * Maps to the `upsert_test` table:
 *   username NVARCHAR(100) PK
 *   score    INT DEFAULT 0
 *
 * SQL Server's upsert_test uses username as the primary key (no auto-increment id).
 */
class SqlSrvUpsertTestRecord extends TActiveRecord
{
	public $username;
	public $score = 0;

	const TABLE = 'upsert_test';

	/**
	 * Exposes the protected record-state integer for test assertions.
	 * @return int one of TActiveRecord::STATE_NEW, STATE_LOADED, STATE_DELETED.
	 */
	public function getRecordState(): int
	{
		return $this->_recordState;
	}

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
