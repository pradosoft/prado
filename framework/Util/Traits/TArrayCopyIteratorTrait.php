<?php

/**
 * TArrayCopyIteratorTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

/**
 * TArrayCopyIteratorTrait trait.
 *
 * TArrayCopyIteratorTrait composes {@see TArrayIteratorTrait} and adds a lazy-loading,
 * copy-and-cache backing store.  Subclasses implement {@see getIteratorArrayCopy()} to
 * supply the initial snapshot; the result is cached and reused for all subsequent
 * iterations (see {@see TConstantReflectionTrait} for a ready-made implementation that
 * reflects a class's own constants).
 *
 * The using class must declare `implements \Iterator`; {@see TArrayIteratorTrait} is
 * composed automatically and need not be listed separately.
 *
 * ```php
 * class MyList implements \Iterator
 * {
 *     use \Prado\Util\Traits\TArrayCopyIteratorTrait;
 *
 *     protected function getIteratorArrayCopy(): array
 *     {
 *         return ['a' => 1, 'b' => 2];
 *     }
 * }
 * ```
 *
 * {@see \Prado\TEnumerable} is the canonical base class demonstrating this pattern.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TArrayIteratorTrait
 * @see TConstantReflectionTrait
 * @see \Prado\IEnumerable
 * @since 4.4.0
 */
trait TArrayCopyIteratorTrait
{
	use TArrayIteratorTrait;

	/** @var ?array Backing store for iteration; null until first load. */
	private ?array $_iterator_array = null;

	/**
	 * Returns the snapshot array that seeds the backing store on first iteration.
	 *
	 * @return array The array to copy into the backing store.
	 */
	abstract protected function getIteratorArrayCopy(): array;

	/**
	 * Populates the backing store if it has not been loaded yet.
	 */
	protected function ensureIteratorArray(): void
	{
		if ($this->getIteratorArrayDirect() === null) {
			$this->setIteratorArrayDirect($this->getIteratorArrayCopy());
		}
	}

	/**
	 * Returns a reference to the backing store without triggering lazy loading.
	 *
	 * Returns `null` when the store has not been populated yet.
	 *
	 * @return ?array Reference to `$_iterator_array`.
	 */
	protected function &getIteratorArrayDirect(): ?array
	{
		return $this->_iterator_array;
	}

	/**
	 * Replaces the backing store directly.
	 *
	 * Pass `null` to reset the store and force a reload on the next iterator access.
	 *
	 * @param ?array $array The replacement array, or `null` to reset the store.
	 */
	protected function setIteratorArrayDirect(?array $array): void
	{
		$this->_iterator_array = $array;
	}

	/**
	 * Returns a cached reference to the backing array, loading it on first call.
	 *
	 * @return array Reference to the backing array.
	 */
	protected function &getIteratorArray(): array
	{
		if ($this->_iterator_array === null) {
			$this->_iterator_array = $this->getIteratorArrayCopy();
		}
		return $this->_iterator_array;
	}

}
