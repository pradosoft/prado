<?php

use PHPUnit\Framework\TestCase;
use Prado\IO\TTarFileExtractor;

class TTarFileExtractorZipSlipTest extends TestCase
{
	private string $extractDir = '';
	private string $parentDir = '';

	protected function setUp(): void
	{
		$this->extractDir = sys_get_temp_dir() . '/prado_tar_test_' . uniqid();
		mkdir($this->extractDir, 0777, true);
		$this->parentDir = dirname($this->extractDir);
	}

	protected function tearDown(): void
	{
		$maliciousInParent = $this->parentDir . '/malicious.txt';
		if (file_exists($maliciousInParent)) {
			unlink($maliciousInParent);
		}
		$this->removeDirectory($this->extractDir);
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

	public function testExtractZipSlipPathTraversalBlocked(): void
	{
		$tarFile = $this->createMaliciousTar();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Zip Slip path traversal attempt detected');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractZipSlipWithSubdirectoryPathTraversalBlocked(): void
	{
		$tarFile = $this->createTarWithSubdirectoryPathTraversal();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Zip Slip path traversal attempt detected');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractBlockedSymlinkWithAbsoluteTarget(): void
	{
		$tarFile = $this->createTarWithSymlinkAbsolute();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Symlink target outside extraction directory');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractBlockedSymlinkWithRelativePathTraversal(): void
	{
		$tarFile = $this->createTarWithSymlinkRelativePathTraversal();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/(Zip Slip path traversal|Symlink target outside)/');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractBlockedHardLinkWithRelativePathTraversal(): void
	{
		$tarFile = $this->createTarWithHardLinkRelativePathTraversal();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/(Zip Slip path traversal|Hard link target outside)/');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractBlockedHardLinkWithAbsolutePath(): void
	{
		$tarFile = $this->createTarWithHardLinkAbsolute();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Hard link target outside extraction directory');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractBlockedSymlinkInsideExtractionDirectory(): void
	{
		$tarFile = $this->createTarWithSymlinkInsideExtraction();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Symlink target outside extraction directory');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractBlockedHardLinkInsideExtractionDirectory(): void
	{
		$tarFile = $this->createTarWithHardLinkInsideExtraction();
		
		$extractor = new TTarFileExtractor($tarFile);
		
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Hard link target outside extraction directory');
		
		try {
			$extractor->extract($this->extractDir);
		} finally {
			unlink($tarFile);
		}
	}

	public function testExtractAllowedWithinDestination(): void
	{
		$tarFile = $this->createSafeTar();
		
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result, 'Extraction should succeed for safe tar');
		
		$safeFile = $this->extractDir . '/safe.txt';
		$this->assertFileExists($safeFile, 'Safe file should be extracted');
		$this->assertEquals('Safe content', file_get_contents($safeFile));
		
		unlink($tarFile);
	}

	public function testExtractAllowedSubdirectoryWithinDestination(): void
	{
		$tarFile = $this->createTarWithSubdirectory();
		
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result, 'Extraction should succeed for safe tar with subdirectory');
		
		$safeFile = $this->extractDir . '/subdir/safe.txt';
		$this->assertFileExists($safeFile, 'Safe file in subdir should be extracted');
		
		unlink($tarFile);
	}

	public function testExtractAllowedSymlinkWithinDestination(): void
	{
		$tarFile = $this->createTarWithSafeSymlink();
		
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result, 'Extraction should succeed for safe symlink');
		
		$this->assertFileExists($this->extractDir . '/target.txt');
		$this->assertFileExists($this->extractDir . '/link.txt');
		$this->assertTrue(is_link($this->extractDir . '/link.txt'));
		
		unlink($tarFile);
	}

	public function testExtractAllowedHardLinkWithinDestination(): void
	{
		$tarFile = $this->createTarWithSafeHardLink();
		
		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);
		
		$this->assertTrue($result, 'Extraction should succeed for safe hard link');
		
		$this->assertFileExists($this->extractDir . '/original.txt');
		$this->assertFileExists($this->extractDir . '/hardlink.txt');
		$this->assertEquals('Original content', file_get_contents($this->extractDir . '/hardlink.txt'));
		
		unlink($tarFile);
	}

	private function createTarHeader(
		string $filename, 
		int $size, 
		string $typeflag = '0', 
		string $linkpath = ''
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
		$header .= pack("a100", $linkpath);
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

	private function createMaliciousTar(): string
	{
		$tarFile = $this->extractDir . '/malicious.tar';
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
		
		return $tarFile;
	}

	private function createTarWithSubdirectoryPathTraversal(): string
	{
		$tarFile = $this->extractDir . '/subdir_traversal.tar';
		
		$fp = fopen($tarFile, 'wb');
		
		$dirHeader = $this->createTarHeader("subdir/", 0, '5');
		fwrite($fp, $dirHeader);
		
		$content = 'Content in subdir';
		$filename = "subdir/../../outside.txt";
		$fileHeader = $this->createTarHeader($filename, strlen($content), '0');
		fwrite($fp, $fileHeader);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createTarWithSymlinkAbsolute(): string
	{
		$tarFile = $this->extractDir . '/symlink_absolute.tar';
		$filename = "mylink";
		$linkpath = "/etc/passwd";
		
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader($filename, 0, '2', $linkpath);
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createTarWithSymlinkRelativePathTraversal(): string
	{
		$tarFile = $this->extractDir . '/symlink_relative.tar';
		$filename = "subdir/../../../mylink";
		$linkpath = "../../etc/passwd";
		
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader($filename, 0, '2', $linkpath);
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createTarWithHardLinkAbsolute(): string
	{
		$tarFile = $this->extractDir . '/hardlink_absolute.tar';
		$filename = "mylink";
		$linkpath = "/etc/passwd";
		
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader($filename, 0, '1', $linkpath);
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createTarWithHardLinkRelativePathTraversal(): string
	{
		$tarFile = $this->extractDir . '/hardlink_relative.tar';
		$filename = "subdir/../../../mylink";
		$linkpath = "../../etc/passwd";
		
		$fp = fopen($tarFile, 'wb');
		
		$header = $this->createTarHeader($filename, 0, '1', $linkpath);
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createTarWithSymlinkInsideExtraction(): string
	{
		$tarFile = $this->extractDir . '/symlink_inside.tar';
		$filename = "subdir/mylink";
		$linkpath = "/etc/passwd";
		
		$fp = fopen($tarFile, 'wb');
		
		$dirHeader = $this->createTarHeader("subdir/", 0, '5');
		fwrite($fp, $dirHeader);
		
		$header = $this->createTarHeader($filename, 0, '2', $linkpath);
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createTarWithHardLinkInsideExtraction(): string
	{
		$tarFile = $this->extractDir . '/hardlink_inside.tar';
		$filename = "subdir/mylink";
		$linkpath = "/etc/passwd";
		
		$fp = fopen($tarFile, 'wb');
		
		$dirHeader = $this->createTarHeader("subdir/", 0, '5');
		fwrite($fp, $dirHeader);
		
		$header = $this->createTarHeader($filename, 0, '1', $linkpath);
		fwrite($fp, $header);
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createSafeTar(): string
	{
		$tarFile = $this->extractDir . '/safe.tar';
		$content = 'Safe content';
		$filename = "safe.txt";
		
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
		
		return $tarFile;
	}

	private function createTarWithSubdirectory(): string
	{
		$tarFile = $this->extractDir . '/subdir.tar';
		
		$fp = fopen($tarFile, 'wb');
		
		$dirHeader = $this->createTarHeader("subdir/", 0, '5');
		fwrite($fp, $dirHeader);
		
		$content = 'Safe content in subdir';
		$fileHeader = $this->createTarHeader("subdir/safe.txt", strlen($content), '0');
		fwrite($fp, $fileHeader);
		fwrite($fp, $content);
		$padding = 512 - (strlen($content) % 512);
		if ($padding < 512) {
			fwrite($fp, str_repeat("\0", $padding));
		}
		
		fwrite($fp, str_repeat("\0", 1024));
		
		fclose($fp);
		
		return $tarFile;
	}

	private function createTarWithSafeSymlink(): string
	{
		$tarFile = $this->extractDir . '/safe_symlink.tar';
		
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
		
		return $tarFile;
	}

	private function createTarWithSafeHardLink(): string
	{
		$tarFile = $this->extractDir . '/safe_hardlink.tar';
		
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
		
		return $tarFile;
	}
}
