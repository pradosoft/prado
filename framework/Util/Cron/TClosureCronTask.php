<?php

/**
 * TClosureCronTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Closure;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplicationMode;
use Prado\TPropertyValue;
use Prado\Util\TSerializableClosure;

/**
 * TClosureCronTask class
 *
 * TClosureCronTask runs a PHP {@see \Closure} as a cron task. The closure receives the task and the
 * calling {@see TDbCronModule} as arguments:
 * ```php
 * $task = new TClosureCronTask(function (TClosureCronTask $task, TCronModule $cron) {
 *     // ... do work ...
 * });
 * $task->setName('mytask');
 * $task->setSchedule('0 0 * * *');
 * $cron->addTask($task);
 * ```
 *
 * Because PHP cannot serialize a {@see \Closure}, the closure is stored through
 * {@see \Prado\Util\TSerializableClosure}, and that payload is then encrypted with a
 * {@see \Prado\Security\TSecurityManager}. Unserializing a closure reconstructs its code with `eval()`,
 * so an unencrypted, unsigned payload is remote code execution; the default keeps the payload encrypted.
 *
 * The {@see setStoreRaw StoreRaw} property controls how the closure payload is stored:
 *  - `false` (default) — encrypt the payload.
 *  - `true` — store raw/decrypted (signed only when application closure signing is configured).
 *  - `'Debug'` — store raw only when the application {@see \Prado\TApplication::getMode Mode} is
 *    `Debug` (readable during development), otherwise encrypt.
 *
 * The {@see setSecurityManagerID SecurityManagerID} property selects which security manager performs the
 * encryption. When it is `null` (unset) or an empty string, the application security manager is used;
 * otherwise it is the ID of a {@see \Prado\Security\TSecurityManager} module.
 *
 * The {@see getData Data} property is a serializable key-value store carried with the task — parameters or
 * state for the closure to read or mutate. It is reached through {@see getData}/{@see setData} or, because
 * the task is an `\ArrayAccess`/`\Countable`/`\IteratorAggregate` container, with array syntax on the task
 * the closure receives:
 * ```php
 * $task = new TClosureCronTask(function (TClosureCronTask $task, TCronModule $cron) {
 *     $cron->log("processing " . $task['accountId']);
 *     $task['runs'] = ($task['runs'] ?? 0) + 1;
 * });
 * $task['accountId'] = 42;
 * ```
 * The data is serialized as a plain task property (it is not encrypted with the closure), so its values
 * must be serializable and should not hold secrets.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TClosureCronTask extends TCronTask implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/** @var ?Closure the closure to run, not serialized directly */
	private $_closure;

	/** @var ?string the encoded (serialized and optionally encrypted) closure payload */
	private $_payload;

	/** @var bool whether {@see $_payload} is encrypted */
	private $_payloadEncrypted = false;

	/** @var bool|string whether to store the closure payload raw (unencrypted): false, true, or TApplicationMode::Debug */
	private $_storeRaw = false;

	/** @var ?string the ID of the TSecurityManager module, null or '' for the application security manager */
	private $_securityManagerID;

	/** @var array the serializable key-value data carried with the task */
	private array $_data = [];

	/**
	 * @param ?Closure $closure the closure to run when the task executes
	 */
	public function __construct($closure = null)
	{
		parent::__construct();
		if ($closure !== null) {
			$this->setClosure($closure);
		}
	}

	/**
	 * Runs the closure, passing this task and the calling cron module.
	 * @param TCronModule $cron the module calling the task
	 * @throws TConfigurationException when no closure is set
	 * @return mixed the result of the closure
	 */
	public function execute($cron)
	{
		$closure = $this->getClosure();
		if ($closure === null) {
			throw new TConfigurationException('closurecrontask_no_closure', $this->getName());
		}
		return $closure($this, $cron);
	}

	/**
	 * Gets the closure, decoding the stored payload on first access after unserialization.
	 * @return ?Closure the closure to run
	 */
	public function getClosure(): ?Closure
	{
		if ($this->_closure === null && $this->_payload !== null) {
			$this->_closure = $this->decodeClosure();
		}
		return $this->_closure;
	}

	/**
	 * @param Closure $closure the closure to run when the task executes
	 */
	public function setClosure($closure)
	{
		if (!($closure instanceof Closure)) {
			throw new TConfigurationException('closurecrontask_closure_required');
		}
		$this->_closure = $closure;
		$this->_payload = null;
		$this->_payloadEncrypted = false;
	}

	/**
	 * @return bool|string whether the closure payload is stored raw (unencrypted): false, true, or TApplicationMode::Debug
	 */
	public function getStoreRaw()
	{
		return $this->_storeRaw;
	}

	/**
	 * @param bool|string $value true stores the payload raw, false encrypts it; 'Debug' stores raw only
	 * when the application is in Debug mode.
	 */
	public function setStoreRaw($value)
	{
		if (is_string($value) && strcasecmp($value, TApplicationMode::Debug) === 0) {
			$this->_storeRaw = TApplicationMode::Debug;
		} else {
			$this->_storeRaw = TPropertyValue::ensureBoolean($value);
		}
	}

	/**
	 * @return ?string the ID of the TSecurityManager module, null or '' for the application security manager
	 */
	public function getSecurityManagerID()
	{
		return $this->_securityManagerID;
	}

	/**
	 * @param ?string $value the ID of the TSecurityManager module; null or '' for the application security manager
	 */
	public function setSecurityManagerID($value)
	{
		$this->_securityManagerID = ($value === null) ? null : TPropertyValue::ensureString($value);
	}

	/**
	 * Resolves the security manager used to encrypt the closure payload. When {@see getSecurityManagerID}
	 * is null or empty, the application security manager is used.
	 * @throws TConfigurationException when the configured module is not a TSecurityManager
	 * @return \Prado\Security\TSecurityManager the security manager
	 */
	public function getSecurityManager()
	{
		$id = $this->_securityManagerID;
		if ($id === null || $id === '') {
			return $this->getApplication()->getSecurityManager();
		}
		$module = $this->getApplication()->getModule($id);
		if (!($module instanceof \Prado\Security\TSecurityManager)) {
			throw new TConfigurationException('closurecrontask_invalid_securitymanager', $id);
		}
		return $module;
	}

	/**
	 * @return bool whether the closure payload should be encrypted given {@see getStoreRaw} and the
	 * application mode.
	 */
	protected function getShouldEncrypt(): bool
	{
		if ($this->_storeRaw === TApplicationMode::Debug) {
			return $this->getApplication()->getMode() !== TApplicationMode::Debug;
		}
		return !$this->_storeRaw;
	}

	/**
	 * Serializes the closure through {@see \Prado\Util\TSerializableClosure} and, when
	 * {@see getShouldEncrypt}, encrypts the payload with the {@see getSecurityManager security manager}.
	 */
	protected function encodeClosure(): void
	{
		if ($this->_closure === null) {
			return;
		}
		// Resolve the security manager BEFORE serializing: it configures the closure signer, and a
		// TSerializableClosure is always HMAC-signed. Signing before the signer is configured (the first
		// task in a cold worker) produces a payload that fails its own signature check on decode.
		$shouldEncrypt = $this->getShouldEncrypt();
		$securityManager = $this->getSecurityManager();
		$payload = serialize(new TSerializableClosure($this->_closure));
		if ($shouldEncrypt) {
			$payload = $securityManager->encrypt($payload);
			$this->_payloadEncrypted = true;
		} else {
			$this->_payloadEncrypted = false;
		}
		$this->_payload = $payload;
	}

	/**
	 * Decrypts (when needed) and unserializes the stored payload back into a closure.
	 * @throws TConfigurationException when the encrypted payload cannot be decrypted
	 * @return ?Closure the decoded closure
	 */
	protected function decodeClosure(): ?Closure
	{
		$payload = $this->_payload;
		if ($payload === null) {
			return null;
		}
		// Resolve the security manager first so the closure signer is configured before unserialize()
		// verifies the HMAC signature (needed for the raw path too, not only to decrypt).
		$securityManager = $this->getSecurityManager();
		if ($this->_payloadEncrypted) {
			$payload = $securityManager->decrypt($payload);
			if ($payload === false) {
				throw new TConfigurationException('closurecrontask_decrypt_failed', $this->getName());
			}
		}
		return unserialize($payload)->getClosure();
	}

	/**
	 * @return array the serializable key-value data carried with the task
	 */
	public function getData(): array
	{
		return $this->_data;
	}

	/**
	 * Replaces the task data with the given key-value set. A `\Traversable` is collected into an array;
	 * null clears the data.
	 * @param null|array|\Traversable $value the data to carry with the task
	 * @return $this for method chaining.
	 */
	public function setData(array|\Traversable|null $value): static
	{
		if ($value === null) {
			$this->_data = [];
		} else {
			$this->_data = is_array($value) ? $value : iterator_to_array($value);
		}
		return $this;
	}

	// =========================================================================
	// ArrayAccess, Countable, IteratorAggregate
	// =========================================================================

	/**
	 * @param mixed $offset the data key to test
	 * @return bool whether the key is present in the {@see getData data}
	 */
	public function offsetExists(mixed $offset): bool
	{
		return array_key_exists($offset, $this->_data);
	}

	/**
	 * @param mixed $offset the data key to read
	 * @return mixed the value at the key, or null when absent
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return $this->_data[$offset] ?? null;
	}

	/**
	 * Sets a data value, appending when the key is null (`$task[] = ...`). Mutates the backing store in
	 * place so a single-element write does not copy the whole array.
	 * @param mixed $offset the data key, or null to append
	 * @param mixed $value the value to store
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if ($offset === null) {
			$this->_data[] = $value;
		} else {
			$this->_data[$offset] = $value;
		}
	}

	/**
	 * @param mixed $offset the data key to remove
	 */
	public function offsetUnset(mixed $offset): void
	{
		unset($this->_data[$offset]);
	}

	/**
	 * @return int the number of data entries
	 */
	public function count(): int
	{
		return count($this->_data);
	}

	/**
	 * @return \Traversable an iterator over the {@see getData data} entries
	 */
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->_data);
	}

	/**
	 * Encodes the closure into the storable payload and excludes the non-serializable closure from
	 * serialization.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		$this->encodeClosure();
		parent::_getZappableSleepProps($exprops);

		$prop = "\0" . __CLASS__ . "\0";
		$exprops[] = $prop . "_closure";
		if ($this->_payload === null) {
			$exprops[] = $prop . "_payload";
			$exprops[] = $prop . "_payloadEncrypted";
		}
		if ($this->_storeRaw === false) {
			$exprops[] = $prop . "_storeRaw";
		}
		if ($this->_securityManagerID === null) {
			$exprops[] = $prop . "_securityManagerID";
		}
		if ($this->_data === []) {
			$exprops[] = $prop . "_data";
		}
	}
}
