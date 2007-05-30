<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Collections.TPagedList');

/**
 * @package System.Collections
 */
class TPagedListTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
	}

	public function tearDown() {
	}

	public function testConstruct() {
		$list = new TPagedList();
		self::assertEquals(true, $list->getReadOnly());
		self::assertEquals(-1, $list->getVirtualCount());
		$list = new TPagedList(array(1, 2, 3));
		$list->setPageSize(3);
		self::assertEquals(3, $list->getCount());
	}

	public function testCustomPaging() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}

	public function testPageSize() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testCurrentPageIndex() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testOnPageIndexChanged() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testOnFetchData() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testGotoPage() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testNextPage() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testPreviousPage() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testVirtualCount() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testIsFirstPage() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testIsLastPage() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testGetCount() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testGetIterator() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testItemAt() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testIndexOf() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testOffsetExists() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testOffsetGet() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testToArray() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}

}

?>
