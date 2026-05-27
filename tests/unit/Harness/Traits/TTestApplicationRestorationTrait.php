<?php

/**
 * TTestApplicationRestorationTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\TComponent;

/**
 * TTestApplicationRestorationTrait provides full global-state save/restore for
 * test-friendly {@see \Prado\TApplication} subclasses.
 *
 * ## What this trait manages
 *
 * Every `TApplication` constructor calls, in order:
 *
 * 1. `registerApplication()` — registers `$this` as the global singleton.
 * 2. `resolvePaths()` — sets the base and runtime paths.
 * 3. `Prado::setPathOfAlias('Application', $this->getBasePath())` — overwrites
 *    the `Application` path alias.
 *
 * Beyond those two writes, calling `run()` or loading modules can register
 * additional path aliases (via `applyConfiguration()`), attach global event
 * listeners via `TComponent::listen()`, and add class behaviors via
 * `TComponent::attachClassBehavior()`. All of this mutates static state on
 * {@see Prado} and {@see TComponent}.
 *
 * ## Global state covered
 *
 * The trait's {@see registerApplication()} takes a full static-property snapshot
 * of both `Prado` and `TComponent` *before* calling `Prado::setApplication($this)`.
 * That snapshot therefore contains:
 *
 * - `Prado::$_application` — the previous singleton.
 * - `Prado::$_aliases` — the complete alias table, including `Application`.
 * - `Prado::$_usings` — the full using/namespace map.
 * - `Prado::$classMap` — the class discovery map.
 * - `Prado::$_logger` — the lazily-created logger instance.
 * - `Prado::$classExists` — the class-exists cache.
 * - `TComponent::$_ue` — the global event-handler registry.
 * - `TComponent::$_um` — the class-behavior registry.
 *
 * {@see restoreApplication()} writes all of those values back via
 * {@see PradoUnit::restoreStatic()}, returning both classes to exactly the state
 * they were in before this application was constructed — even when `run()` was
 * called during the test.
 *
 * ## Usage
 *
 * 1. Use the trait in a `TApplication` (or `TShellApplication`) subclass.
 * 2. The trait `__destruct()` must be called in the using class; it can be `as` redefined for
 *    Apps with their own.
 * 3. Call {@see restoreApplication()} from `tearDown()` for deterministic cleanup.
 *    The destructor is a safety net only.
 *
 * ```php
 * class TTestApplication extends TApplication
 * {
 *     use TTestApplicationRestorationTrait;
 *     // ...
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait TTestApplicationRestorationTrait
{
	/**
	 * @var array<string, mixed>|null Full static-property snapshot of {@see Prado}
	 *   captured at {@see registerApplication()} time — before `Prado::setApplication($this)`
	 *   or `Prado::setPathOfAlias('Application', ...)` run. `null` when not yet registered
	 *   or after {@see restoreApplication()} has completed.
	 */
	private ?array $_pradoSnapshot = null;

	/**
	 * @var array<string, mixed>|null Full static-property snapshot of {@see TComponent}
	 *   captured at {@see registerApplication()} time. Covers the global event-handler
	 *   registry (`$_ue`) and the class-behavior registry (`$_um`). `null` when not yet
	 *   registered or after {@see restoreApplication()} has completed.
	 */
	private ?array $_componentSnapshot = null;

	/**
	 * Snapshots the full static state of {@see Prado} and {@see TComponent}, then
	 * registers `$this` as the PRADO application singleton.
	 *
	 * The snapshot is taken *before* `Prado::setApplication($this)` so that the
	 * stored `$_application` value is the previous singleton — exactly what
	 * {@see restoreApplication()} must put back. Every subsequent mutation made
	 * by the constructor, by `run()`, or by any module loaded during the test is
	 * therefore fully covered by the snapshot.
	 */
	protected function registerApplication(): void
	{
		$this->_pradoSnapshot     = PradoUnit::snapshotStatic(Prado::class);
		$this->_componentSnapshot = PradoUnit::snapshotStatic(TComponent::class);
		Prado::setApplication($this);
	}

	/**
	 * Restores the full static state of {@see Prado} and {@see TComponent} from the
	 * snapshots captured by {@see registerApplication()}. Idempotent — safe to call
	 * more than once.
	 *
	 * Call this from `tearDown()` to guarantee deterministic cleanup between tests.
	 */
	public function restoreApplication(): void
	{
		if ($this->_pradoSnapshot === null) {
			return;
		}
		PradoUnit::restoreStatic(Prado::class, $this->_pradoSnapshot);
		PradoUnit::restoreStatic(TComponent::class, $this->_componentSnapshot);
		$this->_pradoSnapshot     = null;
		$this->_componentSnapshot = null;
	}

	/**
	 * Safety-net destructor: calls {@see restoreApplication()} in case the test
	 * forgot to call it from `tearDown()`. Also calls `parent::__destruct()` to
	 * let `TComponent` clean up behaviors and global-event listeners.
	 */
	public function __destruct()
	{
		$this->restoreApplication();
		parent::__destruct();
	}
}
