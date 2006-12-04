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
	}

	public function prepare()
	{
		$this->_preparedStatement = new TPreparedStatement();
		$this->_preparedStatement->setPreparedSql($this->_commandText);
		if(!is_null($this->_statement->parameterMap()))
			$this->createParametersForTextCommand();
		return $this->_preparedStatement;
	}

	protected function createParametersForTextCommand()
	{
		foreach($this->_statement->ParameterMap()->getProperties() as $prop)
			$this->_preparedStatement->getParameterNames()->add($prop->getProperty());
	}
}

?>