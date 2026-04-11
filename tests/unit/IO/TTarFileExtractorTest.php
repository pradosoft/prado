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
		mkdir($this->extractDir, 0777, true);
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
		$this->createTar($tarFile, 'test.txt', 'Hello World');
		
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
		$this->createTarWithMultiple($tarFile);
		
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
		$this->createTarWithDirectory($tarFile);
		
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
		$this->expectExceptionMessage('Unable to open in read mode');
		
		$extractor->extract($this->extractDir);
	}

	public function testExtractPreservesTimestamp()
	{
		$tarFile = $this->testDir . '/timestamp.tar';
		$expectedMtime = time() - 3600;
		$this->createTarWithMtime($tarFile, 'timed.txt', 'Timestamp content', $expectedMtime);
		
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
		
		$header = pack("a100a8a8a8a12a12a8", 
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
		$header = $this->createTarHeader('', 10, '0');
		fwrite($fp, $header);
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
		$this->createTar($tarFile, 'file with spaces.txt', 'Special content');
		
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
		$this->createTar($tarFile, 'large.txt', $content);
		
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
		$this->createTar($tarFile, 'binary.dat', $binaryContent);
		
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
		$fp = fopen($tarFile, 'wb');
		
		$content = 'Target content';
		$header = $this->createTarHeader('target.txt', strlen($content), '0');
		fwrite($fp, $header);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		$linkHeader = $this->createTarHeader('link.txt', 0, '2', 'target.txt');
		fwrite($fp, $linkHeader);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/target.txt');
		$this->assertFileExists($this->extractDir . '/link.txt');
		$this->assertTrue(is_link($this->extractDir . '/link.txt'));
		$this->assertEquals('target.txt', readlink($this->extractDir . '/link.txt'));
		
		unlink($tarFile);
	}

	public function testExtractSymlinkWithRelativePathWithinDestination()
	{
		$tarFile = $this->testDir . '/relative_symlink.tar';
		$fp = fopen($tarFile, 'wb');
		
		mkdir($this->extractDir . '/subdir', 0777, true);
		mkdir($this->extractDir . '/subdir/linktarget', 0777, true);
		file_put_contents($this->extractDir . '/subdir/linktarget/file.txt', 'Content');
		
		$linkHeader = $this->createTarHeader('subdir/mylink', 0, '2', 'linktarget/file.txt');
		fwrite($fp, $linkHeader);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/subdir/mylink');
		$this->assertTrue(is_link($this->extractDir . '/subdir/mylink'));
		$this->assertEquals('linktarget/file.txt', readlink($this->extractDir . '/subdir/mylink'));
		
		unlink($tarFile);
	}

	public function testExtractHardLinkWithinDestination()
	{
		$tarFile = $this->testDir . '/safe_hardlink.tar';
		$fp = fopen($tarFile, 'wb');
		
		$content = 'Original content';
		$header = $this->createTarHeader('original.txt', strlen($content), '0');
		fwrite($fp, $header);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		$linkHeader = $this->createTarHeader('hardlink.txt', 0, '1', 'original.txt');
		fwrite($fp, $linkHeader);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/original.txt');
		$this->assertFileExists($this->extractDir . '/hardlink.txt');
		$this->assertEquals($content, file_get_contents($this->extractDir . '/hardlink.txt'));
		
		unlink($tarFile);
	}

	public function testExtractBlockedZipSlipPathTraversal()
	{
		$tarFile = $this->testDir . '/zipslip.tar';
		$content = 'Malicious content';
		$filename = "subdir/../../../malicious.txt";
		
		$fp = fopen($tarFile, 'wb');
		$header = $this->createTarHeader($filename, strlen($content), '0');
		fwrite($fp, $header);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Zip Slip path traversal attempt detected');
		
		$extractor->extract($this->extractDir);
	}

	public function testExtractBlockedSymlinkWithAbsolutePath()
	{
		$tarFile = $this->testDir . '/symlink_absolute.tar';
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader("mylink", 0, '2', '/etc/passwd');
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Symlink target outside extraction directory');
		
		$extractor->extract($this->extractDir);
	}

	public function testExtractBlockedHardLinkWithAbsolutePath()
	{
		$tarFile = $this->testDir . '/hardlink_absolute.tar';
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader("mylink", 0, '1', '/etc/passwd');
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Hard link target outside extraction directory');
		
		$extractor->extract($this->extractDir);
	}

	public function testExtractBlockedSymlinkWithRelativePathTraversal()
	{
		$tarFile = $this->testDir . '/symlink_traversal.tar';
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader("subdir/../../../mylink", 0, '2', '../../etc/passwd');
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/(Zip Slip path traversal|Symlink target outside)/');
		
		$extractor->extract($this->extractDir);
	}

	private function createTarHeader(
		string $filename, 
		int $size, 
		string $typeflag = '0', 
		string $linkname = ''
	): string {
		$mtime = sprintf("%011o", time());
		$size_oct = sprintf("%011o", $size);
		
		$header = pack("a100a8a8a8a12a12a8", 
			$filename, 
			"0000644", 
			"0000000", 
			"0000000", 
			$size_oct,
			$mtime,
			"        "
		);
		
		$header .= pack("a1", $typeflag);
		$header .= pack("a100", $linkname);
		$header .= pack("a6", "ustar");
		$header .= pack("a2", "00");
		$header .= pack("a32", "root");
		$header .= pack("a32", "root");
		$header .= pack("a8a8", "", "");
		$header .= pack("a155", "");
		$header .= pack("a12", "");
		
		$header = str_pad($header, 512, "\0");
		
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
		
		$checksumStr = sprintf("%06o\0 ", $checksum);
		$header = substr($header, 0, 148) . $checksumStr . substr($header, 156);
		
		return $header;
	}

	private function createTar(string $tarFile, string $filename, string $content, string $prefix = ''): void
	{
		$fp = fopen($tarFile, 'wb');
		
		$fullFilename = $prefix . $filename;
		$header = $this->createTarHeader($fullFilename, strlen($content), '0');
		fwrite($fp, $header);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
	}

	private function createTarWithMtime(string $tarFile, string $filename, string $content, int $mtime): void
	{
		$fp = fopen($tarFile, 'wb');
		
		$size_oct = sprintf("%011o", strlen($content));
		$mtime_oct = sprintf("%011o", $mtime);
		
		$header = pack("a100a8a8a8a12a12a8", 
			$filename, 
			"0000644", 
			"0000000", 
			"0000000", 
			$size_oct,
			$mtime_oct,
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
		for ($i = 0; $i < 148; $i++) {
			$checksum += ord($header[$i]);
		}
		for ($i = 148; $i < 156; $i++) {
			$checksum += ord(' ');
		}
		for ($i = 156; $i < 512; $i++) {
			$checksum += ord($header[$i]);
		}
		
		$checksumStr = sprintf("%06o\0 ", $checksum);
		$header = substr($header, 0, 148) . $checksumStr . substr($header, 156);
		
		fwrite($fp, $header);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
	}

	private function createTarWithMultiple(string $tarFile): void
	{
		$fp = fopen($tarFile, 'wb');
		
		$entries = [
			['name' => 'file1.txt', 'content' => 'Content 1'],
			['name' => 'file2.txt', 'content' => 'Content 2'],
		];
		
		foreach ($entries as $entry) {
			$header = $this->createTarHeader($entry['name'], strlen($entry['content']), '0');
			fwrite($fp, $header);
			fwrite($fp, $entry['content']);
			$padding = 512 - (strlen($entry['content']) % 512);
			if ($padding < 512) {
				fwrite($fp, str_repeat("\0", $padding));
			}
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
	}

	private function createTarWithDirectory(string $tarFile): void
	{
		$fp = fopen($tarFile, 'wb');
		
		$dirHeader = $this->createTarHeader("subdir/", 0, '5');
		fwrite($fp, $dirHeader);
		
		$nestedContent = 'Nested content';
		$fileHeader = $this->createTarHeader("subdir/nested.txt", strlen($nestedContent), '0');
		fwrite($fp, $fileHeader);
		fwrite($fp, $nestedContent);
		$padding = 512 - (strlen($nestedContent) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
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
		$this->createTar($tarFile, 'safe.txt', 'Safe content');
		
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);
		
		$this->assertFalse($extractor->hasSkippedFiles());
		
		unlink($tarFile);
	}

	public function testGetSkippedFilesDefaultEmpty()
	{
		$tarFile = $this->testDir . '/safe.tar';
		$this->createTar($tarFile, 'safe.txt', 'Safe content');
		
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);
		
		$this->assertEquals([], $extractor->getSkippedFiles());
		
		unlink($tarFile);
	}

	public function testClearSkippedFiles()
	{
		$extractor = new TTarFileExtractor($this->testDir . '/test.tar');
		$result = $extractor->clearSkippedFiles();
		$this->assertSame($extractor, $result);
		$this->assertFalse($extractor->hasSkippedFiles());
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
		$skipped = $extractor->getSkippedFiles();
		$this->assertCount(1, $skipped);
		$this->assertEquals('zip_slip', $skipped[0]['type']);
		$this->assertStringContainsString('malicious.txt', $skipped[0]['filename']);
		$this->assertNull($skipped[0]['linkname']);
		$this->assertIsArray($skipped[0]['header']);
		$this->assertArrayHasKey('timestamp', $skipped[0]);
		
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
		$skipped = $extractor->getSkippedFiles();
		$this->assertCount(1, $skipped);
		$this->assertEquals('symlink', $skipped[0]['type']);
		$this->assertStringContainsString('mylink', $skipped[0]['filename']);
		$this->assertEquals('/etc/passwd', $skipped[0]['linkname']);
		
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
		$skipped = $extractor->getSkippedFiles();
		$this->assertCount(1, $skipped);
		$this->assertEquals('hardlink', $skipped[0]['type']);
		$this->assertStringContainsString('mylink', $skipped[0]['filename']);
		$this->assertEquals('/etc/passwd', $skipped[0]['linkname']);
		
		unlink($tarFile);
	}

	public function testStrictFalseWithMultipleSecurityIssues()
	{
		$tarFile = $this->testDir . '/multi_malicious.tar';
		$fp = fopen($tarFile, 'wb');
		
		$content1 = 'Safe content';
		$header1 = $this->createTarHeader('safe.txt', strlen($content1), '0');
		fwrite($fp, $header1);
		fwrite($fp, $content1);
		$padding1 = 512 - (strlen($content1) % 512);
		if ($padding1 < 512) {
			fwrite($fp, str_repeat("\0", $padding1));
		}
		
		$content2 = 'Malicious!';
		$header2 = $this->createTarHeader("subdir/../../../malicious.txt", strlen($content2), '0');
		fwrite($fp, $header2);
		fwrite($fp, $content2);
		$padding2 = 512 - (strlen($content2) % 512);
		if ($padding2 < 512) {
			fwrite($fp, str_repeat("\0", $padding2));
		}
		
		$header3 = $this->createTarHeader("link_outside", 0, '2', '/etc/passwd');
		fwrite($fp, $header3);
		
		$content4 = 'Another safe';
		$header4 = $this->createTarHeader('safe2.txt', strlen($content4), '0');
		fwrite($fp, $header4);
		fwrite($fp, $content4);
		$padding4 = 512 - (strlen($content4) % 512);
		if ($padding4 < 512) {
			fwrite($fp, str_repeat("\0", $padding4));
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
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

	public function testClearSkippedFilesAfterExtraction()
	{
		$tarFile = $this->testDir . '/zipslip.tar';
		$this->createZipSlipTar($tarFile);
		
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$extractor->extract($this->extractDir);
		
		$this->assertTrue($extractor->hasSkippedFiles());
		
		$extractor->clearSkippedFiles();
		$this->assertFalse($extractor->hasSkippedFiles());
		$this->assertEquals([], $extractor->getSkippedFiles());
		
		unlink($tarFile);
	}

	public function testStrictFalseWithSymlinkRelativePathTraversal()
	{
		$tarFile = $this->testDir . '/symlink_rel.tar';
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader("subdir/mylink", 0, '2', '../../../etc/passwd');
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = $extractor->getSkippedFiles();
		$this->assertCount(1, $skipped);
		$this->assertEquals('symlink', $skipped[0]['type']);
		
		unlink($tarFile);
	}

	public function testStrictFalseWithHardLinkRelativePathTraversal()
	{
		$tarFile = $this->testDir . '/hardlink_rel.tar';
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader("subdir/mylink", 0, '1', '../../../etc/passwd');
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
		
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result);
		$this->assertTrue($extractor->hasSkippedFiles());
		$skipped = $extractor->getSkippedFiles();
		$this->assertCount(1, $skipped);
		$this->assertEquals('hardlink', $skipped[0]['type']);
		
		unlink($tarFile);
	}

	private function createZipSlipTar(string $tarFile): void
	{
		$fp = fopen($tarFile, 'wb');
		
		$content = 'Malicious content';
		$filename = "subdir/../../../malicious.txt";
		$header = $this->createTarHeader($filename, strlen($content), '0');
		fwrite($fp, $header);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
	}

	private function createSymlinkOutsideTar(string $tarFile): void
	{
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader("mylink", 0, '2', '/etc/passwd');
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
	}

	private function createHardLinkOutsideTar(string $tarFile): void
	{
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader("mylink", 0, '1', '/etc/passwd');
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);
	}
}
