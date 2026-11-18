<?php

/**
 * TJavaScriptPackagesTest
 *
 * Unit tests for the package manifests `packages.php` and `css-packages.php`
 * that {@see \Prado\Web\UI\TClientScriptManager} loads.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Javascripts;

use Prado\Prado;
use Prado\Web\UI\TClientScriptManager;

class TJavaScriptPackagesTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @return array<string, array{string}> the manifest files, relative to the framework.
	 */
	public static function manifestProvider(): array
	{
		return [
			'scripts' => [TClientScriptManager::PACKAGES_FILE],
			'styles' => [TClientScriptManager::CSS_PACKAGES_FILE],
		];
	}

	/**
	 * Loads a manifest.
	 * @param string $file the manifest path relative to the framework.
	 * @return array{array<string,string>, array<string,string[]>, array<string,string[]>}
	 */
	private function loadManifest(string $file): array
	{
		$manifest = include Prado::getFrameworkPath() . DIRECTORY_SEPARATOR . $file;
		$this->assertIsArray($manifest);
		$this->assertCount(3, $manifest);
		return $manifest;
	}

	/**
	 * @dataProvider manifestProvider
	 */
	public function testEveryPackageFileExistsInItsFolder(string $file): void
	{
		[$folders, $packages] = $this->loadManifest($file);
		$this->assertNotEmpty($packages);
		foreach ($packages as $name => $files) {
			$this->assertNotEmpty($files, "Package '$name' lists no files.");
			foreach ($files as $path) {
				[$base, $subPath] = explode('/', $path, 2);
				$this->assertArrayHasKey($base, $folders, "Package '$name' file '$path' has no folder.");
				$dir = Prado::getPathOfNamespace($folders[$base]);
				$this->assertFileExists($dir . DIRECTORY_SEPARATOR . $subPath, "Package '$name' file '$path' is missing.");
			}
		}
	}

	/**
	 * @dataProvider manifestProvider
	 */
	public function testEveryDependencyIsAPackage(string $file): void
	{
		[, $packages, $dependencies] = $this->loadManifest($file);
		$this->assertNotEmpty($dependencies);
		foreach ($dependencies as $name => $list) {
			$this->assertNotEmpty($list, "Package '$name' loads nothing.");
			foreach ($list as $dependency) {
				$this->assertArrayHasKey($dependency, $packages, "Package '$name' depends on unknown '$dependency'.");
			}
		}
	}

	public function testJqueryUiStylePackagesUseTheThemeFileNames(): void
	{
		[, $packages] = $this->loadManifest(TClientScriptManager::CSS_PACKAGES_FILE);
		$this->assertSame(['jquery-ui/themes/base/dialog.css'], $packages['jquery.ui.dialog']);
		$this->assertSame(['jquery-ui/themes/base/core.css'], $packages['jquery.ui.core']);
	}
}
