<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\ActiveControls\TActiveBoundColumn;
use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\ActiveControls\TActiveButtonColumn;
use Prado\Web\UI\ActiveControls\TActiveCheckBox;
use Prado\Web\UI\ActiveControls\TActiveCheckBoxColumn;
use Prado\Web\UI\ActiveControls\TActiveDropDownListColumn;
use Prado\Web\UI\ActiveControls\TActiveEditCommandColumn;
use Prado\Web\UI\ActiveControls\TActiveHyperLinkColumn;
use Prado\Web\UI\ActiveControls\TActiveImage;
use Prado\Web\UI\ActiveControls\TActiveImageButton;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TActiveLiteralColumn;
use Prado\Web\UI\ActiveControls\TActiveTemplateColumn;
use Prado\Web\UI\WebControls\TBoundColumn;
use Prado\Web\UI\WebControls\TButtonColumn;
use Prado\Web\UI\WebControls\TButtonColumnType;
use Prado\Web\UI\WebControls\TCheckBoxColumn;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TDropDownListColumn;
use Prado\Web\UI\WebControls\TEditCommandColumn;
use Prado\Web\UI\WebControls\THyperLinkColumn;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TLiteralColumn;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableHeaderCell;
use Prado\Web\UI\WebControls\TTemplateColumn;

class TActiveColumnsTest extends TestCase
{
	/**
	 * Creates a data grid item holding one cell initialized by the column.
	 * @param TDataGridColumn $column
	 * @param string $itemType
	 * @param mixed $data
	 * @return TTableCell the initialized cell
	 */
	private function createCell($column, $itemType, $data = null)
	{
		$item = new TDataGridItem(0, 0, $itemType);
		$item->setData($data);
		$cell = $itemType === TListItemType::Header ? new TTableHeaderCell() : new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, $itemType);
		return $cell;
	}

	/**
	 * Provides each active column overriding initializeHeaderCell, its parent class,
	 * and whether it copies the header text into the tool tip.
	 * @return array the column class, parent class, and tool tip flag
	 */
	public static function headerColumnProvider(): array
	{
		return [
			'TActiveBoundColumn' => [TActiveBoundColumn::class, TBoundColumn::class, true],
			'TActiveCheckBoxColumn' => [TActiveCheckBoxColumn::class, TCheckBoxColumn::class, false],
			'TActiveDropDownListColumn' => [TActiveDropDownListColumn::class, TDropDownListColumn::class, false],
			'TActiveHyperLinkColumn' => [TActiveHyperLinkColumn::class, THyperLinkColumn::class, false],
			'TActiveLiteralColumn' => [TActiveLiteralColumn::class, TLiteralColumn::class, true],
			'TActiveTemplateColumn' => [TActiveTemplateColumn::class, TTemplateColumn::class, false],
		];
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testInheritance($class, $parentClass, $toolTip)
	{
		$this->assertInstanceOf($parentClass, new $class());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testHeaderRenderer($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setHeaderText('Name');
		$column->setSortExpression('name');
		$column->setHeaderImageUrl('head.png');
		$column->setHeaderRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame(1, $cell->getControls()->getCount());
		$label = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertSame('Name', $label->getText());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testHeaderSortImageButton($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setSortExpression('name');
		$column->setHeaderImageUrl('sort.png');
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$button = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TActiveImageButton::class, $button);
		$this->assertSame('sort.png', $button->getImageUrl());
		$this->assertSame(TDataGrid::CMD_SORT, $button->getCommandName());
		$this->assertSame('name', $button->getCommandParameter());
		$this->assertSame('Name', $button->getAlternateText());
		$this->assertSame($toolTip ? 'Name' : '', $button->getToolTip());
		$this->assertFalse($button->getCausesValidation());
		$this->assertSame('', $cell->getText());

		$column->setHeaderText('');
		$button = $this->createCell($column, TListItemType::Header)->getControls()->itemAt(0);
		$this->assertInstanceOf(TActiveImageButton::class, $button);
		$this->assertSame('', $button->getAlternateText());
		$this->assertSame('', $button->getToolTip());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testHeaderSortLinkButton($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setSortExpression('name');
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$button = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TActiveLinkButton::class, $button);
		$this->assertSame('Name', $button->getText());
		$this->assertSame(TDataGrid::CMD_SORT, $button->getCommandName());
		$this->assertSame('name', $button->getCommandParameter());
		$this->assertFalse($button->getCausesValidation());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testHeaderSortWithoutTextOrImage($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setSortExpression('name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('&nbsp;', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testHeaderNoSortWhenGridDisallowsSorting($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setOwner(new TDataGrid());
		$column->setSortExpression('name');
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Name', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testHeaderImage($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setHeaderImageUrl('head.png');
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$image = $cell->getControls()->itemAt(0);
		$this->assertInstanceOf(TActiveImage::class, $image);
		$this->assertNotInstanceOf(TActiveImageButton::class, $image);
		$this->assertSame('head.png', $image->getImageUrl());
		$this->assertSame('Name', $image->getAlternateText());
		$this->assertSame($toolTip ? 'Name' : '', $image->getToolTip());
		$this->assertSame('', $cell->getText());

		$column->setHeaderText('');
		$image = $this->createCell($column, TListItemType::Header)->getControls()->itemAt(0);
		$this->assertInstanceOf(TActiveImage::class, $image);
		$this->assertSame('', $image->getAlternateText());
		$this->assertSame('', $image->getToolTip());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testHeaderText($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setHeaderText('Name');
		$cell = $this->createCell($column, TListItemType::Header);
		$this->assertSame('Name', $cell->getText());
		$this->assertFalse($cell->getHasControls());

		$cell = $this->createCell(new $class(), TListItemType::Header);
		$this->assertSame('&nbsp;', $cell->getText());
		$this->assertFalse($cell->getHasControls());
	}

	/**
	 * @dataProvider headerColumnProvider
	 * @param string $class
	 * @param string $parentClass
	 * @param bool $toolTip
	 */
	public function testFooterUsesParent($class, $parentClass, $toolTip)
	{
		$column = new $class();
		$column->setFooterText('End');
		$this->assertSame('End', $this->createCell($column, TListItemType::Footer)->getText());
	}

	public function testActiveBoundColumnItemCells()
	{
		$column = new TActiveBoundColumn();
		$column->setDataField('name');
		$cell = $this->createCell($column, TListItemType::Item, ['name' => 'Alice']);
		$cell->dataBind();
		$this->assertSame('Alice', $cell->getText());
	}

	public function testActiveButtonColumnInheritance()
	{
		$column = new TActiveButtonColumn();
		$this->assertInstanceOf(TButtonColumn::class, $column);
	}

	public function testActiveButtonColumnLinkButton()
	{
		$column = new TActiveButtonColumn();
		$column->setText('Go');
		$column->setCommandName('select');
		$column->setCausesValidation(true);
		$column->setValidationGroup('group');
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem, TListItemType::EditItem] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$this->assertSame(1, $cell->getControls()->getCount(), $itemType);
			$button = $cell->getControls()->itemAt(0);
			$this->assertInstanceOf(TActiveLinkButton::class, $button, $itemType);
			$this->assertSame($button, $cell->getRegisteredObject('Button'), $itemType);
			$this->assertSame('Go', $button->getText(), $itemType);
			$this->assertSame('select', $button->getCommandName(), $itemType);
			$this->assertTrue($button->getCausesValidation(), $itemType);
			$this->assertSame('group', $button->getValidationGroup(), $itemType);
			$this->assertFalse($button->hasEventHandler('OnDataBinding'), $itemType);
		}
	}

	public function testActiveButtonColumnPushButton()
	{
		$column = new TActiveButtonColumn();
		$column->setButtonType(TButtonColumnType::PushButton);
		$column->setText('Push');
		$column->setCausesValidation(false);
		$button = $this->createCell($column, TListItemType::Item)->getRegisteredObject('Button');
		$this->assertInstanceOf(TActiveButton::class, $button);
		$this->assertSame('Push', $button->getText());
		$this->assertFalse($button->getCausesValidation());
	}

	public function testActiveButtonColumnImageButton()
	{
		$column = new TActiveButtonColumn();
		$column->setButtonType(TButtonColumnType::ImageButton);
		$column->setImageUrl('go.png');
		$column->setText('Go');
		$button = $this->createCell($column, TListItemType::Item)->getRegisteredObject('Button');
		$this->assertInstanceOf(TActiveImageButton::class, $button);
		$this->assertSame('go.png', $button->getImageUrl());
		$this->assertSame('Go', $button->getToolTip());
		$this->assertSame('Go', $button->getText());
		$this->assertFalse($button->hasEventHandler('OnDataBinding'));
	}

	public function testActiveButtonColumnDataTextField()
	{
		$column = new TActiveButtonColumn();
		$column->setDataTextField('name');
		$column->setDataTextFormatString('[%s]');
		$cell = $this->createCell($column, TListItemType::Item, ['name' => 'Bob']);
		$button = $cell->getRegisteredObject('Button');
		$this->assertTrue($button->hasEventHandler('OnDataBinding'));
		$cell->getParent()->dataBind();
		$this->assertSame('[Bob]', $button->getText());
	}

	public function testActiveButtonColumnDataImageUrlField()
	{
		$column = new TActiveButtonColumn();
		$column->setButtonType(TButtonColumnType::ImageButton);
		$column->setDataImageUrlField('icon');
		$cell = $this->createCell($column, TListItemType::Item, ['icon' => 'row.png']);
		$button = $cell->getRegisteredObject('Button');
		$this->assertTrue($button->hasEventHandler('OnDataBinding'));
		$cell->getParent()->dataBind();
		$this->assertSame('row.png', $button->getImageUrl());

		$column->setButtonType(TButtonColumnType::LinkButton);
		$button = $this->createCell($column, TListItemType::Item, ['icon' => 'row.png'])->getRegisteredObject('Button');
		$this->assertFalse($button->hasEventHandler('OnDataBinding'));
	}

	public function testActiveButtonColumnHeaderUsesParent()
	{
		$column = new TActiveButtonColumn();
		$column->setHeaderText('Action');
		$column->setFooterText('End');
		$this->assertSame('Action', $this->createCell($column, TListItemType::Header)->getText());
		$this->assertSame('End', $this->createCell($column, TListItemType::Footer)->getText());
	}

	public function testActiveCheckBoxColumnItemCells()
	{
		$column = new TActiveCheckBoxColumn();
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem] as $itemType) {
			$cell = $this->createCell($column, $itemType);
			$this->assertSame(1, $cell->getControls()->getCount(), $itemType);
			$checkBox = $cell->getControls()->itemAt(0);
			$this->assertInstanceOf(TActiveCheckBox::class, $checkBox, $itemType);
			$this->assertSame($checkBox, $cell->getRegisteredObject('CheckBox'), $itemType);
			$this->assertFalse($checkBox->getEnabled(false), $itemType);
			$this->assertSame('Center', $cell->getHorizontalAlign(), $itemType);
			$this->assertFalse($checkBox->hasEventHandler('OnDataBinding'), $itemType);
		}
	}

	public function testActiveCheckBoxColumnEditItem()
	{
		$column = new TActiveCheckBoxColumn();
		$checkBox = $this->createCell($column, TListItemType::EditItem)->getRegisteredObject('CheckBox');
		$this->assertInstanceOf(TActiveCheckBox::class, $checkBox);
		$this->assertTrue($checkBox->getEnabled(false));

		$column->setReadOnly(true);
		$checkBox = $this->createCell($column, TListItemType::EditItem)->getRegisteredObject('CheckBox');
		$this->assertFalse($checkBox->getEnabled(false));
	}

	public function testActiveCheckBoxColumnDataField()
	{
		$column = new TActiveCheckBoxColumn();
		$column->setDataField('active');
		$cell = $this->createCell($column, TListItemType::EditItem, ['active' => true]);
		$checkBox = $cell->getRegisteredObject('CheckBox');
		$this->assertTrue($checkBox->hasEventHandler('OnDataBinding'));
		$cell->dataBind();
		$this->assertTrue($checkBox->getChecked());
	}

	public function testActiveDropDownListColumnEditItem()
	{
		$column = new TActiveDropDownListColumn();
		$column->setDataValueField('id');
		$cell = $this->createCell($column, TListItemType::EditItem, ['id' => 'One']);
		$this->assertInstanceOf(TDropDownList::class, $cell->getRegisteredObject('DropDownList'));
	}

	public function testActiveEditCommandColumnInheritance()
	{
		$this->assertInstanceOf(TEditCommandColumn::class, new TActiveEditCommandColumn());
	}

	public function testActiveEditCommandColumnLinkButtons()
	{
		$column = new TActiveEditCommandColumn();
		$column->setValidationGroup('group');
		$edit = $this->createCell($column, TListItemType::Item)->EditButton;
		$this->assertInstanceOf(TActiveLinkButton::class, $edit);
		$this->assertSame('Edit', $edit->getText());
		$this->assertSame('Edit', $edit->getCommandName());
		$this->assertFalse($edit->getCausesValidation());
		$this->assertSame('', $edit->getValidationGroup());

		$cell = $this->createCell($column, TListItemType::EditItem);
		$this->assertSame(3, $cell->getControls()->getCount());
		$this->assertInstanceOf(TActiveLinkButton::class, $cell->UpdateButton);
		$this->assertSame('Update', $cell->UpdateButton->getCommandName());
		$this->assertTrue($cell->UpdateButton->getCausesValidation());
		$this->assertSame('group', $cell->UpdateButton->getValidationGroup());
		$this->assertInstanceOf(TActiveLinkButton::class, $cell->CancelButton);
		$this->assertSame('Cancel', $cell->CancelButton->getText());
		$this->assertFalse($cell->CancelButton->getCausesValidation());
	}

	public function testActiveEditCommandColumnPushButtons()
	{
		$column = new TActiveEditCommandColumn();
		$column->setButtonType(TButtonColumnType::PushButton);
		$this->assertInstanceOf(TActiveButton::class, $this->createCell($column, TListItemType::Item)->EditButton);
		$cell = $this->createCell($column, TListItemType::EditItem);
		$this->assertInstanceOf(TActiveButton::class, $cell->UpdateButton);
		$this->assertInstanceOf(TActiveButton::class, $cell->CancelButton);
	}

	public function testActiveEditCommandColumnImageButtons()
	{
		$column = new TActiveEditCommandColumn();
		$column->setButtonType(TButtonColumnType::ImageButton);
		$column->setEditImageUrl('edit.png');
		$column->setUpdateImageUrl('update.png');
		$column->setCancelImageUrl('cancel.png');
		$column->setEditText('Modify');

		$edit = $this->createCell($column, TListItemType::Item)->EditButton;
		$this->assertInstanceOf(TActiveImageButton::class, $edit);
		$this->assertSame('edit.png', $edit->getImageUrl());
		$this->assertSame('Modify', $edit->getToolTip());
		$this->assertSame('Modify', $edit->getText());

		$cell = $this->createCell($column, TListItemType::EditItem);
		$this->assertInstanceOf(TActiveImageButton::class, $cell->UpdateButton);
		$this->assertSame('update.png', $cell->UpdateButton->getImageUrl());
		$this->assertSame('Update', $cell->UpdateButton->getToolTip());
		$this->assertInstanceOf(TActiveImageButton::class, $cell->CancelButton);
		$this->assertSame('cancel.png', $cell->CancelButton->getImageUrl());
		$this->assertSame('Cancel', $cell->CancelButton->getToolTip());
	}

	public function testActiveEditCommandColumnHeaderUsesParent()
	{
		$column = new TActiveEditCommandColumn();
		$column->setHeaderText('Actions');
		$this->assertSame('Actions', $this->createCell($column, TListItemType::Header)->getText());
	}

	public function testActiveHyperLinkColumnItemCells()
	{
		$column = new TActiveHyperLinkColumn();
		$column->setText('Open');
		$cell = $this->createCell($column, TListItemType::Item);
		$this->assertSame('Open', $cell->getControls()->itemAt(0)->getText());
	}

	public function testActiveLiteralColumnItemCells()
	{
		$column = new TActiveLiteralColumn();
		$column->setDataField('name');
		$cell = $this->createCell($column, TListItemType::Item, ['name' => 'Carl']);
		$cell->dataBind();
		$this->assertSame('Carl', $cell->getText());
	}

	public function testActiveTemplateColumnItemCells()
	{
		$column = new TActiveTemplateColumn();
		$column->setItemRenderer(TLabel::class);
		$cell = $this->createCell($column, TListItemType::Item, 'Dora');
		$cell->dataBind();
		$this->assertSame('Dora', $cell->getControls()->itemAt(0)->getText());
	}

	public function testDataGridBinding()
	{
		$grid = new TDataGrid();
		$grid->setAutoGenerateColumns(false);
		$grid->setEditItemIndex(1);
		$bound = new TActiveBoundColumn();
		$bound->setDataField('name');
		$bound->setHeaderText('Name');
		$bound->setSortExpression('name');
		$grid->getColumns()->add($bound);
		$check = new TActiveCheckBoxColumn();
		$check->setDataField('active');
		$grid->getColumns()->add($check);
		$grid->getColumns()->add(new TActiveEditCommandColumn());
		$grid->setAllowSorting(true);
		$grid->setDataSource([['name' => 'ann', 'active' => true], ['name' => 'ben', 'active' => false]]);
		$grid->dataBind();

		$header = $grid->getHeader()->getCells()->itemAt(0)->getControls()->itemAt(0);
		$this->assertInstanceOf(TActiveLinkButton::class, $header);
		$this->assertSame('Name', $header->getText());
		$items = $grid->getItems();
		$this->assertSame('ann', $items->itemAt(0)->getCells()->itemAt(0)->getText());
		$this->assertTrue($items->itemAt(0)->getCells()->itemAt(1)->CheckBox->getChecked());
		$this->assertInstanceOf(TActiveLinkButton::class, $items->itemAt(0)->getCells()->itemAt(2)->EditButton);
		$this->assertSame('ben', $items->itemAt(1)->getCells()->itemAt(0)->TextBox->getText());
		$this->assertInstanceOf(TActiveLinkButton::class, $items->itemAt(1)->getCells()->itemAt(2)->UpdateButton);
	}
}
