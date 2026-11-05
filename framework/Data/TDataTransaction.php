<?php

/**
 * TDataTransaction class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Exceptions\TDbException;

/**
 * TDataTransaction class.
 *
 * Driver-agnostic transaction token.  Delegates {@see commit()} and
 * {@see rollback()} to the owning {@see IDataConnection}, which is the source
 * of truth for the underlying transaction handle.  This class has no
 * SQL/PDO dependency and may be reused by any {@see IDataConnection}
 * implementation; {@see TDbTransaction} is the SQL/PDO subclass kept for
 * backward compatibility.
 *
 * The owning connection raises {@see onCommit OnCommit} after it commits the
 * transaction and {@see onRollback OnRollback} after it rolls the transaction
 * back; the transaction is already inactive when the handlers run.  See
 * {@see TDbTransaction} for the full event behavior.
 *
 * Typical use:
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
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDataTransaction extends \Prado\TComponent implements IDataTransaction
{
	private IDataConnection $_connection;
	private bool $_active;

	/**
	 * @param IDataConnection $connection the connection that owns this transaction.
	 * @see IDataConnection::beginTransaction
	 */
	public function __construct(IDataConnection $connection)
	{
		$this->setConnection($connection);
		$this->_active = true;
		parent::__construct();
	}

	//	-----   Getters and Setters  -----

	/**
	 * @return IDataConnection the connection that owns this transaction.
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * Sets the connection that owns this transaction.  Called once by the
	 * constructor; not intended for external use.
	 *
	 * @param IDataConnection $connection the owning connection.
	 * @return static
	 */
	protected function setConnection(IDataConnection $connection): static
	{
		$this->_connection = $connection;
		return $this;
	}

	/**
	 * @return bool true while the transaction is open, false after commit/rollback.
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * Marks this transaction inactive.  Called by {@see IDataConnection::commit}
	 * and {@see IDataConnection::rollback} after the underlying operation succeeds.
	 */
	public function deactivate(): void
	{
		$this->_active = false;
	}

	/**
	 * Marks this transaction active.  Used by subclasses that support reuse:
	 * reactivating a completed transaction object for a new work unit (e.g.
	 * after a fresh `beginTransaction` on the underlying driver session).
	 */
	protected function activate(): void
	{
		$this->_active = true;
	}

	//	-----   Methods  -----

	/**
	 * Commits the transaction.  Delegates to {@see IDataConnection::commit()}.
	 * @throws TDbException if the transaction or its connection is not active.
	 */
	public function commit()
	{
		$this->assertActive();
		$this->_connection->commit();
	}

	/**
	 * Rolls back the transaction.  Delegates to {@see IDataConnection::rollback()}.
	 * @throws TDbException if the transaction or its connection is not active.
	 */
	public function rollback()
	{
		$this->assertActive();
		$this->_connection->rollback();
	}

	/**
	 * Raises the OnCommit event after the connection commits the transaction.
	 * The sender is this inactive transaction, and the parameter is null.
	 * @since 4.4.0
	 */
	public function onCommit()
	{
		$this->raiseEvent('OnCommit', $this, null);
	}

	/**
	 * Raises the OnRollback event after the connection rolls back the transaction,
	 * by {@see rollback()} or by the closing of the connection.
	 * The sender is this inactive transaction, and the parameter is null.
	 * @since 4.4.0
	 */
	public function onRollback()
	{
		$this->raiseEvent('OnRollback', $this, null);
	}

	/**
	 * Asserts that this transaction and its connection are both active.
	 * @throws TDbException if the transaction or its connection is not active.
	 */
	protected function assertActive(): void
	{
		if (!$this->getActive() || !$this->_connection->getActive()) {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
	}
}
