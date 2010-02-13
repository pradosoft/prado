<?php
/**
 * IDistributedDbConnection, TDistributedDbConnection inferface/class file.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Testing.Data.Distributed
 */

	Prado::using('System.Data.TDbConnection');
	Prado::using('System.Testing.Data.Analysis.TDbStatementAnalysis');

	/**
	 * TDistributedDbConnection interface
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed
	 * @since 4.0
	 */
	interface IDistributedDbConnection /*extends IDbConnection*/
	{
		/**
		 * Gets the statement analyser of type given by
		 * {@link setStatementAnalyserClass StatementAnalyserClass }.
		 * @return IDbStatementAnalysis statement analyser.
		 */
		public function getStatementAnalyser();

		/**
		 * The statement analyser class name to be created when {@link getStatementAnalyserClass}
		 * method is called. The {@link setStatementAnalyserClass StatementAnalyserClass}
		 * property must be set before calling {@link getStatementAnalyser} if you wish to
		 * create the connection using the  given class name.
		 * @param string Statement analyser class name.
		 */
		public function setStatementAnalyserClass($value);

		/**
		 * @param string Statement analyser class name to be created.
		 */
		public function getStatementAnalyserClass();

		/**
		 * @return TDbConnectionServerRole
		 */
		public function getServerRole();
	}

	/**
	 * TDistributedDbConnection class
	 *
	 * TDistributedDbConnection represents a conditional base connection class to a database
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @version $Id$
	 * @package System.Testing.Data.Distributed
	 * @since 4.0
	 */
	abstract class TDistributedDbConnection extends TDbConnection implements IDistributedDbConnection
	{
		/**
		 * @var string
		 */
		private $_statementAnalyserClass = 'System.Testing.Data.Analysis.TDbStatementAnalysis';

		/**
		 * @var IDbStatementAnalysis
		 */
		private $_statementAnalyser = null;

		/**
		 * Gets the statement analyser of type given by
		 * {@link setStatementAnalyserClass StatementAnalyserClass }.
		 * @return IDbStatementAnalysis statement analyser.
		 */
		public function getStatementAnalyser()
		{
			if($this->_statementAnalyser === null)
			{
				$this->setActive(true);
				$this->_statementAnalyser = Prado::createComponent($this->getStatementAnalyserClass());

				if($this->getActive())
					$this->_statementAnalyser->setDriverName($this->getDriverName());
			}
			return $this->_statementAnalyser;
		}

		/**
		 * The statement analyser class name to be created when {@link getStatementAnalyser}
		 * method is called. The {@link setStatementAnalyserClass StatementAnalyserClass}
		 * property must be set before calling {@link getStatementAnalyser} if you wish to
		 * create the connection using the given class name.
		 * @param string Statement analyser class name.
		 */
		public function setStatementAnalyserClass($value)
		{
			if($this->_statementAnalyser === null)
				$this->_statementAnalyserClass = $value;
		}

		/**
		 * @param string Statement analyser class name to be created.
		 */
		public function getStatementAnalyserClass()
		{
			return $this->_statementAnalyserClass;
		}

		/**
		 * @param string The SQL statement that should be analysed
		 * @param TDbStatementClassification
		 */
		protected function getStatementClassification($statement='', $defaultClassification=null) {
			return $this->getStatementAnalyser()->getClassificationAnalysis(new TDbStatementAnalysisParameter($statement, $defaultClassification));
		}
	}

 	/**
	 * TDistributedDbCommand
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @license http://www.pradosoft.com/license/
	 * @version $Id$
	 * @package System.Testing.Data.Distributed
	 * @since 4.0
	 */
	class TDistributedDbCommand extends TDbCommand
	{
		/**
		 * @var TDbStatementClassification
		 */
		private $_statementClassification;

		/**
		 * Constructor.
		 * @param TDbConnection the database connection
		 * @param string the SQL statement to be executed
		 * @param TDbStatementClassification Defaults to 'UNKNOWN'
		 */
		public function __construct(TDbConnection $connection, $text, $classification=TDbStatementClassification::UNKNOWN)
		{
			$connection->setActive(true);
			parent::__construct($connection, $text);
			$this->_statementClassification = $classification;
			Prado::log($classification . ', ' . $connection->getServerRole() . ': ' . preg_replace('/[\s]+/', ' ', $text), TLogger::DEBUG, 'System.Testing.Data.Distributed.TDistributedDbCommand');
		}

		/**
		 * @return TDbStatementClassification
		 */
		public function getStatementClassification()
		{
			return $this->_statementClassification;
		}
	}


 	/**
	 * TDbConnectionServerRole
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @license http://www.pradosoft.com/license/
	 * @version $Id$
	 * @package System.Testing.Data.Distributed
	 * @since 4.0
	 */
	class TDbConnectionServerRole extends TEnumerable
	{
		/**
		 * Master Server (Read/Write)
		 */
		const Master = 'Master';

		/**
		 * Slave Server (Read only)
		 */
		const Slave = 'Slave';

		/**
		 * Mirror Server (Read/Write) for further use
		 */
		//const Mirror = 'Mirror';
	}
?>