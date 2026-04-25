<?php

use PHPUnit\Framework\TestCase;
use Prado\IO\TTarFileExtractor;

class TTarFileExtractorCompressionTest extends TestCase
{
	private string $testDir = '';
	private string $extractDir = '';

	protected function setUp(): void
	{
		$this->testDir = sys_get_temp_dir() . '/prado_tar_compression_test_' . uniqid();
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

	public function testGetCompressionDefaultNone()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';
		$extractor = new TTarFileExtractor($tarFile);
		$this->assertEquals(TTarFileExtractor::COMPRESSION_NONE, $extractor->getCompression());
	}

	public function testDetectGzipCompressionByMagic()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $extractor->getCompression());
	}

	public function testDetectBzip2CompressionByMagic()
	{
		$tarFile = __DIR__ . '/data/test_bzip2.tar.bz2';
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $extractor->getCompression());
	}

	public function testDetectLzmaCompressionByMagic()
	{
		$tarFile = __DIR__ . '/data/test_xz.tar.xz';
		$extractor = new TTarFileExtractor($tarFile);
		$extractor->extract($this->extractDir);

		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $extractor->getCompression());
	}

	public function testExtractGzipTar()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/gzip_content.txt');
		$this->assertFileExists($this->extractDir . '/gzip_data.json');
		$this->assertStringContainsString('gzip compressed tar archive', file_get_contents($this->extractDir . '/gzip_content.txt'));
		$this->assertStringContainsString('"source":"gzip"', file_get_contents($this->extractDir . '/gzip_data.json'));
	}

	public function testExtractBzip2Tar()
	{
		$tarFile = __DIR__ . '/data/test_bzip2.tar.bz2';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/bzip2_content.txt');
		$this->assertFileExists($this->extractDir . '/bzip2_data.json');
		$this->assertStringContainsString('bzip2 compressed tar archive', file_get_contents($this->extractDir . '/bzip2_content.txt'));
		$this->assertStringContainsString('"source":"bzip2"', file_get_contents($this->extractDir . '/bzip2_data.json'));
	}

	public function testExtractLzmaTar()
	{
		$tarFile = __DIR__ . '/data/test_xz.tar.xz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/xz_content.txt');
		$this->assertFileExists($this->extractDir . '/xz_data.json');
		$this->assertStringContainsString('xz compressed tar archive', file_get_contents($this->extractDir . '/xz_content.txt'));
		$this->assertStringContainsString('"source":"xz"', file_get_contents($this->extractDir . '/xz_data.json'));
	}

	public function testExtractGzipWithTimestamp()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/gzip_content.txt');
		$actualMtime = filemtime($this->extractDir . '/gzip_content.txt');
		$this->assertGreaterThan(0, $actualMtime, 'Timestamp should be preserved from tar archive');
	}

	public function testExtractBzip2WithTimestamp()
	{
		$tarFile = __DIR__ . '/data/test_bzip2.tar.bz2';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/bzip2_content.txt');
		$actualMtime = filemtime($this->extractDir . '/bzip2_content.txt');
		$this->assertGreaterThan(0, $actualMtime, 'Timestamp should be preserved from tar archive');
	}

	public function testExtractLzmaWithTimestamp()
	{
		$tarFile = __DIR__ . '/data/test_xz.tar.xz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/xz_content.txt');
		$actualMtime = filemtime($this->extractDir . '/xz_content.txt');
		$this->assertGreaterThan(0, $actualMtime, 'Timestamp should be preserved from tar archive');
	}

	public function testConstantsExist()
	{
		$this->assertEquals(0, TTarFileExtractor::COMPRESSION_NONE);
		$this->assertEquals(1, TTarFileExtractor::COMPRESSION_GZIP);
		$this->assertEquals(2, TTarFileExtractor::COMPRESSION_BZIP2);
		$this->assertEquals(3, TTarFileExtractor::COMPRESSION_LZMA);
	}

	public function testMultipleExtractionsResetState()
	{
		$tarGz = __DIR__ . '/data/test_gzip.tar.gz';
		$tarBz2 = __DIR__ . '/data/test_bzip2.tar.bz2';

		$extractor = new TTarFileExtractor($tarGz);
		$result1 = $extractor->extract($this->extractDir);
		$this->assertTrue($result1);
		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $extractor->getCompression());

		$this->removeDirectory($this->extractDir);
		mkdir($this->extractDir, 0777, true);

		$extractor = new TTarFileExtractor($tarBz2);
		$result2 = $extractor->extract($this->extractDir);
		$this->assertTrue($result2);
		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $extractor->getCompression());
	}

	public function testExtractGzipMultipleFiles()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/gzip_content.txt');
		$this->assertFileExists($this->extractDir . '/gzip_data.json');
	}

	public function testExtractBzip2MultipleFiles()
	{
		$tarFile = __DIR__ . '/data/test_bzip2.tar.bz2';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/bzip2_content.txt');
		$this->assertFileExists($this->extractDir . '/bzip2_data.json');
	}

	public function testExtractLzmaMultipleFiles()
	{
		$tarFile = __DIR__ . '/data/test_xz.tar.xz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/xz_content.txt');
		$this->assertFileExists($this->extractDir . '/xz_data.json');
	}

	public function testExtractGzipPreservesContentIntegrity()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$jsonContent = file_get_contents($this->extractDir . '/gzip_data.json');
		$data = json_decode($jsonContent, true);
		$this->assertIsArray($data);
		$this->assertEquals('gzip', $data['source']);
		$this->assertTrue($data['test']);
	}

	public function testExtractBzip2PreservesContentIntegrity()
	{
		$tarFile = __DIR__ . '/data/test_bzip2.tar.bz2';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$jsonContent = file_get_contents($this->extractDir . '/bzip2_data.json');
		$data = json_decode($jsonContent, true);
		$this->assertIsArray($data);
		$this->assertEquals('bzip2', $data['source']);
		$this->assertTrue($data['test']);
	}

	public function testExtractLzmaPreservesContentIntegrity()
	{
		$tarFile = __DIR__ . '/data/test_xz.tar.xz';

		$extractor = new TTarFileExtractor($tarFile);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$jsonContent = file_get_contents($this->extractDir . '/xz_data.json');
		$data = json_decode($jsonContent, true);
		$this->assertIsArray($data);
		$this->assertEquals('xz', $data['source']);
		$this->assertTrue($data['test']);
	}

	public function testExtractGzipSecurityFeatures()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFalse($extractor->hasSkippedFiles());
	}

	public function testExtractBzip2SecurityFeatures()
	{
		$tarFile = __DIR__ . '/data/test_bzip2.tar.bz2';

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFalse($extractor->hasSkippedFiles());
	}

	public function testExtractLzmaSecurityFeatures()
	{
		$tarFile = __DIR__ . '/data/test_xz.tar.xz';

		$extractor = new TTarFileExtractor($tarFile);
		$extractor->setStrict(false);
		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFalse($extractor->hasSkippedFiles());
	}

}