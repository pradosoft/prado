<?php

/**
 * TTestResource class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\IO\TResource;

/**
 * TTestResource is an instantiable {@see \Prado\IO\TResource} for unit tests.
 *
 * {@see TResource} is abstract (its only abstract-by-intent member is the protected
 * close primitive {@see \Prado\IO\TResource::closeResource()}), so tests need a concrete
 * subclass to exercise the base.  This harness adds two instrumentation seams without
 * changing behavior:
 *
 * - {@see $closeResourceCalls} counts how many times the close primitive ran, so a test
 *   can assert it fires only when the object owns the handle.
 * - {@see $closeResourceReturn}, when not null, forces the primitive's boolean result
 *   (the handle is still really closed to avoid leaking a file descriptor), so a test can
 *   drive the failure path of {@see \Prado\IO\TResource::closeStream()}.
 *
 * Any test that needs a plain `TResource` can use this as-is; the instrumentation is inert
 * at its defaults.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestResource extends TResource
{
	/** @var int The number of times {@see closeResource()} has been invoked. */
	public int $closeResourceCalls = 0;

	/** @var ?bool When set, the forced result of {@see closeResource()}; null uses the real close. */
	public ?bool $closeResourceReturn = null;

	/**
	 * Records the call, then closes the handle (or returns the forced result).
	 * @param mixed $resource The resource to close.
	 * @return bool Whether the close succeeded.
	 */
	protected function closeResource(mixed $resource): bool
	{
		$this->closeResourceCalls++;
		if ($this->closeResourceReturn !== null) {
			if (is_resource($resource)) {
				@fclose($resource); // still release the fd; only the reported result is forced
			}
			return $this->closeResourceReturn;
		}
		return parent::closeResource($resource);
	}
}
