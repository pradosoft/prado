<?php

class TPreparedCommand
{

	public function create($connection, $statement, $parameterObject)
	{
		$prepared = $statement->getSQL()->getPreparedStatement($parameterObject);
		$parameters = $this->applyParameterMap($connection, 
							$prepared, $statement, $parameterObject);
		return array('sql'=>$prepared->getPreparedSql(), 
					 'parameters'=>$parameters);
	}

	protected function applyParameterMap($connection, 
						$prepared, $statement, $parameterObject)
	{
		$properties = $prepared->getParameterNames();
		$parameters = $prepared->getParameterValues();
		$values = array();
		for($i = 0, $k=$properties->getCount(); $i<$k; $i++)
		{
			$property = $statement->parameterMap()->getProperty($i);
			$values[] = $statement->parameterMap()->getParameter(
								$property, $parameterObject, $statement);
		}
		return count($values) > 0 ? $values : false;
	}

/*	protected function applyParameterClass($connection, $statement, $parameter)
	{
		$type=$statement->getParameterClass();
		if(strlen($type) < 1) return;
		$prepared = $statement->getSql()->getPreparedStatement();
		$names = $prepared->getParameterNames();
		$values = $prepared->getParameterValues();
		switch (strtolower($type))
		{
			case 'integer':
			case 'int':
				$values[$names[0]] = $connection->quote(intval($parameter));
				break;
			case 'array':
				foreach($names as $name)
				{
					$key = substr(substr($name,0,-1),1);
					if(isset($parameter[$key]))
						$values->add($name,$connection->quote($parameter[$key]));
					else
						throw new TDataMapperException('unable_to_find_parameter', $key);
				}
				break;
			default:
				var_dump("todo for other parameter classes");
		}
	}
*/
}

?>