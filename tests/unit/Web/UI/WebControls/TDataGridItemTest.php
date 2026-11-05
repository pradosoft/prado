<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TList;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TBoundColumn;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridColumnCollection;
use Prado\Web\UI\WebControls\TDataGridCommandEventParameter;
use Prado\Web\UI\WebControls\TDataGridItem;
use Prado\Web\UI\WebControls\TDataGridItemCollection;
use Prado\Web\UI\WebControls\TDataGridItemEventParameter;
use Prado\Web\UI\WebControls\TDataGridPageChangedEventParameter;
use Prado\Web\UI\WebControls\TDataGridPager;
use Prado\Web\UI\WebControls\TDataGridPagerEventParameter;
use Prado\Web\UI\WebControls\TDataGridSortCommandEventParameter;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableRow;
use Prado\Web\UI\WebControls\TTableRowSection;

/**
 * Control that records the events bubbled up to it.
 */
class TDataGridItemTestContainer extends TControl
{
	private array $_bubbled = [];

	public function getBubbled(): array
	{
		return $this->_bubbled;
	}

	public function bubbleEvent($sender, $param)
	{
		$this->_bubbled[] = [$sender, $param];
		return true;
	}
}

class TDataGridItemTest extends TestCase
{
	// ================================================================================
	// TDataGridItem
	// ================================================================================

	public function testItemConstruction()
	{
		$item = new TDataGridItem(3, 7, TListItemType::AlternatingItem);
		$this->assertInstanceOf(TTableRow::class, $item);
		$this->assertInstanceOf(INamingContainer::class, $item);
		$this->assertSame(3, $item->getItemIndex());
		$this->assertSame(7, $item->getDataSourceIndex());
		$this->assertSame(TListItemType::AlternatingItem, $item->getItemType());
		$this->assertSame(TTableRowSection::Body, $item->getTableSection());
		$this->assertNull($item->getData());
	}

	public function testHeaderAndFooterTableSection()
	{
		$this->assertSame(TTableRowSection::Header, (new TDataGridItem(-1, -1, TListItemType::Header))->getTableSection());
		$this->assertSame(TTableRowSection::Footer, (new TDataGridItem(-1, -1, TListItemType::Footer))->getTableSection());
		$this->assertSame(TTableRowSection::Body, (new TDataGridItem(0, 0, TListItemType::EditItem))->getTableSection());
	}

	public function testItemType()
	{
		$item = new TDataGridItem(0, 0, 'item');
		$this->assertSame(TListItemType::Item, $item->getItemType());
		$item->setItemType('selecteditem');
		$this->assertSame(TListItemType::SelectedItem, $item->getItemType());
		$item->setItemType(TListItemType::EditItem);
		$this->assertSame(TListItemType::EditItem, $item->getItemType());
	}

	public function testItemTypeInvalid()
	{
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$this->expectException(TInvalidDataValueException::class);
		$item->setItemType('Unknown');
	}

	public function testData()
	{
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$data = ['name' => 'value'];
		$item->setData($data);
		$this->assertSame($data, $item->getData());
		$item->setData(null);
		$this->assertNull($item->getData());
	}

	public function testBubbleEventWrapsCommand()
	{
		$container = new TDataGridItemTestContainer();
		$item = new TDataGridItem(2, 2, TListItemType::Item);
		$container->getControls()->add($item);
		$button = new TLinkButton();

		$this->assertTrue($item->bubbleEvent($button, new TCommandEventParameter('Edit', 'arg')));
		$bubbled = $container->getBubbled();
		$this->assertCount(1, $bubbled);
		[$sender, $param] = $bubbled[0];
		$this->assertSame($item, $sender);
		$this->assertInstanceOf(TDataGridCommandEventParameter::class, $param);
		$this->assertSame($item, $param->getItem());
		$this->assertSame($button, $param->getCommandSource());
		$this->assertSame('Edit', $param->getCommandName());
		$this->assertSame('arg', $param->getCommandParameter());
	}

	public function testBubbleEventIgnoresOtherParameters()
	{
		$container = new TDataGridItemTestContainer();
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$container->getControls()->add($item);
		$this->assertFalse($item->bubbleEvent($item, new TEventParameter()));
		$this->assertSame([], $container->getBubbled());
	}

	public function testBubbleEventFromChildButton()
	{
		$container = new TDataGridItemTestContainer();
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$container->getControls()->add($item);
		$cell = new TTableCell();
		$item->getCells()->add($cell);
		$button = new TLinkButton();
		$cell->getControls()->add($button);
		$button->onCommand(new TCommandEventParameter('Delete', '5'));
		$bubbled = $container->getBubbled();
		$this->assertCount(1, $bubbled);
		$this->assertSame($button, $bubbled[0][1]->getCommandSource());
		$this->assertSame('Delete', $bubbled[0][1]->getCommandName());
	}

	// ================================================================================
	// TDataGridItemCollection
	// ================================================================================

	public function testItemCollectionAcceptsItems()
	{
		$collection = new TDataGridItemCollection();
		$this->assertInstanceOf(TList::class, $collection);
		$first = new TDataGridItem(0, 0, TListItemType::Item);
		$second = new TDataGridItem(1, 1, TListItemType::AlternatingItem);
		$collection->add($second);
		$collection->insertAt(0, $first);
		$collection[] = new TDataGridItem(2, 2, TListItemType::Item);
		$this->assertSame(3, $collection->getCount());
		$this->assertSame($first, $collection->itemAt(0));
		$this->assertSame($second, $collection->itemAt(1));
	}

	public function testItemCollectionRejectsOtherObjects()
	{
		$collection = new TDataGridItemCollection();
		foreach ([new TTableRow(), 'item', null] as $value) {
			try {
				$collection->add($value);
				$this->fail('Expected TInvalidDataTypeException');
			} catch (TInvalidDataTypeException $e) {
				$this->assertSame('datagriditemcollection_datagriditem_required', $e->getErrorCode());
			}
		}
		$this->assertSame(0, $collection->getCount());
	}

	// ================================================================================
	// TDataGridColumnCollection
	// ================================================================================

	public function testColumnCollectionSetsOwner()
	{
		$grid = new TDataGrid();
		$collection = new TDataGridColumnCollection($grid);
		$this->assertInstanceOf(TList::class, $collection);
		$this->assertSame($grid, PradoUnit::invoke($collection, 'getOwner'));

		$first = new TBoundColumn();
		$second = new TBoundColumn();
		$collection->add($second);
		$collection->insertAt(0, $first);
		$this->assertSame(2, $collection->getCount());
		$this->assertSame($first, $collection->itemAt(0));
		$this->assertSame($second, $collection->itemAt(1));
		$this->assertSame($grid, $first->getOwner());
		$this->assertSame($grid, $second->getOwner());
	}

	public function testColumnCollectionRejectsOtherObjects()
	{
		$collection = new TDataGridColumnCollection(new TDataGrid());
		foreach ([new TPanel(), 'column', null] as $value) {
			try {
				$collection->add($value);
				$this->fail('Expected TInvalidDataTypeException');
			} catch (TInvalidDataTypeException $e) {
				$this->assertSame('datagridcolumncollection_datagridcolumn_required', $e->getErrorCode());
			}
		}
		$this->assertSame(0, $collection->getCount());
	}

	public function testColumnCollectionRequiresDataGridOwner()
	{
		$this->expectException(\TypeError::class);
		new TDataGridColumnCollection(new TPanel());
	}

	// ================================================================================
	// Event parameters
	// ================================================================================

	public function testCommandEventParameter()
	{
		$item = new TDataGridItem(0, 0, TListItemType::Item);
		$source = new TLinkButton();
		$param = new TDataGridCommandEventParameter($item, $source, new TCommandEventParameter('Select', ['id' => 4]));
		$this->assertInstanceOf(TCommandEventParameter::class, $param);
		$this->assertSame($item, $param->getItem());
		$this->assertSame($source, $param->getCommandSource());
		$this->assertSame('Select', $param->getCommandName());
		$this->assertSame(['id' => 4], $param->getCommandParameter());
	}

	public function testCommandEventParameterRequiresCommandParameter()
	{
		$this->expectException(\TypeError::class);
		new TDataGridCommandEventParameter(null, null, new TEventParameter());
	}

	public function testItemEventParameter()
	{
		$item = new TDataGridItem(0, 0, TListItemType::Header);
		$param = new TDataGridItemEventParameter($item);
		$this->assertInstanceOf(TEventParameter::class, $param);
		$this->assertSame($item, $param->getItem());
	}

	public function testItemEventParameterRequiresItem()
	{
		$this->expectException(\TypeError::class);
		new TDataGridItemEventParameter(new TTableRow());
	}

	public function testPageChangedEventParameter()
	{
		$source = new TLinkButton();
		$param = new TDataGridPageChangedEventParameter($source, 4);
		$this->assertInstanceOf(TEventParameter::class, $param);
		$this->assertSame($source, $param->getCommandSource());
		$this->assertSame(4, $param->getNewPageIndex());
	}

	public function testSortCommandEventParameter()
	{
		$item = new TDataGridItem(-1, -1, TListItemType::Header);
		$command = new TDataGridCommandEventParameter($item, new TLinkButton(), new TCommandEventParameter('Sort', 'price'));
		$param = new TDataGridSortCommandEventParameter($item, $command);
		$this->assertInstanceOf(TEventParameter::class, $param);
		$this->assertSame($item, $param->getCommandSource());
		$this->assertSame('price', $param->getSortExpression());
	}

	public function testSortCommandEventParameterRequiresGridCommand()
	{
		$this->expectException(\TypeError::class);
		new TDataGridSortCommandEventParameter(null, new TCommandEventParameter('Sort', 'price'));
	}

	public function testPagerEventParameter()
	{
		$pager = new TDataGridPager(new TDataGrid());
		$param = new TDataGridPagerEventParameter($pager);
		$this->assertInstanceOf(TEventParameter::class, $param);
		$this->assertSame($pager, $param->getPager());
	}

	public function testPagerEventParameterRequiresPager()
	{
		$this->expectException(\TypeError::class);
		new TDataGridPagerEventParameter(new TPanel());
	}
}
