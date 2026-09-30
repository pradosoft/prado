<?php

/**
 * TSecurityManagerProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Security;

use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\Security\TSecurityManager;
use Prado\Security\TSecurityManagerProxy;
use Prado\TApplication;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\Log\TLogger;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A TSecurityManager with an extra property, method, and `on` event, used as the
 * backing module to exercise forwarding.
 */
class TSecurityManagerProxyBackend extends TSecurityManager
{
	/** @var ?string used to exercise __get/__set/__isset/__unset forwarding */
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
 * A TModule that is NOT a TSecurityManager, used to test the invalid-type guard.
 */
class TSecurityManagerProxyNotASecurityManagerModule extends TModule
{
}

/**
 * Exposes protected internals for direct testing.
 */
class TSecurityManagerProxyAccessor extends TSecurityManagerProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetSecurityManagerDirect(): ?TSecurityManager
	{
		return $this->getSecurityManagerDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TSecurityManagerProxyTest class.
 *
 * Tests TSecurityManagerProxy: BackingSecurityManagerId property, lazy resolution,
 * init() validation, IModuleDependency, delegation of the security operations and
 * key properties, change logging, event forwarding, cloning, and serialization.
 */
class TSecurityManagerProxyTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $app = null;

	private TSecurityManagerProxyBackend $backend;

	private TSecurityManagerProxyAccessor $proxy;

	protected function setUp(): void
	{
		$this->app = new TApplication(__DIR__ . '/../Caching/mockapp');

		$this->backend = new TSecurityManagerProxyBackend();
		$this->backend->setValidationKey('backend-validation-key');
		$this->backend->setEncryptionKey('backend-encryption-key');
		$this->backend->init(null);
		$this->app->setModule('backingSm', $this->backend);

		// Build the proxy but do NOT call init(); tests that need it call it.
		$this->proxy = new TSecurityManagerProxyAccessor();
		$this->proxy->setBackingSecurityManagerId('backingSm');
	}

	protected function tearDown(): void
	{
		\Prado\Util\TSerializableClosure::setSecretKey(null);
		\Prado\Util\TSerializableClosure::setEncryptionUsing(null, null);
		$this->proxy->unlisten();
		$this->backend->unlisten();
		$this->app->unlisten();
		$this->app = null;
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTSecurityManagerProxy(): void
	{
		$this->assertInstanceOf(TSecurityManagerProxy::class, $this->proxy);
		$this->assertInstanceOf(TSecurityManager::class, $this->proxy);
	}

	public function testImplementsIModuleDependencyAndIProxy(): void
	{
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	// ── BackingSecurityManagerId ─────────────────────────────────────────────────

	public function testDefaultBackingSecurityManagerIdIsEmptyString(): void
	{
		$fresh = new TSecurityManagerProxy();
		$this->assertSame('', $fresh->getBackingSecurityManagerId());
	}

	public function testSetGetBackingSecurityManagerId(): void
	{
		$proxy = new TSecurityManagerProxy();
		$proxy->setBackingSecurityManagerId('mySm');
		$this->assertSame('mySm', $proxy->getBackingSecurityManagerId());
		$this->assertSame('mySm', $proxy->BackingSecurityManagerId);
	}

	public function testSetBackingSecurityManagerIdFromEmptyDoesNotLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.security');
		$proxy = new TSecurityManagerProxy();
		$proxy->setBackingSecurityManagerId('backingSm');
		$this->assertSame($before, $this->countLogs(TLogger::WARNING, 'prado.security'));
	}

	public function testSetBackingSecurityManagerIdSameValueDoesNotLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.security');
		$this->proxy->setBackingSecurityManagerId('backingSm');
		$this->assertSame($before, $this->countLogs(TLogger::WARNING, 'prado.security'));
	}

	public function testSetBackingSecurityManagerIdChangeLogsWarningWithBothIds(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.security');
		$this->proxy->setBackingSecurityManagerId('replacementSm');
		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, 'prado.security'));

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.security');
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingSm', $msg);
		$this->assertStringContainsString('replacementSm', $msg);
	}

	public function testMultipleBackingSecurityManagerIdChangesEachLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.security');
		$this->proxy->setBackingSecurityManagerId('first');
		$this->proxy->setBackingSecurityManagerId('second');
		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, 'prado.security'));
	}

	public function testSetBackingSecurityManagerIdInvalidatesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getSecurityManager();

		$second = new TSecurityManagerProxyBackend();
		$second->setValidationKey('second-key');
		$second->init(null);
		$this->app->setModule('secondSm', $second);

		$this->proxy->setBackingSecurityManagerId('secondSm');
		$this->assertNull($this->proxy->pubGetSecurityManagerDirect());
		$this->assertNotSame($first, $this->proxy->getSecurityManager());
		$this->assertSame($second, $this->proxy->getSecurityManager());
		$second->unlisten();
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenIdEmpty(): void
	{
		$proxy = new TSecurityManagerProxy();
		$this->assertNull($proxy->getModuleDependencies());
		$this->assertNull($proxy->getModuleDependencies(true));
	}

	public function testGetModuleDependenciesReturnsBackingId(): void
	{
		$this->assertSame('backingSm', $this->proxy->getModuleDependencies());
		$this->assertSame('backingSm', $this->proxy->getModuleDependencies(true));
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitThrowsWhenBackingSecurityManagerIdIsEmpty(): void
	{
		$proxy = new TSecurityManagerProxy();
		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('securitymanagerproxy_backing_security_manager_id_required', $e->getErrorCode());
		}
	}

	public function testInitRegistersProxyAsApplicationSecurityManager(): void
	{
		$this->assertSame($this->backend, $this->app->getSecurityManager());
		$this->proxy->init(null);
		$this->assertSame($this->proxy, $this->app->getSecurityManager());
	}

	public function testInitDoesNotResolveBacking(): void
	{
		$this->proxy->init(null);
		$this->assertNull($this->proxy->pubGetSecurityManagerDirect());
	}

	public function testInitDoesNotReplaceSerializableClosureEncrypter(): void
	{
		$marker = static fn (string $data): string => 'marker:' . $data;
		\Prado\Util\TSerializableClosure::setEncryptionUsing($marker, $marker);
		$this->proxy->init(null);
		$reflection = new \ReflectionClass(\Prado\Util\TSerializableClosure::class);
		$this->assertSame($marker, $reflection->getStaticPropertyValue('encryptUsing'));
		$this->assertSame($marker, $reflection->getStaticPropertyValue('decryptUsing'));
	}

	// ── getSecurityManager() ────────────────────────────────────────────────────

	public function testGetSecurityManagerReturnsBackingModule(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->backend, $this->proxy->getSecurityManager());
		$this->assertSame($this->backend, $this->proxy->getProxyBacking());
	}

	public function testGetSecurityManagerCachesResolvedReference(): void
	{
		$first = $this->proxy->getSecurityManager();
		$this->assertSame($this->backend, $this->proxy->pubGetSecurityManagerDirect());
		$this->assertSame($first, $this->proxy->getSecurityManager());
	}

	public function testGetSecurityManagerThrowsWhenIdEmpty(): void
	{
		$proxy = new TSecurityManagerProxy();
		try {
			$proxy->getSecurityManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('securitymanagerproxy_backing_security_manager_id_required', $e->getErrorCode());
		}
	}

	public function testGetSecurityManagerThrowsWhenModuleNotFound(): void
	{
		$proxy = new TSecurityManagerProxy();
		$proxy->setBackingSecurityManagerId('missingModule');
		try {
			$proxy->getSecurityManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('securitymanagerproxy_security_manager_not_found', $e->getErrorCode());
		}
	}

	public function testGetSecurityManagerThrowsWhenModuleIsNotTSecurityManager(): void
	{
		$notSm = new TSecurityManagerProxyNotASecurityManagerModule();
		$this->app->setModule('notSm', $notSm);

		$proxy = new TSecurityManagerProxy();
		$proxy->setBackingSecurityManagerId('notSm');
		try {
			$proxy->getSecurityManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('securitymanagerproxy_invalid_security_manager_type', $e->getErrorCode());
		} finally {
			$proxy->unlisten();
			$notSm->unlisten();
		}
	}

	// ── Delegation: encrypt / decrypt / hash / validate ─────────────────────────

	public function testEncryptDecryptRoundTripThroughProxy(): void
	{
		$this->proxy->init(null);
		$cipher = $this->proxy->encrypt('secret payload');
		$this->assertNotSame('secret payload', $cipher);
		$this->assertSame('secret payload', $this->proxy->decrypt($cipher));
	}

	public function testEncryptedByProxyDecryptsOnBacking(): void
	{
		$this->proxy->init(null);
		$cipher = $this->proxy->encrypt('shared');
		$this->assertSame('shared', $this->backend->decrypt($cipher));
		$this->assertSame('shared', $this->proxy->decrypt($this->backend->encrypt('shared')));
	}

	public function testEncryptWithHmacRoundTrip(): void
	{
		$this->proxy->init(null);
		$this->proxy->setUseEncryptionHmac(true);
		$this->assertTrue($this->backend->getUseEncryptionHmac());
		$cipher = $this->proxy->encrypt('authenticated');
		$this->assertSame('authenticated', $this->proxy->decrypt($cipher));
	}

	public function testHashDataAndValidateDataDelegate(): void
	{
		$this->proxy->init(null);
		$hashed = $this->proxy->hashData('payload');
		$this->assertSame($this->backend->hashData('payload'), $hashed);
		$this->assertSame('payload', $this->proxy->validateData($hashed));
		$this->assertSame('payload', $this->backend->validateData($hashed));
		$this->assertFalse($this->proxy->validateData('x' . substr($hashed, 1)));
	}

	public function testClosureEncryptDecryptDelegate(): void
	{
		$this->proxy->init(null);
		$cipher = $this->proxy->encryptClosure('closure body');
		$this->assertSame('closure body', $this->proxy->decryptClosure($cipher));
		$this->assertSame('closure body', $this->backend->decryptClosure($cipher));
	}

	public function testSupportedAlgorithmsDelegate(): void
	{
		$this->assertSame($this->backend->supportedHashAlgorithms(), $this->proxy->supportedHashAlgorithms());
		$this->assertSame($this->backend->supportedCipherAlgorithms(), $this->proxy->supportedCipherAlgorithms());
	}

	public function testCSPNonceDelegates(): void
	{
		$this->assertSame($this->backend->getCSPNonce(), $this->proxy->getCSPNonce());
	}

	// ── Delegation: key and algorithm properties ────────────────────────────────

	public function testValidationKeyForwardsToBacking(): void
	{
		$this->assertSame('backend-validation-key', $this->proxy->getValidationKey());
		$this->proxy->setValidationKey('new-validation-key');
		$this->assertSame('new-validation-key', $this->backend->getValidationKey());
		$this->assertSame('new-validation-key', $this->proxy->ValidationKey);
	}

	public function testEncryptionKeyForwardsToBacking(): void
	{
		$this->assertSame('backend-encryption-key', $this->proxy->getEncryptionKey());
		$this->proxy->EncryptionKey = 'new-encryption-key';
		$this->assertSame('new-encryption-key', $this->backend->getEncryptionKey());
	}

	public function testEmptyKeyRejectedByBacking(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->proxy->setValidationKey('');
	}

	public function testHashAlgorithmForwardsToBacking(): void
	{
		$this->assertSame('sha256', $this->proxy->getHashAlgorithm());
		$this->proxy->setHashAlgorithm('sha1');
		$this->assertSame('sha1', $this->backend->getHashAlgorithm());
		$this->assertSame('sha1', $this->proxy->getHashAlgorithm());
	}

	public function testEncryptionKeyAlgorithmForwardsToBacking(): void
	{
		$this->assertSame('md5', $this->proxy->getEncryptionKeyAlgorithm());
		$this->proxy->setEncryptionKeyAlgorithm('sha256');
		$this->assertSame('sha256', $this->backend->getEncryptionKeyAlgorithm());
		$this->assertSame('sha256', $this->proxy->getEncryptionKeyAlgorithm());
	}

	public function testUseEncryptionHmacForwardsToBacking(): void
	{
		$this->assertFalse($this->proxy->getUseEncryptionHmac());
		$this->proxy->setUseEncryptionHmac('true');
		$this->assertTrue($this->backend->getUseEncryptionHmac());
		$this->assertTrue($this->proxy->getUseEncryptionHmac());
	}

	public function testCryptAlgorithmForwardsToBacking(): void
	{
		$this->assertSame('aes-256-cbc', $this->proxy->getCryptAlgorithm());
		$this->proxy->setCryptAlgorithm('aes-128-cbc');
		$this->assertSame('aes-128-cbc', $this->backend->getCryptAlgorithm());
		$this->assertSame('aes-128-cbc', $this->proxy->getCryptAlgorithm());
	}

	public function testClosureSecretKeyForwardsToBacking(): void
	{
		$this->assertSame('backend-validation-key', $this->proxy->getClosureSecretKey());
		$this->proxy->setClosureSecretKey('closure-secret');
		$this->assertSame('closure-secret', $this->backend->getClosureSecretKey());
		$this->assertSame('closure-secret', $this->proxy->getClosureSecretKey());
	}

	public function testClosureUnencryptedForwardsToBacking(): void
	{
		$this->assertNull($this->proxy->getClosureUnencrypted());
		$this->proxy->setClosureUnencrypted(false);
		$this->assertFalse($this->backend->getClosureUnencrypted());
		$this->assertFalse($this->proxy->getClosureUnencrypted());
		$this->assertTrue($this->proxy->getShouldEncryptClosure());
		$this->proxy->setClosureUnencrypted('Auto');
		$this->assertNull($this->proxy->getClosureUnencrypted());
		$this->assertSame($this->backend->getShouldEncryptClosure(), $this->proxy->getShouldEncryptClosure());
	}

	// ── __call / __get / __set passthrough ──────────────────────────────────────

	public function testCallForwardsPublicMethodToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertSame('custom:hello', $this->proxy->customMethod('hello'));
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	public function testCustomPropertyForwardsToBacking(): void
	{
		$this->proxy->init(null);
		$this->assertFalse(isset($this->proxy->CustomProp));
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backend->getCustomProp());
		$this->assertTrue(isset($this->proxy->CustomProp));
		$this->assertSame('world', $this->proxy->CustomProp);
		unset($this->proxy->CustomProp);
		$this->assertNull($this->backend->getCustomProp());
	}

	public function testSetProxyReadOnlyPropertyThrows(): void
	{
		$this->proxy->init(null);
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->SecurityManager = 'anything';
	}

	// ── isa() transparency ──────────────────────────────────────────────────────

	public function testIsaReturnsTrueForProxyOwnClass(): void
	{
		$this->assertTrue($this->proxy->isa(TSecurityManagerProxy::class));
		$this->assertTrue($this->proxy->isa(TSecurityManager::class));
		$this->assertTrue($this->proxy->isa(IProxy::class));
	}

	public function testIsaLazilyResolvesAndSeesBackingClass(): void
	{
		$this->assertNull($this->proxy->pubGetSecurityManagerDirect());
		$this->assertTrue($this->proxy->isa(TSecurityManagerProxyBackend::class));
		$this->assertNotNull($this->proxy->pubGetSecurityManagerDirect());
	}

	public function testIsaReturnsFalseForUnrelatedClassAndWithoutBacking(): void
	{
		$this->assertFalse($this->proxy->isa(TSecurityManagerProxyNotASecurityManagerModule::class));
		$proxy = new TSecurityManagerProxyAccessor();
		$this->assertFalse($proxy->isa(TSecurityManagerProxyBackend::class));
	}

	// ── __clone ──────────────────────────────────────────────────────────────────

	public function testCloneClearsBackingAndPreservesId(): void
	{
		$this->proxy->init(null);
		$this->proxy->getSecurityManager();

		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetSecurityManagerDirect());
		$this->assertSame('backingSm', $clone->getBackingSecurityManagerId());
		$this->assertSame($this->backend, $clone->getSecurityManager());
		$clone->unlisten();
	}

	public function testCloneIsIndependentOfOriginal(): void
	{
		$this->proxy->init(null);
		$clone = clone $this->proxy;

		$second = new TSecurityManagerProxyBackend();
		$second->setValidationKey('second-key');
		$second->init(null);
		$this->app->setModule('secondSm', $second);

		$clone->setBackingSecurityManagerId('secondSm');

		$this->assertSame($this->backend, $this->proxy->getSecurityManager());
		$this->assertSame($second, $clone->getSecurityManager());
		$clone->unlisten();
		$second->unlisten();
	}

	// ── _getZappableSleepProps ───────────────────────────────────────────────────

	public function testZappableAlwaysExcludesBackingAndForwarderList(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TSecurityManagerProxy::class . "\0_proxyBacking", $exprops);
		$this->assertContains("\0" . TSecurityManagerProxy::class . "\0_proxyEventNames", $exprops);
		$this->assertNotContains("\0" . TSecurityManagerProxy::class . "\0_backingSecurityManagerId", $exprops);
	}

	public function testZappableExcludesBackingSecurityManagerIdWhenEmpty(): void
	{
		$proxy = new TSecurityManagerProxyAccessor();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TSecurityManagerProxy::class . "\0_backingSecurityManagerId", $exprops);
	}

	public function testSerializeRoundTripReresolvesBacking(): void
	{
		$this->proxy->init(null);
		$this->proxy->getSecurityManager();

		$copy = unserialize(serialize($this->proxy));

		$this->assertInstanceOf(TSecurityManagerProxyAccessor::class, $copy);
		$this->assertNull($copy->pubGetSecurityManagerDirect());
		$this->assertSame('backingSm', $copy->getBackingSecurityManagerId());
		$this->assertSame($this->backend, $copy->getSecurityManager());
		$copy->unlisten();
	}

	// ── attachProxy / detachProxy ────────────────────────────────────────────────

	public function testBackingEventNotExposedBeforeResolution(): void
	{
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testResolutionAttachesBackingEvents(): void
	{
		$this->proxy->getSecurityManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
		$this->assertInstanceOf(TWeakCallableCollection::class, $this->proxy->OnTestEvent);
	}

	public function testHandlerAttachedViaProxyFiresOnBackingRaise(): void
	{
		$this->proxy->getSecurityManager();

		$fired = false;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->assertTrue(isset($this->proxy->OnTestEvent));
		$this->backend->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
	}

	public function testDetachProxyAndIdChangeClearEventSharing(): void
	{
		$this->proxy->getSecurityManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->detachProxy();
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->attachProxy();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$this->proxy->setBackingSecurityManagerId('someOtherSm');
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}
}
