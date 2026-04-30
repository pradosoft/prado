<?php


use PHPUnit\Framework\TestCase;
use Prado\IO\TTarFileExtractor;

class TTarFileExtractorTest extends TestCase
{
	private string $testDir = '';
	private string $extractDir = '';

	protected function setUp(): void
	{
		$this->testDir = sys_get_temp_dir() . '/prado_tar_test_' . uniqid();
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

	public function testConstruct()
	{
		$tarFile = $this->testDir . '/test.tar';
		$extractor = new TTarFileExtractor($tarFile);
		$this->assertInstanceOf(TTarFileExtractor::class, $extractor);
	}

	public function testConstructWithEmptyPath()
	{
		$extractor = new TTarFileExtractor('');
		$this->assertInstanceOf(TTarFileExtractor::class, $extractor);
	}

	public function testDestruct()
	{
		$tarFile = $this->testDir . '/test.tar';
		$extractor = new TTarFileExtractor($tarFile);
		unset($extractor);
		$this->assertTrue(true);
	}

	public function testExtractEmptyTar()
	{
		$tarFile = $this->testDir . '/empty.tar';
		$fp = fopen($tarFile, 'wb');
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);

		unlink($tarFile);
	}

	public function testExtractSingleFile()
	{
		$tarFile = $this->testDir . '/single.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('test.txt', 'Hello World'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/test.txt');
		$this->assertEquals('Hello World', file_get_contents($this->extractDir . '/test.txt'));

		unlink($tarFile);
	}

	public function testExtractMultipleFiles()
	{
		$tarFile = $this->testDir . '/multiple.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('file1.txt', 'Content 1'),
			TarTestHelper::entry('file2.txt', 'Content 2'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/file1.txt');
		$this->assertFileExists($this->extractDir . '/file2.txt');
		$this->assertEquals('Content 1', file_get_contents($this->extractDir . '/file1.txt'));
		$this->assertEquals('Content 2', file_get_contents($this->extractDir . '/file2.txt'));

		unlink($tarFile);
	}

	public function testExtractDirectory()
	{
		$tarFile = $this->testDir . '/with_dir.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('subdir/nested.txt', 'Nested content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/subdir');
		$this->assertFileExists($this->extractDir . '/subdir/nested.txt');
		$this->assertEquals('Nested content', file_get_contents($this->extractDir . '/subdir/nested.txt'));

		unlink($tarFile);
	}

	public function testExtractNonExistentTarThrowsException()
	{
		$tarFile = $this->testDir . '/nonexistent.tar';

		$extractor = new TTarFileExtractor($tarFile);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Unable to open in read binary mode');

		$extractor->extract($this->extractDir);
	}

	public function testExtractPreservesTimestamp()
	{
		$tarFile = $this->testDir . '/timestamp.tar';
		$expectedMtime = time() - 3600;
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('timed.txt', 'Timestamp content', '0', '', 0644, 0, 0, $expectedMtime),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/timed.txt');
		$actualMtime = filemtime($this->extractDir . '/timed.txt');
		$this->assertEquals($expectedMtime, $actualMtime);

		unlink($tarFile);
	}

	public function testExtractWithCorruptedChecksumThrowsException()
	{
		$tarFile = $this->testDir . '/corrupted.tar';
		$fp = fopen($tarFile, 'wb');

		$header = pack(
			"a100a8a8a8a12a12a8",
			"corrupted.txt",
			"0000644",
			"0000000",
			"0000000",
			sprintf("%011o", 5),
			sprintf("%011o", time()),
			"        "
		);
		$header .= pack("a1", "0");
		$header .= pack("a100", "");
		$header .= pack("a6", "ustar");
		$header .= pack("a2", "00");
		$header .= pack("a32", "root");
		$header .= pack("a32", "root");
		$header .= pack("a8a8", "", "");
		$header .= pack("a155", "");
		$header .= pack("a12", "");
		$header = str_pad($header, 512, "\0");

		$checksum = 0;
		for ($i = 0; $i < 512; $i++) {
			$checksum += ord($header[$i]);
		}
		$header = substr($header, 0, 148) . sprintf("%06o ", $checksum + 1) . substr($header, 155);

		fwrite($fp, $header);
		fwrite($fp, str_repeat("\0", 512));
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);

		$extractor = new TTarFileExtractor($tarFile);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid checksum');

		$extractor->extract($this->extractDir);
	}

	public function testExtractSkipsEmptyFilename()
	{
		$tarFile = $this->testDir . '/empty_name.tar';
		$fp = fopen($tarFile, 'wb');
		fwrite($fp, TarTestHelper::header('', 10, '0'));
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);

		unlink($tarFile);
	}

	public function testExtractWithSpecialCharactersInFilename()
	{
		$tarFile = $this->testDir . '/special.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('file with spaces.txt', 'Special content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/file with spaces.txt');
		$this->assertEquals('Special content', file_get_contents($this->extractDir . '/file with spaces.txt'));

		unlink($tarFile);
	}

	public function testExtractMultipleBlocks()
	{
		$tarFile = $this->testDir . '/blocks.tar';
		$content = str_repeat('A', 1536);
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('large.txt', $content),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/large.txt');
		$this->assertEquals($content, file_get_contents($this->extractDir . '/large.txt'));

		unlink($tarFile);
	}

	public function testExtractBinaryContent()
	{
		$tarFile = $this->testDir . '/binary.tar';
		$binaryContent = pack("c*", 0x00, 0x01, 0x02, 0xFF, 0xFE, 0xFD);
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('binary.dat', $binaryContent),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/binary.dat');
		$this->assertEquals($binaryContent, file_get_contents($this->extractDir . '/binary.dat'));

		unlink($tarFile);
	}

	public function testExtractSymlinkWithinDestination()
	{
		$tarFile = $this->testDir . '/safe_symlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('target.txt', 'Target content'),
			TarTestHelper::entry('link.txt', '', '2', 'target.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/target.txt');
		$this->assertFileExists($this->extractDir . '/link.txt');
		$this->assertTrue(is_link($this->extractDir . '/link.txt'));
		$this->assertEquals('Target content', file_get_contents($this->extractDir . '/link.txt'));

		unlink($tarFile);
	}

	public function testExtractSymlinkWithRelativePathWithinDestination()
	{
		$tarFile = $this->testDir . '/relative_symlink.tar';

		mkdir($this->extractDir . '/subdir', 0o777, true);
		mkdir($this->extractDir . '/subdir/linktarget', 0o777, true);
		file_put_contents($this->extractDir . '/subdir/linktarget/file.txt', 'Content');

		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/mylink', '', '2', 'linktarget/file.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/subdir/mylink');
		$this->assertTrue(is_link($this->extractDir . '/subdir/mylink'));
		$this->assertStringContainsString('linktarget', readlink($this->extractDir . '/subdir/mylink'));

		unlink($tarFile);
	}

	public function testExtractHardLinkWithinDestination()
	{
		$tarFile = $this->testDir . '/safe_hardlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('original.txt', 'Original content'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/original.txt');
		$this->assertFileExists($this->extractDir . '/hardlink.txt');
		$this->assertEquals('Original content', file_get_contents($this->extractDir . '/hardlink.txt'));

		unlink($tarFile);
	}

	public function testExtractBlockedZipSlipPathTraversal()
	{
		$tarFile = $this->testDir . '/zipslip.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/../../../malicious.txt", 'Malicious content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Zip Slip path traversal attempt detected');

		$extractor->extract($this->extractDir);
	}

	public function testExtractBlockedSymlinkWithAbsolutePath()
	{
		$tarFile = $this->testDir . '/symlink_absolute.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("mylink", '', '2', '/etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Symlink target outside extraction directory');

		$extractor->extract($this->extractDir);
	}

	public function testExtractBlockedHardLinkWithAbsolutePath()
	{
		$tarFile = $this->testDir . '/hardlink_absolute.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("mylink", '', '1', '/etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Hard link target outside extraction directory');

		$extractor->extract($this->extractDir);
	}

	public function testExtractBlockedSymlinkWithRelativePathTraversal()
	{
		$tarFile = $this->testDir . '/symlink_traversal.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/../../../mylink", '', '2', '../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/(Zip Slip path traversal|Symlink target outside)/');

		$extractor->extract($this->extractDir);
	}

	public function testGetStrictDefaultValue()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$this->assertTrue($extractor->getStrict());
	}

	public function testSetStrict()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$result = $extractor->setStrict(false);
		$this->assertSame($extractor, $result);
		$this->assertFalse($extractor->getStrict());
	}

	public function testSetStrictTrue()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$extractor->setStrict(false);
		$extractor->setStrict(true);
		$this->assertTrue($extractor->getStrict());
	}

	public function testHasSkippedFilesDefaultFalse()
	{
		$tarFile = $this->testDir . '/safe.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'Safe content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$this->assertFalse($extractor->hasSkippedFiles());

		unlink($tarFile);
	}

	public function testGetSkippedFilesDefaultEmpty()
	{
		$tarFile = $this->testDir . '/safe.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'Safe content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$this->assertEquals([], $extractor->getSkippedFiles());

		unlink($tarFile);
	}

	public function testClearSkippedFiles()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$this->assertFalse($extractor->hasSkippedFiles());
		$this->assertEquals([], $extractor->getSkippedFiles());
	}

	public function testStrictTrueThrowsOnZipSlip()
	{
		$tarFile = $this->testDir . '/zipslip.tar';
		$this->createZipSlipTar($tarFile);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertTrue($extractor->getStrict());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Zip Slip path traversal attempt detected');

		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testStrictFalseSkipsZipSlipAndContinues()
	{
		$tarFile = $this->testDir . '/zipslip.tar';
		$this->createZipSlipTar($tarFile);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertEquals('zip_slip', $skipped[0]['reason']);
		$this->assertStringContainsString('malicious.txt', $skipped[0]['filepath']);
		$this->assertSame('', $skipped[0]['linkpath'] ?? '');

		unlink($tarFile);
	}

	public function testStrictTrueThrowsOnSymlinkOutside()
	{
		$tarFile = $this->testDir . '/symlink.tar';
		$this->createSymlinkOutsideTar($tarFile);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertTrue($extractor->getStrict());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Symlink target outside extraction directory');

		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testStrictFalseSkipsSymlinkAndContinues()
	{
		$tarFile = $this->testDir . '/symlink.tar';
		$this->createSymlinkOutsideTar($tarFile);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertEquals('symlink', $skipped[0]['reason']);
		$this->assertStringContainsString('mylink', $skipped[0]['filepath']);
		$this->assertEquals('/etc/passwd', $skipped[0]['linkpath']);

		unlink($tarFile);
	}

	public function testStrictTrueThrowsOnHardLinkOutside()
	{
		$tarFile = $this->testDir . '/hardlink.tar';
		$this->createHardLinkOutsideTar($tarFile);

		$extractor = new TTarFileExtractor($tarFile);
		$this->assertTrue($extractor->getStrict());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Hard link target outside extraction directory');

		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testStrictFalseSkipsHardLinkAndContinues()
	{
		$tarFile = $this->testDir . '/hardlink.tar';
		$this->createHardLinkOutsideTar($tarFile);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertEquals('hardlink', $skipped[0]['reason']);
		$this->assertEquals('linkpath_above_root', $skipped[0]['security']);
		$this->assertStringContainsString('mylink', $skipped[0]['filepath']);
		$this->assertEquals('/etc/passwd', $skipped[0]['linkpath']);

		unlink($tarFile);
	}

	public function testStrictFalseWithMultipleSecurityIssues()
	{
		$tarFile = $this->testDir . '/multi_malicious.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'Safe content'),
			TarTestHelper::entry("subdir/../../../malicious.txt", 'Malicious!'),
			TarTestHelper::entry("link_outside", '', '2', '/etc/passwd'),
			TarTestHelper::entry('safe2.txt', 'Another safe'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/safe.txt');
		$this->assertFileExists($this->extractDir . '/safe2.txt');
		$this->assertFileDoesNotExist($this->extractDir . '/malicious.txt');

		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = $extractor->getSkippedFiles();
		$this->assertCount(2, $skipped);

		unlink($tarFile);
	}

	public function testStrictFalseWithSymlinkRelativePathTraversal()
	{
		$tarFile = $this->testDir . '/symlink_rel.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/mylink", '', '2', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertEquals('symlink', $skipped[0]['reason']);
		$this->assertEquals('linkpath_above_root', $skipped[0]['security']);

		unlink($tarFile);
	}

	public function testStrictFalseWithHardLinkRelativePathTraversal()
	{
		$tarFile = $this->testDir . '/hardlink_rel.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/mylink", '', '1', '../../../etc/passwd'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertEquals('hardlink', $skipped[0]['reason']);
		$this->assertEquals('linkpath_above_root', $skipped[0]['security']);

		unlink($tarFile);
	}

	private function createZipSlipTar(string $tarFile): void
	{
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/../../../malicious.txt", 'Malicious content'),
		]);
	}

	private function createSymlinkOutsideTar(string $tarFile): void
	{
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("mylink", '', '2', '/etc/passwd'),
		]);
	}

	private function createHardLinkOutsideTar(string $tarFile): void
	{
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("mylink", '', '1', '/etc/passwd'),
		]);
	}

	public function testGetUrlTimeoutDefaultValue()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$this->assertEquals(6.0, $extractor->getUrlTimeout());
	}

	public function testSetUrlTimeout()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$result = $extractor->setUrlTimeout(10.5);
		$this->assertSame($extractor, $result);
		$this->assertEquals(10.5, $extractor->getUrlTimeout());
	}

	public function testSetUrlTimeoutToZero()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$extractor->setUrlTimeout(0.0);
		$this->assertEquals(0.0, $extractor->getUrlTimeout());
	}

	public function testSetUrlTimeoutToSmallValue()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$extractor->setUrlTimeout(0.1);
		$this->assertEquals(0.1, $extractor->getUrlTimeout());
	}

	public function testSetUrlTimeoutToLargeValue()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$extractor->setUrlTimeout(30.0);
		$this->assertEquals(30.0, $extractor->getUrlTimeout());
	}

	public function testSetUrlTimeoutChainedCalls()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$this->assertTrue($extractor->getStrict());
		$extractor->setUrlTimeout(15.0)
			->setStrict(false);
		$this->assertEquals(15.0, $extractor->getUrlTimeout());
		$this->assertFalse($extractor->getStrict());
	}

	// =========================================================================
	// getExceptionClass / setExceptionClass / _error dispatch
	// =========================================================================

	public function testExceptionClassDefault()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$this->assertSame('\Exception', $extractor->getExceptionClass());
	}

	public function testSetExceptionClassStores()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setExceptionClass('\RuntimeException');
		$this->assertSame('\RuntimeException', $extractor->getExceptionClass());
	}

	public function testSetExceptionClassChaining()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->setExceptionClass('\LogicException');
		$this->assertSame($extractor, $result);
	}

	public function testSetExceptionClassEmptyStringIsStored()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setExceptionClass('\RuntimeException');
		$extractor->setExceptionClass('');
		// Empty string is stored as-is; _error falls back to \Exception at throw time.
		$this->assertSame(TTarFileExtractor::DEFAULT_EXCEPTION_CLASS, $extractor->getExceptionClass());
	}

	public function testErrorThrowsConfiguredExceptionClass()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setExceptionClass('\RuntimeException');

		$method = new \ReflectionMethod($extractor, '_error');
		$method->setAccessible(true);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('test error message');
		$method->invoke($extractor, 'test error message');
	}

	public function testErrorFallsBackToExceptionForUnknownClass()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setExceptionClass('\NoSuchClassDefinedAnywhere');

		$method = new \ReflectionMethod($extractor, '_error');
		$method->setAccessible(true);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('fallback message');
		$method->invoke($extractor, 'fallback message');
	}

	public function testErrorFallsBackToExceptionForEmptyClass()
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setExceptionClass('');

		$method = new \ReflectionMethod($extractor, '_error');
		$method->setAccessible(true);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('empty class fallback');
		$method->invoke($extractor, 'empty class fallback');
	}

	public function testErrorDefaultClassIsException()
	{
		$extractor = new TTarFileExtractor('/dev/null');

		$method = new \ReflectionMethod($extractor, '_error');
		$method->setAccessible(true);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('default exception');
		$method->invoke($extractor, 'default exception');
	}

	// =========================================================================
	// _normalizePath
	// =========================================================================

	private function normalizePath(string $path): ?string
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$method = new \ReflectionMethod($extractor, '_normalizePath');
		$method->setAccessible(true);
		return $method->invoke($extractor, $path);
	}

	/**
	 * @dataProvider normalizePathValidProvider
	 */
	public function testNormalizePath(string $input, ?string $expected): void
	{
		$this->assertSame($expected, $this->normalizePath($input));
	}

	public static function normalizePathValidProvider(): array
	{
		return [
			// Empty / current dir
			[''          , '.'],
			['.'         , '.'],
			['./'        , '.'],
			['././.'     , '.'],

			// Simple relative
			['foo'       , 'foo'],
			['foo/bar'   , 'foo/bar'],
			['foo//bar'  , 'foo/bar'],
			['foo/./bar' , 'foo/bar'],

			// Relative with ..
			['foo/..'        , '.'],
			['foo/bar/..'    , 'foo'],
			['foo/bar/../baz', 'foo/baz'],
			['a/b/c/../../d' , 'a/d'],

			// Leading ..
			['..'            , '..'],
			['../'           , '..'],
			['../a'          , '../a'],
			['../a/..'       , '..'],
			['../../a'       , '../../a'],
			['a/../../b'     , '../b'],
			['a/b/../../../c', '../c'],

			// Absolute basics
			['/'             , '/'],
			['//'            , '/'],
			['/.'            , '/'],
			['/././'         , '/'],
			['/foo'          , '/foo'],
			['/foo/bar'      , '/foo/bar'],
			['/foo//bar'     , '/foo/bar'],
			['/foo/./bar'    , '/foo/bar'],

			// Absolute with ..
			['/foo/..'          , '/'],
			['/foo/bar/..'      , '/foo'],
			['/foo/bar/../baz'  , '/foo/baz'],
			['/a/b/c/../../d'   , '/a/d'],

			// Preserve names that merely contain dots
			['file..txt'        , 'file..txt'],
			['a.../b'           , 'a.../b'],
			['..hidden/file'    , '..hidden/file'],
		];
	}

	/**
	 * @dataProvider normalizePathTraversalAboveRootProvider
	 */
	public function testNormalizePathRejectsTraversalAboveAbsoluteRoot(string $input): void
	{
		$this->assertNull($this->normalizePath($input));
	}

	public static function normalizePathTraversalAboveRootProvider(): array
	{
		return [
			['/..'],
			['/../'],
			['/../../a'],
			['/foo/../../bar'],
			['/a/b/../../../c'],
		];
	}

	// =========================================================================
	// getConflictModeFunction — built-in CONFLICT_* constants
	// =========================================================================

	/** @return array<string,array{int,string}> */
	public static function conflictModeCallableProvider(): array
	{
		return [
			'CONFLICT_ERROR'     => [TTarFileExtractor::CONFLICT_ERROR,     'resolveConflictError'],
			'CONFLICT_SKIP'      => [TTarFileExtractor::CONFLICT_SKIP,      'resolveConflictSkipTar'],
			'CONFLICT_OVERWRITE' => [TTarFileExtractor::CONFLICT_OVERWRITE, 'resolveConflictOverwriteExisting'],
			'CONFLICT_NEWER'     => [TTarFileExtractor::CONFLICT_NEWER,     'resolveConflictNewer'],
			'CONFLICT_OLDER'     => [TTarFileExtractor::CONFLICT_OLDER,     'resolveConflictOlder'],
		];
	}

	/**
	 * @dataProvider conflictModeCallableProvider
	 */
	public function testGetConflictModeFunctionReturnsBuiltInMethod(int $mode, string $methodName): void
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setConflictMode($mode);

		$fn = new \ReflectionMethod($extractor, 'getConflictModeFunction');
		$fn->setAccessible(true);
		$callable = $fn->invoke($extractor);

		$this->assertIsArray($callable);
		$this->assertSame($extractor, $callable[0]);
		$this->assertSame($methodName, $callable[1]);
	}

	public function testGetConflictModeFunctionForUserCallableReturnsWrappedClosure(): void
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$userCallable = static function (array $entry, string $path, ?string &$reason): bool {
			return true;
		};
		$extractor->setConflictMode($userCallable);

		$fn = new \ReflectionMethod($extractor, 'getConflictModeFunction');
		$fn->setAccessible(true);
		$callable = $fn->invoke($extractor);

		$this->assertInstanceOf(\Closure::class, $callable);
	}

	public function testGetConflictModeFunctionForUnknownValueFallsBackToOverwrite(): void
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$extractor->setConflictMode(999); // not a valid CONFLICT_* constant, not callable

		$fn = new \ReflectionMethod($extractor, 'getConflictModeFunction');
		$fn->setAccessible(true);
		$callable = $fn->invoke($extractor);

		$this->assertIsArray($callable);
		$this->assertSame('resolveConflictOverwriteExisting', $callable[1]);
	}

	// =========================================================================
	// Callable conflict mode — functional extraction tests
	// =========================================================================

	/** Create a tar with one pre-existing file + one archive entry for the same path. */
	private function createConflictTar(string $tarFile, string $archiveContent = 'archive'): void
	{
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('existing.txt', $archiveContent, '0', '', 0o644, 0, 0, time() - 100),
		]);
	}

	public function testCallableConflictModeSkipWithDefaultReason(): void
	{
		$tarFile = $this->testDir . '/callable_skip.tar';
		$this->createConflictTar($tarFile);

		// Pre-create the file so a conflict exists.
		file_put_contents($this->extractDir . '/existing.txt', 'original');

		$extractor = new TTarFileExtractor($tarFile);
		// Callable always returns false (skip), leaves $reason unset → default reason applied.
		$extractor->setConflictMode(static function (array $entry, string $path, ?string &$reason): bool {
			return false;
		});

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		// Original content must be preserved.
		$this->assertSame('original', file_get_contents($this->extractDir . '/existing.txt'));

		// The skipped entry should appear in the extract manifest with the default reason.
		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame(TTarFileExtractor::REASON_CONFLICT_CALLABLE_SKIP, $skipped[0]['reason']);

		unlink($tarFile);
	}

	public function testCallableConflictModeSkipWithCustomReason(): void
	{
		$tarFile = $this->testDir . '/callable_custom.tar';
		$this->createConflictTar($tarFile);

		file_put_contents($this->extractDir . '/existing.txt', 'original');

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setConflictMode(static function (array $entry, string $path, ?string &$reason): bool {
			$reason = 'my_custom_skip';
			return false;
		});

		$extractor->extract($this->extractDir);

		$this->assertSame('original', file_get_contents($this->extractDir . '/existing.txt'));

		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame('my_custom_skip', $skipped[0]['reason']);

		unlink($tarFile);
	}

	public function testCallableConflictModeOverwrite(): void
	{
		$tarFile = $this->testDir . '/callable_overwrite.tar';
		$this->createConflictTar($tarFile, 'from archive');

		file_put_contents($this->extractDir . '/existing.txt', 'original');

		$extractor = new TTarFileExtractor($tarFile);
		// Callable returns true → always overwrite.
		$extractor->setConflictMode(static function (array $entry, string $path, ?string &$reason): bool {
			return true;
		});

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertSame('from archive', file_get_contents($this->extractDir . '/existing.txt'));
		$this->assertFalse($extractor->hasSkippedFiles());

		unlink($tarFile);
	}

	public function testCallableConflictModeReceivesCorrectArguments(): void
	{
		$tarFile = $this->testDir . '/callable_args.tar';
		$archiveMtime = time() - 500;
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('myfile.txt', 'content', '0', '', 0o644, 0, 0, $archiveMtime),
		]);

		file_put_contents($this->extractDir . '/myfile.txt', 'existing');

		$capturedEntry = null;
		$capturedPath  = null;

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setConflictMode(function (array $entry, string $path, ?string &$reason) use (&$capturedEntry, &$capturedPath): bool {
			$capturedEntry = $entry;
			$capturedPath  = $path;
			return false;
		});

		$extractor->extract($this->extractDir);

		$this->assertIsArray($capturedEntry);
		$this->assertSame('myfile.txt', $capturedEntry['filepath']);
		$this->assertSame($archiveMtime, (int) $capturedEntry['mtime']);
		$this->assertStringContainsString('myfile.txt', $capturedPath);

		unlink($tarFile);
	}

	public function testCallableConflictModeTypeErrorSkip(): void
	{
		$tarFile = $this->testDir . '/callable_typeerror.tar';
		$this->createConflictTar($tarFile);

		file_put_contents($this->extractDir . '/existing.txt', 'original');

		$extractor = new TTarFileExtractor($tarFile);
		// Callable that throws TypeError (incompatible signature simulation).
		$extractor->setConflictMode(static function (array $entry, string $path, ?string &$reason): bool {
			throw new \TypeError('bad type');
		});

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		// File must not be overwritten.
		$this->assertSame('original', file_get_contents($this->extractDir . '/existing.txt'));

		$skipped = array_values($extractor->getSkippedFiles());
		$this->assertCount(1, $skipped);
		$this->assertSame(TTarFileExtractor::REASON_CONFLICT_CALLABLE_ERROR_SKIP, $skipped[0]['reason']);

		unlink($tarFile);
	}

	// =========================================================================
	// getConflictMode default
	// =========================================================================

	public function testConflictModeDefaultIsOverwrite(): void
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$this->assertSame(TTarFileExtractor::DEFAULT_CONFLICT_MODE, $extractor->getConflictMode());
		$this->assertSame(TTarFileExtractor::CONFLICT_OVERWRITE, $extractor->getConflictMode());
	}

	public function testSetConflictModeChaining(): void
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$result = $extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
		$this->assertSame($extractor, $result);
		$this->assertSame(TTarFileExtractor::CONFLICT_SKIP, $extractor->getConflictMode());
	}

	public function testSetConflictModeAcceptsClosure(): void
	{
		$extractor = new TTarFileExtractor('/dev/null');
		$fn = static fn(): bool => true;
		$extractor->setConflictMode($fn);
		$this->assertSame($fn, $extractor->getConflictMode());
	}

	// =========================================================================
	// restoreOnFailure — helpers
	// =========================================================================

	/**
	 * Build a tar with the given normal entries followed by a zip-slip trap entry
	 * (`../trap.txt`).  In strict mode (the default) processing the trap entry
	 * causes an exception, which lets the restore-on-failure path run.
	 */
	private function createTarWithZipSlipTrap(string $tarFile, array $normalEntries): void
	{
		$entries = $normalEntries;
		$entries[] = TarTestHelper::entry('../trap.txt', 'evil', '0', '', 0o644, 0, 0, time());
		TarTestHelper::writeTar($tarFile, $entries);
	}

	/**
	 * Run an extraction expected to throw, capturing the throw so that further
	 * assertions can still run after it.
	 */
	private function extractExpectingException(TTarFileExtractor $extractor, string $dir): void
	{
		$threw = false;
		try {
			$extractor->extract($dir);
		} catch (\Exception $e) {
			$threw = true;
		}
		$this->assertTrue($threw, 'Expected extraction to throw an exception due to the zip-slip trap entry');
	}

	// =========================================================================
	// restoreOnFailure — successful extraction tests
	// =========================================================================

	public function testRestoreOnFailureSuccessNewFile(): void
	{
		$tarFile = $this->testDir . '/restore_new.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('hello.txt', 'hello world'),
		]);

		// Defaults: atomic=false, restoreOnFailure=true.
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/hello.txt');
		$this->assertSame('hello world', file_get_contents($this->extractDir . '/hello.txt'));
	}

	public function testRestoreOnFailureSuccessMultipleNewFiles(): void
	{
		$tarFile = $this->testDir . '/restore_multi_new.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('a.txt', 'aaa'),
			TarTestHelper::entry('b.txt', 'bbb'),
			TarTestHelper::entry('c.txt', 'ccc'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertSame('aaa', file_get_contents($this->extractDir . '/a.txt'));
		$this->assertSame('bbb', file_get_contents($this->extractDir . '/b.txt'));
		$this->assertSame('ccc', file_get_contents($this->extractDir . '/c.txt'));
	}

	public function testRestoreOnFailureSuccessPreexistingFileOverwritten(): void
	{
		$tarFile = $this->testDir . '/restore_overwrite.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('data.txt', 'from archive'),
		]);
		file_put_contents($this->extractDir . '/data.txt', 'original');

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertSame('from archive', file_get_contents($this->extractDir . '/data.txt'));
	}

	public function testRestoreOnFailureSuccessBackupDirCleaned(): void
	{
		// Use a subclass with a deterministic backup-dir name so we can assert
		// the backup directory is fully removed after a successful extraction.
		$tarFile = $this->testDir . '/restore_clean.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('data.txt', 'new content'),
		]);
		file_put_contents($this->extractDir . '/data.txt', 'old content');

		$extractor = new TTarRestoreTestExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertSame('new content', file_get_contents($this->extractDir . '/data.txt'));
		// The backup directory must be fully removed after a successful extraction.
		$backupDir = $this->extractDir . '/' . TTarRestoreTestExtractor::BACKUP_DIR;
		$this->assertDirectoryDoesNotExist($backupDir, 'Restore backup directory was not cleaned up after successful extraction');
	}

	public function testRestoreOnFailureSuccessWithDirectoryEntries(): void
	{
		$tarFile = $this->testDir . '/restore_dirs.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('subdir/', '', '5'),
			TarTestHelper::entry('subdir/file.txt', 'dir content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertDirectoryExists($this->extractDir . '/subdir');
		$this->assertSame('dir content', file_get_contents($this->extractDir . '/subdir/file.txt'));
	}

	// =========================================================================
	// restoreOnFailure — failure tests (zip-slip entry triggers exception)
	// =========================================================================

	public function testRestoreOnFailureRestoresPreexistingFileOnException(): void
	{
		// tar: data.txt (normal), ../trap.txt (zip-slip — triggers exception in strict mode).
		$tarFile = $this->testDir . '/restore_exception.tar';
		$this->createTarWithZipSlipTrap($tarFile, [
			TarTestHelper::entry('data.txt', 'from archive'),
		]);
		file_put_contents($this->extractDir . '/data.txt', 'original');

		$extractor = new TTarFileExtractor($tarFile);
		$this->extractExpectingException($extractor, $this->extractDir);

		// Pre-existing file must be restored to its original content.
		$this->assertFileExists($this->extractDir . '/data.txt');
		$this->assertSame('original', file_get_contents($this->extractDir . '/data.txt'));
	}

	public function testRestoreOnFailureRemovesNewFileOnException(): void
	{
		// tar: newfile.txt (no pre-existing at dest), ../trap.txt.
		$tarFile = $this->testDir . '/restore_remove.tar';
		$this->createTarWithZipSlipTrap($tarFile, [
			TarTestHelper::entry('newfile.txt', 'written by extraction'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->extractExpectingException($extractor, $this->extractDir);

		// The newly-written file must be removed during cleanup.
		$this->assertFileDoesNotExist($this->extractDir . '/newfile.txt');
	}

	public function testRestoreOnFailureMultiplePreexistingFilesAllRestored(): void
	{
		$tarFile = $this->testDir . '/restore_multi.tar';
		$this->createTarWithZipSlipTrap($tarFile, [
			TarTestHelper::entry('file_a.txt', 'new a'),
			TarTestHelper::entry('file_b.txt', 'new b'),
		]);
		file_put_contents($this->extractDir . '/file_a.txt', 'old a');
		file_put_contents($this->extractDir . '/file_b.txt', 'old b');

		$extractor = new TTarFileExtractor($tarFile);
		$this->extractExpectingException($extractor, $this->extractDir);

		// Both pre-existing files must be restored.
		$this->assertSame('old a', file_get_contents($this->extractDir . '/file_a.txt'));
		$this->assertSame('old b', file_get_contents($this->extractDir . '/file_b.txt'));
	}

	public function testRestoreOnFailureMixedNewAndPreexistingOnFailure(): void
	{
		// existing.txt is pre-existing; brand_new.txt is not.
		$tarFile = $this->testDir . '/restore_mixed.tar';
		$this->createTarWithZipSlipTrap($tarFile, [
			TarTestHelper::entry('existing.txt', 'new existing'),
			TarTestHelper::entry('brand_new.txt', 'new content'),
		]);
		file_put_contents($this->extractDir . '/existing.txt', 'original existing');

		$extractor = new TTarFileExtractor($tarFile);
		$this->extractExpectingException($extractor, $this->extractDir);

		// Pre-existing file must be restored to its original content.
		$this->assertSame('original existing', file_get_contents($this->extractDir . '/existing.txt'));
		// Newly-written file must be removed.
		$this->assertFileDoesNotExist($this->extractDir . '/brand_new.txt');
	}

	public function testRestoreOnFailureBackupDirRemovedOnFailure(): void
	{
		$tarFile = $this->testDir . '/restore_cleanup.tar';
		$this->createTarWithZipSlipTrap($tarFile, [
			TarTestHelper::entry('data.txt', 'content'),
		]);
		file_put_contents($this->extractDir . '/data.txt', 'original');

		$extractor = new TTarRestoreTestExtractor($tarFile);
		$this->extractExpectingException($extractor, $this->extractDir);

		// Backup directory must be removed even after a failed extraction.
		$backupDir = $this->extractDir . '/' . TTarRestoreTestExtractor::BACKUP_DIR;
		$this->assertDirectoryDoesNotExist($backupDir, 'Restore backup directory was not cleaned up after failed extraction');
	}

	public function testRestoreOnFailureExceptionIsRethrown(): void
	{
		// Only the zip-slip trap entry — no normal entries before it.
		$tarFile = $this->testDir . '/restore_rethrow.tar';
		$this->createTarWithZipSlipTrap($tarFile, []);

		$extractor = new TTarFileExtractor($tarFile);
		$this->expectException(\Exception::class);
		$extractor->extract($this->extractDir);
	}

	public function testRestoreOnFailureManifestPopulatedOnFailure(): void
	{
		$tarFile = $this->testDir . '/restore_manifest.tar';
		$this->createTarWithZipSlipTrap($tarFile, [
			TarTestHelper::entry('file.txt', 'content'),
		]);

		$extractor = new TTarFileExtractor($tarFile);
		$this->extractExpectingException($extractor, $this->extractDir);

		// Even on failure the partial manifest must be populated.
		$manifest = $extractor->getExtractManifest();
		$this->assertNotNull($manifest);
		$this->assertArrayHasKey('file.txt', $manifest);
	}

	// =========================================================================
	// restoreOnFailure — conflict-mode interactions
	// =========================================================================

	public function testRestoreOnFailureConflictSkipPreservesOriginalOnSuccess(): void
	{
		// A conflict-skipped entry must not trigger the pre-write hook, so the
		// original file must remain untouched and the backup dir is cleaned up on success.
		$tarFile = $this->testDir . '/restore_skip.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('existing.txt', 'from archive'),
		]);
		file_put_contents($this->extractDir . '/existing.txt', 'original');

		$extractor = new TTarRestoreTestExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertSame('original', file_get_contents($this->extractDir . '/existing.txt'));
		$this->assertTrue($extractor->hasSkippedFiles());
		// Pre-write hook was never called for the skipped entry — backup dir removed on success.
		$backupDir = $this->extractDir . '/' . TTarRestoreTestExtractor::BACKUP_DIR;
		$this->assertDirectoryDoesNotExist($backupDir, 'Backup directory was not cleaned up after successful extraction');
	}

	public function testRestoreOnFailureConflictSkippedFileUnaffectedByFailure(): void
	{
		// A file that is conflict-skipped is not backed up.  A subsequent exception
		// (from the zip-slip trap) must not affect it — it stays as its original.
		$tarFile = $this->testDir . '/restore_conflict_failure.tar';
		$this->createTarWithZipSlipTrap($tarFile, [
			TarTestHelper::entry('skipped.txt', 'new version'),
		]);
		file_put_contents($this->extractDir . '/skipped.txt', 'original');

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
		$this->extractExpectingException($extractor, $this->extractDir);

		// Conflict-skipped → hook not called → no backup → original intact.
		$this->assertSame('original', file_get_contents($this->extractDir . '/skipped.txt'));
	}

}

/**
 * TTarFileExtractor subclass that uses a deterministic backup-directory name so
 * tests can locate and verify backup-dir cleanup without pattern-matching uniqid().
 *
 * @since 4.3.3
 */
class TTarRestoreTestExtractor extends TTarFileExtractor
{
	public const BACKUP_DIR = '.~staging_bkp_test~';

	public function __construct(string $tarpath)
	{
		parent::__construct($tarpath);
	}

	protected function _backup_dir_name(): string
	{
		return self::BACKUP_DIR;
	}
}
