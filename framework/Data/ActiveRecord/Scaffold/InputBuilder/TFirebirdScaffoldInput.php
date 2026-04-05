<?php

/**
 * TFirebirdScaffoldInput class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Prado;

/**
 * TFirebirdScaffoldInput class.
 *
 * Maps Firebird column types to appropriate Prado scaffold input controls.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TFirebirdScaffoldInput extends TScaffoldInputCommon
{
	protected function createControl($container, $column, $record)
	{
		switch (strtoupper($column->getDbType())) {
			case 'DATE':
				return $this->createDateControl($container, $column, $record);
			case 'TIME':
			case 'TIME WITH TIME ZONE':
				return $this->createTimeControl($container, $column, $record);
			case 'TIMESTAMP':
			case 'TIMESTAMP WITH TIME ZONE':
				return $this->createDateTimeControl($container, $column, $record);
			case 'SMALLINT':
			case 'INTEGER':
			case 'BIGINT':
				return $this->createIntegerControl($container, $column, $record);
			case 'FLOAT':
			case 'DOUBLE PRECISION':
			case 'DECIMAL':
			case 'NUMERIC':
			case 'DECFLOAT(16)':
			case 'DECFLOAT(34)':
				return $this->createFloatControl($container, $column, $record);
			case 'BOOLEAN':
				return $this->createBooleanControl($container, $column, $record);
			case 'CHAR':
			case 'VARCHAR':
				return $this->createTextControl($container, $column, $record);
			case 'TEXT':
			case 'BLOB':
				return $this->createMultiLineControl($container, $column, $record);
			default:
				return $this->createDefaultControl($container, $column, $record);
		}
	}

	protected function getControlValue($container, $column, $record)
	{
		switch (strtoupper($column->getDbType())) {
			case 'DATE':
				return $container->findControl(self::DEFAULT_ID)->getDate();
			case 'TIME':
			case 'TIME WITH TIME ZONE':
				return $this->getTimeValue($container, $column, $record);
			case 'TIMESTAMP':
			case 'TIMESTAMP WITH TIME ZONE':
				return $this->getDateTimeValue($container, $column, $record);
			default:
				return $this->getDefaultControlValue($container, $column, $record);
		}
	}
}
