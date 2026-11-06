<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold\InputBuilder;

use Prado\Data\Common\Mysql\TMysqlTableColumn;
use Prado\Data\Common\TDbTableColumn;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TDataTypeValidator;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TDatePickerInputMode;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TRadioButtonList;
use Prado\Web\UI\WebControls\TRangeValidator;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TTextBoxMode;
use Prado\Web\UI\WebControls\TValidationDataType;
use Prado\Web\UI\WebControls\TValidatorDisplayStyle;

/**
 * Covers TScaffoldInputBase and the control factories of TScaffoldInputCommon that every
 * driver input builder shares.
 */
class TScaffoldInputCommonTest extends ScaffoldInputTestCase
{
	private function column(string $type = 'varchar', array $info = []): TMysqlTableColumn
	{
		return new TMysqlTableColumn($this->columnInfo($type, $info));
	}

	// ---------------------------------------------------------------------
	// TScaffoldInputBase: label, custom data, enabled state, load
	// ---------------------------------------------------------------------

	public function testCreateScaffoldInput_setsCustomDataAndLabel()
	{
		$column = $this->column('varchar', ['ColumnId' => 'first_name']);
		$item = $this->build(new CommonScaffoldInputHarness('Default'), $column, $this->newRecord());

		$this->assertSame('first_name', $item->getCustomData());
		$this->assertSame('First Name:', $item->_label->getText());
		$this->assertSame('scaffold_input', $item->_label->getForControl());
	}

	public function testCreateScaffoldInput_noLabelWithoutDefaultInput()
	{
		$column = $this->column();
		$item = $this->build(new CommonScaffoldInputHarness(), $column, $this->newRecord());

		$this->assertSame('value', $item->getCustomData());
		$this->assertSame('', $item->_label->getText());
		$this->assertSame('', $item->_label->getForControl());
	}

	public static function enabledProvider(): array
	{
		return [
			'new record, key column' => [null, ['IsPrimaryKey' => true], true],
			'new record, plain column' => [null, [], true],
			'existing record, key column' => [[5], ['IsPrimaryKey' => true], false],
			'existing record, plain column' => [[5], [], true],
			'new record, sequence column' => [null, ['AutoIncrement' => true], false],
			'existing record, sequence key' => [[5], ['IsPrimaryKey' => true, 'AutoIncrement' => true], false],
		];
	}

	/**
	 * @dataProvider enabledProvider
	 * @param ?array $recordPk
	 * @param array $info
	 * @param bool $enabled
	 */
	public function testInputEnabledState(?array $recordPk, array $info, bool $enabled)
	{
		$column = $this->column('varchar', $info);
		$parent = $this->newParent($recordPk);
		$builder = new CommonScaffoldInputHarness('Default');
		$record = $this->newRecord(['value' => 'old']);
		$item = $this->build($builder, $column, $record, $parent);

		$this->assertSame($enabled, $this->input($item)->getEnabled());

		$this->input($item)->setText('new');
		$this->assertSame($enabled ? 'new' : 'old', $this->load($builder, $item, $column, $record, $parent));
	}

	public function testGetRecordPropertyValue_usesDefaultOnlyForNull()
	{
		$builder = new CommonScaffoldInputHarness();
		$withDefault = $this->column('varchar', ['DefaultValue' => 'dflt']);
		$noDefault = $this->column();

		$this->assertSame('dflt', $builder->callGetRecordPropertyValue($withDefault, $this->newRecord()));
		$this->assertSame('', $builder->callGetRecordPropertyValue($withDefault, $this->newRecord(['value' => ''])));
		$this->assertSame('x', $builder->callGetRecordPropertyValue($withDefault, $this->newRecord(['value' => 'x'])));
		$this->assertNull($builder->callGetRecordPropertyValue($noDefault, $this->newRecord()));
		$this->assertSame(TDbTableColumn::UNDEFINED_VALUE, $noDefault->getDefaultValue());
	}

	public function testSetRecordPropertyValue_writesInputTextToCustomDataColumn()
	{
		$builder = new CommonScaffoldInputHarness();
		$item = $this->newItem();
		$item->setCustomData('first_name');
		$input = new TTextBox();
		$input->setText('Ann');
		$record = $this->newRecord();

		$builder->callSetRecordPropertyValue($item, $record, $input);

		$this->assertSame('Ann', $record->first_name);
	}

	// ---------------------------------------------------------------------
	// Required validator
	// ---------------------------------------------------------------------

	public function testRequiredValidator_forNotNullColumn()
	{
		$column = $this->column('varchar', ['AllowNull' => false]);
		$item = $this->build(new CommonScaffoldInputHarness('Default'), $column, $this->newRecord(), $this->newParent(null, 'group_edit'));

		$validators = $this->inputsOfType($item, TRequiredFieldValidator::class);
		$this->assertCount(1, $validators);
		$val = $validators[0];
		$this->assertSame('*', $val->getErrorMessage());
		$this->assertSame('required-input', $val->getControlCssClass());
		$this->assertSame('required', $val->getCssClass());
		$this->assertSame('scaffold_input', $val->getControlToValidate());
		$this->assertSame('group_edit', $val->getValidationGroup());
		$this->assertSame(TValidatorDisplayStyle::Dynamic, $val->getDisplay());
	}

	public function testRequiredValidator_notForNullableOrSequenceColumn()
	{
		$nullable = $this->column('varchar', ['AllowNull' => true]);
		$sequence = $this->column('varchar', ['AllowNull' => false, 'AutoIncrement' => true]);

		$item = $this->build(new CommonScaffoldInputHarness('Default'), $nullable, $this->newRecord());
		$this->assertCount(0, $this->inputsOfType($item, TRequiredFieldValidator::class));

		$item = $this->build(new CommonScaffoldInputHarness('Default'), $sequence, $this->newRecord());
		$this->assertCount(0, $this->inputsOfType($item, TRequiredFieldValidator::class));
	}

	// ---------------------------------------------------------------------
	// Boolean, default, multi-line
	// ---------------------------------------------------------------------

	public static function booleanProvider(): array
	{
		return [
			'one' => ['1', [], true],
			'zero' => ['0', [], false],
			'empty' => ['', [], false],
			'null' => [null, [], false],
			'null with default' => [null, ['DefaultValue' => '1'], true],
		];
	}

	/**
	 * @dataProvider booleanProvider
	 * @param ?string $value
	 * @param array $info
	 * @param bool $checked
	 */
	public function testBooleanControl(?string $value, array $info, bool $checked)
	{
		$column = $this->column('boolean', $info);
		$builder = new CommonScaffoldInputHarness('Boolean');
		$record = $this->newRecord(['value' => $value]);
		$item = $this->build($builder, $column, $record);

		$box = $this->input($item);
		$this->assertInstanceOf(TCheckBox::class, $box);
		$this->assertSame($builder->created, $box);
		$this->assertSame($checked, $box->getChecked());
		$this->assertSame('boolean-checkbox', $box->getCssClass());

		$box->setChecked(!$checked);
		$this->assertSame(!$checked, $this->load($builder, $item, $column, $record));
	}

	public function testDefaultControl()
	{
		$column = $this->column('varchar', ['ColumnSize' => 20]);
		$builder = new CommonScaffoldInputHarness('Default');
		$record = $this->newRecord(['value' => 'abc']);
		$item = $this->build($builder, $column, $record);

		$box = $this->input($item);
		$this->assertInstanceOf(TTextBox::class, $box);
		$this->assertSame($builder->created, $box);
		$this->assertSame('abc', $box->getText());
		$this->assertSame(20, $box->getMaxLength());
		$this->assertSame(TTextBoxMode::SingleLine, $box->getTextMode());
		$this->assertSame('default-textbox scaffold_input', $box->getCssClass());

		$box->setText('xyz');
		$this->assertSame('xyz', $this->load($builder, $item, $column, $record));
	}

	public function testDefaultControl_noMaxLengthWithoutColumnSize()
	{
		$item = $this->build(new CommonScaffoldInputHarness('Default'), $this->column(), $this->newRecord());

		$this->assertSame(0, $this->input($item)->getMaxLength());
		$this->assertSame('', $this->input($item)->getText());
	}

	public function testDefaultControlValue_isEmptyWithoutInput()
	{
		$column = $this->column();
		$builder = new CommonScaffoldInputHarness();
		$record = $this->newRecord(['value' => 'old']);
		$item = $this->build($builder, $column, $record);

		$this->assertSame('', $this->load($builder, $item, $column, $record));
	}

	public static function blankTextProvider(): array
	{
		return [
			'nullable int, empty' => ['int', true, '', null],
			'nullable int, blank' => ['int', true, '  ', null],
			'nullable int, number' => ['int', true, '12', '12'],
			'nullable decimal, empty' => ['decimal', true, '', null],
			'nullable tinyint(1), empty' => ['tinyint', true, '', null, ['ColumnSize' => 1]],
			'not null int, empty' => ['int', false, '', ''],
			'nullable varchar, empty' => ['varchar', true, '', ''],
			'nullable varchar, blank' => ['varchar', true, '  ', '  '],
		];
	}

	/**
	 * @dataProvider blankTextProvider
	 * @param string $type
	 * @param bool $allowNull
	 * @param string $text
	 * @param ?string $expected
	 * @param array $info
	 */
	public function testDefaultControlValue_blankTextIsNullForNullableNonStringColumn(string $type, bool $allowNull, string $text, ?string $expected, array $info = [])
	{
		$column = $this->column($type, ['AllowNull' => $allowNull] + $info);
		$builder = new CommonScaffoldInputHarness('Default');
		$record = $this->newRecord(['value' => '7']);
		$item = $this->build($builder, $column, $record);

		$this->input($item)->setText($text);
		$this->assertSame($expected, $this->load($builder, $item, $column, $record));
	}

	public function testMultiLineControl()
	{
		$column = $this->column('text', ['AllowNull' => false]);
		$builder = new CommonScaffoldInputHarness('MultiLine');
		$record = $this->newRecord(['value' => "line1\nline2"]);
		$item = $this->build($builder, $column, $record);

		$box = $this->input($item);
		$this->assertSame($builder->created, $box);
		$this->assertSame(TTextBoxMode::MultiLine, $box->getTextMode());
		$this->assertSame("line1\nline2", $box->getText());
		$this->assertSame('multiline-textbox scaffold_input', $box->getCssClass());
		$this->assertCount(1, $this->inputsOfType($item, TRequiredFieldValidator::class));

		$box->setText('changed');
		$this->assertSame('changed', $this->load($builder, $item, $column, $record));
	}

	// ---------------------------------------------------------------------
	// Year
	// ---------------------------------------------------------------------

	private function itemValues(TDropDownList|TCheckBoxList|TRadioButtonList $list): array
	{
		$values = [];
		foreach ($list->getItems() as $listItem) {
			$values[$listItem->getValue()] = $listItem->getText();
		}
		return $values;
	}

	public function testYearControl_listsTenYearsAroundTheCurrentYear()
	{
		$current = (int) date('Y');
		$builder = new CommonScaffoldInputHarness('Year');
		$item = $this->build($builder, $this->column('year'), $this->newRecord(['value' => (string) ($current - 3)]));

		$list = $this->input($item);
		$this->assertInstanceOf(TDropDownList::class, $list);
		$this->assertSame($builder->created, $list);
		$this->assertSame('year-dropdown', $list->getCssClass());
		$expected = [];
		for ($y = $current - 10; $y <= $current + 10; $y++) {
			$expected[(string) $y] = (string) $y;
		}
		$this->assertSame($expected, $this->itemValues($list));
		$this->assertSame((string) ($current - 3), $list->getSelectedValue());
	}

	public function testYearControl_selectsCurrentYearWhenEmpty()
	{
		$item = $this->build(new CommonScaffoldInputHarness('Year'), $this->column('year'), $this->newRecord());

		$this->assertSame(date('Y'), $this->input($item)->getSelectedValue());
	}

	public function testYearControl_keepsYearOutsideTheDefaultRange()
	{
		$item = $this->build(new CommonScaffoldInputHarness('Year'), $this->column('year'), $this->newRecord(['value' => '1950']));
		$list = $this->input($item);

		$this->assertSame('1950', $list->getSelectedValue());
		$this->assertSame('1950', $list->getItems()->itemAt(0)->getValue());
		$this->assertSame((string) ((int) date('Y') + 10), $list->getItems()->itemAt($list->getItems()->getCount() - 1)->getValue());

		$future = (string) ((int) date('Y') + 50);
		$item = $this->build(new CommonScaffoldInputHarness('Year'), $this->column('year'), $this->newRecord(['value' => $future]));
		$this->assertSame($future, $this->input($item)->getSelectedValue());
	}

	// ---------------------------------------------------------------------
	// Integer and float validators
	// ---------------------------------------------------------------------

	public function testIntegerControl_addsIntegerTypeValidator()
	{
		$column = $this->column('int', ['AllowNull' => false]);
		$builder = new CommonScaffoldInputHarness('Integer');
		$item = $this->build($builder, $column, $this->newRecord(['value' => '42']), $this->newParent(null, 'group_int'));

		$this->assertInstanceOf(TTextBox::class, $builder->created);
		$this->assertSame('42', $this->input($item)->getText());
		$this->assertCount(1, $this->inputsOfType($item, TRequiredFieldValidator::class));
		$validators = $this->inputsOfType($item, TDataTypeValidator::class);
		$this->assertCount(1, $validators);
		$val = $validators[0];
		$this->assertSame(TValidationDataType::Integer, $val->getDataType());
		$this->assertSame('Please enter an integer.', $val->getErrorMessage());
		$this->assertSame('required-input2', $val->getControlCssClass());
		$this->assertSame('required', $val->getCssClass());
		$this->assertSame('scaffold_input', $val->getControlToValidate());
		$this->assertSame('group_int', $val->getValidationGroup());
		$this->assertSame(TValidatorDisplayStyle::Dynamic, $val->getDisplay());
	}

	public function testFloatControl_withoutPrecisionHasNoRangeValidator()
	{
		$builder = new CommonScaffoldInputHarness('Float');
		$item = $this->build($builder, $this->column('double'), $this->newRecord(['value' => '1.5']));

		$this->assertInstanceOf(TTextBox::class, $builder->created);
		$validators = $this->inputsOfType($item, TDataTypeValidator::class);
		$this->assertCount(1, $validators);
		$this->assertSame(TValidationDataType::Float, $validators[0]->getDataType());
		$this->assertSame('Please enter a decimal number.', $validators[0]->getErrorMessage());
		$this->assertCount(0, $this->inputsOfType($item, TRangeValidator::class));
	}

	public function testFloatControl_withPrecisionAddsStrictMaximum()
	{
		$column = $this->column('decimal', ['NumericPrecision' => 6, 'NumericScale' => 2]);
		$item = $this->build(new CommonScaffoldInputHarness('Float'), $column, $this->newRecord(), $this->newParent(null, 'group_f'));

		$ranges = $this->inputsOfType($item, TRangeValidator::class);
		$this->assertCount(1, $ranges);
		$range = $ranges[0];
		$this->assertSame(TValidationDataType::Float, $range->getDataType());
		$this->assertSame('10000', $range->getMaxValue());
		$this->assertTrue($range->getStrictComparison());
		$this->assertSame('Please enter a decimal number strictly less than 10000.', $range->getErrorMessage());
		$this->assertSame('required-input3', $range->getControlCssClass());
		$this->assertSame('required', $range->getCssClass());
		$this->assertSame('scaffold_input', $range->getControlToValidate());
		$this->assertSame('group_f', $range->getValidationGroup());
		$this->assertSame(TValidatorDisplayStyle::Dynamic, $range->getDisplay());
	}

	// ---------------------------------------------------------------------
	// Time, date, date-time
	// ---------------------------------------------------------------------

	public function testTimeControl_buildsHourMinuteSecondLists()
	{
		$builder = new CommonScaffoldInputHarness('Time');
		$item = $this->build($builder, $this->column('time'), $this->newRecord(['value' => '13:05:09']));

		[$hour, $min, $sec] = $builder->created;
		$this->assertSame($hour, $this->input($item));
		$this->assertSame($min, $this->input($item, 'scaffold_time_min'));
		$this->assertSame($sec, $this->input($item, 'scaffold_time_sec'));

		$hours = $this->itemValues($hour);
		$this->assertCount(24, $hours);
		$this->assertSame('00', $hours['0']);
		$this->assertSame('23', $hours['23']);
		$this->assertCount(60, $this->itemValues($min));
		$this->assertCount(60, $this->itemValues($sec));
		$this->assertSame('59', $this->itemValues($sec)['59']);

		$this->assertSame('13', $hour->getSelectedValue());
		$this->assertSame('5', $min->getSelectedValue());
		$this->assertSame('9', $sec->getSelectedValue());

		$controls = $item->_input->getControls()->toArray();
		$this->assertSame([$hour, ' : ', $min, ' : ', $sec], $controls);
	}

	public function testTimeControl_valueWithoutSecondsUsesCurrentSecond()
	{
		$before = (int) date('s');
		$builder = new CommonScaffoldInputHarness('Time');
		$item = $this->build($builder, $this->column('time'), $this->newRecord(['value' => '07:30']));
		$after = (int) date('s');

		[$hour, $min, $sec] = $builder->created;
		$this->assertSame('7', $hour->getSelectedValue());
		$this->assertSame('30', $min->getSelectedValue());
		$this->assertContains((int) $sec->getSelectedValue(), array_unique([$before, $after]));
	}

	public function testTimeControl_emptyValueSelectsCurrentTime()
	{
		$before = (int) date('G');
		$builder = new CommonScaffoldInputHarness('Time');
		$this->build($builder, $this->column('time'), $this->newRecord());
		$after = (int) date('G');

		$this->assertContains((int) $builder->created[0]->getSelectedValue(), array_unique([$before, $after]));
	}

	public function testTimeValue_isZeroPadded()
	{
		$column = $this->column('time');
		$builder = new CommonScaffoldInputHarness('Time');
		$record = $this->newRecord(['value' => '13:05:09']);
		$item = $this->build($builder, $column, $record);

		$this->assertSame('13:05:09', $this->load($builder, $item, $column, $record));

		[$hour, $min, $sec] = $builder->created;
		$hour->setSelectedValue('4');
		$min->setSelectedValue('0');
		$sec->setSelectedValue('59');
		$this->assertSame('04:00:59', $this->load($builder, $item, $column, $record));
	}

	public function testDateControl()
	{
		$column = $this->column('date', ['AllowNull' => false]);
		$builder = new CommonScaffoldInputHarness('Date');
		$item = $this->build($builder, $column, $this->newRecord(['value' => '2024-02-29 10:11:12']));

		$picker = $this->input($item);
		$this->assertInstanceOf(TDatePicker::class, $picker);
		$this->assertSame($builder->created, $picker);
		$this->assertSame(1900, $picker->getFromYear());
		$this->assertSame(TDatePickerInputMode::DropDownList, $picker->getInputMode());
		$this->assertSame('yyyy-MM-dd', $picker->getDateFormat());
		$this->assertSame('2024-02-29', $picker->getDate());
		$this->assertSame('date-dropdown', $picker->getCssClass());
		$this->assertCount(1, $this->inputsOfType($item, TRequiredFieldValidator::class));
	}

	public function testDateControl_emptyValueLeavesDateUnset()
	{
		$item = $this->build(new CommonScaffoldInputHarness('Date'), $this->column('date'), $this->newRecord());

		$this->assertSame('', $this->input($item)->getDate());
	}

	public function testDateTimeControl()
	{
		$column = $this->column('datetime');
		$builder = new CommonScaffoldInputHarness('DateTime');
		$record = $this->newRecord(['value' => '2024-02-29 13:05:09']);
		$item = $this->build($builder, $column, $record);

		[$date, $hour, $min, $sec] = $builder->created;
		$this->assertInstanceOf(TDatePicker::class, $date);
		$this->assertSame($date, $this->input($item));
		$this->assertSame($hour, $this->input($item, 'scaffold_time_hour'));
		$this->assertSame($min, $this->input($item, 'scaffold_time_min'));
		$this->assertSame($sec, $this->input($item, 'scaffold_time_sec'));
		$this->assertSame('2024-02-29', $date->getDate());
		$this->assertSame('13', $hour->getSelectedValue());
		$this->assertSame('5', $min->getSelectedValue());
		$this->assertSame('9', $sec->getSelectedValue());
		$this->assertContains(' @ ', $item->_input->getControls()->toArray());

		$this->assertSame('2024-02-29 13:05:09', $this->load($builder, $item, $column, $record));

		$date->setDate('2023-01-02');
		$hour->setSelectedValue('0');
		$min->setSelectedValue('7');
		$sec->setSelectedValue('0');
		$this->assertSame('2023-01-02 00:07:00', $this->load($builder, $item, $column, $record));
	}

	public function testDateTimeControl_valueWithoutTimeKeepsDate()
	{
		$builder = new CommonScaffoldInputHarness('DateTime');
		$this->build($builder, $this->column('datetime'), $this->newRecord(['value' => '2024-02-29']));

		$this->assertSame('2024-02-29', $builder->created[0]->getDate());
	}

	// ---------------------------------------------------------------------
	// Set and enum
	// ---------------------------------------------------------------------

	public function testSetControl()
	{
		$column = $this->column('set', ['DbTypeValues' => ['red', 'green', 'blue']]);
		$builder = new CommonScaffoldInputHarness('Set');
		$record = $this->newRecord(['value' => 'red , blue']);
		$item = $this->build($builder, $column, $record);

		$list = $this->input($item);
		$this->assertInstanceOf(TCheckBoxList::class, $list);
		$this->assertSame($builder->created, $list);
		$this->assertSame('set-checkboxes', $list->getCssClass());
		$this->assertSame(['red', 'green', 'blue'], array_values($this->itemValues($list)));
		$this->assertSame([0, 2], $list->getSelectedIndices());

		$this->assertSame('red,blue', $this->load($builder, $item, $column, $record));

		$list->setSelectedIndices([1]);
		$this->assertSame('green', $this->load($builder, $item, $column, $record));

		$list->clearSelection();
		$this->assertSame('', $this->load($builder, $item, $column, $record));
	}

	public function testSetControl_nullValueSelectsNothing()
	{
		$column = $this->column('set', ['DbTypeValues' => ['red', 'green']]);
		$item = $this->build(new CommonScaffoldInputHarness('Set'), $column, $this->newRecord());

		$this->assertSame([], $this->input($item)->getSelectedIndices());
	}

	public function testEnumControl()
	{
		$column = $this->column('enum', ['DbTypeValues' => ['small', 'medium', 'large'], 'AllowNull' => false]);
		$builder = new CommonScaffoldInputHarness('Enum');
		$record = $this->newRecord(['value' => 'medium']);
		$item = $this->build($builder, $column, $record);

		$list = $this->input($item);
		$this->assertInstanceOf(TRadioButtonList::class, $list);
		$this->assertSame($builder->created, $list);
		$this->assertSame('enum-radio-buttons', $list->getCssClass());
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertCount(1, $this->inputsOfType($item, TRequiredFieldValidator::class));

		$this->assertSame('medium', $this->load($builder, $item, $column, $record));
		$list->setSelectedIndex(2);
		$this->assertSame('large', $this->load($builder, $item, $column, $record));
	}

	public function testEnumControl_unknownOrNullValueSelectsNothing()
	{
		$column = $this->column('enum', ['DbTypeValues' => ['small', 'large']]);

		$item = $this->build(new CommonScaffoldInputHarness('Enum'), $column, $this->newRecord(['value' => 'huge']));
		$this->assertSame(-1, $this->input($item)->getSelectedIndex());

		$item = $this->build(new CommonScaffoldInputHarness('Enum'), $column, $this->newRecord());
		$this->assertSame(-1, $this->input($item)->getSelectedIndex());
	}

	public function testEnumValue_isNullWithoutSelection()
	{
		$column = $this->column('enum', ['DbTypeValues' => ['small', 'large']]);
		$builder = new CommonScaffoldInputHarness('Enum');
		$record = $this->newRecord();
		$item = $this->build($builder, $column, $record);

		$this->assertNull($this->load($builder, $item, $column, $record));
	}

	public function testGetMatchingIndices()
	{
		$builder = new CommonScaffoldInputHarness();

		$this->assertSame([0, 2], $builder->callGetMatchingIndices(['a', 'b', 'c'], ['c', 'a', 'z']));
		$this->assertSame([], $builder->callGetMatchingIndices(['a', 'b'], []));
		$this->assertSame([], $builder->callGetMatchingIndices([], ['a']));
	}
}
