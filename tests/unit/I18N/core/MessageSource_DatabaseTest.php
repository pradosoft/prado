<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\MessageSource;
use Prado\I18N\core\MessageSource_Database;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Clock\TMockClock;

class MessageSource_DatabaseTest extends TestCase
{
	use TTempDirectoryTrait;

	/** The ID of the data source module the tests register. */
	private const MODULE_ID = 'messagesource_database_test';

	/** The time the mock clock reports. */
	private const TIME = 1700000000;

	/** @var array the application modules before the test. */
	private array $_modules;

	/** @var TDbConnection the connection to the test database. */
	private TDbConnection $_db;

	private TMockClock $_clock;

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite is required.');
		}
		$file = $this->makeTempDirectory('prado_msgsrc_db_') . DIRECTORY_SEPARATOR . 'messages.db';
		$this->_db = new TDbConnection('sqlite:' . $file);
		$this->_db->setActive(true);
		$schema = preg_replace('/^#.*$/m', '', file_get_contents(dirname(__DIR__, 4) . '/framework/I18N/schema/sqlite.sql'));
		foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
			$this->_db->createCommand($statement)->execute();
		}

		$app = Prado::getApplication();
		$this->_modules = PradoUnit::getProp($app, '_modules');
		$config = new TDataSourceConfig();
		$config->getDbConnection()->setConnectionString('sqlite:' . $file);
		$app->setModule(self::MODULE_ID, $config);

		$this->_clock = (new TMockClock())->setTime(self::TIME);
	}

	protected function tearDown(): void
	{
		if (isset($this->_modules)) {
			PradoUnit::setProp(Prado::getApplication(), '_modules', $this->_modules);
		}
		if (isset($this->_db)) {
			$this->_db->setActive(false);
		}
		$this->removeTempDirectories();
	}

	private function createSource(string $culture = 'en_AU'): MessageSource_Database
	{
		$source = new MessageSource_Database(self::MODULE_ID);
		$source->setCulture($culture);
		$source->setClock($this->_clock);
		return $source;
	}

	/**
	 * @return array the trans_unit rows of a catalogue, ordered by msg_id.
	 */
	private function units(string $catalogue): array
	{
		$command = $this->_db->createCommand(
			'SELECT t.id, t.source, t.target, t.comments, t.date_added, t.date_modified
				FROM trans_unit t, catalogue c WHERE c.cat_id = t.cat_id AND c.name = :name ORDER BY t.msg_id'
		);
		$command->bindValue(':name', $catalogue);
		return $command->queryAll();
	}

	private function catalogueTime(string $catalogue): mixed
	{
		$command = $this->_db->createCommand('SELECT date_modified FROM catalogue WHERE name = :name');
		$command->bindValue(':name', $catalogue);
		return $command->queryScalar();
	}

	// ------- connection -------

	public function testFactoryCreatesTheSourceWithTheConnectionID()
	{
		$source = MessageSource::factory('Database', self::MODULE_ID);

		$this->assertInstanceOf(MessageSource_Database::class, $source);
		$this->assertSame(self::MODULE_ID, $source->getConnectionID());
		$this->assertInstanceOf(TDbConnection::class, $source->getDbConnection());
	}

	public function testConnectionIDIsNotPublic()
	{
		$method = new \ReflectionMethod(MessageSource_Database::class, 'setConnectionID');

		$this->assertTrue($method->isProtected());
	}

	public function testAnUnknownConnectionIDThrows()
	{
		$source = new MessageSource_Database('messagesource_database_missing');

		try {
			$source->getDbConnection();
			$this->fail('TConfigurationException expected.');
		} catch (TConfigurationException $e) {
			$this->assertSame('messagesource_connectionid_invalid', $e->getErrorCode());
		}
	}

	public function testAnEmptyConnectionIDThrows()
	{
		$source = new MessageSource_Database('');

		try {
			$source->getDbConnection();
			$this->fail('TConfigurationException expected.');
		} catch (TConfigurationException $e) {
			$this->assertSame('messagesource_connectionid_required', $e->getErrorCode());
		}
	}

	// ------- reading -------

	public function testLoadReadsEveryVariantOfTheCulture()
	{
		$source = $this->createSource('en_AU');

		$this->assertTrue($source->load('messages'));

		$this->assertSame([
			'messages.en_AU' => [
				'Hello' => ['G\'day Mate!', '', ''],
				'Welcome' => ['Welcome Mate!', '', ''],
			],
			'messages.en' => [
				'Hello' => ['Hello :)', '', ''],
			],
			'messages' => [
				'Hello' => ['Hello World', '1', ''],
				'Welcome' => ['Welcome', '1', ''],
			],
		], $source->read());
	}

	public function testLoadOfAnUnknownCatalogueReadsNothing()
	{
		$source = $this->createSource('en');

		$this->assertTrue($source->load('unknown'));
		$this->assertSame([], $source->read());
	}

	public function testLoadOfACatalogueWithoutMessagesReadsAnEmptyVariant()
	{
		$this->_db->createCommand("INSERT INTO catalogue (cat_id, name) VALUES (4, 'messages.fr')")->execute();
		$source = $this->createSource('fr');

		$source->load('messages');

		$this->assertSame([], $source->read()['messages.fr']);
	}

	public function testCataloguesOfAnEmptyTableIsEmpty()
	{
		$this->_db->createCommand('DELETE FROM catalogue')->execute();

		$this->assertSame([], $this->createSource()->catalogues());
	}

	public function testGetCatalogueListSkipsEmptyCultureParts()
	{
		$this->assertSame(['messages.en_AU', 'messages.en', 'messages'], PradoUnit::invoke($this->createSource('en_AU'), 'getCatalogueList', 'messages'));
		$this->assertSame(['messages.en', 'messages'], PradoUnit::invoke($this->createSource('en'), 'getCatalogueList', 'messages'));
		$this->assertSame(['messages.en_AU', 'messages.en', 'messages'], PradoUnit::invoke($this->createSource('en__AU'), 'getCatalogueList', 'messages'));
		$this->assertSame(['messages'], PradoUnit::invoke($this->createSource(''), 'getCatalogueList', 'messages'));
	}

	public function testIsValidSource()
	{
		$source = $this->createSource();

		$this->assertTrue(PradoUnit::invoke($source, 'isValidSource', 'messages.en'));
		$this->assertFalse(PradoUnit::invoke($source, 'isValidSource', 'messages.fr'));
	}

	public function testGetLastModified()
	{
		$source = $this->createSource();

		$this->assertSame(0, PradoUnit::invoke($source, 'getLastModified', 'messages.en_AU'));
		$this->assertSame(0, PradoUnit::invoke($source, 'getLastModified', 'messages.fr'));

		$this->assertTrue($source->update('Hello', 'Hiya', ''));

		$this->assertEquals(self::TIME, PradoUnit::invoke($source, 'getLastModified', 'messages.en_AU'));
	}

	public function testGetCatalogueDetailsDefaultsToMessages()
	{
		$source = $this->createSource();

		$this->assertEquals([3, 'messages.en_AU', 2], PradoUnit::invoke($source, 'getCatalogueDetails', ''));
		$this->assertEquals([3, 'messages.en_AU', 2], PradoUnit::invoke($source, 'getCatalogueDetails'));
	}

	// ------- save -------

	public function testSaveWithoutUntranslatedMessagesIsFalse()
	{
		$this->assertFalse($this->createSource()->save());
	}

	public function testSaveAppendsTheUntranslatedMessages()
	{
		$source = $this->createSource();
		$source->append('Goodbye');
		$source->append('');
		$source->append('Thanks');

		$this->assertTrue($source->save('messages'));

		$units = $this->units('messages.en_AU');
		$this->assertCount(4, $units);
		$this->assertEquals(['3', 'Goodbye', null, null, self::TIME, null], array_values($units[2]));
		$this->assertEquals(['4', 'Thanks', null, null, self::TIME, null], array_values($units[3]));
		$this->assertEquals(self::TIME, $this->catalogueTime('messages.en_AU'));
	}

	public function testSaveAppendsOneUntranslatedMessage()
	{
		$source = $this->createSource();
		$source->append('Goodbye');

		$this->assertTrue($source->save());

		$units = $this->units('messages.en_AU');
		$this->assertCount(3, $units);
		$this->assertEquals(['3', 'Goodbye', null, null, self::TIME, null], array_values($units[2]));
	}

	public function testSaveOfOnlyEmptyMessagesIsFalse()
	{
		$source = $this->createSource();
		$source->append('');

		$this->assertFalse($source->save());
		$this->assertCount(2, $this->units('messages.en_AU'));
		$this->assertSame('', $this->catalogueTime('messages.en_AU'));
	}

	public function testSaveToAnUnknownCatalogueIsFalse()
	{
		$source = $this->createSource('fr');
		$source->append('Goodbye');

		$this->assertFalse($source->save('messages'));
		$this->assertSame(5, (int) $this->_db->createCommand('SELECT COUNT(*) FROM trans_unit')->queryScalar());
	}

	public function testSaveToACatalogueWithoutAPositiveIdIsFalse()
	{
		$this->_db->createCommand("INSERT INTO catalogue (cat_id, name) VALUES (0, 'messages.zero')")->execute();
		$source = $this->createSource('zero');
		$source->append('Goodbye');

		$this->assertFalse($source->save('messages'));
		$this->assertSame([], $this->units('messages.zero'));
	}

	// ------- update and delete -------

	public function testUpdateChangesTheTranslation()
	{
		$source = $this->createSource();

		$this->assertTrue($source->update('Hello', 'Hiya', 'casual', 'messages'));

		$units = $this->units('messages.en_AU');
		$this->assertSame('Hiya', $units[0]['target']);
		$this->assertSame('casual', $units[0]['comments']);
		$this->assertEquals(self::TIME, $units[0]['date_modified']);
		$this->assertEquals(self::TIME, $this->catalogueTime('messages.en_AU'));
	}

	public function testUpdateOfAnUnknownMessageIsFalse()
	{
		$source = $this->createSource();

		$this->assertFalse($source->update('Unknown', 'Hiya', ''));
		$this->assertSame('', $this->catalogueTime('messages.en_AU'));
	}

	public function testUpdateOfAnUnknownCatalogueIsFalse()
	{
		$this->assertFalse($this->createSource('fr')->update('Hello', 'Bonjour', ''));
	}

	public function testDeleteRemovesTheMessage()
	{
		$source = $this->createSource();

		$this->assertTrue($source->delete('Hello', 'messages'));

		$units = $this->units('messages.en_AU');
		$this->assertCount(1, $units);
		$this->assertSame('Welcome', $units[0]['source']);
		$this->assertEquals(self::TIME, $this->catalogueTime('messages.en_AU'));
	}

	public function testDeleteOfAnUnknownMessageIsFalse()
	{
		$this->assertFalse($this->createSource()->delete('Unknown'));
		$this->assertCount(2, $this->units('messages.en_AU'));
	}

	public function testDeleteFromAnUnknownCatalogueIsFalse()
	{
		$this->assertFalse($this->createSource('fr')->delete('Hello'));
		$this->assertSame(5, (int) $this->_db->createCommand('SELECT COUNT(*) FROM trans_unit')->queryScalar());
	}

	public function testUpdateCatalogueTimeOfAnUnknownCatalogueIsFalse()
	{
		$this->assertFalse(PradoUnit::invoke($this->createSource(), 'updateCatalogueTime', 99, 'messages.xx'));
	}

	// ------- cache -------

	public function testLoadUsesTheCacheUntilTheCatalogueChanges()
	{
		$cache = new MessageCache($this->makeTempDirectory('prado_msgsrc_cache_'));
		$source = $this->createSource();
		$source->setCache($cache);
		// A catalogue modified at the pinned time, before the cache files are written.
		$this->assertTrue($source->update('Hello', 'Hiya', ''));

		$source->load('messages');
		$this->assertSame('Hiya', $source->read()['messages.en_AU']['Hello'][0]);
		$this->assertSame('Hiya', $cache->get('messages.en_AU', 'en_AU', self::TIME)['Hello'][0]);

		// A row changed without the source is hidden by the cache.
		$this->_db->createCommand("UPDATE trans_unit SET target = 'Direct' WHERE msg_id = 4")->execute();
		$source->load('messages');
		$this->assertSame('Hiya', $source->read()['messages.en_AU']['Hello'][0]);

		// A change through the source cleans the cache of the catalogue.
		$this->assertTrue($source->update('Welcome', 'Welcome Back!', ''));
		$this->assertFalse($cache->get('messages.en_AU', 'en_AU', self::TIME));
		$source->load('messages');
		$this->assertSame('Direct', $source->read()['messages.en_AU']['Hello'][0]);
		$this->assertSame('Welcome Back!', $source->read()['messages.en_AU']['Welcome'][0]);
	}

	public function testCataloguesListsTheCatalogues()
	{
		$this->_db->createCommand("INSERT INTO catalogue (name) VALUES ('app')")->execute();
		$catalogues = $this->createSource()->catalogues();
		$this->assertContains(['messages', 'en_AU'], $catalogues);
		$this->assertContains(['app', null], $catalogues);
	}

	public function testUpdateAndDeleteInAnUnknownCatalogueAreFalse()
	{
		$this->_db->createCommand("INSERT INTO trans_unit (cat_id, id, source, target) VALUES (0, '1', 'Orphan', 'o')")->execute();
		$source = $this->createSource('xx');
		$this->assertFalse($source->update('Orphan', 'changed', '', 'messages'));
		$this->assertFalse($source->delete('Orphan', 'messages'));
		$this->assertSame('o', $this->_db->createCommand("SELECT target FROM trans_unit WHERE source = 'Orphan'")->queryScalar());
	}

	public function testCataloguesOfOneCatalogue()
	{
		$this->_db->createCommand('DELETE FROM catalogue')->execute();
		$this->_db->createCommand("INSERT INTO catalogue (name) VALUES ('app')")->execute();
		$this->assertSame([['app', null]], $this->createSource()->catalogues());

		$this->_db->createCommand('DELETE FROM catalogue')->execute();
		$this->_db->createCommand("INSERT INTO catalogue (name) VALUES ('messages.fr')")->execute();
		$this->assertSame([['messages', 'fr']], $this->createSource()->catalogues());
	}

	public function testGetCatalogueDetailsOfAnUnknownCultureIsFalse()
	{
		$this->assertFalse(PradoUnit::invoke($this->createSource('xx'), 'getCatalogueDetails'));
	}
}
