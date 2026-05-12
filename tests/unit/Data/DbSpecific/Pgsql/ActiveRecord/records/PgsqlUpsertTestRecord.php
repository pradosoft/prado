<?php

/**
 * UpsertTestRecord — ActiveRecord fixture for insertOrIgnore and upsert integration tests (PostgreSQL).
 *
 * Maps to the `upsert_test` table:
 *   id       SERIAL (auto-increment) PK
 *   username VARCHAR(100) UNIQUE NOT NULL
 *   score    INT DEFAULT 0
 */
class PgsqlUpsertTestRecord extends TActiveRecord
{
	public $id;
	public $username;
	public $score = 0;

	const TABLE = 'upsert_test';

	/**
	 * Default conflict-target column for upsert().
	 * PostgreSQL's upsert_test has `username` as a UNIQUE constraint distinct from
	 * the `id` primary key, so bare upsert() calls must conflict on `username`.
	 */
	const CONFLICT_COLUMNS = ['username'];

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
