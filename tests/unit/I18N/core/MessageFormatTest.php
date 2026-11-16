<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\IMessageSource;
use Prado\I18N\core\MessageFormat;
use Prado\Test\Unit\PradoUnit;

/**
 * An IMessageSource that serves fixed catalogues from memory and records its calls.
 */
class MessageFormatMemorySource implements IMessageSource
{
	/** @var array catalogue => (variant => messages) */
	public array $catalogues = [];

	/** @var string[] the catalogues load() received. */
	public array $loaded = [];

	/** @var string[] the messages append() received. */
	public array $appended = [];

	public bool $loadResult = true;

	private array $_current = [];

	public function load($catalogue = 'messages')
	{
		$this->loaded[] = $catalogue;
		$this->_current = $this->catalogues[$catalogue] ?? [];
		return $this->loadResult;
	}

	public function read()
	{
		return $this->_current;
	}

	public function save($catalogue = 'messages')
	{
		return false;
	}

	public function append($message)
	{
		$this->appended[] = $message;
	}

	public function delete($message, $catalogue = 'messages')
	{
		return false;
	}

	public function update($text, $target, $comments, $catalogue = 'messages')
	{
		return false;
	}

	public function catalogues()
	{
		return array_keys($this->catalogues);
	}

	public function setCulture($culture)
	{
	}

	public function getCulture()
	{
		return 'en';
	}
}

class MessageFormatTest extends TestCase
{
	private MessageFormatMemorySource $_source;

	protected function setUp(): void
	{
		$this->_source = new MessageFormatMemorySource();
		$this->_source->catalogues = [
			'messages' => [
				'messages.en_US.xml' => [
					'Hello {name}' => ['Howdy {name}', '1', ''],
					'Plain' => 'Plain target',
				],
				'messages.xml' => [
					'Bye' => ['Goodbye', '1', ''],
					'Hello {name}' => ['Hi {name}', '2', ''],
					'Empty {name}' => ['', '3', ''],
				],
			],
			'app' => [
				'app.xml' => ['Bye' => ['App goodbye', '1', '']],
			],
		];
	}

	public function testConstructor()
	{
		$format = new MessageFormat($this->_source);

		$this->assertSame($this->_source, $format->getSource());
		$this->assertSame('UTF-8', $format->getCharset());
		$this->assertNull($format->Catalogue);
		$this->assertSame('ISO-8859-1', (new MessageFormat($this->_source, 'ISO-8859-1'))->getCharset());
	}

	public function testCharset()
	{
		$format = new MessageFormat($this->_source);

		$format->setCharset('ISO-8859-1');

		$this->assertSame('ISO-8859-1', $format->getCharset());
	}

	public function testFormatTranslates()
	{
		$format = new MessageFormat($this->_source);

		$this->assertSame('Howdy Ann', $format->format('Hello {name}', ['{name}' => 'Ann']));
		$this->assertSame('Goodbye', $format->format('Bye'));
		$this->assertSame('Plain target', $format->format('Plain'));
		$this->assertSame([], $this->_source->appended);
	}

	public function testFormatSearchesOneVariant()
	{
		$this->_source->catalogues['messages'] = ['messages.xml' => ['Bye' => ['Goodbye', '1', '']]];
		$format = new MessageFormat($this->_source);

		$this->assertSame('Goodbye', $format->format('Bye'));
	}

	public function testFormatUntranslatedTarget()
	{
		$format = new MessageFormat($this->_source);
		$this->assertSame('Empty Ann', $format->format('Empty {name}', ['{name}' => 'Ann']));

		$format->setUntranslatedPS(['[T]', '[/T]']);

		$this->assertSame('[T]Empty Ann[/T]', $format->format('Empty {name}', ['{name}' => 'Ann']));
		$this->assertSame([], $this->_source->appended);
	}

	public function testFormatMissingMessage()
	{
		$format = new MessageFormat($this->_source);
		$format->setUntranslatedPS(['[T]', '[/T]']);

		$this->assertSame('[T]Missing Ann[/T]', $format->format('Missing {name}', ['{name}' => 'Ann']));

		$this->assertSame(['Missing {name}'], $this->_source->appended);
	}

	public function testFormatMissingMessageInAnEmptyCatalogue()
	{
		$this->_source->catalogues['messages'] = ['messages.xml' => []];
		$format = new MessageFormat($this->_source);

		$this->assertSame('Missing', $format->format('Missing'));

		$this->assertSame(['Missing'], $this->_source->appended);
	}

	public function testSetUntranslatedPSIgnoresInvalidValues()
	{
		$format = new MessageFormat($this->_source);

		$format->setUntranslatedPS('[T]');
		$format->setUntranslatedPS(['[T]']);

		$this->assertSame(['', ''], PradoUnit::getProp($format, 'postscript'));

		$format->setUntranslatedPS(['<', '>', 'ignored']);

		$this->assertSame(['<', '>'], PradoUnit::getProp($format, 'postscript'));
	}

	public function testFormatUsesTheCatalogueProperty()
	{
		$format = new MessageFormat($this->_source);
		$format->Catalogue = 'app';

		$this->assertSame('App goodbye', $format->format('Bye'));
		$this->assertSame('Goodbye', $format->format('Bye', [], 'messages'));
		$this->assertSame(['app', 'messages'], $this->_source->loaded);
	}

	public function testFormatLoadsEachCatalogueOnce()
	{
		$format = new MessageFormat($this->_source);

		$format->format('Bye');
		$format->format('Bye', [], 'app');
		$format->format('Hello {name}');
		$format->format('Bye', [], 'app');

		$this->assertSame(['messages', 'app'], $this->_source->loaded);
		$this->assertSame(['messages', 'app'], PradoUnit::getProp($format, 'catagloues'));
	}

	public function testLoadCatalogueKeepsAFailedCatalogueUnloaded()
	{
		$this->_source->loadResult = false;
		$format = new MessageFormat($this->_source);

		PradoUnit::invoke($format, 'loadCatalogue', 'messages');
		PradoUnit::invoke($format, 'loadCatalogue', 'messages');

		$this->assertSame(['messages', 'messages'], $this->_source->loaded);
		$this->assertSame([], PradoUnit::getProp($format, 'messages'));
		$this->assertSame([], PradoUnit::getProp($format, 'catagloues'));
	}

	public function testFormatConvertsTheCharset()
	{
		if (!extension_loaded('iconv')) {
			$this->markTestSkipped('iconv is required for the charset conversion.');
		}
		$this->_source->catalogues['messages'] = [
			'messages.xml' => ["Caf\u{e9} {name}" => ["Br\u{fc}cke {name}", '1', '']],
		];
		$format = new MessageFormat($this->_source, 'ISO-8859-1');

		$this->assertSame("Br\xFCcke Z\xFCrich", $format->format("Caf\xE9 {name}", ['{name}' => "Z\xFCrich"]));
		$this->assertSame("[\xE9]", $format->format("[\xE9]"));
		$this->assertSame(["[\u{e9}]"], $this->_source->appended);
	}

	public function testFormatConvertsTheCharsetArgument()
	{
		if (!extension_loaded('iconv')) {
			$this->markTestSkipped('iconv is required for the charset conversion.');
		}
		$this->_source->catalogues['messages'] = [
			'messages.xml' => ["Caf\u{e9}" => ["Br\u{fc}cke", '1', '']],
		];
		$format = new MessageFormat($this->_source);

		$this->assertSame("Br\xFCcke", $format->format("Caf\xE9", [], null, 'ISO-8859-1'));
		$this->assertSame("[Z\xFCrich]", $format->format('[{name}]', ['{name}' => "Z\xFCrich"], null, 'ISO-8859-1'));
		$this->assertSame("Br\u{fc}cke", $format->format("Caf\u{e9}"));
	}

	public function testFormatComparesTheSourceStrictly()
	{
		$this->_source->catalogues['messages'] = ['m' => ['10' => ['ten', '1', '']]];
		$format = new MessageFormat($this->_source);
		$this->assertSame('1e1', $format->format('1e1'));
		$this->assertSame('10.0', $format->format('10.0'));
		$this->assertSame('ten', $format->format('10'));
	}

	public function testFormatTranslatesToZero()
	{
		$this->_source->catalogues['messages'] = ['m' => ['none' => ['0', '1', ''], 'nil' => '0']];
		$format = new MessageFormat($this->_source);
		$this->assertSame('0', $format->format('none'));
		$this->assertSame('0', $format->format('nil'));
	}

	public function testFormatWithAFailedCatalogue()
	{
		$this->_source->loadResult = false;
		$format = new MessageFormat($this->_source);
		$format->setUntranslatedPS(['[', ']']);
		$this->assertSame('[Bye]', $format->format('Bye'));
		$this->assertSame(['Bye'], $this->_source->appended);
	}

	public function testFormatWithNullArguments()
	{
		$this->assertSame('Goodbye', (new MessageFormat($this->_source))->format('Bye', null));
	}
}
