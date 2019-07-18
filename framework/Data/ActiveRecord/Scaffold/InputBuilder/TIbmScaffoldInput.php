<?php
/**
 * TIbmScaffoldInput class file.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold\InputBuilder
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Prado;

/**
 * TIbmScaffoldInput class.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @package Prado\Data\ActiveRecord\Scaffold\InputBuilder
 */

class TIbmScaffoldInput extends TScaffoldInputCommon
{
	protected function createControl($container, $column, $record)
	{
		switch (strtolower($column->getDbType())) {
			case 'date':
				return $this->createDateControl($container, $column, $record);
			case 'time':
				return $this->createTimeControl($container, $column, $record);
			case 'timestamp':
				return $this->createDateTimeControl($container, $column, $record);
			case 'smallint': case 'integer': case 'bigint':
				return $this->createIntegerControl($container, $column, $record);
			case 'decimal': case 'numeric': case 'real': case 'float': case 'double':
				return $this->createFloatControl($container, $column, $record);
			case 'char': case 'varchar':
				return $this->createMultiLineControl($container, $column, $record);
			default:
				return $this->createDefaultControl($container, $column, $record);
		}
	}

	protected function getControlValue($container, $column, $record)
	{
		switch (strtolower($column->getDbType())) {
			case 'date':
				return $container->findControl(self::DEFAULT_ID)->getDate();
			case 'time':
				return $this->getTimeValue($container, $column, $record);
			case 'timestamp':
				return $this->getDateTimeValue($container, $column, $record);
			default:
				return $this->getDefaultControlValue($container, $column, $record);
		}
	}
}
