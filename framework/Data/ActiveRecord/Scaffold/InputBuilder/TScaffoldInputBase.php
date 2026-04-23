<?php

/**
 * TScaffoldInputBase class file.
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\Common\TDbTableColumn;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;

/**
 * TScaffoldInputBase class.
 *
 * TScaffoldInputBase is the base class for creating scaffold input builders
 * that generate appropriate input controls for active record columns based on
 * the database driver.
 *
 * This class provides the foundation for database-specific input builder implementations
 * (e.g., TMysqlScaffoldInput, TSqliteScaffoldInput, etc.) that map database
 * column types to appropriate Prado web controls like TTextBox, TCheckBox, TDropDownList,
 * TDatePicker, etc.
 *
 * The input builders are created via the static {@see createInputBuilder} method which
 * determines the appropriate builder based on the database driver. When no built-in driver
 * matches, the {@see fxActiveRecordCreateScaffoldInput()} global event is raised to allow
 * for extensibility through custom implementations.
 *
 * Example usage:
 * ```php
 * $builder = TScaffoldInputBase::createInputBuilder($record);
 * $builder->createScaffoldInput($parent, $item, $column, $record);
 * ```
 */
class TScaffoldInputBase
{
	public const DEFAULT_ID = 'scaffold_input';
	private $_parent;

	/**
	 * Gets the parent scaffold configuration.
	 *
	 * @return mixed the parent scaffold configuration.
	 */
	protected function getParent()
	{
		return $this->_parent;
	}

	/**
	 * Creates a database-specific scaffold input builder based on the active record's database driver.
	 *
	 * This method determines the appropriate input builder for the given database driver.
	 * If no built-in driver is found, the {@see fxActiveRecordCreateScaffoldInput()} global event
	 * is raised to allow custom implementations to provide a builder.
	 *
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 * @throws TConfigurationException if no builder can be created for the driver.
	 * @return self the appropriate input builder for the database driver.
	 */
	public static function createInputBuilder($record)
	{
		$connection = $record->getDbConnection();
		$connection->setActive(true); //must be connected before retrieving driver name!
		$driver = $connection->getDriverName();
		switch (strtolower($driver)) {
			case TDbConnection::DRIVER_SQLITE: //sqlite 3
			case TDbConnection::DRIVER_SQLITE2: //sqlite 2
				require_once(__DIR__ . '/TSqliteScaffoldInput.php');
				return new TSqliteScaffoldInput();
			case TDbConnection::DRIVER_MYSQL:
				require_once(__DIR__ . '/TMysqlScaffoldInput.php');
				return new TMysqlScaffoldInput();
			case TDbConnection::DRIVER_PGSQL:
				require_once(__DIR__ . '/TPgsqlScaffoldInput.php');
				return new TPgsqlScaffoldInput();
			case TDbConnection::DRIVER_SQLSRV:
				require_once(__DIR__ . '/TMssqlScaffoldInput.php');
				return new TMssqlScaffoldInput();
			case TDbConnection::DRIVER_IBM:
				require_once(__DIR__ . '/TIbmScaffoldInput.php');
				return new TIbmScaffoldInput();
			case TDbConnection::DRIVER_FIREBIRD:
				require_once(__DIR__ . '/TFirebirdScaffoldInput.php');
				return new TFirebirdScaffoldInput();
			default:
				$instances = $record->getDbConnection()->raiseEvent('fxActiveRecordCreateScaffoldInput', self::class, $record->getDbConnection());
				if (empty($instances)) {
					// @todo v4.4 TActiveRecordConfigurationException, move message
					throw new TConfigurationException('ar_invalid_database_driver', $driver);
				}
				$scaffoldInput = $instances[0];
				if ($scaffoldInput instanceof static) {
					// @todo v4.4 TActiveRecordConfigurationException, move  message
					throw new TConfigurationException('ar_not_input_base', $scaffoldInput::class, static::class);
				}
				return $scaffoldInput;
		}
	}

	/**
	 * Creates the scaffold input control and label for a given column.
	 *
	 * This method sets up the input control for the specified column and creates
	 * a corresponding label if an input control with the default ID is found.
	 *
	 * @param mixed $parent the parent scaffold configuration.
	 * @param mixed $item the scaffold input item container.
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 */
	public function createScaffoldInput($parent, $item, $column, $record)
	{
		$this->_parent = $parent;
		$item->setCustomData($column->getColumnId());
		$this->createControl($item->_input, $column, $record);
		if ($item->_input->findControl(self::DEFAULT_ID)) {
			$this->createControlLabel($item->_label, $column, $record);
		}
	}

	/**
	 * Creates a label for the input control.
	 *
	 * @param mixed $label the label control.
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 */
	protected function createControlLabel($label, $column, $record)
	{
		$fieldname = ucwords(str_replace('_', ' ', $column->getColumnId())) . ':';
		$label->setText($fieldname);
		$label->setForControl(self::DEFAULT_ID);
	}

	/**
	 * Loads the input control value back into the active record.
	 *
	 * @param mixed $parent the parent scaffold configuration.
	 * @param mixed $item the scaffold input item container.
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 */
	public function loadScaffoldInput($parent, $item, $column, $record)
	{
		$this->_parent = $parent;
		if ($this->getIsEnabled($column, $record)) {
			$prop = $column->getColumnId();
			$record->setColumnValue($prop, $this->getControlValue($item->_input, $column, $record));
		}
	}

	/**
	 * Determines if the input control should be enabled for editing.
	 *
	 * Primary key columns with sequences are disabled (auto-generated).
	 *
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 * @return bool whether the control should be enabled.
	 */
	protected function getIsEnabled($column, $record)
	{
		return !($this->getParent()->getRecordPk() !== null
				&& $column->getIsPrimaryKey() || $column->hasSequence());
	}

	/**
	 * Gets the record property value with default handling.
	 *
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 * @return mixed the property value or the default value.
	 */
	protected function getRecordPropertyValue($column, $record)
	{
		$value = $record->getColumnValue($column->getColumnId());
		if ($column->getDefaultValue() !== TDbTableColumn::UNDEFINED_VALUE && $value === null) {
			return $column->getDefaultValue();
		} else {
			return $value;
		}
	}

	/**
	 * Sets the record property value from the input control.
	 *
	 * @param mixed $item the scaffold input item.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 * @param mixed $input the input control.
	 */
	protected function setRecordPropertyValue($item, $record, $input)
	{
		$record->setColumnValue($item->getCustomData(), $input->getText());
	}

	/**
	 * Creates the input control for the column.
	 *
	 * Subclasses should override this method to create database-specific controls.
	 *
	 * @param mixed $container the container control.
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 * @return mixed the created control.
	 */
	protected function createControl($container, $column, $record)
	{
	}

	/**
	 * Gets the value from the input control.
	 *
	 * Subclasses should override this method to return the appropriate value.
	 *
	 * @param mixed $container the container control.
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 * @return mixed the control value.
	 */
	protected function getControlValue($container, $column, $record)
	{
	}
}
