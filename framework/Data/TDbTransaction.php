<?php

/**
 * TDbTransaction class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Exceptions\TDbException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TDbTransaction class.
 *
 * TDbTransaction represents a DB transaction.
 * It is usually created by calling {@see \Prado\Data\TDbConnection::beginTransaction}.
 *
 * The following code is a common scenario of using transactions:
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... other SQL executions
 *    $transaction->commit();
 * } catch (\Exception $e) {
 *    if ($transaction->getActive()) {
 *        $transaction->rollBack();
 *    }
 *    throw $e;
 * }
 * ```
 *
 * The transaction raises {@see onCommit OnCommit} after the database commits it and
 * {@see onRollback OnRollback} after the database rolls it back. Work that waits for
 * the commit, such as announcing a change to other processes, attaches to OnCommit:
 * ```php
 * $transaction = $connection->getCurrentTransaction();
 * if ($transaction === null) {
 *    $this->announce($change);
 * } else {
 *    $transaction->attachEventHandler('OnCommit', fn ($sender, $param) => $this->announce($change));
 * }
 * ```
 *
 * Event behavior:
 * - Each event is raised after the PDO call succeeds. A commit or rollback that
 *   throws, or that PDO reports as failed, raises no event.
 * - The transaction is inactive when the handlers run, and
 *   {@see \Prado\Data\TDbConnection::getCurrentTransaction()} returns null. A handler
 *   may begin a new transaction on the connection.
 * - A transaction commits or rolls back once, so its handlers run at most once. Each
 *   {@see \Prado\Data\TDbConnection::beginTransaction()} creates a new transaction
 *   without handlers.
 * - An exception thrown by a handler propagates out of {@see commit()} or
 *   {@see rollback()}, and the remaining handlers do not run. The database work is
 *   already committed or rolled back, and the transaction stays inactive. A caller
 *   that rolls back in a `catch` block checks {@see getActive()} first.
 * - Nesting is not supported. The connection has one transaction at a time, and the
 *   events belong to that outermost transaction.
 * - Closing the connection ({@see \Prado\Data\TDbConnection::setActive()} false) with
 *   the transaction open rolls it back and raises OnRollback, after the connection is
 *   closed, when the database still held it ({@see endOnClose()}).  So a transaction
 *   that the connection began and saw end raises exactly one of the two events.
 * - A transaction begun, committed, or rolled back directly on the PDO instance, ended
 *   implicitly by the database server, or still open when the script ends raises no
 *   event.  Closing the connection after such an end raises no event either: the
 *   outcome is unknown.
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
	 * @param \Prado\Data\TDbConnection $connection the connection associated with this transaction
	 * @see TDbConnection::beginTransaction
	 */
	public function __construct(TDbConnection $connection)
	{
		$this->_connection = $connection;
		$this->setActive(true);
		parent::__construct();
	}

	/**
	 * Commits a transaction and raises {@see onCommit OnCommit} when the commit succeeds.
	 * @throws TDbException if the transaction or the DB connection is not active.
	 */
	public function commit()
	{
		if (!$this->getIsOperable()) {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
		$committed = $this->getConnection()->getPdoInstance()->commit();
		$this->setActive(false);
		if ($committed !== false) {
			$this->onCommit();
		}
	}

	/**
	 * Rolls back a transaction and raises {@see onRollback OnRollback} when the rollback succeeds.
	 * @throws TDbException if the transaction or the DB connection is not active.
	 */
	public function rollback()
	{
		if (!$this->getIsOperable()) {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
		$rolledBack = $this->getConnection()->getPdoInstance()->rollBack();
		$this->setActive(false);
		if ($rolledBack !== false) {
			$this->onRollback();
		}
	}

	/**
	 * Ends the transaction because its connection is closing.  When the database still
	 * holds the transaction (`PDO::inTransaction()`), it is rolled back, as the database
	 * discards the uncommitted work of a closing connection; a rollback the connection
	 * cannot send is left to the server.  When the database no longer holds it, the
	 * transaction ended in a way this object did not see, such as a commit on the PDO
	 * instance, and its outcome is unknown.  The transaction is inactive afterwards.
	 * {@see \Prado\Data\TDbConnection} calls this before it drops the PDO instance, and
	 * raises {@see onRollback OnRollback} after, when this returns true.
	 * @return bool whether the transaction was rolled back; false when it was inactive or
	 *   its outcome is unknown.
	 * @since 4.4.0
	 */
	public function endOnClose(): bool
	{
		if (!$this->getActive()) {
			return false;
		}
		$this->setActive(false);
		$pdo = $this->getConnection()->getPdoInstance();
		if ($pdo === null || !$pdo->inTransaction()) {
			return false;
		}
		try {
			$pdo->rollBack();
		} catch (\PDOException $e) {
			// The connection is going away; the server discards the uncommitted work.
		}
		return true;
	}

	/**
	 * Raises the OnCommit event after the database commits the transaction.
	 * The sender is this inactive transaction, and the parameter is null.
	 * @since 4.4.0
	 */
	public function onCommit()
	{
		$this->raiseEvent('OnCommit', $this, null);
	}

	/**
	 * Raises the OnRollback event after the database rolls back the transaction, by
	 * {@see rollback()} or by the closing of its connection ({@see endOnClose()}).
	 * The sender is this inactive transaction, and the parameter is null.
	 * @since 4.4.0
	 */
	public function onRollback()
	{
		$this->raiseEvent('OnRollback', $this, null);
	}

	/**
	 * Returns whether the transaction can commit or roll back.
	 * @return bool whether this transaction and its DB connection are both active.
	 * @since 4.4.0
	 */
	protected function getIsOperable(): bool
	{
		return $this->getActive() && $this->getConnection()->getActive();
	}

	/**
	 * @return \Prado\Data\TDbConnection the DB connection for this transaction
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return bool whether this transaction is active
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param bool $value whether this transaction is active
	 */
	protected function setActive($value)
	{
		$this->_active = TPropertyValue::ensureBoolean($value);
	}
}
