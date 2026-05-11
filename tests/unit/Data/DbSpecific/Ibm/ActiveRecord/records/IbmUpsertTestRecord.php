<?php

/**
 * UpsertTestRecord — ActiveRecord fixture for insertOrIgnore and upsert integration tests (IBM DB2).
 *
 * Maps to the `upsert_test` table:
 *   username VARCHAR(100) PK
 *   score    INTEGER DEFAULT 0
 *
 * IBM DB2's upsert_test uses username as the primary key (no auto-increment id).
 */
class IbmUpsertTestRecord extends TActiveRecord
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
