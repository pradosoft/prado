<?php

/**
 * TCollectionItemChangeParameter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\TEventParameter;

/**
 * TCollectionItemChangeParameter class
 *
 * Event parameter for events that report a change to a single keyed element
 * within a collection (e.g. a map, global-state store, or similar key→value
 * structure). It carries the changed key, new value, previous value, and a
 * set of boolean state flags encoded as a compact integer bitmask.
 *
 * All data is stored in the inherited parameter array under the keys defined
 * by {@see KEY}, {@see VALUE}, {@see OLD_VALUE}, and {@see FLAGS}. These
 * constants may be used directly in array-access expressions:
 * ```php
 * $key   = $param[TCollectionItemChangeParameter::KEY];
 * $flags = $param[TCollectionItemChangeParameter::FLAGS];
 * ```
 *
 * ## Flag constants
 *
 * Each constant occupies one bit (`1 << n`) and can be tested individually
 * via its getter or combined freely via {@see getFlags}/{@see setFlags}.
 *
 * | Constant          | Bit      | Meaning                                                          |
 * |-------------------|----------|------------------------------------------------------------------|
 * | {@see IS_DEFAULT} | `1 << 0` | Value equals the element's default; the key was removed.        |
 * | {@see IS_NEW}     | `1 << 1` | Key did not previously exist; `oldValue` is `null`.             |
 * | {@see IS_UNSET}   | `1 << 2` | Key was explicitly removed; `value`/`isDefault`/`isNew` absent. |
 *
 * ## Old value semantics
 *
 * {@see IS_NEW} doubles as the "no previous value" sentinel:
 * - `isNew === true`  → the key was created; `oldValue` is `null` (placeholder).
 * - `isNew === false` → the key existed; `oldValue` holds the previous value
 *   (which may legitimately be `null`).
 *
 * ## Array-access keys
 *
 * All seven keys are always readable via array-access. The `offsetExists`
 * (`isset`) result signals **semantic meaningfulness** for the current
 * operation — not whether the key is physically in the underlying array.
 * The four stored keys (`key`, `value`, `oldValue`, `flags`) are always
 * present in the parameter array. The three bool views (`isDefault`,
 * `isNew`, `isUnset`) are derived on-the-fly from the `flags` bitmask
 * and always return a `bool`, even when `offsetExists` is `false`.
 *
 * | Key         | `offsetExists`  | Description                                                        |
 * |-------------|-----------------|--------------------------------------------------------------------|
 * | `key`       | always          | The collection key that was modified.                              |
 * | `value`     | `!isUnset`      | The new value; stored as `null` when `isUnset` is set.            |
 * | `isDefault` | `!isUnset`      | Derived from `flags`; `false` by default, `true` when cleared.    |
 * | `isNew`     | `!isUnset`      | Derived from `flags`; `false` by default, `true` for a new key.   |
 * | `isUnset`   | `isUnset`       | Derived from `flags`; `false` by default, `true` when removed.    |
 * | `oldValue`  | `!isNew`        | The previous value; stored as `null` when `isNew` is set.         |
 * | `flags`     | always          | The raw bitmask integer; `0` when no flags are set.               |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TCollectionItemChangeParameter extends TEventParameter
{
	// =========================================================================
	// Flag bit constants
	// =========================================================================

	/**
	 * Flag: value equals the element's default; the key was removed from the collection.
	 */
	public const IS_DEFAULT = 1 << 0;

	/**
	 * Flag: the key did not previously exist; {@see getOldValue()} returns `null`.
	 */
	public const IS_NEW = 1 << 1;

	/**
	 * Flag: the key was explicitly removed via a clear/unset operation.
	 * When set, `value`, `isDefault`, and `isNew` are not meaningful.
	 */
	public const IS_UNSET = 1 << 2;

	// =========================================================================
	// Parameter array key constants
	// =========================================================================

	/** Parameter array key for the collection key that changed. */
	public const KEY = 'key';

	/** Parameter array key for the new value. */
	public const VALUE = 'value';

	/** Parameter array key for the previous value. */
	public const OLD_VALUE = 'oldValue';

	/** Parameter array key for the flags bitmask integer. */
	public const FLAGS = 'flags';

	/**
	 * Constructor.
	 * @param string $key the collection key that changed.
	 * @param mixed $value the new value (`null` when {@see IS_UNSET} is set).
	 * @param mixed $oldValue the previous value, or `null` when {@see IS_NEW} is set.
	 * @param int $flags bitmask of {@see IS_DEFAULT}, {@see IS_NEW}, and/or {@see IS_UNSET}.
	 * @param bool $readOnly whether to make the parameter immutable immediately.
	 */
	public function __construct(
		string $key = '',
		mixed $value = null,
		mixed $oldValue = null,
		int $flags = 0,
		bool $readOnly = false
	) {
		parent::__construct([
			self::KEY => $key,
			self::VALUE => $value,
			self::OLD_VALUE => $oldValue,
			self::FLAGS => $flags,
		], $readOnly);
	}

	// =========================================================================
	// Key
	// =========================================================================

	/**
	 * Returns the collection key that was modified.
	 * @return string
	 */
	public function getKey(): string
	{
		return (string) parent::offsetGet(self::KEY);
	}

	/**
	 * Sets the collection key.
	 * @param string $key
	 * @throws \Prado\Exceptions\TInvalidOperationException if the parameter is read-only.
	 */
	public function setKey(string $key): void
	{
		parent::offsetSet(self::KEY, $key);
	}

	// =========================================================================
	// Value
	// =========================================================================

	/**
	 * Returns the new value, or `null` when {@see IS_UNSET} is set.
	 * @return mixed
	 */
	public function getValue(): mixed
	{
		return parent::offsetGet(self::VALUE);
	}

	/**
	 * Sets the new value.
	 * @param mixed $value
	 * @throws \Prado\Exceptions\TInvalidOperationException if the parameter is read-only.
	 */
	public function setValue(mixed $value): void
	{
		parent::offsetSet(self::VALUE, $value);
	}

	// =========================================================================
	// OldValue
	// =========================================================================

	/**
	 * Returns the previous value, or `null` when {@see IS_NEW} is set.
	 * A `null` return when `isNew` is `false` means the old value was itself `null`.
	 * @return mixed
	 */
	public function getOldValue(): mixed
	{
		return parent::offsetGet(self::OLD_VALUE);
	}

	/**
	 * Sets the old value.
	 * @param mixed $oldValue
	 * @throws \Prado\Exceptions\TInvalidOperationException if the parameter is read-only.
	 */
	public function setOldValue(mixed $oldValue): void
	{
		parent::offsetSet(self::OLD_VALUE, $oldValue);
	}

	// =========================================================================
	// Flags bitmask
	// =========================================================================

	/**
	 * Returns the flags bitmask (combination of {@see IS_DEFAULT}, {@see IS_NEW},
	 * and/or {@see IS_UNSET}) stored under the {@see FLAGS} parameter key.
	 * @return int
	 */
	public function getFlags(): int
	{
		return (int) parent::offsetGet(self::FLAGS);
	}

	/**
	 * Replaces the entire flags bitmask. Use the individual flag setters
	 * ({@see setIsDefault}, {@see setIsNew}, {@see setIsUnset}) to toggle a
	 * single bit without disturbing the others.
	 * @param int $flags new bitmask value.
	 * @throws \Prado\Exceptions\TInvalidOperationException if the parameter is read-only.
	 */
	public function setFlags(int $flags): void
	{
		parent::offsetSet(self::FLAGS, $flags);
	}

	// =========================================================================
	// Individual flag getters / setters
	// =========================================================================

	/**
	 * Returns whether the value was set to its default, causing the key to be removed.
	 * Derived from the {@see IS_DEFAULT} bit of the {@see FLAGS} parameter entry.
	 * @return bool
	 */
	public function getIsDefault(): bool
	{
		return (bool) ($this->getFlags() & self::IS_DEFAULT);
	}

	/**
	 * Sets or clears the {@see IS_DEFAULT} bit without affecting {@see IS_NEW} or
	 * {@see IS_UNSET}.
	 * @param bool $value `true` to set the bit, `false` to clear it.
	 * @throws \Prado\Exceptions\TInvalidOperationException if the parameter is read-only.
	 */
	public function setIsDefault(bool $value): void
	{
		$flags = $this->getFlags();
		parent::offsetSet(self::FLAGS, $value ? ($flags | self::IS_DEFAULT) : ($flags & ~self::IS_DEFAULT));
	}

	/**
	 * Returns whether the key was new (did not exist before this change).
	 * When `true`, {@see getOldValue()} returns `null`.
	 * Derived from the {@see IS_NEW} bit of the {@see FLAGS} parameter entry.
	 * @return bool
	 */
	public function getIsNew(): bool
	{
		return (bool) ($this->getFlags() & self::IS_NEW);
	}

	/**
	 * Sets or clears the {@see IS_NEW} bit without affecting {@see IS_DEFAULT} or
	 * {@see IS_UNSET}.
	 * @param bool $value `true` to set the bit, `false` to clear it.
	 * @throws \Prado\Exceptions\TInvalidOperationException if the parameter is read-only.
	 */
	public function setIsNew(bool $value): void
	{
		$flags = $this->getFlags();
		parent::offsetSet(self::FLAGS, $value ? ($flags | self::IS_NEW) : ($flags & ~self::IS_NEW));
	}

	/**
	 * Returns whether the key was explicitly removed from the collection.
	 * When `true`, `value`, `isDefault`, and `isNew` are not meaningful.
	 * Derived from the {@see IS_UNSET} bit of the {@see FLAGS} parameter entry.
	 * @return bool
	 */
	public function getIsUnset(): bool
	{
		return (bool) ($this->getFlags() & self::IS_UNSET);
	}

	/**
	 * Sets or clears the {@see IS_UNSET} bit without affecting {@see IS_DEFAULT} or
	 * {@see IS_NEW}.
	 * @param bool $value `true` to set the bit, `false` to clear it.
	 * @throws \Prado\Exceptions\TInvalidOperationException if the parameter is read-only.
	 */
	public function setIsUnset(bool $value): void
	{
		$flags = $this->getFlags();
		parent::offsetSet(self::FLAGS, $value ? ($flags | self::IS_UNSET) : ($flags & ~self::IS_UNSET));
	}

	// =========================================================================
	// ArrayAccess overrides
	// =========================================================================

	/**
	 * Semantic `isset` for the seven proxied offsets — returns `true` when the
	 * offset is **meaningfully present** for the current operation, not merely stored:
	 *
	 * | Offset        | Constant    | Returns `true` when | Notes                                                    |
	 * |---------------|-------------|---------------------|----------------------------------------------------------|
	 * | `'key'`       | `KEY`       | always              | Explicitly handled.                                      |
	 * | `'value'`     | `VALUE`     | `!isUnset`          | Not meaningful when the key was removed.                 |
	 * | `'oldValue'`  | `OLD_VALUE` | `!isNew`            | Not meaningful when the key did not previously exist.    |
	 * | `'flags'`     | `FLAGS`     | always              | Falls through to parent; stored as a non-null integer.   |
	 * | `'isDefault'` | —           | `!isUnset`          | Not meaningful when the key was removed.                 |
	 * | `'isNew'`     | —           | `!isUnset`          | Not meaningful when the key was removed.                 |
	 * | `'isUnset'`   | —           | `isUnset`           | Only meaningful when the key was removed.                |
	 * | other         | —           | parent result       | Falls through to {@see \Prado\TEventParameter::offsetExists()}. |
	 *
	 * {@inheritdoc}
	 */
	public function offsetExists($offset): bool
	{
		if ($offset === self::KEY) {
			return true;
		}
		if ($offset === self::VALUE || $offset === 'isDefault' || $offset === 'isNew') {
			return !$this->getIsUnset();
		}
		if ($offset === 'isUnset') {
			return $this->getIsUnset();
		}
		if ($offset === self::OLD_VALUE) {
			return !$this->getIsNew();
		}
		return parent::offsetExists($offset);
	}

	/**
	 * Array-access getter — proxies the seven named offsets through their typed getters;
	 * all other offsets fall through to {@see \Prado\TEventParameter::offsetGet()}:
	 *
	 * | Offset        | Constant    | Getter                |
	 * |---------------|-------------|-----------------------|
	 * | `'key'`       | `KEY`       | {@see getKey()}       |
	 * | `'value'`     | `VALUE`     | {@see getValue()}     |
	 * | `'oldValue'`  | `OLD_VALUE` | {@see getOldValue()}  |
	 * | `'flags'`     | `FLAGS`     | {@see getFlags()}     |
	 * | `'isDefault'` | —           | {@see getIsDefault()} |
	 * | `'isNew'`     | —           | {@see getIsNew()}     |
	 * | `'isUnset'`   | —           | {@see getIsUnset()}   |
	 *
	 * {@inheritdoc}
	 */
	public function offsetGet($offset): mixed
	{
		if ($offset === self::KEY) {
			return $this->getKey();
		}
		if ($offset === self::VALUE) {
			return $this->getValue();
		}
		if ($offset === self::OLD_VALUE) {
			return $this->getOldValue();
		}
		if ($offset === self::FLAGS) {
			return $this->getFlags();
		}
		if ($offset === 'isDefault') {
			return $this->getIsDefault();
		}
		if ($offset === 'isNew') {
			return $this->getIsNew();
		}
		if ($offset === 'isUnset') {
			return $this->getIsUnset();
		}
		return parent::offsetGet($offset);
	}

	/**
	 * Array-access setter — proxies named offsets through typed setters with coercion;
	 * all other offsets fall through to {@see \Prado\TEventParameter::offsetSet()}.
	 * Throws {@see \Prado\Exceptions\TInvalidOperationException} when read-only,
	 * enforced by the typed setter chain:
	 *
	 * | Offset        | Constant    | Setter                | Coercion   |
	 * |---------------|-------------|-----------------------|------------|
	 * | `'key'`       | `KEY`       | {@see setKey()}       | `(string)` |
	 * | `'value'`     | `VALUE`     | {@see setValue()}     | none       |
	 * | `'oldValue'`  | `OLD_VALUE` | {@see setOldValue()}  | none       |
	 * | `'flags'`     | `FLAGS`     | {@see setFlags()}     | `(int)`    |
	 * | `'isDefault'` | —           | {@see setIsDefault()} | `(bool)`   |
	 * | `'isNew'`     | —           | {@see setIsNew()}     | `(bool)`   |
	 * | `'isUnset'`   | —           | {@see setIsUnset()}   | `(bool)`   |
	 *
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $item): void
	{
		if ($offset === self::KEY) {
			$this->setKey((string) $item);
		} elseif ($offset === self::VALUE) {
			$this->setValue($item);
		} elseif ($offset === self::OLD_VALUE) {
			$this->setOldValue($item);
		} elseif ($offset === self::FLAGS) {
			$this->setFlags((int) $item);
		} elseif ($offset === 'isDefault') {
			$this->setIsDefault((bool) $item);
		} elseif ($offset === 'isNew') {
			$this->setIsNew((bool) $item);
		} elseif ($offset === 'isUnset') {
			$this->setIsUnset((bool) $item);
		} else {
			parent::offsetSet($offset, $item);
		}
	}

	/**
	 * Array-access unsetter — resets named offsets to their zero/null values;
	 * all other offsets fall through to {@see \Prado\TEventParameter::offsetUnset()}.
	 * Throws {@see \Prado\Exceptions\TInvalidOperationException} when read-only,
	 * enforced by the typed setter chain:
	 *
	 * | Offset        | Constant    | Resets to | Notes                              |
	 * |---------------|-------------|-----------|------------------------------------|
	 * | `'key'`       | `KEY`       | `''`      | Empty string.                      |
	 * | `'value'`     | `VALUE`     | `null`    |                                    |
	 * | `'oldValue'`  | `OLD_VALUE` | `null`    |                                    |
	 * | `'flags'`     | `FLAGS`     | `0`       | All flag bits cleared.             |
	 * | `'isDefault'` | —           | `false`   | Clears {@see IS_DEFAULT} bit only. |
	 * | `'isNew'`     | —           | `false`   | Clears {@see IS_NEW} bit only.     |
	 * | `'isUnset'`   | —           | `false`   | Clears {@see IS_UNSET} bit only.   |
	 *
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset): void
	{
		if ($offset === self::KEY) {
			$this->setKey('');
		} elseif ($offset === self::VALUE) {
			$this->setValue(null);
		} elseif ($offset === self::OLD_VALUE) {
			$this->setOldValue(null);
		} elseif ($offset === self::FLAGS) {
			$this->setFlags(0);
		} elseif ($offset === 'isDefault') {
			$this->setIsDefault(false);
		} elseif ($offset === 'isNew') {
			$this->setIsNew(false);
		} elseif ($offset === 'isUnset') {
			$this->setIsUnset(false);
		} else {
			parent::offsetUnset($offset);
		}
	}
}
