<?php

namespace Prado\Test\Unit\I18N;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\MessageFormat;
use Prado\I18N\core\MessageSource_PHP;
use Prado\I18N\TGlobalization;
use Prado\I18N\Translation;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * Translation keeps its formatters and its once-only flags in static state, so each test runs in its own process.
 */
class TranslationTest extends TestCase
{
	use TTempDirectoryTrait;

	private string $_dir;

	protected function setUp(): void
	{
		$this->_dir = $this->makeTempDirectory('prado_translation_');
	}

	protected function tearDown(): void
	{
		$this->removeTempDirectories();
	}

	/**
	 * Registers a globalization with a translation configuration.
	 */
	private function configure(array|TMap $config, string $culture = 'fr'): TGlobalization
	{
		$globalization = new TGlobalization();
		$globalization->setCulture($culture);
		$globalization->setCharset('ISO-8859-1');
		PradoUnit::setProp($globalization, '_translation', $config);
		Prado::getApplication()->setGlobalization($globalization);
		return $globalization;
	}

	private function endRequestHandlerCount(): int
	{
		return Prado::getApplication()->getEventHandlers('OnEndRequest')->getCount();
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testInitCreatesAFormatterOfTheConfiguredSource(): void
	{
		$this->configure(['type' => 'PHP', 'source' => $this->_dir]);
		$handlers = $this->endRequestHandlerCount();
		Translation::init();
		$formatter = Translation::formatter();
		$this->assertInstanceOf(MessageFormat::class, $formatter);
		$this->assertSame('ISO-8859-1', $formatter->getCharset());
		$source = $formatter->getSource();
		$this->assertInstanceOf(MessageSource_PHP::class, $source);
		$this->assertSame('fr', $source->getCulture());
		$this->assertNull($source->getCache());
		$this->assertSame('Hello', $formatter->format('Hello'));
		$this->assertSame($handlers, $this->endRequestHandlerCount());

		Translation::init();
		$this->assertSame($formatter, Translation::formatter('messages'));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testInitSetsTheCacheAndTheUntranslatedMarker(): void
	{
		$cache = $this->makeTempDirectory('prado_translation_cache_');
		$this->configure(new TMap(['type' => 'PHP', 'source' => $this->_dir, 'cache' => $cache, 'marker' => '@@']));
		Translation::init('app');
		$formatter = Translation::formatter('app');
		$this->assertInstanceOf(MessageCache::class, $formatter->getSource()->getCache());
		$this->assertSame('@@Hello@@', $formatter->format('Hello', [], 'app'));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testAutosaveAttachesSaveMessagesOnce(): void
	{
		$this->configure(['type' => 'PHP', 'source' => $this->_dir, 'autosave' => 'true']);
		$handlers = $this->endRequestHandlerCount();
		Translation::init('first');
		$this->assertSame($handlers + 1, $this->endRequestHandlerCount());
		Translation::init('second');
		$this->assertSame($handlers + 1, $this->endRequestHandlerCount());
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testSaveMessagesSavesTheUntranslatedMessagesOnce(): void
	{
		$this->configure(['type' => 'PHP', 'source' => $this->_dir, 'autosave' => 'true']);
		Translation::init();
		Translation::formatter()->format('Hello');
		Translation::saveMessages();
		$file = $this->_dir . '/fr/messages.php';
		$this->assertFileExists($file);
		$php = include $file;
		$this->assertSame([['source' => 'Hello', 'target' => '']], $php['trans-unit']);

		Translation::formatter()->format('Goodbye');
		Translation::saveMessages();
		$php = include $file;
		$this->assertCount(1, $php['trans-unit']);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testSaveMessagesWithAutosaveOffDoesNotSave(): void
	{
		$this->configure(['type' => 'PHP', 'source' => $this->_dir, 'autosave' => 'false']);
		$handlers = $this->endRequestHandlerCount();
		Translation::init();
		$this->assertSame($handlers, $this->endRequestHandlerCount());
		Translation::formatter()->format('Hello');
		Translation::saveMessages();
		$this->assertSame(['.', '..'], scandir($this->_dir));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testSaveMessagesWithoutFormattersSavesNothing(): void
	{
		$this->configure(['type' => 'PHP', 'source' => $this->_dir, 'autosave' => 'true']);
		Translation::saveMessages();
		$this->assertSame([], PradoUnit::getStaticProp(Translation::class, 'formatters'));
		$this->assertSame(['.', '..'], scandir($this->_dir));
	}
}
