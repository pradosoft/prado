<?php
/**
 * TDbTransaction class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2009 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Prado::using('System.Db.TDbDataReader');


/**
 * TDbTransaction represents a DB transaction.
 *
 * It is usually created by calling {@link TDbConnection::beginTransaction}.
 *
 * The following code is a common scenario of using transactions:
 * <pre>
 * $transaction=$connection->beginTransaction();
 * try
 * {
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... other SQL executions
 *    $transaction->commit();
 * }
 * catch(Exception $e)
 * {
 *    $transaction->rollBack();
 * }
 * </pre>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package system.db
 * @since 1.0
 */
class TDbTransaction extends TComponent
{
	private $_connection=null;
	private $_active;

	/**
	 * Constructor.
	 * @param TDbConnection the connection associated with this transaction
	 * @see TDbConnection::beginTransaction
	 */
	public function __construct(TDbConnection $connection)
	{
		$this->_connection=$connection;
		$this->setActive(true);
	}

	/**
	 * Commits a transaction.
	 * @throws CException if the transaction or the DB connection is not active.
	 */
	public function commit()
	{
		if($this->_active && $this->_connection->getActive())
		{
			Prado::trace('Committing transaction','system.Db.TDbTransaction');
			$this->_connection->getPdoInstance()->commit();
			$this->_active=false;
		}
		else
			throw new TDbException('dbtransaction_transaction_inactive');
	}

	/**
	 * Rolls back a transaction.
	 * @throws CException if the transaction or the DB connection is not active.
	 */
	public function rollback()
	{
		if($this->_active && $this->_connection->getActive())
		{
			Prado::trace('Rolling back transaction','system.Fb.TDbTransaction');
			$this->_connection->getPdoInstance()->rollBack();
			$this->_active=false;
		}
		else
			throw new TDbException('dbtransaction_transaction_inactive');
	}

	/**
	 * @return TDbConnection the DB connection for this transaction
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return boolean whether this transaction is active
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param boolean whether this transaction is active
	 */
	protected function setActive($value)
	{
		$this->_active=TPropertyValue::ensureBoolean($value);
	}
}
