<?php


namespace Prado\Test\Unit\Collections;

use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Collections\TPagedDataSource;

class TPagedDataSourceTest extends \PHPUnit\Framework\TestCase
{
	private function createPaged($data, int $pageSize, int $pageIndex): TPagedDataSource
	{
		$ds = new TPagedDataSource();
		$ds->setDataSource($data);
		$ds->setPageSize($pageSize);
		$ds->setCurrentPageIndex($pageIndex);
		$ds->setAllowPaging(true);
		return $ds;
	}

	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testDataSource()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testPageSize()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testCurrentPageIndex()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testAllowPaging()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testAllowCustomPaging()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testVirtualItemCount()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
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

	public function testPageCount()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testIsFirstPage()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
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

	public function testFirstIndexInPage()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testDataSourceCount()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}

	public function testIterator()
	{
		throw new \PHPUnit\Framework\IncompleteTestError();
	}
}
