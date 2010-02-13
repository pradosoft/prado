<?php
/**
 * TMasterSlaveDbTransaction class file.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Testing.Data.Distributed.MasterSlave
 */
	Prado::using('System.Data.TDbTransaction');

	/**
	 * TMasterSlaveDbTransaction class
	 *
	 * IMPORTANT!!!
	 * BETA Version - Use with care and NOT in production environment (only tested with MySql)
	 *
	 * TMasterSlaveDbTransaction represents a DB transaction in master/slave senario.
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed.MasterSlave
	 * @since 4.0
	 */
	class TMasterSlaveDbTransaction extends TDbTransaction
	{
		/**
		 * @var boolean
		 */
		private $_compatible = false;

		/**
		 * Constructor.
		 * @param TDbConnection the connection associated with this transaction
		 * @see TDbConnection::beginTransaction
		 */
		public function __construct(TDbConnection $connection)
		{
			if($connection instanceof ISlaveDbConnection)
			{
				$this->_compatible = true;
				$master = $connection->getMasterConnection();
				$master->setForceMaster(TMasterSlaveDbConnectionForceMaster::ON_TRANSACTION);
				Prado::log('contstuct, ForceMaster: ON_TRANSACTION', TLogger::DEBUG, 'System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbTransaction');
				parent::__construct($master);
			}
			else
			{
				if($connection instanceof IMasterSlaveDbConnection)
				{
					$this->_compatible = true;
					$connection->setForceMaster(TMasterSlaveDbConnectionForceMaster::ON_TRANSACTION);
					Prado::log('contstuct, ForceMaster: ON_TRANSACTION', TLogger::DEBUG, 'System.TestingData.Distributed.MasterSlave.TMasterSlaveDbTransaction');
				}
				parent::__construct($connection);
			}
		}

		/**
		 * Commits a transaction.
		 * @throws TDbException if the transaction or the DB connection is not active.
		 */
		public function commit()
		{
			if($this->_compatible) $this->getConnection()->setForceMaster(TMasterSlaveDbConnectionForceMaster::OFF_AUTOMATIC);
			Prado::log('commit, ForceMaster: OFF_AUTOMATIC', TLogger::DEBUG, 'System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbTransaction');
			parent::commit();
		}

		/**
		 * Rolls back a transaction.
		 * @throws TDbException if the transaction or the DB connection is not active.
		 */
		public function rollback()
		{
			if($this->_compatible) $this->getConnection()->setForceMaster(TMasterSlaveDbConnectionForceMaster::OFF_AUTOMATIC);
			Prado::log('rollback, ForceMaster: OFF_AUTOMATIC', TLogger::DEBUG, 'System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbTransaction');
			parent::rollback();
		}
	}
?>