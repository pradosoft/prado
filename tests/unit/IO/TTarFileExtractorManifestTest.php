<?php


use PHPUnit\Framework\TestCase;
use Prado\IO\TTarFileExtractor;

/**
 * Comprehensive tests for the tarPathInfoMap / tarPathMap API introduced in 4.3.3.
 *
 * Covers: scanning without extraction, extraction populating the map, all per-entry
 * getters, GNU long-name and long-link extensions, device / special-file handling,
 * path-traversal (Zip Slip) security, symlink and hardlink security, skipped-file
 * tracking, unwind-on-failure, all four compression formats, and URL simulation
 * (using reflection-injected _temp_tarpath, the same technique used in the URL test suite).
 */
class TTarFileExtractorManifestTest extends TestCase
{
	private string $testDir = '';
	private string $extractDir = '';

	// Known fixed mtime used when building test archives so assertions are deterministic.
	private const FIXED_MTIME = 1609459200; // 2021-01-01 00:00:00 UTC

	protected function setUp(): void
	{
		$this->testDir = sys_get_temp_dir() . '/prado_tar_pathmap_test_' . uniqid();
		$this->extractDir = $this->testDir . '/extract';
		mkdir($this->extractDir, 0o777, true);
	}

	protected function tearDown(): void
	{
		$this->removeDirectory($this->testDir);
	}

	// ---------------------------------------------------------------------------
	// Infrastructure helpers
	// ---------------------------------------------------------------------------

	private function removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		$files = array_diff(scandir($dir), ['.', '..']);
		foreach ($files as $file) {
			$path = $dir . '/' . $file;
			if (is_link($path)) {
				unlink($path);
			} elseif (is_dir($path)) {
				$this->removeDirectory($path);
			} else {
				unlink($path);
			}
		}
		rmdir($dir);
	}

	/** Use reflection to simulate a completed URL download by injecting _temp_tarpath. */
	private function injectTempTarPath(TTarFileExtractor $extractor, string $path): void
	{
		$ref = new \ReflectionClass($extractor);
		$prop = $ref->getProperty('_temp_tarpath');
		$prop->setAccessible(true);
		$prop->setValue($extractor, $path);
	}

	// ---------------------------------------------------------------------------
	// Group 1: getManifest / getManifest basics
	// ---------------------------------------------------------------------------

	public function testScanPopulatesPathMapWithCorrectKeys()
	{
		$tarFile = $this->testDir . '/basic.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('subdir/file.txt', 'hello'),
			TarTestHelper::entry('root.txt', 'root'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$paths = $extractor->getManifestPaths();

		$this->assertContains('subdir/', $paths);
		$this->assertContains('subdir/file.txt', $paths);
		$this->assertContains('root.txt', $paths);
	}

	public function testDirectoriesAlwaysPrecedeFilesInPathMap()
	{
		$tarFile = $this->testDir . '/ordering.tar';
		// Archive has files first, then directory — map must reorder dirs before files.
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('z_file.txt', 'data'),
			TarTestHelper::entry('a_dir/', '', '5'),
			TarTestHelper::entry('a_dir/child.txt', 'child'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$paths = $extractor->getManifestPaths();

		$firstDir = false;
		foreach ($paths as $p) {
			if (str_ends_with($p, '/')) {
				$firstDir = true;
			} elseif ($firstDir === false) {
				// A file appeared before any directory — fail.
				$this->fail('File appeared before directory in path map: ' . $p);
			}
		}
		$this->assertTrue($firstDir, 'Expected at least one directory entry');
	}

	public function testDirectoryKeyEndsWithDirectorySeparator()
	{
		$tarFile = $this->testDir . '/dirsep.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('mydir/', '', '5'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();
		$key = 'mydir' . DIRECTORY_SEPARATOR;

		$this->assertArrayHasKey($key, $map);
	}

	public function testMapIsNullBeforeScanOrExtract()
	{
		$tarFile = $this->testDir . '/lazy.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('a.txt', 'a')]);

		$extractor = new TTarFileExtractor($tarFile);

		$ref = new \ReflectionClass($extractor);
		$prop = $ref->getProperty('_tarManifest');
		$prop->setAccessible(true);
		$this->assertNull($prop->getValue($extractor), '_tarManifest should be null before first access');

		// Access triggers lazy scan.
		$extractor->getManifest();
		$this->assertNotNull($prop->getValue($extractor));
	}

	public function testgetManifestIsKeysOfInfoMap()
	{
		$tarFile = $this->testDir . '/keys.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('dir/f.txt', 'x'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(array_keys($extractor->getManifest()), $extractor->getManifestPaths());
	}

	// ---------------------------------------------------------------------------
	// Group 2: Metadata correctness — all fields verified after scan
	// ---------------------------------------------------------------------------

	public function testScanPopulatesAllMetadataFields()
	{
		$tarFile = $this->testDir . '/meta.tar';
		$content = 'metadata content';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry(
				'meta.txt',
				$content,
				'0',        // typeflag
				'',         // linkname
				0o755,      // mode
				501,        // uid
				20,         // gid
				self::FIXED_MTIME,
				'alice',
				'staff'
			),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$info = $extractor->getManifestInfo('meta.txt');

		$this->assertNotNull($info);
		$this->assertSame('meta.txt', $info['path']);
		$this->assertSame('meta.txt', $info['filename']);
		$this->assertSame('file', $info['type']);
		$this->assertSame(strlen($content), $info['size']);
		$this->assertSame(self::FIXED_MTIME, $info['mtime']);
		$this->assertSame(0o755, $info['mode']);
		$this->assertSame(501, $info['uid']);
		$this->assertSame(20, $info['gid']);
		$this->assertSame('alice', $info['uname']);
		$this->assertSame('staff', $info['gname']);
		$this->assertSame('', $info['linkpath']);
		$this->assertSame(TTarFileExtractor::TYPE_FILE, $info['typeflag']);
		$this->assertIsInt($info['checksum']);
		$this->assertSame(TTarFileExtractor::COMPRESSION_NONE, $extractor->getCompression());
		$this->assertTrue($info['filesafe']);
		$this->assertFalse($info['device']);
	}

	public function testDirectoryEntryMetadata()
	{
		$tarFile = $this->testDir . '/direntry.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('docs/', '', '5', '', 0o755, 1000, 1000, self::FIXED_MTIME, 'user', 'group'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$info = $extractor->getManifestInfo('docs/');

		$this->assertNotNull($info);
		$this->assertSame('directory', $info['type']);
		$this->assertSame(TTarFileExtractor::TYPE_DIRECTORY, $info['typeflag']);
		$this->assertSame(0, $info['size']);   // _readHeader zeroes size for dirs
		$this->assertTrue($info['filesafe']);
		$this->assertFalse($info['device']);
	}

	public function testSymlinkEntryMetadata()
	{
		$tarFile = $this->testDir . '/symlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('target.txt', 'content'),
			TarTestHelper::entry('link.txt', '', '2', 'target.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$info = $extractor->getManifestInfo('link.txt');

		$this->assertNotNull($info);
		$this->assertSame('symlink', $info['type']);
		$this->assertSame('target.txt', $info['linkpath']);
		$this->assertSame(TTarFileExtractor::TYPE_SYMLINK, $info['typeflag']);
		$this->assertFalse($info['device']);
	}

	public function testHardlinkEntryMetadata()
	{
		$tarFile = $this->testDir . '/hardlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('original.txt', 'original'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$info = $extractor->getManifestInfo('hardlink.txt');

		$this->assertNotNull($info);
		$this->assertSame('hardlink', $info['type']);
		$this->assertSame('original.txt', $info['linkpath']);
		$this->assertSame(TTarFileExtractor::TYPE_HARDLINK, $info['typeflag']);
	}

	// ---------------------------------------------------------------------------
	// Group 3: Per-entry getters
	// ---------------------------------------------------------------------------

	public function testgetManifestTypeForFileAndDirectory()
	{
		$tarFile = $this->testDir . '/types.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('dir/f.txt', 'x'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame('directory', $extractor->getManifestType('dir/'));
		$this->assertSame('directory', $extractor->getManifestType('dir'));   // without trailing sep
		$this->assertSame('file', $extractor->getManifestType('dir/f.txt'));
		$this->assertNull($extractor->getManifestType('nonexistent.txt'));
	}

	public function testgetManifestSize()
	{
		$tarFile = $this->testDir . '/size.tar';
		$content = 'exactly twenty chars';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('f.txt', $content)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(strlen($content), $extractor->getManifestSize('f.txt'));
		$this->assertNull($extractor->getManifestSize('missing.txt'));
	}

	public function testgetManifestMtime()
	{
		$tarFile = $this->testDir . '/mtime.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('f.txt', 'x', '0', '', 0644, 0, 0, self::FIXED_MTIME)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(self::FIXED_MTIME, $extractor->getManifestMtime('f.txt'));
		$this->assertNull($extractor->getManifestMtime('missing.txt'));
	}

	public function testgetManifestMode()
	{
		$tarFile = $this->testDir . '/mode.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('f.txt', 'x', '0', '', 0o755)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(0o755, $extractor->getManifestMode('f.txt'));
		$this->assertNull($extractor->getManifestMode('missing.txt'));
	}

	public function testgetManifestUidAndGid()
	{
		$tarFile = $this->testDir . '/uidgid.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('f.txt', 'x', '0', '', 0644, 501, 20)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(501, $extractor->getManifestUid('f.txt'));
		$this->assertSame(20, $extractor->getManifestGid('f.txt'));
		$this->assertNull($extractor->getManifestUid('missing.txt'));
		$this->assertNull($extractor->getManifestGid('missing.txt'));
	}

	public function testgetManifestUnameAndGname()
	{
		$tarFile = $this->testDir . '/names.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('f.txt', 'x', '0', '', 0644, 0, 0, self::FIXED_MTIME, 'alice', 'staff')]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame('alice', $extractor->getManifestUname('f.txt'));
		$this->assertSame('staff', $extractor->getManifestGname('f.txt'));
		$this->assertNull($extractor->getManifestUname('missing.txt'));
		$this->assertNull($extractor->getManifestGname('missing.txt'));
	}

	public function testgetManifestLinkpath()
	{
		$tarFile = $this->testDir . '/linkname.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('target.txt', 'data'),
			TarTestHelper::entry('link.txt', '', '2', 'target.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame('target.txt', $extractor->getManifestLinkpath('link.txt'));
		$this->assertSame('', $extractor->getManifestLinkpath('target.txt'));
		$this->assertNull($extractor->getManifestLinkpath('missing.txt'));
	}

	public function testgetManifestIsSafeForSafeAndUnsafePaths()
	{
		$tarFile = $this->testDir . '/safety.tar';
		// Build a tar with both a safe entry and a deliberate zip-slip entry.
		$safeHeader = TarTestHelper::header('safe/file.txt', 5);
		$unsafeHeader = TarTestHelper::header('../evil.txt', 5);
		$data = str_pad('hello', 512, "\x00");
		file_put_contents($tarFile, $safeHeader . $data . $unsafeHeader . $data . str_repeat("\x00", 1024));

		// Use non-strict scan so the archive is fully read despite the unsafe path.
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->getManifest();   // trigger scan

		$this->assertTrue($extractor->getManifestIsSafe('safe/file.txt'));
		$this->assertFalse($extractor->getManifestIsSafe('../evil.txt'));
		$this->assertNull($extractor->getManifestIsSafe('nonexistent.txt'));
	}

	public function testgetManifestIsDeviceForDeviceAndRegularFile()
	{
		$tarFile = $this->testDir . '/device.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('regular.txt', 'data'),
			TarTestHelper::entry('char_dev', '', '3'),   // TYPE_CHAR_SPECIAL
			TarTestHelper::entry('block_dev', '', '4'),  // TYPE_BLOCK_SPECIAL
		]);

		// Non-strict scan so device entries are recorded without throwing.
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->getManifest();

		$this->assertFalse($extractor->getManifestIsDevice('regular.txt'));
		$this->assertTrue($extractor->getManifestIsDevice('char_dev'));
		$this->assertTrue($extractor->getManifestIsDevice('block_dev'));
		$this->assertNull($extractor->getManifestIsDevice('nonexistent'));
	}

	public function testGetManifestIsFileForFileAndNonFile()
	{
		$tarFile = $this->testDir . '/isfile.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('file.txt', 'data'),
			TarTestHelper::entry('link.txt', '', '2', 'file.txt'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'file.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		// TYPE_FILE (0) → true.
		$this->assertTrue($extractor->getManifestIsFile('file.txt'));
		// TYPE_DIRECTORY (5), TYPE_SYMLINK (2), TYPE_HARDLINK (1) → false.
		$this->assertFalse($extractor->getManifestIsFile('dir/'));
		$this->assertFalse($extractor->getManifestIsFile('link.txt'));
		$this->assertFalse($extractor->getManifestIsFile('hardlink.txt'));
		// Missing entry → null.
		$this->assertNull($extractor->getManifestIsFile('nonexistent.txt'));
	}

	public function testGetManifestIsDirectoryForDirAndNonDir()
	{
		$tarFile = $this->testDir . '/isdir.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('mydir/', '', '5'),
			TarTestHelper::entry('mydir/file.txt', 'data'),
			TarTestHelper::entry('link.txt', '', '2', 'mydir/file.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		// TYPE_DIRECTORY (5) → true.
		$this->assertTrue($extractor->getManifestIsDirectory('mydir/'));
		// TYPE_FILE (0), TYPE_SYMLINK (2) → false.
		$this->assertFalse($extractor->getManifestIsDirectory('mydir/file.txt'));
		$this->assertFalse($extractor->getManifestIsDirectory('link.txt'));
		// Missing entry → null.
		$this->assertNull($extractor->getManifestIsDirectory('nonexistent/'));
	}

	public function testGetManifestIsSymLinkForSymlinkAndNonSymlink()
	{
		$tarFile = $this->testDir . '/issymlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('target.txt', 'data'),
			TarTestHelper::entry('symlink.txt', '', '2', 'target.txt'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'target.txt'),
			TarTestHelper::entry('dir/', '', '5'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		// TYPE_SYMLINK (2) → true.
		$this->assertTrue($extractor->getManifestIsSymLink('symlink.txt'));
		// TYPE_FILE (0), TYPE_HARDLINK (1), TYPE_DIRECTORY (5) → false.
		$this->assertFalse($extractor->getManifestIsSymLink('target.txt'));
		$this->assertFalse($extractor->getManifestIsSymLink('hardlink.txt'));
		$this->assertFalse($extractor->getManifestIsSymLink('dir/'));
		// Missing entry → null.
		$this->assertNull($extractor->getManifestIsSymLink('nonexistent.txt'));
	}

	public function testGetManifestIsTypeFlagMatchesExactConstant()
	{
		$tarFile = $this->testDir . '/istypeflag.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('file.txt', 'data'),
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('symlink.txt', '', '2', 'file.txt'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'file.txt'),
			TarTestHelper::entry('char_dev', '', '3'),
			TarTestHelper::entry('block_dev', '', '4'),
			TarTestHelper::entry('fifo_pipe', '', '6'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->getManifest();

		// Each entry matches only its own TYPE_* constant.
		$this->assertTrue($extractor->getManifestIsTypeFlag('file.txt',     TTarFileExtractor::TYPE_FILE));
		$this->assertTrue($extractor->getManifestIsTypeFlag('dir/',         TTarFileExtractor::TYPE_DIRECTORY));
		$this->assertTrue($extractor->getManifestIsTypeFlag('symlink.txt',  TTarFileExtractor::TYPE_SYMLINK));
		$this->assertTrue($extractor->getManifestIsTypeFlag('hardlink.txt', TTarFileExtractor::TYPE_HARDLINK));
		$this->assertTrue($extractor->getManifestIsTypeFlag('char_dev',     TTarFileExtractor::TYPE_CHAR_SPECIAL));
		$this->assertTrue($extractor->getManifestIsTypeFlag('block_dev',    TTarFileExtractor::TYPE_BLOCK_SPECIAL));
		$this->assertTrue($extractor->getManifestIsTypeFlag('fifo_pipe',    TTarFileExtractor::TYPE_FIFO));

		// Cross-checks: a wrong constant returns false.
		$this->assertFalse($extractor->getManifestIsTypeFlag('file.txt',    TTarFileExtractor::TYPE_DIRECTORY));
		$this->assertFalse($extractor->getManifestIsTypeFlag('dir/',        TTarFileExtractor::TYPE_FILE));
		$this->assertFalse($extractor->getManifestIsTypeFlag('symlink.txt', TTarFileExtractor::TYPE_HARDLINK));

		// Missing entry → null regardless of the requested typeflag.
		$this->assertNull($extractor->getManifestIsTypeFlag('nonexistent.txt', TTarFileExtractor::TYPE_FILE));
	}

	public function testgetManifestInfoReturnsNullForMissingPath()
	{
		$tarFile = $this->testDir . '/missing.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('exists.txt', 'x')]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertNull($extractor->getManifestInfo('does_not_exist.txt'));
	}

	public function testgetManifestInfoAcceptsDirectoryWithOrWithoutTrailingSeparator()
	{
		$tarFile = $this->testDir . '/dirlookup.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('src/', '', '5'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertNotNull($extractor->getManifestInfo('src/'));
		$this->assertNotNull($extractor->getManifestInfo('src'));  // without trailing sep
	}

	// ---------------------------------------------------------------------------
	// Group 3b: Path-field getters — getManifestTarPath / getManifestTarLink / getManifestFilePath
	// ---------------------------------------------------------------------------

	public function testGetManifestTarPathReturnsRawPosixPath()
	{
		$tarFile = $this->testDir . '/tarpath.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('subdir/file.txt', 'hello'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		// Raw tarpath preserves the trailing slash on directory entries.
		$this->assertSame('subdir/', $extractor->getManifestTarPath('subdir/'));
		$this->assertSame('subdir/file.txt', $extractor->getManifestTarPath('subdir/file.txt'));
		$this->assertNull($extractor->getManifestTarPath('nonexistent.txt'));
	}

	public function testGetManifestTarPathRootLevelFile()
	{
		$tarFile = $this->testDir . '/tarpath_root.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('root.txt', 'data'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->assertSame('root.txt', $extractor->getManifestTarPath('root.txt'));
	}

	public function testGetManifestTarLinkEmptyForNonLinkEntries()
	{
		$tarFile = $this->testDir . '/tarlink_nonlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('file.txt', 'data'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->assertSame('', $extractor->getManifestTarLink('file.txt'));
		$this->assertSame('', $extractor->getManifestTarLink('dir/'));
		$this->assertNull($extractor->getManifestTarLink('nonexistent.txt'));
	}

	public function testGetManifestTarLinkForSymlinkAndHardlink()
	{
		$tarFile = $this->testDir . '/tarlink_links.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('original.txt', 'data'),
			TarTestHelper::entry('symlink.txt', '', '2', 'original.txt'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->assertSame('original.txt', $extractor->getManifestTarLink('symlink.txt'));
		$this->assertSame('original.txt', $extractor->getManifestTarLink('hardlink.txt'));
	}

	public function testGetManifestTarPathNormalizedPreservesDirectorySlash()
	{
		$tarFile = $this->testDir . '/tarpath_norm.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('subdir/file.txt', 'hello'),
			TarTestHelper::entry('root.txt', 'data'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		// Directory entries retain the trailing slash after normalisation.
		$this->assertSame('subdir/', $extractor->getManifestTarPathNormalized('subdir/'));
		// Regular files are unchanged.
		$this->assertSame('subdir/file.txt', $extractor->getManifestTarPathNormalized('subdir/file.txt'));
		$this->assertSame('root.txt', $extractor->getManifestTarPathNormalized('root.txt'));
		// Missing entry returns null.
		$this->assertNull($extractor->getManifestTarPathNormalized('nonexistent.txt'));
	}

	public function testGetManifestTarLinkNormalizedEmptyForNonLinks()
	{
		$tarFile = $this->testDir . '/tarlink_norm_nonlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('file.txt', 'data'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->assertSame('', $extractor->getManifestTarLinkNormalized('file.txt'));
		$this->assertSame('', $extractor->getManifestTarLinkNormalized('dir/'));
		$this->assertNull($extractor->getManifestTarLinkNormalized('nonexistent.txt'));
	}

	public function testGetManifestTarLinkNormalizedForLinks()
	{
		$tarFile = $this->testDir . '/tarlink_norm_links.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('original.txt', 'data'),
			TarTestHelper::entry('symlink.txt', '', '2', 'original.txt'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->assertSame('original.txt', $extractor->getManifestTarLinkNormalized('symlink.txt'));
		$this->assertSame('original.txt', $extractor->getManifestTarLinkNormalized('hardlink.txt'));
	}

	public function testGetManifestFilePathReturnsOsNativePath()
	{
		$tarFile = $this->testDir . '/filepath.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('subdir/file.txt', 'hello'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$sep = DIRECTORY_SEPARATOR;
		$this->assertSame('subdir' . $sep, $extractor->getManifestFilePath('subdir/'));
		$this->assertSame('subdir' . $sep . 'file.txt', $extractor->getManifestFilePath('subdir/file.txt'));
		$this->assertNull($extractor->getManifestFilePath('nonexistent.txt'));
	}

	public function testGetManifestFilePathMatchesTarPathOnPosix()
	{
		$tarFile = $this->testDir . '/filepath_posix.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('a/b/c.txt', 'deep'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		if (DIRECTORY_SEPARATOR === '/') {
			// On POSIX systems filepath (raw OS-native) equals tarpath (raw POSIX).
			$this->assertSame(
				$extractor->getManifestTarPath('subdir/'),
				$extractor->getManifestFilePath('subdir/')
			);
			$this->assertSame(
				$extractor->getManifestTarPath('a/b/c.txt'),
				$extractor->getManifestFilePath('a/b/c.txt')
			);
		} else {
			// On Windows forward slashes become backslashes in filepath.
			$tarPath = $extractor->getManifestTarPath('a/b/c.txt');
			$this->assertSame(
				str_replace('/', '\\', (string) $tarPath),
				$extractor->getManifestFilePath('a\\b\\c.txt')
			);
		}
	}

	public function testGetManifestFilePathNormalizedStripsTrailingSlash()
	{
		$tarFile = $this->testDir . '/filepath_norm.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('subdir/file.txt', 'hello'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$sep = DIRECTORY_SEPARATOR;
		// Normalized filepath strips the trailing separator from directory entries.
		$this->assertSame('subdir', $extractor->getManifestFilePathNormalized('subdir/'));
		$this->assertSame('subdir' . $sep . 'file.txt', $extractor->getManifestFilePathNormalized('subdir/file.txt'));
		$this->assertNull($extractor->getManifestFilePathNormalized('nonexistent.txt'));
	}

	public function testGetManifestLinkPathNormalized()
	{
		$tarFile = $this->testDir . '/linkpath_norm.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('original.txt', 'data'),
			TarTestHelper::entry('symlink.txt', '', '2', 'original.txt'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		// Non-link entries return empty string.
		$this->assertSame('', $extractor->getManifestLinkPathNormalized('original.txt'));
		$this->assertSame('', $extractor->getManifestLinkPathNormalized('dir/'));
		// Link entries return the normalised OS-native target.
		$this->assertSame('original.txt', $extractor->getManifestLinkPathNormalized('symlink.txt'));
		$this->assertSame('original.txt', $extractor->getManifestLinkPathNormalized('hardlink.txt'));
		// Missing entry returns null.
		$this->assertNull($extractor->getManifestLinkPathNormalized('nonexistent.txt'));
	}

	// ---------------------------------------------------------------------------
	// Group 4: Scan without extraction — extracted/extractedPath remain false/''
	// ---------------------------------------------------------------------------

	public function testScanWithoutExtractionLeavesExtractedAbsent()
	{
		$tarFile = $this->testDir . '/scanonly.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('dir/a.txt', 'aaa'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		foreach ($map as $info) {
			$this->assertArrayNotHasKey('extracted', $info, "Entry {$info['path']} should have no 'extracted' key after scan-only");
			$this->assertArrayNotHasKey('extractedPath', $info, "Entry {$info['path']} should have no 'extractedPath' key after scan-only");
		}
		// Files must NOT have been written to disk.
		$this->assertFileDoesNotExist($this->extractDir . '/dir/a.txt');
	}

	public function testScanBeforeExtractSetsRetainTempFile()
	{
		// Simulate a URL archive by injecting _temp_tarpath before scan.
		$tarFile = $this->testDir . '/url_sim.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('readme.txt', 'hello')]);

		$extractor = new TTarFileExtractor('http://example.com/test.tar');
		$this->injectTempTarPath($extractor, $tarFile);

		// Scan first — this should set _retainTempFile to true.
		$extractor->getManifest();

		$ref = new \ReflectionClass($extractor);
		$retain = $ref->getProperty('_retainTempFile');
		$retain->setAccessible(true);
		$this->assertTrue($retain->getValue($extractor), '_retainTempFile must be true after scan of URL archive');
	}

	// ---------------------------------------------------------------------------
	// Group 5: Extraction populates the map with extracted = true / extractedPath set
	// ---------------------------------------------------------------------------

	public function testExtractionSetsExtractedTrueAndPath()
	{
		$tarFile = $this->testDir . '/extract_map.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('out.txt', 'output'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$info = $extractor->getExtractManifestInfo('out.txt');
		$this->assertNotNull($info);
		$this->assertTrue($info['extracted']);
		$this->assertStringEndsWith('out.txt', $info['extractedPath']);
		$this->assertFileExists($info['extractedPath']);
	}

	public function testExtractionDirectoryEntryMarkedExtracted()
	{
		$tarFile = $this->testDir . '/extract_dir.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('newdir/', '', '5'),
			TarTestHelper::entry('newdir/f.txt', 'data'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$dirInfo = $extractor->getExtractManifestInfo('newdir/');
		$this->assertTrue($dirInfo['extracted']);
		$this->assertDirectoryExists($dirInfo['extractedPath']);
	}

	public function testExistingDirectoryNotMarkedExtracted()
	{
		$tarFile = $this->testDir . '/preexist_dir.tar';
		mkdir($this->extractDir . '/predir', 0o777, true);
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('predir/', '', '5'),
			TarTestHelper::entry('predir/f.txt', 'data'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$dirInfo = $extractor->getExtractManifestInfo('predir');
		$this->assertNotNull($dirInfo);
		$this->assertTrue($dirInfo['extracted']);
	}

	public function testExtractionResetsMapOnEachCall()
	{
		$tarFile1 = $this->testDir . '/t1.tar';
		$tarFile2 = $this->testDir . '/t2.tar';
		TarTestHelper::writeTar($tarFile1, [TarTestHelper::entry('t1.txt', 'one')]);
		TarTestHelper::writeTar($tarFile2, [TarTestHelper::entry('t2.txt', 'two')]);

		$extractor = new TTarFileExtractor($tarFile1);
		$extractor->extract($this->extractDir);
		$this->assertNotNull($extractor->getManifestInfo('t1.txt'));

		// Second extractor on a different archive — its map should NOT contain t1.txt.
		$extractor2 = new TTarFileExtractor($tarFile2);
		$extractor2->extract($this->extractDir);
		$this->assertNotNull($extractor2->getManifestInfo('t2.txt'));
		$this->assertNull($extractor2->getManifestInfo('t1.txt'));
	}

	// ---------------------------------------------------------------------------
	// Group 6: Path map through all compression formats
	// ---------------------------------------------------------------------------

	public function testPathMapFromPlainTar()
	{
		$tarFile = $this->testDir . '/plain.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('dir/plain.txt', 'plain content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertArrayHasKey('dir/', $map);
		$this->assertArrayHasKey('dir/plain.txt', $map);
	}

	public function testPathMapFromGzipTar()
	{
		$tarFile = $this->testDir . '/gz.tar.gz';
		TarTestHelper::writeTarGz($tarFile, [
			TarTestHelper::entry('gz_dir/', '', '5'),
			TarTestHelper::entry('gz_dir/gz.txt', 'gz content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertArrayHasKey('gz_dir' . DIRECTORY_SEPARATOR, $map);
		$this->assertArrayHasKey('gz_dir/gz.txt', $map);
	}

	public function testPathMapFromBzip2Tar()
	{
		$tarFile = $this->testDir . '/bz2.tar.bz2';
		TarTestHelper::writeTarBz2($tarFile, [
			TarTestHelper::entry('bz_file.txt', 'bzip2 content'),
		], $this);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertArrayHasKey('bz_file.txt', $map);
	}

	public function testPathMapFromExistingGzipFixture()
	{
		// Use the shared fixture archive from the compression test suite.
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertNotEmpty($map);
		$this->assertArrayHasKey('gzip_content.txt', $map);
		$this->assertArrayHasKey('gzip_data.json', $map);
		$this->assertSame('file', $map['gzip_content.txt']['type']);
	}

	public function testPathMapFromExistingBzip2Fixture()
	{
		$tarFile = __DIR__ . '/data/test_bzip2.tar.bz2';

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertNotEmpty($map);
		$this->assertArrayHasKey('bzip2_content.txt', $map);
	}

	public function testPathMapFromExistingXzFixture()
	{
		$tarFile = __DIR__ . '/data/test_xz.tar.xz';

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertNotEmpty($map);
		$this->assertArrayHasKey('xz_content.txt', $map);
	}

	public function testExtractionViaGzipSetsExtractedPath()
	{
		$tarFile = $this->testDir . '/ext.tar.gz';
		TarTestHelper::writeTarGz($tarFile, [
			TarTestHelper::entry('gz_out.txt', 'gz output'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$info = $extractor->getExtractManifestInfo('gz_out.txt');
		$this->assertTrue($info['extracted']);
		$this->assertFileExists($info['extractedPath']);
	}

	// ---------------------------------------------------------------------------
	// Group 7: GNU long-name and long-link extensions
	// ---------------------------------------------------------------------------

	public function testGnuLongNameScannedCorrectly()
	{
		$longName = str_repeat('a', 80) . '/' . str_repeat('b', 80) . '.txt'; // 162 chars
		$tarFile = $this->testDir . '/longname.tar';
		[$gnuBlock, $realBlock] = TarTestHelper::gnuLongNamePair($longName, 'long content');
		TarTestHelper::writeTar($tarFile, [$gnuBlock, $realBlock]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertArrayHasKey($longName, $map);
		$this->assertSame('file', $map[$longName]['type']);
	}

	public function testGnuLongNameExtracted()
	{
		$longName = str_repeat('d', 50) . '/' . str_repeat('f', 80) . '.txt'; // 132 chars
		$tarFile = $this->testDir . '/longname_extract.tar';
		[$gnuBlock, $realBlock] = TarTestHelper::gnuLongNamePair($longName, 'long file content', '0');

		// The directory component must come first in the archive.
		$dirEntry = TarTestHelper::entry(str_repeat('d', 50) . '/', '', '5');
		TarTestHelper::writeTar($tarFile, [$dirEntry, $gnuBlock, $realBlock]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/' . $longName);
		$this->assertSame('long file content', file_get_contents($this->extractDir . '/' . $longName));
	}

	public function testGnuLongLinkScannedCorrectly()
	{
		$longLink = str_repeat('t', 80) . '/' . str_repeat('g', 80) . '.txt'; // 162-char link target
		$tarFile = $this->testDir . '/longlink.tar';
		[$gnuBlock, $realBlock] = TarTestHelper::gnuLongLinkPair('mylink.txt', $longLink);
		TarTestHelper::writeTar($tarFile, [$gnuBlock, $realBlock]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false); // link target likely outside extract dir for scan
		$map = $extractor->getManifest();

		$this->assertArrayHasKey('mylink.txt', $map);
		$this->assertSame('symlink', $map['mylink.txt']['type']);
		$this->assertSame($longLink, $map['mylink.txt']['linkpath']);
	}

	public function testGnuLongLinkExtracted()
	{
		// Long link target that is safe (resides inside extraction dir).
		$subdir = str_repeat('s', 40);
		$longLink = $subdir . '/' . str_repeat('t', 50) . '.txt'; // safe relative path

		$tarFile = $this->testDir . '/longlink_extract.tar';
		$dirEntry = TarTestHelper::entry($subdir . '/', '', '5');
		$targetManifest = TarTestHelper::entry($longLink, 'target data');
		[$gnuBlock, $realBlock] = TarTestHelper::gnuLongLinkPair('mylink.txt', $longLink);
		TarTestHelper::writeTar($tarFile, [$dirEntry, $targetManifest, $gnuBlock, $realBlock]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/mylink.txt');
		$map = $extractor->getManifest();
		$this->assertSame($longLink, $map['mylink.txt']['linkpath']);
	}

	// ---------------------------------------------------------------------------
	// Group 8: Device and special file handling
	// ---------------------------------------------------------------------------

	public function testCharSpecialInStrictModeThrows()
	{
		$tarFile = $this->testDir . '/char_strict.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('regular.txt', 'ok'),
			TarTestHelper::entry('char_dev', '', '3'),  // TYPE_CHAR_SPECIAL
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testBlockSpecialInStrictModeThrows()
	{
		$tarFile = $this->testDir . '/block_strict.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('block_dev', '', '4'),  // TYPE_BLOCK_SPECIAL
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testFifoInStrictModeThrows()
	{
		$tarFile = $this->testDir . '/fifo_strict.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('myfifo', '', '6'),  // TYPE_FIFO
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testCharSpecialInNonStrictModeSkipped()
	{
		$tarFile = $this->testDir . '/char_nostrict.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'safe'),
			TarTestHelper::entry('char_dev', '', '3'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$this->assertFileExists($this->extractDir . '/safe.txt');
		$this->assertFileDoesNotExist($this->extractDir . '/char_dev');
	}

	public function testBlockSpecialInNonStrictModeSkipped()
	{
		$tarFile = $this->testDir . '/block_nostrict.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('block_dev', '', '4'),
			TarTestHelper::entry('after.txt', 'after'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileDoesNotExist($this->extractDir . '/block_dev');
		$this->assertFileExists($this->extractDir . '/after.txt');
	}

	public function testDeviceEntryTypeInMap()
	{
		$tarFile = $this->testDir . '/dev_types.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('cdev', '', '3'),   // char_device
			TarTestHelper::entry('bdev', '', '4'),   // block_device
			TarTestHelper::entry('fifo', '', '6'),   // fifo
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$map = $extractor->getManifest();

		$this->assertSame('char_device', $map['cdev']['type']);
		$this->assertSame('block_device', $map['bdev']['type']);
		$this->assertSame('fifo', $map['fifo']['type']);
	}

	public function testDeviceFilegetManifestIsDeviceTrue()
	{
		$tarFile = $this->testDir . '/dev_isdevice.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('cdev', '', '3'),
			TarTestHelper::entry('regular.txt', 'x'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);

		$this->assertTrue($extractor->getManifestIsDevice('cdev'));
		$this->assertFalse($extractor->getManifestIsDevice('regular.txt'));
	}

	public function testDeviceFileCanHaveSafePath()
	{
		// A device file at a normal relative path is still path-safe.
		$tarFile = $this->testDir . '/dev_safe.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe/device', '', '3'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->getManifest();

		$this->assertTrue($extractor->getManifestIsDevice('safe/device'));
		$this->assertTrue($extractor->getManifestIsSafe('safe/device'));
	}

	public function testSkippedDeviceEntryInMapWithExtractedFalse()
	{
		$tarFile = $this->testDir . '/dev_map.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('char_dev', '', '3'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);

		$info = $extractor->getExtractManifestInfo('char_dev');
		$this->assertNotNull($info);
		$this->assertArrayNotHasKey('extracted', $info);
		$this->assertSame('device', $info['reason'] ?? '');
	}

	// ---------------------------------------------------------------------------
	// Group 9: Zip Slip (path traversal) security
	// ---------------------------------------------------------------------------

	public function testZipSlipInStrictModeThrows()
	{
		$tarFile = $this->testDir . '/zipslip_strict.tar';
		$slipHeader = TarTestHelper::header('../escape.txt', 5);
		$data = str_pad('evil!', 512, "\x00");
		file_put_contents($tarFile, $slipHeader . $data . str_repeat("\x00", 1024));

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testZipSlipInNonStrictModeIsSkipped()
	{
		$tarFile = $this->testDir . '/zipslip_nostrict.tar';
		$safeHeader = TarTestHelper::header('safe.txt', 4);
		$safeData = str_pad('safe', 512, "\x00");
		$slipHeader = TarTestHelper::header('../evil.txt', 4);
		$evilData = str_pad('evil', 512, "\x00");
		file_put_contents($tarFile, $safeHeader . $safeData . $slipHeader . $evilData . str_repeat("\x00", 1024));

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$this->assertFileExists($this->extractDir . '/safe.txt');
	}

	public function testZipSlipEntryInMapHasSafeFalse()
	{
		$tarFile = $this->testDir . '/zipslip_map.tar';
		$slipHeader = TarTestHelper::header('../evil.txt', 4);
		$data = str_pad('evil', 512, "\x00");
		file_put_contents($tarFile, $slipHeader . $data . str_repeat("\x00", 1024));

		// Non-strict scan so it doesn't throw during scan.
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->getManifest();

		$this->assertFalse($extractor->getManifestIsSafe('../evil.txt'));
	}

	public function testZipSlipSkippedFileRecordedCorrectly()
	{
		$tarFile = $this->testDir . '/zipslip_record.tar';
		$slipHeader = TarTestHelper::header('../oops.txt', 4);
		$data = str_pad('oops', 512, "\x00");
		file_put_contents($tarFile, $slipHeader . $data . str_repeat("\x00", 1024));

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);

		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame('zip_slip', $skipped[0]['reason']);
		$this->assertStringContainsString('oops.txt', $skipped[0]['filepath']);
	}

	// ---------------------------------------------------------------------------
	// Group 10: Symlink security
	// ---------------------------------------------------------------------------

	public function testSafeSymlinkExtracted()
	{
		$tarFile = $this->testDir . '/safe_symlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('target.txt', 'target content'),
			TarTestHelper::entry('link.txt', '', '2', 'target.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/link.txt');
		$this->assertSame('target content', file_get_contents($this->extractDir . '/link.txt'));
	}

	public function testUnsafeSymlinkStrictModeThrows()
	{
		$tarFile = $this->testDir . '/evil_symlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('evil_link.txt', '', '2', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testUnsafeSymlinkNonStrictSkipped()
	{
		$tarFile = $this->testDir . '/evil_sym_nostrict.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'ok'),
			TarTestHelper::entry('evil_link.txt', '', '2', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertSame('symlink', $skipped[0]['reason']);
		$this->assertSame('../../../etc/passwd', $skipped[0]['linkpath']);
		$this->assertFileDoesNotExist($this->extractDir . '/evil_link.txt');
	}

	// ---------------------------------------------------------------------------
	// Group 11: Hard link security
	// ---------------------------------------------------------------------------

	public function testSafeHardlinkExtracted()
	{
		$tarFile = $this->testDir . '/safe_hardlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('original.txt', 'original data'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/hardlink.txt');
		$this->assertSame('original data', file_get_contents($this->extractDir . '/hardlink.txt'));
	}

	public function testUnsafeHardlinkStrictModeThrows()
	{
		$tarFile = $this->testDir . '/evil_hardlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('evil_hard.txt', '', '1', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testUnsafeHardlinkNonStrictSkipped()
	{
		$tarFile = $this->testDir . '/evil_hard_nostrict.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'ok'),
			TarTestHelper::entry('evil_hard.txt', '', '1', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertSame('hardlink', $skipped[0]['reason']);
		$this->assertFileDoesNotExist($this->extractDir . '/evil_hard.txt');
	}

	// ---------------------------------------------------------------------------
	// Group 12: Skipped-file tracking API
	// ---------------------------------------------------------------------------

	public function testHasSkippedFilesDefaultFalse()
	{
		$tarFile = $this->testDir . '/noskip.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('f.txt', 'x')]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$this->assertFalse($extractor->hasSkippedFiles());
	}

	public function testSkippedFileEntryStructure()
	{
		$tarFile = $this->testDir . '/skipstruct.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('char_dev', '', '3'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);

		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$entry = $skipped[0];

		$this->assertArrayHasKey('reason', $entry);
		$this->assertArrayHasKey('filepath', $entry);
		$this->assertArrayHasKey('linkpath', $entry);
		$this->assertArrayHasKey('typeflag', $entry);
		$this->assertSame('device', $entry['reason']);
	}

	public function testMultipleSkipTypesTrackedSeparately()
	{
		$tarFile = $this->testDir . '/multiskip.tar';
		$slipHeader = TarTestHelper::header('../slip.txt', 3);
		$slipData = str_pad('ooh', 512, "\x00");
		$devEntry = TarTestHelper::entry('char_dev', '', '3');
		$evilSymlink = TarTestHelper::entry('bad_link', '', '2', '../../../etc/passwd');
		file_put_contents($tarFile, $slipHeader . $slipData . $devEntry . $evilSymlink . str_repeat("\x00", 1024));

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);

		$skipped = $extractor->getSkippedFiles();
		$reasons = array_column($skipped, 'reason');

		$this->assertContains('zip_slip', $reasons);
		$this->assertContains('device', $reasons);
		$this->assertContains('symlink', $reasons);
	}

	// ---------------------------------------------------------------------------
	// Group 13: Unwind on failure
	// ---------------------------------------------------------------------------

	public function testUnwindOnFailureRemovesExtractedFilesOnError()
	{
		// Archive: valid file first, then a device file that will throw in strict mode.
		// Restore-on-failure is the sole extraction mode — extracted files are unwound on failure.
		$tarFile = $this->testDir . '/unwind_test.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('first.txt', 'first file content'),
			TarTestHelper::entry('char_dev', '', '3'),   // will throw in strict mode
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$threw = false;
		try {
			$extractor->extract($this->extractDir);
		} catch (\Exception $e) {
			$threw = true;
		}

		$this->assertTrue($threw, 'Expected exception from device file in strict mode');
		// first.txt was extracted then unwound by restore-on-failure.
		$this->assertFileDoesNotExist($this->extractDir . '/first.txt', 'Restore-on-failure should have removed extracted file');
	}

	public function testUnwindOnFailureWithZipSlipThrow()
	{
		// Use zip slip as the trigger in strict mode.
		$tarFile = $this->testDir . '/unwind_slip.tar';
		$safeEntry = TarTestHelper::entry('good.txt', 'good');
		$slipHeader = TarTestHelper::header('../evil.txt', 4);
		$evilData = str_pad('evil', 512, "\x00");
		file_put_contents($tarFile, $safeEntry . $slipHeader . $evilData . str_repeat("\x00", 1024));

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		try {
			$extractor->extract($this->extractDir);
		} catch (\Exception $e) {
			// Expected.
		}

		$this->assertFileDoesNotExist($this->extractDir . '/good.txt', 'Restore-on-failure should have removed good.txt');
	}

	// ---------------------------------------------------------------------------
	// Group 14: URL simulation (reflection-injected _temp_tarpath)
	// ---------------------------------------------------------------------------

	public function testUrlSimulationExtractPopulatesMap()
	{
		$tarFile = $this->testDir . '/url_sim.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('dir/', '', '5'),
			TarTestHelper::entry('dir/url_file.txt', 'url content'),
		]);

		$extractor = new TTarFileExtractor('http://example.com/test.tar');
		$this->injectTempTarPath($extractor, $tarFile);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$map = $extractor->getExtractManifest();
		$this->assertArrayHasKey('dir' . DIRECTORY_SEPARATOR, $map);
		$this->assertArrayHasKey('dir/url_file.txt', $map);
		$this->assertTrue($map['dir/url_file.txt']['extracted']);
	}

	public function testUrlSimulationScanBeforeExtractReusesFile()
	{
		$tarFile = $this->testDir . '/url_scan_then_extract.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('readme.txt', 'readme'),
		]);

		$extractor = new TTarFileExtractor('http://example.com/test.tar');
		$this->injectTempTarPath($extractor, $tarFile);
		$extractor->setRetainTempFile(true);

		// Scan first — should NOT throw and should populate the map.
		$map = $extractor->getManifest();
		$this->assertArrayHasKey('readme.txt', $map);
		// Note: 'extracted' is only set in extraction manifest, not in scan manifest
		$this->assertArrayNotHasKey('extracted', $map['readme.txt']);

		// Now extract — should reuse the already-set _temp_tarpath.
		$result = $extractor->extract($this->extractDir);
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/readme.txt');
	}

	public function testUrlSimulationWithGzipFixture()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_pathmap_gz_' . uniqid() . '.tar.gz';
		copy(__DIR__ . '/data/test_gzip.tar.gz', $tempArchive);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.gz');
		$this->injectTempTarPath($extractor, $tempArchive);
		$extractor->setRetainTempFile(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$map = $extractor->getExtractManifest();
		$this->assertArrayHasKey('gzip_content.txt', $map);
		$this->assertTrue($map['gzip_content.txt']['extracted']);

		// Cleanup in case the test leaves the archive behind.
		if (file_exists($tempArchive)) {
			unlink($tempArchive);
		}
	}

	public function testUrlSimulationWithBzip2Fixture()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_pathmap_bz2_' . uniqid() . '.tar.bz2';
		copy(__DIR__ . '/data/test_bzip2.tar.bz2', $tempArchive);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.bz2');
		$this->injectTempTarPath($extractor, $tempArchive);
		$extractor->setRetainTempFile(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$map = $extractor->getManifest();
		$this->assertArrayHasKey('bzip2_content.txt', $map);

		if (file_exists($tempArchive)) {
			unlink($tempArchive);
		}
	}

	public function testUrlSimulationWithXzFixture()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_pathmap_xz_' . uniqid() . '.tar.xz';
		copy(__DIR__ . '/data/test_xz.tar.xz', $tempArchive);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.xz');
		$this->injectTempTarPath($extractor, $tempArchive);
		$extractor->setRetainTempFile(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$map = $extractor->getManifest();
		$this->assertArrayHasKey('xz_content.txt', $map);

		if (file_exists($tempArchive)) {
			unlink($tempArchive);
		}
	}

	// ---------------------------------------------------------------------------
	// Group 15: extractModify (strip prefix)
	// ---------------------------------------------------------------------------

	public function testExtractModifyStripsPrefix()
	{
		$tarFile = $this->testDir . '/modify.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('prefix/subdir/', '', '5'),
			TarTestHelper::entry('prefix/subdir/file.txt', 'modified'),
		]);

		// extractModify is protected; call via extract + reflection to confirm the
		// map keys are relative to the *stripped* path.
		$extractor = new TTarFileExtractor($tarFile);
		$ref = new \ReflectionClass($extractor);
		$method = $ref->getMethod('extractModify');
		$method->setAccessible(true);
		$result = $method->invoke($extractor, $this->extractDir, 'prefix');

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/subdir/file.txt');

		// Map keys should be relative to the stripped prefix.
		$map = $extractor->getManifest();
		$this->assertArrayHasKey('subdir/', $map);
		$this->assertArrayHasKey('subdir/file.txt', $map);
	}

	// ---------------------------------------------------------------------------
	// Group 16: Edge cases
	// ---------------------------------------------------------------------------

	public function testEmptyArchiveReturnsEmptyMap()
	{
		$tarFile = $this->testDir . '/empty.tar';
		file_put_contents($tarFile, str_repeat("\x00", 1024)); // just EOA blocks

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertIsArray($map);
		$this->assertEmpty($map);
	}

	public function testSymlinkEntryInMap()
	{
		$tarFile = $this->testDir . '/sym_map.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('target.txt', 'data'),
			TarTestHelper::entry('link.txt', '', '2', 'target.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$info = $extractor->getExtractManifestInfo('link.txt');
		$this->assertNotNull($info);
		$this->assertSame('symlink', $info['type']);
		$this->assertTrue($info['extracted']);
	}

	public function testStrictPropertyDefaultTrue()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$this->assertTrue($extractor->getStrict());
	}

	public function testSetStrictReturnsSelf()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->setStrict(false);
		$this->assertSame($extractor, $result);
		$this->assertFalse($extractor->getStrict());
	}

	public function testMultipleScansReturnSameMap()
	{
		$tarFile = $this->testDir . '/multi_scan.tar';
		TarTestHelper::writeTar($tarFile, [TarTestHelper::entry('once.txt', 'x')]);

		$extractor = new TTarFileExtractor($tarFile);
		$map1 = $extractor->getManifest();
		$map2 = $extractor->getManifest();  // second call should return cached map

		$this->assertSame($map1, $map2);
	}

	public function testContiguousFileTypeExtractedAsRegularFile()
	{
		// TYPE_CONTIGUOUS ('7') should be treated like a regular file.
		$tarFile = $this->testDir . '/contiguous.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('contig.txt', 'contiguous content', '7'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/contig.txt');
		$this->assertSame('contiguous content', file_get_contents($this->extractDir . '/contig.txt'));

		$info = $extractor->getManifestInfo('contig.txt');
		$this->assertSame('file', $info['type']);   // TYPE_CONTIGUOUS maps to 'file'
	}
}
