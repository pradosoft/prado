<?php
/**
 * IMasterSlaveDbConnection, ISlaveDbConnection inferface,
 * TMasterSlaveDbConnection, TSlaveDbConnection class file.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Testing.Data.Distributed.MasterSlave
 * @todo Test with Backport of Yii's DBO
 */

	Prado::using('System.Data.TDbConnection');
	Prado::using('System.Testing.Data.Distributed.TDistributedDbConnection');
	Prado::using('System.Collections.TQueue');
	Prado::using('System.Collections.TStack');
	Prado::using('System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbTransaction');

	/**
	 * IMasterSlaveDbConnection interface
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed.MasterSlave
	 * @since 4.0
	 */
	interface IMasterSlaveDbConnection extends IDistributedDbConnection
	{
		/**
		 * @return TQueue
		 */
		public function getStatementQueue();

		/**
		 * @return ISlaveDbConnection|null
		 */
		public function getSlaveConnection();

		/**
		 * @param ISlaveDbConnection|null
		 */
		public function setSlaveConnection($conn);

		/**
		 * @return TMasterSlaveDbConnectionForceMaster
		 */
		public function getForceMaster();

		/**
		 * @param TMasterSlaveDbConnectionForceMaster
		 */
		public function setForceMaster($value);

	}

	/**
	 * ISlaveDbConnection interface
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed.MasterSlave
	 * @since 4.0
	 */
	interface ISlaveDbConnection extends IDistributedDbConnection
	{
		/**
		 * @return IMasterSlaveDbConnection|null
		 */
		public function getMasterConnection();

		/**
		 * @param IMasterSlaveDbConnection|null
		 */
		public function setMasterConnection($conn);

		/**
		 * @return TMasterSlaveDbConnectionForceMaster
		 */
		public function getForceMaster();

		/**
		 * @param TMasterSlaveDbConnectionForceMaster
		 */
		public function setForceMaster($value);
	}

	/**
	 * TMasterSlaveDbConnection class
	 *
	 * IMPORTANT!!!
	 * BETA Version - Use with care and NOT in production environment (only tested with MySql)
	 *
	 * TMasterSlaveDbConnection represents a master connection to a database in master/slave senario.
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed.MasterSlave
	 * @since 4.0
	 */
	class TMasterSlaveDbConnection extends TDistributedDbConnection implements IMasterSlaveDbConnection
	{
		/**
		 * @var TSlaveDbConnection|null
		 */
		private $_connSlave = null;

		/**
		 * @var TQueue
		 */
		private $_statementQueue = null;

		/**
		 * @var integer
		 * @see TMasterSlaveDbConnectionForceMaster
		 */
		private $_forceMaster = TMasterSlaveDbConnectionForceMaster::OFF_AUTOMATIC;

		private $_forceMasterStack = null;

		/**
		 * Constructor.
		 * Note, the DB connection is not established when this connection
		 * instance is created. Set {@link setActive Active} property to true
		 * to establish the connection.
		 *
		 * @param string The Data Source Name, or DSN, contains the information required to connect to the database.
		 * @param string The user name for the DSN string.
		 * @param string The password for the DSN string.
		 * @param string Charset used for DB Connection (MySql & pgsql only). If not set, will use the default charset of your database server
		 * @see http://www.php.net/manual/en/function.PDO-construct.php
		 */
		public function __construct($dsn='', $username='', $password='', $charset='')
		{
			parent::__construct($dsn, $username, $password, $charset);
			parent::setTransactionClass('System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbTransaction');
		}

		/**
		 * @return TQueue
		 */
		public function getStatementQueue()
		{
			if($this->_statementQueue===null)
				$this->_statementQueue = new TQueue();
			return $this->_statementQueue;
		}

		/**
		 * @return ISlaveDbConnection|null
		 */
		public function getSlaveConnection()
		{
			return $this->_connSlave;
		}

		/**
		 * @param ISlaveDbConnection|null
		 * @throws TDbConnectionException if the slave connection already exists
		 * @throws TDbConnectionException connection not instance of ISlaveDbConnection
		 */
		public function setSlaveConnection($conn)
		{
			if($this->_connSlave !== null)
				throw new TDbConnectionException('masterslavedbconnection_connection_exists', get_class($this), 'SlaveConnection');

			if($conn!==null && !$conn instanceof ISlaveDbConnection)
				throw new TDbConnectionException('masterslavedbconnection_interface_required', get_class($this), 'SlaveConnection', 'ISlaveDbConnection');

			$this->_connSlave = $conn;

			if($this->_connSlave===null) return;
			if($this->_connSlave->getMasterConnection()!==null) return;

			$this->_connSlave->setMasterConnection($this);
		}

		/**
		 * Creates a command for execution.
		 * @param string SQL statement associated with the new command.
		 * @return TDistributedDbCommand the DB command
		 * @throws TDbException if the connection is not active
		 */
		public function createCommand($sql)
		{
			$force = $this->getForceMaster();
			if($force == TMasterSlaveDbConnectionForceMaster::ON_MANUAL || $force == TMasterSlaveDbConnectionForceMaster::ON_TRANSACTION)
			{
				Prado::log('ForceMaster: ' . $force, TLogger::DEBUG, 'System.Testing.Data.Distributed.MasterSlave.TMasterSlaveDbConnection');
				return new TDistributedDbCommand($this, $sql, TDbStatementClassification::UNKNOWN);
			}

			$bEnqueue	= false;
			$bMaster	= true;

			$classification = $this->getStatementClassification($sql);

			switch($classification) {
				case TDbStatementClassification::CONTEXT:
					$bEnqueue	= true;
					$bMaster	= true;
				break;
				case TDbStatementClassification::SQL:
					$bMaster = false;
				break;
				case TDbStatementClassification::TCL:
					$this->setForceMaster(TMasterSlaveDbConnectionForceMaster::ON_TCL);
				case TDbStatementClassification::DDL:
				case TDbStatementClassification::DML:
				case TDbStatementClassification::DCL:
				case TDbStatementClassification::UNKNOWN:
				default:
					$bMaster = true;
				break;
			}

			$bMaster = $bMaster || $this->getForceMaster();

			$result = new TDistributedDbCommand(($bMaster ? $this : $this->getSlaveConnection()), $sql, $classification);
			//$result = new TDistributedDbCommand($this, $sql, $classification);

			if($bEnqueue)
				$this->getStatementQueue()->enqueue($result);

			return $result;
		}

		/**
		 * @return TMasterSlaveDbConnectionForceMaster
		 */
		public function getForceMaster()
		{
			return $this->_forceMaster;
		}

		/**
		 * @param TMasterSlaveDbConnectionForceMaster
		 */
		public function setForceMaster($value)
		{
			if($this->_forceMasterStack===null)
				$this->_forceMasterStack = new TStack();

			if($value)
			{
				$this->_forceMaster = (integer)$value;
				$this->_forceMasterStack->push((integer)$value);
			}
			elseif($this->_forceMasterStack->count() > 0)
				$this->_forceMaster = $this->_forceMasterStack->pop();
			else
				$this->_forceMaster = (integer)$value;
		}

		/**
		 * @return TDbConnectionServerRole
		 */
		public function getServerRole()
		{
			return TDbConnectionServerRole::Master;
		}
	}

	/**
	 * TSlaveDbConnection class
	 *
	 * IMPORTANT!!!
	 * BETA Version - Use with care and NOT in production environment (only tested with MySql)
	 *
	 * TSlaveDbConnection represents a readonly connection to a database in master/slave senario.
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed.MasterSlave
	 * @since 4.0
	 */
	class TSlaveDbConnection extends TDbConnection implements ISlaveDbConnection
	{
		/**
		 * @var TMasterSlaveDbConnection|null
		 */
		private $_connMaster = null;

		/**
		 * @return IMasterSlaveDbConnection|null
		 */
		public function getMasterConnection()
		{
			return $this->_connMaster;
		}

		/**
		 * @param IMasterSlaveDbConnection|null
		 * @throws TDbConnectionException if the master connection already exists
		 * @throws TDbConnectionException connection not instance of IMasterSlaveDbConnection
		 */
		public function setMasterConnection($conn)
		{
			if($this->_connMaster!==null)
				throw new TDbConnectionException('masterslavedbconnection_connection_exists', get_class($this), 'MasterConnection');

			if($conn!==null && !$conn instanceof IMasterSlaveDbConnection)
				throw new TDbConnectionException('masterslavedbconnection_interface_required', get_class($this), 'MasterConnection', 'IMasterSlaveDbConnection');

			$this->_connMaster = $conn;
		}

		/**
		 * Creates a command for execution.
		 * @param string SQL statement associated with the new command.
		 * @return TDistributedDbCommand the DB command
		 * @throws TDbException if the connection is not active
		 */
		public function createCommand($sql)
		{
			$force = $this->getForceMaster();
			if($force == TMasterSlaveDbConnectionForceMaster::ON_MANUAL || $force == TMasterSlaveDbConnectionForceMaster::ON_TRANSACTION)
			{
				Prado::log('ForceMaster: ' . $force, TLogger::DEBUG, 'System.Testing.Data.Distributed.MasterSlave.TSlaveDbConnection');
				return new TDistributedDbCommand($this->getMasterConnection(), $sql, TDbStatementClassification::UNKNOWN);
			}

			$bEnqueue	= false;
			$bMaster	= false;

			$classification = $this->getStatementClassification($sql);

			switch($classification) {
				case TDbStatementClassification::SQL:
					$bMaster = false;
				break;
				case TDbStatementClassification::CONTEXT:
					$bEnqueue	= true;
					$bMaster	= true;
				break;
				case TDbStatementClassification::TCL:
					$this->setForceMaster(TMasterSlaveDbConnectionForceMaster::ON_TCL);
				case TDbStatementClassification::DDL:
				case TDbStatementClassification::DML:
				case TDbStatementClassification::DCL:
				case TDbStatementClassification::UNKNOWN:
				default:
					$bMaster = true;
				break;
			}

			$bMaster = $bMaster || $this->getForceMaster();

			$result = new TDistributedDbCommand(($bMaster ? $this->getMasterConnection() : $this), $sql, $classification);

			if($bEnqueue)
				$this->getMasterConnection()->getStatementQueue()->enqueue($result);

			return $result;
		}

		/**
		 * Starts a transaction.
		 * @return TDbTransaction the transaction initiated
		 * @throws TDbException if no master connection exists or the connection is not active
		 */
		public function beginTransaction()
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');

			return $this->getMasterConnection()->beginTransaction();
		}

		/**
		 * @return string Transaction class name to be created by calling {@link TDbConnection::beginTransaction}.
		 * @throws TDbException if no master connection exists
		 */
		public function getTransactionClass()
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');
			return $this->getMasterConnection()->getTransactionClass();
		}

		/**
		 * @param string Transaction class name to be created by calling {@link TDbConnection::beginTransaction}.
		 * @throws TDbException if no master connection exists
		 */
		public function setTransactionClass($value)
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');
			$this->getMasterConnection()->setTransactionClass($value);
		}

		/**
		 * Gets the statement analyser of type given by
		 * {@link setStatementAnalyserClass StatementAnalyserClass }.
		 * @return IDbStatementAnalysis statement analyser.
		 * @throws TDbException if no master connection exists
		 */
		public function getStatementAnalyser()
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');
			return $this->getMasterConnection()->getStatementAnalyser();
		}

		/**
		 * The statement analyser class name to be created when {@link getStatementAnalyserClass}
		 * method is called. The {@link setStatementAnalyserClass StatementAnalyserClass}
		 * property must be set before calling {@link getStatementAnalyser} if you wish to
		 * create the connection using the  given class name.
		 * @param string Statement analyser class name.
		 * @throws TDbException if no master connection exists
		 */
		public function setStatementAnalyserClass($value)
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');
			$this->getMasterConnection()->setStatementAnalyserClass($value);
		}

		/**
		 * @param string Statement analyser class name to be created.
		 * @throws TDbException if no master connection exists
		 */
		public function getStatementAnalyserClass()
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');
			return $this->getMasterConnection()->getStatementAnalyserClass();
		}

		/**
		 * @return TMasterSlaveDbConnectionForceMaster
		 * @throws TDbException if no master connection exists
		 */
		public function getForceMaster()
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');
			return $this->getMasterConnection()->getForceMaster();
		}

		/**
		 * @param TMasterSlaveDbConnectionForceMaster
		 * @throws TDbException if no master connection exists
		 */
		public function setForceMaster($value)
		{
			if($this->getMasterConnection() === null)
				throw new TDbException('slavedbconnection_requires_master', getclass($this), 'MasterConnection');
			$this->getMasterConnection()->setForceMaster($value);
		}

		/**
		 * @return TDbConnectionServerRole
		 */
		public function getServerRole()
		{
			return TDbConnectionServerRole::Slave;
		}
	}

	/**
	 * TMasterSlaveDbConnectionForceMaster class
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed.MasterSlave
	 * @since 4.0
	 */
	class TMasterSlaveDbConnectionForceMaster extends TEnumerable
	{
		const OFF_AUTOMATIC		= 0;
		const ON_MANUAL			= 1;
		const ON_TCL			= -1;
		const ON_TRANSACTION	= -2;
	}
