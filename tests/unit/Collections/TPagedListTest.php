<?php

use Prado\Collections\TPagedList;
use Prado\Collections\TPagedListFetchDataEventParameter;
use Prado\Collections\TPagedListPageChangedEventParameter;
use Prado\Exceptions\TInvalidDataValueException;

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

class TPagedListTest extends PHPUnit\Framework\TestCase
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
		self::expectException('Prado\\Exceptions\\TInvalidDataValueException');
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
}
