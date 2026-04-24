<?php

/**
 * TDbSerialTransaction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use PDO;
use Prado\Exceptions\TDbException;

/**
 * TDbSerialTransaction represents a permanent, reusable explicit-transaction
 * context for database drivers that always keep an implicit transaction alive
 * (e.g. pdo_firebird).
 *
 * Unlike {@see TDbTransaction}, which becomes inactive after a single commit or
 * rollback, TDbSerialTransaction is always in an explicit PDO transaction. On
 * construction it immediately converts the driver's connection-time implicit
 * transaction into an explicit one via PDO::beginTransaction(). After each
 * commit() or rollback() it restarts a fresh explicit transaction so the object
 * is immediately ready for the next use without any additional call.
 *
 * Typical usage — the same object is reused across multiple cycles:
 * ```php
 * $txn = $connection->beginTransaction();
 * $connection->createCommand($sql1)->execute();
 * $txn->commit();   // commits and immediately begins the next transaction
 *
 * $txn = $connection->beginTransaction(); // returns the same TDbSerialTransaction
 * $connection->createCommand($sql2)->execute();
 * $txn->commit();   // commits again; ready for the next cycle
 * ```
 *
 * The connection-level convenience methods {@see TDbConnection::commit()} and
 * {@see TDbConnection::rollback()} are the most ergonomic way to drive this
 * transaction from outside code that does not hold a reference to the object.
 *
 * For Firebird (pdo_firebird), isc_commit_transaction and
 * isc_rollback_transaction start a new implicit transaction immediately before
 * returning. That implicit transaction's MVCC snapshot can see stale data.
 * TDbSerialTransaction commits it (the post-transaction flush described by
 * {@see TDbDriverCapabilities::requiresPostTransactionFlush}) to force a fresh
 * snapshot, then calls PDO::beginTransaction() to begin the next explicit one.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbSerialTransaction extends TDbTransaction
{
	/**
	 * @return bool should the transaction mark as no longer active.
	 */
	public function isTransactionComplete(): bool
	{
		if ($this->getConnection()->getAutoCommit()) {
			return true;
		}

		$this->restartTransaction();
		return false;
	}

	/**
	 * Restarts a new explicit PDO transaction after commit or rollback.
	 *
	 * For drivers that require pre-transaction flushing (e.g. Firebird),
	 * the implicit transaction started by the driver is committed first,
	 * then a new explicit transaction is begun.
	 */
	protected function restartTransaction(): void
	{
		$pdo = $this->getConnection()->getPdoInstance();
		$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

		if (TDbDriverCapabilities::requiresPreBeginTransactionFlush($driver)) {
			try {
				$pdo->commit();
			} catch (\Exception $e) {
			}
		}
		$pdo->beginTransaction();
	}
}
