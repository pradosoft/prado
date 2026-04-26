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

	/**
	 * Build a single 512-byte tar header block.
	 * Returns a header-only string (no data blocks).
	 */
	private function createTarHeader(
		string $filename,
		int $size,
		string $typeflag = '0',
		string $linkname = '',
		int $mode = 0644,
		int $uid = 1000,
		int $gid = 1000,
		int $mtime = self::FIXED_MTIME,
		string $uname = 'testuser',
		string $gname = 'testgroup'
	): string {
		$header = pack(
			'a100a8a8a8a12a12a8',
			$filename,
			sprintf('%07o', $mode) . "\x00",
			sprintf('%07o', $uid) . "\x00",
			sprintf('%07o', $gid) . "\x00",
			sprintf('%011o', $size) . "\x00",
			sprintf('%011o', $mtime) . "\x00",
			'        '          // 8-space checksum placeholder
		);
		$header .= pack(
			'a1a100a6a2a32a32a8a8a155a12',
			$typeflag,
			$linkname,
			'ustar ',
			'00',
			$uname,
			$gname,
			'',
			'',
			'',
			''
		);
		$header = str_pad($header, 512, "\x00");

		// Calculate and embed the checksum.
		$checksum = 0;
		for ($i = 0; $i < 148; $i++) {
			$checksum += ord($header[$i]);
		}
		for ($i = 148; $i < 156; $i++) {
			$checksum += ord(' ');
		}
		for ($i = 156; $i < 512; $i++) {
			$checksum += ord($header[$i]);
		}
		$header = substr($header, 0, 148) . sprintf('%06o', $checksum) . "\x00 " . substr($header, 156);

		return $header;
	}

	/**
	 * Returns a header + padded data block string for a single tar entry.
	 */
	private function createTarEntry(
		string $filename,
		string $content = '',
		string $typeflag = '0',
		string $linkname = '',
		int $mode = 0644,
		int $uid = 1000,
		int $gid = 1000,
		int $mtime = self::FIXED_MTIME,
		string $uname = 'testuser',
		string $gname = 'testgroup'
	): string {
		$size = strlen($content);
		$header = $this->createTarHeader($filename, $size, $typeflag, $linkname, $mode, $uid, $gid, $mtime, $uname, $gname);
		if ($size === 0) {
			return $header;
		}
		$paddedSize = (int)(ceil($size / 512) * 512);
		return $header . str_pad($content, $paddedSize, "\x00");
	}

	/**
	 * Returns the binary representation of a complete tar archive (entries + EOA marker).
	 */
	private function buildTar(array $entryBlocks): string
	{
		return implode('', $entryBlocks) . str_repeat("\x00", 1024);
	}

	/** Write a plain .tar file. */
	private function writeTar(string $path, array $entries): void
	{
		file_put_contents($path, $this->buildTar($entries));
	}

	/** Write a gzip-compressed .tar.gz file. */
	private function writeTarGz(string $path, array $entries): void
	{
		$tarData = $this->buildTar($entries);
		$gz = gzopen($path, 'wb9');
		gzwrite($gz, $tarData);
		gzclose($gz);
	}

	/** Write a bzip2-compressed .tar.bz2 file (skips test if bzcompress unavailable). */
	private function writeTarBz2(string $path, array $entries): void
	{
		if (!function_exists('bzcompress')) {
			$this->markTestSkipped('bz2 extension not available');
		}
		file_put_contents($path, bzcompress($this->buildTar($entries)));
	}

	/**
	 * Create a GNU long-filename (TYPE 'L') entry + a following file entry.
	 * Used to create archives where a path exceeds 100 characters.
	 */
	private function createGnuLongNamePair(
		string $longFilename,
		string $content = '',
		string $typeflag = '0',
		string $linkname = ''
	): array {
		$longData = $longFilename . "\x00";
		// GNU long-name header: filename=././@LongLink, typeflag='L', size=len(longData)
		$gnuHeader = $this->createTarHeader('././@LongLink', strlen($longData), 'L', '', 0, 0, 0, 0, '', '');
		$paddedData = str_pad($longData, (int)(ceil(strlen($longData) / 512) * 512), "\x00");
		// Real entry header — the short name slot can be anything (truncated); the actual
		// name will come from the preceding 'L' block at extraction time.
		$realEntry = $this->createTarEntry(
			substr($longFilename, 0, 100),
			$content,
			$typeflag,
			$linkname
		);
		return [$gnuHeader . $paddedData, $realEntry];
	}

	/**
	 * Create a GNU long-linkname (TYPE 'K') entry + a following symlink entry.
	 */
	private function createGnuLongLinkPair(string $linkFilename, string $longLinkname): array
	{
		$longData = $longLinkname . "\x00";
		$gnuHeader = $this->createTarHeader('././@LongLink', strlen($longData), 'K', '', 0, 0, 0, 0, '', '');
		$paddedData = str_pad($longData, (int)(ceil(strlen($longData) / 512) * 512), "\x00");
		// Real symlink entry — linkname will be overridden by the 'K' block
		$realEntry = $this->createTarEntry($linkFilename, '', '2', substr($longLinkname, 0, 100));
		return [$gnuHeader . $paddedData, $realEntry];
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('subdir/', '', '5'),
			$this->createTarEntry('subdir/file.txt', 'hello'),
			$this->createTarEntry('root.txt', 'root'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('z_file.txt', 'data'),
			$this->createTarEntry('a_dir/', '', '5'),
			$this->createTarEntry('a_dir/child.txt', 'child'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('mydir/', '', '5'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();
		$key = 'mydir';

		$this->assertArrayHasKey($key, $map);
	}

	public function testMapIsNullBeforeScanOrExtract()
	{
		$tarFile = $this->testDir . '/lazy.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('a.txt', 'a')]);

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
		$this->writeTar($tarFile, [
			$this->createTarEntry('dir/', '', '5'),
			$this->createTarEntry('dir/f.txt', 'x'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry(
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
		$this->assertSame('meta.txt', $info['name']);
		$this->assertSame('file', $info['type']);
		$this->assertSame('meta.txt', $info['filename']);
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('docs/', '', '5', '', 0o755, 1000, 1000, self::FIXED_MTIME, 'user', 'group'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('target.txt', 'content'),
			$this->createTarEntry('link.txt', '', '2', 'target.txt'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('original.txt', 'original'),
			$this->createTarEntry('hardlink.txt', '', '1', 'original.txt'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('dir/', '', '5'),
			$this->createTarEntry('dir/f.txt', 'x'),
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
		$this->writeTar($tarFile, [$this->createTarEntry('f.txt', $content)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(strlen($content), $extractor->getManifestSize('f.txt'));
		$this->assertNull($extractor->getManifestSize('missing.txt'));
	}

	public function testgetManifestMtime()
	{
		$tarFile = $this->testDir . '/mtime.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('f.txt', 'x', '0', '', 0644, 0, 0, self::FIXED_MTIME)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(self::FIXED_MTIME, $extractor->getManifestMtime('f.txt'));
		$this->assertNull($extractor->getManifestMtime('missing.txt'));
	}

	public function testgetManifestMode()
	{
		$tarFile = $this->testDir . '/mode.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('f.txt', 'x', '0', '', 0o755)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(0o755, $extractor->getManifestMode('f.txt'));
		$this->assertNull($extractor->getManifestMode('missing.txt'));
	}

	public function testgetManifestUidAndGid()
	{
		$tarFile = $this->testDir . '/uidgid.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('f.txt', 'x', '0', '', 0644, 501, 20)]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame(501, $extractor->getManifestUid('f.txt'));
		$this->assertSame(20, $extractor->getManifestGid('f.txt'));
		$this->assertNull($extractor->getManifestUid('missing.txt'));
		$this->assertNull($extractor->getManifestGid('missing.txt'));
	}

	public function testgetManifestUnameAndGname()
	{
		$tarFile = $this->testDir . '/names.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('f.txt', 'x', '0', '', 0644, 0, 0, self::FIXED_MTIME, 'alice', 'staff')]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertSame('alice', $extractor->getManifestUname('f.txt'));
		$this->assertSame('staff', $extractor->getManifestGname('f.txt'));
		$this->assertNull($extractor->getManifestUname('missing.txt'));
		$this->assertNull($extractor->getManifestGname('missing.txt'));
	}

	public function testgetManifestLinkpath()
	{
		$tarFile = $this->testDir . '/linkname.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('target.txt', 'data'),
			$this->createTarEntry('link.txt', '', '2', 'target.txt'),
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
		$safeHeader = $this->createTarHeader('safe/file.txt', 5);
		$unsafeHeader = $this->createTarHeader('../evil.txt', 5);
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('regular.txt', 'data'),
			$this->createTarEntry('char_dev', '', '3'),   // TYPE_CHAR_SPECIAL
			$this->createTarEntry('block_dev', '', '4'),  // TYPE_BLOCK_SPECIAL
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

	public function testgetManifestInfoReturnsNullForMissingPath()
	{
		$tarFile = $this->testDir . '/missing.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('exists.txt', 'x')]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertNull($extractor->getManifestInfo('does_not_exist.txt'));
	}

	public function testgetManifestInfoAcceptsDirectoryWithOrWithoutTrailingSeparator()
	{
		$tarFile = $this->testDir . '/dirlookup.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('src/', '', '5'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertNotNull($extractor->getManifestInfo('src/'));
		$this->assertNotNull($extractor->getManifestInfo('src'));  // without trailing sep
	}

	// ---------------------------------------------------------------------------
	// Group 4: Scan without extraction — extracted/extractedPath remain false/''
	// ---------------------------------------------------------------------------

	public function testScanWithoutExtractionLeavesExtractedFalse()
	{
		$tarFile = $this->testDir . '/scanonly.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('dir/', '', '5'),
			$this->createTarEntry('dir/a.txt', 'aaa'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		foreach ($map as $info) {
			$this->assertFalse($info['extracted'], "Entry {$info['path']} should not be marked extracted after scan");
			$this->assertSame('', $info['extractedPath']);
		}
		// Files must NOT have been written to disk.
		$this->assertFileDoesNotExist($this->extractDir . '/dir/a.txt');
	}

	public function testScanBeforeExtractSetsRetainTempFile()
	{
		// Simulate a URL archive by injecting _temp_tarpath before scan.
		$tarFile = $this->testDir . '/url_sim.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('readme.txt', 'hello')]);

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
		$this->writeTar($tarFile, [
			$this->createTarEntry('out.txt', 'output'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('newdir/', '', '5'),
			$this->createTarEntry('newdir/f.txt', 'data'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('predir/', '', '5'),
			$this->createTarEntry('predir/f.txt', 'data'),
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
		$this->writeTar($tarFile1, [$this->createTarEntry('t1.txt', 'one')]);
		$this->writeTar($tarFile2, [$this->createTarEntry('t2.txt', 'two')]);

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
		$this->writeTar($tarFile, [
			$this->createTarEntry('dir/', '', '5'),
			$this->createTarEntry('dir/plain.txt', 'plain content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertArrayHasKey('dir/', $map);
		$this->assertArrayHasKey('dir/plain.txt', $map);
	}

	public function testPathMapFromGzipTar()
	{
		$tarFile = $this->testDir . '/gz.tar.gz';
		$this->writeTarGz($tarFile, [
			$this->createTarEntry('gz_dir/', '', '5'),
			$this->createTarEntry('gz_dir/gz.txt', 'gz content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertArrayHasKey('gz_dir' . DIRECTORY_SEPARATOR, $map);
		$this->assertArrayHasKey('gz_dir/gz.txt', $map);
	}

	public function testPathMapFromBzip2Tar()
	{
		$tarFile = $this->testDir . '/bz2.tar.bz2';
		$this->writeTarBz2($tarFile, [
			$this->createTarEntry('bz_file.txt', 'bzip2 content'),
		]);

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
		$this->writeTarGz($tarFile, [
			$this->createTarEntry('gz_out.txt', 'gz output'),
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
		[$gnuBlock, $realBlock] = $this->createGnuLongNamePair($longName, 'long content');
		$this->writeTar($tarFile, [$gnuBlock, $realBlock]);

		$extractor = new TTarFileExtractor($tarFile);
		$map = $extractor->getManifest();

		$this->assertArrayHasKey($longName, $map);
		$this->assertSame('file', $map[$longName]['type']);
	}

	public function testGnuLongNameExtracted()
	{
		$longName = str_repeat('d', 50) . '/' . str_repeat('f', 80) . '.txt'; // 132 chars
		$tarFile = $this->testDir . '/longname_extract.tar';
		[$gnuBlock, $realBlock] = $this->createGnuLongNamePair($longName, 'long file content', '0');

		// The directory component must come first in the archive.
		$dirEntry = $this->createTarEntry(str_repeat('d', 50) . '/', '', '5');
		$this->writeTar($tarFile, [$dirEntry, $gnuBlock, $realBlock]);

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
		[$gnuBlock, $realBlock] = $this->createGnuLongLinkPair('mylink.txt', $longLink);
		$this->writeTar($tarFile, [$gnuBlock, $realBlock]);

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
		$dirEntry = $this->createTarEntry($subdir . '/', '', '5');
		$targetManifest = $this->createTarEntry($longLink, 'target data');
		[$gnuBlock, $realBlock] = $this->createGnuLongLinkPair('mylink.txt', $longLink);
		$this->writeTar($tarFile, [$dirEntry, $targetManifest, $gnuBlock, $realBlock]);

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
		$this->writeTar($tarFile, [
			$this->createTarEntry('regular.txt', 'ok'),
			$this->createTarEntry('char_dev', '', '3'),  // TYPE_CHAR_SPECIAL
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testBlockSpecialInStrictModeThrows()
	{
		$tarFile = $this->testDir . '/block_strict.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('block_dev', '', '4'),  // TYPE_BLOCK_SPECIAL
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testFifoInStrictModeThrows()
	{
		$tarFile = $this->testDir . '/fifo_strict.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('myfifo', '', '6'),  // TYPE_FIFO
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testCharSpecialInNonStrictModeSkipped()
	{
		$tarFile = $this->testDir . '/char_nostrict.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('safe.txt', 'safe'),
			$this->createTarEntry('char_dev', '', '3'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('block_dev', '', '4'),
			$this->createTarEntry('after.txt', 'after'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('cdev', '', '3'),   // char_device
			$this->createTarEntry('bdev', '', '4'),   // block_device
			$this->createTarEntry('fifo', '', '6'),   // fifo
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('cdev', '', '3'),
			$this->createTarEntry('regular.txt', 'x'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('safe/device', '', '3'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('char_dev', '', '3'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);

		$info = $extractor->getExtractManifestInfo('char_dev');
		$this->assertNotNull($info);
		$this->assertFalse($info['extracted']);
		$this->assertSame('', $info['extractedPath']);
	}

	// ---------------------------------------------------------------------------
	// Group 9: Zip Slip (path traversal) security
	// ---------------------------------------------------------------------------

	public function testZipSlipInStrictModeThrows()
	{
		$tarFile = $this->testDir . '/zipslip_strict.tar';
		$slipHeader = $this->createTarHeader('../escape.txt', 5);
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
		$safeHeader = $this->createTarHeader('safe.txt', 4);
		$safeData = str_pad('safe', 512, "\x00");
		$slipHeader = $this->createTarHeader('../evil.txt', 4);
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
		$slipHeader = $this->createTarHeader('../evil.txt', 4);
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
		$slipHeader = $this->createTarHeader('../oops.txt', 4);
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('target.txt', 'target content'),
			$this->createTarEntry('link.txt', '', '2', 'target.txt'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('evil_link.txt', '', '2', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testUnsafeSymlinkNonStrictSkipped()
	{
		$tarFile = $this->testDir . '/evil_sym_nostrict.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('safe.txt', 'ok'),
			$this->createTarEntry('evil_link.txt', '', '2', '../../../etc/passwd'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('original.txt', 'original data'),
			$this->createTarEntry('hardlink.txt', '', '1', 'original.txt'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('evil_hard.txt', '', '1', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);

		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testUnsafeHardlinkNonStrictSkipped()
	{
		$tarFile = $this->testDir . '/evil_hard_nostrict.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('safe.txt', 'ok'),
			$this->createTarEntry('evil_hard.txt', '', '1', '../../../etc/passwd'),
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
		$this->writeTar($tarFile, [$this->createTarEntry('f.txt', 'x')]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$this->assertFalse($extractor->hasSkippedFiles());
	}

	public function testClearSkippedFiles()
	{
		$tarFile = $this->testDir . '/clearskip.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('char_dev', '', '3'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);

		$this->assertTrue($extractor->hasSkippedFiles());
		$extractor->clearSkippedFiles();
		$this->assertFalse($extractor->hasSkippedFiles());
		$this->assertEmpty($extractor->getSkippedFiles());
	}

	public function testSkippedFileEntryStructure()
	{
		$tarFile = $this->testDir . '/skipstruct.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('char_dev', '', '3'),
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
		$slipHeader = $this->createTarHeader('../slip.txt', 3);
		$slipData = str_pad('ooh', 512, "\x00");
		$devEntry = $this->createTarEntry('char_dev', '', '3');
		$evilSymlink = $this->createTarEntry('bad_link', '', '2', '../../../etc/passwd');
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

	public function testUnwindOnFailureDefaultFalse()
	{
		$tarFile = $this->testDir . '/unwind_default.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('f.txt', 'x')]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertFalse($extractor->getRollbackOnFailure());
	}

	public function testsetRollbackOnFailureChaining()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->setRollbackOnFailure(true);
		$this->assertSame($extractor, $result);
		$this->assertTrue($extractor->getRollbackOnFailure());
	}

	public function testUnwindOnFailureRemovesExtractedFilesOnError()
	{
		// Archive: valid file first, then a device file that will throw in strict mode.
		$tarFile = $this->testDir . '/unwind_test.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('first.txt', 'first file content'),
			$this->createTarEntry('char_dev', '', '3'),   // will throw in strict mode
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);
		$extractor->setRollbackOnFailure(true);

		$threw = false;
		try {
			$extractor->extract($this->extractDir);
		} catch (\Exception $e) {
			$threw = true;
		}

		$this->assertTrue($threw, 'Expected exception from device file in strict mode');
		// first.txt was extracted then unwound.
		$this->assertFileDoesNotExist($this->extractDir . '/first.txt', 'Unwind should have removed extracted file');
	}

	public function testNoUnwindLeavesPartialExtractionOnError()
	{
		$tarFile = $this->testDir . '/no_unwind.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('partial.txt', 'partial content'),
			$this->createTarEntry('char_dev', '', '3'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);
		$extractor->setRollbackOnFailure(false);

		try {
			$extractor->extract($this->extractDir);
		} catch (\Exception $e) {
			// Expected.
		}

		// Without unwind, partial.txt should remain.
		$this->assertFileExists($this->extractDir . '/partial.txt', 'File extracted before failure should remain when unwind is off');
	}

	public function testUnwindOnFailureWithZipSlipThrow()
	{
		// Use zip slip as the trigger in strict mode.
		$tarFile = $this->testDir . '/unwind_slip.tar';
		$safeEntry = $this->createTarEntry('good.txt', 'good');
		$slipHeader = $this->createTarHeader('../evil.txt', 4);
		$evilData = str_pad('evil', 512, "\x00");
		file_put_contents($tarFile, $safeEntry . $slipHeader . $evilData . str_repeat("\x00", 1024));

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(true);
		$extractor->setRollbackOnFailure(true);

		try {
			$extractor->extract($this->extractDir);
		} catch (\Exception $e) {
			// Expected.
		}

		$this->assertFileDoesNotExist($this->extractDir . '/good.txt', 'Unwind should have removed good.txt');
	}

	// ---------------------------------------------------------------------------
	// Group 14: URL simulation (reflection-injected _temp_tarpath)
	// ---------------------------------------------------------------------------

	public function testUrlSimulationExtractPopulatesMap()
	{
		$tarFile = $this->testDir . '/url_sim.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('dir/', '', '5'),
			$this->createTarEntry('dir/url_file.txt', 'url content'),
		]);

		$extractor = new TTarFileExtractor('http://example.com/test.tar');
		$this->injectTempTarPath($extractor, $tarFile);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$map = $extractor->getExtractManifest();
		$this->assertArrayHasKey('dir', $map);
		$this->assertArrayHasKey('dir/url_file.txt', $map);
		$this->assertTrue($map['dir/url_file.txt']['extracted']);
	}

	public function testUrlSimulationScanBeforeExtractReusesFile()
	{
		$tarFile = $this->testDir . '/url_scan_then_extract.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('readme.txt', 'readme'),
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('prefix/subdir/', '', '5'),
			$this->createTarEntry('prefix/subdir/file.txt', 'modified'),
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
		$this->assertArrayHasKey('subdir' . DIRECTORY_SEPARATOR, $map);
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
		$this->writeTar($tarFile, [
			$this->createTarEntry('target.txt', 'data'),
			$this->createTarEntry('link.txt', '', '2', 'target.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$info = $extractor->getManifestInfo('link.txt');
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

	public function testClearSkippedFilesReturnsSelf()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->clearSkippedFiles();
		$this->assertSame($extractor, $result);
	}

	public function testMultipleScansReturnSameMap()
	{
		$tarFile = $this->testDir . '/multi_scan.tar';
		$this->writeTar($tarFile, [$this->createTarEntry('once.txt', 'x')]);

		$extractor = new TTarFileExtractor($tarFile);
		$map1 = $extractor->getManifest();
		$map2 = $extractor->getManifest();  // second call should return cached map

		$this->assertSame($map1, $map2);
	}

	public function testContiguousFileTypeExtractedAsRegularFile()
	{
		// TYPE_CONTIGUOUS ('7') should be treated like a regular file.
		$tarFile = $this->testDir . '/contiguous.tar';
		$this->writeTar($tarFile, [
			$this->createTarEntry('contig.txt', 'contiguous content', '7'),
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
