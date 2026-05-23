<?php

/**
 * UpsertTestRecord — ActiveRecord fixture for insertOrIgnore and upsert integration tests.
 *
 * Maps to the `upsert_test` table:
 *   id       INT  AUTO_INCREMENT PK
 *   username VARCHAR(100) UNIQUE NOT NULL
 *   score    INT  DEFAULT 0
 *
 * Exposes the protected $_recordState via {@see getRecordState()} so that tests
 * can assert STATE_NEW / STATE_LOADED / STATE_DELETED transitions without
 * relying on reflection.
 */
class UpsertTestRecord extends TActiveRecord
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
