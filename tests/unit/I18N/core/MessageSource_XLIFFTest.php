<?php

namespace Prado\Test\Unit\I18N\core;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use Prado\Util\Clock\TMockClock;
use Prado\Exceptions\TException;
use Prado\Exceptions\TIOException;
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\MessageSource_XLIFF;
use Prado\I18N\core\TMessageSourceIOException;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A MessageCache that records the variants it cleans.
 */
class MessageSource_XLIFFRecordingCache extends MessageCache
{
	public array $cleaned = [];

	public function clean($catalogue, $culture)
	{
		$this->cleaned[] = [$catalogue, $culture];
		parent::clean($catalogue, $culture);
	}
}

class MessageSource_XLIFFTest extends TestCase
{
	use TTempDirectoryTrait;

	private string $_dir;

	protected function setUp(): void
	{
		$this->_dir = $this->makeTempDirectory();
	}

	protected function tearDown(): void
	{
		$this->removeTempDirectories();
	}

	private function createSource(string $culture = 'en_US', ?string $dir = null): MessageSource_XLIFF
	{
		$source = new MessageSource_XLIFF($dir ?? $this->_dir);
		$source->setCulture($culture);
		return $source;
	}

	/**
	 * Writes an XLIFF file with one trans-unit per element of $units.
	 * @param array $units each unit is [id, source, target] or [id, source, target, note]; a null target omits the element.
	 */
	private function writeXliff(string $file, array $units, bool $withFile = true): string
	{
		$body = '';
		foreach ($units as $unit) {
			$body .= "\n<trans-unit id=\"{$unit[0]}\">\n<source>{$unit[1]}</source>\n";
			if ($unit[2] !== null) {
				$body .= "<target>{$unit[2]}</target>\n";
			}
			if (isset($unit[3])) {
				$body .= "<note>{$unit[3]}</note>\n";
			}
			$body .= "</trans-unit>\n";
		}
		$body = "<body>$body</body>";
		if ($withFile) {
			$body = "<file source-language=\"EN\" original=\"messages\">$body</file>";
		}
		$path = $this->_dir . '/' . $file;
		if (!is_dir(dirname($path))) {
			mkdir(dirname($path), 0o777, true);
		}
		file_put_contents($path, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<xliff version=\"1.0\">$body</xliff>");
		return $path;
	}

	/**
	 * Returns each trans-unit of a file as [id, source, target, note]; a missing element is null.
	 */
	private function readUnits(string $file): array
	{
		$dom = new DOMDocument();
		$dom->load($this->_dir . '/' . $file);
		$units = [];
		foreach ($dom->getElementsByTagName('trans-unit') as $unit) {
			$value = fn ($name) => $unit->getElementsByTagName($name)->item(0)?->textContent;
			$units[] = [$unit->getAttribute('id'), $value('source'), $value('target'), $value('note')];
		}
		return $units;
	}

	public function testConstructorCastsTheSourceToString()
	{
		$this->assertSame('12', PradoUnit::getProp(new MessageSource_XLIFF(12), 'source'));
	}

	public function testLoadReadsTheTransUnits()
	{
		$this->writeXliff('messages.xml', [
			[1, 'Hello', 'Howdy', 'Greeting'],
			[2, 'Bye', ''],
		]);
		$source = $this->createSource('');

		$this->assertTrue($source->load());

		$this->assertSame([
			'messages.xml' => [
				'Hello' => ['Howdy', '1', 'Greeting'],
				'Bye' => ['', '2', ''],
			],
		], $source->read());
	}

	public function testLoadReadsAnEmptyCatalogue()
	{
		$this->writeXliff('messages.xml', []);
		$source = $this->createSource('');

		$source->load();

		$this->assertSame(['messages.xml' => []], $source->read());
	}

	public function testLoadReadsTheVariantsByFileNameAndByDirectory()
	{
		$this->writeXliff('en_US/messages.xml', [[1, 'A', 'dir en_US']]);
		$this->writeXliff('en/messages.xlf', [[1, 'B', 'dir en']]);
		$this->writeXliff('messages.en_US.xlf', [[1, 'C', 'file en_US']]);
		$this->writeXliff('messages.en.xml', [[1, 'D', 'file en']]);
		$this->writeXliff('messages.xml', [[1, 'E', 'default']]);
		$this->writeXliff('messages.fr.xml', [[1, 'F', 'french']]);

		$source = $this->createSource();
		$source->load();

		$this->assertSame([
			'en_US/messages.xml' => ['A' => ['dir en_US', '1', '']],
			'en/messages.xlf' => ['B' => ['dir en', '1', '']],
			'messages.en_US.xlf' => ['C' => ['file en_US', '1', '']],
			'messages.en.xml' => ['D' => ['file en', '1', '']],
			'messages.xml' => ['E' => ['default', '1', '']],
		], $source->read());
	}

	public function testLoadReadsTheCachedMessages()
	{
		$this->writeXliff('messages.xml', [[1, 'Hello', 'Howdy']]);
		$cache = new MessageCache($this->makeTempDirectory());
		$source = $this->createSource('');
		$source->setCache($cache);
		$source->load();

		$this->writeXliff('messages.xml', [[1, 'Hello', 'Changed']]);
		touch($this->_dir . '/messages.xml', time() - 3600);
		$source->load();

		$this->assertSame(['messages.xml' => ['Hello' => ['Howdy', '1', '']]], $source->read());
	}

	public function testLoadSkipsAnUnreadableFile()
	{
		file_put_contents($this->_dir . '/messages.xml', 'not xml');
		$source = $this->createSource('');

		$this->assertTrue($source->load());
		$this->assertSame([], $source->read());
	}

	public function testGetCatalogueListOrdersTheVariants()
	{
		$this->assertSame([
			'en_US/messages.xml', 'en_US/messages.xlf',
			'en/messages.xml', 'en/messages.xlf',
			'messages.en_US.xml', 'messages.en_US.xlf',
			'messages.en.xml', 'messages.en.xlf',
			'messages.xml', 'messages.xlf',
		], PradoUnit::invoke($this->createSource(), 'getCatalogueList', 'messages'));

		$this->assertSame(
			['en/app.xml', 'en/app.xlf', 'app.en.xml', 'app.en.xlf', 'app.xml', 'app.xlf'],
			PradoUnit::invoke($this->createSource('en'), 'getCatalogueList', 'app')
		);

		$this->assertSame(
			['messages.xml', 'messages.xlf'],
			PradoUnit::invoke($this->createSource(''), 'getCatalogueList', 'messages')
		);
	}

	public function testGetCatalogueListSkipsEmptyCultureParts()
	{
		$this->assertSame([
			'en_US/messages.xml', 'en_US/messages.xlf',
			'en/messages.xml', 'en/messages.xlf',
			'messages.en_US.xml', 'messages.en_US.xlf',
			'messages.en.xml', 'messages.en.xlf',
			'messages.xml', 'messages.xlf',
		], PradoUnit::invoke($this->createSource('en__US'), 'getCatalogueList', 'messages'));
	}

	public function testGetLastModified()
	{
		$file = $this->writeXliff('messages.xml', []);
		touch($file, 1000000);
		$source = $this->createSource();

		$this->assertSame(1000000, PradoUnit::invoke($source, 'getLastModified', $file));
		$this->assertSame(0, PradoUnit::invoke($source, 'getLastModified', $this->_dir . '/missing.xml'));
	}

	public function testCataloguesListsTheFilesAndDirectories()
	{
		$this->writeXliff('messages.xml', []);
		$this->writeXliff('messages.fr_FR.xml', []);
		$this->writeXliff('app.de.xml', []);
		$this->writeXliff('en_US/messages.xml', []);
		$this->writeXliff('en_US/tags.en.xml', []);
		$this->writeXliff('misc/other.xml', []);
		file_put_contents($this->_dir . '/notes.txt', '');
		file_put_contents($this->_dir . '/backup.xml.bak', '');
		file_put_contents($this->_dir . '/.xml', '');

		$this->assertSame([
			['app', 'de'],
			['messages', null],
			['messages', 'en_US'],
			['messages', 'fr_FR'],
			['tags', 'en'],
		], $this->createSource()->catalogues());
	}

	public function testCataloguesListsOneFile()
	{
		$this->writeXliff('messages.xml', []);

		$this->assertSame([['messages', null]], $this->createSource()->catalogues());
	}

	public function testSaveWithoutUntranslatedMessages()
	{
		$this->assertFalse($this->createSource()->save());
		$this->assertSame(['.', '..'], scandir($this->_dir));
	}

	public function testSaveAppendsToTheVariantFile()
	{
		$this->writeXliff('messages.en.xml', [[1, 'Hello', 'Howdy'], [7, 'Bye', 'Later']]);
		$cache = new MessageSource_XLIFFRecordingCache($this->makeTempDirectory());
		$source = $this->createSource();
		$source->setCache($cache);
		$source->append('One');
		$source->append('Two & <three>');

		$this->assertTrue($source->save());

		$this->assertSame([
			['1', 'Hello', 'Howdy', null],
			['7', 'Bye', 'Later', null],
			['8', 'One', '', null],
			['9', 'Two & <three>', '', null],
		], $this->readUnits('messages.en.xml'));
		$this->assertSame([['messages.en.xml', 'en_US']], $cache->cleaned);
		$dom = new DOMDocument();
		$dom->load($this->_dir . '/messages.en.xml');
		$this->assertTrue($dom->documentElement->getElementsByTagName('file')->item(0)->hasAttribute('date'));
	}

	public function testSaveAppendsToAFileWithoutAFileElement()
	{
		$this->writeXliff('messages.xml', [[1, 'Hello', 'Howdy']], false);
		$source = $this->createSource('');
		$source->append('Bye');

		$this->assertTrue($source->save(null));

		$this->assertSame([['1', 'Hello', 'Howdy', null], ['2', 'Bye', '', null]], $this->readUnits('messages.xml'));
	}

	public function testSaveCreatesTheTemplateInTheCultureDirectory()
	{
		$source = $this->createSource();
		$source->append('Hello');

		$this->assertTrue($source->save('app'));

		$this->assertSame([['1', 'Hello', '', null]], $this->readUnits('en_US/app.xml'));
		$source->load('app');
		$this->assertSame(['en_US/app.xml' => ['Hello' => ['', '1', '']]], $source->read());
	}

	public function testSaveCreatesTheTemplateInTheSourceDirectory()
	{
		$dir = $this->_dir . '/new';
		$source = $this->createSource('', $dir);
		$source->append('Hello');

		$this->assertTrue($source->save(null));

		$this->assertSame([['1', 'Hello', '', null]], $this->readUnits('new/messages.xml'));
	}

	public function testSaveRejectsAReadOnlyFile()
	{
		$file = $this->writeXliff('messages.xml', []);
		chmod($file, 0o444);
		$source = $this->createSource('');
		$source->append('Hello');

		$this->expectException(TIOException::class);
		$source->save();
	}

	public function testCreateMessageTemplateRejectsADirectoryItCannotCreate()
	{
		$file = $this->_dir . '/file';
		file_put_contents($file, '');
		$source = $this->createSource('en', $file);

		$this->expectException(TMessageSourceIOException::class);
		$this->expectExceptionMessage("Unable to create the message catalogue directory '$file/en'");
		PradoUnit::invoke($source, 'createMessageTemplate', 'messages');
	}

	public function testCreateMessageTemplateDefaultsTheCatalogueOfADirectoryItCannotCreate()
	{
		$file = $this->_dir . '/file';
		file_put_contents($file, '');
		$source = $this->createSource('en', $file);

		$this->expectException(TMessageSourceIOException::class);
		$this->expectExceptionMessage("Unable to create the message catalogue directory '$file/en'");
		PradoUnit::invoke($source, 'createMessageTemplate', null);
	}

	public function testCreateMessageTemplateInAnExistingDirectory()
	{
		mkdir($this->_dir . '/en');

		$this->assertSame(
			['en/app.xml', $this->_dir . '/en/app.xml'],
			PradoUnit::invoke($this->createSource('en'), 'createMessageTemplate', 'app')
		);
		$this->assertSame([], $this->readUnits('en/app.xml'));
	}

	public function testCreateMessageTemplateDefaultsTheCatalogue()
	{
		$source = $this->createSource('');

		$this->assertSame(
			['messages.xml', $this->_dir . '/messages.xml'],
			PradoUnit::invoke($source, 'createMessageTemplate', null)
		);
		$this->assertFileExists($this->_dir . '/messages.xml');
	}

	public function testGetTemplate()
	{
		$dom = new DOMDocument();
		$this->assertTrue($dom->loadXML(PradoUnit::invoke($this->createSource('fr_FR'), 'getTemplate', 'app')));

		$file = $dom->getElementsByTagName('file')->item(0);
		$this->assertSame('xliff', $dom->documentElement->nodeName);
		$this->assertSame('1.0', $dom->documentElement->getAttribute('version'));
		$this->assertSame('EN', $file->getAttribute('source-language'));
		$this->assertSame('fr_FR', $file->getAttribute('target-language'));
		$this->assertSame('plaintext', $file->getAttribute('datatype'));
		$this->assertSame('app', $file->getAttribute('original'));
		$this->assertSame('app', $file->getAttribute('product-name'));
		$this->assertSame(1, $dom->getElementsByTagName('body')->length);
		$this->assertSame(0, $dom->getElementsByTagName('trans-unit')->length);
	}

	public function testUpdateWithoutAFile()
	{
		$this->assertFalse($this->createSource()->update('Hello', 'Howdy', ''));
	}

	public function testUpdateReplacesTheTargetAndNote()
	{
		$this->writeXliff('messages.xml', [[1, 'Bye', 'Later'], [2, 'Hello', 'Hi', 'Old'], [3, 'Hello', 'Third']]);
		$cache = new MessageSource_XLIFFRecordingCache($this->makeTempDirectory());
		$source = $this->createSource('');
		$source->setCache($cache);

		$this->assertTrue($source->update('Hello', 'Howdy', 'New'));

		$this->assertSame([
			['1', 'Bye', 'Later', null],
			['2', 'Hello', 'Howdy', 'New'],
			['3', 'Hello', 'Third', null],
		], $this->readUnits('messages.xml'));
		$this->assertSame([['messages.xml', '']], $cache->cleaned);
	}

	public function testUpdateKeepsTheNoteWithoutComments()
	{
		$this->writeXliff('messages.xml', [[1, 'Hello', 'Hi', 'Old']]);

		$this->assertTrue($this->createSource('')->update('Hello', 'Howdy', ''));

		$this->assertSame([['1', 'Hello', 'Howdy', 'Old']], $this->readUnits('messages.xml'));
	}

	public function testUpdateAppendsTheTargetAndNote()
	{
		$this->writeXliff('messages.xml', [[1, 'Hello', null]]);

		$this->assertTrue($this->createSource('')->update('Hello', 'Howdy', 'New'));

		$this->assertSame([['1', 'Hello', 'Howdy', 'New']], $this->readUnits('messages.xml'));
	}

	public function testUpdateAppendsTheTargetWithoutComments()
	{
		$this->writeXliff('messages.xml', [[1, 'Hello', null]]);

		$this->assertTrue($this->createSource('')->update('Hello', 'Howdy', ''));

		$this->assertSame([['1', 'Hello', 'Howdy', null]], $this->readUnits('messages.xml'));
	}

	public function testUpdateRejectsAReadOnlyFile()
	{
		chmod($this->writeXliff('messages.xml', [[1, 'Hello', 'Hi']]), 0o444);

		$this->expectException(TIOException::class);
		$this->createSource('')->update('Hello', 'Howdy', '');
	}

	public function testDeleteWithoutAFile()
	{
		$this->assertFalse($this->createSource()->delete('Hello'));
	}

	public function testDeleteRemovesTheUnit()
	{
		$this->writeXliff('messages.xml', [[1, 'Bye', 'Later'], [2, 'Hello', 'Hi']]);
		$cache = new MessageSource_XLIFFRecordingCache($this->makeTempDirectory());
		$source = $this->createSource('');
		$source->setCache($cache);

		$this->assertTrue($source->delete('Hello'));

		$this->assertSame([['1', 'Bye', 'Later', null]], $this->readUnits('messages.xml'));
		$this->assertSame([['messages.xml', '']], $cache->cleaned);
	}

	public function testDeleteRemovesTheUnitWithoutCache()
	{
		$this->writeXliff('messages.xml', [[1, 'Hello', 'Hi']], false);

		$this->assertTrue($this->createSource('')->delete('Hello', null));

		$this->assertSame([], $this->readUnits('messages.xml'));
	}

	public function testDeleteAnUnknownMessage()
	{
		$this->writeXliff('messages.xml', [[1, 'Hello', 'Hi']]);

		$this->assertFalse($this->createSource('')->delete('Bye'));

		$this->assertSame([['1', 'Hello', 'Hi', null]], $this->readUnits('messages.xml'));
	}

	public function testDeleteRejectsAReadOnlyFile()
	{
		chmod($this->writeXliff('messages.xml', [[1, 'Hello', 'Hi']]), 0o444);

		$this->expectException(TIOException::class);
		$this->createSource('')->delete('Hello');
	}

	public function testLoadWithoutACulture()
	{
		$this->writeXliff('messages.xml', []);
		$source = new MessageSource_XLIFF($this->_dir);
		$this->assertTrue($source->load());
		$this->assertSame(['messages.xml' => []], $source->read());
	}

	public function testCataloguesListsTheXlfFiles()
	{
		$this->writeXliff('messages.fr.xlf', []);
		$this->writeXliff('de/app.xlf', []);
		$this->assertSame([['app', 'de'], ['messages', 'fr']], $this->createSource('')->catalogues());
	}

	public function testCataloguesOfAMissingDirectory()
	{
		$this->assertSame([], $this->createSource('', $this->_dir . '/missing')->catalogues());
	}

	public function testSaveTwiceWritesTheMessagesOnce()
	{
		$source = $this->createSource('');
		$source->append('A');
		$this->assertTrue($source->save());
		$this->assertFalse($source->save());
		$this->assertCount(1, $this->readUnits('messages.xml'));
	}

	public function testUpdateAndDeleteASavedMessage()
	{
		$source = $this->createSource('');
		$source->append('Hello');
		$source->save();
		$this->assertTrue($source->update('Hello', 'Hi', ''));
		$this->assertSame('Hi', $this->readUnits('messages.xml')[0][2]);
		$this->assertTrue($source->delete('Hello'));
		$this->assertSame([], $this->readUnits('messages.xml'));
	}

	public function testUpdateEscapesTheTargetAndNote()
	{
		$this->writeXliff('messages.xml', [[1, 'A', 'x', 'n'], [2, 'B', null]]);
		$source = $this->createSource('');
		$this->assertTrue($source->update('A', 'Tom & Jerry', 'a & b'));
		$this->assertTrue($source->update('B', 'Tom & Jerry', 'a & b'));
		$this->assertSame([['1', 'A', 'Tom & Jerry', 'a & b'], ['2', 'B', 'Tom & Jerry', 'a & b']], $this->readUnits('messages.xml'));
	}

	public function testUpdateAnUnknownMessageLeavesTheFile()
	{
		$path = $this->writeXliff('messages.xml', [[1, 'A', 'a']]);
		$before = file_get_contents($path);
		$this->assertFalse($this->createSource('')->update('B', 'b', ''));
		$this->assertSame($before, file_get_contents($path));
	}

	public function testUpdateAndDeleteOfAnEmptySource()
	{
		$this->writeXliff('messages.xml', [[1, '', 'empty'], [2, 'B', 'b']]);
		$source = $this->createSource('');
		$this->assertTrue($source->update('B', 'bee', ''));
		$this->assertTrue($source->delete('B'));
		$this->assertSame([['1', '', 'empty', null]], $this->readUnits('messages.xml'));
	}

	public function testUpdateAndDeleteCompareTheSourceStrictly()
	{
		$this->writeXliff('messages.xml', [[1, '10', 'ten']]);
		$source = $this->createSource('');
		$this->assertFalse($source->update('1e1', 'x', ''));
		$this->assertFalse($source->delete('1e1'));
		$this->assertSame([['1', '10', 'ten', null]], $this->readUnits('messages.xml'));
	}

	public function testDatesComeFromTheClock()
	{
		$source = $this->createSource('fr');
		$source->setClock((new TMockClock())->setNow(new \DateTimeImmutable('2024-01-15T10:30:00+02:00')));
		$source->append('Hello');
		$this->assertTrue($source->save());
		$xml = file_get_contents($this->_dir . '/fr/messages.xml');
		$this->assertStringContainsString('date="2024-01-15T08:30:00Z"', $xml);
		$this->assertStringNotContainsString('2024-01-15T10:30:00+02:00', $xml);
		$this->assertStringContainsString('date="2024-01-15T10:30:00+02:00"', PradoUnit::invoke($source, 'getTemplate', 'messages'));

		$source->setClock((new TMockClock())->setNow(new \DateTimeImmutable('2024-02-01T00:00:00Z')));
		$this->assertTrue($source->update('Hello', 'Bonjour', ''));
		$this->assertStringContainsString('date="2024-02-01T00:00:00Z"', file_get_contents($this->_dir . '/fr/messages.xml'));
		$source->setClock((new TMockClock())->setNow(new \DateTimeImmutable('2024-03-01T00:00:00Z')));
		$this->assertTrue($source->delete('Hello'));
		$this->assertStringContainsString('date="2024-03-01T00:00:00Z"', file_get_contents($this->_dir . '/fr/messages.xml'));
	}
}
