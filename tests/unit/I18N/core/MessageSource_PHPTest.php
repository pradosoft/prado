<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\Util\Clock\TMockClock;
use Prado\Exceptions\TException;
use Prado\Exceptions\TIOException;
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\MessageSource_PHP;
use Prado\I18N\core\TMessageSourceIOException;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A MessageCache that records the variants it cleans.
 */
class MessageSource_PHPRecordingCache extends MessageCache
{
	public array $cleaned = [];

	public function clean($catalogue, $culture)
	{
		$this->cleaned[] = [$catalogue, $culture];
		parent::clean($catalogue, $culture);
	}
}

class MessageSource_PHPTest extends TestCase
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

	private function createSource(string $culture = 'en_US', ?string $dir = null): MessageSource_PHP
	{
		$source = new MessageSource_PHP($dir ?? $this->_dir);
		$source->setCulture($culture);
		return $source;
	}

	/**
	 * Writes a PHP catalogue file with the given trans-units.
	 */
	private function writeCatalogue(string $file, array $units): string
	{
		$path = $this->_dir . '/' . $file;
		if (!is_dir(dirname($path))) {
			mkdir(dirname($path), 0o777, true);
		}
		$php = ['info' => ['source-language' => 'EN', 'original' => 'messages'], 'trans-unit' => $units];
		file_put_contents($path, "<?php\nreturn " . var_export($php, true) . ';');
		return $path;
	}

	/**
	 * Returns the trans-units of a catalogue file.
	 */
	private function readUnits(string $file): array
	{
		return (include $this->_dir . '/' . $file)['trans-unit'];
	}

	public function testConstructorCastsTheSourceToString()
	{
		$this->assertSame('12', PradoUnit::getProp(new MessageSource_PHP(12), 'source'));
	}

	public function testLoadReadsTheTransUnits()
	{
		$this->writeCatalogue('messages.php', [
			4 => ['source' => 'Hello', 'target' => 'Howdy', 'note' => 'Greeting'],
			5 => ['source' => 'Bye', 'target' => ''],
		]);
		$source = $this->createSource('');

		$this->assertTrue($source->load());

		$this->assertSame([
			'messages.php' => [
				'Hello' => ['Howdy', '4', 'Greeting'],
				'Bye' => ['', '5', ''],
			],
		], $source->read());
	}

	public function testLoadReadsOneTransUnit()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Howdy', 'note' => 'Greeting']]);
		$source = $this->createSource('');

		$source->load();

		$this->assertSame(['messages.php' => ['Hello' => ['Howdy', '0', 'Greeting']]], $source->read());
	}

	public function testLoadReadsAnEmptyCatalogue()
	{
		$this->writeCatalogue('messages.php', []);
		$source = $this->createSource('');

		$source->load();

		$this->assertSame(['messages.php' => []], $source->read());
	}

	public function testLoadReadsTheVariantsByFileNameAndByDirectory()
	{
		$this->writeCatalogue('en_US/messages.php', [['source' => 'A', 'target' => 'dir en_US']]);
		$this->writeCatalogue('en/messages.php', [['source' => 'B', 'target' => 'dir en']]);
		$this->writeCatalogue('messages.en_US.php', [['source' => 'C', 'target' => 'file en_US']]);
		$this->writeCatalogue('messages.en.php', [['source' => 'D', 'target' => 'file en']]);
		$this->writeCatalogue('messages.php', [['source' => 'E', 'target' => 'default']]);
		$this->writeCatalogue('messages.fr.php', [['source' => 'F', 'target' => 'french']]);
		$source = $this->createSource();

		$source->load();

		$this->assertSame([
			'en_US/messages.php' => ['A' => ['dir en_US', '0', '']],
			'en/messages.php' => ['B' => ['dir en', '0', '']],
			'messages.en_US.php' => ['C' => ['file en_US', '0', '']],
			'messages.en.php' => ['D' => ['file en', '0', '']],
			'messages.php' => ['E' => ['default', '0', '']],
		], $source->read());
	}

	public function testLoadReadsTheCachedMessages()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Howdy']]);
		$cache = new MessageCache($this->makeTempDirectory());
		$source = $this->createSource('');
		$source->setCache($cache);
		$source->load();

		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Changed']]);
		touch($this->_dir . '/messages.php', time() - 3600);
		$source->load();

		$this->assertSame(['messages.php' => ['Hello' => ['Howdy', '0', '']]], $source->read());
	}

	public function testLoadSkipsAFileReturningFalse()
	{
		file_put_contents($this->_dir . '/messages.php', '<?php return false;');
		$source = $this->createSource('');

		$this->assertTrue($source->load());
		$this->assertSame([], $source->read());
	}

	public function testGetCatalogueListOrdersTheVariants()
	{
		$this->assertSame([
			'en_US/messages.php',
			'en/messages.php',
			'messages.en_US.php',
			'messages.en.php',
			'messages.php',
		], PradoUnit::invoke($this->createSource(), 'getCatalogueList', 'messages'));

		$this->assertSame(
			['en/app.php', 'app.en.php', 'app.php'],
			PradoUnit::invoke($this->createSource('en'), 'getCatalogueList', 'app')
		);

		$this->assertSame(['messages.php'], PradoUnit::invoke($this->createSource(''), 'getCatalogueList', 'messages'));
	}

	public function testGetCatalogueListSkipsEmptyCultureParts()
	{
		$this->assertSame([
			'en_US/messages.php',
			'en/messages.php',
			'messages.en_US.php',
			'messages.en.php',
			'messages.php',
		], PradoUnit::invoke($this->createSource('en__US'), 'getCatalogueList', 'messages'));
	}

	public function testGetLastModified()
	{
		$file = $this->writeCatalogue('messages.php', []);
		touch($file, 1000000);
		$source = $this->createSource();

		$this->assertSame(1000000, PradoUnit::invoke($source, 'getLastModified', $file));
		$this->assertSame(0, PradoUnit::invoke($source, 'getLastModified', $this->_dir . '/missing.php'));
	}

	public function testCataloguesListsTheFilesAndDirectories()
	{
		$this->writeCatalogue('messages.php', []);
		$this->writeCatalogue('messages.fr_FR.php', []);
		$this->writeCatalogue('app.de.php', []);
		$this->writeCatalogue('en_US/messages.php', []);
		$this->writeCatalogue('en_US/tags.en.php', []);
		$this->writeCatalogue('misc/other.php', []);
		file_put_contents($this->_dir . '/notes.txt', '');
		file_put_contents($this->_dir . '/backup.php.bak', '');
		file_put_contents($this->_dir . '/.php', '');

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
		$this->writeCatalogue('messages.php', []);

		$this->assertSame([['messages', null]], $this->createSource()->catalogues());
	}

	public function testSaveWithoutUntranslatedMessages()
	{
		$this->assertFalse($this->createSource()->save());
		$this->assertSame(['.', '..'], scandir($this->_dir));
	}

	public function testSaveAppendsToTheVariantFile()
	{
		$this->writeCatalogue('messages.en.php', [['source' => 'Hello', 'target' => 'Howdy']]);
		$cache = new MessageSource_PHPRecordingCache($this->makeTempDirectory());
		$source = $this->createSource();
		$source->setCache($cache);
		$source->append('One');
		$source->append("Two's & <three>");

		$this->assertTrue($source->save());

		$this->assertSame([
			['source' => 'Hello', 'target' => 'Howdy'],
			['source' => 'One', 'target' => ''],
			['source' => "Two's & <three>", 'target' => ''],
		], $this->readUnits('messages.en.php'));
		$this->assertArrayHasKey('date', (include $this->_dir . '/messages.en.php')['info']);
		$this->assertSame([['messages.en.php', 'en_US']], $cache->cleaned);
	}

	public function testSaveCreatesTheTemplateInTheCultureDirectory()
	{
		$source = $this->createSource();
		$source->append('Hello');

		$this->assertTrue($source->save('app'));

		$this->assertSame([['source' => 'Hello', 'target' => '']], $this->readUnits('en_US/app.php'));
		$source->load('app');
		$this->assertSame(['en_US/app.php' => ['Hello' => ['', '0', '']]], $source->read());
	}

	public function testSaveCreatesTheTemplateInTheSourceDirectory()
	{
		$source = $this->createSource('', $this->_dir . '/new');
		$source->append('Hello');

		$this->assertTrue($source->save(null));

		$this->assertSame([['source' => 'Hello', 'target' => '']], $this->readUnits('new/messages.php'));
	}

	public function testSaveRejectsAReadOnlyFile()
	{
		chmod($this->writeCatalogue('messages.php', []), 0o444);
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
			['en/app.php', $this->_dir . '/en/app.php'],
			PradoUnit::invoke($this->createSource('en'), 'createMessageTemplate', 'app')
		);
		$this->assertSame([], $this->readUnits('en/app.php'));
	}

	public function testCreateMessageTemplateDefaultsTheCatalogue()
	{
		$source = $this->createSource('');

		$this->assertSame(
			['messages.php', $this->_dir . '/messages.php'],
			PradoUnit::invoke($source, 'createMessageTemplate', null)
		);
		$this->assertFileExists($this->_dir . '/messages.php');
	}

	public function testGetTemplate()
	{
		$file = $this->_dir . '/template.php';
		file_put_contents($file, PradoUnit::invoke($this->createSource('fr_FR'), 'getTemplate', 'app'));

		$php = include $file;

		$this->assertSame(['source-language', 'target-language', 'original', 'date'], array_keys($php['info']));
		$this->assertSame('EN', $php['info']['source-language']);
		$this->assertSame('fr_FR', $php['info']['target-language']);
		$this->assertSame('app', $php['info']['original']);
		$this->assertSame([], $php['trans-unit']);
	}

	public function testUpdateWithoutAFile()
	{
		$this->assertFalse($this->createSource()->update('Hello', 'Howdy', ''));
	}

	public function testUpdateSetsTheTargetAndNote()
	{
		$this->writeCatalogue('messages.php', [
			['source' => 'Bye', 'target' => 'Later'],
			['source' => 'Hello', 'target' => 'Hi', 'note' => 'Old'],
			['source' => 'Hello', 'target' => 'Third'],
		]);
		$cache = new MessageSource_PHPRecordingCache($this->makeTempDirectory());
		$source = $this->createSource('');
		$source->setCache($cache);

		$this->assertTrue($source->update('Hello', 'Howdy', 'New'));

		$this->assertSame([
			['source' => 'Bye', 'target' => 'Later'],
			['source' => 'Hello', 'target' => 'Howdy', 'note' => 'New'],
			['source' => 'Hello', 'target' => 'Third'],
		], $this->readUnits('messages.php'));
		$this->assertSame([['messages.php', '']], $cache->cleaned);
	}

	public function testUpdateKeepsTheNoteWithoutComments()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Hi', 'note' => 'Old']]);

		$this->assertTrue($this->createSource('')->update('Hello', 'Howdy', ''));

		$this->assertSame([['source' => 'Hello', 'target' => 'Howdy', 'note' => 'Old']], $this->readUnits('messages.php'));
	}

	public function testUpdateSetsTheTargetAndNoteOfOneUnit()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Hi']]);

		$this->assertTrue($this->createSource('')->update('Hello', 'Howdy', 'New'));

		$this->assertSame([['source' => 'Hello', 'target' => 'Howdy', 'note' => 'New']], $this->readUnits('messages.php'));
	}

	public function testUpdateKeepsTheUnitsOfAnUnknownMessage()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Hi']]);

		$this->createSource('')->update('Bye', 'Later', 'New');

		$this->assertSame([['source' => 'Hello', 'target' => 'Hi']], $this->readUnits('messages.php'));
	}

	public function testUpdateKeepsAnEmptyCatalogue()
	{
		$this->writeCatalogue('messages.php', []);

		$this->createSource('')->update('Bye', 'Later', 'New');

		$this->assertSame([], $this->readUnits('messages.php'));
	}

	public function testUpdateRejectsAReadOnlyFile()
	{
		chmod($this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Hi']]), 0o444);

		$this->expectException(TIOException::class);
		$this->createSource('')->update('Hello', 'Howdy', '');
	}

	public function testDeleteWithoutAFile()
	{
		$this->assertFalse($this->createSource()->delete('Hello'));
	}

	public function testDeleteRemovesTheUnit()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Bye', 'target' => 'Later'], ['source' => 'Hello', 'target' => 'Hi']]);
		$cache = new MessageSource_PHPRecordingCache($this->makeTempDirectory());
		$source = $this->createSource('');
		$source->setCache($cache);

		$this->assertTrue($source->delete('Hello'));

		$this->assertSame([['source' => 'Bye', 'target' => 'Later']], $this->readUnits('messages.php'));
		$this->assertSame([['messages.php', '']], $cache->cleaned);
	}

	public function testDeleteRemovesTheOnlyUnit()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Hi']]);

		$this->assertTrue($this->createSource('')->delete('Hello', null));

		$this->assertSame([], $this->readUnits('messages.php'));
	}

	public function testDeleteAnUnknownMessage()
	{
		$this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Hi']]);

		$this->assertFalse($this->createSource('')->delete('Bye'));

		$this->assertSame([['source' => 'Hello', 'target' => 'Hi']], $this->readUnits('messages.php'));
	}

	public function testDeleteFromAnEmptyCatalogue()
	{
		$this->writeCatalogue('messages.php', []);

		$this->assertFalse($this->createSource('')->delete('Bye'));
	}

	public function testDeleteRejectsAReadOnlyFile()
	{
		chmod($this->writeCatalogue('messages.php', [['source' => 'Hello', 'target' => 'Hi']]), 0o444);

		$this->expectException(TIOException::class);
		$this->createSource('')->delete('Hello');
	}

	public function testLoadReadsAFileWithoutTransUnits()
	{
		file_put_contents($this->_dir . '/messages.php', '<?php return ["info" => []];');
		$source = $this->createSource('');
		$this->assertTrue($source->load());
		$this->assertSame(['messages.php' => []], $source->read());
	}

	public function testLoadWithoutACulture()
	{
		$this->writeCatalogue('messages.php', []);
		$source = new MessageSource_PHP($this->_dir);
		$this->assertTrue($source->load());
		$this->assertSame(['messages.php' => []], $source->read());
	}

	public function testCataloguesOfAMissingDirectory()
	{
		$this->assertSame([], $this->createSource('', $this->_dir . '/missing')->catalogues());
	}

	public function testCataloguesSplitsTheExtensionAtTheEnd()
	{
		$this->writeCatalogue('app.php.fr.php', []);
		$this->assertSame([['app.php', 'fr']], $this->createSource('')->catalogues());
	}

	public function testSaveTwiceWritesTheMessagesOnce()
	{
		$source = $this->createSource('');
		$source->append('A');
		$this->assertTrue($source->save());
		$this->assertFalse($source->save());
		$this->assertCount(1, $this->readUnits('messages.php'));
	}

	public function testUpdateAnUnknownMessageLeavesTheFile()
	{
		$path = $this->writeCatalogue('messages.php', [['source' => 'A', 'target' => 'a']]);
		$before = file_get_contents($path);
		$this->assertFalse($this->createSource('')->update('B', 'b', ''));
		$this->assertSame($before, file_get_contents($path));
	}

	public function testUpdateAndDeleteCompareTheSourceStrictly()
	{
		$this->writeCatalogue('messages.php', [['source' => '10', 'target' => 'ten']]);
		$source = $this->createSource('');
		$this->assertFalse($source->update('1e1', 'x', ''));
		$this->assertFalse($source->delete('1e1'));
		$this->assertFalse($source->delete('10.0'));
		$this->assertSame([['source' => '10', 'target' => 'ten']], $this->readUnits('messages.php'));
		$this->assertTrue($source->delete('10'));
	}

	public function testDatesComeFromTheClock()
	{
		$source = $this->createSource('fr');
		$source->setClock((new TMockClock())->setNow(new \DateTimeImmutable('2024-01-15T10:30:00+02:00')));
		$this->assertStringContainsString("'date' => '2024-01-15T08:30:00Z'", PradoUnit::invoke($source, 'getTemplate', 'messages'));
		$source->append('Hello');
		$this->assertTrue($source->save());
		$this->assertSame('2024-01-15T08:30:00Z', (include $this->_dir . '/fr/messages.php')['info']['date']);
	}
}
