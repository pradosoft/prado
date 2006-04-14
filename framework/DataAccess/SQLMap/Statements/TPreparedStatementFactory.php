<?php

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
	//	$this->_statement = new TPreparedStatement();
//		$this->_statement->setSqlString($sqlString);
	}

	public function prepare()
	{
		//$this->createParametersFromTextCommand();
		//return $this->_statement;
		$this->_preparedStatement = new TPreparedStatement();
		$this->_preparedStatement->setPreparedSql($this->_commandText);
		if(!is_null($this->_statement->parameterMap()))
		{
			$this->createParametersForTextCommand();
			//$this->evaluateParameterMap();
		}
		//var_dump($this->_preparedStatement);		
		return $this->_preparedStatement;
	}

	protected function createParametersForTextCommand()
	{
		/*$matches = array();
		$string = $this->_statement->getSqlString();
		preg_match_all('/#([a-zA-Z0-9._]+)#/', $string, $matches);
		$this->_statement->getParameterNames()->copyFrom($matches[0]);*/
		//var_dump($this->_statement);
		foreach($this->_statement->ParameterMap()->getProperties() as $prop)
		{
			$this->_preparedStatement->getParameterNames()->add($prop->getProperty());
		}
	}
}

?>