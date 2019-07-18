<?php
/**
 * TMysqlScaffoldInput class file.
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold\InputBuilder
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Prado;

/**
 * TMysqlScaffoldInput class.
 *
 * @link https://github.com/pradosoft/prado
 * @package Prado\Data\ActiveRecord\Scaffold\InputBuilder
 */

class TMysqlScaffoldInput extends TScaffoldInputCommon
{
	protected function createControl($container, $column, $record)
	{
		$dbtype = trim(str_replace(['unsigned', 'zerofill'], ['', '', ], strtolower($column->getDbType())));
		switch ($dbtype) {
			case 'date':
				return $this->createDateControl($container, $column, $record);
			case 'blob': case 'tinyblob': case 'mediumblob': case 'longblob':
			case 'text': case 'tinytext': case 'mediumtext': case 'longtext':
				return $this->createMultiLineControl($container, $column, $record);
			case 'year':
				return $this->createYearControl($container, $column, $record);
			case 'int': case 'integer': case 'tinyint': case 'smallint': case 'mediumint': case 'bigint':
				return $this->createIntegerControl($container, $column, $record);
			case 'decimal': case 'double': case 'float':
				return $this->createFloatControl($container, $column, $record);
			case 'time':
				return $this->createTimeControl($container, $column, $record);
			case 'datetime': case 'timestamp':
				return $this->createDateTimeControl($container, $column, $record);
			case 'set':
				return $this->createSetControl($container, $column, $record);
			case 'enum':
				return $this->createEnumControl($container, $column, $record);
			default:
				return $this->createDefaultControl($container, $column, $record);
		}
	}

	protected function getControlValue($container, $column, $record)
	{
		$dbtype = trim(str_replace(['unsigned', 'zerofill'], ['', '', ], strtolower($column->getDbType())));
		switch ($dbtype) {
			case 'date':
				return $container->findControl(self::DEFAULT_ID)->getDate();
			case 'year':
				return $container->findControl(self::DEFAULT_ID)->getSelectedValue();
			case 'time':
				return $this->getTimeValue($container, $column, $record);
			case 'datetime': case 'timestamp':
				return $this->getDateTimeValue($container, $column, $record);
			case 'tinyint':
				return $this->getIntBooleanValue($container, $column, $record);
			case 'set':
				return $this->getSetValue($container, $column, $record);
			case 'enum':
				return $this->getEnumValue($container, $column, $record);
			default:
				return $this->getDefaultControlValue($container, $column, $record);
		}
	}

	protected function createIntegerControl($container, $column, $record)
	{
		if ($column->getColumnSize() == 1) {
			return $this->createBooleanControl($container, $column, $record);
		} else {
			parent::createIntegerControl($container, $column, $record);
		}
	}

	protected function getIntBooleanValue($container, $column, $record)
	{
		if ($column->getColumnSize() == 1) {
			return (int) $container->findControl(self::DEFAULT_ID)->getChecked();
		} else {
			return $this->getDefaultControlValue($container, $column, $record);
		}
	}
}
