<?php
/**
 * IDbStatementAnalysis, TDbStatementAnalysisParameter,
 * TDbStatementAnalysis, TDbStatementClassification file.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Testing.Data.Analysis
 */

	/**
	 * IDbStatementAnalysis interface
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @license http://www.pradosoft.com/license/
	 * @version $Id$
	 * @package System.Testing.Data.Analysis
	 * @since 4.0
	 */
	interface IDbStatementAnalysis
	{
		/**
		 * @param TDbStatementAnalysisParamete
		 * @return TDbStatementClassification
		 */
		public static function doClassificationAnalysis(TDbStatementAnalysisParameter $param);

		/**
		 * @param TDbStatementAnalysisParameter
		 * @return TDbStatementClassification
		 */
		public function getClassificationAnalysis(TDbStatementAnalysisParameter $param);

		/**
		 * @param string PDO drivername of connection
		 */
		public function setDriverName($value);

		/**
		 * @return string PDO drivername of connection
		 */
		public function getDriverName();
	}

	/**
	 * TDbStatementAnalysisParameter class
	 *
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @license http://www.pradosoft.com/license/
	 * @version $Id$
	 * @package System.Testing.Data.Analysis
	 * @since 4.0
	 */
	class TDbStatementAnalysisParameter
	{
		/**
		 * @var string The SQL statement that should be analysed
		 */
		protected $_statement = null;

		/**
		 * TDbStatementClassification Defaults to 'UNKNOWN'
		 */
		protected $_defaultClassification = TDbStatementClassification::UNKNOWN;

		/**
		 * string|null PDO drivername of connection
		 */
		protected  $_drivername	= null;

		/**
		 * @param string The SQL statement that should be analysed
		 * @param TDbStatementClassification
		 * @param string|null PDO drivername of connection
		 */
		public function __construct($statement='', $defaultClassification=null, $drivername=null)
		{
			$this->setStatement($statement);
			$this->setDefaultClassification($defaultClassification);
			$this->setDriverName($drivername);
		}

		/**
		 * @param string The SQL statement that should be analysed
		 */
		public function setStatement($value)
		{
			$this->_statement = (string)$value;
		}

		/**
		 * @return string The SQL statement that should be analysed
		 */
		public function getStatement()
		{
			return $this->_statement;
		}

		/**
		 * @param string|null PDO drivername of connection
		 */
		public function setDriverName($value)
		{
			$this->_drivername = ($value===null) ? null : (string)$value;
		}

		/**
		 * @return string|null PDO drivername of connection
		 */
		public function getDriverName()
		{
			return $this->_drivername;
		}

		/**
		 * @param TDbStatementClassification Defaults to 'UNKNOWN'
		 */
		public function setDefaultClassification($value)
		{
			if($value!==null)
				$this->_defaultClassification = (string)$value;
			else
				$this->_defaultClassification = TDbStatementClassification::UNKNOWN;
		}

		/**
		 * @return TDbStatementClassification
		 */
		public function getDefaultClassification()
		{
			return $this->_defaultClassification;
		}
	}

	/**
	 * TDbStatementAnalysis class
	 *
	 * Basic "dummy" implementation allways return {@link TDbStatementAnalysisParameter::getDefaultClassification DefaultClassification}
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @license http://www.pradosoft.com/license/
	 * @version $Id$
	 * @package System.Testing.Data.Analysis
	 * @since 4.0
	 */
	class TDbStatementAnalysis implements IDbStatementAnalysis
	{
		/**
		 * @var string|null PDO drivername of connection
		 */
		protected $_drivername = null;

		/**
		 * @param string|null PDO drivername of connection
		 */
		public function setDriverName($value)
		{
			$this->_drivername = ($value===null) ? null : (string)$value;
		}

		/**
		 * @return string|null PDO drivername of connection
		 */
		public function getDriverName()
		{
			return $this->_drivername;
		}

		/**
		 * @param TDbStatementAnalysisParamete
		 * @return TDbStatementClassification
		 */
		public static function doClassificationAnalysis(TDbStatementAnalysisParameter $param)
		{
			return $param->getDefaultClassification();
		}

		/**
		 * @param TDbStatementAnalysisParameter
		 * @return TDbStatementClassification
		 */
		public function getClassificationAnalysis(TDbStatementAnalysisParameter $param)
		{
			return $param->getDefaultClassification();
		}
	}

	/**
	 * TDbStatementClassification
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @license http://www.pradosoft.com/license/
	 * @version $Id$
	 * @package System.Testing.Data.Analysis
	 * @since 4.0
	 */
	class TDbStatementClassification extends TEnumerable
	{
		/**
		 * Structured Query Language
		 */
		const SQL = 'SQL';

		/**
		 * Data Definition Language
		 */
		const DDL = 'DDL';

		/**
		 * Data Manipulation Language
		 */
		const DML = 'DML';

		/**
		 * Data Control Language
		 */
		const DCL = 'DCL';

		/**
		 * Transaction Control Language
		 */
		const TCL = 'TCL';

		/**
		 * classification depends on subsequent statement(s)
		 */
		const CONTEXT = 'CONTEXT';

		/**
		 * unable to detect real classification or multiple possibilities
		 */
		const UNKNOWN = 'UNKNOWN';
	}
?>
