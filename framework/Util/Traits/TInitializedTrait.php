<?php

/**
 * TInitializedTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TInvalidOperationException;

/**
 * TInitializedTrait class.
 * ```php
 * public function init($config): void
 * {
 *    $this->markInitialized();
 *     // … your initialization setup that behaviors depend upon …
 *     parent::init($config);
 *     // … further init after behaviors are initialized …
 *     $this->markInitialized();
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait TInitializedTrait
{
	/** @var bool Whether the owning class has completed its `init()` phase.  */
	private ?bool $_initialized = null;

	/**
	 * Returns whether the owning object has not yet started to initialize.
	 * @return bool `true` once {@see markInitialized()} has been called
	 */
	public function getIsUninitialized(): bool
	{
		return $this->_initialized === null;
	}

	/**
	 * Returns whether the owning object is in the process of initializing.
	 * @return bool `true` once {@see markInitialized()} has been called
	 */
	public function getIsInitializing(): bool
	{
		return $this->_initialized === false;
	}

	/**
	 * Returns whether the owning object has completed its initialization phase.
	 * @return bool `true` once {@see markInitialized()} has been called
	 */
	public function getIsInitialized(): bool
	{
		return $this->_initialized === true;
	}

	/**
	 * Marks the owning object as complete in its initialization.
	 *
	 * Call this at the end of `init()`, after `parent::init($config)`, so that
	 * configuration properties remain mutable during the entire initialization
	 * sequence (including any parent class setup):
	 *
	 * ```php
	 * public function init($config): void
	 * {
	 *    try {
	 *        $this->markStartInitialize();
	 *        // … your setup …
	 *        parent::init($config);
	 *        $this->markInitialized();
	 *    } catch(\Exception $e) {
	 *        $this->resetInitialized();
	 *        throw $e;
	 *    }
	 * }
	 * ```
	 *
	 * This method is intentionally idempotent: calling it more than once is
	 * safe and produces no error.
	 */
	protected function markStartInitialize(): void
	{
		if ($this->_initialized === null) {
			$this->_initialized = false;
		}
	}

	/**
	 * Marks the owning object as complete in its initialization.
	 *
	 * Call this at the end of `init()`, after `parent::init($config)`, so that
	 * configuration properties remain mutable during the entire initialization
	 * sequence (including any parent class setup):
	 *
	 * ```php
	 * public function init($config): void
	 * {
	 *     // … your setup …
	 *     parent::init($config);
	 *     $this->markInitialized();
	 * }
	 * ```
	 *
	 * This method is intentionally idempotent: calling it more than once is
	 * safe and produces no error.
	 */
	protected function resetInitialized(): void
	{
		$this->_initialized = null;
	}

	/**
	 * Marks the owning object as complete in its initialization.
	 *
	 * Call this at the end of `init()`, after `parent::init($config)`, so that
	 * configuration properties remain mutable during the entire initialization
	 * sequence (including any parent class setup):
	 *
	 * ```php
	 * public function init($config): void
	 * {
	 *     // … your setup …
	 *     parent::init($config);
	 *     $this->markInitialized();
	 * }
	 * ```
	 *
	 * This method is intentionally idempotent: calling it more than once is
	 * safe and produces no error.
	 */
	protected function markInitialized(): void
	{
		$this->_initialized = true;
	}

	/**
	 * Guards a configuration-phase property setter against mutation after
	 * initialization.
	 *
	 * Place this as the very first statement of any setter that must be
	 * frozen once the module has been initialized:
	 *
	 * ```php
	 * public function setTableName(string $value): void
	 * {
	 *     $this->assertUninitialized('TableName');
	 *     $this->_tableName = $value;
	 * }
	 * ```
	 *
	 * The exception message key is controlled by {@see getIsInitializedExceptionKey()}.
	 * Override it in the consuming class to supply a module-specific
	 * catalogue key.
	 *
	 * @param string $property the property name included in the exception message
	 * @param string $exceptionKey The key name of the exception message, default is null
	 *							   and uses {@see getIsInitializedExceptionKey()} for the
	 *							   backup default message.
	 * @throws TInvalidOperationException when the object is already initialized
	 */
	protected function assertUninitialized(string $property, ?string $exceptionKey = null): void
	{
		if (!$this->getIsUninitialized()) {
			throw new TInvalidOperationException(
				$exceptionKey ?? $this->getIsInitializedExceptionKey(),
				$property,
				array_slice(explode('\\', static::class), -1)[0]
			);
		}
	}

	/**
	 * Guards a method or process against operation before initialization.
	 *
	 * Place this as the very first statement of any method or process that must be
	 * initialized before continuing:
	 *
	 * ```php
	 * public function setTableName(string $value): void
	 * {
	 *     $this->assertUninitialized('TableName');
	 *     $this->_tableName = $value;
	 * }
	 * ```
	 *
	 * The exception message key is controlled by {@see getIsInitializedExceptionKey()}.
	 * Override it in the consuming class to supply a module-specific
	 * catalogue key.
	 *
	 * @param string $property the property name included in the exception message
	 * @param string $exceptionKey The key name of the exception message, default is null
	 *							   and uses {@see getIsInitializedExceptionKey()} for the
	 *							   backup default message.
	 * @throws TInvalidOperationException when the object is already initialized
	 */
	protected function assertInitialized(string $property, ?string $exceptionKey = null): void
	{
		if (!$this->getIsInitialized()) {
			throw new TInvalidOperationException(
				$exceptionKey ?? $this->getIsNotInitializedExceptionKey(),
				$property,
				array_slice(explode('\\', static::class), -1)[0]
			);
		}
	}

	/**
	 * Returns the PRADO error message catalogue key used by {@see assertUninitialized()}
	 * when it throws a {@see TInvalidOperationException}.
	 *
	 * The key's corresponding message are:
	 *   - `{0}` placeholder for the property name
	 *   - `{1}` placeholder for the static class name
	 *
	 * For example:
	 * ```
	 * "{1}.{0} cannot be changed after initialization."
	 * ```
	 *
	 * Override in the consuming class to return a module-specific key:
	 * ```php
	 * protected function getIsInitializedExceptionKey(): string
	 * {
	 *     return 'mymodule_property_unchangeable';
	 * }
	 * ```
	 *
	 * @return string a PRADO error message catalogue key.
	 */
	protected function getIsInitializedExceptionKey(): string
	{
		return 'initialized_property_unchangeable';
	}

	/**
	 * Returns the PRADO error message catalogue key used by {@see assertUninitialized()}
	 * when it throws a {@see TInvalidOperationException}.
	 *
	 * The key's corresponding message are:
	 *   - `{0}` placeholder for the property name
	 *   - `{1}` placeholder for the static class name
	 *
	 * For example:
	 * ```
	 * "{1}.{0} requires initialization before running."
	 * ```
	 *
	 * Override in the consuming class to return a module-specific key:
	 * ```php
	 * protected function getIsUninitializedExceptionKey(): string
	 * {
	 *     return 'mymodule_requires_initialization';
	 * }
	 * ```
	 *
	 * @return string a PRADO error message catalogue key.
	 */
	protected function getIsNotInitializedExceptionKey(): string
	{
		return 'initialized_requires_initialization';
	}
}
