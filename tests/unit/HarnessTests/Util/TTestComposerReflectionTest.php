<?php

/**
 * TTestComposerReflectionTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Util\TComposerReflection;

/**
 * Tests for {@see TTestComposerReflection}, the seam subclass of {@see \Prado\Util\TComposerReflection}.
 *
 * Verifies the two overridden filesystem seams and the configuration helpers:
 *
 *  - {@see TTestComposerReflection::readManifest()} records the file, returns
 *    {@see TTestComposerReflection::$manifestOverride} when set, and otherwise delegates to
 *    the parent decode.
 *  - {@see TTestComposerReflection::newFileCacheDependency()} records the file and returns
 *    null when {@see TTestComposerReflection::$nullDependency} is set, otherwise the parent
 *    file dependency.
 *  - {@see TTestComposerReflection::reset()} clears all configuration and recorded calls.
 *
 * The {@see TComposerReflectionTestTrait} supplies the temp-vendor builder and cleanup.
 *
 * @package System.Harness
 */
class TTestComposerReflectionTest extends PHPUnit\Framework\TestCase
{
	use TComposerReflectionTestTrait;

	public function testIsTComposerReflectionSubclass(): void
	{
		$this->assertTrue(is_subclass_of(TTestComposerReflection::class, TComposerReflection::class));
	}

	// -----------------------------------------------------------------------
	// readManifest seam
	// -----------------------------------------------------------------------

	public function testReadManifest_recordsFileAndReturnsOverride(): void
	{
		$file = $this->manifestPath($this->makeVendor([['name' => 'on/disk']]));
		TTestComposerReflection::$manifestOverride = ['packages' => [['name' => 'injected/pkg']]];

		$manifest = PradoUnit::invoke(TTestComposerReflection::class, 'readManifest', $file);

		$this->assertSame(['packages' => [['name' => 'injected/pkg']]], $manifest);
		$this->assertSame([$file], TTestComposerReflection::$readFiles);
	}

	public function testReadManifest_delegatesToParentWhenNoOverride(): void
	{
		$file = $this->manifestPath($this->makeVendor([['name' => 'real/pkg', 'version' => '3']]));

		$manifest = PradoUnit::invoke(TTestComposerReflection::class, 'readManifest', $file);

		$this->assertSame([['name' => 'real/pkg', 'version' => '3']], $manifest['packages']);
		$this->assertContains($file, TTestComposerReflection::$readFiles);
	}

	public function testReadManifest_parentMalformedJsonThrows(): void
	{
		$file = $this->manifestPath($this->makeVendor([], '{ broken'));

		$this->expectException(\JsonException::class);
		PradoUnit::invoke(TTestComposerReflection::class, 'readManifest', $file);
	}

	// -----------------------------------------------------------------------
	// newFileCacheDependency seam
	// -----------------------------------------------------------------------

	public function testNewFileCacheDependency_recordsFileAndReturnsNullWhenConfigured(): void
	{
		$file = $this->manifestPath($this->makeVendor([]));
		TTestComposerReflection::$nullDependency = true;

		$dep = PradoUnit::invoke(TTestComposerReflection::class, 'newFileCacheDependency', $file);

		$this->assertNull($dep);
		$this->assertSame([$file], TTestComposerReflection::$dependencyFiles);
	}

	public function testNewFileCacheDependency_delegatesToParentByDefault(): void
	{
		$file = $this->manifestPath($this->makeVendor([]));

		$dep = PradoUnit::invoke(TTestComposerReflection::class, 'newFileCacheDependency', $file);

		$this->assertInstanceOf(\Prado\Caching\TFileCacheDependency::class, $dep);
		$this->assertContains($file, TTestComposerReflection::$dependencyFiles);
	}

	// -----------------------------------------------------------------------
	// reset
	// -----------------------------------------------------------------------

	public function testReset_clearsAllConfigurationAndRecordedCalls(): void
	{
		TTestComposerReflection::$manifestOverride = ['packages' => []];
		TTestComposerReflection::$nullDependency = true;
		TTestComposerReflection::$readFiles = ['a'];
		TTestComposerReflection::$dependencyFiles = ['b'];

		TTestComposerReflection::reset();

		$this->assertNull(TTestComposerReflection::$manifestOverride);
		$this->assertFalse(TTestComposerReflection::$nullDependency);
		$this->assertSame([], TTestComposerReflection::$readFiles);
		$this->assertSame([], TTestComposerReflection::$dependencyFiles);
	}
}
