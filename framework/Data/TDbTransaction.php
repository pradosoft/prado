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
 * Usage:
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
	 * Managed internally by {@see completeTransaction()} and the constructor;
	 * not intended for external use.
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
	 * Commits the transaction.
	 *
	 * The transaction becomes inactive after commit. To start another work unit,
	 * call {@see TDbConnection::beginTransaction()} to obtain a fresh transaction object.
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
	 * call {@see TDbConnection::beginTransaction()} to obtain a fresh transaction object.
	 *
	 * @throws TDbException if the transaction or its connection is not active.
	 */
	public function rollback()
	{
		$pdo = $this->assertActive();
		try {
			$pdo->rollBack();
		} catch (PDOException | \Error $e) {
			// Swallow errors from any driver where the transaction handle is already closed.
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
}
