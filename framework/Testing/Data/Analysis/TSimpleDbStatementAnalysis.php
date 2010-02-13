<?php
/**
 * TSimpleDbStatementAnalysis file.
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Testing.Data.Analysis
 */

	Prado::using('System.Testing.Data.Analysis.TDbStatementAnalysis');

	/**
	 * TSimpleDbStatementAnalysis class
	 *
	 * IMPORTANT!!!
	 * BETA Version - Use with care and NOT in production environment (only tested with MySql)
	 *
	 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
	 * @license http://www.pradosoft.com/license/
	 * @version $Id$
	 * @package System.Testing.Data.Analysis
	 * @since 4.0
	 * @todo SELECT * FOR UPDATE (row lock)
	 * @todo SELECT * INTO [ TEMPORARY | TEMP ] [ TABLE ] new_table (PostgreSQL)
	 * @todo mysql conditional commands in multiline comments e.g. / *! MySQL specific code * /
	 */
	class TSimpleDbStatementAnalysis extends TDbStatementAnalysis
	{
		/**
		 * @var array mapping of commands to classification
		 */
		protected static $mappingClassificationAnalysis = array(
			'CREATE'	=> TDbStatementClassification::DDL,
			'DROP'		=> TDbStatementClassification::DDL,
			'ALTER'		=> TDbStatementClassification::DDL,
			'RENAME'	=> TDbStatementClassification::DDL,

			'INSERT'	=> TDbStatementClassification::DML,
			'UPDATE'	=> TDbStatementClassification::DML,
			'DELETE'	=> TDbStatementClassification::DML,
			'REPLACE'	=> TDbStatementClassification::DML,
			'TRUNCATE'	=> TDbStatementClassification::DML,
			'LOAD'		=> TDbStatementClassification::DML,

			'GRANT'		=> TDbStatementClassification::DCL,
			'REVOKE'	=> TDbStatementClassification::DCL,

			'XA'				=> TDbStatementClassification::TCL,
			'SAVEPOINT'			=> TDbStatementClassification::TCL,
			'CHECKPOINT'		=> TDbStatementClassification::TCL,
			'RELEASE SAVEPOINT'	=> TDbStatementClassification::TCL,
			'START TRANSACTION'	=> TDbStatementClassification::TCL,
			'BEGIN'				=> TDbStatementClassification::TCL,
			'COMMIT'			=> TDbStatementClassification::TCL,
			'ROLLBACK'			=> TDbStatementClassification::TCL,
			'LOCK'				=> TDbStatementClassification::TCL,
			'UNLOCK'			=> TDbStatementClassification::TCL,
			'ABORT'				=> TDbStatementClassification::TCL,
			'END'				=> TDbStatementClassification::TCL,

			'SELECT'	=> TDbStatementClassification::SQL,

			'SHOW'		=> TDbStatementClassification::SQL,
			'DESCRIBE'	=> TDbStatementClassification::SQL,
			'EXPLAIN'	=> TDbStatementClassification::SQL,
			'PRAGMA'	=> TDbStatementClassification::SQL,

			'SET'	=> TDbStatementClassification::CONTEXT,
			'USE'	=> TDbStatementClassification::CONTEXT,

			'CALL'			=> TDbStatementClassification::UNKNOWN,
			'EXEC'			=> TDbStatementClassification::UNKNOWN,
			'PREPARE'		=> TDbStatementClassification::UNKNOWN,
			'EXECUTE'		=> TDbStatementClassification::UNKNOWN,
			'DEALLOCATE'	=> TDbStatementClassification::UNKNOWN,
		);

		/**
		 * @var array
		 */
		protected static $cacheClassificationAnalysis = array();

		/**
		 * @var string
		 */
		protected static $regExpClassificationAnalysis = null;

		/**
		 * @param TDbStatementAnalysisParamete
		 * @return TDbStatementClassification
		 */
		public static function doClassificationAnalysis(TDbStatementAnalysisParameter $param)
		{
			$statement	= $param->getStatement();
			$default	= $param->getDefaultClassification();

			$hash = md5($statement . '-' . $default);
			
			if( isset(self::$cacheClassificationAnalysis[$hash]) )
				return self::$cacheClassificationAnalysis[$hash];
			
			self::$cacheClassificationAnalysis[$hash] = $default;
				
			$statement = preg_replace('/(?:--|\\#)[\x20\\t\\S]*\s+|\/\\*[\x20\\t\\n\\r\\S]*?\\*\//Ssmux', '', $statement);
			$statement = preg_replace('/[\s]+/Smu', ' ', $statement);
			$statement = trim($statement);
			
			if(self::$regExpClassificationAnalysis===null)
				self::$regExpClassificationAnalysis = '/^(' . str_replace(' ', '\x20', implode('|', array_keys(self::$mappingClassificationAnalysis))) . ')+[\s]+.*|\k1/Siu';
			
			$cmd = strToUpper(preg_replace(self::$regExpClassificationAnalysis, '\1', $statement));
			
			if( isset(self::$mappingClassificationAnalysis[$cmd]) )
				self::$cacheClassificationAnalysis[$hash] = self::$mappingClassificationAnalysis[$cmd];

			return self::$cacheClassificationAnalysis[$hash];
		}

		/**
		 * @param TDbStatementAnalysisParameter
		 * @return TDbStatementClassification
		 */
		public function getClassificationAnalysis(TDbStatementAnalysisParameter $param)
		{
			if( ($drivername = $this->getDriverName())!== null )
				$param->setDriverName($drivername);

			return self::doClassificationAnalysis($param);
		}
	}
?>