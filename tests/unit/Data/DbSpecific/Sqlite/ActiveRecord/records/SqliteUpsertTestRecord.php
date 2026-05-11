<?php

/**
 * UpsertTestRecord — ActiveRecord fixture for insertOrIgnore and upsert integration tests (SQLite).
 *
 * Maps to the `upsert_test` table created in-memory:
 *   id       INTEGER PRIMARY KEY AUTOINCREMENT
 *   username TEXT UNIQUE NOT NULL
 *   score    INTEGER DEFAULT 0
 */
class SqliteUpsertTestRecord extends TActiveRecord
{
	public $id;
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
