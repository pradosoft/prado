<?php

/**
 * TComposerReflectionTestTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Composer\Autoload\ClassLoader;
use Prado\Util\TComposerReflection;

/**
 * Tests for {@see TComposerReflectionTestTrait}, the shared composer-test scaffolding.
 *
 * The test class uses the trait under test, so its setUp/tearDown drive the
 * isolation and cleanup being verified.
 *
 * @package System.Harness.Traits
 */
class TComposerReflectionTestTraitTest extends PHPUnit\Framework\TestCase
{
	use TComposerReflectionTestTrait;

	public function testMakeVendor_writesManifestAndRegistersLoader(): void
	{
		$vendorDir = $this->makeVendor([['name' => 'trait/pkg', 'version' => '1']]);

		$this->assertFileExists($this->manifestPath($vendorDir));
		$this->assertArrayHasKey(
			$vendorDir,
			ClassLoader::getRegisteredLoaders(),
			'makeVendor must register a ClassLoader keyed by the vendor dir'
		);
	}

	public function testManifestPath_pointsAtComposerInstalledJson(): void
	{
		$vendorDir = $this->makeVendor([]);
		$expected = $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
		$this->assertSame($expected, $this->manifestPath($vendorDir));
	}

	public function testMakeVendor_writeFileFalse_skipsManifest(): void
	{
		$vendorDir = $this->makeVendor([], null, false);
		$this->assertFileDoesNotExist($this->manifestPath($vendorDir));
	}

	public function testMakeVendor_rawJsonWrittenVerbatim(): void
	{
		$vendorDir = $this->makeVendor([], '{ not valid');
		$this->assertSame('{ not valid', file_get_contents($this->manifestPath($vendorDir)));
	}

	public function testInvokeLoad_readsRegisteredVendor(): void
	{
		$this->makeVendor([['name' => 'trait/loaded', 'version' => '9']]);

		$packages = $this->invokeLoad();

		$this->assertArrayHasKey('trait/loaded', $packages);
	}

	public function testInvokeLoad_acceptsSubclass(): void
	{
		$this->makeVendor([['name' => 'trait/sub', 'version' => '1']]);

		$packages = $this->invokeLoad(TTestComposerReflection::class);

		$this->assertArrayHasKey('trait/sub', $packages);
	}

	public function testRemoveTree_deletesDirectoryRecursively(): void
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcomposer_trait_' . uniqid('', true);
		mkdir($dir . DIRECTORY_SEPARATOR . 'nested', 0777, true);
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'f.txt', 'x');

		$this->removeTree($dir);

		$this->assertDirectoryDoesNotExist($dir);
	}

	public function testSetUp_clearsStaticPackageCache(): void
	{
		// setUp ran before this test and must have nulled the static cache.
		$this->assertNull(PradoUnit::getStaticProp(TComposerReflection::class, '_packages'));
	}
}
