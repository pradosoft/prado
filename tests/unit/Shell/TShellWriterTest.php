<?php

namespace Prado\Test\Unit\Shell;

use Prado\IO\TMemoryStream;
use Prado\IO\TTextWriter;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Util\Helpers\TProcessHelper;

/**
 * Reports standard output as the terminal {@see $terminal} says, for color detection tests.
 */
class TShellWriterTestTerminal extends TShellWriter
{
	public static bool $terminal = false;

	protected function isStdOutTerminal(): bool
	{
		return static::$terminal;
	}

	protected function isWindowsConsoleColor(): bool
	{
		return static::$terminal;
	}
}

class TShellWriterTest extends \PHPUnit\Framework\TestCase
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

	/** @var array<string, false|string> environment variables to restore in tearDown */
	private array $_env = [];

	protected function tearDown(): void
	{
		$this->obj = null;
		$restoreSize = isset($this->_env['COLUMNS']) || isset($this->_env['LINES']);
		foreach ($this->_env as $name => $value) {
			putenv($value === false ? $name : "$name=$value");
		}
		$this->_env = [];
		if ($restoreSize) {
			TShellWriter::getScreenSize(true);
		}
	}

	/**
	 * Sets environment variables, null unsets one, and restores them in tearDown.
	 * @param array<string, ?string> $vars
	 */
	private function setEnv(array $vars): void
	{
		foreach ($vars as $name => $value) {
			if (!array_key_exists($name, $this->_env)) {
				$this->_env[$name] = getenv($name);
			}
			putenv($value === null ? $name : "$name=$value");
		}
	}

	/**
	 * Clears the color environment variables, then sets `$vars`.
	 * @param array<string, ?string> $vars
	 */
	private function setColorEnv(array $vars): void
	{
		$this->setEnv($vars + ['NO_COLOR' => null, 'FORCE_COLOR' => null, 'CLICOLOR_FORCE' => null, 'TERM' => null]);
	}

	private function colorDetected(array $vars, bool $terminal): bool
	{
		$this->setColorEnv($vars);
		TShellWriterTestTerminal::$terminal = $terminal;
		return (new TShellWriterTestTerminal(new TTextWriter()))->getColorSupported();
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

	public function testFormatErrorFramesTheCenteredText()
	{
		$this->obj->setColorSupported(false);
		$frame = '*' . str_repeat('-', 78) . '*';
		$header = '*' . str_repeat('-', 35) . ' Error ' . str_repeat('-', 36) . '*';
		$line = '*  ' . str_repeat(' ', 35) . 'boom' . str_repeat(' ', 35) . '  *';

		self::assertSame("\n$header\n$line\n$frame\n\n", $this->obj->formatError('boom'));
	}

	public function testFormatErrorColorsEachFrameLine()
	{
		$this->obj->setColorSupported(true);

		$block = $this->obj->formatError('boom');

		self::assertSame(3, substr_count($block, "\033[41;97;1m"));
		self::assertSame(3, substr_count($block, "\033[0m\n"));
	}

	public function testWriteErrorWithoutErrorStreamWritesTheBlockToTheWriter()
	{
		self::assertNull($this->obj->getErrorStream());

		$this->obj->write('before');
		$this->obj->writeError('boom');

		self::assertSame('before' . $this->obj->formatError('boom'), $this->obj->flush());
	}

	public function testWriteErrorFlushesTheWriterThenWritesTheErrorStream()
	{
		$text = new TTestCapturingTextWriter();
		$writer = new TShellWriter($text);
		$stream = new TMemoryStream();
		$writer->setErrorStream($stream);
		self::assertSame($stream, $writer->getErrorStream());

		$writer->write('before');
		$writer->writeError('boom');

		self::assertSame('before', $text->getCaptured());
		self::assertSame('', $text->flush());
		self::assertSame($writer->formatError('boom'), (string) $stream);

		$writer->setErrorStream(null);
		self::assertNull($writer->getErrorStream());
	}

	public function testColorDetectionHonorsNoColor()
	{
		self::assertFalse($this->colorDetected(['NO_COLOR' => '1', 'FORCE_COLOR' => '1', 'TERM' => 'xterm'], true));
		self::assertTrue($this->colorDetected(['NO_COLOR' => '', 'TERM' => 'xterm'], true));
	}

	public function testColorDetectionHonorsForceColor()
	{
		self::assertTrue($this->colorDetected(['FORCE_COLOR' => '1'], false));
		self::assertTrue($this->colorDetected(['FORCE_COLOR' => ''], false));
		self::assertFalse($this->colorDetected(['FORCE_COLOR' => '0', 'TERM' => 'xterm'], true));
		self::assertFalse($this->colorDetected(['FORCE_COLOR' => 'FALSE', 'TERM' => 'xterm'], true));
	}

	public function testColorDetectionHonorsClicolorForce()
	{
		self::assertTrue($this->colorDetected(['CLICOLOR_FORCE' => '1'], false));
		self::assertFalse($this->colorDetected(['CLICOLOR_FORCE' => '0'], false));
		self::assertFalse($this->colorDetected(['CLICOLOR_FORCE' => ''], false));
	}

	public function testColorDetectionNeedsATerminalWithATerm()
	{
		if (TProcessHelper::isSystemWindows()) {
			self::markTestSkipped('Windows decides color from the console, not TERM.');
		}
		self::assertTrue($this->colorDetected(['TERM' => 'xterm'], true));
		self::assertTrue($this->colorDetected(['TERM' => 'xterm-256color'], true));
		self::assertFalse($this->colorDetected(['TERM' => 'xterm'], false));
		self::assertFalse($this->colorDetected(['TERM' => 'dumb'], true));
		self::assertFalse($this->colorDetected(['TERM' => ''], true));
		self::assertFalse($this->colorDetected([], true));
	}

	public function testColorDetectionOnWindowsAsksTheConsole()
	{
		if (!TProcessHelper::isSystemWindows()) {
			self::markTestSkipped('Only Windows decides color from the console.');
		}
		self::assertTrue($this->colorDetected(['TERM' => 'dumb'], true));
		self::assertTrue($this->colorDetected([], true));
		self::assertFalse($this->colorDetected(['TERM' => 'xterm'], false));
	}

	public function testWidth()
	{
		self::assertNull($this->obj->getWidth());
		$this->obj->setWidth('40');
		self::assertSame(40, $this->obj->getWidth());
		$this->obj->setWidth(0);
		self::assertNull($this->obj->getWidth());
		$this->obj->setWidth(12);
		$this->obj->setWidth('');
		self::assertNull($this->obj->getWidth());
		$this->obj->setWidth(12);
		$this->obj->setWidth(null);
		self::assertNull($this->obj->getWidth());
	}

	public function testWrapTextUsesTheWidth()
	{
		$this->obj->setWidth(16);

		self::assertSame("Lorem ipsum\n    dolor sit\n    amet.", $this->obj->wrapText('Lorem ipsum dolor sit amet.', 4));
		self::assertSame('Lorem ipsum dolor', $this->obj->wrapText('Lorem ipsum dolor', 16));
	}

	public function testGetScreenSizeReadsColumnsAndLinesFirst()
	{
		$this->setEnv(['COLUMNS' => '123', 'LINES' => '45']);

		self::assertSame([123, 45], TShellWriter::getScreenSize(true));
		self::assertSame([123, 45], TShellWriter::getScreenSize());
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
