<?php
/**
 * TPreparedStatementFactory class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Data.SqlMap.Statements
 */

/**
 * TPreparedStatementFactory class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.SqlMap.Statements
 * @since 3.1
 */
class TPreparedStatementFactory
{
	private $_statement;
	private $_preparedStatement;
	private $_parameterPrefix = 'param';
	private $_commandText;

	public function __construct($statement, $sqlString)
	{
		$this->_statement = $statement;
		$this->_commandText = $sqlString;
	}

	public function prepare()
	{
		$this->_preparedStatement = new TPreparedStatement();
		$this->_preparedStatement->setPreparedSql($this->_commandText);
		if($this->_statement->parameterMap()!==null)
			$this->createParametersForTextCommand();
		return $this->_preparedStatement;
	}

	protected function createParametersForTextCommand()
	{
		foreach($this->_statement->ParameterMap()->getProperties() as $prop)
			$this->_preparedStatement->getParameterNames()->add($prop->getProperty());
	}
}

