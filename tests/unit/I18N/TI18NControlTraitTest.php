<?php

namespace Prado\Test\Unit\I18N;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\CultureInfo;
use Prado\I18N\TGlobalization;
use Prado\I18N\TI18NControl;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TBehavior;

/**
 * Answers the culture and charset filter events of TI18NControlTrait with fixed values.
 */
class TI18NControlTraitDefaultsBehavior extends TBehavior
{
	public function __construct(private mixed $_charset, private mixed $_culture)
	{
		parent::__construct();
	}

	public function dyDefaultCharsetValue($value, $chain)
	{
		$chain->dyDefaultCharsetValue($value);
		return $this->_charset;
	}

	public function dyDefaultCultureValue($value, $chain)
	{
		$chain->dyDefaultCultureValue($value);
		return $this->_culture;
	}
}

/**
 * Exposes the protected helper methods of TI18NControlTrait.
 */
class TI18NControlTraitHost extends TI18NControl
{
	public function pubGetCultureInfo($culture = null)
	{
		return $this->getCultureInfo($culture);
	}

	public function pubConvertToCharset($text)
	{
		return $this->convertToCharset($text);
	}
}

class TI18NControlTraitTest extends TestCase
{
	private array $_appSnapshot;

	protected function setUp(): void
	{
		$this->_appSnapshot = TTestApplication::snapshotApp(null, ['_globalization']);
	}

	protected function tearDown(): void
	{
		TTestApplication::restoreApp($this->_appSnapshot);
	}

	private function setGlobalization(?TGlobalization $globalization): void
	{
		PradoUnit::setProp(\Prado\Prado::getApplication(), '_globalization', $globalization);
	}

	private function newGlobalization(?string $culture, ?string $charset, ?string $defaultCharset = 'UTF-8'): TGlobalization
	{
		$globalization = new TGlobalization();
		$globalization->setCulture($culture ?? '');
		PradoUnit::setProp($globalization, '_culture', $culture);
		$globalization->setCharset($charset);
		$globalization->setDefaultCharset($defaultCharset);
		return $globalization;
	}

	// ── Culture ─────────────────────────────────────────────────────────────

	public function testCulturePropertyOverridesTheApplication(): void
	{
		$this->setGlobalization($this->newGlobalization('de_DE', 'UTF-8'));
		$control = new TI18NControlTraitHost();
		$control->setCulture('fr_FR');
		$this->assertSame('fr_FR', $control->getCulture());
		$control->setCulture('');
		$this->assertSame('de_DE', $control->getCulture());
	}

	public function testCultureWithoutGlobalizationIsTheFilterDefault(): void
	{
		$this->setGlobalization(null);
		$this->assertSame('', (new TI18NControlTraitHost())->getCulture());
	}

	public function testCultureWithoutGlobalizationIsTheBehaviorValue(): void
	{
		$this->setGlobalization(null);
		$control = new TI18NControlTraitHost();
		$control->attachBehavior('defaults', new TI18NControlTraitDefaultsBehavior(null, 'ja_JP'));
		$this->assertSame('ja_JP', $control->getCulture());
	}

	public function testCultureKeepsTheDefaultOnANullBehaviorResult(): void
	{
		$this->setGlobalization(null);
		$control = new TI18NControlTraitHost();
		$control->attachBehavior('defaults', new TI18NControlTraitDefaultsBehavior(null, null));
		$this->assertSame('', $control->getCulture());
	}

	public function testCultureWithAnEmptyApplicationCultureIsTheBehaviorValue(): void
	{
		$this->setGlobalization($this->newGlobalization(null, 'UTF-8'));
		$control = new TI18NControlTraitHost();
		$this->assertSame('', $control->getCulture());
		$control->attachBehavior('defaults', new TI18NControlTraitDefaultsBehavior(null, 'es'));
		$this->assertSame('es', $control->getCulture());
	}

	// ── Charset ─────────────────────────────────────────────────────────────

	public function testCharsetPropertyOverridesTheApplication(): void
	{
		$this->setGlobalization($this->newGlobalization('en', 'ISO-8859-1'));
		$control = new TI18NControlTraitHost();
		$control->setCharset('Windows-1252');
		$this->assertSame('Windows-1252', $control->getCharset());
		$control->setCharset('');
		$this->assertSame('ISO-8859-1', $control->getCharset());
	}

	public function testCharsetFallsBackToTheDefaultCharset(): void
	{
		$this->setGlobalization($this->newGlobalization('en', null, 'ISO-8859-15'));
		$this->assertSame('ISO-8859-15', (new TI18NControlTraitHost())->getCharset());
	}

	public function testCharsetWithEmptyApplicationCharsetsIsUtf8(): void
	{
		$this->setGlobalization($this->newGlobalization('en', '', ''));
		$this->assertSame('UTF-8', (new TI18NControlTraitHost())->getCharset());
	}

	public function testCharsetWithEmptyApplicationCharsetsIsTheBehaviorValue(): void
	{
		$this->setGlobalization($this->newGlobalization('en', '', ''));
		$control = new TI18NControlTraitHost();
		$control->attachBehavior('defaults', new TI18NControlTraitDefaultsBehavior('ISO-8859-2', null));
		$this->assertSame('ISO-8859-2', $control->getCharset());
	}

	public function testCharsetWithoutGlobalizationIsUtf8(): void
	{
		$this->setGlobalization(null);
		$this->assertSame('UTF-8', (new TI18NControlTraitHost())->getCharset());
	}

	public function testCharsetWithoutGlobalizationIsTheBehaviorValue(): void
	{
		$this->setGlobalization(null);
		$control = new TI18NControlTraitHost();
		$control->attachBehavior('defaults', new TI18NControlTraitDefaultsBehavior('ISO-8859-1', null));
		$this->assertSame('ISO-8859-1', $control->getCharset());
	}

	public function testCharsetKeepsUtf8OnANullBehaviorResult(): void
	{
		$this->setGlobalization(null);
		$control = new TI18NControlTraitHost();
		$control->attachBehavior('defaults', new TI18NControlTraitDefaultsBehavior(null, null));
		$this->assertSame('UTF-8', $control->getCharset());
	}

	// ── Helpers ─────────────────────────────────────────────────────────────

	public function testGetCultureInfoOfTheControlCulture(): void
	{
		$control = new TI18NControlTraitHost();
		$control->setCulture('fr_FR');
		$info = $control->pubGetCultureInfo();
		$this->assertInstanceOf(CultureInfo::class, $info);
		$this->assertSame('fr_FR', $info->getName());
		$this->assertSame('de', $control->pubGetCultureInfo('de')->getName());
	}

	public function testConvertToCharsetConvertsFromUtf8(): void
	{
		$control = new TI18NControlTraitHost();
		$control->setCharset('ISO-8859-1');
		$this->assertSame("caf\xE9", $control->pubConvertToCharset('café'));
		$control->setCharset('UTF-8');
		$this->assertSame('café', $control->pubConvertToCharset('café'));
	}
}
