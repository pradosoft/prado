<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TButtonColumn;
use Prado\Web\UI\WebControls\TButtonColumnType;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TTableCell;

class TButtonColumnTest extends TestCase
{
	/**
	 * Creates a data grid item holding one cell initialized by the column.
	 * @param TButtonColumn $column
	 * @param mixed $data
	 * @param mixed $itemType
	 * @return TTableCell the initialized cell
	 */
	private function createCell(TButtonColumn $column, $data, $itemType = TListItemType::Item)
	{
		$item = new TDataGridItem(0, 0, $itemType);
		$item->setData($data);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, $itemType);
		return $cell;
	}

	/**
	 * Creates, initializes and data binds a cell.
	 * @param TButtonColumn $column
	 * @param mixed $data
	 * @param mixed $itemType
	 * @return mixed the button control of the cell
	 */
	private function bindButton(TButtonColumn $column, $data, $itemType = TListItemType::Item)
	{
		$cell = $this->createCell($column, $data, $itemType);
		$cell->getParent()->dataBind();
		return $cell->getRegisteredObject('Button');
	}

	public function testConstruct()
	{
		$column = new TButtonColumn();
		$this->assertInstanceOf(TDataGridColumn::class, $column);
	}

	public function testDefaults()
	{
		$column = new TButtonColumn();
		$this->assertSame('', $column->getText());
		$this->assertSame('', $column->getDataTextField());
		$this->assertSame('', $column->getDataTextFormatString());
		$this->assertSame('', $column->getImageUrl());
		$this->assertSame('', $column->getDataImageUrlField());
		$this->assertSame('', $column->getDataImageUrlFormatString());
		$this->assertSame(TButtonColumnType::LinkButton, $column->getButtonType());
		$this->assertSame('', $column->getCommandName());
		$this->assertTrue($column->getCausesValidation());
		$this->assertSame('', $column->getValidationGroup());
		$this->assertSame([], $column->saveState());
	}

	public function testStringProperties()
	{
		$column = new TButtonColumn();
		$properties = [
			'Text' => 'Delete',
			'DataTextField' => 'name',
			'DataTextFormatString' => 'Name: %s',
			'ImageUrl' => '/img/delete.png',
			'DataImageUrlField' => 'icon',
			'DataImageUrlFormatString' => '/img/%s.png',
			'CommandName' => 'delete',
			'ValidationGroup' => 'grid',
		];
		foreach ($properties as $name => $value) {
			$column->{'set' . $name}($value);
			$this->assertSame($value, $column->{'get' . $name}(), $name);
		}
		$this->assertSame($properties, $column->saveState());
		foreach ($properties as $name => $value) {
			$column->{'set' . $name}('');
			$this->assertSame('', $column->{'get' . $name}(), $name);
		}
		$this->assertSame([], $column->saveState());
	}

	public function testButtonType()
	{
		$column = new TButtonColumn();
		$column->setButtonType('PushButton');
		$this->assertSame(TButtonColumnType::PushButton, $column->getButtonType());
		$column->setButtonType(TButtonColumnType::ImageButton);
		$this->assertSame(TButtonColumnType::ImageButton, $column->getButtonType());
		$column->setButtonType(TButtonColumnType::LinkButton);
		$this->assertSame(TButtonColumnType::LinkButton, $column->getButtonType());
		$this->assertSame([], $column->saveState());
	}

	public function testButtonTypeInvalid()
	{
		$column = new TButtonColumn();
		$this->expectException(TInvalidDataValueException::class);
		$column->setButtonType('RadioButton');
	}

	public function testCausesValidation()
	{
		$column = new TButtonColumn();
		$column->setCausesValidation('false');
		$this->assertFalse($column->getCausesValidation());
		$column->setCausesValidation(true);
		$this->assertTrue($column->getCausesValidation());
		$this->assertSame([], $column->saveState());
	}

	public function testCallableFormatStrings()
	{
		$column = new TButtonColumn();
		$text = fn ($value) => strtoupper($value);
		$url = fn ($value) => "/u/$value";
		$column->setDataTextFormatString($text);
		$column->setDataImageUrlFormatString($url);
		$this->assertSame($text, $column->getDataTextFormatString());
		$this->assertSame($url, $column->getDataImageUrlFormatString());
		$this->assertSame([], $column->saveState());
	}

	public function testInitializeCellButtonTypes()
	{
		$expected = [
			TButtonColumnType::LinkButton => TLinkButton::class,
			TButtonColumnType::PushButton => TButton::class,
			TButtonColumnType::ImageButton => TImageButton::class,
		];
		$itemTypes = [TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem, TListItemType::EditItem];
		foreach ($expected as $buttonType => $class) {
			$column = new TButtonColumn();
			$column->setButtonType($buttonType);
			foreach ($itemTypes as $itemType) {
				$cell = $this->createCell($column, [], $itemType);
				$this->assertSame(1, $cell->getControls()->getCount());
				$button = $cell->getControls()->itemAt(0);
				$this->assertInstanceOf($class, $button, "$buttonType / $itemType");
				$this->assertSame($button, $cell->getRegisteredObject('Button'));
			}
		}
	}

	public function testInitializeCellCopiesProperties()
	{
		foreach ([TButtonColumnType::LinkButton, TButtonColumnType::PushButton, TButtonColumnType::ImageButton] as $buttonType) {
			$column = new TButtonColumn();
			$column->setButtonType($buttonType);
			$column->setText('Remove');
			$column->setCommandName('delete');
			$column->setCausesValidation(false);
			$column->setValidationGroup('rows');
			$column->setImageUrl('/img/x.png');
			$button = $this->createCell($column, [])->getRegisteredObject('Button');
			$this->assertSame('Remove', $button->getText(), $buttonType);
			$this->assertSame('delete', $button->getCommandName(), $buttonType);
			$this->assertFalse($button->getCausesValidation(), $buttonType);
			$this->assertSame('rows', $button->getValidationGroup(), $buttonType);
			if ($buttonType === TButtonColumnType::ImageButton) {
				$this->assertSame('/img/x.png', $button->getImageUrl());
				$this->assertSame('Remove', $button->getToolTip());
			}
		}
	}

	public function testInitializeCellAttachesDataBindingOnlyWithFields()
	{
		$column = new TButtonColumn();
		$button = $this->createCell($column, [])->getRegisteredObject('Button');
		$this->assertFalse($button->hasEventHandler('OnDataBinding'));

		$column->setDataImageUrlField('icon');
		$button = $this->createCell($column, [])->getRegisteredObject('Button');
		$this->assertFalse($button->hasEventHandler('OnDataBinding'), 'image field is ignored for link buttons');

		$column->setButtonType(TButtonColumnType::ImageButton);
		$button = $this->createCell($column, [])->getRegisteredObject('Button');
		$this->assertTrue($button->hasEventHandler('OnDataBinding'));

		$column = new TButtonColumn();
		$column->setDataTextField('name');
		$button = $this->createCell($column, [])->getRegisteredObject('Button');
		$this->assertTrue($button->hasEventHandler('OnDataBinding'));
	}

	public function testInitializeCellHeaderFooterAndOthers()
	{
		$column = new TButtonColumn();
		$column->setHeaderText('Actions');
		$column->setFooterText('End');
		$column->setText('Go');

		$cell = $this->createCell($column, [], TListItemType::Header);
		$this->assertSame('Actions', $cell->getText());
		$this->assertSame(0, $cell->getControls()->getCount());

		$cell = $this->createCell($column, [], TListItemType::Footer);
		$this->assertSame('End', $cell->getText());
		$this->assertSame(0, $cell->getControls()->getCount());

		foreach ([TListItemType::Separator, TListItemType::Pager] as $itemType) {
			$cell = $this->createCell($column, [], $itemType);
			$this->assertSame('', $cell->getText());
			$this->assertSame(0, $cell->getControls()->getCount());
		}
	}

	public function testDataBindText()
	{
		$column = new TButtonColumn();
		$column->setText('static');
		$column->setDataTextField('name');
		$button = $this->bindButton($column, ['name' => 'alpha']);
		$this->assertSame('alpha', $button->getText());

		$column->setButtonType(TButtonColumnType::PushButton);
		$button = $this->bindButton($column, ['name' => 7]);
		$this->assertSame('7', $button->getText());
	}

	public function testDataBindTextFormats()
	{
		$column = new TButtonColumn();
		$column->setDataTextField('name');

		$column->setDataTextFormatString('Edit %s');
		$this->assertSame('Edit alpha', $this->bindButton($column, ['name' => 'alpha'])->getText());

		$column->setDataTextFormatString('#strtoupper({0})');
		$this->assertSame('ALPHA', $this->bindButton($column, ['name' => 'alpha'])->getText());

		$column->setDataTextFormatString(fn ($value) => "<$value>");
		$this->assertSame('<alpha>', $this->bindButton($column, ['name' => 'alpha'])->getText());
	}

	public function testDataBindImageButton()
	{
		$column = new TButtonColumn();
		$column->setButtonType(TButtonColumnType::ImageButton);
		$column->setImageUrl('/img/default.png');
		$column->setText('Tip');
		$column->setDataImageUrlField('icon');
		$button = $this->bindButton($column, ['icon' => '/img/a.png', 'name' => 'alpha']);
		$this->assertSame('/img/a.png', $button->getImageUrl());
		$this->assertSame('Tip', $button->getText());

		$column->setDataTextField('name');
		$column->setDataTextFormatString('Name %s');
		$column->setDataImageUrlFormatString('/img/%s.png');
		$button = $this->bindButton($column, ['icon' => 'b', 'name' => 'beta']);
		$this->assertSame('/img/b.png', $button->getImageUrl());
		$this->assertSame('Name beta', $button->getText());
	}

	public function testDataBindImageUrlFormats()
	{
		$column = new TButtonColumn();
		$column->setButtonType(TButtonColumnType::ImageButton);
		$column->setDataImageUrlField('id');

		$column->setDataImageUrlFormatString('#"/icons/" . ({0} * 10) . ".png"');
		$this->assertSame('/icons/30.png', $this->bindButton($column, ['id' => 3])->getImageUrl());

		$column->setDataImageUrlFormatString(fn ($value) => "/callable/$value.gif");
		$this->assertSame('/callable/3.gif', $this->bindButton($column, ['id' => 3])->getImageUrl());
	}

	public function testDataBindObjectData()
	{
		$column = new TButtonColumn();
		$column->setDataTextField('ItemType');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(new TDataGridItem(1, 1, TListItemType::SelectedItem));
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$column->initializeCell($cell, 0, TListItemType::Item);
		$item->dataBind();
		$this->assertSame(TListItemType::SelectedItem, $cell->getRegisteredObject('Button')->getText());
	}

	public function testDataBindColumnDirect()
	{
		$column = new TButtonColumn();
		$column->setDataTextField('name');
		$column->setDataImageUrlField('icon');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['name' => 'gamma', 'icon' => '/g.png']);
		$cell = new TTableCell();
		$item->getCells()->add($cell);

		$link = new TLinkButton();
		$cell->getControls()->add($link);
		$column->dataBindColumn($link, null);
		$this->assertSame('gamma', $link->getText());

		$image = new TImageButton();
		$cell->getControls()->add($image);
		$column->dataBindColumn($image, null);
		$this->assertSame('gamma', $image->getText());
		$this->assertSame('/g.png', $image->getImageUrl());
	}

	public function testDataBindColumnIgnoresNonButtons()
	{
		$column = new TButtonColumn();
		$column->setDataTextField('name');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['name' => 'delta']);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$column->dataBindColumn($cell, null);
		$this->assertSame('', $cell->getText());
	}

	public function testDataBindMissingFieldThrows()
	{
		$column = new TButtonColumn();
		$column->setDataTextField('missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->bindButton($column, ['name' => 'alpha']);
	}
}
