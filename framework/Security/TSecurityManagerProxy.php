<?php

/**
 * TSecurityManagerProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\Util\Log\TLogger;

/**
 * TSecurityManagerProxy class.
 *
 * TSecurityManagerProxy is a transparent security manager module that delegates
 * every {@see TSecurityManager} operation to another TSecurityManager module
 * registered with the application. The application security manager slot can
 * be swapped at configuration time without changing the consumers that depend
 * on it.
 *
 * ## Configuration
 *
 * {@see getBackingSecurityManagerId BackingSecurityManagerId} names the backing
 * security manager module. TSecurityManagerProxy declares that module as a
 * required {@see IModuleDependency}, so the application initializes it first.
 * {@see init()} registers the proxy as the application security manager; the
 * backing keeps its own registration until the proxy replaces it.
 *
 * ## Transparency
 *
 * The keys, algorithms, and closure settings live on the backing. Every key and
 * algorithm property, {@see encrypt()}, {@see decrypt()}, {@see hashData()},
 * {@see validateData()}, {@see encryptClosure()}, {@see decryptClosure()},
 * {@see getShouldEncryptClosure()}, {@see supportedHashAlgorithms()},
 * {@see supportedCipherAlgorithms()}, and {@see getCSPNonce()} call the backing's
 * public methods, so the proxy generates no keys of its own. Other property
 * reads and writes, method calls, and events reach the backing through
 * {@see TComponentProxyTrait}, which wires the backing's public `on` events on
 * first resolution.
 *
 * Replacing a {@see setBackingSecurityManagerId BackingSecurityManagerId} logs
 * a {@see TLogger::WARNING} so a runtime swap is visible in the application log.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="security" class="Prado\Security\TSecurityManagerProxy" BackingSecurityManagerId="realSecurity" />
 * <module id="realSecurity" class="Prado\Security\TSecurityManager" ValidationKey="..." EncryptionKey="..." />
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TSecurityManagerProxy();
 * $proxy->setBackingSecurityManagerId('realSecurity');
 * $proxy->init(null);
 * // All operations now delegate to the 'realSecurity' module.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSecurityManagerProxy extends TSecurityManager implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing security manager; empty until configured */
	private string $_backingSecurityManagerId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing security manager module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing security manager module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingSecurityManagerId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy security manager module. {@see TSecurityManager::init()}
	 * registers the module as the application security manager; the proxy takes that
	 * slot. The serializable closure setup of the parent is skipped through
	 * {@see setupSerializableClosure()}, so the proxy resolves no keys during init.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingSecurityManagerId BackingSecurityManagerId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingSecurityManagerId() === '') {
			throw new TConfigurationException('securitymanagerproxy_backing_security_manager_id_required');
		}
		parent::init($config);
	}

	/**
	 * Does nothing. The backing security manager configures
	 * {@see \Prado\Util\TSerializableClosure} during its own {@see TSecurityManager::init()},
	 * which runs first, and the proxy holds no keys of its own.
	 */
	protected function setupSerializableClosure()
	{
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing security manager through {@see getSecurityManager()}.
	 * @throws TConfigurationException when {@see getBackingSecurityManagerId BackingSecurityManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TSecurityManager}
	 * @return ?TComponent the backing security manager
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getSecurityManager();
	}

	/**
	 * Returns whether a {@see getBackingSecurityManagerId BackingSecurityManagerId}
	 * is configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingSecurityManagerId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing security manager
	 */
	protected function getBackingSecurityManagerIdDirect(): string
	{
		return $this->_backingSecurityManagerId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingSecurityManagerIdDirect(string $value): void
	{
		$this->_backingSecurityManagerId = $value;
	}

	/**
	 * @return string the module ID of the backing security manager
	 */
	public function getBackingSecurityManagerId(): string
	{
		return $this->getBackingSecurityManagerIdDirect();
	}

	/**
	 * Sets the module ID of the backing security manager. Replacing a non-empty ID
	 * detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops the
	 * resolved security manager so the next operation resolves the new module.
	 * @param string $value the module ID of the security manager to proxy
	 */
	public function setBackingSecurityManagerId(string $value): void
	{
		$current = $this->getBackingSecurityManagerIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TSecurityManagerProxy.BackingSecurityManagerId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.security'
			);
		}
		$this->setBackingSecurityManagerIdDirect($value);
		$this->setSecurityManagerDirect(null);
	}

	/**
	 * Returns the stored backing security manager, narrowing the trait's
	 * `?TComponent` to `?TSecurityManager`.
	 * @return ?TSecurityManager the backing security manager, or null when not yet resolved
	 */
	protected function getSecurityManagerDirect(): ?TSecurityManager
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TSecurityManager ? $backing : null;
	}

	/**
	 * @param ?TSecurityManager $securityManager the backing security manager to store
	 */
	protected function setSecurityManagerDirect(?TSecurityManager $securityManager): void
	{
		$this->setProxyBackingDirect($securityManager);
	}

	/**
	 * Returns the backing {@see TSecurityManager}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingSecurityManagerId BackingSecurityManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TSecurityManager}
	 * @return TSecurityManager the backing security manager module
	 */
	public function getSecurityManager(): TSecurityManager
	{
		$securityManager = $this->getSecurityManagerDirect();
		if ($securityManager === null) {
			$id = $this->getBackingSecurityManagerId();
			if ($id === '') {
				throw new TConfigurationException('securitymanagerproxy_backing_security_manager_id_required');
			}
			$securityManager = $this->getApplication()->getModule($id);
			if ($securityManager === null) {
				throw new TConfigurationException('securitymanagerproxy_security_manager_not_found', $id);
			}
			if (!($securityManager instanceof TSecurityManager)) {
				throw new TConfigurationException('securitymanagerproxy_invalid_security_manager_type', $id);
			}
			$this->setSecurityManagerDirect($securityManager);
			$this->attachProxy();
		}
		return $securityManager;
	}

	// ----------------------------------------------------------------- key and algorithm properties

	/**
	 * @return string the backing's private key used to generate HMAC
	 */
	public function getValidationKey()
	{
		return $this->getSecurityManager()->getValidationKey();
	}

	/**
	 * Sets the backing's key used to generate HMAC.
	 * @param string $value the key used to generate HMAC
	 */
	public function setValidationKey($value)
	{
		$this->getSecurityManager()->setValidationKey($value);
	}

	/**
	 * @return string the backing's private key used to encrypt and decrypt data
	 */
	public function getEncryptionKey()
	{
		return $this->getSecurityManager()->getEncryptionKey();
	}

	/**
	 * Sets the backing's key used to encrypt and decrypt data.
	 * @param string $value the key used to encrypt and decrypt data
	 */
	public function setEncryptionKey($value)
	{
		$this->getSecurityManager()->setEncryptionKey($value);
	}

	/**
	 * @return string the backing's hashing algorithm used to generate HMAC
	 */
	public function getHashAlgorithm()
	{
		return $this->getSecurityManager()->getHashAlgorithm();
	}

	/**
	 * Sets the backing's hashing algorithm used to generate HMAC.
	 * @param string $value hashing algorithm used to generate HMAC
	 */
	public function setHashAlgorithm($value)
	{
		$this->getSecurityManager()->setHashAlgorithm($value);
	}

	/**
	 * @return string the backing's hashing algorithm used to hash the encryption key
	 */
	public function getEncryptionKeyAlgorithm()
	{
		return $this->getSecurityManager()->getEncryptionKeyAlgorithm();
	}

	/**
	 * Sets the backing's hashing algorithm used to hash the encryption key.
	 * @param string $value hashing algorithm used to hash the encryption key
	 */
	public function setEncryptionKeyAlgorithm($value)
	{
		$this->getSecurityManager()->setEncryptionKeyAlgorithm($value);
	}

	/**
	 * @return bool whether the backing's {@see encrypt()} prepends an HMAC
	 */
	public function getUseEncryptionHmac(): bool
	{
		return $this->getSecurityManager()->getUseEncryptionHmac();
	}

	/**
	 * Sets whether the backing's {@see encrypt()} prepends an HMAC.
	 * @param bool $value `true` to enable authenticated encryption
	 */
	public function setUseEncryptionHmac($value): void
	{
		$this->getSecurityManager()->setUseEncryptionHmac($value);
	}

	/**
	 * @return string the backing's algorithm used to encrypt and decrypt data
	 */
	public function getCryptAlgorithm()
	{
		return $this->getSecurityManager()->getCryptAlgorithm();
	}

	/**
	 * Sets the backing's cipher used for {@see encrypt()} and {@see decrypt()}.
	 * @param string $value the cipher name
	 */
	public function setCryptAlgorithm($value)
	{
		$this->getSecurityManager()->setCryptAlgorithm($value);
	}

	/**
	 * @return string the backing's serializable closure key
	 */
	public function getClosureSecretKey(): string
	{
		return $this->getSecurityManager()->getClosureSecretKey();
	}

	/**
	 * Sets the backing's serializable closure key.
	 * @param ?string $value the serializable closure key; null or empty restores the default
	 */
	public function setClosureSecretKey($value)
	{
		$this->getSecurityManager()->setClosureSecretKey($value);
	}

	/**
	 * @return ?bool the backing's ClosureUnencrypted setting: true, false, or null for Auto
	 */
	public function getClosureUnencrypted(): ?bool
	{
		return $this->getSecurityManager()->getClosureUnencrypted();
	}

	/**
	 * Sets the backing's ClosureUnencrypted setting.
	 * @param null|bool|string $value true, false, or null for the mode-aware default
	 */
	public function setClosureUnencrypted($value)
	{
		$this->getSecurityManager()->setClosureUnencrypted($value);
	}

	/**
	 * @return bool whether the backing encrypts serialized closures
	 */
	public function getShouldEncryptClosure(): bool
	{
		return $this->getSecurityManager()->getShouldEncryptClosure();
	}

	// ----------------------------------------------------------------- operations

	/**
	 * Encrypts a serialized closure payload with the backing security manager.
	 * @param string $data the serialized closure payload
	 * @return string the encrypted payload
	 */
	public function encryptClosure($data)
	{
		return $this->getSecurityManager()->encryptClosure($data);
	}

	/**
	 * Decrypts an encrypted closure payload with the backing security manager.
	 * @param string $data the encrypted payload
	 * @return false|string the serialized closure payload, or false on failure
	 */
	public function decryptClosure($data)
	{
		return $this->getSecurityManager()->decryptClosure($data);
	}

	/**
	 * Encrypts data with the backing security manager.
	 * @param string $data data to be encrypted
	 * @return string the encrypted data
	 */
	public function encrypt($data)
	{
		return $this->getSecurityManager()->encrypt($data);
	}

	/**
	 * Decrypts data with the backing security manager.
	 * @param string $data data to be decrypted
	 * @return false|string the decrypted data, or false on failure
	 */
	public function decrypt($data)
	{
		return $this->getSecurityManager()->decrypt($data);
	}

	/**
	 * Prefixes data with an HMAC computed by the backing security manager.
	 * @param string $data data to be hashed
	 * @return string data prefixed with HMAC
	 */
	public function hashData($data)
	{
		return $this->getSecurityManager()->hashData($data);
	}

	/**
	 * Validates HMAC-prefixed data with the backing security manager.
	 * @param string $data data previously generated by {@see hashData()}
	 * @return false|string the real data with HMAC stripped off, or false when tampered
	 */
	public function validateData($data)
	{
		return $this->getSecurityManager()->validateData($data);
	}

	/**
	 * @return array the hash algorithms supported by the backing security manager
	 */
	public function supportedHashAlgorithms()
	{
		return $this->getSecurityManager()->supportedHashAlgorithms();
	}

	/**
	 * @return array the cipher algorithms supported by the backing security manager
	 */
	public function supportedCipherAlgorithms()
	{
		return $this->getSecurityManager()->supportedCipherAlgorithms();
	}

	/**
	 * @return string the per-request Content-Security-Policy nonce of the backing security manager
	 */
	public function getCSPNonce()
	{
		return $this->getSecurityManager()->getCSPNonce();
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing security manager
	 * (re-resolved from the module registry after unserialization), and an empty
	 * backing ID from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingSecurityManagerIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingSecurityManagerId";
		}
	}
}
