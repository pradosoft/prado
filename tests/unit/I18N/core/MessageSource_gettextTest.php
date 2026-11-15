<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\Util\Clock\TMockClock;
use Prado\Exceptions\TException;
use Prado\Exceptions\TIOException;
use Prado\I18N\core\Gettext\TGettext_MO;
use Prado\I18N\core\Gettext\TGettext_PO;
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\MessageSource;
use Prado\I18N\core\MessageSource_gettext;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

class MessageSource_gettextTest extends TestCase
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

	/**
	 * Returns a gettext source in the test directory.
	 * @param ?string $culture the culture, or null to leave it unset.
	 * @param ?string $dir the source directory, or null for the test directory.
	 * @return MessageSource_gettext the source.
	 */
	private function source(?string $culture = 'en_AU', ?string $dir = null): MessageSource_gettext
	{
		$source = MessageSource::factory('gettext', $dir ?? $this->_dir);
		if ($culture !== null) {
			$source->setCulture($culture);
		}
		return $source;
	}

	/**
	 * Writes an MO file without meta and its PO file with meta.
	 * @param string $mofile the MO file path, relative to the test directory.
	 * @param array $strings the [msgid => msgstr] strings.
	 * @param bool $withPo whether to write the PO file.
	 * @param bool $withMo whether to write the MO file.
	 */
	private function writeCatalogue(string $mofile, array $strings, bool $withPo = true, bool $withMo = true): void
	{
		$path = $this->_dir . '/' . $mofile;
		if (!is_dir(dirname($path))) {
			mkdir(dirname($path), 0o777, true);
		}
		if ($withMo) {
			$mo = new TGettext_MO();
			$mo->fromArray(['meta' => [], 'strings' => $strings]);
			$mo->save($path);
		}
		if ($withPo) {
			$po = new TGettext_PO();
			$po->fromArray(['meta' => ['Language' => 'en'], 'strings' => $strings]);
			$po->save(substr($path, 0, -3) . '.po');
		}
	}

	/**
	 * Returns the strings of a PO file.
	 * @param string $pofile the PO file path, relative to the test directory.
	 * @return array the [msgid => msgstr] strings.
	 */
	private function poStrings(string $pofile): array
	{
		$po = new TGettext_PO($this->_dir . '/' . $pofile);
		$po->load();
		return $po->toArray()['strings'];
	}

	public function testFactoryCreatesTheSourceForTheDirectory()
	{
		$source = MessageSource::factory('gettext', $this->_dir);

		$this->assertInstanceOf(MessageSource_gettext::class, $source);
		$this->assertSame($this->_dir, PradoUnit::getProp($source, 'source'));
	}

	public function testConstructCastsTheSourceToString()
	{
		$this->assertSame('5', PradoUnit::getProp(new MessageSource_gettext(5), 'source'));
	}

	public function testCatalogueListOrdersTheDirectoriesThenTheFileVariants()
	{
		$this->assertSame([
			'en_AU/messages.mo',
			'en/messages.mo',
			'messages.en_AU.mo',
			'messages.en.mo',
			'messages.mo',
		], PradoUnit::invoke($this->source(), 'getCatalogueList', 'messages'));
	}

	public function testCatalogueListSkipsEmptyCultureParts()
	{
		$this->assertSame(['de/app.mo', 'app.de.mo', 'app.mo'], PradoUnit::invoke($this->source('de_'), 'getCatalogueList', 'app'));
		$this->assertSame(['app.mo'], PradoUnit::invoke($this->source(''), 'getCatalogueList', 'app'));
	}

	public function testCatalogueListOfALanguageCulture()
	{
		$this->assertSame(['fr/messages.mo', 'messages.fr.mo', 'messages.mo'], PradoUnit::invoke($this->source('fr'), 'getCatalogueList', 'messages'));
	}

	public function testGetSourceJoinsTheDirectoryAndVariant()
	{
		$this->assertSame($this->_dir . '/en/messages.mo', PradoUnit::invoke($this->source(), 'getSource', 'en/messages.mo'));
	}

	public function testIsValidSourceRequiresAFile()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'G\'day']);
		$source = $this->source();

		$this->assertTrue(PradoUnit::invoke($source, 'isValidSource', $this->_dir . '/messages.mo'));
		$this->assertFalse(PradoUnit::invoke($source, 'isValidSource', $this->_dir . '/missing.mo'));
		$this->assertFalse(PradoUnit::invoke($source, 'isValidSource', $this->_dir));
	}

	public function testGetLastModifiedReturnsTheFileTime()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'G\'day']);
		touch($this->_dir . '/messages.mo', 1000000000);
		clearstatcache();
		$source = $this->source();

		$this->assertSame(1000000000, PradoUnit::invoke($source, 'getLastModified', $this->_dir . '/messages.mo'));
		$this->assertSame(0, PradoUnit::invoke($source, 'getLastModified', $this->_dir . '/missing.mo'));
	}

	public function testLoadReadsEveryVariantOfTheCulture()
	{
		$this->writeCatalogue('en_AU/messages.mo', ['Hello' => 'G\'day', 'Bye' => 'Hooroo']);
		$this->writeCatalogue('messages.en.mo', ['Hello' => 'Hi']);
		$this->writeCatalogue('messages.mo', [], false);
		$source = $this->source();

		$this->assertTrue($source->load());
		$this->assertSame([
			'en_AU/messages.mo' => ['Hello' => ['G\'day', 0, ''], 'Bye' => ['Hooroo', 1, '']],
			'messages.en.mo' => ['Hello' => ['Hi', 0, '']],
			'messages.mo' => [],
		], $source->read());
	}

	public function testLoadReadsACatalogueByName()
	{
		$this->writeCatalogue('app.mo', ['Hello' => 'Hi']);
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hey']);
		$source = $this->source('');

		$this->assertTrue($source->load('app'));
		$this->assertSame(['app.mo' => ['Hello' => ['Hi', 0, '']]], $source->read());
	}

	public function testLoadReadsTheCachedVariant()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);
		touch($this->_dir . '/messages.mo', time() - 100);
		clearstatcache();
		$cacheDir = $this->makeTempDirectory();
		$source = $this->source('');
		$source->setCache(new MessageCache($cacheDir));
		$this->assertTrue($source->load());
		$this->writeCatalogue('messages.mo', ['Hello' => 'Changed']);
		touch($this->_dir . '/messages.mo', time() - 100);
		clearstatcache();

		$this->assertTrue($source->load());

		$this->assertSame(['messages.mo' => ['Hello' => ['Hi', 0, '']]], $source->read());
	}

	public function testCataloguesListsTheFilesAndCultureDirectories()
	{
		$this->writeCatalogue('messages.mo', []);
		$this->writeCatalogue('messages.de.mo', []);
		$this->writeCatalogue('app.fr_CA.mo', []);
		$this->writeCatalogue('en_AU/messages.mo', []);
		$this->writeCatalogue('en_AU/app.mo', []);
		$this->writeCatalogue('assets/skipped.mo', []);
		file_put_contents($this->_dir . '/.mo', '');
		file_put_contents($this->_dir . '/notes.mo.txt', '');

		$this->assertSame([
			['app', 'en_AU'],
			['app', 'fr_CA'],
			['messages', null],
			['messages', 'de'],
			['messages', 'en_AU'],
		], $this->source()->catalogues());
	}

	public function testCataloguesReturnsAnEmptyListForAnEmptyDirectory()
	{
		$this->assertSame([], $this->source()->catalogues());
	}

	public function testSaveReturnsFalseWithoutUntranslatedMessages()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);

		$this->assertFalse($this->source()->save());
	}

	public function testSaveReturnsFalseWhenEveryMessageExists()
	{
		$this->writeCatalogue('messages.en.mo', ['Hello' => 'Hi', 'Bye' => '']);
		$source = $this->source();
		$source->append('Hello');
		$source->append('Bye');

		$this->assertFalse($source->save());
		$this->assertSame(['Hello' => 'Hi', 'Bye' => ''], $this->poStrings('messages.en.po'));
	}

	public function testSaveFindsTheMessagesCatalogueForANullCatalogue()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);
		$source = $this->source('');
		$source->append('Hello');

		$this->assertFalse($source->save(null));
	}

	public function testSaveThrowsWhenTheMoFileIsReadOnly()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);
		chmod($this->_dir . '/messages.mo', 0o444);
		$source = $this->source();
		$source->append('New');

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('messages.mo, file must be writable.');

		$source->save();
	}

	public function testSaveThrowsWhenThePoFileIsMissing()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi'], false);
		$source = $this->source();
		$source->append('New');

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('messages.po, file must be writable.');

		$source->save();
	}

	public function testSaveThrowsWhenOnlyThePoFileExists()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi'], true, false);
		$source = $this->source('');
		$source->append('New');

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('Unable to save to file ' . $this->_dir . '/messages.mo, file must be writable.');

		$source->save();
	}

	public function testSaveThrowsWhenTheTemplateDirectoryCannotBeCreated()
	{
		if (PHP_OS_FAMILY === 'Windows') {
			$this->markTestSkipped('Directory permissions do not stop writes on Windows.');
		}
		chmod($this->_dir, 0o555);
		$source = $this->source();
		$source->append('New');

		$this->expectException(TException::class);
		$this->expectExceptionMessage('Unable to create directory ' . $this->_dir . '/en_AU');

		$source->save();
	}

	public function testCreateMessageTemplateUsesTheMessagesCatalogueForANullCatalogue()
	{
		if (PHP_OS_FAMILY === 'Windows') {
			$this->markTestSkipped('Directory permissions do not stop writes on Windows.');
		}
		chmod($this->_dir, 0o555);

		$this->expectException(TException::class);
		$this->expectExceptionMessage('Unable to create directory ' . $this->_dir . '/en_AU');

		PradoUnit::invoke($this->source(), 'createMessageTemplate', null);
	}

	public function testDeleteReturnsFalseWithoutACatalogue()
	{
		$this->assertFalse($this->source()->delete('Hello'));
	}

	public function testDeleteReturnsFalseWithoutTheMessagesCatalogueForANullCatalogue()
	{
		$this->assertFalse($this->source('')->delete('Hello', null));
	}

	public function testDeleteFindsThePoFileForANullCatalogue()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi'], true, false);

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('Unable to modify file ' . $this->_dir . '/messages.mo, file must be writable.');

		$this->source('')->delete('Hello', null);
	}

	public function testDeleteReturnsFalseForAnUnknownMessage()
	{
		$this->writeCatalogue('en/messages.mo', ['Hello' => 'Hi']);

		$this->assertFalse($this->source()->delete('Missing'));
		$this->assertSame(['Hello' => 'Hi'], $this->poStrings('en/messages.po'));
	}

	public function testDeleteFindsTheMessagesCatalogueForANullCatalogue()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);

		$this->assertFalse($this->source('')->delete('Missing', null));
	}

	public function testDeleteReturnsFalseForAnEmptyCatalogue()
	{
		$this->writeCatalogue('messages.mo', []);

		$this->assertFalse($this->source('')->delete('Missing'));
	}

	public function testDeleteThrowsWhenTheMoFileIsReadOnly()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);
		chmod($this->_dir . '/messages.mo', 0o444);

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('messages.mo, file must be writable.');

		$this->source()->delete('Hello');
	}

	public function testDeleteThrowsWhenThePoFileIsReadOnly()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);
		chmod($this->_dir . '/messages.po', 0o444);

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('messages.po, file must be writable.');

		$this->source()->delete('Hello');
	}

	public function testUpdateReturnsFalseWithoutACatalogue()
	{
		$this->assertFalse($this->source()->update('Hello', 'Hi', ''));
	}

	public function testUpdateReturnsFalseForAnUnknownMessage()
	{
		$this->writeCatalogue('messages.en_AU.mo', ['Hello' => 'Hi']);

		$this->assertFalse($this->source()->update('Missing', 'Gone', ''));
		$this->assertSame(['Hello' => 'Hi'], $this->poStrings('messages.en_AU.po'));
	}

	public function testUpdateFindsTheMessagesCatalogueForANullCatalogue()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);

		$this->assertFalse($this->source('')->update('Missing', 'Gone', '', null));
	}

	public function testUpdateReturnsFalseForAnEmptyCatalogue()
	{
		$this->writeCatalogue('messages.mo', []);

		$this->assertFalse($this->source('')->update('Missing', 'Gone', ''));
	}

	public function testUpdateThrowsWhenTheMoFileIsReadOnly()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);
		chmod($this->_dir . '/messages.mo', 0o444);

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('messages.mo, file must be writable.');

		$this->source()->update('Hello', 'Hey', '');
	}

	public function testUpdateThrowsWhenThePoFileIsReadOnly()
	{
		$this->writeCatalogue('messages.mo', ['Hello' => 'Hi']);
		chmod($this->_dir . '/messages.po', 0o444);

		$this->expectException(TIOException::class);
		$this->expectExceptionMessage('messages.po, file must be writable.');

		$this->source()->update('Hello', 'Hey', '');
	}

	public function testSaveKeepsNumericIdsAndTranslations()
	{
		$this->writeCatalogue('messages.mo', ['10' => 'ten', '1e1' => 'x']);
		$source = $this->source('');
		$source->append('New');
		$this->assertTrue($source->save());
		$this->assertSame(['10' => 'ten', '1e1' => 'x', 'New' => ''], $this->poStrings('messages.po'));
		$this->assertFalse($source->save());
	}

	public function testSaveCreatesTheTemplate()
	{
		$source = $this->source('fr');
		$source->append('Hello');
		$this->assertTrue($source->save());
		$this->assertSame(['Hello' => ''], $this->poStrings('fr/messages.po'));
		$this->assertFileExists($this->_dir . '/fr/messages.mo');
	}

	public function testDeleteAndUpdateCompareTheIdStrictly()
	{
		$this->writeCatalogue('messages.mo', ['10' => 'ten', '1e1' => 'x', 'A' => 'a']);
		$source = $this->source('');
		$this->assertFalse($source->delete('10.0'));
		$this->assertTrue($source->delete('10'));
		$this->assertSame(['1e1' => 'x', 'A' => 'a'], $this->poStrings('messages.po'));
		$this->assertFalse($source->update('10', 'y', ''));
		$this->assertTrue($source->update('1e1', 'y', ''));
		$this->assertTrue($source->update('A', 'b', ''));
		$this->assertSame(['1e1' => 'y', 'A' => 'b'], $this->poStrings('messages.po'));
		$mo = new TGettext_MO($this->_dir . '/messages.mo');
		$mo->load();
		$this->assertSame(['1e1' => 'y', 'A' => 'b'], $mo->toArray()['strings']);
	}

	public function testLoadWithoutACulture()
	{
		$this->writeCatalogue('messages.mo', ['A' => 'a']);
		$source = $this->source(null);
		$this->assertTrue($source->load());
		$this->assertSame(['messages.mo' => ['A' => ['a', 0, '']]], $source->read());
	}

	public function testCataloguesSplitsTheExtensionAtTheEnd()
	{
		$this->writeCatalogue('x.mod.mo', [], false);
		$this->assertSame([['x', 'mod']], $this->source('')->catalogues());
	}

	public function testCataloguesOfAMissingDirectory()
	{
		$this->assertSame([], $this->source('', $this->_dir . '/missing')->catalogues());
	}

	public function testSaveDeleteAndUpdateCleanTheCache()
	{
		$this->writeCatalogue('messages.mo', ['A' => 'a', 'B' => 'b']);
		$cacheDir = $this->makeTempDirectory();
		$source = $this->source('');
		$source->setCache(new MessageCache($cacheDir));
		$this->assertTrue($source->load());
		$this->assertNotSame([], array_diff(scandir($cacheDir), ['.', '..']));
		$source->append('New');
		$this->assertTrue($source->save());
		$this->assertSame([], array_diff(scandir($cacheDir), ['.', '..']));

		$this->assertTrue($source->load());
		$this->assertTrue($source->update('A', 'aa', ''));
		$this->assertSame([], array_diff(scandir($cacheDir), ['.', '..']));

		$this->assertTrue($source->load());
		$this->assertTrue($source->delete('B'));
		$this->assertSame([], array_diff(scandir($cacheDir), ['.', '..']));
		$this->assertSame(['A' => 'aa', 'New' => ''], $this->poStrings('messages.po'));
	}

	public function testCreateMessageTemplateInAReadOnlyDirectoryThrows()
	{
		if (PHP_OS_FAMILY === 'Windows') {
			$this->markTestSkipped('Directory permissions do not stop writes on Windows.');
		}
		mkdir($this->_dir . '/en_AU');
		chmod($this->_dir . '/en_AU', 0o555);
		if (is_writable($this->_dir . '/en_AU')) {
			$this->markTestSkipped('The process writes into a read-only directory.');
		}

		$this->expectException(TException::class);
		$this->expectExceptionMessage('Unable to create file');

		PradoUnit::invoke($this->source(), 'createMessageTemplate', 'messages');
	}

	public function testRevisionDatesComeFromTheClock()
	{
		$source = $this->source('fr');
		$source->setClock((new TMockClock())->setNow(new \DateTimeImmutable('2024-01-15T10:30:00+02:00')));
		$source->append('Hello');
		$this->assertTrue($source->save());
		$po = new TGettext_PO($this->_dir . '/fr/messages.po');
		$po->load();
		$this->assertSame('2024-01-15 10:30+0200', $po->toArray()['meta']['PO-Revision-Date']);

		$source->setClock((new TMockClock())->setNow(new \DateTimeImmutable('2024-02-01T00:00:00Z')));
		$this->assertTrue($source->update('Hello', 'Bonjour', ''));
		$po->load();
		$this->assertSame('2024-02-01 00:00+0000', $po->toArray()['meta']['PO-Revision-Date']);
	}
}
