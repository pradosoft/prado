<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TListItemCollection;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\UI\WebControls\TListItem;

class TListItemCollectionTestBadFactory extends TListItemCollection
{
	protected function createNewListItem($text = null)
	{
		return new \stdClass();
	}
}

class TListItemCollectionTest extends \PHPUnit\Framework\TestCase
{
	private function createCollection(): TListItemCollection
	{
		$items = new TListItemCollection();
		$items->add(new TListItem('Apple', 'a'));
		$items->add(new TListItem('Banana', 'b', false));
		$items->add(new TListItem('Banana', 'b2'));
		$items->add(new TListItem('Cherry', 'b'));
		return $items;
	}

	public function testCreateListItemAppends()
	{
		$items = new TListItemCollection();
		$items->add('first');
		$item = $items->createListItem();
		self::assertInstanceOf(TListItem::class, $item);
		self::assertSame(2, $items->getCount());
		self::assertSame($item, $items->itemAt(1));
	}

	public function testCreateListItemInsertsAtIndex()
	{
		$items = new TListItemCollection();
		$items->add('first');
		$items->add('second');
		$item = $items->createListItem(1);
		self::assertSame(3, $items->getCount());
		self::assertSame($item, $items->itemAt(1));
		self::assertSame('second', $items->itemAt(2)->getText());
	}

	public function testInsertStringCreatesListItem()
	{
		$items = new TListItemCollection();
		$items->insertAt(0, 'Option');
		$item = $items->itemAt(0);
		self::assertInstanceOf(TListItem::class, $item);
		self::assertSame('Option', $item->getText());
		self::assertSame('Option', $item->getValue());
	}

	public function testInsertRejectsNonListItem()
	{
		$items = new TListItemCollection();
		try {
			$items->insertAt(0, 42);
			self::fail('TInvalidDataTypeException expected');
		} catch (TInvalidDataTypeException $e) {
			self::assertSame('listitemcollection_item_invalid', $e->getErrorCode());
		}
		self::assertSame(0, $items->getCount());
	}

	public function testInsertRejectsStringWhenFactoryReturnsNonListItem()
	{
		$items = new TListItemCollectionTestBadFactory();
		try {
			$items->add('Option');
			self::fail('TInvalidDataTypeException expected');
		} catch (TInvalidDataTypeException $e) {
			self::assertSame('listitemcollection_item_invalid', $e->getErrorCode());
		}
		self::assertSame(0, $items->getCount());
	}

	public function testFindIndexByValue()
	{
		$items = $this->createCollection();
		self::assertSame(0, $items->findIndexByValue('a'));
		self::assertSame(1, $items->findIndexByValue('b'));
		self::assertSame(-1, $items->findIndexByValue('z'));
	}

	public function testFindIndexByValueSkipsDisabledItems()
	{
		$items = $this->createCollection();
		self::assertSame(3, $items->findIndexByValue('b', false));
		self::assertSame(0, $items->findIndexByValue('a', false));
	}

	public function testFindIndexByValueExcludingDisabledFindsNothing()
	{
		$items = new TListItemCollection();
		$items->add(new TListItem('Only', 'x', false));
		self::assertSame(-1, $items->findIndexByValue('x', false));
		self::assertSame(0, $items->findIndexByValue('x'));
	}

	public function testFindIndexByValueOnEmptyCollection()
	{
		$items = new TListItemCollection();
		self::assertSame(-1, $items->findIndexByValue('a'));
	}

	public function testFindIndexByText()
	{
		$items = $this->createCollection();
		self::assertSame(0, $items->findIndexByText('Apple'));
		self::assertSame(1, $items->findIndexByText('Banana'));
		self::assertSame(-1, $items->findIndexByText('Durian'));
	}

	public function testFindIndexByTextSkipsDisabledItems()
	{
		$items = $this->createCollection();
		self::assertSame(2, $items->findIndexByText('Banana', false));
		self::assertSame(0, $items->findIndexByText('Apple', false));
	}

	public function testFindIndexByTextExcludingDisabledFindsNothing()
	{
		$items = new TListItemCollection();
		$items->add(new TListItem('Only', 'x', false));
		self::assertSame(-1, $items->findIndexByText('Only', false));
		self::assertSame(0, $items->findIndexByText('Only'));
	}

	public function testFindIndexByTextOnEmptyCollection()
	{
		$items = new TListItemCollection();
		self::assertSame(-1, $items->findIndexByText('Apple'));
	}

	public function testFindIndexByTextConvertsToString()
	{
		$items = new TListItemCollection();
		$items->add('1');
		self::assertSame(0, $items->findIndexByText(1));
	}

	public function testFindItemByValue()
	{
		$items = $this->createCollection();
		self::assertSame($items->itemAt(3), $items->findItemByValue('b', false));
		self::assertNull($items->findItemByValue('z'));
	}

	public function testFindItemByText()
	{
		$items = $this->createCollection();
		self::assertSame($items->itemAt(1), $items->findItemByText('Banana'));
		self::assertSame($items->itemAt(2), $items->findItemByText('Banana', false));
		self::assertNull($items->findItemByText('Durian'));
	}

	public function testSaveAndLoadState()
	{
		$items = new TListItemCollection();
		self::assertNull($items->saveState());
		$items->add('one');
		$items->add('two');
		$state = $items->saveState();
		self::assertCount(2, $state);

		$restored = new TListItemCollection();
		$restored->add('stale');
		$restored->loadState($state);
		self::assertSame(2, $restored->getCount());
		self::assertSame('one', $restored->itemAt(0)->getText());
		self::assertSame('two', $restored->itemAt(1)->getText());

		$restored->loadState(null);
		self::assertSame(0, $restored->getCount());
	}
}
