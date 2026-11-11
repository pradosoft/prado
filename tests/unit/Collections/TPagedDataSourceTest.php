<?php

namespace Prado\Test\Unit\Collections;

use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Collections\TPagedDataSource;
use Prado\Collections\TPagedListIterator;
use Prado\Collections\TPagedMapIterator;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;

class TPagedDataSourceTest extends \PHPUnit\Framework\TestCase
{
	private function createPaged($data, int $pageSize = 2, int $pageIndex = 0, bool $allowPaging = true): TPagedDataSource
	{
		$ds = new TPagedDataSource();
		$ds->setDataSource($data);
		$ds->setPageSize($pageSize);
		$ds->setCurrentPageIndex($pageIndex);
		$ds->setAllowPaging($allowPaging);
		return $ds;
	}

	public function testDefaults()
	{
		$ds = new TPagedDataSource();
		self::assertNull($ds->getDataSource());
		self::assertSame(10, $ds->getPageSize());
		self::assertSame(0, $ds->getCurrentPageIndex());
		self::assertFalse($ds->getAllowPaging());
		self::assertFalse($ds->getAllowCustomPaging());
		self::assertSame(0, $ds->getVirtualItemCount());
	}

	public function testDataSourceKeepsTListAndTMap()
	{
		$ds = new TPagedDataSource();
		$list = new TList([1, 2]);
		$ds->setDataSource($list);
		self::assertSame($list, $ds->getDataSource());

		$map = new TMap(['a' => 1]);
		$ds->setDataSource($map);
		self::assertSame($map, $ds->getDataSource());
	}

	public function testDataSourceWrapsArrayInTMap()
	{
		$ds = new TPagedDataSource();
		$ds->setDataSource(['a' => 1, 'b' => 2]);
		self::assertInstanceOf(TMap::class, $ds->getDataSource());
		self::assertSame(['a' => 1, 'b' => 2], $ds->getDataSource()->toArray());
	}

	public function testDataSourceWrapsTraversableInTList()
	{
		$ds = new TPagedDataSource();
		$ds->setDataSource(new \ArrayIterator(['x' => 'a', 'y' => 'b']));
		self::assertInstanceOf(TList::class, $ds->getDataSource());
		self::assertSame(['a', 'b'], $ds->getDataSource()->toArray());
	}

	public function testDataSourceAcceptsNull()
	{
		$ds = new TPagedDataSource();
		$ds->setDataSource([1]);
		$ds->setDataSource(null);
		self::assertNull($ds->getDataSource());
	}

	public function testDataSourceRejectsScalar()
	{
		$ds = new TPagedDataSource();
		try {
			$ds->setDataSource('not iterable');
			self::fail('TInvalidDataTypeException expected');
		} catch (TInvalidDataTypeException $e) {
			self::assertSame('pageddatasource_datasource_invalid', $e->getErrorCode());
		}
		self::assertNull($ds->getDataSource());
	}

	public function testPageSize()
	{
		$ds = new TPagedDataSource();
		$ds->setPageSize('5');
		self::assertSame(5, $ds->getPageSize());
	}

	public function testPageSizeRejectsZero()
	{
		$ds = new TPagedDataSource();
		try {
			$ds->setPageSize(0);
			self::fail('TInvalidDataValueException expected');
		} catch (TInvalidDataValueException $e) {
			self::assertSame('pageddatasource_pagesize_invalid', $e->getErrorCode());
		}
		self::assertSame(10, $ds->getPageSize());
	}

	public function testCurrentPageIndex()
	{
		$ds = new TPagedDataSource();
		$ds->setCurrentPageIndex('3');
		self::assertSame(3, $ds->getCurrentPageIndex());
	}

	public function testCurrentPageIndexClampsNegativeToZero()
	{
		$ds = new TPagedDataSource();
		$ds->setCurrentPageIndex(4);
		$ds->setCurrentPageIndex(-2);
		self::assertSame(0, $ds->getCurrentPageIndex());
	}

	public function testAllowPagingAndCustomPaging()
	{
		$ds = new TPagedDataSource();
		$ds->setAllowPaging('true');
		$ds->setAllowCustomPaging(true);
		self::assertTrue($ds->getAllowPaging());
		self::assertTrue($ds->getAllowCustomPaging());
		$ds->setAllowPaging(false);
		$ds->setAllowCustomPaging('false');
		self::assertFalse($ds->getAllowPaging());
		self::assertFalse($ds->getAllowCustomPaging());
	}

	public function testVirtualItemCount()
	{
		$ds = new TPagedDataSource();
		$ds->setVirtualItemCount('7');
		self::assertSame(7, $ds->getVirtualItemCount());
		$ds->setVirtualItemCount(0);
		self::assertSame(0, $ds->getVirtualItemCount());
	}

	public function testVirtualItemCountRejectsNegative()
	{
		$ds = new TPagedDataSource();
		$ds->setVirtualItemCount(3);
		try {
			$ds->setVirtualItemCount(-1);
			self::fail('TInvalidDataValueException expected');
		} catch (TInvalidDataValueException $e) {
			self::assertSame('pageddatasource_virtualitemcount_invalid', $e->getErrorCode());
		}
		self::assertSame(3, $ds->getVirtualItemCount());
	}

	public function testCountWithoutDataSourceIsZero()
	{
		$ds = new TPagedDataSource();
		$ds->setAllowPaging(true);
		self::assertSame(0, $ds->getCount());
		self::assertCount(0, $ds);
	}

	public function testCountWithoutPagingIsDataSourceCount()
	{
		$ds = $this->createPaged([1, 2, 3, 4, 5], 2, 1, false);
		self::assertSame(5, $ds->getCount());
		self::assertCount(5, $ds);
	}

	public function testCountOnLastPageIsRemainder()
	{
		$ds = $this->createPaged(new TList([1, 2, 3, 4, 5]), 2, 2);
		self::assertSame(1, $ds->getCount());
		self::assertCount(1, $ds);
	}

	public function testCountOnInnerPageIsPageSize()
	{
		$ds = $this->createPaged(new TList([1, 2, 3, 4, 5]), 2, 1);
		self::assertSame(2, $ds->getCount());
	}

	public function testCountUnderCustomPagingCountsHeldItems()
	{
		$ds = $this->createPaged(new TList([1, 2]), 3, 0);
		$ds->setAllowCustomPaging(true);
		$ds->setVirtualItemCount(3);
		self::assertTrue($ds->getIsLastPage());
		self::assertSame(2, $ds->getCount());
	}

	public function testPageCountWithoutDataSourceIsZero()
	{
		$ds = new TPagedDataSource();
		$ds->setAllowPaging(true);
		self::assertSame(0, $ds->getPageCount());
	}

	public function testPageCountWithoutPagingIsOne()
	{
		$ds = $this->createPaged([1, 2, 3, 4, 5], 2, 0, false);
		self::assertSame(1, $ds->getPageCount());
	}

	public function testPageCountOfEmptyDataSourceIsOne()
	{
		$ds = $this->createPaged(new TList(), 2, 0);
		self::assertSame(1, $ds->getPageCount());
		self::assertSame(0, $ds->getCount());
	}

	public function testPageCountRoundsUp()
	{
		$ds = $this->createPaged(new TList([1, 2, 3, 4, 5]), 2, 0);
		self::assertSame(3, $ds->getPageCount());
		$ds->setPageSize(5);
		self::assertSame(1, $ds->getPageCount());
	}

	public function testPageCountUnderCustomPagingUsesVirtualItemCount()
	{
		$ds = $this->createPaged(new TList([1, 2]), 2, 0);
		$ds->setAllowCustomPaging(true);
		$ds->setVirtualItemCount(7);
		self::assertSame(4, $ds->getPageCount());
	}

	public function testIsFirstPage()
	{
		$ds = $this->createPaged(new TList([1, 2, 3]), 1, 0);
		self::assertTrue($ds->getIsFirstPage());
		$ds->setCurrentPageIndex(1);
		self::assertFalse($ds->getIsFirstPage());
	}

	public function testIsFirstPageWithoutPagingIsTrue()
	{
		$ds = $this->createPaged(new TList([1, 2, 3]), 1, 2, false);
		self::assertTrue($ds->getIsFirstPage());
	}

	public function testIsLastPage()
	{
		$ds = $this->createPaged(new TList([1, 2, 3]), 1, 1);
		self::assertFalse($ds->getIsLastPage());
		$ds->setCurrentPageIndex(2);
		self::assertTrue($ds->getIsLastPage());
	}

	public function testIsLastPageWithoutPagingIsTrue()
	{
		$ds = $this->createPaged(new TList([1, 2, 3]), 1, 0, false);
		self::assertTrue($ds->getIsLastPage());
	}

	public function testFirstIndexInPage()
	{
		$ds = $this->createPaged(new TList([1, 2, 3, 4, 5]), 2, 2);
		self::assertSame(4, $ds->getFirstIndexInPage());
	}

	public function testFirstIndexInPageWithoutDataSourceIsZero()
	{
		$ds = new TPagedDataSource();
		$ds->setAllowPaging(true);
		$ds->setPageSize(2);
		$ds->setCurrentPageIndex(2);
		self::assertSame(0, $ds->getFirstIndexInPage());
	}

	public function testFirstIndexInPageWithoutPagingIsZero()
	{
		$ds = $this->createPaged(new TList([1, 2, 3, 4, 5]), 2, 2, false);
		self::assertSame(0, $ds->getFirstIndexInPage());
	}

	public function testFirstIndexInPageUnderCustomPagingIsZero()
	{
		$ds = $this->createPaged(new TList([1, 2, 3, 4, 5]), 2, 2);
		$ds->setAllowCustomPaging(true);
		self::assertSame(0, $ds->getFirstIndexInPage());
	}

	public function testDataSourceCount()
	{
		$ds = new TPagedDataSource();
		self::assertSame(0, $ds->getDataSourceCount());
		$ds->setDataSource(new TList([1, 2, 3]));
		self::assertSame(3, $ds->getDataSourceCount());
		$ds->setAllowCustomPaging(true);
		$ds->setVirtualItemCount(40);
		self::assertSame(40, $ds->getDataSourceCount());
	}

	public function testIteratorWithoutDataSourceIsNull()
	{
		$ds = new TPagedDataSource();
		self::assertNull($ds->getIterator());
	}

	public function testIteratorOverTListPage()
	{
		$ds = $this->createPaged(new TList(['a', 'b', 'c', 'd', 'e']), 2, 1);
		$iterator = $ds->getIterator();
		self::assertInstanceOf(TPagedListIterator::class, $iterator);
		self::assertSame([0 => 'c', 1 => 'd'], iterator_to_array($ds));
	}

	public function testIteratorOverTListLastPage()
	{
		$ds = $this->createPaged(new TList(['a', 'b', 'c', 'd', 'e']), 2, 2);
		self::assertSame([0 => 'e'], iterator_to_array($ds));
	}

	public function testIteratorOverTMapPageKeepsKeys()
	{
		$ds = $this->createPaged(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5], 2, 1);
		self::assertInstanceOf(TPagedMapIterator::class, $ds->getIterator());
		self::assertSame(['c' => 3, 'd' => 4], iterator_to_array($ds));
	}

	public function testIteratorUnderCustomPagingStopsAtDataSourceEnd()
	{
		$ds = $this->createPaged(new TList(['a', 'b']), 5, 3);
		$ds->setAllowCustomPaging(true);
		$ds->setVirtualItemCount(20);
		self::assertSame(2, $ds->getCount());
		self::assertSame(['a', 'b'], iterator_to_array($ds));
	}

	public function testCountMatchesIterationOnEveryPage()
	{
		foreach ([new TList([1, 2, 3, 4, 5]), new TMap(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5])] as $data) {
			foreach ([0 => 2, 1 => 2, 2 => 1, 3 => 0, 50 => 0] as $pageIndex => $expected) {
				$ds = $this->createPaged($data, 2, $pageIndex);
				self::assertSame($expected, $ds->getCount(), "Page $pageIndex");
				self::assertSame($expected, count($ds), "Page $pageIndex");
				self::assertCount($expected, iterator_to_array($ds->getIterator()), "Page $pageIndex");
			}
		}
	}

	public function testCountOfEmptyDataSourceIsZero()
	{
		$ds = $this->createPaged(new TList(), 2, 0);
		self::assertSame(0, $ds->getCount());
		self::assertSame(1, $ds->getPageCount());
		self::assertTrue($ds->getIsLastPage());
	}

	public function testCustomPagingCountMatchesIteration()
	{
		foreach ([[[1, 2], 2], [[1, 2, 3], 3], [[1, 2, 3, 4, 5], 3], [[], 0]] as [$items, $expected]) {
			$ds = $this->createPaged(new TList($items), 3, 1);
			$ds->setAllowCustomPaging(true);
			$ds->setVirtualItemCount(5);
			self::assertSame($expected, $ds->getCount());
			self::assertCount($expected, iterator_to_array($ds->getIterator()));
		}
	}

	public function testIsLastPageIncludesPagesPastTheEnd()
	{
		$data = new TList([1, 2, 3, 4, 5]);
		self::assertFalse($this->createPaged($data, 2, 1)->getIsLastPage());
		self::assertTrue($this->createPaged($data, 2, 2)->getIsLastPage());
		self::assertTrue($this->createPaged($data, 2, 3)->getIsLastPage());
	}

	public function testIsLastPageWithoutDataSourceIsFalse()
	{
		$ds = new TPagedDataSource();
		$ds->setAllowPaging(true);
		self::assertFalse($ds->getIsLastPage());
	}
}
