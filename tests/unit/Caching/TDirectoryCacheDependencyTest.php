<?php

/**
 * TDirectoryCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TDirectoryCacheDependency;
use Prado\Caching\TCacheDependency;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * Unit tests for {@see \Prado\Caching\TDirectoryCacheDependency}.
 *
 * Each test uses an isolated subdirectory under `temp/` so tests do not
 * interfere with one another.  Directories and files created during a test
 * are cleaned up in `tearDown()`.
 */
class TDirectoryCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	private string $_tempBase;
	private array $_createdPaths = [];

	protected function setUp(): void
	{
		$this->_tempBase = __DIR__ . '/temp';
		$this->_createdPaths = [];
	}

	protected function tearDown(): void
	{
		// Remove in reverse so files are deleted before their parent directories.
		foreach (array_reverse($this->_createdPaths) as $path) {
			if (is_file($path)) {
				@unlink($path);
			} elseif (is_dir($path)) {
				@rmdir($path);
			}
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function makeDir(string $name): string
	{
		$path = $this->_tempBase . '/' . $name;
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}
		$this->_createdPaths[] = $path;
		return $path;
	}

	private function makeFile(string $dirPath, string $name, string $content = 'x'): string
	{
		$path = $dirPath . '/' . $name;
		file_put_contents($path, $content);
		$this->_createdPaths[] = $path;
		clearstatcache();
		return $path;
	}

	// -------------------------------------------------------------------------
	// Construction and directory resolution
	// -------------------------------------------------------------------------

	public function testConstructorResolvesRealPath(): void
	{
		$dir = $this->makeDir('dir_resolve');
		$dep = new TDirectoryCacheDependency($dir);
		$this->assertSame(realpath($dir), $dep->getDirectory());
	}

	public function testConstructorWithInvalidDirectoryThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		new TDirectoryCacheDependency($this->_tempBase . '/does_not_exist_xyz');
	}

	public function testConstructorWithFilePath(): void
	{
		$file = $this->makeFile($this->_tempBase, 'TDirCacheDep_not_a_dir.txt');
		$this->expectException(TInvalidDataValueException::class);
		new TDirectoryCacheDependency($file);
	}

	// -------------------------------------------------------------------------
	// RecursiveCheck and RecursiveLevel
	// -------------------------------------------------------------------------

	public function testRecursiveCheckDefaultsToTrue(): void
	{
		$dir = $this->makeDir('dir_rc_default');
		$dep = new TDirectoryCacheDependency($dir);
		$this->assertTrue($dep->getRecursiveCheck());
	}

	public function testSetRecursiveCheckFalse(): void
	{
		$dir = $this->makeDir('dir_rc_false');
		$dep = new TDirectoryCacheDependency($dir);
		$dep->setRecursiveCheck(false);
		$this->assertFalse($dep->getRecursiveCheck());
	}

	public function testSetRecursiveCheckTrue(): void
	{
		$dir = $this->makeDir('dir_rc_true');
		$dep = new TDirectoryCacheDependency($dir);
		$dep->setRecursiveCheck(false);
		$dep->setRecursiveCheck(true);
		$this->assertTrue($dep->getRecursiveCheck());
	}

	public function testRecursiveLevelDefaultsToMinusOne(): void
	{
		$dir = $this->makeDir('dir_rl_default');
		$dep = new TDirectoryCacheDependency($dir);
		$this->assertSame(-1, $dep->getRecursiveLevel());
	}

	public function testSetRecursiveLevel(): void
	{
		$dir = $this->makeDir('dir_rl_set');
		$dep = new TDirectoryCacheDependency($dir);
		$dep->setRecursiveLevel(3);
		$this->assertSame(3, $dep->getRecursiveLevel());
	}

	public function testSetRecursiveLevelZero(): void
	{
		$dir = $this->makeDir('dir_rl_zero');
		$dep = new TDirectoryCacheDependency($dir);
		$dep->setRecursiveLevel(0);
		$this->assertSame(0, $dep->getRecursiveLevel());
	}

	// -------------------------------------------------------------------------
	// getHasChanged — basic
	// -------------------------------------------------------------------------

	public function testGetHasChangedFalseWhenUnchanged(): void
	{
		$dir = $this->makeDir('dir_unchanged');
		$this->makeFile($dir, 'a.txt');

		$dep = new TDirectoryCacheDependency($dir);
		clearstatcache();
		$this->assertFalse($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenFileMtimeChanges(): void
	{
		$dir = $this->makeDir('dir_mtime');
		$file = $this->makeFile($dir, 'b.txt');
		$originalMtime = filemtime($file);

		$dep = new TDirectoryCacheDependency($dir);

		touch($file, $originalMtime - 10);
		clearstatcache();

		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenFileAdded(): void
	{
		$dir = $this->makeDir('dir_add');
		$this->makeFile($dir, 'existing.txt');

		$dep = new TDirectoryCacheDependency($dir);

		// Add a new file after dependency was created.
		$newFile = $dir . '/new_file.txt';
		file_put_contents($newFile, 'new');
		$this->_createdPaths[] = $newFile;
		clearstatcache();

		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedTrueWhenFileRemoved(): void
	{
		$dir = $this->makeDir('dir_remove');
		$file = $this->makeFile($dir, 'to_remove.txt');

		$dep = new TDirectoryCacheDependency($dir);

		unlink($file);
		// Remove from cleanup list since it's gone.
		$this->_createdPaths = array_filter($this->_createdPaths, fn($p) => $p !== $file);
		clearstatcache();

		$this->assertTrue($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// getHasChanged — recursive behaviour
	// -------------------------------------------------------------------------

	public function testGetHasChangedTrueForDeepFileWhenFullyRecursive(): void
	{
		$dir   = $this->makeDir('dir_deep');
		$sub   = $this->makeDir('dir_deep/sub');
		$file  = $this->makeFile($sub, 'deep.txt');
		$mtime = filemtime($file);

		$dep = new TDirectoryCacheDependency($dir);

		touch($file, $mtime - 10);
		clearstatcache();

		$this->assertTrue($dep->getHasChanged());
	}

	public function testGetHasChangedFalseForSubdirFileWhenNotRecursive(): void
	{
		$dir  = $this->makeDir('dir_nonrecursive');
		$sub  = $this->makeDir('dir_nonrecursive/sub');
		$file = $this->makeFile($sub, 'hidden.txt');
		$mtime = filemtime($file);

		$dep = new TDirectoryCacheDependency($dir);
		$dep->setRecursiveCheck(false);

		touch($file, $mtime - 10);
		clearstatcache();

		// Subdirectory is excluded → dependency has not changed.
		$this->assertFalse($dep->getHasChanged());
	}

	public function testRecursiveLevelZeroIgnoresSubdirChanges(): void
	{
		$dir  = $this->makeDir('dir_level0');
		$sub  = $this->makeDir('dir_level0/sub');
		$file = $this->makeFile($sub, 'nested.txt');
		$mtime = filemtime($file);

		$dep = new TDirectoryCacheDependency($dir);
		$dep->setRecursiveLevel(0);

		touch($file, $mtime - 10);
		clearstatcache();

		// Level 0 → only top-level files are checked.
		$this->assertFalse($dep->getHasChanged());
	}

	public function testRecursiveLevelOneSeesFirstLevelOnly(): void
	{
		$dir   = $this->makeDir('dir_level1');
		$sub1  = $this->makeDir('dir_level1/sub1');
		$sub2  = $this->makeDir('dir_level1/sub1/sub2');
		$fileL1 = $this->makeFile($sub1, 'level1.txt');
		$fileL2 = $this->makeFile($sub2, 'level2.txt');

		$dep = new TDirectoryCacheDependency($dir);
		$dep->setRecursiveLevel(1);

		// Snapshot taken with RecursiveLevel=1 only includes files up to depth 1.
		clearstatcache();
		$this->assertFalse($dep->getHasChanged());

		// Changing a level-2 file should not be detected.
		touch($fileL2, filemtime($fileL2) - 10);
		clearstatcache();
		$this->assertFalse($dep->getHasChanged());

		// Changing a level-1 file should be detected.
		touch($fileL1, filemtime($fileL1) - 10);
		clearstatcache();
		$this->assertTrue($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// validateFile / validateDirectory overrides
	// -------------------------------------------------------------------------

	public function testValidateFileCanExcludeFiles(): void
	{
		$dir  = $this->makeDir('dir_validatefile');
		$kept = $this->makeFile($dir, 'keep.txt');
		$skip = $this->makeFile($dir, 'skip.log');

		$dep = new class($dir) extends TDirectoryCacheDependency {
			protected function validateFile(string $fileName): bool
			{
				return str_ends_with($fileName, '.txt');
			}
		};

		// Change only the excluded file — dependency must stay false.
		touch($skip, filemtime($skip) - 10);
		clearstatcache();
		$this->assertFalse($dep->getHasChanged());

		// Change the included file — dependency must become true.
		touch($kept, filemtime($kept) - 10);
		clearstatcache();
		$this->assertTrue($dep->getHasChanged());
	}

	public function testValidateDirectoryCanExcludeSubdirs(): void
	{
		$dir     = $this->makeDir('dir_validatedir');
		$included = $this->makeDir('dir_validatedir/included');
		$excluded = $this->makeDir('dir_validatedir/excluded');
		$fileIn  = $this->makeFile($included, 'a.txt');
		$fileOut = $this->makeFile($excluded, 'b.txt');

		$dep = new class($dir) extends TDirectoryCacheDependency {
			protected function validateDirectory(string $directory): bool
			{
				return !str_ends_with($directory, 'excluded');
			}
		};

		// Change only the excluded subdirectory's file — no change expected.
		touch($fileOut, filemtime($fileOut) - 10);
		clearstatcache();
		$this->assertFalse($dep->getHasChanged());

		// Change the included subdirectory's file — change expected.
		touch($fileIn, filemtime($fileIn) - 10);
		clearstatcache();
		$this->assertTrue($dep->getHasChanged());
	}

	// -------------------------------------------------------------------------
	// Serialization round-trip
	// -------------------------------------------------------------------------

	public function testSerializationPreservesState(): void
	{
		$dir  = $this->makeDir('dir_serial');
		$this->makeFile($dir, 'serial.txt');

		$dep      = new TDirectoryCacheDependency($dir);
		$restored = unserialize(serialize($dep));

		$this->assertSame($dep->getDirectory(), $restored->getDirectory());
		$this->assertSame($dep->getRecursiveCheck(), $restored->getRecursiveCheck());
		$this->assertSame($dep->getRecursiveLevel(), $restored->getRecursiveLevel());
	}

	public function testGetHasChangedAfterSerializationWhenUnchanged(): void
	{
		$dir = $this->makeDir('dir_serial_ok');
		$this->makeFile($dir, 'stable.txt');

		$dep = new TDirectoryCacheDependency($dir);
		$restored = unserialize(serialize($dep));

		clearstatcache();
		$this->assertFalse($restored->getHasChanged());
	}

	public function testGetHasChangedAfterSerializationWhenChanged(): void
	{
		$dir  = $this->makeDir('dir_serial_changed');
		$file = $this->makeFile($dir, 'will_change.txt');

		$dep = new TDirectoryCacheDependency($dir);
		$serialized = serialize($dep);

		touch($file, filemtime($file) - 10);
		clearstatcache();

		$restored = unserialize($serialized);
		$this->assertTrue($restored->getHasChanged());
	}
}
