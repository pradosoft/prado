<?php
/**
 * TPreparedCommand class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.SqlMap.Statements
 */

Prado::using('System.Data.Common.TDbMetaData');

/**
 * TPreparedCommand class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap.Statements
 * @since 3.1
 */
class TPreparedCommand
{
	public function create(TSqlMapManager $manager, $connection, $statement, $parameterObject,$skip=null,$max=null)
	{
		$prepared = $statement->getSQLText()->getPreparedStatement($parameterObject);
		$connection->setActive(true);
		$sql = $prepared->getPreparedSql();
		if($max!==null || $skip!==null)
		{
			$builder = TDbMetaData::getInstance($connection)->createCommandBuilder();
			$sql = $builder->applyLimitOffset($sql,$max,$skip);
		}
		$command = $connection->createCommand($sql);
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
			if(!empty($property->getDbType()))
				$command->bindValue($i+1,$value, constant($property->getDbType())); //assumes PDO types, e.g. PDO::PARAM_INT
			else
				$command->bindValue($i+1,$value, TDbCommandBuilder::getPdoType($value));
		}
	}
}

?>