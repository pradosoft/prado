<?php

/**
 * TTestComposer class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Util\TComposer;

/**
 * TTestComposer exposes the two filesystem seams of {@see \Prado\Util\TComposer}
 * for override in unit tests.
 *
 * The behavior is configurable through static properties so tests can inject
 * manifest data and suppress the file cache dependency without reading from
 * disk through these methods:
 *
 * - {@see TTestComposer::$manifestOverride} replaces the decoded manifest returned
 *   by {@see readManifest()}.
 * - {@see TTestComposer::$nullDependency} makes {@see newFileCacheDependency()} return
 *   null so no file dependency is added to the cache chain.
 *
 * {@see readFiles} and {@see dependencyFiles} record the files passed to each seam.
 * {@see reset()} clears all configuration and recorded calls between tests.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestComposer extends TComposer
{
	/** @var null|array when set, returned by readManifest() instead of reading the file. */
	public static ?array $manifestOverride = null;

	/** @var bool when true, newFileCacheDependency() returns null (no dependency). */
	public static bool $nullDependency = false;

	/** @var array<int, string> files passed to readManifest(). */
	public static array $readFiles = [];

	/** @var array<int, string> files passed to newFileCacheDependency(). */
	public static array $dependencyFiles = [];

	/**
	 * Clears all seam configuration and recorded calls.
	 */
	public static function reset(): void
	{
		static::$manifestOverride = null;
		static::$nullDependency = false;
		static::$readFiles = [];
		static::$dependencyFiles = [];
	}

	protected static function readManifest(string $file): array
	{
		static::$readFiles[] = $file;
		if (static::$manifestOverride !== null) {
			return static::$manifestOverride;
		}
		return parent::readManifest($file);
	}

	protected static function newFileCacheDependency(string $file): ?\Prado\Caching\ICacheDependency
	{
		static::$dependencyFiles[] = $file;
		if (static::$nullDependency) {
			return null;
		}
		return parent::newFileCacheDependency($file);
	}
}
