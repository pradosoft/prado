<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TPagedList;
use Prado\Collections\TPagedListFetchDataEventParameter;
use Prado\Collections\TPagedListPageChangedEventParameter;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidDataTypeException;

class MyPagedList extends TPagedList
{
	private $_isPageIndexChanged = false;
	private $_hasFetchedData = false;

	public function pageIndexChanged($sender, $param)
	{
		$this->_isPageIndexChanged = true;
	}

	public function fetchData($sender, $param)
	{
		$this->_hasFetchedData = true;
	}

	public function isPageIndexChanged()
	{
		return $this->_isPageIndexChanged;
	}

	public function hasFetchedData()
	{
		return $this->_hasFetchedData;
	}
}

class TPagedListTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$list = new TPagedList();
		self::assertEquals(true, $list->ReadOnly);
		self::assertEquals(-1, $list->VirtualCount);
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 3;
		self::assertEquals(3, $list->Count);
	}

	public function testCustomPaging()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		self::assertEquals(true, $list->CustomPaging);
		$list->CustomPaging = false;
		self::assertEquals(false, $list->CustomPaging);
	}

	public function testPageSize()
	{
		$list = new TPagedList();
		$list->PageSize = 5;
		self::assertEquals(5, $list->PageSize);
	}

	public function testCanNotSetInvalidPageSize()
	{
		$list = new TPagedList();
		self::expectException(TInvalidDataValueException::class);
		$list->PageSize = 0;
	}

	public function testCurrentPageIndex()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		$list->CurrentPageIndex = 2;
		self::assertEquals(2, $list->CurrentPageIndex);
	}

	public function testOnPageIndexChanged()
	{
		$list = new TPagedList([1, 2, 3, 4, 5]);
		$list->PageSize = 1;
		$list->CurrentPageIndex = 1;
		$oldPage = $list->CurrentPageIndex;
		$myList = new MyPagedList();
		$list->attachEventHandler('OnPageIndexChanged', [$myList, 'pageIndexChanged']);
		self::assertEquals(false, $myList->isPageIndexChanged());
		$list->onPageIndexChanged(new TPagedListPageChangedEventParameter($oldPage));
		self::assertEquals(true, $myList->isPageIndexChanged());
	}

	public function testOnFetchData()
	{
		$list = new TPagedList([1, 2, 3, 4]);
		$list->CustomPaging = true;
		$list->PageSize = 2;
		$list->gotoPage(0);
		$myList = new MyPagedList();
		$list->attachEventHandler('OnFetchData', [$myList, 'fetchData']);
		self::assertEquals(false, $myList->hasFetchedData());
		$list->onFetchData(new TPagedListFetchDataEventParameter($list->CurrentPageIndex, $list->PageSize * $list->CurrentPageIndex, $list->PageSize));
		self::assertEquals(true, $myList->hasFetchedData());
	}

	public function testGotoPage()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		self::assertEquals(2, $list->gotoPage(2));
		self::assertEquals(false, $list->gotoPage(4));
	}

	public function testNextPage()
	{
		$list = new TPagedList([1, 2]);
		$list->PageSize = 1;
		$list->gotoPage(0);
		self::assertEquals(1, $list->nextPage());
		self::assertEquals(false, $list->nextPage());
	}

	public function testPreviousPage()
	{
		$list = new TPagedList([1, 2]);
		$list->PageSize = 1;
		$list->gotoPage(1);
		self::assertEquals(0, $list->previousPage());
		self::assertEquals(false, $list->previousPage());
	}

	public function testVirtualCount()
	{
		$list = new TPagedList([1, 2]);
		$list->VirtualCount = -10;
		self::assertEquals(-1, $list->VirtualCount);
		$list->VirtualCount = 5;
		self::assertEquals(5, $list->VirtualCount);
	}

	public function testPageCount()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		$list->CustomPaging = true;
		self::assertEquals(-1, $list->PageCount);
		$list->VirtualCount = 3;
		self::assertEquals(3, $list->PageCount);
		$list->CustomPaging = false;
		self::assertEquals(3, $list->PageCount);
	}

	public function testIsFirstPage()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		$list->gotoPage(0);
		self::assertEquals(true, $list->IsFirstPage);
		$list->gotoPage(1);
		self::assertEquals(false, $list->IsFirstPage);
	}

	public function testIsLastPage()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		$list->gotoPage(0);
		self::assertEquals(false, $list->IsLastPage);
		$list->gotoPage(2);
		self::assertEquals(true, $list->IsLastPage);
	}

	public function testGetCount()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		self::assertEquals(1, $list->Count);
		$list->CustomPaging = true;
		self::assertEquals(3, $list->Count);
	}

	public function testGetIterator()
	{
		$list = new TPagedList([1, 2]);
		$list->CustomPaging = true;
		self::assertInstanceOf('ArrayIterator', $list->getIterator());
		$n = 0;
		$found = 0;
		foreach ($list as $index => $item) {
			foreach ($list as $a => $b); // test of iterator
			$n++;
			if ($index === 0 && $item === 1) {
				$found++;
			}
			if ($index === 1 && $item === 2) {
				$found++;
			}
		}
		self::assertTrue($n == 2 && $found == 2);
	}

	public function testItemAt()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->CustomPaging = true;
		self::assertEquals(1, $list[0]);
		$list->CustomPaging = false;
		$list->PageSize = 1;
		$list->CurrentPageIndex = 0;
		self::assertEquals(1, $list[0]);
	}

	public function testIndexOf()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->CustomPaging = true;
		self::assertEquals(0, $list->indexOf(1));
		self::assertEquals(-1, $list->indexOf(0));
	}

	public function testOffsetExists()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->CustomPaging = true;
		self::assertEquals(true, isset($list[0]));
		self::assertEquals(false, isset($list[4]));
	}

	public function testOffsetGet()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->CustomPaging = true;
		self::assertEquals(2, $list[1]);
	}

	public function testToArray()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->CustomPaging = true;
		self::assertEquals([1, 2, 3], $list->toArray());
	}

	public function testNewManagedListShorterThanPageSizeIsPageZero()
	{
		$list = new TPagedList([1, 2]);
		self::assertSame(0, $list->getCurrentPageIndex());
		self::assertSame(1, $list->getPageCount());
		self::assertSame(2, $list->getCount());
		self::assertSame(2, count($list));
		self::assertSame([1, 2], $list->toArray());
		self::assertSame(1, $list->itemAt(0));
		self::assertSame(2, $list[1]);
		self::assertTrue(isset($list[1]));
		self::assertFalse(isset($list[2]));
		self::assertSame(1, $list->indexOf(2));
		self::assertTrue($list->getIsFirstPage());
		self::assertTrue($list->getIsLastPage());
		$items = [];
		foreach ($list as $index => $item) {
			$items[$index] = $item;
		}
		self::assertSame([1, 2], $items);
	}

	public function testNewManagedEmptyListIsPageZero()
	{
		$list = new TPagedList();
		self::assertSame(0, $list->getCurrentPageIndex());
		self::assertSame(0, $list->getPageCount());
		self::assertSame(0, $list->getCount());
		self::assertSame([], $list->toArray());
		self::assertTrue($list->getIsFirstPage());
		self::assertTrue($list->getIsLastPage());
		self::assertFalse($list->nextPage());
		self::assertFalse($list->previousPage());
	}

	public function testNewManagedListSpanningPagesIsPageZero()
	{
		$list = new TPagedList([1, 2, 3, 4, 5]);
		$list->PageSize = 2;
		self::assertSame(0, $list->getCurrentPageIndex());
		self::assertSame(3, $list->getPageCount());
		self::assertSame(2, $list->getCount());
		self::assertSame([1, 2], $list->toArray());
		self::assertSame(2, $list->itemAt(1));
		self::assertTrue($list->getIsFirstPage());
		self::assertFalse($list->getIsLastPage());
		self::assertFalse($list->previousPage());

		self::assertSame(1, $list->nextPage());
		self::assertSame([3, 4], $list->toArray());
		self::assertSame(2, $list->nextPage());
		self::assertSame(1, $list->getCount());
		self::assertSame([5], $list->toArray());
		self::assertSame(5, $list->itemAt(0));
		self::assertTrue($list->getIsLastPage());
	}

	public function testManagedGotoCurrentPageRaisesNoEvent()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		$raised = 0;
		$list->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$raised) {
			$raised++;
		});
		self::assertSame(0, $list->gotoPage(0));
		self::assertSame(0, $raised);
	}

	public function testManagedGotoPageEventParameterCarriesOldPage()
	{
		$list = new TPagedList([1, 2, 3, 4, 5]);
		$list->PageSize = 2;
		$params = [];
		$list->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$params, $list) {
			self::assertSame($list, $sender);
			$params[] = $param;
		});

		self::assertSame(2, $list->gotoPage(2));
		self::assertSame(1, $list->previousPage());
		$list->CurrentPageIndex = 0;

		self::assertCount(3, $params);
		foreach ($params as $param) {
			self::assertInstanceOf(TPagedListPageChangedEventParameter::class, $param);
		}
		self::assertSame(0, $params[0]->getOldPageIndex());
		self::assertSame(2, $params[1]->getOldPageIndex());
		self::assertSame(1, $params[2]->getOldPageIndex());
	}

	public function testCustomPagingFirstGotoPageZeroFetches()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		$list->PageSize = 2;
		self::assertSame(-1, $list->getCurrentPageIndex());
		self::assertFalse($list->getIsFirstPage());

		$fetches = [];
		$list->attachEventHandler('OnFetchData', function ($sender, $param) use (&$fetches) {
			$fetches[] = [$param->getNewPageIndex(), $param->getOffset(), $param->getLimit()];
			$param->setData(['a' . $param->getOffset(), 'b' . $param->getOffset()]);
		});
		$oldPages = [];
		$list->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$oldPages) {
			$oldPages[] = $param->getOldPageIndex();
		});

		self::assertSame(0, $list->gotoPage(0));
		self::assertSame([[0, 0, 2]], $fetches);
		self::assertSame([-1], $oldPages);
		self::assertSame(0, $list->getCurrentPageIndex());
		self::assertSame(['a0', 'b0'], $list->toArray());
		self::assertTrue($list->getIsFirstPage());

		self::assertSame(0, $list->gotoPage(0));
		self::assertCount(1, $fetches);

		self::assertSame(1, $list->nextPage());
		self::assertSame([[0, 0, 2], [1, 2, 2]], $fetches);
		self::assertSame([-1, 0], $oldPages);
		self::assertSame(['a2', 'b2'], $list->toArray());
	}

	public function testCustomPagingNextPageFromNewListFetchesPageZero()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		$pages = [];
		$list->attachEventHandler('OnFetchData', function ($sender, $param) use (&$pages) {
			$pages[] = $param->getNewPageIndex();
			$param->setData([1]);
		});
		self::assertSame(0, $list->nextPage());
		self::assertSame([0], $pages);
	}

	public function testCustomPagingEnabledAfterConstructionStillFetchesPageZero()
	{
		$list = new TPagedList([1, 2, 3]);
		self::assertSame(0, $list->getCurrentPageIndex());
		$list->CustomPaging = true;
		self::assertSame(-1, $list->getCurrentPageIndex());
		$fetched = 0;
		$list->attachEventHandler('OnFetchData', function ($sender, $param) use (&$fetched) {
			$fetched++;
			$param->setData([7, 8]);
		});
		self::assertSame(0, $list->gotoPage(0));
		self::assertSame(1, $fetched);
		self::assertSame([7, 8], $list->toArray());
		$list->CustomPaging = false;
		self::assertSame(0, $list->getCurrentPageIndex());
		self::assertSame([7, 8], $list->toArray());
	}

	public function testSetCurrentPageIndexOutOfRangeThrows()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 2;
		$list->CurrentPageIndex = 1;
		try {
			$list->CurrentPageIndex = 2;
			self::fail('TInvalidDataValueException expected');
		} catch (TInvalidDataValueException $e) {
			self::assertSame('pagedlist_currentpageindex_invalid', $e->getErrorCode());
		}
		self::assertSame(1, $list->CurrentPageIndex);
	}

	public function testGotoCurrentPageReturnsIndexWithoutEvent()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		$list->gotoPage(1);
		$raised = 0;
		$list->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$raised) {
			$raised++;
		});
		self::assertSame(1, $list->gotoPage(1));
		self::assertSame(0, $raised);
	}

	public function testManagedGotoPageRaisesPageIndexChanged()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 2;
		$senders = [];
		$list->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$senders) {
			$senders[] = $sender;
		});
		self::assertSame(1, $list->gotoPage(1));
		self::assertSame([$list], $senders);
		self::assertSame(1, $list->CurrentPageIndex);
	}

	public function testManagedGotoNegativePageFails()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 1;
		$list->gotoPage(1);
		self::assertFalse($list->gotoPage(-1));
		self::assertSame(1, $list->CurrentPageIndex);
	}

	public function testCustomGotoPageFetchesDataWithoutVirtualCount()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		$list->PageSize = 2;
		$fetch = null;
		$changed = null;
		$list->attachEventHandler('OnFetchData', function ($sender, $param) use (&$fetch) {
			$fetch = $param;
			$param->Data = ['e', 'f'];
		});
		$list->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$changed) {
			$changed = $param;
		});

		self::assertSame(2, $list->gotoPage(2));

		self::assertInstanceOf(TPagedListFetchDataEventParameter::class, $fetch);
		self::assertSame(2, $fetch->getNewPageIndex());
		self::assertSame(4, $fetch->getOffset());
		self::assertSame(2, $fetch->getLimit());
		self::assertInstanceOf(TPagedListPageChangedEventParameter::class, $changed);
		self::assertSame(-1, $changed->getOldPageIndex());
		self::assertSame(2, $list->CurrentPageIndex);
		self::assertSame(['e', 'f'], $list->toArray());
		self::assertTrue($list->ReadOnly);
	}

	public function testCustomGotoPageWithinVirtualCountReplacesData()
	{
		$list = new TPagedList(['a', 'b']);
		$list->CustomPaging = true;
		$list->PageSize = 2;
		$list->VirtualCount = 5;
		$list->attachEventHandler('OnFetchData', function ($sender, $param) {
			$param->Data = array_slice(['a', 'b', 'c', 'd', 'e'], $param->Offset, $param->Limit);
		});
		$oldPages = [];
		$list->attachEventHandler('OnPageIndexChanged', function ($sender, $param) use (&$oldPages) {
			$oldPages[] = $param->getOldPageIndex();
		});

		self::assertSame(0, $list->gotoPage(0));
		self::assertSame(2, $list->gotoPage(2));
		self::assertSame([-1, 0], $oldPages);
		self::assertSame(['e'], $list->toArray());
		self::assertSame(1, $list->Count);
	}

	public function testCustomGotoPageFailsWhenNoDataSupplied()
	{
		$list = new TPagedList(['a', 'b']);
		$list->CustomPaging = true;
		$list->PageSize = 2;
		$fetched = 0;
		$list->attachEventHandler('OnFetchData', function ($sender, $param) use (&$fetched) {
			$fetched++;
		});
		self::assertFalse($list->gotoPage(1));
		self::assertSame(1, $fetched);
		self::assertSame(-1, $list->CurrentPageIndex);
		self::assertSame(['a', 'b'], $list->toArray());
	}

	public function testCustomGotoPageWithinVirtualCountFailsWhenNoDataSupplied()
	{
		$list = new TPagedList(['a', 'b']);
		$list->CustomPaging = true;
		$list->PageSize = 2;
		$list->VirtualCount = 4;
		$fetched = [];
		$list->attachEventHandler('OnFetchData', function ($sender, $param) use (&$fetched) {
			$fetched[] = $param->NewPageIndex;
		});
		self::assertFalse($list->gotoPage(1));
		self::assertSame([1], $fetched);
		self::assertSame(-1, $list->CurrentPageIndex);
		self::assertSame(['a', 'b'], $list->toArray());
	}

	public function testCustomGotoPageBeyondVirtualCountFailsWithoutFetch()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		$list->PageSize = 2;
		$list->VirtualCount = 4;
		$fetched = 0;
		$list->attachEventHandler('OnFetchData', function ($sender, $param) use (&$fetched) {
			$fetched++;
			$param->Data = ['x'];
		});
		self::assertFalse($list->gotoPage(2));
		self::assertFalse($list->gotoPage(-2));
		self::assertSame(0, $fetched);
		self::assertSame(-1, $list->CurrentPageIndex);
	}

	public function testCustomGotoPageRejectsNonIterableData()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		$list->attachEventHandler('OnFetchData', function ($sender, $param) {
			$param->Data = 'scalar';
		});
		self::expectException(TInvalidDataTypeException::class);
		$list->gotoPage(0);
	}

	public function testCustomPagingIsLastPageFollowsVirtualCount()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		$list->PageSize = 2;
		$list->VirtualCount = 5;
		$list->attachEventHandler('OnFetchData', function ($sender, $param) {
			$param->Data = array_slice(['a', 'b', 'c', 'd', 'e'], $param->Offset, $param->Limit);
		});

		self::assertFalse($list->getIsLastPage());
		$list->gotoPage(1);
		self::assertFalse($list->getIsLastPage());
		$list->gotoPage(2);
		self::assertTrue($list->getIsLastPage());
		self::assertSame(['e'], $list->toArray());
	}

	public function testGetCountOnLastPageIsRemainder()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->PageSize = 2;
		$list->gotoPage(1);
		self::assertSame(1, $list->Count);
		$list->gotoPage(0);
		self::assertSame(2, $list->Count);
	}

	public function testManagedIteratorTraversesCurrentPage()
	{
		$list = new TPagedList([1, 2, 3, 4, 5]);
		$list->PageSize = 2;
		$list->gotoPage(1);
		$iterator = $list->getIterator();
		self::assertInstanceOf(\ArrayIterator::class, $iterator);
		self::assertSame([0 => 3, 1 => 4], iterator_to_array($list));
		$list->gotoPage(2);
		self::assertSame([0 => 5], iterator_to_array($list));
	}

	public function testManagedItemAccessIsPageRelative()
	{
		$list = new TPagedList([1, 2, 3, 4, 5]);
		$list->PageSize = 2;
		$list->gotoPage(1);
		self::assertSame(3, $list[0]);
		self::assertSame(1, $list->indexOf(4));
		self::assertSame(-1, $list->indexOf(1));
		self::assertTrue(isset($list[1]));
		self::assertFalse(isset($list[2]));
	}

	public function testOffsetExistsRejectsNegativeOffset()
	{
		$list = new TPagedList([1, 2, 3]);
		$list->CustomPaging = true;
		self::assertFalse(isset($list[-1]));
	}

	public function testEmptyCustomPagedList()
	{
		$list = new TPagedList();
		$list->CustomPaging = true;
		self::assertSame(0, $list->Count);
		self::assertSame([], $list->toArray());
		self::assertSame(-1, $list->indexOf(1));
	}
}
