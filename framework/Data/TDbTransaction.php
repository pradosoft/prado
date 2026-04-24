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
 * try
 * {
 *    $transaction=$connection->beginTransaction();
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... other SQL executions
 *    $transaction->commit();
 * }
 * catch(Exception $e)
 * {
 *    $transaction->rollBack();
 * }
 * ```
 *
 * Since 4.3.3, TDbTransaction supports serial transaction mode for drivers
 * that always keep an implicit transaction alive (e.g. Firebird/pdo_firebird).
 * In serial mode, the transaction remains active after commit or rollback
 * and immediately begins a new explicit transaction. This provides seamless
 * reuse of the transaction object without additional calls.
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
	 * Creates a command for execution.
	 * @param string $sql SQL statement associated with the new command.
	 * @throws TDbException if the connection is not active
	 * @return TDbCommand the DB command
	 * @since 4.3.3
	 */
	public function createCommand($sql)
	{
		return $this->getConnection()->createCommand($sql);
	}

	/**
	 * @return TDbMetaData
	 */
	public function getDbMetaData()
	{
		return $this->getConnection()->getDbMetaData();
	}

	/**
	 * Commits a transaction.
	 *
	 * For Firebird connections, `pdo_firebird` starts a new implicit transaction
	 * immediately inside `isc_commit_transaction`, before the just-committed
	 * transaction's changes are fully visible in Firebird's Transaction Inventory
	 * Page. That implicit transaction's MVCC snapshot can therefore miss rows
	 * committed by the transaction that was just finished, which causes subsequent
	 * reads (including DELETE cleanup in test setUp) to see stale data. Committing
	 * the empty implicit transaction forces pdo_firebird to open a fresh one whose
	 * snapshot is guaranteed to reflect the completed commit.
	 *
	 * @throws TDbException if the transaction or the DB connection is not active.
	 */
	public function commit()
	{
		$connection = $this->getConnection();
		
		if (!$this->getActive() || !$connection->getActive()) {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
		
		$pdo = $connection->getPdoInstance();
		$pdo->commit();

		if ($this->isTransactionComplete()) {
			// pdo_firebird starts a new implicit transaction immediately after
			// commit, with a snapshot that may not yet reflect the committed
			// data. Commit it so the next read starts with a fresh snapshot.
			if (TDbDriverCapabilities::requiresPostTransactionFlush($pdo->getAttribute(PDO::ATTR_DRIVER_NAME))) {
				try {
					$pdo->commit();
				} catch (\Exception $e) {
				}
			}
			$this->setActive(false);
		}
	}

	/**
	 * Rolls back a transaction.
	 *
	 * For Firebird connections, `pdo_firebird` starts a new implicit transaction
	 * immediately inside `isc_rollback_transaction`, before the rolled-back
	 * transaction is fully recorded in Firebird's Transaction Inventory Page.
	 * That implicit transaction's MVCC snapshot can therefore see stale data
	 * (e.g. a pre-rollback committed row whose deletion is not yet visible).
	 * Committing the empty implicit transaction forces pdo_firebird to open a
	 * fresh one whose snapshot is guaranteed to reflect the completed rollback,
	 * so that subsequent reads on the same connection return correct results.
	 *
	 * @throws TDbException if the transaction or the DB connection is not active.
	 */
	public function rollback()
	{
		$connection = $this->getConnection();
		
		if (!$this->getActive() || !$connection->getActive()) {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
		
		$pdo = $connection->getPdoInstance();
		$pdo->rollBack();

		if ($this->isTransactionComplete()) {
			// pdo_firebird starts a new implicit transaction immediately after
			// rollback, with a snapshot that may not yet reflect the rolled-back
			// state. Commit it so the next read starts with a fresh snapshot.
			if (TDbDriverCapabilities::requiresPostTransactionFlush($pdo->getAttribute(PDO::ATTR_DRIVER_NAME))) {
				try {
					$pdo->commit();
				} catch (\Exception $e) {
				}
			}
			$this->setActive(false);
		}
	}

	/**
	 * Children should override this if the transaction is not complete after
	 * rollback/commit, eg Serial.
	 * @return bool should the transaction mark as no longer active.
	 * @since 4.3.3
	 */
	public function isTransactionComplete(): bool
	{
		return true;
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
	protected function setActive(bool $value)
	{
		$this->_active = $value;
	}
}
