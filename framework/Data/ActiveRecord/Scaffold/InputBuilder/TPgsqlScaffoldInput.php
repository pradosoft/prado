<?php
/**
 * TPgsqlScaffoldInput class file.
 *
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold\InputBuilder
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Prado;

class TPgsqlScaffoldInput extends TScaffoldInputCommon
{
	protected function createControl($container, $column, $record)
	{
		switch (strtolower($column->getDbType())) {
			case 'boolean':
				return $this->createBooleanControl($container, $column, $record);
			case 'date':
				return $this->createDateControl($container, $column, $record);
			case 'text':
				return $this->createMultiLineControl($container, $column, $record);
			case 'smallint': case 'integer': case 'bigint':
				return $this->createIntegerControl($container, $column, $record);
			case 'decimal': case 'numeric': case 'real': case 'double precision':
				return $this->createFloatControl($container, $column, $record);
			case 'time without time zone':
				return $this->createTimeControl($container, $column, $record);
			case 'timestamp without time zone':
				return $this->createDateTimeControl($container, $column, $record);
			default:
				return $this->createDefaultControl($container, $column, $record);
		}
	}

	protected function getControlValue($container, $column, $record)
	{
		switch (strtolower($column->getDbType())) {
			case 'boolean':
				return $container->findControl(self::DEFAULT_ID)->getChecked();
			case 'date':
				return $container->findControl(self::DEFAULT_ID)->getDate();
			case 'time without time zone':
				return $this->getTimeValue($container, $column, $record);
			case 'timestamp without time zone':
				return $this->getDateTimeValue($container, $column, $record);
			default:
				return $this->getDefaultControlValue($container, $column, $record);
		}
	}
}
