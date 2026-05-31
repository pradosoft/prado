<?php

/**
 * TTestComposerTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Util\TComposer;

/**
 * Tests for {@see TTestComposer}, the seam subclass of {@see \Prado\Util\TComposer}.
 *
 * Verifies the two overridden filesystem seams and the configuration helpers:
 *
 *  - {@see TTestComposer::readManifest()} records the file, returns
 *    {@see TTestComposer::$manifestOverride} when set, and otherwise delegates to
 *    the parent decode.
 *  - {@see TTestComposer::newFileCacheDependency()} records the file and returns
 *    null when {@see TTestComposer::$nullDependency} is set, otherwise the parent
 *    file dependency.
 *  - {@see TTestComposer::reset()} clears all configuration and recorded calls.
 *
 * The {@see TComposerTestTrait} supplies the temp-vendor builder and cleanup.
 *
 * @package System.Harness
 */
class TTestComposerTest extends PHPUnit\Framework\TestCase
{
	use TComposerTestTrait;

	public function testIsTComposerSubclass(): void
	{
		$this->assertTrue(is_subclass_of(TTestComposer::class, TComposer::class));
	}

	// -----------------------------------------------------------------------
	// readManifest seam
	// -----------------------------------------------------------------------

	public function testReadManifest_recordsFileAndReturnsOverride(): void
	{
		$file = $this->manifestPath($this->makeVendor([['name' => 'on/disk']]));
		TTestComposer::$manifestOverride = ['packages' => [['name' => 'injected/pkg']]];

		$manifest = PradoUnit::invoke(TTestComposer::class, 'readManifest', $file);

		$this->assertSame(['packages' => [['name' => 'injected/pkg']]], $manifest);
		$this->assertSame([$file], TTestComposer::$readFiles);
	}

	public function testReadManifest_delegatesToParentWhenNoOverride(): void
	{
		$file = $this->manifestPath($this->makeVendor([['name' => 'real/pkg', 'version' => '3']]));

		$manifest = PradoUnit::invoke(TTestComposer::class, 'readManifest', $file);

		$this->assertSame([['name' => 'real/pkg', 'version' => '3']], $manifest['packages']);
		$this->assertContains($file, TTestComposer::$readFiles);
	}

	public function testReadManifest_parentMalformedJsonThrows(): void
	{
		$file = $this->manifestPath($this->makeVendor([], '{ broken'));

		$this->expectException(\JsonException::class);
		PradoUnit::invoke(TTestComposer::class, 'readManifest', $file);
	}

	// -----------------------------------------------------------------------
	// newFileCacheDependency seam
	// -----------------------------------------------------------------------

	public function testNewFileCacheDependency_recordsFileAndReturnsNullWhenConfigured(): void
	{
		$file = $this->manifestPath($this->makeVendor([]));
		TTestComposer::$nullDependency = true;

		$dep = PradoUnit::invoke(TTestComposer::class, 'newFileCacheDependency', $file);

		$this->assertNull($dep);
		$this->assertSame([$file], TTestComposer::$dependencyFiles);
	}

	public function testNewFileCacheDependency_delegatesToParentByDefault(): void
	{
		$file = $this->manifestPath($this->makeVendor([]));

		$dep = PradoUnit::invoke(TTestComposer::class, 'newFileCacheDependency', $file);

		$this->assertInstanceOf(\Prado\Caching\TFileCacheDependency::class, $dep);
		$this->assertContains($file, TTestComposer::$dependencyFiles);
	}

	// -----------------------------------------------------------------------
	// reset
	// -----------------------------------------------------------------------

	public function testReset_clearsAllConfigurationAndRecordedCalls(): void
	{
		TTestComposer::$manifestOverride = ['packages' => []];
		TTestComposer::$nullDependency = true;
		TTestComposer::$readFiles = ['a'];
		TTestComposer::$dependencyFiles = ['b'];

		TTestComposer::reset();

		$this->assertNull(TTestComposer::$manifestOverride);
		$this->assertFalse(TTestComposer::$nullDependency);
		$this->assertSame([], TTestComposer::$readFiles);
		$this->assertSame([], TTestComposer::$dependencyFiles);
	}
}
