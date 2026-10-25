<?php

namespace Prado\Test\Unit\Data\DbSpecific\Oracle;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TOracleScaffoldInput;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase;
use Prado\Data\ActiveRecord\Scaffold\TScaffoldEditView;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\Common\Oracle\TOracleTableColumn;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TDataTypeValidator;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TTextBoxMode;
use Prado\Web\UI\WebControls\TValidationDataType;

/**
 * Naming container that holds the controls a scaffold input builder creates.
 */
class TOracleScaffoldInputContainer extends TControl implements INamingContainer
{
}

/**
 * TOracleScaffoldInput control selection by Oracle column type, without a database.
 */
class TOracleScaffoldInputTest extends \PHPUnit\Framework\TestCase
{
	private function builder(): TOracleScaffoldInput
	{
		$builder = new TOracleScaffoldInput();
		$parent = $this->createMock(TScaffoldEditView::class);
		$parent->method('getRecordPk')->willReturn(null);
		$parent->method('getValidationGroup')->willReturn('group');
		PradoUnit::setProp($builder, '_parent', $parent);
		return $builder;
	}

	private function column(string $dbType, array $info = []): TOracleTableColumn
	{
		return new TOracleTableColumn($info + ['ColumnName' => 'col', 'ColumnId' => 'col', 'DbType' => $dbType, 'AllowNull' => true]);
	}

	private function record($value = null): TActiveRecord
	{
		$record = $this->createMock(TActiveRecord::class);
		$record->method('getColumnValue')->willReturn($value);
		return $record;
	}

	private function create(TOracleTableColumn $column, $value = null): TOracleScaffoldInputContainer
	{
		$container = new TOracleScaffoldInputContainer();
		PradoUnit::invoke($this->builder(), 'createControl', $container, $column, $this->record($value));
		return $container;
	}

	private function typeValidator(TControl $container): ?TDataTypeValidator
	{
		foreach ($container->getControls() as $control) {
			if ($control instanceof TDataTypeValidator) {
				return $control;
			}
		}
		return null;
	}

	public function testBooleanColumnIsACheckBox()
	{
		$input = $this->create($this->column('BOOLEAN'), 1)->findControl(TScaffoldInputBase::DEFAULT_ID);

		$this->assertInstanceOf(TCheckBox::class, $input);
		$this->assertTrue($input->getChecked());
	}

	public function testDateAndTimestampColumnsAreDateTimeControls()
	{
		foreach (['DATE', 'TIMESTAMP', 'TIMESTAMP WITH TIME ZONE', 'TIMESTAMP WITH LOCAL TIME ZONE'] as $type) {
			$container = $this->create($this->column($type), '2024-03-05 14:07:09');
			$input = $container->findControl(TScaffoldInputBase::DEFAULT_ID);

			$this->assertInstanceOf(TDatePicker::class, $input, $type);
			$this->assertSame('2024-03-05', $input->getDate(), $type);
			$this->assertInstanceOf(TDropDownList::class, $container->findControl('scaffold_time_hour'), $type);
			$this->assertSame('14', (string) $container->findControl('scaffold_time_hour')->getSelectedValue(), $type);
			$this->assertSame('7', (string) $container->findControl('scaffold_time_min')->getSelectedValue(), $type);
			$this->assertSame('9', (string) $container->findControl('scaffold_time_sec')->getSelectedValue(), $type);
		}
	}

	public function testNumberWithScaleZeroIsAnIntegerTextBox()
	{
		foreach (['NUMBER', 'DECIMAL', 'NUMERIC'] as $type) {
			$container = $this->create($this->column($type, ['NumericPrecision' => 10, 'NumericScale' => 0]));

			$this->assertInstanceOf(TTextBox::class, $container->findControl(TScaffoldInputBase::DEFAULT_ID), $type);
			$this->assertSame(TValidationDataType::Integer, $this->typeValidator($container)->getDataType(), $type);
		}
		foreach (['INTEGER', 'INT', 'SMALLINT'] as $type) {
			$container = $this->create($this->column($type));

			$this->assertSame(TValidationDataType::Integer, $this->typeValidator($container)->getDataType(), $type);
		}
	}

	public function testNumberWithAScaleOrWithoutPrecisionIsAFloatTextBox()
	{
		$container = $this->create($this->column('NUMBER', ['NumericPrecision' => 10, 'NumericScale' => 2]));
		$this->assertSame(TValidationDataType::Float, $this->typeValidator($container)->getDataType());

		$container = $this->create($this->column('NUMBER'));
		$this->assertSame(TValidationDataType::Float, $this->typeValidator($container)->getDataType());

		foreach (['FLOAT', 'BINARY_FLOAT', 'BINARY_DOUBLE', 'REAL', 'DOUBLE PRECISION'] as $type) {
			$container = $this->create($this->column($type));

			$this->assertSame(TValidationDataType::Float, $this->typeValidator($container)->getDataType(), $type);
		}
	}

	public function testLargeObjectColumnsAreMultilineTextBoxes()
	{
		foreach (['CLOB', 'NCLOB', 'LONG', 'BLOB'] as $type) {
			$input = $this->create($this->column($type), 'body')->findControl(TScaffoldInputBase::DEFAULT_ID);

			$this->assertInstanceOf(TTextBox::class, $input, $type);
			$this->assertSame(TTextBoxMode::MultiLine, $input->getTextMode(), $type);
			$this->assertSame('body', $input->getText(), $type);
		}
	}

	public function testCharacterColumnsAreTextBoxesLimitedToTheColumnSize()
	{
		foreach (['VARCHAR2', 'NVARCHAR2', 'CHAR', 'NCHAR'] as $type) {
			$container = $this->create($this->column($type, ['ColumnSize' => 40]), 'text');
			$input = $container->findControl(TScaffoldInputBase::DEFAULT_ID);

			$this->assertInstanceOf(TTextBox::class, $input, $type);
			$this->assertSame(TTextBoxMode::SingleLine, $input->getTextMode(), $type);
			$this->assertSame(40, $input->getMaxLength(), $type);
			$this->assertNull($this->typeValidator($container), $type);
		}
	}

	public function testNotNullColumnGetsARequiredValidator()
	{
		$required = static function (TControl $container): bool {
			foreach ($container->getControls() as $control) {
				if ($control instanceof TRequiredFieldValidator) {
					return true;
				}
			}
			return false;
		};

		$this->assertTrue($required($this->create($this->column('VARCHAR2', ['AllowNull' => false]))));
		$this->assertFalse($required($this->create($this->column('VARCHAR2'))));
	}

	public function testControlValuesReadBackByType()
	{
		$builder = $this->builder();

		$container = $this->create($this->column('BOOLEAN'), 1);
		$this->assertTrue(PradoUnit::invoke($builder, 'getControlValue', $container, $this->column('BOOLEAN'), $this->record()));

		$container = $this->create($this->column('DATE'), '2024-03-05 14:07:09');
		$this->assertSame('2024-03-05 14:7:9', PradoUnit::invoke($builder, 'getControlValue', $container, $this->column('DATE'), $this->record()));

		$container = $this->create($this->column('VARCHAR2'), 'text');
		$this->assertSame('text', PradoUnit::invoke($builder, 'getControlValue', $container, $this->column('VARCHAR2'), $this->record()));

		$container = $this->create($this->column('NUMBER', ['NumericScale' => 0]), 7);
		$this->assertSame('7', PradoUnit::invoke($builder, 'getControlValue', $container, $this->column('NUMBER'), $this->record()));
	}
}
