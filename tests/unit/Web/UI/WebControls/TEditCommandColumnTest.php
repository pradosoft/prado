<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TButtonColumnType;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TEditCommandColumn;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;

class TEditCommandColumnTest extends TestCase
{
	/**
	 * Creates a data grid item holding one cell initialized by the column.
	 * @param TEditCommandColumn $column
	 * @param string $itemType
	 * @return TTableCell the initialized cell
	 */
	private function createCell($column, $itemType)
	{
		$item = new TDataGridItem(0, 0, $itemType);
		$cell = $itemType === TListItemType::Header ? new TTableHeaderCell() : new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, $itemType);
		return $cell;
	}

	public function testConstruct()
	{
		$column = new TEditCommandColumn();
		$this->assertInstanceOf(TDataGridColumn::class, $column);
	}

	public function testTextProperties()
	{
		$column = new TEditCommandColumn();
		$defaults = [
			'EditText' => 'Edit',
			'UpdateText' => 'Update',
			'CancelText' => 'Cancel',
			'EditImageUrl' => '',
			'UpdateImageUrl' => '',
			'CancelImageUrl' => '',
			'ValidationGroup' => '',
		];
		foreach ($defaults as $property => $default) {
			$getter = 'get' . $property;
			$setter = 'set' . $property;
			$this->assertSame($default, $column->$getter(), $property);
			$column->$setter('value-' . $property);
			$this->assertSame('value-' . $property, $column->$getter(), $property);
			$this->assertSame('value-' . $property, $column->saveState()[$property], $property);
			$column->$setter($default);
			$this->assertSame($default, $column->$getter(), $property);
			$this->assertArrayNotHasKey($property, $column->saveState(), $property);
		}
	}

	public function testButtonType()
	{
		$column = new TEditCommandColumn();
		$this->assertSame(TButtonColumnType::LinkButton, $column->getButtonType());
		$column->setButtonType('PushButton');
		$this->assertSame(TButtonColumnType::PushButton, $column->getButtonType());
		$this->assertSame(TButtonColumnType::PushButton, $column->saveState()['ButtonType']);
		$column->setButtonType(TButtonColumnType::ImageButton);
		$this->assertSame(TButtonColumnType::ImageButton, $column->getButtonType());
		$column->setButtonType(TButtonColumnType::LinkButton);
		$this->assertSame(TButtonColumnType::LinkButton, $column->getButtonType());
		$this->assertArrayNotHasKey('ButtonType', $column->saveState());
	}

	public function testButtonTypeInvalid()
	{
		$column = new TEditCommandColumn();
		$this->expectException(TInvalidDataValueException::class);
		$column->setButtonType('RadioButton');
	}

	public function testCausesValidation()
	{
		$column = new TEditCommandColumn();
		$this->assertTrue($column->getCausesValidation());
		$column->setCausesValidation('false');
		$this->assertFalse($column->getCausesValidation());
		$this->assertFalse($column->saveState()['CausesValidation']);
		$column->setCausesValidation(true);
		$this->assertTrue($column->getCausesValidation());
		$this->assertArrayNotHasKey('CausesValidation', $column->saveState());
	}

	public function testItemCellsHaveEditButton()
	{
		$column = new TEditCommandColumn();
		$column->setEditText('Modify');
		$column->setValidationGroup('group');
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$this->assertSame(1, $cell->getControls()->getCount(), $itemType);
			$button = $cell->getControls()->itemAt(0);
			$this->assertInstanceOf(TLinkButton::class, $button, $itemType);
			$this->assertSame($button, $cell->getRegisteredObject('EditButton'), $itemType);
			$this->assertSame($button, $cell->EditButton, $itemType);
			$this->assertSame('Modify', $button->getText(), $itemType);
			$this->assertSame('Edit', $button->getCommandName(), $itemType);
			$this->assertFalse($button->getCausesValidation(), $itemType);
			$this->assertSame('', $button->getValidationGroup(), $itemType);
		}
	}

	public function testEditItemCellHasUpdateAndCancelButtons()
	{
		$column = new TEditCommandColumn();
		$column->setUpdateText('Save');
		$column->setCancelText('Back');
		$column->setValidationGroup('group');
		$cell = $this->createCell($column, TListItemType::EditItem);
		$controls = $cell->getControls();
		$this->assertSame(3, $controls->getCount());

		$update = $controls->itemAt(0);
		$this->assertInstanceOf(TLinkButton::class, $update);
		$this->assertSame($update, $cell->UpdateButton);
		$this->assertSame('Save', $update->getText());
		$this->assertSame('Update', $update->getCommandName());
		$this->assertTrue($update->getCausesValidation());
		$this->assertSame('group', $update->getValidationGroup());

		$this->assertSame('&nbsp;', $controls->itemAt(1));

		$cancel = $controls->itemAt(2);
		$this->assertInstanceOf(TLinkButton::class, $cancel);
		$this->assertSame($cancel, $cell->CancelButton);
		$this->assertSame('Back', $cancel->getText());
		$this->assertSame('Cancel', $cancel->getCommandName());
		$this->assertFalse($cancel->getCausesValidation());
		$this->assertSame('', $cancel->getValidationGroup());
	}

	public function testEditItemUpdateWithoutValidation()
	{
		$column = new TEditCommandColumn();
		$column->setCausesValidation(false);
		$this->assertFalse($this->createCell($column, TListItemType::EditItem)->UpdateButton->getCausesValidation());
	}

	public function testPushButtons()
	{
		$column = new TEditCommandColumn();
		$column->setButtonType(TButtonColumnType::PushButton);
		$this->assertInstanceOf(TButton::class, $this->createCell($column, TListItemType::Item)->EditButton);
		$cell = $this->createCell($column, TListItemType::EditItem);
		$this->assertInstanceOf(TButton::class, $cell->UpdateButton);
		$this->assertInstanceOf(TButton::class, $cell->CancelButton);
		$this->assertSame('Update', $cell->UpdateButton->getText());
		$this->assertSame('Cancel', $cell->CancelButton->getText());
	}

	public function testImageButtons()
	{
		$column = new TEditCommandColumn();
		$column->setButtonType(TButtonColumnType::ImageButton);
		$column->setEditImageUrl('edit.png');
		$column->setUpdateImageUrl('update.png');
		$column->setCancelImageUrl('cancel.png');

		$edit = $this->createCell($column, TListItemType::Item)->EditButton;
		$this->assertInstanceOf(TImageButton::class, $edit);
		$this->assertSame('edit.png', $edit->getImageUrl());
		$this->assertSame('Edit', $edit->getText());
		$this->assertSame('Edit', $edit->getCommandName());

		$cell = $this->createCell($column, TListItemType::EditItem);
		$this->assertInstanceOf(TImageButton::class, $cell->UpdateButton);
		$this->assertSame('update.png', $cell->UpdateButton->getImageUrl());
		$this->assertInstanceOf(TImageButton::class, $cell->CancelButton);
		$this->assertSame('cancel.png', $cell->CancelButton->getImageUrl());
	}

	public function testCreateButtonMatchesCommandNameCaseInsensitively()
	{
		$column = new TEditCommandColumn();
		$column->setButtonType(TButtonColumnType::ImageButton);
		$column->setEditImageUrl('edit.png');
		$column->setUpdateImageUrl('update.png');
		$column->setCancelImageUrl('cancel.png');
		$update = PradoUnit::invoke($column, 'createButton', 'UPDATE', 'Go', true, 'g');
		$this->assertSame('update.png', $update->getImageUrl());
		$this->assertSame('UPDATE', $update->getCommandName());
		$this->assertSame('Go', $update->getText());
		$this->assertTrue($update->getCausesValidation());
		$this->assertSame('g', $update->getValidationGroup());
		$this->assertSame('cancel.png', PradoUnit::invoke($column, 'createButton', 'cancel', '', false, '')->getImageUrl());
		$this->assertSame('edit.png', PradoUnit::invoke($column, 'createButton', 'Other', '', false, '')->getImageUrl());
	}

	public function testHeaderAndFooterUseParent()
	{
		$column = new TEditCommandColumn();
		$column->setHeaderText('Actions');
		$column->setFooterText('End');
		$header = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Actions', $header->getText());
		$this->assertFalse($header->getHasControls());
		$this->assertSame('End', $this->createCell($column, TListItemType::Footer)->getText());
		$separator = $this->createCell($column, TListItemType::Separator);
		$this->assertSame('', $separator->getText());
		$this->assertFalse($separator->getHasControls());
	}

	public function testDataGridBinding()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$grid->setEditItemIndex(1);
		$grid->getColumns()->add(new TEditCommandColumn());
		$grid->setDataSource([['id' => 1], ['id' => 2]]);
		$grid->dataBind();

		$items = $grid->getItems();
		$first = $items->itemAt(0)->getCells()->itemAt(0);
		$this->assertInstanceOf(TLinkButton::class, $first->EditButton);
		$edit = $items->itemAt(1)->getCells()->itemAt(0);
		$this->assertSame('Update', $edit->UpdateButton->getCommandName());
		$this->assertSame('Cancel', $edit->CancelButton->getCommandName());
	}
}
