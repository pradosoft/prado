<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Util.TSimpleDateFormatter');

/**
 * @package System.Util
 */
class TSimpleDateFormatterTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
	}

	public function tearDown() {
	}

	public function testConstruct() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testPattern() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testCharset() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testFormat() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testMonthPattern() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testDayPattern() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testYearPattern() {
		$formatter = new TSimpleDateFormatter("yyyy");
		self::assertEquals("2008-01-01", date('Y-m-d', $formatter->parse("2008")));
	}
	
	public function testMissingYearPattern() {
		$formatter = new TSimpleDateFormatter("MM/dd");
		self::assertEquals("2008-10-22", date('Y-m-d', $formatter->parse("10/22")));
	}
	
	public function testDayMonthYearOrdering() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testIsValidDate() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testParse() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}

}

?>
