<?php

/**
 * TSqlSrvScaffoldInput class file.
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

/**
 * TSqlSrvScaffoldInput class.
 *
 * Maps Microsoft SQL Server column types to Prado scaffold input controls for the
 * `sqlsrv` and `dblib` drivers and the `mssql` extension.
 *
 * | SQL Server type | control |
 * |---|---|
 * | `bit` | check box |
 * | `text` | multiline text box |
 * | `smallint`, `int`, `bigint`, `tinyint` | integer text box |
 * | `decimal`, `float`, `money`, `numeric`, `real`, `smallmoney` | float text box |
 * | `datetime`, `smalldatetime` | date picker with time lists |
 * | any other | text box; disabled for an excluded column such as `timestamp` |
 *
 * @link https://github.com/pradosoft/prado
 * @since 4.3.3
 */
class TSqlSrvScaffoldInput extends TScaffoldInputCommon
{
	protected function createControl($container, $column, $record)
	{
		switch (strtolower($column->getDbType())) {
			case 'bit':
				return $this->createBooleanControl($container, $column, $record);
			case 'text':
				return $this->createMultiLineControl($container, $column, $record);
			case 'smallint': case 'int': case 'bigint': case 'tinyint':
				return $this->createIntegerControl($container, $column, $record);
			case 'decimal': case 'float': case 'money': case 'numeric': case 'real': case 'smallmoney':
				return $this->createFloatControl($container, $column, $record);
			case 'datetime': case 'smalldatetime':
				return $this->createDateTimeControl($container, $column, $record);
			default:
				$control = $this->createDefaultControl($container, $column, $record);
				if ($column->getIsExcluded()) {
					$control->setEnabled(false);
				}
				return $control;
		}
	}

	protected function getControlValue($container, $column, $record)
	{
		switch (strtolower($column->getDbType())) {
			case 'bit':
			case 'boolean':
				return $container->findControl(self::DEFAULT_ID)->getChecked();
			case 'datetime': case 'smalldatetime':
				return $this->getDateTimeValue($container, $column, $record);
			default:
				$value = $this->getDefaultControlValue($container, $column, $record);
				if (trim((string) $value) === '' && $column->getAllowNull()) {
					return null;
				} else {
					return $value;
				}
		}
	}
}
