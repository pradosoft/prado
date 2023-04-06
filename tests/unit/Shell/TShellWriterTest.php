<?php

use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;


class TShellWriterTest extends PHPUnit\Framework\TestCase
{
	protected $writer;
	protected $obj;
	
	protected function getTestClass()
	{
		return TShellWriter::class;
	}

	protected function setUp(): void
	{
		$this->writer = new TTextWriter();
		$baseClass = $this->getTestClass();
		$this->obj = new $baseClass($this->writer);
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TShellWriter::class, $this->obj);
	}
	
	public function testColorSupported()
	{
		$this->obj->setColorSupported(true);
		self::assertTrue($this->obj->getColorSupported());
		$this->obj->setColorSupported(false);
		self::assertFalse($this->obj->getColorSupported());
		$this->obj->setColorSupported('true');
		self::assertTrue($this->obj->getColorSupported());
		$this->obj->setColorSupported('false');
		self::assertFalse($this->obj->getColorSupported());
	}
	
	public function testWriter()
	{
		self::assertEquals($this->writer, $this->obj->getWriter());
		$writer = new TTextWriter();
		$this->obj->setWriter($writer);
		self::assertEquals($writer, $this->obj->getWriter());
	}
	
	public function testFlush()
	{
		self::assertEquals('', $this->obj->flush());
		$this->obj->write('some text');
		self::assertEquals('some text', $this->obj->flush());
	}

	public function testWrite()
	{
		$this->obj->write("some text\n");
		$this->obj->write("more text\n");
		self::assertEquals("some text\nmore text\n", $this->obj->flush());
	}

	public function testWriteLine()
	{
		$this->obj->writeLine('some text');
		self::assertEquals("some text\n", $this->obj->flush());
	}
	
	public function testWriteError()
	{
		$this->obj->writeError('my error text');
		$text = $this->obj->flush();
		self::assertTrue(str_contains($text, "Error"));
		self::assertTrue(str_contains($text, "my error text"));
	}
	
	public function testPad()
	{
		self::assertEquals('text ', $this->obj->pad('text', 5));
		self::assertEquals('text-', $this->obj->pad('text', 5, '-'));
		self::assertEquals(' text', $this->obj->pad('text', 5, ' ', STR_PAD_LEFT));
		self::assertEquals('text ', $this->obj->pad('text', 5, ' ', STR_PAD_RIGHT));
		self::assertEquals(' text ', $this->obj->pad('text', 6, ' ', STR_PAD_BOTH));
		self::assertEquals('-text', $this->obj->pad('text', 5, '-', STR_PAD_LEFT));
		self::assertEquals('text-', $this->obj->pad('text', 5, '-', STR_PAD_RIGHT));
		self::assertEquals('-text-', $this->obj->pad('text', 6, '-', STR_PAD_BOTH));
		
		self::assertEquals('  text', $this->obj->pad('text', 6, ' ', STR_PAD_LEFT));
		self::assertEquals('text  ', $this->obj->pad('text', 6, ' ', STR_PAD_RIGHT));
		self::assertEquals('  text  ', $this->obj->pad('text', 8, ' ', STR_PAD_BOTH));
	}
	
	public function testTableWidget()
	{
		$str = $this->obj->tableWidget(['headers' => ['title 1', 'title 2', 'count'], 'rows' => [['aa', 'bb', 'cc'], ['dd', 'ee', 'ff']]]);
		self::assertEquals(1, preg_match('/title 1(?:.*)title 2(?:.*)count(?:.*)aa(?:.*)bb(?:.*)cc(?:.*)dd(?:.*)ee(?:.*)ff/ms', $str));
	}
	
	public function testFormat()
	{
		$this->obj->setColorSupported(true);
		self::assertEquals("\033[43mtext\033[0m", $this->obj->format('text', 43));
		self::assertEquals("\033[43mtext\033[0m", $this->obj->format('text', [43]));
		self::assertEquals("\033[34;1mtext\033[0m", $this->obj->format('text', [34, 1]));
		
		$this->obj->setColorSupported(false);
		self::assertEquals("text", $this->obj->format('text', 43));
		self::assertEquals("text", $this->obj->format('text', [43]));
		self::assertEquals("text", $this->obj->format('text', [34, 1]));
	}
	
	public function testMoveCursorUp()
	{
		$this->obj->moveCursorUp();
		self::assertEquals("\033[1A", $this->obj->flush());
		
		$this->obj->moveCursorUp(3);
		self::assertEquals("\033[3A", $this->obj->flush());
	}
	
	public function testMoveCursorDown()
	{
		$this->obj->moveCursorDown();
		self::assertEquals("\033[1B", $this->obj->flush());
		
		$this->obj->moveCursorDown(3);
		self::assertEquals("\033[3B", $this->obj->flush());
	}
	
	public function testMoveCursorForward()
	{
		$this->obj->moveCursorForward();
		self::assertEquals("\033[1C", $this->obj->flush());
		
		$this->obj->moveCursorForward(3);
		self::assertEquals("\033[3C", $this->obj->flush());
	}
	
	public function testMoveCursorBackward()
	{
		$this->obj->moveCursorBackward();
		self::assertEquals("\033[1D", $this->obj->flush());
		
		$this->obj->moveCursorBackward(3);
		self::assertEquals("\033[3D", $this->obj->flush());
	}
	
	public function testMoveCursorNextLine()
	{
		$this->obj->moveCursorNextLine();
		self::assertEquals("\033[1E", $this->obj->flush());
		
		$this->obj->moveCursorNextLine(3);
		self::assertEquals("\033[3E", $this->obj->flush());
	}
	
	public function testMoveCursorPrevLine()
	{
		$this->obj->moveCursorPrevLine();
		self::assertEquals("\033[1F", $this->obj->flush());
		
		$this->obj->moveCursorPrevLine(3);
		self::assertEquals("\033[3F", $this->obj->flush());
	}
	
	public function testMoveCursorTo()
	{
		$this->obj->moveCursorTo(1);
		self::assertEquals("\033[1G", $this->obj->flush());
		
		$this->obj->moveCursorTo(3);
		self::assertEquals("\033[3G", $this->obj->flush());
		
		$this->obj->moveCursorTo(2, 1);
		self::assertEquals("\033[1;2H", $this->obj->flush());
		
		$this->obj->moveCursorTo(2, 3);
		self::assertEquals("\033[3;2H", $this->obj->flush());
	}
	
	public function testScrollUp()
	{
		$this->obj->scrollUp();
		self::assertEquals("\033[1S", $this->obj->flush());
		
		$this->obj->scrollUp(3);
		self::assertEquals("\033[3S", $this->obj->flush());
	}
	
	public function testScrollDown()
	{
		$this->obj->scrollDown();
		self::assertEquals("\033[1T", $this->obj->flush());
		
		$this->obj->scrollDown(3);
		self::assertEquals("\033[3T", $this->obj->flush());
	}
	
	public function testSaveCursorPosition()
	{
		$this->obj->saveCursorPosition();
		self::assertEquals("\033[s", $this->obj->flush());
	}
	
	public function testRestoreCursorPosition()
	{
		$this->obj->restoreCursorPosition();
		self::assertEquals("\033[u", $this->obj->flush());
	}
	
	public function testHideCursor()
	{
		$this->obj->hideCursor();
		self::assertEquals("\033[?25l", $this->obj->flush());
	}
	
	public function testShowCursor()
	{
		$this->obj->showCursor();
		self::assertEquals("\033[?25h", $this->obj->flush());
	}
	
	public function testClearScreen()
	{
		$this->obj->clearScreen();
		self::assertEquals("\033[2J", $this->obj->flush());
	}
	
	public function testClearScreenBeforeCursor()
	{
		$this->obj->clearScreenBeforeCursor();
		self::assertEquals("\033[1J", $this->obj->flush());
	}
	
	public function testClearScreenAfterCursor()
	{
		$this->obj->clearScreenAfterCursor();
		self::assertEquals("\033[0J", $this->obj->flush());
	}
	
	public function testClearLine()
	{
		$this->obj->clearLine();
		self::assertEquals("\033[2K", $this->obj->flush());
	}
	
	public function testClearLineBeforeCursor()
	{
		$this->obj->clearLineBeforeCursor();
		self::assertEquals("\033[1K", $this->obj->flush());
	}
	
	public function testClearLineAfterCursor()
	{
		$this->obj->clearLineAfterCursor();
		self::assertEquals("\033[0K", $this->obj->flush());
	}
	
	
}
