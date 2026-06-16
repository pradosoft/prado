<?php

/**
 * TComposer class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Prado\Caching\ICacheDependency;
use Prado\Caching\TChainedCacheDependency;
use Prado\Caching\TFileCacheDependency;
use Prado\Prado;

/**
 * TComposer class
 *
 * TComposer reads the Composer metadata of the project. It collects every installed
 * package from the `installed.json` manifest of each registered Composer vendor
 * directory and exposes the package data and the `extra` fields to the application.
 * A package's absolute install path is available via {@see getPackagePath()}, read
 * from Composer's {@see \Composer\InstalledVersions} runtime.
 *
 * The installed packages are read from the registered {@see \Composer\Autoload\ClassLoader}
 * loaders. The result is cached using the application cache, keyed by
 * {@see TComposer::COMPOSER_INSTALLED_CACHE}, with a file dependency on each
 * `installed.json` so the cache invalidates when the installed packages change.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TComposer extends \Prado\TComponent
{
	/**
	 * The cache name for the installed Prado Composer packages.
	 */
	public const COMPOSER_INSTALLED_CACHE = 'prado:composer:installedpackages';

	/**
	 * Name of the Composer `extra` sub-array that namespaces a package's Prado
	 * settings, e.g. `extra.prado.bootstrap`.
	 */
	public const EXTRA_PRADO = 'prado';

	/**
	 * @var null|array<string, array> installed packages indexed by package name.
	 */
	private static $_packages;

	/**
	 * Reads the installed Composer packages of the project. The static
	 * {@see \Composer\Autoload\ClassLoader::getRegisteredLoaders()} provide the vendor
	 * directories. The `installed.json` of each vendor directory is read and merged.
	 * The result is cached with a file dependency on each `installed.json`.
	 * @return array<string, array> the installed package manifests indexed by package name.
	 */
	protected static function loadInstalledPackages(): array
	{
		if ($cache = Prado::getApplication()->getCache()) {
			$packages = $cache->get(static::COMPOSER_INSTALLED_CACHE);
			if ($packages !== false && $packages !== null) {
				return $packages;
			}
		}
		$dependencies = new TChainedCacheDependency();
		$listDeps = $dependencies->getDependencies();
		$packages = [];
		foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
			$file = $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
			if (!is_file($file)) {
				continue;
			}
			$manifests = static::readManifest($file);
			foreach ($manifests['packages'] as $package) {
				if (isset($package['name'])) {
					$packages[$package['name']] = $package;
				}
			}
			if (($dep = static::newFileCacheDependency($file)) !== null) {
				$listDeps[] = $dep;
			}
		}
		if ($cache) {
			$cache->set(static::COMPOSER_INSTALLED_CACHE, $packages, 0, $dependencies);
		}
		return $packages;
	}

	/**
	 * Reads and decodes a Composer `installed.json` manifest file.
	 * @param string $file the absolute path to the `installed.json` file.
	 * @throws \JsonException when the file content is not valid JSON.
	 * @return array the decoded manifest, including its `packages` list.
	 */
	protected static function readManifest(string $file): array
	{
		return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * Creates the cache dependency for a Composer `installed.json` manifest file.
	 * The dependency invalidates the cached packages when the file changes.
	 * @param string $file the absolute path to the `installed.json` file.
	 * @return null|\Prado\Caching\ICacheDependency the file dependency, or null for no dependency.
	 */
	protected static function newFileCacheDependency(string $file): ?ICacheDependency
	{
		return new TFileCacheDependency($file);
	}

	/**
	 * Returns every installed Composer package of the project, indexed by package name.
	 * The packages are loaded once and held for the life of the process.
	 * @return array<string, array> the installed package manifests indexed by package name.
	 */
	public static function getInstalledPackages(): array
	{
		if (self::$_packages === null) {
			self::$_packages = static::loadInstalledPackages();
		}
		return self::$_packages;
	}

	/**
	 * Returns the latest modification time across the `installed.json` manifests of
	 * every registered Composer vendor directory. Composer rewrites these files when
	 * packages are installed, updated, or removed, so the value changes whenever the
	 * set of installed packages (or any package's `extra` metadata) changes. Callers
	 * use it to invalidate caches derived from the installed packages.
	 * @return null|int the newest manifest modification time, or null when no
	 *   manifest exists.
	 * @since 4.4.0
	 */
	public static function getInstalledManifestsTime(): ?int
	{
		$time = null;
		foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
			$file = $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
			if (is_file($file) && ($mtime = filemtime($file)) !== false) {
				$time = ($time === null) ? $mtime : max($time, $mtime);
			}
		}
		return $time;
	}

	/**
	 * Returns the manifest of a single installed Composer package.
	 * @param string $name the package name, for example `vendor/package`.
	 * @return null|array the package manifest, or null when the package is not installed.
	 */
	public static function getPackage(string $name): ?array
	{
		return static::getInstalledPackages()[$name] ?? null;
	}

	/**
	 * Returns the Composer `extra` data of an installed package.
	 *
	 * When $key is null, the whole `extra` array of the package is returned. When $key is
	 * given, the value of that single `extra` field is returned.
	 *
	 * @param string $name the package name, for example `vendor/package`.
	 * @param null|string $key the `extra` field to read, or null for the whole `extra` array.
	 * @return mixed the `extra` array, the single `extra` field value, or null when the
	 *   package or field is not present.
	 */
	public static function getExtra(string $name, ?string $key = null): mixed
	{
		$extra = static::getPackage($name)['extra'] ?? null;
		if ($key === null || !is_array($extra)) {
			return $extra;
		}
		return $extra[$key] ?? null;
	}

	/**
	 * Returns the Prado-namespaced Composer `extra` data of an installed package,
	 * read from the {@see EXTRA_PRADO} (`extra.prado`) sub-array.
	 *
	 * When $key is null, the whole `extra.prado` array is returned. When $key is
	 * given, the value of that single `extra.prado` field is returned.
	 *
	 * @param string $name the package name, for example `vendor/package`.
	 * @param null|string $key the `extra.prado` field to read, or null for the whole array.
	 * @return mixed the `extra.prado` array, the single field value, or null when the
	 *   package, the `prado` sub-array, or the field is not present.
	 */
	public static function getPradoExtra(string $name, ?string $key = null): mixed
	{
		$prado = static::getExtra($name, static::EXTRA_PRADO);
		if ($key === null || !is_array($prado)) {
			return $prado;
		}
		return $prado[$key] ?? null;
	}

	/**
	 * Returns the absolute filesystem path of an installed Composer package, the
	 * base directory used to resolve package-relative file references.
	 * @param string $name the package name, for example `vendor/package`.
	 * @return null|string the absolute install path, or null when the package is
	 *   not installed.
	 */
	public static function getPackagePath(string $name): ?string
	{
		if (!InstalledVersions::isInstalled($name)) {
			return null;
		}
		$path = InstalledVersions::getInstallPath($name);
		if ($path === null) {
			return null;
		}
		return realpath($path) ?: $path;
	}

	/**
	 * Clears the in-memory installed packages, so the next read reloads them.
	 */
	public static function flushInstalledPackages(): void
	{
		self::$_packages = null;
	}
}
