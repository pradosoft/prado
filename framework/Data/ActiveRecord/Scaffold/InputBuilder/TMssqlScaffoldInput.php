<?php
/**
 * TMssqlScaffoldInput class file.
 *
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold\InputBuilder
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Prado;

class TMssqlScaffoldInput extends TScaffoldInputCommon
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
			case 'boolean':
				return $container->findControl(self::DEFAULT_ID)->getChecked();
			case 'datetime': case 'smalldatetime':
				return $this->getDateTimeValue($container, $column, $record);
			default:
				$value = $this->getDefaultControlValue($container, $column, $record);
				if (trim($value) === '' && $column->getAllowNull()) {
					return null;
				} else {
					return $value;
				}
		}
	}
}
