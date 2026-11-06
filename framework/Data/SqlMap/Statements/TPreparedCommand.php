<?php

/**
 * TPreparedCommand class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Data\Common\IDbCommandBuilder;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\SqlMap\TSqlMapManager;
use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * TPreparedCommand class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TPreparedCommand
{
	public function create(TSqlMapManager $manager, $connection, $statement, $parameterObject, $skip = null, $max = null)
	{
		$sqlText = $statement->getSQLText();

		$prepared = $sqlText->getPreparedStatement($parameterObject);
		$connection->setActive(true);
		$sql = $prepared->getPreparedSql();

		if ($sqlText instanceof TSimpleDynamicSql) {
			$sql = $sqlText->replaceDynamicParameter($sql, $parameterObject);
		}

		if ($max !== null || $skip !== null) {
			$builder = TDbMetaData::getInstance($connection)->createCommandBuilder();
			if (!($builder instanceof IDbCommandBuilder)) {
				throw new TDbException('dbmetadata_not_meta_data', get_debug_type($builder), IDbCommandBuilder::class);
			}
			$sql = $builder->applyLimitOffset($sql, $max, $skip);
		}
		$command = $connection->createCommand($sql);
		$this->applyParameterMap($manager, $command, $prepared, $statement, $parameterObject);

		return $command;
	}

	protected function applyParameterMap($manager, $command, $prepared, $statement, $parameterObject)
	{
		$properties = $prepared->getParameterNames(false);
		//$parameters = $prepared->getParameterValues();
		$registry = $manager->getTypeHandlers();
		if ($properties) {
			$driverName = $command->getConnection()->getDriverName();
			for ($i = 0, $k = $properties->getCount(); $i < $k; $i++) {
				$property = $statement->parameterMap()->getProperty($i);
				$value = $statement->parameterMap()->getPropertyValue($registry, $property, $parameterObject);
				if (is_bool($value) && $driverName === 'firebird') {
					// pdo_firebird binds a PHP bool only to a BOOLEAN column; integer flag columns take 0 or 1.
					$value = (int) $value;
				}
				$dbType = $property->getDbType();
				if ($dbType == '') { //relies on PHP lax comparison
					$paramType = TDbCommandBuilder::getParamType($value);
					if ($paramType === \PDO::PARAM_NULL && $driverName === 'ibm') {
						// pdo_ibm rejects PDO::PARAM_NULL; it binds a null PDO::PARAM_STR as SQL NULL.
						$paramType = \PDO::PARAM_STR;
					}
					$command->bindValue($i + 1, $value, $paramType);
				} elseif (strpos($dbType, 'PDO::') === 0) {
					$command->bindValue($i + 1, $value, constant($property->getDbType()));
				} //assumes PDO types, e.g. PDO::PARAM_INT
				else {
					$command->bindValue($i + 1, $value);
				}
			}
		}
	}
}
