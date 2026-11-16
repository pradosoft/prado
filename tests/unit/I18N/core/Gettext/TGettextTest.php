<?php

namespace Prado\Test\Unit\I18N\core\Gettext;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\Gettext\TGettext;
use Prado\I18N\core\Gettext\TGettext_MO;
use Prado\I18N\core\Gettext\TGettext_PO;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

class TGettextTest extends TestCase
{
	use TTempDirectoryTrait;

	protected function tearDown(): void
	{
		$this->removeTempDirectories();
	}

	public function testFactoryCreatesTheMoReader()
	{
		$mo = TGettext::factory('mo', '/path/messages.mo');

		$this->assertInstanceOf(TGettext_MO::class, $mo);
		$this->assertSame('/path/messages.mo', PradoUnit::getProp($mo, 'file'));
	}

	public function testFactoryCreatesThePoReader()
	{
		$po = TGettext::factory('PO');

		$this->assertInstanceOf(TGettext_PO::class, $po);
		$this->assertSame('', PradoUnit::getProp($po, 'file'));
	}

	public function testFactoryThrowsForAnUnknownFormat()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('TGettext_XX.php not found');

		TGettext::factory('xx');
	}

	public function testPoFile2moFileThrowsForAMissingPoFile()
	{
		$dir = $this->makeTempDirectory();

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage("File $dir/missing.po doesn't exist.");

		TGettext::poFile2moFile("$dir/missing.po", "$dir/missing.mo");
	}

	public function testPrepareUnescapesThePoStringEscapes()
	{
		$this->assertSame("a\nb\rc\td\"e", TGettext::prepare('a\\nb\\rc\\td\\"e'));
		$this->assertSame('ab', TGettext::prepare("a\"\n  \"b"));
		$this->assertSame('', TGettext::prepare(''));
	}

	public function testPrepareReverseEscapesTheStringForAPoFile()
	{
		$this->assertSame('a\\"b\\n"' . "\n" . '"c\\td\\re', TGettext::prepare("a\"b\nc\td\re", true));
		$this->assertSame('plain', TGettext::prepare('plain', true));
	}

	public function testPrepareReverseThenForwardRestoresTheString()
	{
		$string = "say \"hi\"\n\tto\rall\n";

		$this->assertSame($string, TGettext::prepare(TGettext::prepare($string, true)));
	}

	public function testMeta2arrayParsesTheHeaderLines()
	{
		$meta = "Content-Type: text/plain; charset=UTF-8\n\n  Language :  de  \nReport-Msgid-Bugs-To: http://example.com:80/\n";

		$this->assertSame([
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Language' => 'de',
			'Report-Msgid-Bugs-To' => 'http://example.com:80/',
		], TGettext::meta2array($meta));
	}

	public function testMeta2arrayParsesOneLine()
	{
		$this->assertSame(['Language' => 'fr'], TGettext::meta2array('Language: fr'));
	}

	public function testMeta2arrayReturnsAnEmptyArrayForEmptyMeta()
	{
		$this->assertSame([], TGettext::meta2array(''));
		$this->assertSame([], TGettext::meta2array("\n \n"));
	}

	public function testToArrayReturnsTheMetaAndStrings()
	{
		$gettext = new TGettext();

		$this->assertSame(['meta' => [], 'strings' => []], $gettext->toArray());
	}

	public function testFromArrayAssignsTheKeyedMetaAndStrings()
	{
		$gettext = new TGettext();
		$data = ['meta' => ['Language' => 'de'], 'strings' => ['Hello' => 'Hallo']];

		$this->assertTrue($gettext->fromArray($data));
		$this->assertSame($data, $gettext->toArray());
	}

	public function testFromArrayAssignsAListOfMetaAndStrings()
	{
		$gettext = new TGettext();

		$this->assertTrue($gettext->fromArray([['Language' => 'de'], ['Hello' => 'Hallo']]));
		$this->assertSame(['meta' => ['Language' => 'de'], 'strings' => ['Hello' => 'Hallo']], $gettext->toArray());
	}

	public function testFromArrayRejectsAListWithoutTwoEntries()
	{
		$gettext = new TGettext();

		$this->assertFalse($gettext->fromArray([['Language' => 'de']]));
		$this->assertFalse($gettext->fromArray([[], [], []]));
		$this->assertSame(['meta' => [], 'strings' => []], $gettext->toArray());
	}

	public function testToMOAndToPOConvertTheStrings()
	{
		$po = new TGettext_PO();
		$po->fromArray(['meta' => ['Language' => 'de'], 'strings' => ['Hello' => 'Hallo']]);
		$mo = $po->toMO();
		$this->assertInstanceOf(TGettext_MO::class, $mo);
		$this->assertSame($po->toArray(), $mo->toArray());
		$this->assertInstanceOf(TGettext_PO::class, $mo->toPO());
		$this->assertSame($po->toArray(), $mo->toPO()->toArray());
	}

	public function testPoFile2moFileWritesTheMoFile()
	{
		$dir = $this->makeTempDirectory();
		$po = new TGettext_PO();
		$po->fromArray(['meta' => ['Language' => 'de'], 'strings' => ['Hello' => 'Hallo']]);
		$po->save("$dir/m.po");
		$this->assertTrue(TGettext::poFile2moFile("$dir/m.po", "$dir/m.mo"));
		$mo = new TGettext_MO("$dir/m.mo");
		$this->assertTrue($mo->load());
		$this->assertSame($po->toArray(), $mo->toArray());
	}

	public function testPoFile2moFileReturnsTheLoadFailure()
	{
		$dir = $this->makeTempDirectory();
		file_put_contents("$dir/m.po", "# no entries\n");
		$this->assertFalse(TGettext::poFile2moFile("$dir/m.po", "$dir/m.mo"));
		$this->assertFileDoesNotExist("$dir/m.mo");
	}

	public function testPrepareRoundTripsBackslashesAndQuotes()
	{
		$string = "C:\\new \"quoted\"\ttab";
		$this->assertSame('C:\\\\new \\"quoted\\"\\ttab', TGettext::prepare($string, true));
		$this->assertSame($string, TGettext::prepare(TGettext::prepare($string, true)));
	}

	public function testMeta2arraySkipsALineWithoutAColon()
	{
		$this->assertSame(['Language' => 'de'], TGettext::meta2array("no colon\nLanguage: de"));
	}

	public function testFromArrayWithoutMeta()
	{
		$po = new TGettext_PO();
		$this->assertTrue($po->fromArray(['strings' => ['A' => 'a']]));
		$this->assertSame(['meta' => [], 'strings' => ['A' => 'a']], $po->toArray());
	}

	public function testPoFile2moFileReturnsTheSaveFailure()
	{
		$dir = $this->makeTempDirectory();
		$po = new TGettext_PO();
		$po->fromArray(['meta' => [], 'strings' => ['Hello' => 'Hallo']]);
		$po->save("$dir/m.po");
		$this->assertFalse(TGettext::poFile2moFile("$dir/m.po", "$dir/missing/m.mo"));
	}

	public function testMeta2arrayOfOneLineWithoutAColon()
	{
		$this->assertSame([], TGettext::meta2array('no colon'));
	}
}
