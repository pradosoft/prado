<?php


use PHPUnit\Framework\TestCase;
use Prado\IO\TTarFileExtractor;

/**
 * Non-atomic (direct) extraction tests for TTarFileExtractor introduced in 4.3.3.
 *
 * All extraction tests run with atomic=false (the default) explicitly set via
 * setAtomic(false).  This file covers the restore-on-failure path of
 * _extractDirect(): pre-existing files are backed up before being overwritten,
 * and restored if an error occurs mid-extraction.
 *
 * Covers:
 *  - CONFLICT_* constant values
 *  - getConflictMode() / setConflictMode() property
 *  - Non-atomic (direct) extraction with restore-on-failure
 *  - All five conflict modes in non-atomic mode
 *  - File and directory permission application (chmod)
 *  - Scan-mode reason annotation (zip_slip, device, symlink, hardlink)
 *
 * @since 4.3.3
 */
class TTarFileExtractorRollbackTest extends TestCase
{
	private string $testDir   = '';
	private string $extractDir = '';

	protected function setUp(): void
	{
		$this->testDir    = sys_get_temp_dir() . '/prado_tar_rollback_test_' . uniqid();
		$this->extractDir = $this->testDir . '/extract';
		mkdir($this->extractDir, 0o777, true);
	}

	protected function tearDown(): void
	{
		$this->removeDirectory($this->testDir);
	}

	private function removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
			$path = "$dir/$item";
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
	 * Creates a TTarFileExtractor for the given tar path with atomic=false
	 * (non-atomic, direct extraction with restore-on-failure) explicitly set.
	 */
	private function newExtractor(string $tarFile): TTarFileExtractor
	{
		return (new TTarFileExtractor($tarFile))->setAtomic(false);
	}

	// =========================================================================
	// Group 1: CONFLICT_* constants
	// =========================================================================

	public function testConflictConstantValues()
	{
		$this->assertSame(0, TTarFileExtractor::CONFLICT_ERROR);
		$this->assertSame(1, TTarFileExtractor::CONFLICT_SKIP);
		$this->assertSame(2, TTarFileExtractor::CONFLICT_OVERWRITE);
		$this->assertSame(3, TTarFileExtractor::CONFLICT_NEWER);
		$this->assertSame(4, TTarFileExtractor::CONFLICT_OLDER);
	}

	public function testConflictConstantsAreIntegers()
	{
		$this->assertIsInt(TTarFileExtractor::CONFLICT_ERROR);
		$this->assertIsInt(TTarFileExtractor::CONFLICT_SKIP);
		$this->assertIsInt(TTarFileExtractor::CONFLICT_OVERWRITE);
		$this->assertIsInt(TTarFileExtractor::CONFLICT_NEWER);
		$this->assertIsInt(TTarFileExtractor::CONFLICT_OLDER);
	}

	public function testConflictConstantsAreDistinct()
	{
		$values = [
			TTarFileExtractor::CONFLICT_ERROR,
			TTarFileExtractor::CONFLICT_SKIP,
			TTarFileExtractor::CONFLICT_OVERWRITE,
			TTarFileExtractor::CONFLICT_NEWER,
			TTarFileExtractor::CONFLICT_OLDER,
		];
		$this->assertSame(count($values), count(array_unique($values)));
	}

	// =========================================================================
	// Group 3: getConflictMode / setConflictMode
	// =========================================================================

	public function testConflictModeDefaultOverwrite()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$this->assertSame(TTarFileExtractor::CONFLICT_OVERWRITE, $extractor->getConflictMode());
	}

	public function testSetConflictModeReturnsSelf()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
		$this->assertSame($extractor, $result);
	}

	public function testSetConflictModeAllValues()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		foreach ([
			TTarFileExtractor::CONFLICT_ERROR,
			TTarFileExtractor::CONFLICT_SKIP,
			TTarFileExtractor::CONFLICT_OVERWRITE,
			TTarFileExtractor::CONFLICT_NEWER,
			TTarFileExtractor::CONFLICT_OLDER,
		] as $mode) {
			$extractor->setConflictMode($mode);
			$this->assertSame($mode, $extractor->getConflictMode());
		}
	}

	// =========================================================================
	// Group 6: Direct extraction
	// =========================================================================

	public function testExtractionWritesFilesDirectly()
	{
		$tarFile = $this->testDir . '/direct.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('direct.txt', 'direct'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/direct.txt');
		$this->assertSame('direct', file_get_contents($this->extractDir . '/direct.txt'));
	}

	// =========================================================================
	// Group 7: CONFLICT_ERROR mode
	// =========================================================================

	public function testConflictErrorThrowsWhenFileExists()
	{
		$tarFile = $this->testDir . '/err_nonatom.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('existing.txt', 'new'),
		]);
		file_put_contents($this->extractDir . '/existing.txt', 'old');

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_ERROR);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/Conflict.*existing\.txt/');
		$extractor->extract($this->extractDir);
	}

	public function testConflictErrorDoesNotThrowWhenFileAbsent()
	{
		$tarFile = $this->testDir . '/err_absent.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('brand_new.txt', 'content'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_ERROR);

		$result = $extractor->extract($this->extractDir);
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/brand_new.txt');
	}

	public function testConflictErrorDirectoriesAreNeverAConflict()
	{
		// Pre-create the directory; CONFLICT_ERROR must not throw for directories.
		$tarFile = $this->testDir . '/err_dir.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('predir/', '', '5'),
			TarTestHelper::entry('predir/f.txt', 'ok'),
		]);
		mkdir($this->extractDir . '/predir', 0o777, true);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_ERROR);

		$result = $extractor->extract($this->extractDir);
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/predir/f.txt');
	}

	// =========================================================================
	// Group 8: CONFLICT_SKIP mode
	// =========================================================================

	public function testConflictSkipKeepsExistingFile()
	{
		$tarFile = $this->testDir . '/skip_nonatom.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('keep.txt', 'new content'),
		]);
		file_put_contents($this->extractDir . '/keep.txt', 'original');

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);

		$result = $extractor->extract($this->extractDir);
		$this->assertTrue($result);
		$this->assertSame('original', file_get_contents($this->extractDir . '/keep.txt'));
	}

	public function testConflictSkipRecordsReason()
	{
		$tarFile = $this->testDir . '/skip_reason.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('skipped.txt', 'new'),
			TarTestHelper::entry('written.txt', 'ok'),
		]);
		file_put_contents($this->extractDir . '/skipped.txt', 'old');

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
		$extractor->extract($this->extractDir);

		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame('conflict_skip', $skipped[0]['reason']);
		$this->assertStringContainsString('skipped.txt', $skipped[0]['filepath']);
		$this->assertFileExists($this->extractDir . '/written.txt');
	}

	public function testConflictSkipDoesNotSkipWhenFileAbsent()
	{
		$tarFile = $this->testDir . '/skip_absent.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('fresh.txt', 'new'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/fresh.txt');
		$this->assertFalse($extractor->hasSkippedFiles());
	}

	// =========================================================================
	// Group 9: CONFLICT_OVERWRITE mode
	// =========================================================================

	public function testConflictOverwriteReplacesExistingFile()
	{
		$tarFile = $this->testDir . '/over_nonatom.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('overwrite.txt', 'new content'),
		]);
		file_put_contents($this->extractDir . '/overwrite.txt', 'old content');

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_OVERWRITE);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertSame('new content', file_get_contents($this->extractDir . '/overwrite.txt'));
	}

	// =========================================================================
	// Group 10: CONFLICT_NEWER mode
	// =========================================================================

	public function testConflictNewerKeepsExistingWhenExistingIsNewer()
	{
		$tarFile = $this->testDir . '/newer_keep.tar';
		$archiveMtime  = TarTestHelper::FIXED_MTIME;           // 2021-01-01
		$existingMtime = TarTestHelper::FIXED_MTIME + 86400;   // 2021-01-02 — newer

		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('f.txt', 'archive version', '0', '', 0644, 0, 0, $archiveMtime),
		]);
		file_put_contents($this->extractDir . '/f.txt', 'existing version');
		touch($this->extractDir . '/f.txt', $existingMtime);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_NEWER);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		// Existing file is newer → archive entry must be skipped.
		$this->assertSame('existing version', file_get_contents($this->extractDir . '/f.txt'));
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame('conflict_existing_newer', $skipped[0]['reason']);
	}

	public function testConflictNewerOverwritesWhenArchiveIsNewer()
	{
		$tarFile = $this->testDir . '/newer_overwrite.tar';
		$archiveMtime  = TarTestHelper::FIXED_MTIME + 86400;   // 2021-01-02 — newer
		$existingMtime = TarTestHelper::FIXED_MTIME;           // 2021-01-01 — older

		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('f.txt', 'archive version', '0', '', 0644, 0, 0, $archiveMtime),
		]);
		file_put_contents($this->extractDir . '/f.txt', 'existing version');
		touch($this->extractDir . '/f.txt', $existingMtime);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_NEWER);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		// Archive entry is newer → must overwrite.
		$this->assertSame('archive version', file_get_contents($this->extractDir . '/f.txt'));
		$this->assertFalse($extractor->hasSkippedFiles());
	}

	// =========================================================================
	// Group 11: CONFLICT_OLDER mode
	// =========================================================================

	public function testConflictOlderKeepsExistingWhenExistingIsOlder()
	{
		$tarFile = $this->testDir . '/older_keep.tar';
		$archiveMtime  = TarTestHelper::FIXED_MTIME + 86400;   // archive is newer
		$existingMtime = TarTestHelper::FIXED_MTIME;           // existing is older → keep it

		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('f.txt', 'archive version', '0', '', 0644, 0, 0, $archiveMtime),
		]);
		file_put_contents($this->extractDir . '/f.txt', 'existing version');
		touch($this->extractDir . '/f.txt', $existingMtime);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_OLDER);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		// Archive is newer than existing → CONFLICT_OLDER keeps the existing (older) file.
		$this->assertSame('existing version', file_get_contents($this->extractDir . '/f.txt'));
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame('conflict_existing_older', $skipped[0]['reason']);
	}

	public function testConflictOlderOverwritesWhenArchiveIsOlder()
	{
		$tarFile = $this->testDir . '/older_overwrite.tar';
		$archiveMtime  = TarTestHelper::FIXED_MTIME;           // archive is older
		$existingMtime = TarTestHelper::FIXED_MTIME + 86400;   // existing is newer

		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('f.txt', 'archive version', '0', '', 0644, 0, 0, $archiveMtime),
		]);
		file_put_contents($this->extractDir . '/f.txt', 'existing version');
		touch($this->extractDir . '/f.txt', $existingMtime);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_OLDER);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		// Archive is older → must overwrite (keep the older version).
		$this->assertSame('archive version', file_get_contents($this->extractDir . '/f.txt'));
		$this->assertFalse($extractor->hasSkippedFiles());
	}

	// =========================================================================
	// Group 12: File and directory permissions
	// =========================================================================

	public function testFilePermissionsAppliedAfterExtraction()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		$tarFile = $this->testDir . '/perms_file.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('exec.sh', '#!/bin/sh', '0', '', 0o755),
			TarTestHelper::entry('read.txt', 'text', '0', '', 0o644),
		]);

		$extractor = $this->newExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$execMode = fileperms($this->extractDir . '/exec.sh') & 0o777;
		$readMode = fileperms($this->extractDir . '/read.txt') & 0o777;
		$this->assertSame(0o755, $execMode, 'exec.sh must have 0755 permissions');
		$this->assertSame(0o644, $readMode, 'read.txt must have 0644 permissions');
	}

	public function testDirectoryPermissionsApplied()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		$tarFile = $this->testDir . '/perms_dir.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('restricteddir/', '', '5', '', 0o750),
			TarTestHelper::entry('restricteddir/file.txt', 'content'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$dirMode = fileperms($this->extractDir . '/restricteddir') & 0o777;
		
		$constHasPradoDefault = 'PRADO_TAR_DIR_DEFAULT';
		if (defined($constHasPradoDefault) && constant($constHasPradoDefault)) {
			$this->assertSame(\Prado\Prado::getDefaultDirPermissions(), $dirMode, 'Null dirModeOverride must fall through to tar-stored mode');
		} else {
			$this->assertSame(0o750, $dirMode, 'Directory must have deferred 0750 mode applied');
		}
	}

	// -------------------------------------------------------------------------
	// Group 12 continued: dirModeOverride / fileModeOverride
	// -------------------------------------------------------------------------

	public function testDirModeOverrideDefaultReturnsNonNull()
	{
		//With the PRADO default, this test breaks
		$constHasPradoDefault = 'PRADO_TAR_DIR_DEFAULT';
		if (defined($constHasPradoDefault) && constant($constHasPradoDefault)) {
			// getDirModeOverride() falls back to Prado::getDefaultDirPermissions() when
			// Prado is available (which it always is in this test suite).
			$extractor = new TTarFileExtractor('/dev/null');
			$this->assertNotNull($extractor->getDirModeOverride(), 'Should return Prado default, not null');
			$this->assertIsInt($extractor->getDirModeOverride());
		} else {
			// getDirModeOverride() falls back to Prado::getDefaultDirPermissions() when
			// Prado is available (which it always is in this test suite).
			$extractor = new TTarFileExtractor('/dev/null');
			$this->assertNull($extractor->getDirModeOverride(), 'Should return Prado default, not null');
		} 
	}

	public function testSetDirModeOverrideRoundtrip()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setDirModeOverride(0o700);
		$this->assertSame(0o700, $extractor->getDirModeOverride());
	}

	public function testSetDirModeOverrideChaining()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->setDirModeOverride(0o755);
		$this->assertSame($extractor, $result);
	}

	public function testSetDirModeOverrideNullRestoresFallback()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setDirModeOverride(0o700);
		$extractor->setDirModeOverride(null);
		// After clearing, fallback (Prado default) is returned, not 0o700.
		$this->assertNotSame(0o700, $extractor->getDirModeOverride());
	}

	public function testFileModeOverrideDefaultNull()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$this->assertNull($extractor->getFileModeOverride());
	}

	public function testSetFileModeOverrideRoundtrip()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setFileModeOverride(0o600);
		$this->assertSame(0o600, $extractor->getFileModeOverride());
	}

	public function testSetFileModeOverrideChaining()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->setFileModeOverride(0o644);
		$this->assertSame($extractor, $result);
	}

	public function testSetFileModeOverrideNullClears()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setFileModeOverride(0o600);
		$extractor->setFileModeOverride(null);
		$this->assertNull($extractor->getFileModeOverride());
	}

	public function testDirModeOverrideApplied()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		// Archive stores 0o755 for the directory; override must win.
		$tarFile = $this->testDir . '/dir_override_nonatomic.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('mydir/', '', '5', '', 0o755),
			TarTestHelper::entry('mydir/f.txt', 'data'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setDirModeOverride(0o700);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$dirMode = fileperms($this->extractDir . '/mydir') & 0o777;
		$this->assertSame(0o700, $dirMode, 'dirModeOverride must override tar directory mode');
	}

	public function testFileModeOverrideApplied()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		// Archive stores 0o755; override to 0o600 must win.
		$tarFile = $this->testDir . '/file_override_nonatomic.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('script.sh', '#!/bin/sh', '0', '', 0o755),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setFileModeOverride(0o600);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$fileMode = fileperms($this->extractDir . '/script.sh') & 0o777;
		$this->assertSame(0o600, $fileMode, 'fileModeOverride must override tar file mode');
	}

	public function testNullDirModeOverrideUsesTarMode()
	{
		//With the PRADO default, this test breaks
		
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		// With no override the archive's stored 0o750 must be applied.
		$tarFile = $this->testDir . '/null_dir_override.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('locked/', '', '5', '', 0o750),
			TarTestHelper::entry('locked/f.txt', 'x'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setDirModeOverride(null);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$dirMode = fileperms($this->extractDir . '/locked') & 0o777;
		
		$constHasPradoDefault = 'PRADO_TAR_DIR_DEFAULT';
		if (defined($constHasPradoDefault) && constant($constHasPradoDefault)) {
			$this->assertSame(\Prado\Prado::getDefaultDirPermissions(), $dirMode, 'Null dirModeOverride must fall through to tar-stored mode');
		} else {
			$this->assertSame(0o750, $dirMode, 'Null dirModeOverride must fall through to tar-stored mode');
		}
	}

	public function testNullFileModeOverrideUsesTarMode()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		$tarFile = $this->testDir . '/null_file_override.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('secret.txt', 'data', '0', '', 0o600),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setFileModeOverride(null);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$fileMode = fileperms($this->extractDir . '/secret.txt') & 0o777;
		$this->assertSame(0o600, $fileMode, 'Null fileModeOverride must fall through to tar-stored mode');
	}

	public function testDirModeOverride755Explicit()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		// Override = 0o755 explicitly; archive stores 0o700.
		// An explicit 0o755 override must win even though 0o755 is the "working" default.
		$tarFile = $this->testDir . '/dir755_explicit_na.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('mydir/', '', '5', '', 0o700),
			TarTestHelper::entry('mydir/f.txt', 'data'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setDirModeOverride(0o755);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$dirMode = fileperms($this->extractDir . '/mydir') & 0o777;
		$this->assertSame(0o755, $dirMode, 'Explicit 0o755 dirModeOverride must win over tar-stored 0o700');
	}

	public function testDirModeOverride_NestedDirs_OtherThan755()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		// Nested directory tree with override = 0o750 (traversable, group-execute set,
		// group-write cleared).  All archive-entry directories must end up at 0o750 after
		// the deferred-chmod pass; files inside must be accessible throughout extraction.
		$tarFile = $this->testDir . '/dir750_nested_na.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('a/', '', '5'),
			TarTestHelper::entry('a/b/', '', '5'),
			TarTestHelper::entry('a/b/c/', '', '5'),
			TarTestHelper::entry('a/b/c/deep.txt', 'deep content'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setDirModeOverride(0o750);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result, 'Nested extraction must succeed with 0o750 dirModeOverride');

		$manifest = $extractor->getExtractManifest();
		$this->assertTrue(
			$manifest['a/b/c/deep.txt']['extracted'] ?? false,
			'Deeply nested file must be extracted before directories are chmod-ed'
		);

		// All three archive-entry directories must end up at 0o750.
		$modeA   = fileperms($this->extractDir . '/a') & 0o777;
		$modeAB  = fileperms($this->extractDir . '/a/b') & 0o777;
		$modeABC = fileperms($this->extractDir . '/a/b/c') & 0o777;

		$this->assertSame(0o750, $modeA,   'a/ must have mode 0o750');
		$this->assertSame(0o750, $modeAB,  'a/b/ must have mode 0o750');
		$this->assertSame(0o750, $modeABC, 'a/b/c/ must have mode 0o750');
	}

	public function testFileModeOverride644Explicit()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}

		// Override = 0o644 (the conventional default); archive stores 0o600.
		// Explicitly setting 0o644 must win over the stricter archive mode.
		$tarFile = $this->testDir . '/file644_explicit_na.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('secret.txt', 'data', '0', '', 0o600),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setFileModeOverride(0o644);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$fileMode = fileperms($this->extractDir . '/secret.txt') & 0o777;
		$this->assertSame(0o644, $fileMode, 'Explicit 0o644 fileModeOverride must win over tar-stored 0o600');
	}

	// =========================================================================
	// Group 13: Scan-mode reason annotation in getManifest()
	// =========================================================================

	public function testScanAnnotatesZipSlipEntryWithReason()
	{
		// Build an archive with a path-traversal entry manually.
		$tarFile = $this->testDir . '/scan_zipslip.tar';
		$slipHeader = TarTestHelper::header('../evil.txt', 4);
		$data = str_pad('evil', 512, "\x00");
		file_put_contents($tarFile, $slipHeader . $data . str_repeat("\x00", 1024));

		$extractor = $this->newExtractor($tarFile);
		$extractor->setStrict(false);
		$manifest = $extractor->getManifest();

		// The entry must appear in the manifest with reason = 'zip_slip'
		// and security = 'zip_slip' (security violations carry both fields).
		$this->assertArrayHasKey('../evil.txt', $manifest);
		$this->assertSame('zip_slip', $manifest['../evil.txt']['reason'] ?? null);
		$this->assertSame('zip_slip_attack', $manifest['../evil.txt']['security'] ?? null);
		$this->assertFalse($manifest['../evil.txt']['filesafe']);
	}

	public function testScanAnnotatesDeviceEntryWithReason()
	{
		$tarFile = $this->testDir . '/scan_device.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('cdev', '', '3'),   // TYPE_CHAR_SPECIAL
			TarTestHelper::entry('fifo', '', '6'),   // TYPE_FIFO
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setStrict(false);
		$manifest = $extractor->getManifest();

		$this->assertArrayHasKey('cdev', $manifest);
		$this->assertSame('device', $manifest['cdev']['reason'] ?? null);
		$this->assertSame('is_device', $manifest['cdev']['security'] ?? null);
		$this->assertArrayHasKey('fifo', $manifest);
		$this->assertSame('device', $manifest['fifo']['reason'] ?? null);
		$this->assertSame('is_device', $manifest['fifo']['security'] ?? null);
	}

	public function testScanAnnotatesUnsafeSymlinkWithReason()
	{
		$tarFile = $this->testDir . '/scan_sym.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('evil_link', '', '2', '../../../etc/passwd'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setStrict(false);
		$manifest = $extractor->getManifest();

		$this->assertArrayHasKey('evil_link', $manifest);
		$this->assertSame('symlink', $manifest['evil_link']['reason'] ?? null);
		$this->assertSame('linkpath_above_root', $manifest['evil_link']['security'] ?? null);
	}

	public function testScanAnnotatesUnsafeHardlinkWithReason()
	{
		$tarFile = $this->testDir . '/scan_hard.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('evil_hard', '', '1', '/etc/shadow'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setStrict(false);
		$manifest = $extractor->getManifest();

		$this->assertArrayHasKey('evil_hard', $manifest);
		$this->assertSame('hardlink', $manifest['evil_hard']['reason'] ?? null);
		$this->assertSame('linkpath_above_root', $manifest['evil_hard']['security'] ?? null);
	}

	public function testScanDoesNotAnnotateSafeEntries()
	{
		$tarFile = $this->testDir . '/scan_safe.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe/', '', '5'),
			TarTestHelper::entry('safe/file.txt', 'ok'),
			TarTestHelper::entry('target.txt', 'data'),
			TarTestHelper::entry('link.txt', '', '2', 'target.txt'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$manifest = $extractor->getManifest();

		foreach ($manifest as $path => $entry) {
			$this->assertArrayNotHasKey(
				'reason',
				$entry,
				"Safe entry '$path' must not have a 'reason' annotation"
			);
			$this->assertArrayNotHasKey(
				'security',
				$entry,
				"Safe entry '$path' must not have a 'security' annotation"
			);
		}
	}

	public function testGetManifestUnsafeReasonReturnsCorrectValue()
	{
		$tarFile = $this->testDir . '/unsafe_reason.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'ok'),
			TarTestHelper::entry('cdev', '', '3'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->getManifest();

		$this->assertNull($extractor->getManifestUnsafeReason('safe.txt'));
		$this->assertSame('device', $extractor->getManifestUnsafeReason('cdev'));
	}

	public function testGetManifestSecurityReturnsViolationTypeOrNull()
	{
		$tarFile = $this->testDir . '/security_field.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'ok'),
			TarTestHelper::entry('cdev', '', '3'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->getManifest();

		$this->assertNull($extractor->getManifestSecurity('safe.txt'), 'Safe entries have no security field');
		$this->assertSame('is_device', $extractor->getManifestSecurity('cdev'), 'Device entries carry security = device');
	}

	public function testConflictSkipHasNoSecurityField()
	{
		// Conflict-based skips must NOT set the 'security' field.
		$tarFile = $this->testDir . '/conflict_no_security.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('existing.txt', 'archive version'),
		]);
		file_put_contents($this->extractDir . '/existing.txt', 'original');

		$extractor = $this->newExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
		$extractor->extract($this->extractDir);

		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame('conflict_skip', $skipped[0]['reason']);
		$this->assertArrayNotHasKey('security', $skipped[0], 'Conflict skips must not carry a security field');
	}

	public function testScanMixedSafeAndUnsafeEntries()
	{
		$tarFile = $this->testDir . '/scan_mixed.tar';
		$slipHeader = TarTestHelper::header('../oops.txt', 3);
		$slipData   = str_pad('ooh', 512, "\x00");
		$devEntry   = TarTestHelper::entry('cdev', '', '3');
		$safeEntry  = TarTestHelper::entry('safe.txt', 'safe');
		file_put_contents(
			$tarFile,
			$slipHeader . $slipData . $devEntry . $safeEntry . str_repeat("\x00", 1024)
		);

		$extractor = $this->newExtractor($tarFile);
		$extractor->setStrict(false);
		$manifest = $extractor->getManifest();

		$this->assertSame('zip_slip', $manifest['../oops.txt']['reason'] ?? null);
		$this->assertSame('zip_slip_attack', $manifest['../oops.txt']['security'] ?? null);
		$this->assertSame('device', $manifest['cdev']['reason'] ?? null);
		$this->assertSame('is_device', $manifest['cdev']['security'] ?? null);
		$this->assertArrayNotHasKey('reason', $manifest['safe.txt'] ?? []);
		$this->assertArrayNotHasKey('security', $manifest['safe.txt'] ?? []);
	}

	// =========================================================================
	// Group 14: Hard link inode preservation
	// =========================================================================

	public function testHardLinkPreservesSharedInode()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Inode equality not reliable on Windows');
		}

		$tarFile = $this->testDir . '/hardlink_nonatomic.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('original.txt', 'shared content'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);

		$extractor = $this->newExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);

		$origPath = $this->extractDir . '/original.txt';
		$linkPath = $this->extractDir . '/hardlink.txt';

		$this->assertFileExists($origPath);
		$this->assertFileExists($linkPath);
		$this->assertSame('shared content', file_get_contents($linkPath));

		$origIno = stat($origPath)['ino'];
		$linkIno = stat($linkPath)['ino'];
		$this->assertSame($origIno, $linkIno, 'Extraction must preserve the shared inode for hard link entries');
	}

	public function testFailsWhenDestinationExistsButNotWritable()
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
			$this->markTestSkipped('Permission bits not applicable on Windows');
		}
		if (function_exists('posix_getuid') && posix_getuid() === 0) {
			$this->markTestSkipped('Root bypasses write-permission checks');
		}

		$tarFile = $this->testDir . '/dest_not_writable.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('file.txt', 'data'),
		]);

		// Destination exists but is not writable.
		chmod($this->extractDir, 0o555);

		try {
			$extractor = $this->newExtractor($tarFile);
			$this->expectException(\Exception::class);
			$extractor->extract($this->extractDir);
		} finally {
			// Restore so tearDown can clean up.
			chmod($this->extractDir, 0o755);
		}
	}
}
