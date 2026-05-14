<?php

/**
 * TDbTransaction class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use PDO;
use PDOException;
use Prado\Exceptions\TDbException;

/**
 * TDbTransaction class
 *
 * TDbTransaction represents a PDO database transaction. It is created by calling
 * {@see TDbConnection::beginTransaction()} and must be explicitly committed or
 * rolled back. After either operation the transaction becomes inactive.
 *
 * **Single-use pattern** — the classic approach, where each work unit gets a
 * fresh transaction object from the connection:
 *
 * ```php
 * try {
 *     $transaction = $connection->beginTransaction();
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     $transaction->commit();
 * } catch (Exception $e) {
 *     $transaction->rollback();
 * }
 * ```
 *
 * **Reuse pattern** — a single `TDbTransaction` instance can be restarted for
 * sequential work units by calling {@see beginTransaction()} on the object
 * itself after committing or rolling back, avoiding a new object allocation:
 *
 * ```php
 * $tx = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $tx->commit();
 * } catch (Exception $e) {
 *     $tx->rollback();
 * }
 * // Start the next unit of work on the same object.
 * $tx->beginTransaction();
 * try {
 *     $connection->createCommand($sql2)->execute();
 *     $tx->commit();
 * } catch (Exception $e) {
 *     $tx->rollback();
 * }
 * ```
 *
 * **Supersession:** calling {@see TDbConnection::beginTransaction()} always
 * creates a **new** `TDbTransaction` object.  If the connection's
 * `beginTransaction()` is called after a TDbTransaction completes, that old
 * transaction is superseded.  Attempting to restart a superseded transaction
 * via self {@see TDbTransaction::beginTransaction()} will throw a
 * {@see TDbException}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDbTransaction extends \Prado\TComponent implements IDataTransaction
{
	private $_connection;
	private $_active;

	/**
	 * Constructor.
	 * @param TDbConnection $connection the connection that owns this transaction.
	 * @see TDbConnection::beginTransaction
	 */
	public function __construct(TDbConnection $connection)
	{
		$this->setConnection($connection);
		$this->setActive(true);
		parent::__construct();
	}

	//	-----   Getters and Setters  -----

	/**
	 * Returns the connection that owns this transaction.
	 *
	 * @return TDbConnection the connection that created this transaction.
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * Sets the connection that owns this transaction.
	 *
	 * Called once by the constructor; not intended for external use.
	 *
	 * @param TDbConnection $connection the owning connection.
	 * @return static
	 */
	protected function setConnection(TDbConnection $connection): static
	{
		$this->_connection = $connection;
		return $this;
	}

	/**
	 * Returns whether this transaction is currently active (i.e. has been
	 * started and not yet committed or rolled back).
	 *
	 * @return bool true while the transaction is open, false after commit/rollback.
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * Sets the active state of this transaction.
	 *
	 * Managed internally by {@see beginTransaction()}, {@see completeTransaction()},
	 * and the constructor; not intended for external use.
	 *
	 * @param bool $value true to mark as active, false to mark as inactive.
	 * @return static
	 */
	protected function setActive(bool $value): static
	{
		$this->_active = $value;
		return $this;
	}

	//	-----   Methods  -----

	/**
	 * Creates a command on this transaction's connection.
	 *
	 * Convenience shorthand for `$transaction->getConnection()->createCommand($sql)`.
	 *
	 * @param string $sql SQL statement for the new command.
	 * @return TDbCommand the new command object.
	 * @since 4.3.3
	 */
	public function createCommand($sql)
	{
		return $this->getConnection()->createCommand($sql);
	}

	/**
	 * Commits the transaction.
	 *
	 * The transaction becomes inactive after commit. To start another work unit,
	 * either call {@see TDbTransaction::beginTransaction()} on this object (reuse
	 * pattern) or call {@see TDbConnection::beginTransaction()} to obtain a fresh
	 * transaction object.
	 *
	 * @throws TDbException if the transaction or its connection is not active.
	 */
	public function commit()
	{
		$pdo = $this->assertActive();
		$pdo->commit();
		$this->completeTransaction($pdo);
	}

	/**
	 * Rolls back the transaction.
	 *
	 * The transaction becomes inactive after rollback. To start another work unit,
	 * either call {@see TDbTransaction::beginTransaction()} on this object (reuse
	 * pattern) or call {@see TDbConnection::beginTransaction()} to obtain a fresh
	 * transaction object.
	 *
	 * @throws TDbException if the transaction or its connection is not active.
	 */
	public function rollback()
	{
		$pdo = $this->assertActive();
		try {
			$pdo->rollBack();
		} catch (PDOException | \Error $e) {
			// pdo_firebird may throw if its internal transaction state is out of
			// sync (e.g. after a previous error that already closed the Firebird
			// transaction handle). Catching here ensures completeTransaction() is
			// always reached so the PHP-side active flag is cleared correctly.
		}
		$this->completeTransaction($pdo);
	}

	/**
	 * Asserts that this transaction and its connection are both active, then
	 * returns the underlying PDO instance.
	 *
	 * @throws TDbException if the transaction or its connection is not active.
	 * @return PDO the active PDO instance.
	 */
	protected function assertActive(): PDO
	{
		$connection = $this->getConnection();

		if (!$this->getActive() || !$connection->getActive()) {
			throw new TDbException('dbtransaction_transaction_inactive');
		}

		return $connection->getPdoInstance();
	}

	/**
	 * Marks the transaction inactive and, for drivers that require it, flushes
	 * the implicit transaction that the driver opens immediately after a commit
	 * or rollback.
	 *
	 * pdo_firebird starts a new implicit transaction right after every
	 * `isc_commit_transaction` or `isc_rollback_transaction` call, before the
	 * completed transaction is fully visible in Firebird's Transaction Inventory
	 * Page. The implicit transaction's MVCC snapshot can therefore see stale data.
	 * Committing the empty implicit transaction forces pdo_firebird to open a fresh
	 * one whose snapshot reflects the completed work.
	 *
	 * @param PDO $pdo the PDO instance returned by {@see assertActive()}.
	 */
	protected function completeTransaction(PDO $pdo): void
	{
		try {
			$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
		} catch (PDOException | \Error $e) {
			$driver = null;
		}
		if ($driver !== null && TDbDriverCapabilities::requiresPostTransactionFlush($driver)) {
			try {
				$pdo->commit();
			} catch (PDOException | \Error $e) {
			}
		}

		$this->setActive(false);
	}

	/**
	 * Starts a new transaction on this transaction's connection, reactivating
	 * this transaction object for a new work unit.
	 *
	 * This allows a single TDbTransaction instance to span multiple sequential
	 * work units without allocating a new object each time:
	 *
	 * ```php
	 * $tx = $conn->beginTransaction();
	 * $tx->commit();
	 * // ...
	 * $tx->beginTransaction(); // reuse the same object
	 * $tx->commit();
	 * ```
	 *
	 * This is equivalent to calling {@see TDbConnection::beginTransaction()} but
	 * reactivates this existing object rather than returning a new one.
	 *
	 * **Supersession guard:** {@see TDbConnection::beginTransaction()} always
	 * allocates a **new** transaction object and stores it on the connection.
	 * If it was called after this transaction completed, this object is
	 * superseded — the connection now owns a different, newer transaction.
	 * Calling `beginTransaction()` on a superseded object throws a
	 * {@see TDbException} to prevent silently bypassing the active transaction's
	 * lifecycle.  Use the new transaction object returned by the last
	 * {@see TDbConnection::beginTransaction()} call instead, or call it again.
	 *
	 * For pdo_firebird a pre-begin flush (`PDO::commit()`) is issued before
	 * `PDO::beginTransaction()` to clear the implicit transaction that Firebird
	 * keeps running in autocommit mode. See {@see TDbConnection::beginTransaction()}
	 * for the full explanation of this requirement.
	 *
	 * @throws TDbException if this transaction is already active, if its
	 *   connection is not active, or if this transaction has been superseded by
	 *   a newer transaction on the same connection.
	 * @return static
	 * @since 4.3.3
	 * @see TDbConnection::beginTransaction
	 */
	public function beginTransaction(): static
	{
		if ($this->getActive()) {
			throw new TDbException('dbconnection_active_transaction');
		}
		$connection = $this->getConnection();
		$connection->assertActive();
		if ($connection->getLastTransaction() !== $this) {
			throw new TDbException('dbtransaction_transaction_superseded');
		}
		$pdo = $connection->getPdoInstance();
		if (TDbDriverCapabilities::requiresPreBeginTransactionFlush($connection->getDriverName())) {
			try {
				$pdo->commit();
			} catch (PDOException $e) {
			}
		}
		$pdo->beginTransaction();
		$this->setActive(true);
		return $this;
	}
}
