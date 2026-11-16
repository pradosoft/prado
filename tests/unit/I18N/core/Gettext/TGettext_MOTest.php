<?php

namespace Prado\Test\Unit\I18N\core\Gettext;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\Gettext\TGettext_MO;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A stream wrapper that opens every path and refuses every lock.
 */
class TGettextMOLockFailStream
{
	public const SCHEME = 'gettextmolockfail';

	public static int $closed = 0;

	public $context;

	public function stream_open($path, $mode, $options, &$openedPath): bool
	{
		return true;
	}

	public function stream_lock($operation): bool
	{
		return false;
	}

	public function stream_close(): void
	{
		self::$closed++;
	}
}

class TGettext_MOTest extends TestCase
{
	use TTempDirectoryTrait;

	protected function setUp(): void
	{
		TGettextMOLockFailStream::$closed = 0;
		stream_wrapper_register(TGettextMOLockFailStream::SCHEME, TGettextMOLockFailStream::class);
	}

	protected function tearDown(): void
	{
		stream_wrapper_unregister(TGettextMOLockFailStream::SCHEME);
		$this->removeTempDirectories();
	}

	/**
	 * Returns a reader whose handle is a memory stream holding the data.
	 * @param string $data the stream contents.
	 * @return TGettext_MO the reader.
	 */
	private function moWithMemory(string $data = ''): TGettext_MO
	{
		$handle = fopen('php://memory', 'w+b');
		fwrite($handle, $data);
		rewind($handle);
		$mo = new TGettext_MO();
		PradoUnit::setProp($mo, '_handle', $handle);
		return $mo;
	}

	/**
	 * Returns the contents of the reader's memory stream.
	 * @param TGettext_MO $mo the reader.
	 * @return string the stream contents.
	 */
	private function memoryContents(TGettext_MO $mo): string
	{
		$handle = PradoUnit::getProp($mo, '_handle');
		rewind($handle);
		return stream_get_contents($handle);
	}

	/**
	 * Packs a big-endian MO file with one string and no meta.
	 * @param int $revision the file format revision.
	 * @return string the MO data.
	 */
	private function bigEndianMo(int $revision = 0): string
	{
		// header: magic, revision, count, msgid table, msgstr table, hash size, hash offset.
		return pack('N*', 0x950412de, $revision, 1, 28, 36, 0, 44)
			. pack('N*', 5, 44) . pack('N*', 5, 50)
			. "Hello\0Hallo\0";
	}

	public function testConstructStoresTheFile()
	{
		$this->assertSame('/path/messages.mo', PradoUnit::getProp(new TGettext_MO('/path/messages.mo'), 'file'));
		$this->assertSame('', PradoUnit::getProp(new TGettext_MO(), 'file'));
	}

	public function testReadReadsTheByteCount()
	{
		$mo = $this->moWithMemory('abcdef');

		$this->assertSame('a', $mo->_read());
		$this->assertSame('bc', $mo->_read(2));
		$this->assertSame('def', $mo->_read(-3));
	}

	public function testReadReturnsNullForZeroBytes()
	{
		$mo = $this->moWithMemory('abc');

		$this->assertNull($mo->_read(0));
		$this->assertSame('a', $mo->_read());
	}

	public function testReadIntReadsLittleAndBigEndian()
	{
		$mo = $this->moWithMemory(pack('V', 0x01020304) . pack('N', 0x01020304));

		$this->assertSame(0x01020304, $mo->_readInt());
		$this->assertSame(0x01020304, $mo->_readInt(true));
	}

	public function testReadStrReadsAtTheOffset()
	{
		$mo = $this->moWithMemory('xxHelloyy');

		$this->assertSame('Hello', $mo->_readStr(['offset' => 2, 'length' => 5]));
	}

	public function testWriteMethodsWriteLittleEndianByDefault()
	{
		$mo = $this->moWithMemory();

		$this->assertSame(2, $mo->_write('ab'));
		$this->assertSame(4, $mo->_writeInt('7'));
		$this->assertSame(3, $mo->_writeStr('cd'));
		$this->assertSame('ab' . pack('V', 7) . "cd\0", $this->memoryContents($mo));
	}

	public function testWriteIntWritesBigEndian()
	{
		$mo = $this->moWithMemory();
		PradoUnit::setProp($mo, 'writeBigEndian', true);

		$mo->_writeInt(7);

		$this->assertSame(pack('N', 7), $this->memoryContents($mo));
	}

	public function testLoadReturnsFalseForAMissingFile()
	{
		$dir = $this->makeTempDirectory();

		$this->assertFalse((new TGettext_MO("$dir/missing.mo"))->load());
	}

	public function testLoadReturnsFalseAndClosesTheFileWhenTheLockFails()
	{
		$mo = new TGettext_MO();

		$this->assertFalse($mo->load(TGettextMOLockFailStream::SCHEME . '://messages.mo'));
		$this->assertSame(1, TGettextMOLockFailStream::$closed);
	}

	public function testLoadReturnsFalseForAnUnknownMagicNumber()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/bad.mo", pack('V*', 0x12345678, 0, 0, 28, 28, 0, 28));

		$this->assertFalse((new TGettext_MO("$dir/bad.mo"))->load());
	}

	public function testLoadReturnsFalseForAnUnknownRevision()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/rev.mo", $this->bigEndianMo(1));

		$this->assertFalse((new TGettext_MO("$dir/rev.mo"))->load());
	}

	public function testLoadReadsABigEndianFile()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/be.mo", $this->bigEndianMo());
		$mo = new TGettext_MO("$dir/be.mo");

		$this->assertTrue($mo->load());
		$this->assertSame(['meta' => [], 'strings' => ['Hello' => 'Hallo']], $mo->toArray());
		$this->assertNull(PradoUnit::getProp($mo, '_handle'));
	}

	public function testLoadReadsAnEmptyFile()
	{
		$dir = $this->makeTempDirectory();
		$writer = new TGettext_MO("$dir/empty.mo");
		$this->assertTrue($writer->save());

		$mo = new TGettext_MO();

		$this->assertTrue($mo->load("$dir/empty.mo"));
		$this->assertSame(['meta' => [], 'strings' => []], $mo->toArray());
	}

	public function testSaveWritesTheLittleEndianLayout()
	{
		$dir = $this->makeTempDirectory();
		$mo = new TGettext_MO();
		$mo->fromArray(['meta' => ['Language' => 'de'], 'strings' => ['Hello' => 'Hallo']]);

		$this->assertTrue($mo->save("$dir/le.mo"));

		$meta = "Language: de\n";
		$expected = pack('c*', 0xde, 0x12, 0x04, 0x95) . pack('V*', 0, 2, 28, 44, 0, 60)
			. pack('V*', 0, 60, 5, 61)
			. pack('V*', strlen($meta), 67, 5, 81)
			. "\0Hello\0" . $meta . "\0Hallo\0";
		$this->assertSame($expected, file_get_contents("$dir/le.mo"));
		clearstatcache();
		if (PHP_OS_FAMILY !== 'Windows') {
			$this->assertSame(Prado::getDefaultFilePermissions(), fileperms("$dir/le.mo") & 0o777);
		}
	}

	public function testSaveWritesTheBigEndianLayout()
	{
		$dir = $this->makeTempDirectory();
		$mo = new TGettext_MO("$dir/be.mo");
		PradoUnit::setProp($mo, 'writeBigEndian', true);
		$mo->fromArray(['meta' => [], 'strings' => ['Hello' => 'Hallo']]);

		$this->assertTrue($mo->save());

		$this->assertSame($this->bigEndianMo(), file_get_contents("$dir/be.mo"));
	}

	public function testSaveThenLoadRestoresTheStrings()
	{
		$dir = $this->makeTempDirectory();
		$data = [
			'meta' => [],
			'strings' => ['Hello' => 'Hallo', 'Line' => "eins\nzwei", '10' => 'zehn'],
		];
		$writer = new TGettext_MO();
		$writer->fromArray($data);
		$writer->save("$dir/round.mo");

		$mo = new TGettext_MO("$dir/round.mo");

		$this->assertTrue($mo->load());
		$this->assertSame($data, $mo->toArray());
	}

	public function testSaveReturnsFalseWhenTheFileCannotOpen()
	{
		$dir = $this->makeTempDirectory();

		$this->assertFalse((new TGettext_MO())->save("$dir/missing/messages.mo"));
	}

	public function testSaveReturnsFalseAndClosesTheFileWhenTheLockFails()
	{
		$mo = new TGettext_MO(TGettextMOLockFailStream::SCHEME . '://messages.mo');

		$this->assertFalse($mo->save());
		$this->assertSame(1, TGettextMOLockFailStream::$closed);
	}

	public function testLoadReadsTheMetaAndAnEmptyTranslation()
	{
		$dir = $this->makeTempDirectory();
		$data = ['meta' => ['Language' => 'de'], 'strings' => ['Bye' => '', 'Hello' => 'Hallo']];
		$writer = new TGettext_MO();
		$writer->fromArray($data);
		$this->assertTrue($writer->save("$dir/m.mo"));
		$mo = new TGettext_MO("$dir/m.mo");
		$this->assertTrue($mo->load());
		$this->assertSame($data, $mo->toArray());
	}

	public function testLoadRejectsAnEmptyOrTruncatedFile()
	{
		$dir = $this->makeTempDirectory();
		foreach (['' => 'empty', "\xde\x12" => 'short', "\xde\x12\x04\x95" => 'magic'] as $data => $name) {
			file_put_contents("$dir/$name.mo", $data);
			$mo = new TGettext_MO("$dir/$name.mo");
			$this->assertFalse($mo->load(), $name);
			$this->assertNull(PradoUnit::getProp($mo, '_handle'), $name);
		}
	}

	public function testLoadClosesTheFileOfAnUnknownMagicOrRevision()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/magic.mo", pack('V*', 0x12345678, 0, 0, 0, 0));
		file_put_contents("$dir/rev.mo", pack('V*', 0x950412de, 1, 0, 0, 0));
		foreach (['magic', 'rev'] as $name) {
			$mo = new TGettext_MO("$dir/$name.mo");
			$this->assertFalse($mo->load(), $name);
			$this->assertNull(PradoUnit::getProp($mo, '_handle'), $name);
		}
	}
}
