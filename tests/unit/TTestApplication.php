<?php

/**
 * TTestApplication class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestApplicationRestorationTrait.php';

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;

/**
 * TTestApplication extends {@see TApplication} with test-friendly behaviour.
 *
 * Five things differ from the production class:
 *
 * **1. Full global-state safety.**
 * {@see TTestApplicationRestorationTrait::registerApplication()} snapshots the complete
 * static state of both {@see \Prado\Prado} and {@see \Prado\TComponent} — including the
 * singleton, all path aliases, the class-behavior registry, and the global event-handler
 * registry — before registering `$this`. {@see TTestApplicationRestorationTrait::restoreApplication()}
 * writes every field back via {@see PradoUnit::restoreStatic()}. This covers mutations
 * from the constructor, from `run()`, and from any module loaded during the test. Call
 * `restoreApplication()` from `tearDown()` in any test that constructs a
 * `TTestApplication`; the destructor calls it as a safety net if you forget.
 *
 * **2. Lightweight path resolution.**
 * {@see resolvePaths()} accepts any writable directory, creates the runtime
 * sub-directory if it is absent, and does not require or search for an
 * application configuration file. It does not create the versioned runtime
 * sub-directories that the production implementation adds.
 *
 * **3. Sane test defaults.**
 * `$basePath` defaults to `sys_get_temp_dir()` and `$cacheConfig` defaults
 * to `false`, so a plain `new TTestApplication()` works without any setup.
 *
 * **4. Exit capture.**
 * {@see exit()} is overridden to store the exit code in {@see $capturedExitCode}
 * instead of terminating the process, letting tests assert on TExitException
 * paths without ending the test runner.
 *
 * **5. Application snapshot helpers.**
 * {@see snapshotApp()} and {@see restoreApp()} delegate to {@see PradoUnit} to
 * capture and restore every instance property on a `TApplication` (including
 * private ancestor properties) across the full class hierarchy.
 *
 * Typical usage:
 *
 * ```php
 * class MyTest extends PHPUnit\Framework\TestCase
 * {
 *     private TTestApplication $app;
 *
 *     protected function setUp(): void
 *     {
 *         $this->app = new TTestApplication();
 *     }
 *
 *     protected function tearDown(): void
 *     {
 *         $this->app->restoreApplication();
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TTestApplication extends TApplication
{
	use TTestApplicationRestorationTrait;

	/**
	 * @var ?int Exit code passed to {@see exit()} by the last TExitException
	 *   caught in {@see run()}. Null until run() encounters a TExitException.
	 */
	public ?int $capturedExitCode = null;

	/**
	 * @var ?\Throwable Exception captured by {@see onError()} or any
	 *   override that intercepts non-exit errors. Null until such an error
	 *   is encountered.
	 */
	public ?\Throwable $capturedError = null;

	/**
	 * Constructor.
	 * @param ?string $basePath Application base path. Defaults to `sys_get_temp_dir()`.
	 * @param bool $cacheConfig Whether to cache parsed configuration to a file.
	 *   Defaults to `false` — no cache file is written during unit tests.
	 * @param ?string $configType Configuration type constant. Defaults to
	 *   {@see TApplication::CONFIG_TYPE_XML}.
	 */
	public function __construct($basePath = null, $cacheConfig = false, $configType = null)
	{
		parent::__construct($basePath ?? sys_get_temp_dir(), $cacheConfig, $configType);
	}

	// =========================================================================
	// Application property snapshot / restore
	// =========================================================================

	/**
	 * Captures a snapshot of every instance property on a `TApplication`.
	 *
	 * Delegates to {@see PradoUnit::snapshot()} so the full class hierarchy
	 * (including private properties declared in `TApplication` and any ancestor)
	 * is covered. Pass an explicit `$propNames` list to capture only a subset.
	 *
	 * The returned array can be passed directly to {@see restoreApp()}.
	 *
	 * ```php
	 * $snap = TTestApplication::snapshotApp();
	 * // ... exercise code that mutates the app ...
	 * TTestApplication::restoreApp($snap);
	 * ```
	 *
	 * @param TApplication|null $app    Instance to snapshot; defaults to the
	 *   current global singleton ({@see Prado::getApplication()}).
	 * @param string[]          $propNames Optional list of property names to
	 *   capture. When empty every non-static instance property is captured.
	 * @return array<string, mixed> Snapshot map of property name to value.
	 */
	public static function snapshotApp(?TApplication $app = null, array $propNames = []): array
	{
		return PradoUnit::snapshot($app ?? Prado::getApplication(), $propNames);
	}

	/**
	 * Restores `TApplication` instance properties from a snapshot produced by
	 * {@see snapshotApp()}.
	 *
	 * Only the properties present in `$snapshot` are written back, so a
	 * partial snapshot (captured with an explicit `$propNames` list) is safe.
	 *
	 * @param array<string, mixed> $snapshot Snapshot previously returned by snapshotApp().
	 * @param TApplication|null    $app      Instance to restore; defaults to the
	 *   current global singleton.
	 */
	public static function restoreApp(array $snapshot, ?TApplication $app = null): void
	{
		PradoUnit::restore($app ?? Prado::getApplication(), $snapshot);
	}

	/**
	 * Stores `$exitCode` in {@see $capturedExitCode} instead of terminating the process.
	 * @param int $exitCode exit status that would have been passed to the OS.
	 */
	protected function exit(int $exitCode): void
	{
		$this->capturedExitCode = $exitCode;
	}

	/**
	 * Resolves the application base and runtime paths for a test environment.
	 *
	 * Unlike {@see TApplication::resolvePaths()}, this method:
	 * - Accepts any writable directory — the standard PRADO directory layout is
	 *   not required.
	 * - Does not search for an application configuration file.
	 * - Delegates runtime path creation to {@see resolveRuntimePath()}.
	 *
	 * @param string $basePath Absolute path to a writable directory to use as
	 *   the application base.
	 * @throws TConfigurationException if `$basePath` is not a directory or the
	 *   runtime sub-directory cannot be created.
	 */
	protected function resolvePaths($basePath): void
	{
		if (!is_dir($basePath)) {
			throw new TConfigurationException('application_basepath_invalid', $basePath);
		}

		$this->setBasePath($basePath);
		$this->setRuntimePathDirect($this->resolveRuntimePath($basePath, null));
	}

	/**
	 * Creates and returns the runtime sub-directory for a test environment.
	 * Creates `runtime/` inside `$basePath` when it is absent. Versioned
	 * sub-directories are not created — tests share a single flat runtime dir.
	 *
	 * @param string $basePath the validated application base path.
	 * @param string|null $configFile ignored in the test environment.
	 * @return string absolute path to the runtime directory.
	 * @throws TConfigurationException if the runtime sub-directory cannot be
	 *   created.
	 */
	protected function resolveRuntimePath(string $basePath, ?string $configFile): string
	{
		$runtimePath = $basePath . DIRECTORY_SEPARATOR . static::RUNTIME_PATH;
		if (!is_dir($runtimePath)) {
			if (@mkdir($runtimePath, 0777, true) === false) {
				throw new TConfigurationException('application_runtimepath_failed', $runtimePath);
			}
			@chmod($runtimePath, Prado::getDefaultDirPermissions());
		}
		return $runtimePath;
	}
}
