<?php

namespace Prado\Test\Unit\I18N;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\Exceptions\TConfigurationException;
use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\TApplication;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Xml\TXmlDocument;

/**
 * A TGlobalization without an application.
 */
class TGlobalizationWithoutApplication extends TGlobalization
{
	public function getApplication()
	{
		return null;
	}
}

class TGlobalizationTest extends TestCase
{
	use TTempDirectoryTrait;

	private TGlobalization $glob;

	private array $_appSnapshot;

	private array $_aliasSnapshot;

	protected function setUp(): void
	{
		$this->glob = new TGlobalization();
		$this->_appSnapshot = TTestApplication::snapshotApp(null, ['_globalization', '_configType', '_runtimePath']);
		$this->_aliasSnapshot = PradoUnit::snapshotStatic(Prado::class, ['_aliases']);
	}

	protected function tearDown(): void
	{
		TTestApplication::restoreApp($this->_appSnapshot);
		PradoUnit::restoreStatic(Prado::class, $this->_aliasSnapshot);
		$this->removeTempDirectories();
	}

	/**
	 * Calls the protected setTranslationConfiguration().
	 */
	private function configure(array|TMap $config): void
	{
		PradoUnit::invoke($this->glob, 'setTranslationConfiguration', $config);
	}

	/**
	 * Asserts that a callable throws a TConfigurationException with an error code.
	 */
	private function assertConfigurationError(string $errorCode, callable $callable): void
	{
		try {
			$callable();
			$this->fail("Expected TConfigurationException '$errorCode'.");
		} catch (TConfigurationException $e) {
			$this->assertSame($errorCode, $e->getErrorCode());
		}
	}

	// ── default state ───────────────────────────────────────────────────────

	public function testDefaultCultureIsEn()
	{
		$this->assertSame('en', $this->glob->getDefaultCulture());
	}

	public function testDefaultCharsetIsUtf8()
	{
		$this->assertSame('UTF-8', $this->glob->getDefaultCharset());
	}

	public function testDefaultTranslateDefaultCultureIsTrue()
	{
		$this->assertTrue($this->glob->getTranslateDefaultCulture());
	}

	public function testGetCultureReturnsNullBeforeInit()
	{
		// setCulture() has never been called and init() has not run.
		$this->assertNull($this->glob->getCulture());
	}

	public function testGetCharsetReturnsNullBeforeInit()
	{
		$this->assertNull($this->glob->getCharset());
	}

	// ── setCulture / getCulture ─────────────────────────────────────────────

	public function testSetCultureStoresPosixForm()
	{
		$this->glob->setCulture('en_US');
		$this->assertSame('en_US', $this->glob->getCulture());
	}

	/**
	 * Browsers and HTTP Accept-Language headers send BCP 47 hyphen-separated
	 * identifiers.  setCulture() must convert them to the POSIX underscore
	 * form so the rest of the framework (file lookup, CultureInfo, etc.) works.
	 */
	public function testSetCultureNormalisesBcp47HyphensToPostixUnderscores()
	{
		$this->glob->setCulture('en-US');
		$this->assertSame('en_US', $this->glob->getCulture());

		$this->glob->setCulture('zh-TW');
		$this->assertSame('zh_TW', $this->glob->getCulture());

		$this->glob->setCulture('fr-FR');
		$this->assertSame('fr_FR', $this->glob->getCulture());
	}

	/** BCP 47 script subtags also use hyphens; all must become underscores. */
	public function testSetCultureNormalisesMultipleHyphens()
	{
		$this->glob->setCulture('zh-Hant-TW');
		$this->assertSame('zh_Hant_TW', $this->glob->getCulture());
	}

	public function testSetCultureIdempotentWhenAlreadyPosix()
	{
		$this->glob->setCulture('de_DE');
		$this->assertSame('de_DE', $this->glob->getCulture());
	}

	public function testSetCultureAcceptsNeutralCulture()
	{
		$this->glob->setCulture('fr');
		$this->assertSame('fr', $this->glob->getCulture());
	}

	/**
	 * When the new value is identical to the current value (after normalisation)
	 * the assignment branch is skipped, so the RTL cache must NOT be cleared.
	 * We hard-set the cache to a known value and verify it survives.
	 */
	public function testSetCultureSameValueDoesNotClearRtlCache()
	{
		$this->glob->setCulture('en');
		$this->glob->setIsCultureRTL(true);        // poison cache with sentinel

		$this->glob->setCulture('en');              // no-op: same value
		$this->assertTrue($this->glob->getIsCultureRTL()); // cache still holds
	}

	/** Changing the culture must invalidate the RTL cache. */
	public function testSetCultureClearsRtlCacheOnChange()
	{
		$this->glob->setCulture('ar');
		$this->glob->getIsCultureRTL();             // populate cache (ar is RTL)
		$this->assertTrue($this->glob->getIsCultureRTL());

		$this->glob->setCulture('en');              // different value → clears cache
		$this->assertFalse($this->glob->getIsCultureRTL()); // recomputed for 'en'
	}

	// ── setDefaultCulture / getDefaultCulture ───────────────────────────────

	public function testSetDefaultCultureStoresPosixForm()
	{
		$this->glob->setDefaultCulture('de_DE');
		$this->assertSame('de_DE', $this->glob->getDefaultCulture());
	}

	/** setDefaultCulture also follows the POSIX normalisation rule. */
	public function testSetDefaultCultureNormalisesBcp47Hyphens()
	{
		$this->glob->setDefaultCulture('zh-TW');
		$this->assertSame('zh_TW', $this->glob->getDefaultCulture());
	}

	// ── charset ─────────────────────────────────────────────────────────────

	public function testSetGetCharset()
	{
		$this->glob->setCharset('ISO-8859-1');
		$this->assertSame('ISO-8859-1', $this->glob->getCharset());
	}

	public function testSetGetDefaultCharset()
	{
		$this->glob->setDefaultCharset('windows-1252');
		$this->assertSame('windows-1252', $this->glob->getDefaultCharset());
	}

	// ── setTranslateDefaultCulture / getTranslateDefaultCulture ────────────

	public function testSetTranslateDefaultCultureWithBoolTrue()
	{
		$this->glob->setTranslateDefaultCulture(true);
		$this->assertTrue($this->glob->getTranslateDefaultCulture());
	}

	public function testSetTranslateDefaultCultureWithBoolFalse()
	{
		$this->glob->setTranslateDefaultCulture(false);
		$this->assertFalse($this->glob->getTranslateDefaultCulture());
	}

	public function testSetTranslateDefaultCultureWithStringTrue()
	{
		$this->glob->setTranslateDefaultCulture('true');
		$this->assertTrue($this->glob->getTranslateDefaultCulture());

		$this->glob->setTranslateDefaultCulture('TRUE');   // case-insensitive
		$this->assertTrue($this->glob->getTranslateDefaultCulture());
	}

	public function testSetTranslateDefaultCultureWithStringFalse()
	{
		$this->glob->setTranslateDefaultCulture('false');
		$this->assertFalse($this->glob->getTranslateDefaultCulture());
	}

	public function testSetTranslateDefaultCultureWithNumericString()
	{
		$this->glob->setTranslateDefaultCulture('1');
		$this->assertTrue($this->glob->getTranslateDefaultCulture());

		$this->glob->setTranslateDefaultCulture('0');
		$this->assertFalse($this->glob->getTranslateDefaultCulture());
	}

	// ── getTranslationConfiguration ─────────────────────────────────────────

	public function testGetTranslationConfigurationReturnsNullWhenNotConfigured()
	{
		// Default: _translateDefaultCulture=true, _translation=null
		$this->assertNull($this->glob->getTranslationConfiguration());
	}

	/**
	 * When TranslateDefaultCulture is false and the current culture matches
	 * the default culture, no translation should be applied — returns null
	 * regardless of whether _translation is set.
	 */
	public function testGetTranslationConfigurationReturnsNullWhenCultureMatchesDefaultAndTranslateDefaultIsOff()
	{
		$this->glob->setTranslateDefaultCulture(false);
		$this->glob->setCulture('en');               // matches default 'en'
		$this->glob->setTranslationCatalogue('messages');

		$this->assertNull($this->glob->getTranslationConfiguration());
	}

	/**
	 * When TranslateDefaultCulture is false but the current culture differs
	 * from the default, the translation configuration IS returned.
	 */
	public function testGetTranslationConfigurationReturnsCatalogueWhenCultureDiffersFromDefault()
	{
		$this->glob->setTranslateDefaultCulture(false);
		$this->glob->setCulture('fr');               // differs from default 'en'
		$this->glob->setTranslationCatalogue('messages');

		$config = $this->glob->getTranslationConfiguration();
		$this->assertNotNull($config);
		$this->assertSame('messages', $config['catalogue']);
	}

	/**
	 * When TranslateDefaultCulture is true (the default), the translation
	 * configuration is always returned, even when culture == defaultCulture.
	 */
	public function testGetTranslationConfigurationReturnsCatalogueWhenTranslateDefaultIsOn()
	{
		$this->glob->setTranslateDefaultCulture(true);
		$this->glob->setCulture('en');               // matches default, but translate=true
		$this->glob->setTranslationCatalogue('messages');

		$config = $this->glob->getTranslationConfiguration();
		$this->assertNotNull($config);
		$this->assertSame('messages', $config['catalogue']);
	}

	// ── setTranslationCatalogue / getTranslationCatalogue ───────────────────

	public function testSetGetTranslationCatalogue()
	{
		$this->glob->setTranslationCatalogue('messages');
		$this->assertSame('messages', $this->glob->getTranslationCatalogue());
	}

	public function testSetTranslationCatalogueCanBeUpdated()
	{
		$this->glob->setTranslationCatalogue('messages');
		$this->glob->setTranslationCatalogue('other');
		$this->assertSame('other', $this->glob->getTranslationCatalogue());
	}

	// ── getCultureVariants ──────────────────────────────────────────────────

	public function testGetCultureVariantsUsesCurrentCultureWhenNullPassed()
	{
		$this->glob->setCulture('en_US');
		$this->assertSame(['en_US', 'en'], $this->glob->getCultureVariants());
	}

	public function testGetCultureVariantsForNeutralCulture()
	{
		$this->glob->setCulture('fr');
		$this->assertSame(['fr'], $this->glob->getCultureVariants());
	}

	public function testGetCultureVariantsForSpecificCulture()
	{
		$this->assertSame(['de_DE', 'de'], $this->glob->getCultureVariants('de_DE'));
	}

	/** Three-level POSIX culture (script subtag) splits into three variants. */
	public function testGetCultureVariantsForThreeLevelCulture()
	{
		$variants = $this->glob->getCultureVariants('zh_Hant_TW');
		$this->assertSame(['zh_Hant_TW', 'zh_Hant', 'zh'], $variants);
	}

	/** Explicit culture param overrides the current culture. */
	public function testGetCultureVariantsExplicitParamOverridesCurrentCulture()
	{
		$this->glob->setCulture('en_US');
		$this->assertSame(['fr_FR', 'fr'], $this->glob->getCultureVariants('fr_FR'));
	}

	/**
	 * BCP 47 input is normalised to POSIX by setCulture() before reaching
	 * getCultureVariants(), so the variants are always underscore-separated.
	 */
	public function testGetCultureVariantsAfterBcp47Normalisation()
	{
		$this->glob->setCulture('zh-TW');           // normalised to zh_TW
		$this->assertSame(['zh_TW', 'zh'], $this->glob->getCultureVariants());
	}

	// ── getLocalizedResource ────────────────────────────────────────────────

	public function testGetLocalizedResourceReturnsAllVariantsInOrder()
	{
		// For en_US the expected order is:
		//   directory variants (most specific first): en_US/, en/
		//   filename variants (most specific first):  .en_US., .en.
		//   original file (fallback)
		$sep = DIRECTORY_SEPARATOR;
		$expected = [
			"path/to{$sep}en_US{$sep}Home.page",
			"path/to{$sep}en{$sep}Home.page",
			"path/to{$sep}Home.en_US.page",
			"path/to{$sep}Home.en.page",
			'path/to/Home.page',
		];

		$this->glob->setCulture('en_US');
		$this->assertSame($expected, $this->glob->getLocalizedResource('path/to/Home.page'));
	}

	public function testGetLocalizedResourceWithExplicitCulture()
	{
		$sep = DIRECTORY_SEPARATOR;
		$this->glob->setCulture('en');

		$files = $this->glob->getLocalizedResource('path/to/Home.page', 'de_DE');

		$this->assertContains("path/to{$sep}de_DE{$sep}Home.page", $files);
		$this->assertContains("path/to{$sep}de{$sep}Home.page", $files);
		$this->assertContains("path/to{$sep}Home.de_DE.page", $files);
		$this->assertContains("path/to{$sep}Home.de.page", $files);
		$this->assertNotContains("path/to{$sep}en{$sep}Home.page", $files); // explicit overrides
	}

	public function testGetLocalizedResourceAlwaysIncludesOriginalFileAsLast()
	{
		$this->glob->setCulture('fr');
		$files = $this->glob->getLocalizedResource('path/to/Home.page');

		$this->assertSame('path/to/Home.page', end($files));
	}

	public function testGetLocalizedResourceForNeutralCultureHasThreeEntries()
	{
		// neutral culture: 1 directory + 1 filename + 1 original = 3
		$this->glob->setCulture('fr');
		$this->assertCount(3, $this->glob->getLocalizedResource('path/to/Home.page'));
	}

	public function testGetLocalizedResourceForSpecificCultureHasFiveEntries()
	{
		// specific culture (2 variants): 2 directory + 2 filename + 1 original = 5
		$this->glob->setCulture('en_US');
		$this->assertCount(5, $this->glob->getLocalizedResource('path/to/Home.page'));
	}

	public function testGetLocalizedResourceForThreeLevelCultureHasSevenEntries()
	{
		// 3 variants: 3 directory + 3 filename + 1 original = 7
		$this->glob->setCulture('zh_Hant_TW');
		$this->assertCount(7, $this->glob->getLocalizedResource('path/to/Home.page'));
	}

	/**
	 * Resource file names follow POSIX underscore convention.  A BCP 47 input
	 * is normalised before lookup so the paths contain underscores, not hyphens.
	 */
	public function testGetLocalizedResourceBcp47InputProducesPosixUnderscorePaths()
	{
		$sep = DIRECTORY_SEPARATOR;
		$this->glob->setCulture('zh-TW');           // normalised to zh_TW

		$files = $this->glob->getLocalizedResource('path/to/Home.page');

		$this->assertContains("path/to{$sep}zh_TW{$sep}Home.page", $files);
		$this->assertContains("path/to{$sep}Home.zh_TW.page", $files);
		$this->assertNotContains("path/to{$sep}zh-TW{$sep}Home.page", $files);
		$this->assertNotContains("path/to{$sep}Home.zh-TW.page", $files);
	}

	// ── getIsCultureRTL / setIsCultureRTL ───────────────────────────────────

	public function testGetIsCultureRtlReturnsFalseForLtrCulture()
	{
		$this->glob->setCulture('en');
		$this->assertFalse($this->glob->getIsCultureRTL());
	}

	public function testGetIsCultureRtlReturnsTrueForRtlCulture()
	{
		$this->glob->setCulture('ar');              // Arabic is right-to-left
		$this->assertTrue($this->glob->getIsCultureRTL());
	}

	/** Result for the current culture is cached after the first call. */
	public function testGetIsCultureRtlCachesResultForCurrentCulture()
	{
		$this->glob->setCulture('en');
		$this->glob->getIsCultureRTL();             // populate cache

		// Poison the hard-set value; if cache is in use the poisoned value is returned.
		$this->glob->setIsCultureRTL(true);
		$this->assertTrue($this->glob->getIsCultureRTL()); // cache holds
	}

	/**
	 * Querying an explicit culture that differs from the current culture must
	 * NOT store the result in the cache; the next null-param call must still
	 * recompute from the current culture.
	 */
	public function testGetIsCultureRtlDoesNotCacheForExplicitDifferentCulture()
	{
		$this->glob->setCulture('en');

		$rtl = $this->glob->getIsCultureRTL('ar'); // compute for 'ar', must not cache
		$this->assertTrue($rtl);

		// 'en' cache must still be empty — hard-set to sentinel to distinguish
		$this->glob->setIsCultureRTL(false);
		$this->assertFalse($this->glob->getIsCultureRTL()); // returns hard-set 'en' cache
	}

	/**
	 * Calling getIsCultureRTL() with an explicit culture equal to the current
	 * culture must use — and populate — the cache just like the null-param form.
	 */
	public function testGetIsCultureRtlExplicitCultureMatchingCurrentUsesCacheSlot()
	{
		$this->glob->setCulture('ar');
		$this->glob->getIsCultureRTL('ar');         // explicit == current → populates cache

		$this->glob->setIsCultureRTL(false);        // overwrite cache to sentinel
		$this->assertFalse($this->glob->getIsCultureRTL()); // cache consulted, returns false
	}

	public function testSetIsCultureRtlHardSetsBoolTrue()
	{
		$this->glob->setCulture('en');
		$this->glob->setIsCultureRTL(true);
		$this->assertTrue($this->glob->getIsCultureRTL());
	}

	public function testSetIsCultureRtlHardSetsBoolFalse()
	{
		$this->glob->setCulture('ar');
		$this->glob->setIsCultureRTL(false);
		$this->assertFalse($this->glob->getIsCultureRTL());
	}

	public function testSetIsCultureRtlAcceptsStringBooleans()
	{
		$this->glob->setCulture('en');

		$this->glob->setIsCultureRTL('true');
		$this->assertTrue($this->glob->getIsCultureRTL());

		$this->glob->setIsCultureRTL('false');
		$this->assertFalse($this->glob->getIsCultureRTL());
	}

	public function testSetIsCultureRtlAcceptsNumericStrings()
	{
		$this->glob->setCulture('en');

		$this->glob->setIsCultureRTL('1');
		$this->assertTrue($this->glob->getIsCultureRTL());

		$this->glob->setIsCultureRTL('0');
		$this->assertFalse($this->glob->getIsCultureRTL());
	}

	// ── init ────────────────────────────────────────────────────────────────

	public function testInitWithoutConfigurationSetsTheDefaultsAndRegisters()
	{
		$this->glob->setDefaultCulture('fr_FR');
		$this->glob->setDefaultCharset('ISO-8859-1');
		$this->glob->init(null);
		$this->assertSame('fr_FR', $this->glob->getCulture());
		$this->assertSame('ISO-8859-1', $this->glob->getCharset());
		$this->assertNull($this->glob->getTranslationConfiguration());
		$this->assertSame($this->glob, Prado::getApplication()->getGlobalization(false));
	}

	public function testInitKeepsAnExplicitCultureAndCharset()
	{
		$this->glob->setCulture('de');
		$this->glob->setCharset('Windows-1252');
		$this->glob->init(null);
		$this->assertSame('de', $this->glob->getCulture());
		$this->assertSame('Windows-1252', $this->glob->getCharset());
	}

	public function testInitAppliesTheXmlTranslationElement()
	{
		PradoUnit::setProp(Prado::getApplication(), '_configType', TApplication::CONFIG_TYPE_XML);
		$xml = new TXmlDocument();
		$xml->loadFromString('<module id="globalization"><translation type="Database" source="db1" catalogue="app" /></module>');
		$this->glob->init($xml);
		$config = $this->glob->getTranslationConfiguration();
		$this->assertInstanceOf(TMap::class, $config);
		$this->assertSame('Database', $config['type']);
		$this->assertSame('db1', $config['source']);
		$this->assertSame('app', $this->glob->getTranslationCatalogue());
	}

	public function testInitWithoutAnXmlTranslationElement()
	{
		PradoUnit::setProp(Prado::getApplication(), '_configType', TApplication::CONFIG_TYPE_XML);
		$xml = new TXmlDocument();
		$xml->loadFromString('<module id="globalization" />');
		$this->glob->init($xml);
		$this->assertNull($this->glob->getTranslationConfiguration());
	}

	public function testInitAppliesThePhpTranslateArray()
	{
		PradoUnit::setProp(Prado::getApplication(), '_configType', TApplication::CONFIG_TYPE_PHP);
		$this->glob->init(['translate' => ['type' => 'Database', 'source' => 'db1']]);
		$this->assertSame(['type' => 'Database', 'source' => 'db1'], $this->glob->getTranslationConfiguration());
		$this->assertNull($this->glob->getTranslationCatalogue());
	}

	public function testInitWithoutAPhpTranslateArray()
	{
		PradoUnit::setProp(Prado::getApplication(), '_configType', TApplication::CONFIG_TYPE_PHP);
		$this->glob->init([]);
		$this->assertNull($this->glob->getTranslationConfiguration());
	}

	public function testInitWithoutAnApplicationDoesNotRegister()
	{
		$glob = new TGlobalizationWithoutApplication();
		PradoUnit::invoke($glob, 'setAppGlobalization');
		$this->assertNotSame($glob, Prado::getApplication()->getGlobalization(false));
	}

	// ── setTranslationConfiguration ─────────────────────────────────────────

	public function testGetTranslationCatalogueIsNullWithoutConfiguration()
	{
		$this->assertNull($this->glob->getTranslationCatalogue());
	}

	public function testFileSourceResolvesAnExistingNamespacePath()
	{
		$dir = $this->makeTempDirectory();
		mkdir($dir . '/messages');
		Prado::setPathOfAlias('I18NTestRoot', $dir);
		$this->configure(['type' => 'XLIFF', 'source' => 'I18NTestRoot.messages']);
		$this->assertSame($dir . DIRECTORY_SEPARATOR . 'messages', $this->glob->getTranslationConfiguration()['source']);
	}

	public function testFileSourceCreatesAMissingDirectory()
	{
		$dir = $this->makeTempDirectory();
		Prado::setPathOfAlias('I18NTestRoot', $dir);
		foreach (['gettext', 'PHP'] as $type) {
			$this->configure(['type' => $type, 'source' => 'I18NTestRoot.' . $type]);
			$path = $dir . DIRECTORY_SEPARATOR . $type;
			$this->assertSame($path, $this->glob->getTranslationConfiguration()['source']);
			$this->assertDirectoryExists($path);
			if (PHP_OS_FAMILY !== 'Windows') {
				$this->assertSame(Prado::getDefaultDirPermissions(), fileperms($path) & 0o777);
			}
		}
	}

	public function testFileSourceThatCannotBeCreatedThrows()
	{
		$dir = $this->makeTempDirectory();
		touch($dir . '/blocker');
		Prado::setPathOfAlias('I18NTestRoot', $dir);
		$this->assertConfigurationError('globalization_source_path_failed', fn () => $this->configure(['type' => 'XLIFF', 'source' => 'I18NTestRoot.blocker.messages']));
	}

	public function testFileSourceIsRequired()
	{
		$this->assertConfigurationError('globalization_source_required', fn () => $this->configure(['type' => 'PHP']));
		$this->assertConfigurationError('globalization_source_required', fn () => $this->configure(new TMap(['type' => 'gettext', 'source' => ''])));
	}

	public function testFileSourceMustBeANamespacePath()
	{
		$this->assertConfigurationError('globalization_source_invalid', fn () => $this->configure(['type' => 'XLIFF', 'source' => 'NoSuchI18NAlias.messages']));
		$this->assertConfigurationError('globalization_source_invalid', fn () => $this->configure(['type' => 'XLIFF', 'source' => 'messages']));
	}

	public function testDatabaseAndUntypedSourcesAreKept()
	{
		$this->configure(['type' => 'Database', 'source' => 'db1']);
		$this->assertSame(['type' => 'Database', 'source' => 'db1'], $this->glob->getTranslationConfiguration());
		$this->configure(['source' => 'anything']);
		$this->assertSame(['source' => 'anything'], $this->glob->getTranslationConfiguration());
	}

	public function testCacheCreatesTheRuntimeI18NDirectory()
	{
		$runtime = $this->makeTempDirectory();
		PradoUnit::setProp(Prado::getApplication(), '_runtimePath', $runtime);
		$path = $runtime . '/i18n';
		$this->configure(['type' => 'Database', 'source' => 'db1', 'cache' => 'true']);
		$this->assertSame($path, $this->glob->getTranslationConfiguration()['cache']);
		$this->assertDirectoryExists($path);
		if (PHP_OS_FAMILY !== 'Windows') {
			$this->assertSame(Prado::getDefaultDirPermissions(), fileperms($path) & 0o777);
		}

		$this->configure(['type' => 'Database', 'source' => 'db1', 'cache' => true]);
		$this->assertSame($path, $this->glob->getTranslationConfiguration()['cache']);
	}

	public function testCacheDirectoryThatCannotBeCreatedThrows()
	{
		$runtime = $this->makeTempDirectory();
		touch($runtime . '/blocker');
		PradoUnit::setProp(Prado::getApplication(), '_runtimePath', $runtime . '/blocker');
		$this->assertConfigurationError('globalization_cache_path_failed', fn () => $this->configure(['type' => 'Database', 'source' => 'db1', 'cache' => 'true']));
	}

	public function testCacheOffIsRemoved()
	{
		$this->configure(['type' => 'Database', 'source' => 'db1', 'cache' => 'false']);
		$this->assertSame(['type' => 'Database', 'source' => 'db1'], $this->glob->getTranslationConfiguration());
	}
}
