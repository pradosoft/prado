<?php

namespace Prado\Test\Unit\I18N\core;

use Exception;
use PHPUnit\Framework\TestCase;
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\MessageSource;
use Prado\I18N\core\MessageSource_PHP;
use Prado\I18N\core\MessageSource_XLIFF;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A MessageSource that keeps the base class defaults for every protected method.
 */
class MessageSourceDefaults extends MessageSource
{
	public function __construct($source)
	{
		$this->source = $source;
	}

	public function save($catalogue = 'messages')
	{
		return false;
	}

	public function update($text, $target, $comments, $catalogue = 'messages')
	{
		return false;
	}

	public function delete($message, $catalogue = 'messages')
	{
		return false;
	}

	public function catalogues()
	{
		return [];
	}
}

/**
 * A MessageSource that serves fixed variants from memory and records each loadData() call.
 */
class MessageSourceMemory extends MessageSourceDefaults
{
	/** @var array variant => messages, or false for a variant that fails to load. */
	public array $data = [];

	/** @var string[] variants whose source is invalid. */
	public array $invalid = [];

	/** @var string[] the sources loadData() received. */
	public array $loaded = [];

	public int $lastModified = 1;

	protected function &loadData($variant)
	{
		$this->loaded[] = $variant;
		$data = $this->data[substr($variant, 4)];
		return $data;
	}

	protected function getSource($variant)
	{
		return 'src:' . $variant;
	}

	protected function isValidSource($source)
	{
		return !in_array(substr($source, 4), $this->invalid, true);
	}

	protected function getCatalogueList($catalogue)
	{
		return array_keys($this->data);
	}

	protected function getLastModified($source)
	{
		return $this->lastModified;
	}
}

class MessageSourceTest extends TestCase
{
	use TTempDirectoryTrait;

	protected function tearDown(): void
	{
		$this->removeTempDirectories();
	}

	public function testFactoryCreatesTheBuiltInTypes()
	{
		$xliff = MessageSource::factory('XLIFF', '/messages');
		$this->assertInstanceOf(MessageSource_XLIFF::class, $xliff);
		$this->assertSame('/messages', PradoUnit::getProp($xliff, 'source'));

		$php = MessageSource::factory('PHP');
		$this->assertInstanceOf(MessageSource_PHP::class, $php);
		$this->assertSame('.', PradoUnit::getProp($php, 'source'));
	}

	public function testFactoryRejectsAnUnknownType()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Invalid type "Unknown", valid types are XLIFF, PHP, gettext, Database');
		MessageSource::factory('Unknown');
	}

	public function testFactoryRejectsAMissingClassFile()
	{
		$file = $this->makeTempDirectory() . '/MessageSource_Missing.php';
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("File $file not found");
		MessageSource::factory('Missing', '.', $file);
	}

	public function testFactoryLoadsACustomClassFile()
	{
		$type = 'Custom' . bin2hex(random_bytes(4));
		$file = $this->makeTempDirectory() . "/MessageSource_$type.php";
		file_put_contents($file, '<?php class MessageSource_' . $type . ' extends ' . MessageSourceDefaults::class . ' {}');

		$source = MessageSource::factory($type, 'custom-source', $file);

		$this->assertInstanceOf('MessageSource_' . $type, $source);
		$this->assertSame('custom-source', PradoUnit::getProp($source, 'source'));
	}

	public function testFactoryUsesTheBaseConstructor()
	{
		$type = 'Plain' . bin2hex(random_bytes(4));
		$file = $this->makeTempDirectory() . "/MessageSource_$type.php";
		file_put_contents($file, '<?php class MessageSource_' . $type . ' extends ' . MessageSource::class . ' {
			public function save($catalogue = "messages") { return false; }
			public function update($text, $target, $comments, $catalogue = "messages") { return false; }
			public function delete($message, $catalogue = "messages") { return false; }
			public function catalogues() { return []; }
		}');

		$source = MessageSource::factory($type, 'unused', $file);

		$this->assertInstanceOf('MessageSource_' . $type, $source);
		$this->assertNull(PradoUnit::getProp($source, 'source'));
	}

	public function testBaseDefaults()
	{
		$source = new MessageSourceDefaults('.');

		$this->assertSame(0, PradoUnit::invoke($source, 'getLastModified', 'messages'));
		$this->assertSame('messages.xml', PradoUnit::invoke($source, 'getSource', 'messages.xml'));
		$this->assertFalse(PradoUnit::invoke($source, 'isValidSource', 'messages.xml'));
		$this->assertSame([], PradoUnit::invoke($source, 'getCatalogueList', 'messages'));

		$this->assertTrue($source->load());
		$this->assertSame([], $source->read());
	}

	public function testBaseLoadDataReturnsNoMessages()
	{
		$this->assertSame([], PradoUnit::invoke(new MessageSourceDefaults('.'), 'loadData', 'messages'));
	}

	public function testLoadReadsEachValidVariant()
	{
		$source = new MessageSourceMemory('.');
		$source->data = [
			'messages.en_US' => ['Hello' => ['Howdy', '1', '']],
			'messages.en' => false,
			'messages.fr' => ['Hello' => ['Bonjour', '1', '']],
			'messages' => ['Bye' => ['Goodbye', '2', '']],
		];
		$source->invalid = ['messages.fr'];

		$this->assertTrue($source->load('messages'));

		$this->assertSame(['src:messages.en_US', 'src:messages.en', 'src:messages'], $source->loaded);
		$this->assertSame([
			'messages.en_US' => ['Hello' => ['Howdy', '1', '']],
			'messages' => ['Bye' => ['Goodbye', '2', '']],
		], $source->read());
	}

	public function testLoadSkipsAnInvalidVariant()
	{
		$source = new MessageSourceMemory('.');
		$source->data = ['messages' => ['A' => ['a', '1', '']]];
		$source->invalid = ['messages'];

		$this->assertTrue($source->load());

		$this->assertSame([], $source->loaded);
		$this->assertSame([], $source->read());
	}

	public function testLoadSkipsAFailedVariantWithACache()
	{
		$cache = new MessageCache($this->makeTempDirectory());
		$source = new MessageSourceMemory('.');
		$source->setCulture('en_US');
		$source->setCache($cache);
		$source->data = ['messages' => false];

		$this->assertTrue($source->load());

		$this->assertSame(['src:messages'], $source->loaded);
		$this->assertSame([], $source->read());
		$this->assertFalse($cache->get('messages', 'en_US', 1));
	}

	public function testLoadReplacesThePreviousMessages()
	{
		$source = new MessageSourceMemory('.');
		$source->data = ['first' => ['A' => ['a', '1', '']]];
		$source->load();
		$source->data = ['second' => ['B' => ['b', '1', '']]];

		$source->load();

		$this->assertSame(['second' => ['B' => ['b', '1', '']]], $source->read());
	}

	public function testLoadSavesToAndReadsFromTheCache()
	{
		$cache = new MessageCache($this->makeTempDirectory());
		$source = new MessageSourceMemory('.');
		$source->setCulture('en_US');
		$source->setCache($cache);
		$source->data = [
			'messages' => ['Hello' => ['Howdy', '1', '']],
			'failing' => false,
		];

		$source->load();

		$this->assertSame(['src:messages', 'src:failing'], $source->loaded);
		$this->assertSame(['Hello' => ['Howdy', '1', '']], $cache->get('messages', 'en_US', 1));
		$this->assertFalse($cache->get('failing', 'en_US', 1));

		$source->loaded = [];
		$source->data['messages'] = ['Hello' => ['Changed', '1', '']];

		$source->load();

		$this->assertSame(['src:failing'], $source->loaded);
		$this->assertSame(['messages' => ['Hello' => ['Howdy', '1', '']]], $source->read());
	}

	public function testLoadReloadsAVariantNewerThanTheCache()
	{
		$cache = new MessageCache($this->makeTempDirectory());
		$source = new MessageSourceMemory('.');
		$source->setCulture('en_US');
		$source->setCache($cache);
		$source->data = ['messages' => ['Hello' => ['Howdy', '1', '']]];
		$source->load();

		$source->loaded = [];
		$source->data['messages'] = ['Hello' => ['Changed', '1', '']];
		$source->lastModified = time() + 3600;

		$source->load();

		$this->assertSame(['src:messages'], $source->loaded);
		$this->assertSame(['messages' => ['Hello' => ['Changed', '1', '']]], $source->read());
	}

	public function testCacheAccessors()
	{
		$source = new MessageSourceDefaults('.');
		$this->assertNull($source->getCache());

		$cache = new MessageCache($this->makeTempDirectory());
		$source->setCache($cache);

		$this->assertSame($cache, $source->getCache());
	}

	public function testCultureAccessors()
	{
		$source = new MessageSourceDefaults('.');
		$this->assertNull($source->getCulture());

		$source->setCulture('fr_FR');

		$this->assertSame('fr_FR', $source->getCulture());
	}

	public function testAppendAddsEachMessageOnce()
	{
		$source = new MessageSourceDefaults('.');

		$source->append('Hello');
		$source->append('Bye');
		$source->append('Hello');

		$this->assertSame(['Hello', 'Bye'], PradoUnit::getProp($source, 'untranslated'));
	}
}
