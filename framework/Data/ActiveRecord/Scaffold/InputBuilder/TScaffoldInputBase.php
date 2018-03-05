<?php
/**
 * TScaffoldInputBase class file.
 *
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold\InputBuilder
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\Common\TDbTableColumn;
use Prado\Exceptions\TConfigurationException;

class TScaffoldInputBase
{
	const DEFAULT_ID = 'scaffold_input';
	private $_parent;

	protected function getParent()
	{
		return $this->_parent;
	}

	public static function createInputBuilder($record)
	{
		$record->getDbConnection()->setActive(true); //must be connected before retrieving driver name!
		$driver = $record->getDbConnection()->getDriverName();
		switch (strtolower($driver)) {
			case 'sqlite': //sqlite 3
			case 'sqlite2': //sqlite 2
				require_once(__DIR__ . '/TSqliteScaffoldInput.php');
				return new TSqliteScaffoldInput($conn);
			case 'mysqli':
			case 'mysql':
				require_once(__DIR__ . '/TMysqlScaffoldInput.php');
				return new TMysqlScaffoldInput($conn);
			case 'pgsql':
				require_once(__DIR__ . '/TPgsqlScaffoldInput.php');
				return new TPgsqlScaffoldInput($conn);
			case 'mssql':
				require_once(__DIR__ . '/TMssqlScaffoldInput.php');
				return new TMssqlScaffoldInput($conn);
			case 'ibm':
				require_once(__DIR__ . '/TIbmScaffoldInput.php');
				return new TIbmScaffoldInput($conn);
			default:
				throw new TConfigurationException(
					'scaffold_invalid_database_driver',
					$driver
				);
		}
	}

	public function createScaffoldInput($parent, $item, $column, $record)
	{
		$this->_parent = $parent;
		$item->setCustomData($column->getColumnId());
		$this->createControl($item->_input, $column, $record);
		if ($item->_input->findControl(self::DEFAULT_ID)) {
			$this->createControlLabel($item->_label, $column, $record);
		}
	}

	protected function createControlLabel($label, $column, $record)
	{
		$fieldname = ucwords(str_replace('_', ' ', $column->getColumnId())) . ':';
		$label->setText($fieldname);
		$label->setForControl(self::DEFAULT_ID);
	}

	public function loadScaffoldInput($parent, $item, $column, $record)
	{
		$this->_parent = $parent;
		if ($this->getIsEnabled($column, $record)) {
			$prop = $column->getColumnId();
			$record->setColumnValue($prop, $this->getControlValue($item->_input, $column, $record));
		}
	}

	protected function getIsEnabled($column, $record)
	{
		return !($this->getParent()->getRecordPk() !== null
				&& $column->getIsPrimaryKey() || $column->hasSequence());
	}

	protected function getRecordPropertyValue($column, $record)
	{
		$value = $record->getColumnValue($column->getColumnId());
		if ($column->getDefaultValue() !== TDbTableColumn::UNDEFINED_VALUE && $value === null) {
			return $column->getDefaultValue();
		} else {
			return $value;
		}
	}

	protected function setRecordPropertyValue($item, $record, $input)
	{
		$record->setColumnValue($item->getCustomData(), $input->getText());
	}

	protected function createControl($container, $column, $record)
	{
	}

	protected function getControlValue($container, $column, $record)
	{
	}
}
