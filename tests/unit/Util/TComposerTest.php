<?php

/**
 * TComposerTest class file.
 *
 * Comprehensive coverage for {@see \Prado\Util\TComposer}, the dumb access layer over
 * the project's Composer metadata. Three surfaces are exercised:
 *
 *  - {@see TComposer::loadInstalledPackages} — the raw read across every
 *    registered {@see \Composer\Autoload\ClassLoader} vendor directory, the
 *    cache short-circuit / write-back, missing-file skipping, name keying, and
 *    malformed-JSON propagation. Driven with real ClassLoader registrations
 *    pointed at temporary vendor directories.
 *  - {@see TComposer::getInstalledPackages} / {@see TComposer::getPackage} —
 *    the lazy in-memory cache and single-package lookup.
 *  - {@see TComposer::getExtra} — the `extra` field accessor with and without a key.
 *
 * The static `$_packages` cache and the application cache module are snapshotted
 * in setUp and restored in tearDown so no test leaks process state.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\Util\TComposer;

// =============================================================================
// Tests
// =============================================================================

/**
 * The shared scaffolding (setUp/tearDown, temp-vendor builder, load invoker)
 * lives in {@see TComposerTestTrait}; the {@see TTestCacheStub} cache stub
 * and the {@see TTestComposer} seam subclass live in the test Harness.
 *
 */
class TComposerTest extends PHPUnit\Framework\TestCase
{
	use TComposerTestTrait;

	// =======================================================================
	// loadInstalledPackages — raw read across registered loaders
	// =======================================================================

	public function testLoad_readsRegisteredVendorInstalledJson(): void
	{
		$this->makeVendor([
			['name' => 'acme/widget', 'version' => '1.0.0'],
		]);

		$packages = $this->invokeLoad();

		$this->assertArrayHasKey('acme/widget', $packages);
		$this->assertSame('1.0.0', $packages['acme/widget']['version']);
	}

	public function testLoad_includesRealProjectDependencies(): void
	{
		// The dumb access layer reads the actual project vendor too.
		$packages = $this->invokeLoad();
		$this->assertArrayHasKey('bower-asset/jquery', $packages);
		$this->assertIsArray($packages['bower-asset/jquery']);
	}

	public function testLoad_keysByPackageName(): void
	{
		$this->makeVendor([
			['name' => 'acme/one', 'version' => '1'],
			['name' => 'acme/two', 'version' => '2'],
		]);

		$packages = $this->invokeLoad();

		$this->assertSame('acme/one', $packages['acme/one']['name']);
		$this->assertSame('acme/two', $packages['acme/two']['name']);
	}

	public function testLoad_mergesMultipleVendorDirectories(): void
	{
		$this->makeVendor([['name' => 'first/pkg', 'version' => '1']]);
		$this->makeVendor([['name' => 'second/pkg', 'version' => '2']]);

		$packages = $this->invokeLoad();

		$this->assertArrayHasKey('first/pkg', $packages);
		$this->assertArrayHasKey('second/pkg', $packages);
	}

	public function testLoad_laterVendorOverridesSameName(): void
	{
		$this->makeVendor([['name' => 'dup/pkg', 'version' => 'early']]);
		$this->makeVendor([['name' => 'dup/pkg', 'version' => 'late']]);

		$packages = $this->invokeLoad();

		// Iteration order follows registration; the last writer of a name wins.
		$this->assertSame('late', $packages['dup/pkg']['version']);
	}

	public function testLoad_skipsPackagesWithoutName(): void
	{
		$this->makeVendor([
			['version' => 'no-name'],
			['name' => 'acme/named', 'version' => '1'],
		]);

		$packages = $this->invokeLoad();

		$this->assertArrayHasKey('acme/named', $packages);
		// The nameless package contributes nothing detectable; only named keys exist.
		foreach (array_keys($packages) as $key) {
			$this->assertIsString($key);
			$this->assertNotSame('', $key);
		}
	}

	public function testLoad_skipsVendorWithMissingInstalledJson(): void
	{
		// Vendor dir registered but no installed.json written.
		$vendorDir = $this->makeVendor([], null, false);

		// Must not throw; the missing manifest is simply skipped.
		$packages = $this->invokeLoad();
		$this->assertIsArray($packages);
		$this->assertArrayNotHasKey($vendorDir, $packages);
	}

	public function testLoad_malformedJsonThrows(): void
	{
		$this->makeVendor([], '{ this is not json ');

		$this->expectException(\JsonException::class);
		$this->invokeLoad();
	}

	public function testLoad_emptyPackagesArrayYieldsRealOnly(): void
	{
		$this->makeVendor([]);

		$packages = $this->invokeLoad();

		// Empty manifest adds nothing but the real project packages remain.
		$this->assertArrayHasKey('bower-asset/jquery', $packages);
	}

	// =======================================================================
	// loadInstalledPackages — cache short-circuit and write-back
	// =======================================================================

	public function testLoad_returnsCachedValueWithoutReadingLoaders(): void
	{
		$sentinel = ['cached/pkg' => ['name' => 'cached/pkg']];
		$cache = new TTestCacheStub();
		$cache->getReturn = $sentinel;
		Prado::getApplication()->setCache($cache);

		// Register a loader whose package must NOT appear because the cache hits.
		$this->makeVendor([['name' => 'ignored/pkg', 'version' => '1']]);

		$packages = $this->invokeLoad();

		$this->assertSame($sentinel, $packages);
		$this->assertArrayNotHasKey('ignored/pkg', $packages);
		$this->assertSame([[TComposer::COMPOSER_INSTALLED_CACHE]], $cache->getCollectedCalls('get'));
		$this->assertSame(0, $cache->getCollectedCallCount('set'), 'A cache hit must not write back');
	}

	public function testLoad_cacheMissFalse_readsLoadersAndWritesBack(): void
	{
		$cache = new TTestCacheStub();
		$cache->getReturn = false; // miss
		Prado::getApplication()->setCache($cache);
		$this->makeVendor([['name' => 'fresh/pkg', 'version' => '9']]);

		$packages = $this->invokeLoad();

		$this->assertArrayHasKey('fresh/pkg', $packages);
		$this->assertSame(1, $cache->getCollectedCallCount('set'));
		// set() args are recorded positionally: [id, value, expire, dependency].
		[$id, $value, $expire, $dependency] = $cache->getCollectedCalls('set')[0];
		$this->assertSame(TComposer::COMPOSER_INSTALLED_CACHE, $id);
		$this->assertSame($packages, $value);
		$this->assertSame(0, $expire);
		$this->assertInstanceOf(\Prado\Caching\TChainedCacheDependency::class, $dependency);
	}

	public function testLoad_cacheMissNull_readsLoaders(): void
	{
		$cache = new TTestCacheStub();
		$cache->getReturn = null; // also a miss
		Prado::getApplication()->setCache($cache);
		$this->makeVendor([['name' => 'nullmiss/pkg', 'version' => '1']]);

		$packages = $this->invokeLoad();

		$this->assertArrayHasKey('nullmiss/pkg', $packages);
		$this->assertSame(1, $cache->getCollectedCallCount('set'));
	}

	public function testLoad_noCacheModule_stillReads(): void
	{
		// Default test app has no cache module.
		PradoUnit::setProp(Prado::getApplication(), '_cache', null);
		$this->makeVendor([['name' => 'nocache/pkg', 'version' => '1']]);

		$packages = $this->invokeLoad();

		$this->assertArrayHasKey('nocache/pkg', $packages);
	}

	// =======================================================================
	// getInstalledPackages — lazy in-memory cache
	// =======================================================================

	public function testGetInstalledPackages_populatesStaticCacheOnFirstCall(): void
	{
		$this->assertNull(PradoUnit::getStaticProp(TComposer::class, '_packages'));

		$packages = TComposer::getInstalledPackages();

		$this->assertIsArray($packages);
		$this->assertSame($packages, PradoUnit::getStaticProp(TComposer::class, '_packages'));
	}

	public function testGetInstalledPackages_returnsSeededCacheWithoutReloading(): void
	{
		$seed = ['seed/pkg' => ['name' => 'seed/pkg', 'version' => '1']];
		PradoUnit::setStaticProp(TComposer::class, '_packages', $seed);

		// Register a loader whose package must NOT appear — the seeded cache wins.
		$this->makeVendor([['name' => 'late/pkg', 'version' => '1']]);

		$packages = TComposer::getInstalledPackages();

		$this->assertSame($seed, $packages);
		$this->assertArrayNotHasKey('late/pkg', $packages);
	}

	public function testGetInstalledPackages_emptySeedIsNotReloaded(): void
	{
		// An empty array is a real (non-null) cache; it must not trigger reload.
		PradoUnit::setStaticProp(TComposer::class, '_packages', []);
		$this->makeVendor([['name' => 'should/skip', 'version' => '1']]);

		$this->assertSame([], TComposer::getInstalledPackages());
	}

	// =======================================================================
	// getPackage — single lookup
	// =======================================================================

	public function testGetPackage_returnsManifestWhenPresent(): void
	{
		PradoUnit::setStaticProp(TComposer::class, '_packages', [
			'acme/widget' => ['name' => 'acme/widget', 'version' => '2.5'],
		]);

		$this->assertSame(
			['name' => 'acme/widget', 'version' => '2.5'],
			TComposer::getPackage('acme/widget')
		);
	}

	public function testGetPackage_returnsNullWhenAbsent(): void
	{
		PradoUnit::setStaticProp(TComposer::class, '_packages', [
			'acme/widget' => ['name' => 'acme/widget'],
		]);

		$this->assertNull(TComposer::getPackage('ghost/missing'));
	}

	public function testGetPackage_returnsNullFromEmptyManifest(): void
	{
		PradoUnit::setStaticProp(TComposer::class, '_packages', []);
		$this->assertNull(TComposer::getPackage('anything/at-all'));
	}

	// =======================================================================
	// getPackagePath — absolute package path
	// =======================================================================

	public function testGetPackagePath_returnsAbsolutePathForInstalledPackage(): void
	{
		// getPackagePath resolves through Composer's InstalledVersions runtime, so
		// it reports the real on-disk location of an installed package.
		$path = TComposer::getPackagePath('phpunit/phpunit');
		$this->assertNotNull($path);
		$this->assertDirectoryExists($path);
		$this->assertSame(realpath(\Composer\InstalledVersions::getInstallPath('phpunit/phpunit')), $path);
	}

	public function testGetPackagePath_canonicalizesAwayRelativeSegments(): void
	{
		$path = TComposer::getPackagePath('phpunit/phpunit');
		$this->assertStringNotContainsString(DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR, $path);
	}

	public function testGetPackagePath_nullWhenPackageNotInstalled(): void
	{
		$this->assertNull(TComposer::getPackagePath('ghost/definitely-not-installed-xyz'));
	}

	// =======================================================================
	// getInstalledManifestsTime — newest installed.json mtime
	// =======================================================================

	public function testGetInstalledManifestsTime_isPositiveWithRealProject(): void
	{
		// The project's own vendor/composer/installed.json is always registered.
		$this->assertGreaterThan(0, TComposer::getInstalledManifestsTime());
	}

	public function testGetInstalledManifestsTime_returnsNewestManifestMtime(): void
	{
		$vendorDir = $this->makeVendor([['name' => 'acme/x', 'version' => '1']]);
		$future = time() + 100000;
		touch($this->manifestPath($vendorDir), $future);

		// The freshly-touched manifest is the newest across all registered loaders.
		$this->assertSame($future, TComposer::getInstalledManifestsTime());
	}

	public function testGetInstalledManifestsTime_ignoresVendorWithoutManifest(): void
	{
		// A registered vendor with no installed.json must not break the scan.
		$this->makeVendor([], null, false);
		$this->assertGreaterThan(0, TComposer::getInstalledManifestsTime());
	}

	// =======================================================================
	// getExtra — extra field accessor
	// =======================================================================

	private function seedExtraFixture(): void
	{
		PradoUnit::setStaticProp(TComposer::class, '_packages', [
			'acme/full' => [
				'name' => 'acme/full',
				'extra' => [
					'bootstrap' => 'Acme\\Bootstrap',
					'setup' => 'Acme\\Setup',
					'branch-alias' => ['dev-master' => '1.x-dev'],
				],
			],
			'acme/noextra' => ['name' => 'acme/noextra'],
			'acme/scalarextra' => ['name' => 'acme/scalarextra', 'extra' => 'a-string'],
		]);
	}

	public function testGetExtra_nullKeyReturnsWholeExtraArray(): void
	{
		$this->seedExtraFixture();

		$this->assertSame(
			[
				'bootstrap' => 'Acme\\Bootstrap',
				'setup' => 'Acme\\Setup',
				'branch-alias' => ['dev-master' => '1.x-dev'],
			],
			TComposer::getExtra('acme/full')
		);
	}

	public function testGetExtra_namedKeyReturnsThatValue(): void
	{
		$this->seedExtraFixture();
		$this->assertSame('Acme\\Bootstrap', TComposer::getExtra('acme/full', 'bootstrap'));
		$this->assertSame('Acme\\Setup', TComposer::getExtra('acme/full', 'setup'));
	}

	public function testGetExtra_namedKeyReturnsNestedArrayValue(): void
	{
		$this->seedExtraFixture();
		$this->assertSame(['dev-master' => '1.x-dev'], TComposer::getExtra('acme/full', 'branch-alias'));
	}

	public function testGetExtra_absentKeyReturnsNull(): void
	{
		$this->seedExtraFixture();
		$this->assertNull(TComposer::getExtra('acme/full', 'does-not-exist'));
	}

	public function testGetExtra_packageWithoutExtra_nullKeyReturnsNull(): void
	{
		$this->seedExtraFixture();
		$this->assertNull(TComposer::getExtra('acme/noextra'));
	}

	public function testGetExtra_packageWithoutExtra_namedKeyReturnsNull(): void
	{
		$this->seedExtraFixture();
		$this->assertNull(TComposer::getExtra('acme/noextra', 'bootstrap'));
	}

	public function testGetExtra_missingPackage_nullKeyReturnsNull(): void
	{
		$this->seedExtraFixture();
		$this->assertNull(TComposer::getExtra('ghost/missing'));
	}

	public function testGetExtra_missingPackage_namedKeyReturnsNull(): void
	{
		$this->seedExtraFixture();
		$this->assertNull(TComposer::getExtra('ghost/missing', 'bootstrap'));
	}

	public function testGetExtra_scalarExtra_nullKeyReturnsScalar(): void
	{
		$this->seedExtraFixture();
		$this->assertSame('a-string', TComposer::getExtra('acme/scalarextra'));
	}

	public function testGetExtra_scalarExtra_namedKeyReturnsScalar(): void
	{
		// extra is not an array, so the key cannot be indexed; the scalar is returned.
		$this->seedExtraFixture();
		$this->assertSame('a-string', TComposer::getExtra('acme/scalarextra', 'bootstrap'));
	}

	// =======================================================================
	// getPradoExtra — extra.prado sub-array accessor
	// =======================================================================

	private function seedPradoExtraFixture(): void
	{
		PradoUnit::setStaticProp(TComposer::class, '_packages', [
			'acme/nested' => ['name' => 'acme/nested',
				'extra' => ['prado' => ['bootstrap' => 'Acme\\Module', 'config' => 'cfg.xml']]],
			'acme/noprado' => ['name' => 'acme/noprado', 'extra' => ['bootstrap' => 'Legacy\\Module']],
			'acme/noextra' => ['name' => 'acme/noextra'],
		]);
	}

	public function testGetPradoExtra_nullKeyReturnsWholePradoArray(): void
	{
		$this->seedPradoExtraFixture();
		$this->assertSame(
			['bootstrap' => 'Acme\\Module', 'config' => 'cfg.xml'],
			TComposer::getPradoExtra('acme/nested'),
		);
	}

	public function testGetPradoExtra_namedKeyReturnsThatValue(): void
	{
		$this->seedPradoExtraFixture();
		$this->assertSame('Acme\\Module', TComposer::getPradoExtra('acme/nested', 'bootstrap'));
		$this->assertSame('cfg.xml', TComposer::getPradoExtra('acme/nested', 'config'));
	}

	public function testGetPradoExtra_absentKeyReturnsNull(): void
	{
		$this->seedPradoExtraFixture();
		$this->assertNull(TComposer::getPradoExtra('acme/nested', 'missing'));
	}

	public function testGetPradoExtra_noPradoSubArrayReturnsNull(): void
	{
		// extra exists but has no `prado` sub-array — legacy keys are not read here.
		$this->seedPradoExtraFixture();
		$this->assertNull(TComposer::getPradoExtra('acme/noprado'));
		$this->assertNull(TComposer::getPradoExtra('acme/noprado', 'bootstrap'));
	}

	public function testGetPradoExtra_missingPackageReturnsNull(): void
	{
		$this->seedPradoExtraFixture();
		$this->assertNull(TComposer::getPradoExtra('ghost/missing', 'bootstrap'));
	}

	// =======================================================================
	// flushInstalledPackages — clears the in-memory cache
	// =======================================================================

	public function testFlush_clearsStaticCache(): void
	{
		PradoUnit::setStaticProp(TComposer::class, '_packages', ['x' => ['name' => 'x']]);

		TComposer::flushInstalledPackages();

		$this->assertNull(PradoUnit::getStaticProp(TComposer::class, '_packages'));
	}

	public function testFlush_forcesReloadOnNextGet(): void
	{
		PradoUnit::setStaticProp(TComposer::class, '_packages', ['stale/pkg' => ['name' => 'stale/pkg']]);
		$this->makeVendor([['name' => 'reloaded/pkg', 'version' => '1']]);

		TComposer::flushInstalledPackages();
		$packages = TComposer::getInstalledPackages();

		$this->assertArrayNotHasKey('stale/pkg', $packages);
		$this->assertArrayHasKey('reloaded/pkg', $packages);
	}

	// =======================================================================
	// readManifest — encapsulated decode seam
	// =======================================================================

	public function testReadManifest_decodesValidJson(): void
	{
		$file = $this->manifestPath($this->makeVendor([['name' => 'a/b', 'version' => '1']]));

		$manifest = PradoUnit::invoke(TComposer::class, 'readManifest', $file);

		$this->assertSame([['name' => 'a/b', 'version' => '1']], $manifest['packages']);
	}

	public function testReadManifest_malformedJsonThrows(): void
	{
		$file = $this->manifestPath($this->makeVendor([], '{ bad json'));

		$this->expectException(\JsonException::class);
		PradoUnit::invoke(TComposer::class, 'readManifest', $file);
	}

	// =======================================================================
	// newFileCacheDependency — encapsulated dependency seam
	// =======================================================================

	public function testNewFileCacheDependency_returnsFileDependency(): void
	{
		$file = $this->manifestPath($this->makeVendor([]));

		$dep = PradoUnit::invoke(TComposer::class, 'newFileCacheDependency', $file);

		$this->assertInstanceOf(\Prado\Caching\TFileCacheDependency::class, $dep);
	}

	// =======================================================================
	// Seam overrides via subclass — readManifest / newFileCacheDependency
	// =======================================================================

	private function invokeHarnessLoad(): array
	{
		return $this->invokeLoad(TTestComposer::class);
	}

	public function testSeam_readManifestOverrideReplacesParsing(): void
	{
		// A real file must exist so the is_file gate passes; its content is
		// ignored because the override supplies the manifest.
		$this->makeVendor([['name' => 'on/disk', 'version' => 'x']]);
		TTestComposer::$manifestOverride = ['packages' => [['name' => 'injected/pkg', 'version' => '7']]];

		$packages = $this->invokeHarnessLoad();

		$this->assertArrayHasKey('injected/pkg', $packages);
		$this->assertArrayNotHasKey('on/disk', $packages);
		$this->assertNotEmpty(TTestComposer::$readFiles, 'readManifest seam must be invoked');
	}

	public function testSeam_nullDependencyAddsNoFileDependency(): void
	{
		$cache = new TTestCacheStub();
		$cache->getReturn = false;
		Prado::getApplication()->setCache($cache);

		$this->makeVendor([['name' => 'dep/none', 'version' => '1']]);
		TTestComposer::$nullDependency = true;

		$this->invokeHarnessLoad();

		$this->assertNotEmpty(TTestComposer::$dependencyFiles, 'dependency seam must be consulted');
		$this->assertSame(1, $cache->getCollectedCallCount('set'));
		$dependency = $cache->getCollectedCalls('set')[0][3];
		$this->assertInstanceOf(\Prado\Caching\TChainedCacheDependency::class, $dependency);
		$this->assertSame(
			0,
			$dependency->getDependencies()->getCount(),
			'A null seam result must add no file dependency to the chain'
		);
	}

	public function testSeam_defaultDependencyPopulatesChain(): void
	{
		$cache = new TTestCacheStub();
		$cache->getReturn = false;
		Prado::getApplication()->setCache($cache);

		$this->makeVendor([['name' => 'dep/some', 'version' => '1']]);
		// nullDependency stays false: parent seam yields a real file dependency.

		$this->invokeHarnessLoad();

		$dependency = $cache->getCollectedCalls('set')[0][3];
		$this->assertGreaterThan(
			0,
			$dependency->getDependencies()->getCount(),
			'Default seam must contribute a file dependency per manifest'
		);
	}
}
