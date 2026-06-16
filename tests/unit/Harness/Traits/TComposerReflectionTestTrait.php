<?php

/**
 * TComposerReflectionTestTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Composer\Autoload\ClassLoader;
use Prado\Prado;
use Prado\Util\TComposerReflection;

/**
 * TComposerReflectionTestTrait provides the shared scaffolding for {@see \Prado\Util\TComposerReflection}
 * tests.
 *
 * The trait isolates process state so each test starts clean:
 *
 * - {@see setUp()} captures the application cache module and clears the static
 *   `$_packages` cache.
 * - {@see tearDown()} resets {@see TTestComposerReflection}, restores the cache module and
 *   static cache, then unregisters and deletes every temporary vendor directory
 *   created during the test.
 *
 * {@see makeVendor()} builds a temporary Composer vendor directory with an
 * `installed.json` manifest and registers a real {@see ClassLoader} for it, so
 * {@see TComposerReflection::loadInstalledPackages} reads it like any installed dependency.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TComposerReflectionTestTrait
{
	/** @var mixed The application cache module captured before each test. */
	private mixed $_cacheSnap = null;

	/** @var array<int, string> Temp vendor directories registered during a test. */
	private array $_tempVendors = [];

	protected function setUp(): void
	{
		// Capture and clear the static package cache so each test starts fresh.
		$this->_cacheSnap = PradoUnit::getProp(Prado::getApplication(), '_cache');
		PradoUnit::setStaticProp(TComposerReflection::class, '_packages', null);
		$this->_tempVendors = [];
	}

	protected function tearDown(): void
	{
		TTestComposerReflection::reset();
		// Restore the static cache and the application cache module.
		PradoUnit::setStaticProp(TComposerReflection::class, '_packages', null);
		PradoUnit::setProp(Prado::getApplication(), '_cache', $this->_cacheSnap);

		// Unregister and delete every temp vendor directory created by the test.
		foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
			if (in_array($vendorDir, $this->_tempVendors, true)) {
				$loader->unregister();
			}
		}
		foreach ($this->_tempVendors as $vendorDir) {
			$this->removeTree($vendorDir);
		}
		$this->_tempVendors = [];
	}

	/**
	 * Creates a temporary vendor directory containing `composer/installed.json`,
	 * registers a real ClassLoader for it, and returns the vendor path.
	 *
	 * @param array<int, array> $packages packages written to the manifest.
	 * @param null|string $rawJson when given, written verbatim instead of $packages
	 *   (used to inject malformed JSON).
	 * @param bool $writeFile when false, the installed.json is not created.
	 * @return string the temp vendor directory path (the loader registration key).
	 */
	protected function makeVendor(array $packages = [], ?string $rawJson = null, bool $writeFile = true): string
	{
		$vendorDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcomposer_' . uniqid('', true);
		mkdir($vendorDir . DIRECTORY_SEPARATOR . 'composer', 0777, true);
		if ($writeFile) {
			$file = $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
			$content = $rawJson ?? json_encode(['packages' => $packages]);
			file_put_contents($file, $content);
		}
		$loader = new ClassLoader($vendorDir);
		$loader->register();
		$this->_tempVendors[] = $vendorDir;
		return $vendorDir;
	}

	/**
	 * Returns the `installed.json` path inside a vendor directory from {@see makeVendor()}.
	 *
	 * @param string $vendorDir the vendor directory path.
	 * @return string the absolute manifest path.
	 */
	protected function manifestPath(string $vendorDir): string
	{
		return $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
	}

	/**
	 * Recursively deletes a directory tree created by {@see makeVendor()}.
	 */
	protected function removeTree(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		foreach (scandir($dir) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $entry;
			is_dir($path) ? $this->removeTree($path) : unlink($path);
		}
		rmdir($dir);
	}

	/**
	 * Invokes the protected {@see TComposerReflection::loadInstalledPackages} on the given class.
	 *
	 * @param class-string $class the class to invoke on (TComposerReflection or a subclass).
	 * @return array the loaded packages.
	 */
	protected function invokeLoad(string $class = TComposerReflection::class): array
	{
		return PradoUnit::invoke($class, 'loadInstalledPackages');
	}
}
