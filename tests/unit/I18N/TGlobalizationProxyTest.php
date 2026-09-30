<?php

/**
 * TGlobalizationProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\I18N;

use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\I18N\TGlobalization;
use Prado\I18N\TGlobalizationProxy;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TApplication;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\Log\TLogger;
use Prado\Util\TBehavior;
use Prado\Xml\TXmlElement;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A TGlobalization subclass exposing an extra property, method, and event to
 * exercise the trait's dispatch and event forwarding.
 */
class TGlobalizationProxyBackend extends TGlobalization
{
	private ?string $_customProp = null;

	public function getCustomProp(): ?string
	{
		return $this->_customProp;
	}

	public function setCustomProp(?string $value): void
	{
		$this->_customProp = $value;
	}

	public function customMethod(string $arg): string
	{
		return 'custom:' . $arg;
	}

	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}
}

/**
 * A TModule that is NOT a TGlobalization, used to test the invalid-type guard.
 */
class TGlobalizationProxyNotAGlobalizationModule extends TModule
{
}

/**
 * A TBehavior with a public on[A-Z]* event, used to verify that attachProxy()
 * discovers events exposed by behaviors attached to the backing module.
 */
class TGlobalizationProxyBehaviorWithEvent extends TBehavior
{
	public function onBehaviorEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnBehaviorEvent', $this->getOwner(), $param);
	}
}

/**
 * Exposes protected internals for direct testing.
 */
class TGlobalizationProxyAccessor extends TGlobalizationProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetGlobalizationDirect(): ?TGlobalization
	{
		return $this->getGlobalizationDirect();
	}

	public function pubHasTranslationConfig($config): bool
	{
		return $this->hasTranslationConfig($config);
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TGlobalizationProxyTest class.
 *
 * Tests TGlobalizationProxy: BackingGlobalizationId property, lazy resolution,
 * init() validation and registration, translation rejection, IModuleDependency,
 * transparent TGlobalization delegation, change logging, isa transparency,
 * cloning, serialization, and event forwarding.
 */
class TGlobalizationProxyTest extends \PHPUnit\Framework\TestCase
{
	private static string $mockAppPath;

	private ?TApplication $app = null;

	private TGlobalizationProxyBackend $backend;

	private TGlobalizationProxyAccessor $proxy;

	public static function setUpBeforeClass(): void
	{
		self::$mockAppPath = __DIR__ . '/../Caching/mockapp';
	}

	protected function setUp(): void
	{
		$this->app = new TApplication(self::$mockAppPath);

		$this->backend = new TGlobalizationProxyBackend();
		$this->backend->init(null);
		$this->app->setModule('backingGlob', $this->backend);

		// Build the proxy but do NOT call init(); tests that need it call it.
		$this->proxy = new TGlobalizationProxyAccessor();
		$this->proxy->setBackingGlobalizationId('backingGlob');
	}

	protected function tearDown(): void
	{
		$this->proxy->unlisten();
		$this->backend->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTGlobalizationProxy(): void
	{
		$this->assertInstanceOf(TGlobalizationProxy::class, $this->proxy);
	}

	public function testExtendsTGlobalization(): void
	{
		$this->assertInstanceOf(TGlobalization::class, $this->proxy);
	}

	public function testImplementsIModuleDependency(): void
	{
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
	}

	public function testImplementsIProxy(): void
	{
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	// ── BackingGlobalizationId property ──────────────────────────────────────────

	public function testDefaultBackingGlobalizationIdIsEmptyString(): void
	{
		$fresh = new TGlobalizationProxy();
		$this->assertSame('', $fresh->getBackingGlobalizationId());
	}

	public function testSetGetBackingGlobalizationId(): void
	{
		$proxy = new TGlobalizationProxy();
		$proxy->setBackingGlobalizationId('myGlob');
		$this->assertSame('myGlob', $proxy->getBackingGlobalizationId());
	}

	public function testSetBackingGlobalizationIdSameValueIsNoOp(): void
	{
		$cat = 'prado.i18n';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingGlobalizationId('backingGlob');

		$this->assertSame('backingGlob', $this->proxy->getBackingGlobalizationId());
		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingGlobalizationIdFromEmptyDoesNotLog(): void
	{
		$cat = 'prado.i18n';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$proxy = new TGlobalizationProxy();
		$proxy->setBackingGlobalizationId('backingGlob');

		$this->assertSame($before, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingGlobalizationIdChangeLogsWarning(): void
	{
		$cat = 'prado.i18n';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingGlobalizationId('otherGlob');

		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingGlobalizationIdChangeLogMessageContainsBothIds(): void
	{
		$this->proxy->setBackingGlobalizationId('replacementGlob');

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.i18n');
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingGlob', $msg);
		$this->assertStringContainsString('replacementGlob', $msg);
	}

	public function testMultipleBackingGlobalizationIdChangesEachLog(): void
	{
		$cat = 'prado.i18n';
		$before = $this->countLogs(TLogger::WARNING, $cat);

		$this->proxy->setBackingGlobalizationId('first');
		$this->proxy->setBackingGlobalizationId('second');

		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, $cat));
	}

	public function testSetBackingGlobalizationIdInvalidatesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getGlobalization();

		$secondBackend = new TGlobalizationProxyBackend();
		$secondBackend->init(null);
		$this->app->setModule('secondGlob', $secondBackend);

		$this->proxy->setBackingGlobalizationId('secondGlob');
		$second = $this->proxy->getGlobalization();

		$this->assertNotSame($first, $second);
		$this->assertSame($secondBackend, $second);
		$secondBackend->unlisten();
	}

	public function testGetProxyOwnPropertyUsesProxyGetter(): void
	{
		$this->assertSame('backingGlob', $this->proxy->BackingGlobalizationId);
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenIdEmpty(): void
	{
		$proxy = new TGlobalizationProxy();
		$this->assertNull($proxy->getModuleDependencies());
		$this->assertNull($proxy->getModuleDependencies(true));
	}

	public function testGetModuleDependenciesReturnsBackingId(): void
	{
		$this->assertSame('backingGlob', $this->proxy->getModuleDependencies());
		$this->assertSame('backingGlob', $this->proxy->getModuleDependencies(true));
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitSucceedsWhenBackingGlobalizationIdIsSet(): void
	{
		$this->proxy->init(null);
		$this->assertSame('backingGlob', $this->proxy->getBackingGlobalizationId());
	}

	public function testInitThrowsWhenBackingGlobalizationIdIsEmpty(): void
	{
		$proxy = new TGlobalizationProxy();

		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('globalizationproxy_backing_globalization_id_required', $e->getErrorCode());
		}
	}

	public function testInitRegistersProxyAsApplicationGlobalization(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->proxy, $this->app->getGlobalization());
	}

	public function testInitDoesNotResolveBacking(): void
	{
		$this->proxy->init(null);
		$this->assertNull($this->proxy->pubGetGlobalizationDirect());
	}

	public function testInitRaisesDyInitOnBehaviors(): void
	{
		$behavior = new class () extends TBehavior {
			public mixed $received = 'unset';

			public function dyInit($config): void
			{
				$this->received = $config;
			}
		};
		$this->proxy->attachBehavior('initSpy', $behavior);

		$this->proxy->init(['properties' => []]);

		$this->assertSame(['properties' => []], $behavior->received);
	}

	public function testInitWithPlainXmlConfigSucceeds(): void
	{
		$config = new TXmlElement('module');
		$this->proxy->init($config);
		$this->assertSame($this->proxy, $this->app->getGlobalization());
	}

	// ── translation rejection ────────────────────────────────────────────────────

	public function testInitThrowsWhenXmlConfigHasTranslationElement(): void
	{
		$config = new TXmlElement('module');
		$config->getElements()->add(new TXmlElement('translation'));

		try {
			$this->proxy->init($config);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('globalizationproxy_translation_not_allowed', $e->getErrorCode());
		}
	}

	public function testInitThrowsWhenPhpConfigHasTranslateKey(): void
	{
		try {
			$this->proxy->init(['translate' => ['type' => 'PHP']]);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('globalizationproxy_translation_not_allowed', $e->getErrorCode());
		}
	}

	public function testInitThrowsWhenPhpConfigHasTranslationKey(): void
	{
		try {
			$this->proxy->init(['translation' => ['type' => 'PHP']]);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('globalizationproxy_translation_not_allowed', $e->getErrorCode());
		}
	}

	public function testInitTranslationRejectionDoesNotRegisterProxy(): void
	{
		try {
			$this->proxy->init(['translate' => ['type' => 'PHP']]);
		} catch (TConfigurationException $e) {
		}
		$this->assertNotSame($this->proxy, $this->app->getGlobalization(false));
	}

	public function testHasTranslationConfigForNullAndPlainConfigs(): void
	{
		$this->assertFalse($this->proxy->pubHasTranslationConfig(null));
		$this->assertFalse($this->proxy->pubHasTranslationConfig([]));
		$this->assertFalse($this->proxy->pubHasTranslationConfig(new TXmlElement('module')));
	}

	// ── getGlobalization() ──────────────────────────────────────────────────────

	public function testGetGlobalizationReturnsBackingModule(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->backend, $this->proxy->getGlobalization());
	}

	public function testGetGlobalizationCachesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getGlobalization();
		$second = $this->proxy->getGlobalization();
		$this->assertSame($first, $second);
		$this->assertSame($this->backend, $this->proxy->pubGetGlobalizationDirect());
	}

	public function testGetProxyBackingReturnsBackingModule(): void
	{
		$this->assertSame($this->backend, $this->proxy->getProxyBacking());
	}

	public function testGetGlobalizationThrowsWhenIdIsEmpty(): void
	{
		$proxy = new TGlobalizationProxy();

		try {
			$proxy->getGlobalization();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('globalizationproxy_backing_globalization_id_required', $e->getErrorCode());
		}
	}

	public function testGetGlobalizationThrowsWhenModuleNotFound(): void
	{
		$proxy = new TGlobalizationProxyAccessor();
		$proxy->setBackingGlobalizationId('missingModule');

		try {
			$proxy->getGlobalization();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('globalizationproxy_globalization_not_found', $e->getErrorCode());
		}
	}

	public function testGetGlobalizationThrowsWhenModuleIsNotTGlobalization(): void
	{
		$notAGlob = new TGlobalizationProxyNotAGlobalizationModule();
		$this->app->setModule('notGlob', $notAGlob);

		$proxy = new TGlobalizationProxyAccessor();
		$proxy->setBackingGlobalizationId('notGlob');

		try {
			$proxy->getGlobalization();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('globalizationproxy_invalid_globalization_type', $e->getErrorCode());
		} finally {
			$proxy->unlisten();
			$notAGlob->unlisten();
		}
	}

	public function testSetGlobalizationPropertyIsReadOnly(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->Globalization = 'anything';
	}

	// ── Delegation: Culture / Charset ────────────────────────────────────────────

	public function testGetCultureDelegatesToBacking(): void
	{
		$this->backend->setCulture('fr_FR');
		$this->assertSame('fr_FR', $this->proxy->getCulture());
	}

	public function testSetCultureDelegatesToBacking(): void
	{
		$this->proxy->setCulture('de-DE');
		$this->assertSame('de_DE', $this->backend->getCulture());
		$this->assertSame('de_DE', $this->proxy->getCulture());
	}

	public function testCulturePropertyAccessDelegates(): void
	{
		$this->proxy->Culture = 'zh_TW';
		$this->assertSame('zh_TW', $this->backend->getCulture());
		$this->assertSame('zh_TW', $this->proxy->Culture);
	}

	public function testGetCharsetDelegatesToBacking(): void
	{
		$this->backend->setCharset('ISO-8859-1');
		$this->assertSame('ISO-8859-1', $this->proxy->getCharset());
	}

	public function testSetCharsetDelegatesToBacking(): void
	{
		$this->proxy->setCharset('ISO-8859-15');
		$this->assertSame('ISO-8859-15', $this->backend->getCharset());
	}

	// ── Delegation: DefaultCulture / DefaultCharset / TranslateDefaultCulture ────

	public function testGetDefaultCultureDelegatesToBacking(): void
	{
		$this->backend->setDefaultCulture('es_ES');
		$this->assertSame('es_ES', $this->proxy->getDefaultCulture());
	}

	public function testSetDefaultCultureDelegatesToBacking(): void
	{
		$this->proxy->setDefaultCulture('pt-BR');
		$this->assertSame('pt_BR', $this->backend->getDefaultCulture());
	}

	public function testGetDefaultCharsetDelegatesToBacking(): void
	{
		$this->backend->setDefaultCharset('ASCII');
		$this->assertSame('ASCII', $this->proxy->getDefaultCharset());
	}

	public function testSetDefaultCharsetDelegatesToBacking(): void
	{
		$this->proxy->setDefaultCharset('Windows-1252');
		$this->assertSame('Windows-1252', $this->backend->getDefaultCharset());
	}

	public function testTranslateDefaultCultureDelegatesToBacking(): void
	{
		$this->assertTrue($this->proxy->getTranslateDefaultCulture());
		$this->proxy->setTranslateDefaultCulture(false);
		$this->assertFalse($this->backend->getTranslateDefaultCulture());
		$this->assertFalse($this->proxy->getTranslateDefaultCulture());
	}

	// ── Delegation: translation configuration / catalogue ────────────────────────

	public function testGetTranslationConfigurationDelegatesToBacking(): void
	{
		$this->assertNull($this->proxy->getTranslationConfiguration());
	}

	public function testTranslationCatalogueDelegatesToBacking(): void
	{
		$this->proxy->setTranslationCatalogue('admin');
		$this->assertSame('admin', $this->backend->getTranslationCatalogue());
		$this->assertSame('admin', $this->proxy->getTranslationCatalogue());
	}

	// ── Delegation: getCultureVariants / getLocalizedResource ────────────────────

	public function testGetCultureVariantsDelegatesToBacking(): void
	{
		$this->assertSame(['zh_Hant_TW', 'zh_Hant', 'zh'], $this->proxy->getCultureVariants('zh_Hant_TW'));
	}

	public function testGetCultureVariantsUsesBackingCurrentCulture(): void
	{
		$this->backend->setCulture('en_GB');
		$this->assertSame(['en_GB', 'en'], $this->proxy->getCultureVariants());
	}

	public function testGetLocalizedResourceDelegatesToBacking(): void
	{
		$this->assertSame(
			$this->backend->getLocalizedResource('path/to/Home.page', 'en_US'),
			$this->proxy->getLocalizedResource('path/to/Home.page', 'en_US')
		);
	}

	public function testGetLocalizedResourceUsesBackingCurrentCulture(): void
	{
		$this->backend->setCulture('fr_CA');
		$files = $this->proxy->getLocalizedResource('path/to/Home.page');
		$this->assertContains('path/to' . DIRECTORY_SEPARATOR . 'fr_CA' . DIRECTORY_SEPARATOR . 'Home.page', $files);
		$this->assertSame('path/to/Home.page', end($files));
	}

	// ── Delegation: IsCultureRTL ─────────────────────────────────────────────────

	public function testSetIsCultureRTLDelegatesToBacking(): void
	{
		$this->proxy->setIsCultureRTL(true);
		$this->assertTrue($this->backend->getIsCultureRTL());
		$this->assertTrue($this->proxy->getIsCultureRTL());
	}

	public function testGetIsCultureRTLForExplicitCultureDelegatesToBacking(): void
	{
		$this->assertSame($this->backend->getIsCultureRTL('en_US'), $this->proxy->getIsCultureRTL('en_US'));
	}

	// ── Transparency: proxy and backing share state ──────────────────────────────

	public function testProxyDoesNotHoldItsOwnCulture(): void
	{
		$this->proxy->init(null);
		$this->proxy->setCulture('it_IT');
		$this->backend->setCulture('ja_JP');
		$this->assertSame('ja_JP', $this->proxy->getCulture());
	}

	// ── __call / __get / __set passthrough ───────────────────────────────────────

	public function testCallForwardsPublicMethodToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertSame('custom:hello', $this->proxy->customMethod('hello'));
	}

	public function testCallDoesNotForwardDyEvent(): void
	{
		$this->proxy->init(null);
		$this->assertSame('value', $this->proxy->dyCustomEvent('value'));
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	public function testGetForwardsBackingSpecificProperty(): void
	{
		$this->backend->setCustomProp('hello');
		$this->assertSame('hello', $this->proxy->CustomProp);
	}

	public function testSetForwardsBackingSpecificProperty(): void
	{
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backend->getCustomProp());
	}

	public function testIssetAndUnsetForwardBackingSpecificProperty(): void
	{
		$this->assertFalse(isset($this->proxy->CustomProp));
		$this->proxy->CustomProp = 'set';
		$this->assertTrue(isset($this->proxy->CustomProp));
		unset($this->proxy->CustomProp);
		$this->assertFalse(isset($this->proxy->CustomProp));
	}

	public function testGetUndefinedPropertyThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$_ = $this->proxy->CompletelyUndefinedProperty;
	}

	// ── isa() transparency ───────────────────────────────────────────────────────

	public function testIsaReturnsTrueForProxyOwnClass(): void
	{
		$this->assertTrue($this->proxy->isa(TGlobalizationProxy::class));
		$this->assertTrue($this->proxy->isa(TGlobalization::class));
		$this->assertTrue($this->proxy->isa(IProxy::class));
	}

	public function testIsaLazilyResolvesBackingClass(): void
	{
		$this->assertNull($this->proxy->pubGetGlobalizationDirect());
		$this->assertTrue($this->proxy->isa(TGlobalizationProxyBackend::class));
		$this->assertNotNull($this->proxy->pubGetGlobalizationDirect());
	}

	public function testIsaReturnsFalseForUnrelatedClass(): void
	{
		$this->assertFalse($this->proxy->isa(TGlobalizationProxyNotAGlobalizationModule::class));
	}

	public function testIsaReturnsFalseWhenNoBackingIdSet(): void
	{
		$proxy = new TGlobalizationProxyAccessor();
		$this->assertFalse($proxy->isa(TGlobalizationProxyBackend::class));
	}

	public function testIsaReturnsFalseWhenBackingCannotBeResolved(): void
	{
		$proxy = new TGlobalizationProxyAccessor();
		$proxy->setBackingGlobalizationId('missingModule');
		$this->assertFalse($proxy->isa(TGlobalizationProxyBackend::class));
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneClearsBackingReference(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();

		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetGlobalizationDirect());
	}

	public function testClonePreservesBackingGlobalizationId(): void
	{
		$clone = clone $this->proxy;
		$this->assertSame('backingGlob', $clone->getBackingGlobalizationId());
	}

	public function testCloneReresolvesBackingOnFirstUse(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;
		$this->assertSame($this->backend, $clone->getGlobalization());
	}

	public function testCloneIsIndependentOfOriginal(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;

		$secondBackend = new TGlobalizationProxyBackend();
		$secondBackend->init(null);
		$this->app->setModule('secondGlob', $secondBackend);

		$clone->setBackingGlobalizationId('secondGlob');

		$this->assertSame($this->backend, $this->proxy->getGlobalization());
		$this->assertSame($secondBackend, $clone->getGlobalization());
		$secondBackend->unlisten();
	}

	public function testCloneDetachesProxyEvents(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$clone = clone $this->proxy;

		$this->assertFalse($clone->hasEvent('OnTestEvent'));
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	// ── _getZappableSleepProps ───────────────────────────────────────────────────

	public function testZappableAlwaysExcludesBackingReference(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TGlobalizationProxy::class . "\0_proxyBacking", $exprops);
	}

	public function testZappableAlwaysExcludesProxyEventNames(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TGlobalizationProxy::class . "\0_proxyEventNames", $exprops);
	}

	public function testZappableExcludesBackingGlobalizationIdWhenEmpty(): void
	{
		$proxy = new TGlobalizationProxyAccessor();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TGlobalizationProxy::class . "\0_backingGlobalizationId", $exprops);
	}

	public function testZappableKeepsBackingGlobalizationIdWhenNonEmpty(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertNotContains("\0" . TGlobalizationProxy::class . "\0_backingGlobalizationId", $exprops);
	}

	public function testSerializeRoundTripKeepsIdAndReresolves(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();

		/** @var TGlobalizationProxyAccessor $restored */
		$restored = unserialize(serialize($this->proxy));

		$this->assertSame('backingGlob', $restored->getBackingGlobalizationId());
		$this->assertNull($restored->pubGetGlobalizationDirect());
		$this->assertSame($this->backend, $restored->getGlobalization());
		$restored->unlisten();
	}

	// ── attachProxy / detachProxy ────────────────────────────────────────────────

	public function testHasEventReturnsFalseForBackingEventBeforeAttach(): void
	{
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testGetGlobalizationTriggersAttachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testHandlerAddedViaProxyFiresOnBackingRaise(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();

		$fired = false;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->backend->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
	}

	public function testProxyEventCollectionIsIndependentOfBacking(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();

		$handlers = $this->proxy->OnTestEvent;
		$this->assertInstanceOf(TWeakCallableCollection::class, $handlers);
		$this->assertNotSame($this->backend->getEventHandlers('OnTestEvent'), $handlers);
	}

	public function testDetachProxyClearsEventSharing(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->detachProxy();
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testBackingGlobalizationIdChangeCallsDetachProxy(): void
	{
		$this->proxy->init(null);
		$this->proxy->getGlobalization();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->setBackingGlobalizationId('someOtherGlob');
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testAttachProxyIncludesBehaviorProvidedOnEvent(): void
	{
		$this->backend->attachBehavior('testBehavior', new TGlobalizationProxyBehaviorWithEvent());

		$this->proxy->init(null);
		$this->proxy->getGlobalization();

		$this->assertTrue($this->proxy->hasEvent('OnBehaviorEvent'));

		$fired = false;
		$this->proxy->OnBehaviorEvent = function () use (&$fired) {
			$fired = true;
		};
		$behaviors = $this->backend->getBehaviors(TGlobalizationProxyBehaviorWithEvent::class);
		/** @var TGlobalizationProxyBehaviorWithEvent $beh */
		$beh = reset($behaviors);
		$beh->onBehaviorEvent(new TEventParameter());

		$this->assertTrue($fired);
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}
}
