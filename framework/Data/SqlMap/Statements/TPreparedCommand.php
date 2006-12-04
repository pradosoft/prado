<?php

class TPreparedCommand
{

	public function create(TSqlMapManager $manager, $connection, $statement, $parameterObject)
	{
		$prepared = $statement->getSQLText()->getPreparedStatement($parameterObject);
		$connection->setActive(true);
		$command = $connection->createCommand($prepared->getPreparedSql());
		$this->applyParameterMap($manager, $command, $prepared, $statement, $parameterObject);
		return $command;
	}

	protected function applyParameterMap($manager,$command,$prepared, $statement, $parameterObject)
	{
		$properties = $prepared->getParameterNames();
		$parameters = $prepared->getParameterValues();
		$registry=$manager->getTypeHandlers();
		for($i = 0, $k=$properties->getCount(); $i<$k; $i++)
		{
			$property = $statement->parameterMap()->getProperty($i);
			$value = $statement->parameterMap()->getPropertyValue($registry,$property, $parameterObject);
			$command->bindValue($i+1,$value);
		}
	}
}

?>