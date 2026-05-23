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
use Prado\Data\TDbDriverCapabilities;
use Prado\Exceptions\TConfigurationException;

/**
 * TScaffoldInputBase class.
 *
 * TScaffoldInputBase is the base class for creating scaffold input builders
 * that generate appropriate input controls for active record columns based on
 * the database driver.  It implements {@see IScaffoldInput}, the common
 * interface that all scaffold input builders must satisfy.
 *
 * This class provides the foundation for database-specific input builder
 * implementations (e.g., TMysqlScaffoldInput, TSqliteScaffoldInput) that map
 * database column types to appropriate Prado web controls like TTextBox,
 * TCheckBox, TDropDownList, TDatePicker, etc.
 *
 * The input builders are created via the static {@see createInputBuilder}
 * method which delegates all driver resolution — including the
 * `fxActiveRecordScaffoldInputClass` global event for unknown drivers — to
 * {@see TDbDriverCapabilities::createScaffoldInput}.
 *
 * Example usage:
 * ```php
 * $builder = TScaffoldInputBase::createInputBuilder($record);
 * $builder->createScaffoldInput($parent, $item, $column, $record);
 * ```
 */
class TScaffoldInputBase implements IScaffoldInput
{
	public const DEFAULT_ID = IScaffoldInput::DEFAULT_ID;
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
	 * Creates a database-specific scaffold input builder based on the active
	 * record's database driver.
	 *
	 * For built-in drivers the appropriate builder is loaded and returned
	 * directly.  For unknown drivers,
	 * {@see TDbDriverCapabilities::createScaffoldInput} raises the
	 * **`fxActiveRecordScaffoldInputClass`** global event on the connection
	 * with the driver name as the parameter.  Event handlers must return the
	 * fully-qualified **class name** of a class that implements
	 * {@see IScaffoldInput}; the class is then instantiated here and validated.
	 *
	 * All driver resolution and event raising is encapsulated in
	 * {@see TDbDriverCapabilities::createScaffoldInput}; this method does not
	 * call `raiseEvent` directly.
	 *
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 * @throws TConfigurationException if no builder can be created for the
	 *   driver, or if the returned instance does not implement {@see IScaffoldInput}.
	 * @return IScaffoldInput the appropriate input builder for the database driver.
	 */
	public static function createInputBuilder($record)
	{
		$connection = $record->getDbConnection();
		$connection->setActive(true); //must be connected before retrieving driver name!
		$scaffoldInput = TDbDriverCapabilities::createScaffoldInput($connection);
		if (!($scaffoldInput instanceof IScaffoldInput)) {
			// @todo v4.4 TActiveRecordConfigurationException, move message
			throw new TConfigurationException('ar_not_input_base', $scaffoldInput::class, IScaffoldInput::class);
		}
		return $scaffoldInput;
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
