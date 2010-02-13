<?php
/**
 * TMasterSlaveDataSourceConfig class file.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Testing.Data.Distributed.MasterSlave
 */

	Prado::using('System.Testing.Data.Distributed.TDistributedDataSourceConfig');
	Prado::using('System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbConnection');

	/**
	 * TMasterSlaveDataSourceConfig module class provides <module> configuration for database connections in master/slave senario.
	 *
	 * IMPORTANT!!!
	 * BETA Version - Use with care and NOT in production environment (only tested with MySql)
	 *
	 * Example usage: mysql connection
	 * <code>
	 * <modules>
	 * <module id="db1" class="System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDataSourceConfig"
	 *	ConnectionClass="System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbConnection"
	 *	DistributedConnectionClass="System.Testing.Data.Distributed.MasterSlave.TSlaveDbConnection"
	 *	DbConnection.StatementAnalyserClass="System.Testing.Data.Analysis.TSimpleDbStatementAnalysis">
	 *		<database ConnectionString="mysql:host=127.0.0.1;port=3306;dbname=mydatabase" Username="dbuser" Password="dbpass" />
	 * 		<slave ConnectionString="mysql:host=127.0.0.1;port=3307;dbname=mydatabase" Username="dbuser" Password="dbpass" Weight="3" />
	 * 		<slave ConnectionString="mysql:host=127.0.0.1;port=3308;dbname=mydatabase" Username="dbuser" Password="dbpass" Weight="2" />
	 * 		<slave ConnectionString="mysql:host=127.0.0.1;port=3309;dbname=mydatabase" Username="dbuser" Password="dbpass" Weight="5" />
	 *	</module>
	 * </modules>
	 * </code>
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed.MasterSlave
	 * @since 4.0
	 */
	class TMasterSlaveDataSourceConfig extends TDistributedDataSourceConfig
	{
		/**
		 * @var boolean
		 */
		private $_bMasterInitialized = false;

		/**
		 * @var boolean
		 */
		private $_bSlaveInitialized = false;

		/**
		 * Constructor
		 */
		public function __construct()
		{
			$this->setConnectionClass('System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbConnection');
			$this->setDistributedConnectionClass('System.Testing.Data.Distributed.MasterSlave.TSlaveDbConnection');
		}

		/**
		 * Initalize the database connection properties from attributes in slave tag.
		 * @param TXmlDocument xml configuration.
		 */
		protected function initChildConnectionData($xml)
		{
			parent::initChildConnectionData($xml, 'slave');
		}

		/**
		 * @return IMasterSlaveDbConnection
		 */
		public function getDbConnection() {
			$id = $this->getID();
			static $result = array();

			if(!isset($result[$id]))
				$result[$id] = parent::getDbConnection();

			if(!$this->bInitialized)
				return $result[$id];

			if($this->_bMasterInitialized)
				return $result[$id];

			$this->_bMasterInitialized = true;

			if(!$result[$id] instanceof IMasterSlaveDbConnection)
				return $result[$id];

			$slave = parent::getDistributedDbConnection();

			if($slave instanceof ISlaveDbConnection && $slave->getMasterConnection()===null)
				$slave->setMasterConnection($result[$id]);

			if($result[$id]->getSlaveConnection()===null)
				$result[$id]->setSlaveConnection($slave);

			return $result[$id];
		}

		/**
		 * @return ISlaveDbConnection
		 */
		public function getDistributedDbConnection() {
			$id = $this->getID();
			static $result = array();

			if(!isset($result[$id]))
				$result[$id] = parent::getDistributedDbConnection();

			if(!$this->bInitialized)
				return $result[$id];

			if($this->_bSlaveInitialized)
				return $result[$id];

			$this->_bSlaveInitialized = true;

			if(!$result[$id] instanceof ISlaveDbConnection)
				return $result[$id];

			$master = parent::getDbConnection();

			if($master instanceof IMasterSlaveDbConnection && ($master->getSlaveConnection()===null))
				$master->setSlaveConnection($result[$id]);

			if($result[$id]->getMasterConnection()===null)
				$result[$id]->setMasterConnection($master);

			return $result[$id];
		}

		/**
		 * Alias for getDbConnection().
		 * @return IMasterSlaveDbConnection database connection.
		 */
		public function getMasterDbConnection()
		{
			return $this->getDbConnection();
		}

		/**
		 * Alias for getDistributedDbConnection().
		 * @return ISlaveDbConnection database connection.
		 */
		public function getSlaveDbConnection()
		{
			return $this->getDistributedDbConnection();
		}
	}
?>