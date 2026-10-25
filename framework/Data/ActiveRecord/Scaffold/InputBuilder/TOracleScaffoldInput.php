<?php

/**
 * TOracleScaffoldInput class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

/**
 * TOracleScaffoldInput class.
 *
 * Maps Oracle column types to Prado scaffold input controls. `DbType` is the
 * `ALL_TAB_COLUMNS` data type without its precision, so `NUMBER(10,0)` is
 * `NUMBER` and `TIMESTAMP(6) WITH TIME ZONE` is `TIMESTAMP WITH TIME ZONE`.
 *
 * | Oracle type | control |
 * |---|---|
 * | `BOOLEAN` | check box |
 * | `DATE`, `TIMESTAMP`, `TIMESTAMP WITH TIME ZONE`, `TIMESTAMP WITH LOCAL TIME ZONE` | date picker with time lists; Oracle `DATE` holds a time |
 * | `INTEGER`, `INT`, `SMALLINT`; `NUMBER`, `DECIMAL`, `NUMERIC` with scale 0 | integer text box |
 * | `NUMBER`, `DECIMAL`, `NUMERIC` with another or no scale; `FLOAT`, `BINARY_FLOAT`, `BINARY_DOUBLE`, `REAL`, `DOUBLE PRECISION` | float text box |
 * | `CLOB`, `NCLOB`, `LONG`, `BLOB` | multiline text box |
 * | any other, such as `VARCHAR2`, `NVARCHAR2`, `CHAR`, `NCHAR` | text box limited to the column size |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TOracleScaffoldInput extends TScaffoldInputCommon
{
	protected function createControl($container, $column, $record)
	{
		switch (strtoupper($column->getDbType())) {
			case 'BOOLEAN':
				return $this->createBooleanControl($container, $column, $record);
			case 'DATE':
			case 'TIMESTAMP':
			case 'TIMESTAMP WITH TIME ZONE':
			case 'TIMESTAMP WITH LOCAL TIME ZONE':
				return $this->createDateTimeControl($container, $column, $record);
			case 'INTEGER':
			case 'INT':
			case 'SMALLINT':
				return $this->createIntegerControl($container, $column, $record);
			case 'NUMBER':
			case 'DECIMAL':
			case 'NUMERIC':
				if ($column->getNumericScale() === 0) {
					return $this->createIntegerControl($container, $column, $record);
				}
				return $this->createFloatControl($container, $column, $record);
			case 'FLOAT':
			case 'BINARY_FLOAT':
			case 'BINARY_DOUBLE':
			case 'REAL':
			case 'DOUBLE PRECISION':
				return $this->createFloatControl($container, $column, $record);
			case 'CLOB':
			case 'NCLOB':
			case 'LONG':
			case 'BLOB':
				return $this->createMultiLineControl($container, $column, $record);
			default:
				return $this->createDefaultControl($container, $column, $record);
		}
	}

	protected function getControlValue($container, $column, $record)
	{
		switch (strtoupper($column->getDbType())) {
			case 'BOOLEAN':
				return $container->findControl(self::DEFAULT_ID)->getChecked();
			case 'DATE':
			case 'TIMESTAMP':
			case 'TIMESTAMP WITH TIME ZONE':
			case 'TIMESTAMP WITH LOCAL TIME ZONE':
				return $this->getDateTimeValue($container, $column, $record);
			default:
				return $this->getDefaultControlValue($container, $column, $record);
		}
	}
}
