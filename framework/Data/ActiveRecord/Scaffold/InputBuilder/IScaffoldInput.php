<?php

/**
 * IScaffoldInput interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold\InputBuilder;

/**
 * IScaffoldInput interface.
 *
 * IScaffoldInput defines the public contract for database-specific scaffold
 * input builders.  Implementations map database column types to appropriate
 * Prado web controls (TTextBox, TCheckBox, TDropDownList, TDatePicker, etc.)
 * and read the submitted value back into the active record.
 *
 * The built-in driver-specific classes (`TMysqlScaffoldInput`,
 * `TSqliteScaffoldInput`, etc.) all implement this interface by inheriting
 * from {@see TScaffoldInputBase}.
 *
 * Custom implementations for unsupported drivers may be registered by
 * handling the **`fxActiveRecordScaffoldInputClass`** global event raised on
 * the connection by {@see \Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase}.
 * The sender is the connection and the parameter is the driver name string.
 * Handlers must return the **fully-qualified class name** of a class that
 * implements this interface; the first returned value is used.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IScaffoldInput
{
	/**
	 * Default ID assigned to the primary input control within each scaffold item.
	 *
	 * Implementations must honour this constant so that
	 * {@see TScaffoldInputBase::createScaffoldInput} can locate the control
	 * when generating the associated label.
	 */
	public const DEFAULT_ID = 'scaffold_input';

	/**
	 * Creates the appropriate input control(s) for a column and attaches them
	 * to the scaffold item container.
	 *
	 * Implementations should call `createControl` to build the control and
	 * `createControlLabel` (via the base class) when the primary control with
	 * {@see DEFAULT_ID} is found inside the item.
	 *
	 * @param mixed $parent the parent scaffold configuration.
	 * @param mixed $item the scaffold input item container.
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 */
	public function createScaffoldInput($parent, $item, $column, $record);

	/**
	 * Reads the submitted input control value and stores it back into the
	 * active record column.
	 *
	 * Called during post-back to transfer user input into the record before
	 * save.  Implementations should skip read-only columns (primary keys with
	 * sequences) via `getIsEnabled`.
	 *
	 * @param mixed $parent the parent scaffold configuration.
	 * @param mixed $item the scaffold input item container.
	 * @param \Prado\Data\Common\TDbTableColumn $column the column metadata.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record the active record instance.
	 */
	public function loadScaffoldInput($parent, $item, $column, $record);
}
