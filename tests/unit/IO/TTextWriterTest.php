<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.IO.TTextWriter');

/**
 * @package System.IO
 */
class TTextWriterTest extends PHPUnit_Framework_TestCase {

	public function testFlush() {
		$writer = new TTextWriter();
		self::assertEquals('', $writer->flush());
		$writer->write('some text');
		self::assertEquals('some text', $writer->flush());
	}

	public function testWrite() {
		$writer = new TTextWriter();
		$writer->write("some text\n");
		$writer->write("more text\n");
		self::assertEquals("some text\nmore text\n", $writer->flush());
	}
	
	public function testWriteLine() {
		$writer = new TTextWriter();
		$writer->writeLine('some text');
		self::assertEquals("some text\n", $writer->flush());
	}
}

?>
