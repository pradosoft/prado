<?php

namespace Prado\Test\Unit\I18N;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TAttributeCollection;
use Prado\I18N\core\MessageFormat;
use Prado\I18N\TGlobalization;
use Prado\I18N\Translation;
use Prado\I18N\TTranslate;
use Prado\I18N\TTranslateParameter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\WebControls\TLiteral;

/**
 * A MessageFormat that records its calls and prefixes the substituted text with the catalogue.
 */
class TTranslateRecordingFormat extends MessageFormat
{
	public array $calls = [];

	public function __construct()
	{
	}

	public function format($string, $args = [], $catalogue = null, $charset = null)
	{
		$this->calls[] = [$string, $args, $catalogue, $charset];
		return $catalogue . ':' . strtr($string, $args);
	}
}

/**
 * A TGlobalization with a fixed translation configuration.
 */
class TTranslateGlobalization extends TGlobalization
{
	public function __construct(private mixed $_config)
	{
		parent::__construct();
	}

	public function getTranslationConfiguration()
	{
		return $this->_config;
	}
}

class TTranslateTest extends TestCase
{
	use TOutputBufferRestorationTrait;
	use TWebControlRenderTrait;

	private array $_appSnapshot;

	private array $_formatterSnapshot;

	private TTranslateRecordingFormat $_format;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
		$this->_appSnapshot = TTestApplication::snapshotApp(null, ['_globalization']);
		$this->_formatterSnapshot = PradoUnit::snapshotStatic(Translation::class, ['formatters']);
		$this->_format = new TTranslateRecordingFormat();
		PradoUnit::setStaticProp(Translation::class, 'formatters', ['messages' => $this->_format, 'app' => $this->_format, 'own' => $this->_format]);
		$this->useConfiguration(null);
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(Translation::class, $this->_formatterSnapshot);
		TTestApplication::restoreApp($this->_appSnapshot);
		$this->restoreOutputBufferLevel();
	}

	private function useConfiguration(?array $config): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_globalization', new TTranslateGlobalization($config));
	}

	// ── properties ──────────────────────────────────────────────────────────

	public function testTextKeyAndCatalogueProperties(): void
	{
		$translate = new TTranslate();
		$this->assertSame('', $translate->getText());
		$this->assertSame('', $translate->getKey());
		$this->assertSame('', $translate->getCatalogue());
		$translate->setText('Hello');
		$translate->setKey('greeting');
		$translate->setCatalogue('app');
		$this->assertSame('Hello', $translate->getText());
		$this->assertSame('greeting', $translate->getKey());
		$this->assertSame('app', $translate->getCatalogue());
	}

	public function testTrimProperty(): void
	{
		$translate = new TTranslate();
		$this->assertTrue($translate->getTrim());
		$translate->setTrim('false');
		$this->assertFalse($translate->getTrim());
		$translate->setTrim(true);
		$this->assertTrue($translate->getTrim());
	}

	// ── parameters ──────────────────────────────────────────────────────────

	public function testGetParametersCreatesACaseSensitiveCollectionOnce(): void
	{
		$translate = new TTranslate();
		$parameters = $translate->getParameters();
		$this->assertInstanceOf(TAttributeCollection::class, $parameters);
		$this->assertTrue($parameters->getCaseSensitive());
		$this->assertSame($parameters, $translate->getParameters());
	}

	public function testParameterAccessWithoutParameters(): void
	{
		$translate = new TTranslate();
		$this->assertFalse($translate->hasParameter('name'));
		$this->assertNull($translate->getParameter('name'));
		$this->assertNull($translate->removeParameter('name'));
	}

	public function testSetHasGetAndRemoveParameter(): void
	{
		$translate = new TTranslate();
		$translate->setParameter('name', 'Ann');
		$this->assertTrue($translate->hasParameter('name'));
		$this->assertFalse($translate->hasParameter('Name'));
		$this->assertSame('Ann', $translate->getParameter('name'));
		$this->assertNull($translate->getParameter('other'));
		$this->assertSame('Ann', $translate->removeParameter('name'));
		$this->assertFalse($translate->hasParameter('name'));
	}

	// ── render without a translation configuration ─────────────────────────

	public function testRenderSubstitutesTheTextParameters(): void
	{
		$translate = new TTranslate();
		$translate->setText(' Hello {name}, {name}! ');
		$translate->setParameter('name', 'Ann');
		$this->assertSame('Hello Ann, Ann!', $this->render($translate));
		$this->assertSame([], $this->_format->calls);
	}

	public function testRenderTextKeepsSpacesWithoutTrim(): void
	{
		$translate = new TTranslate();
		$translate->setText(' Hello ');
		$translate->setTrim(false);
		$this->assertSame(' Hello ', $this->render($translate));
	}

	public function testRenderBodyOfStringsControlsAndParameters(): void
	{
		$translate = new TTranslate();
		$parameter = new TTranslateParameter();
		$parameter->setKey('who');
		$parameter->setValue('World');
		$literal = new TLiteral();
		$literal->setText(' and {who}');
		$translate->getControls()->add('  Hello ');
		$translate->getControls()->add($literal);
		$translate->getControls()->add($parameter);
		$translate->getControls()->add('!  ');
		$this->assertSame('Hello  and World!', $this->render($translate));
	}

	public function testRenderTextReplacesTheBody(): void
	{
		$translate = new TTranslate();
		$translate->getControls()->add('Body');
		$translate->setText('Text');
		$this->assertSame('Text', $this->render($translate));
	}

	// ── render with a translation configuration ────────────────────────────

	public function testRenderFormatsInTheDefaultCatalogue(): void
	{
		$this->useConfiguration(['type' => 'PHP']);
		$translate = new TTranslate();
		$translate->setText('Hi {name}');
		$translate->setParameter('name', 'Ann');
		$translate->setCharset('ISO-8859-1');
		$this->assertSame('messages:Hi Ann', $this->render($translate));
		$this->assertSame([['Hi {name}', ['{name}' => 'Ann'], 'messages', 'ISO-8859-1']], $this->_format->calls);
	}

	public function testRenderFormatsInTheConfiguredCatalogue(): void
	{
		$this->useConfiguration(['type' => 'PHP', 'catalogue' => 'app']);
		$translate = new TTranslate();
		$translate->setText('Hi');
		$this->assertSame('app:Hi', $this->render($translate));
		$translate->setCatalogue('own');
		$this->assertSame('own:Hi', $this->render($translate));
	}

	public function testRenderFormatsTheKeyInPlaceOfTheText(): void
	{
		$this->useConfiguration(['type' => 'PHP', 'catalogue' => '']);
		$translate = new TTranslate();
		$translate->setText('Hello');
		$translate->setKey('greeting');
		$this->assertSame('messages:greeting', $this->render($translate));
	}
}
