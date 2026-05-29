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
 * Driver-agnostic transaction token.  {@see commit} and {@see rollback}
 * delegate to the owning {@see IDataConnection}, which performs the actual
 * work.  Has no SQL/PDO dependency; {@see TDbTransaction} is the SQL/PDO
 * subclass kept as a BC discovery marker.
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
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
	 * Sets the owning connection.  Called once by the constructor.
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
	 * / {@see IDataConnection::rollback} after the underlying operation succeeds.
	 */
	public function deactivate(): void
	{
		$this->_active = false;
	}

	/**
	 * Marks this transaction active.  Used by reuse-pattern subclasses that
	 * reactivate a completed transaction for a new work unit.
	 */
	protected function activate(): void
	{
		$this->_active = true;
	}

	//	-----   Methods  -----

	/**
	 * Commits the transaction.  Delegates to {@see IDataConnection::commit}.
	 * @throws TDbException if the transaction or connection is not active.
	 */
	public function commit()
	{
		$this->assertActive();
		$this->_connection->commit();
	}

	/**
	 * Rolls back the transaction.  Delegates to {@see IDataConnection::rollback}.
	 * @throws TDbException if the transaction or connection is not active.
	 */
	public function rollback()
	{
		$this->assertActive();
		$this->_connection->rollback();
	}

	/**
	 * @throws TDbException if the transaction or connection is not active.
	 */
	protected function assertActive(): void
	{
		if (!$this->getActive() || !$this->_connection->getActive()) {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
	}
}
