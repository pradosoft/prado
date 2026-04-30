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

	private function createMaliciousTar(): string
	{
		$tarFile = $this->extractDir . '/malicious.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/../../../malicious.txt", 'Malicious content'),
		]);
		return $tarFile;
	}

	private function createTarWithSubdirectoryPathTraversal(): string
	{
		$tarFile = $this->extractDir . '/subdir_traversal.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/", '', '5'),
			TarTestHelper::entry("subdir/../../outside.txt", 'Content in subdir'),
		]);
		return $tarFile;
	}

	private function createTarWithSymlinkAbsolute(): string
	{
		$tarFile = $this->extractDir . '/symlink_absolute.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("mylink", '', '2', '/etc/passwd'),
		]);
		return $tarFile;
	}

	private function createTarWithSymlinkRelativePathTraversal(): string
	{
		$tarFile = $this->extractDir . '/symlink_relative.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/../../../mylink", '', '2', '../../etc/passwd'),
		]);
		return $tarFile;
	}

	private function createTarWithHardLinkAbsolute(): string
	{
		$tarFile = $this->extractDir . '/hardlink_absolute.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("mylink", '', '1', '/etc/passwd'),
		]);
		return $tarFile;
	}

	private function createTarWithHardLinkRelativePathTraversal(): string
	{
		$tarFile = $this->extractDir . '/hardlink_relative.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/../../../mylink", '', '1', '../../etc/passwd'),
		]);
		return $tarFile;
	}

	private function createTarWithSymlinkInsideExtraction(): string
	{
		$tarFile = $this->extractDir . '/symlink_inside.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/", '', '5'),
			TarTestHelper::entry("subdir/mylink", '', '2', '/etc/passwd'),
		]);
		return $tarFile;
	}

	private function createTarWithHardLinkInsideExtraction(): string
	{
		$tarFile = $this->extractDir . '/hardlink_inside.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/", '', '5'),
			TarTestHelper::entry("subdir/mylink", '', '1', '/etc/passwd'),
		]);
		return $tarFile;
	}

	private function createSafeTar(): string
	{
		$tarFile = $this->extractDir . '/safe.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('safe.txt', 'Safe content'),
		]);
		return $tarFile;
	}

	private function createTarWithSubdirectory(): string
	{
		$tarFile = $this->extractDir . '/subdir.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry("subdir/", '', '5'),
			TarTestHelper::entry("subdir/safe.txt", 'Safe content in subdir'),
		]);
		return $tarFile;
	}

	private function createTarWithSafeSymlink(): string
	{
		$tarFile = $this->extractDir . '/safe_symlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('target.txt', 'Target content'),
			TarTestHelper::entry('link.txt', '', '2', 'target.txt'),
		]);
		return $tarFile;
	}

	private function createTarWithSafeHardLink(): string
	{
		$tarFile = $this->extractDir . '/safe_hardlink.tar';
		TarTestHelper::writeTar($tarFile, [
			TarTestHelper::entry('original.txt', 'Original content'),
			TarTestHelper::entry('hardlink.txt', '', '1', 'original.txt'),
		]);
		return $tarFile;
	}
}
