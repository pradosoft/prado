<?php

/**
 * TTestShellApplication class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/Traits/TTestApplicationRestorationTrait.php';

use Prado\Prado;
use Prado\Shell\TShellApplication;
use Prado\Shell\TShellWriter;

/**
 * TTestShellApplication extends {@see TShellApplication} with test-friendly behaviour.
 *
 * Three things differ from the production class:
 *
 * **1. Full global-state safety.**
 * {@see TTestApplicationRestorationTrait} snapshots the complete static state of both
 * {@see \Prado\Prado} and {@see \Prado\TComponent} — singleton, all path aliases,
 * class-behavior registry, global event-handler registry — before registering `$this`,
 * then restores it all via {@see restoreApplication()}. Call `restoreApplication()` from
 * `tearDown()`; the destructor provides a safety net if you forget.
 *
 * **2. Always-temp path resolution.**
 * {@see resolvePaths()} always uses `sys_get_temp_dir()` as the base path and
 * creates a `runtime/` subdirectory there. The `$basePath` argument passed to the
 * constructor is silently ignored, so tests do not need a real application directory.
 *
 * **3. Protected accessors exposed for testing.**
 * A suite of `pub*` wrapper methods expose protected `TShellApplication` getters and
 * setters as public, eliminating the need for reflection boilerplate in test classes.
 *
 * Typical usage:
 *
 * ```php
 * class MyShellTest extends PHPUnit\Framework\TestCase
 * {
 *     private TTestShellApplication $_app;
 *
 *     protected function setUp(): void
 *     {
 *         $this->_app = new TTestShellApplication();
 *     }
 *
 *     protected function tearDown(): void
 *     {
 *         $this->_app->restoreApplication();
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TTestShellApplication extends TShellApplication
{
	use TTestApplicationRestorationTrait;

	/**
	 * Constructor.
	 * @param ?string $basePath Ignored — the base path is always resolved to
	 *   `sys_get_temp_dir()` by {@see resolvePaths()}. Accepted only to match the
	 *   parent signature.
	 * @param bool $cacheConfig Whether to cache parsed configuration. Defaults to
	 *   `false` — no cache file is written during unit tests.
	 * @param ?string $configType Configuration type constant. Defaults to
	 *   {@see \Prado\TApplication::CONFIG_TYPE_XML}.
	 */
	public function __construct($basePath = null, $cacheConfig = false, $configType = null)
	{
		parent::__construct($basePath ?? sys_get_temp_dir(), $cacheConfig, $configType);
	}

	/**
	 * Resolves the base and runtime paths for a test environment.
	 *
	 * Unlike the production implementation this method always uses `sys_get_temp_dir()`
	 * as the application base path, ignoring `$basePath`. This avoids the need for a
	 * real application directory layout during unit tests.
	 *
	 * The `runtime/` sub-directory is created inside `sys_get_temp_dir()` when absent.
	 *
	 * @param string $basePath Ignored.
	 */
	protected function resolvePaths($basePath): void
	{
		$tmpDir = sys_get_temp_dir();
		$this->setBasePath($tmpDir);
		$runtimePath = $tmpDir . DIRECTORY_SEPARATOR . static::RUNTIME_PATH;
		if (!is_dir($runtimePath)) {
			@mkdir($runtimePath, 0777, true);
		}
		$this->setRuntimePathDirect($runtimePath);
	}

	// =========================================================================
	// Protected accessors exposed as public wrappers for test assertions
	// =========================================================================

	/** @return array<string, callable> Application-level option name → handler map. */
	public function pubGetOptions(): array { return $this->getOptions(); }

	/** @return array<string, string> Short-option alias → canonical option name map. */
	public function pubGetOptionAliases(): array { return $this->getOptionAliases(); }

	/** @return array<string, array{0:string,1:string}> Option name → [description, valueSuffix] map. */
	public function pubGetOptionsData(): array { return $this->getOptionsData(); }

	/** @return bool Whether the greeting/help banner has already been printed this run. */
	public function pubIsHelpPrinted(): bool { return $this->isHelpPrinted(); }

	/** @param bool $v New help-printed flag value. */
	public function pubSetHelpPrinted(bool $v): void { $this->setHelpPrinted($v); }

	/** @return array<int, string> The current argv-style argument list. */
	public function pubGetArguments(): array { return $this->getArguments(); }

	/** @param array<int, string> $args New argv-style argument list. */
	public function pubSetArguments(array $args): void { $this->setArguments($args); }

	/**
	 * Returns the underlying {@see TShellWriter} without triggering lazy creation.
	 * Returns `null` when no writer has been set yet or after {@see flushOutput(false)}.
	 *
	 * @return ?TShellWriter
	 */
	public function pubGetWriterDirect(): ?TShellWriter { return $this->getWriterDirect(); }
}
