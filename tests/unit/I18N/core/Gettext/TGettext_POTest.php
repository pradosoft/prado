<?php

namespace Prado\Test\Unit\I18N\core\Gettext;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\Gettext\TGettext_PO;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A stream wrapper that opens every path and refuses every lock.
 */
class TGettextPOLockFailStream
{
	public const SCHEME = 'gettextpolockfail';

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

class TGettext_POTest extends TestCase
{
	use TTempDirectoryTrait;

	protected function setUp(): void
	{
		TGettextPOLockFailStream::$closed = 0;
		stream_wrapper_register(TGettextPOLockFailStream::SCHEME, TGettextPOLockFailStream::class);
	}

	protected function tearDown(): void
	{
		stream_wrapper_unregister(TGettextPOLockFailStream::SCHEME);
		$this->removeTempDirectories();
	}

	public function testConstructStoresTheFile()
	{
		$this->assertSame('/path/messages.po', PradoUnit::getProp(new TGettext_PO('/path/messages.po'), 'file'));
		$this->assertSame('', PradoUnit::getProp(new TGettext_PO(), 'file'));
	}

	public function testLoadReturnsFalseForAMissingFile()
	{
		$dir = $this->makeTempDirectory();

		$this->assertFalse((new TGettext_PO("$dir/missing.po"))->load());
	}

	public function testLoadReturnsFalseForAnEmptyFile()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/empty.po", '');

		$this->assertFalse((new TGettext_PO())->load("$dir/empty.po"));
	}

	public function testLoadReturnsFalseWithoutEntries()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/comment.po", "# a comment\n");

		$this->assertFalse((new TGettext_PO("$dir/comment.po"))->load());
		$this->assertFalse((new TGettext_PO())->load("$dir/comment.po"));
	}

	public function testLoadReadsOneEntry()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/one.po", "msgid \"Hello\"\nmsgstr \"Hallo\"\n");
		$po = new TGettext_PO("$dir/one.po");

		$this->assertTrue($po->load());
		$this->assertSame(['meta' => [], 'strings' => ['Hello' => 'Hallo']], $po->toArray());
	}

	public function testLoadReadsOneEntryOfTheGivenFile()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/one.po", "msgid \"Hello\"\nmsgstr \"Hallo\"\n");
		$po = new TGettext_PO("$dir/other.po");

		$this->assertTrue($po->load("$dir/one.po"));
		$this->assertSame(['meta' => [], 'strings' => ['Hello' => 'Hallo']], $po->toArray());
	}

	public function testLoadReadsAMetaOnlyFile()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/meta.po", "msgid \"\"\nmsgstr \"Language: de\\n\"\n");
		$po = new TGettext_PO();

		$this->assertTrue($po->load("$dir/meta.po"));
		$this->assertSame(['meta' => ['Language' => 'de'], 'strings' => []], $po->toArray());
	}

	public function testLoadReadsTheMetaAndContinuedEntries()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/full.po", <<<'PO'
			# translator comment
			msgid ""
			msgstr ""
			"Content-Type: text/plain; charset=UTF-8\n"
			"Language: de\n"

			msgid "Hello"
			msgstr "Hallo"

			msgid "say \"hi\""
			msgstr "sag hallo"

			msgid "two\n"
			"lines"
			msgstr "zwei\n"
			"Zeilen\tund\r"

			PO);
		$po = new TGettext_PO("$dir/full.po");

		$this->assertTrue($po->load());
		$this->assertSame([
			'meta' => ['Content-Type' => 'text/plain; charset=UTF-8', 'Language' => 'de'],
			'strings' => [
				'Hello' => 'Hallo',
				'say "hi"' => 'sag hallo',
				"two\nlines" => "zwei\nZeilen\tund\r",
			],
		], $po->toArray());
	}

	public function testSaveWritesTheMetaAndStrings()
	{
		$dir = $this->makeTempDirectory();
		$po = new TGettext_PO();
		$po->fromArray(['meta' => ['Language' => 'de'], 'strings' => ['Hello' => 'Hallo', 'say "hi"' => "a\nb"]]);

		$this->assertTrue($po->save("$dir/out.po"));

		$this->assertSame(
			"msgid \"\"\nmsgstr \"\"\n\"Language: de\\n\"\n\n"
			. "msgid \"Hello\"\nmsgstr \"Hallo\"\n\n"
			. "msgid \"say \\\"hi\\\"\"\nmsgstr \"a\\n\"\n\"b\"\n\n",
			file_get_contents("$dir/out.po")
		);
		clearstatcache();
		if (PHP_OS_FAMILY !== 'Windows') {
			$this->assertSame(Prado::getDefaultFilePermissions(), fileperms("$dir/out.po") & 0o777);
		}
	}

	public function testSaveWritesOneString()
	{
		$dir = $this->makeTempDirectory();
		$po = new TGettext_PO("$dir/one.po");
		$po->fromArray(['meta' => [], 'strings' => ['Hello' => 'Hallo']]);

		$this->assertTrue($po->save());

		$this->assertSame("msgid \"Hello\"\nmsgstr \"Hallo\"\n\n", file_get_contents("$dir/one.po"));
	}

	public function testSaveWritesAnEmptyFileWithoutMetaOrStrings()
	{
		$dir = $this->makeTempDirectory();

		$this->assertTrue((new TGettext_PO("$dir/empty.po"))->save());

		$this->assertSame('', file_get_contents("$dir/empty.po"));
	}

	public function testSaveThenLoadRestoresTheMetaAndStrings()
	{
		$dir = $this->makeTempDirectory();
		$data = [
			'meta' => ['Content-Type' => 'text/plain; charset=UTF-8', 'Language' => 'de'],
			'strings' => ['Hello' => 'Hallo', 'say "hi"' => "zwei\nZeilen\tund\r", '10' => 'zehn'],
		];
		$writer = new TGettext_PO();
		$writer->fromArray($data);
		$writer->save("$dir/round.po");

		$po = new TGettext_PO("$dir/round.po");

		$this->assertTrue($po->load());
		$this->assertSame($data, $po->toArray());
	}

	public function testSaveReturnsFalseWhenTheFileCannotOpen()
	{
		$dir = $this->makeTempDirectory();

		$this->assertFalse((new TGettext_PO())->save("$dir/missing/messages.po"));
	}

	public function testSaveReturnsFalseAndClosesTheFileWhenTheLockFails()
	{
		$po = new TGettext_PO(TGettextPOLockFailStream::SCHEME . '://messages.po');

		$this->assertFalse($po->save());
		$this->assertSame(1, TGettextPOLockFailStream::$closed);
	}

	public function testSaveAndLoadRoundTripEscapes()
	{
		$dir = $this->makeTempDirectory();
		$data = ['meta' => ['Language' => 'de'], 'strings' => ['C:\\new' => 'sag "hallo"', "two\nlines" => "tab\there"]];
		$writer = new TGettext_PO();
		$writer->fromArray($data);
		$this->assertTrue($writer->save("$dir/m.po"));
		$po = new TGettext_PO("$dir/m.po");
		$this->assertTrue($po->load());
		$this->assertSame($data, $po->toArray());
	}
}
