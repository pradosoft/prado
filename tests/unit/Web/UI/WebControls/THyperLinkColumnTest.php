<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TDataGridColumn;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\THyperLink;
use Prado\Web\UI\WebControls\THyperLinkColumn;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TTableCell;

class THyperLinkColumnTest extends TestCase
{
	/**
	 * Creates a data grid item holding one cell initialized by the column.
	 * @param THyperLinkColumn $column
	 * @param mixed $data
	 * @param mixed $itemType
	 * @return TTableCell the initialized cell
	 */
	private function createCell(THyperLinkColumn $column, $data, $itemType = TListItemType::Item)
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
	 * @param THyperLinkColumn $column
	 * @param mixed $data
	 * @param mixed $itemType
	 * @return THyperLink the hyperlink of the cell
	 */
	private function bindLink(THyperLinkColumn $column, $data, $itemType = TListItemType::Item)
	{
		$cell = $this->createCell($column, $data, $itemType);
		$cell->getParent()->dataBind();
		return $cell->getRegisteredObject('HyperLink');
	}

	public function testConstruct()
	{
		$column = new THyperLinkColumn();
		$this->assertInstanceOf(TDataGridColumn::class, $column);
	}

	public function testDefaults()
	{
		$column = new THyperLinkColumn();
		$this->assertSame('', $column->getText());
		$this->assertSame('', $column->getDataTextField());
		$this->assertSame('', $column->getDataTextFormatString());
		$this->assertSame('', $column->getNavigateUrl());
		$this->assertSame('', $column->getDataNavigateUrlField());
		$this->assertSame('', $column->getDataNavigateUrlFormatString());
		$this->assertSame('', $column->getTarget());
		$this->assertSame('', $column->getImageUrl());
		$this->assertSame('', $column->getImageWidth());
		$this->assertSame('', $column->getImageHeight());
		$this->assertSame([], $column->saveState());
	}

	public function testStringProperties()
	{
		$column = new THyperLinkColumn();
		$properties = [
			'Text' => 'View',
			'DataTextField' => 'name',
			'DataTextFormatString' => 'Name: %s',
			'NavigateUrl' => '/view',
			'DataNavigateUrlField' => 'id',
			'DataNavigateUrlFormatString' => '/item/%s',
			'Target' => '_blank',
			'ImageUrl' => '/img/view.png',
			'ImageWidth' => '16px',
			'ImageHeight' => '12px',
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

	public function testCallableFormatStrings()
	{
		$column = new THyperLinkColumn();
		$text = fn ($value) => strtoupper($value);
		$url = fn ($value) => "/u/$value";
		$column->setDataTextFormatString($text);
		$column->setDataNavigateUrlFormatString($url);
		$this->assertSame($text, $column->getDataTextFormatString());
		$this->assertSame($url, $column->getDataNavigateUrlFormatString());
		$this->assertSame([], $column->saveState());
	}

	public function testInitializeCellItemTypes()
	{
		$column = new THyperLinkColumn();
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::SelectedItem, TListItemType::EditItem] as $itemType) {
			$cell = $this->createCell($column, [], $itemType);
			$this->assertSame(1, $cell->getControls()->getCount(), $itemType);
			$link = $cell->getControls()->itemAt(0);
			$this->assertInstanceOf(THyperLink::class, $link, $itemType);
			$this->assertSame($link, $cell->getRegisteredObject('HyperLink'));
		}
	}

	public function testInitializeCellCopiesProperties()
	{
		$column = new THyperLinkColumn();
		$column->setText('Open');
		$column->setNavigateUrl('/open');
		$column->setTarget('_top');
		$link = $this->createCell($column, [])->getRegisteredObject('HyperLink');
		$this->assertSame('Open', $link->getText());
		$this->assertSame('/open', $link->getNavigateUrl());
		$this->assertSame('_top', $link->getTarget());
		$this->assertSame('', $link->getImageUrl());
		$this->assertSame('', $link->getImageWidth());
		$this->assertSame('', $link->getImageHeight());
	}

	public function testInitializeCellImage()
	{
		$column = new THyperLinkColumn();
		$column->setImageWidth('20px');
		$column->setImageHeight('10px');
		$link = $this->createCell($column, [])->getRegisteredObject('HyperLink');
		$this->assertSame('', $link->getImageWidth(), 'image size requires ImageUrl');
		$this->assertSame('', $link->getImageHeight(), 'image size requires ImageUrl');

		$column->setImageUrl('/img/a.png');
		$link = $this->createCell($column, [])->getRegisteredObject('HyperLink');
		$this->assertSame('/img/a.png', $link->getImageUrl());
		$this->assertSame('20px', $link->getImageWidth());
		$this->assertSame('10px', $link->getImageHeight());

		$column->setImageWidth('');
		$column->setImageHeight('');
		$link = $this->createCell($column, [])->getRegisteredObject('HyperLink');
		$this->assertSame('/img/a.png', $link->getImageUrl());
		$this->assertSame('', $link->getImageWidth());
		$this->assertSame('', $link->getImageHeight());
	}

	public function testInitializeCellAttachesDataBindingOnlyWithFields()
	{
		$column = new THyperLinkColumn();
		$this->assertFalse($this->createCell($column, [])->getRegisteredObject('HyperLink')->hasEventHandler('OnDataBinding'));

		$column->setDataTextField('name');
		$this->assertTrue($this->createCell($column, [])->getRegisteredObject('HyperLink')->hasEventHandler('OnDataBinding'));

		$column = new THyperLinkColumn();
		$column->setDataNavigateUrlField('id');
		$this->assertTrue($this->createCell($column, [])->getRegisteredObject('HyperLink')->hasEventHandler('OnDataBinding'));
	}

	public function testInitializeCellHeaderFooterAndOthers()
	{
		$column = new THyperLinkColumn();
		$column->setHeaderText('Links');
		$column->setFooterText('End');
		$column->setText('Go');

		$cell = $this->createCell($column, [], TListItemType::Header);
		$this->assertSame('Links', $cell->getText());
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

	public function testDataBindTextAndUrl()
	{
		$column = new THyperLinkColumn();
		$column->setText('static');
		$column->setNavigateUrl('/static');
		$column->setDataTextField('name');
		$link = $this->bindLink($column, ['name' => 'alpha', 'id' => 5]);
		$this->assertSame('alpha', $link->getText());
		$this->assertSame('/static', $link->getNavigateUrl());

		$column = new THyperLinkColumn();
		$column->setText('static');
		$column->setDataNavigateUrlField('id');
		$link = $this->bindLink($column, ['name' => 'alpha', 'id' => 5]);
		$this->assertSame('static', $link->getText());
		$this->assertSame('5', $link->getNavigateUrl());

		$column->setDataTextField('name');
		$link = $this->bindLink($column, ['name' => 'beta', 'id' => 6], TListItemType::EditItem);
		$this->assertSame('beta', $link->getText());
		$this->assertSame('6', $link->getNavigateUrl());
	}

	public function testDataBindStringFormats()
	{
		$column = new THyperLinkColumn();
		$column->setDataTextField('name');
		$column->setDataNavigateUrlField('id');
		$column->setDataTextFormatString('View %s');
		$column->setDataNavigateUrlFormatString('/item.php?id=%d');
		$link = $this->bindLink($column, ['name' => 'alpha', 'id' => '42']);
		$this->assertSame('View alpha', $link->getText());
		$this->assertSame('/item.php?id=42', $link->getNavigateUrl());
	}

	public function testDataBindExpressionFormats()
	{
		$column = new THyperLinkColumn();
		$column->setDataTextField('name');
		$column->setDataNavigateUrlField('id');
		$column->setDataTextFormatString('#ucfirst({0})');
		$column->setDataNavigateUrlFormatString('#"/item/" . ({0} + 1)');
		$link = $this->bindLink($column, ['name' => 'alpha', 'id' => 41]);
		$this->assertSame('Alpha', $link->getText());
		$this->assertSame('/item/42', $link->getNavigateUrl());
	}

	public function testDataBindCallableFormats()
	{
		$column = new THyperLinkColumn();
		$column->setDataTextField('name');
		$column->setDataNavigateUrlField('id');
		$column->setDataTextFormatString(fn ($value) => strrev($value));
		$column->setDataNavigateUrlFormatString(fn ($value) => '/c/' . ($value * 2));
		$link = $this->bindLink($column, ['name' => 'abc', 'id' => 21]);
		$this->assertSame('cba', $link->getText());
		$this->assertSame('/c/42', $link->getNavigateUrl());
	}

	public function testDataBindInvalidExpressionThrows()
	{
		$column = new THyperLinkColumn();
		$column->setDataTextField('name');
		$column->setDataTextFormatString('#{0} +');
		try {
			$this->bindLink($column, ['name' => 'alpha']);
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('datagridcolumn_expression_invalid', $e->getErrorCode());
		}
	}

	public function testDataBindColumnDirect()
	{
		$column = new THyperLinkColumn();
		$column->setDataTextField('name');
		$column->setDataNavigateUrlField('id');
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$item->setData(['name' => 'gamma', 'id' => 'g']);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$link = new THyperLink();
		$cell->getControls()->add($link);
		$column->dataBindColumn($link, null);
		$this->assertSame('gamma', $link->getText());
		$this->assertSame('g', $link->getNavigateUrl());
	}

	public function testDataBindMissingFieldThrows()
	{
		$column = new THyperLinkColumn();
		$column->setDataNavigateUrlField('missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->bindLink($column, ['id' => 1]);
	}
}
