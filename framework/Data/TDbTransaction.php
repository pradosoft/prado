<?php
/**
 * TDbTransaction class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data
 */

namespace Prado\Data;

use Prado\Exceptions\TDbException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TDbTransaction class.
 *
 * TDbTransaction represents a DB transaction.
 * It is usually created by calling {@link TDbConnection::beginTransaction}.
 *
 * The following code is a common scenario of using transactions:
 * <code>
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
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Data
 * @since 3.0
 */
class TDbTransaction extends \Prado\TComponent
{
	private $_connection;
	private $_active;

	/**
	 * Constructor.
	 * @param TDbConnection $connection the connection associated with this transaction
	 * @see TDbConnection::beginTransaction
	 */
	public function __construct(TDbConnection $connection)
	{
		$this->_connection = $connection;
		$this->setActive(true);
	}

	/**
	 * Commits a transaction.
	 * @throws TDbException if the transaction or the DB connection is not active.
	 */
	public function commit()
	{
		if ($this->_active && $this->_connection->getActive()) {
			$this->_connection->getPdoInstance()->commit();
			$this->_active = false;
		} else {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
	}

	/**
	 * Rolls back a transaction.
	 * @throws TDbException if the transaction or the DB connection is not active.
	 */
	public function rollback()
	{
		if ($this->_active && $this->_connection->getActive()) {
			$this->_connection->getPdoInstance()->rollBack();
			$this->_active = false;
		} else {
			throw new TDbException('dbtransaction_transaction_inactive');
		}
	}

	/**
	 * @return TDbConnection the DB connection for this transaction
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
