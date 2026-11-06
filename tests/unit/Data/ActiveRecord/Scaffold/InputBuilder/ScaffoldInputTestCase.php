<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\IScaffoldInput;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TPlaceHolder;
use Prado\Web\UI\WebControls\TRepeaterItem;

/**
 * Base of the scaffold input builder tests.
 *
 * An item mirrors one row of the TScaffoldEditView input repeater: a TRepeaterItem
 * holding the `_label` TLabel and the `_input` TPlaceHolder. {@see build()} runs the
 * builder and then data binds the item, as TRepeater does after OnItemCreated.
 * PHP notices and deprecations not suppressed with `@` raise an ErrorException so a test fails on them.
 */
abstract class ScaffoldInputTestCase extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
			if (!(error_reporting() & $errno)) {
				return false;
			}
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
	}

	protected function tearDown(): void
	{
		restore_error_handler();
	}

	protected function newParent(?array $recordPk = null, string $group = 'group_test'): ScaffoldInputParentStub
	{
		return new ScaffoldInputParentStub($recordPk, $group);
	}

	protected function newItem(): TRepeaterItem
	{
		$item = new TRepeaterItem();
		$label = new TLabel();
		$label->setID('_label');
		$input = new TPlaceHolder();
		$input->setID('_input');
		$item->getControls()->add($label);
		$item->getControls()->add($input);
		$item->registerObject('_label', $label);
		$item->registerObject('_input', $input);
		return $item;
	}

	/**
	 * Creates the input for a column and data binds the item, as TScaffoldEditView does.
	 * @param IScaffoldInput $builder
	 * @param mixed $column
	 * @param mixed $record
	 * @param mixed $parent
	 */
	protected function build($builder, $column, $record, $parent = null): TRepeaterItem
	{
		$item = $this->newItem();
		$builder->createScaffoldInput($parent ?? $this->newParent(), $item, $column, $record);
		$item->dataBind();
		return $item;
	}

	/**
	 * Reads the item inputs back into the record.
	 * @param IScaffoldInput $builder
	 * @param mixed $item
	 * @param mixed $column
	 * @param mixed $record
	 * @param mixed $parent
	 */
	protected function load($builder, $item, $column, $record, $parent = null)
	{
		$builder->loadScaffoldInput($parent ?? $this->newParent(), $item, $column, $record);
		return $record->getColumnValue($column->getColumnId());
	}

	protected function input(TRepeaterItem $item, string $id = IScaffoldInput::DEFAULT_ID)
	{
		return $item->_input->findControl($id);
	}

	/**
	 * @param TRepeaterItem $item
	 * @return array the controls of the input placeholder that are not literal strings.
	 */
	protected function inputControls(TRepeaterItem $item): array
	{
		$controls = [];
		foreach ($item->_input->getControls() as $control) {
			if (is_object($control)) {
				$controls[] = $control;
			}
		}
		return $controls;
	}

	protected function inputsOfType(TRepeaterItem $item, string $class): array
	{
		return array_values(array_filter($this->inputControls($item), fn ($c) => $c instanceof $class));
	}

	/**
	 * Names the kind of input an item holds:
	 * checkbox, date, datetime, time, year, set, enum, multiline, integer, float, or text.
	 * @param TRepeaterItem $item
	 */
	protected function inputKind(TRepeaterItem $item): string
	{
		$input = $this->input($item);
		if ($input instanceof \Prado\Web\UI\WebControls\TCheckBox) {
			return 'checkbox';
		}
		if ($input instanceof \Prado\Web\UI\WebControls\TDatePicker) {
			return $this->input($item, 'scaffold_time_hour') !== null ? 'datetime' : 'date';
		}
		if ($input instanceof \Prado\Web\UI\WebControls\TRadioButtonList) {
			return 'enum';
		}
		if ($input instanceof \Prado\Web\UI\WebControls\TCheckBoxList) {
			return 'set';
		}
		if ($input instanceof \Prado\Web\UI\WebControls\TDropDownList) {
			return $this->input($item, 'scaffold_time_min') !== null ? 'time' : 'year';
		}
		if ($input instanceof \Prado\Web\UI\WebControls\TTextBox) {
			if ($input->getTextMode() === \Prado\Web\UI\WebControls\TTextBoxMode::MultiLine) {
				return 'multiline';
			}
			foreach ($this->inputsOfType($item, \Prado\Web\UI\WebControls\TDataTypeValidator::class) as $validator) {
				return strtolower($validator->getDataType());
			}
			return 'text';
		}
		return $input === null ? 'none' : $input::class;
	}

	protected function newRecord(array $values = []): ScaffoldInputRecord
	{
		$record = new ScaffoldInputRecord();
		foreach ($values as $name => $value) {
			$record->setColumnValue($name, $value);
		}
		return $record;
	}

	protected function columnInfo(string $type, array $info = []): array
	{
		return $info + [
			'ColumnName' => '"value"',
			'ColumnId' => 'value',
			'DbType' => $type,
			'AllowNull' => true,
		];
	}
}
