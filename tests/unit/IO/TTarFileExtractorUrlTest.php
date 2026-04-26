<?php

use PHPUnit\Framework\TestCase;
use Prado\IO\TTarFileExtractor;

class TTarFileExtractorUrlTest extends TestCase
{
	private string $testDir = '';
	private string $extractDir = '';

	protected function setUp(): void
	{
		$this->testDir = sys_get_temp_dir() . '/prado_tar_url_test_' . uniqid();
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

	private function createTarWithMtime(string $tarFile, string $filename, string $content, int $mtime): void
	{
		$fp = fopen($tarFile, 'wb');

		$size_oct = sprintf("%011o", strlen($content));
		$mtime_oct = sprintf("%011o", $mtime);

		$header = pack(
			"a100a8a8a8a12a12a8",
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

	private function createTarHeader(
		string $filename,
		int $size,
		string $typeflag = '0',
		string $linkpath = ''
	): string {
		$mtime = sprintf("%011o", time());
		$size_oct = sprintf("%011o", $size);

		$header = pack(
			"a100a8a8a8a12a12a8",
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

	// ---------------------------------------------------------------------------
	// _detectCompression called directly with URL strings
	// ---------------------------------------------------------------------------

	public function testHttpProtocolDetected()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('http://example.com/test.tar');
		$result = $method->invoke($extractor, 'http://example.com/test.tar');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_NONE, $result);
	}

	public function testHttpsProtocolDetected()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('https://example.com/test.tar');
		$result = $method->invoke($extractor, 'https://example.com/test.tar');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_NONE, $result);
	}

	public function testHttpUrlWithGzipCompression()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('http://example.com/test.tar.gz');
		$result = $method->invoke($extractor, 'http://example.com/test.tar.gz');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $result);
	}

	public function testHttpsUrlWithGzipCompression()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.gz');
		$result = $method->invoke($extractor, 'https://example.com/test.tar.gz');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $result);
	}

	public function testHttpUrlWithBzip2Compression()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('http://example.com/test.tar.bz2');
		$result = $method->invoke($extractor, 'http://example.com/test.tar.bz2');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $result);
	}

	public function testHttpsUrlWithBzip2Compression()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.bz2');
		$result = $method->invoke($extractor, 'https://example.com/test.tar.bz2');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $result);
	}

	public function testHttpUrlWithLzmaCompression()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('http://example.com/test.tar.xz');
		$result = $method->invoke($extractor, 'http://example.com/test.tar.xz');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $result);
	}

	public function testHttpsUrlWithLzmaCompression()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.xz');
		$result = $method->invoke($extractor, 'https://example.com/test.tar.xz');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $result);
	}

	public function testTgzExtension()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('http://example.com/archive.tgz');
		$result = $method->invoke($extractor, 'http://example.com/archive.tgz');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $result);
	}

	public function testTxzExtension()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('https://example.com/archive.txz');
		$result = $method->invoke($extractor, 'https://example.com/archive.txz');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $result);
	}

	public function testTbz2Extension()
	{
		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('http://example.com/archive.tbz2');
		$result = $method->invoke($extractor, 'http://example.com/archive.tbz2');

		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $result);
	}

	// ---------------------------------------------------------------------------
	// _detectCompression called with local file paths (magic-byte path)
	// ---------------------------------------------------------------------------

	public function testMagicBytesWithHttpUrl()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('http://example.com/test.tar.gz');
		$result = $method->invoke($extractor, $tarFile);

		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $result);
	}

	public function testMagicBytesWithHttpsUrl()
	{
		$tarFile = __DIR__ . '/data/test_gzip.tar.gz';

		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.gz');
		$result = $method->invoke($extractor, $tarFile);

		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $result);
	}

	public function testLocalFileDetectionHasPrecedenceOverUrlExtension()
	{
		// A plain .tar file has no compression magic bytes; detection returns NONE.
		$tarFile = $this->testDir . '/local.tar';
		$this->createTarWithMtime($tarFile, 'test.txt', 'test content', time() - 3600);

		$reflection = new \ReflectionClass(TTarFileExtractor::class);
		$method = $reflection->getMethod('_detectCompression');
		$method->setAccessible(true);

		$extractor = new TTarFileExtractor($tarFile);
		$result = $method->invoke($extractor, $tarFile);

		$this->assertEquals(TTarFileExtractor::COMPRESSION_NONE, $result);
	}

	// ---------------------------------------------------------------------------
	// Full extract via pre-injected _temp_tarpath (simulates completed download)
	// ---------------------------------------------------------------------------

	public function testHttpUrlDownloadAndExtract()
	{
		$tarFile = $this->testDir . '/test.tar';
		$this->createTarWithMtime($tarFile, 'test.txt', 'test content', time() - 3600);

		$extractor = new TTarFileExtractor('http://example.com/test.tar');

		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tarFile);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/test.txt');
		$this->assertEquals('test content', file_get_contents($this->extractDir . '/test.txt'));
	}

	public function testHttpsUrlDownloadAndExtract()
	{
		$tarFile = $this->testDir . '/test.tar';
		$this->createTarWithMtime($tarFile, 'https_test.txt', 'https content', time() - 3600);

		$extractor = new TTarFileExtractor('https://example.com/test.tar');

		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tarFile);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/https_test.txt');
		$this->assertEquals('https content', file_get_contents($this->extractDir . '/https_test.txt'));
	}
	
	// ---------------------------------------------------------------------------
	//   -----  HTTP/S URL Begin NoRetain 

	public function testHttpUrlExtractWithGzipCompression_NoRetain()
	{
		// Copy the test archive so _close() doesn't delete the shared test fixture.
		$tempArchive = sys_get_temp_dir() . '/prado_url_gzip_' . uniqid() . '.tar.gz';
		copy(__DIR__ . '/data/test_gzip.tar.gz', $tempArchive);

		$extractor = new TTarFileExtractor('http://example.com/test.tar.gz');
		$extractor->setRetainTempFile(false);
		
		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/gzip_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $extractor->getCompression());
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded gz file should be deleted after decompression');
	}

	public function testHttpsUrlExtractWithGzipCompression_NoRetain()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_url_gzip_' . uniqid() . '.tar.gz';
		copy(__DIR__ . '/data/test_gzip.tar.gz', $tempArchive);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.gz');
		$extractor->setRetainTempFile(false);

		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/gzip_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $extractor->getCompression());
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded GZ file should be deleted after decompression');
	}

	public function testHttpUrlExtractWithBzip2Compression_NoRetain()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_url_bzip2_' . uniqid() . '.tar.bz2';
		copy(__DIR__ . '/data/test_bzip2.tar.bz2', $tempArchive);

		$extractor = new TTarFileExtractor('http://example.com/test.tar.bz2');
		$extractor->setRetainTempFile(false);

		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/bzip2_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $extractor->getCompression());
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded bz2 file should be deleted after decompression');
	}

	public function testHttpsUrlExtractWithBzip2Compression_NoRetain()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_url_bzip2_' . uniqid() . '.tar.bz2';
		copy(__DIR__ . '/data/test_bzip2.tar.bz2', $tempArchive);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.bz2');
		$extractor->setRetainTempFile(false);

		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/bzip2_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $extractor->getCompression());
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded XZ file should be deleted after decompression');
	}

	public function testHttpUrlExtractWithLzmaCompressionReplacesTempFile_NoRetain()
	{
		// Copy to a temp file that simulates a completed URL download.
		// Using a copy prevents _openCompressedRead from deleting the real test fixture.
		$tempXzFile = sys_get_temp_dir() . '/prado_url_xz_' . uniqid() . '.tar.xz';
		copy(__DIR__ . '/data/test_xz.tar.xz', $tempXzFile);

		$extractor = new TTarFileExtractor('http://example.com/test.tar.xz');
		$extractor->setRetainTempFile(false);

		$reflection = new \ReflectionClass($extractor);
		$tempTarnameProperty = $reflection->getProperty('_temp_tarpath');
		$tempTarnameProperty->setAccessible(true);
		$tempTarnameProperty->setValue($extractor, $tempXzFile);

		$result = $extractor->extract($this->extractDir);

		$newTempXzFile = $tempTarnameProperty->getValue($extractor);
		$this->assertNotEquals($tempXzFile, $newTempXzFile);
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/xz_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $extractor->getCompression());
		$this->assertFileDoesNotExist($newTempXzFile, 'Downloaded XZ file should be deleted after decompression');
	}

	public function testHttpsUrlExtractWithLzmaCompressionReplacesTempFile_NoRetain()
	{
		$tempXzFile = sys_get_temp_dir() . '/prado_url_xz_' . uniqid() . '.tar.xz';
		copy(__DIR__ . '/data/test_xz.tar.xz', $tempXzFile);

		$extractor = new TTarFileExtractor('https://example.com/test.tar.xz');
		$extractor->setRetainTempFile(false);

		$reflection = new \ReflectionClass($extractor);
		$tempTarnameProperty = $reflection->getProperty('_temp_tarpath');
		$tempTarnameProperty->setAccessible(true);
		$tempTarnameProperty->setValue($extractor, $tempXzFile);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/xz_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $extractor->getCompression());
		$this->assertFileDoesNotExist($tempXzFile, 'Downloaded XZ file should be deleted after decompression');
	}
	
	
	//	-------  HTTP/S extractions - End NoRetain - Begin Retain
	
	public function testHttpUrlExtractWithGzipCompression_Retain()
	{
		// Copy the test archive so _close() doesn't delete the shared test fixture.
		$tempArchive = sys_get_temp_dir() . '/prado_url_gzip_' . uniqid() . '.tar.gz';
		copy(__DIR__ . '/data/test_gzip.tar.gz', $tempArchive);
	
		$extractor = new TTarFileExtractor('http://example.com/test.tar.gz');
		$extractor->setRetainTempFile(true);
	
		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);
	
		$result = $extractor->extract($this->extractDir);
	
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/gzip_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $extractor->getCompression());
		$this->assertFileExists($tempArchive);
		$extractor = null;
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded gz file should be deleted after decompression');
	}
	
	public function testHttpsUrlExtractWithGzipCompression_Retain()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_url_gzip_' . uniqid() . '.tar.gz';
		copy(__DIR__ . '/data/test_gzip.tar.gz', $tempArchive);
	
		$extractor = new TTarFileExtractor('https://example.com/test.tar.gz');
		$extractor->setRetainTempFile(true);
	
		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);
	
		$result = $extractor->extract($this->extractDir);
	
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/gzip_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_GZIP, $extractor->getCompression());
		$this->assertFileExists($tempArchive);
		$extractor = null;
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded GZ file should be deleted after decompression');
	}
	
	public function testHttpUrlExtractWithBzip2Compression_Retain()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_url_bzip2_' . uniqid() . '.tar.bz2';
		copy(__DIR__ . '/data/test_bzip2.tar.bz2', $tempArchive);
	
		$extractor = new TTarFileExtractor('http://example.com/test.tar.bz2');
		$extractor->setRetainTempFile(true);
	
		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);
	
		$result = $extractor->extract($this->extractDir);
	
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/bzip2_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $extractor->getCompression());
		$this->assertFileExists($tempArchive);
		$extractor = null;
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded bz2 file should be deleted after decompression');
	}
	
	public function testHttpsUrlExtractWithBzip2Compression_Retain()
	{
		$tempArchive = sys_get_temp_dir() . '/prado_url_bzip2_' . uniqid() . '.tar.bz2';
		copy(__DIR__ . '/data/test_bzip2.tar.bz2', $tempArchive);
	
		$extractor = new TTarFileExtractor('https://example.com/test.tar.bz2');
		$extractor->setRetainTempFile(true);
	
		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tempArchive);
	
		$result = $extractor->extract($this->extractDir);
	
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/bzip2_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_BZIP2, $extractor->getCompression());
		$this->assertFileExists($tempArchive);
		$extractor = null;
		$this->assertFileDoesNotExist($tempArchive, 'Downloaded bz2 file should be deleted after decompression');
	}
	
	public function testHttpUrlExtractWithLzmaCompressionReplacesTempFile_Retain()
	{
		// Copy to a temp file that simulates a completed URL download.
		// Using a copy prevents _openCompressedRead from deleting the real test fixture.
		$tempXzFile = sys_get_temp_dir() . '/prado_url_xz_' . uniqid() . '.tar.xz';
		copy(__DIR__ . '/data/test_xz.tar.xz', $tempXzFile);
	
		$extractor = new TTarFileExtractor('http://example.com/test.tar.xz');
		$extractor->setRetainTempFile(true);
	
		$reflection = new \ReflectionClass($extractor);
		$tempTarnameProperty = $reflection->getProperty('_temp_tarpath');
		$tempTarnameProperty->setAccessible(true);
		$tempTarnameProperty->setValue($extractor, $tempXzFile);
	
		$result = $extractor->extract($this->extractDir);
		$newTempXzFile = $tempTarnameProperty->getValue($extractor);
		
		$this->assertNotEquals($tempXzFile, $newTempXzFile);
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/xz_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $extractor->getCompression());
		$this->assertFileDoesNotExist($tempXzFile, 'Downloaded XZ file should be deleted after decompression');
		$this->assertFileExists($newTempXzFile);
		$extractor = null;
		$this->assertFileDoesNotExist($newTempXzFile, 'Downloaded XZ file should be deleted after decompression');
	}
	
	public function testHttpsUrlExtractWithLzmaCompressionReplacesTempFile_Retain()
	{
		$tempXzFile = sys_get_temp_dir() . '/prado_url_xz_' . uniqid() . '.tar.xz';
		copy(__DIR__ . '/data/test_xz.tar.xz', $tempXzFile);
	
		$extractor = new TTarFileExtractor('https://example.com/test.tar.xz');
		$extractor->setRetainTempFile(true);
	
		$reflection = new \ReflectionClass($extractor);
		$tempTarnameProperty = $reflection->getProperty('_temp_tarpath');
		$tempTarnameProperty->setAccessible(true);
		$tempTarnameProperty->setValue($extractor, $tempXzFile);
	
		$result = $extractor->extract($this->extractDir);
		$newTempXzFile = $tempTarnameProperty->getValue($extractor);
		
		$this->assertNotEquals($tempXzFile, $newTempXzFile);
		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/xz_content.txt');
		$this->assertEquals(TTarFileExtractor::COMPRESSION_LZMA, $extractor->getCompression());
	
		$this->assertFileDoesNotExist($tempXzFile, 'Downloaded XZ file should be deleted after decompression');
		$this->assertFileExists($newTempXzFile);
		$extractor = null;
		$this->assertFileDoesNotExist($newTempXzFile, 'Downloaded XZ file should be deleted after decompression');
	}
	
	// ---  End HTTP/S extractions - Retain
	// ---------------------------------------------------------------------------

	public function testHttpUrlExtractMultipleFiles()
	{
		$tarFile = $this->testDir . '/multi.tar';
		$fp = fopen($tarFile, 'wb');

		$header1 = $this->createTarHeader('file1.txt', 5);
		fwrite($fp, $header1);
		fwrite($fp, "test1");
		fwrite($fp, str_repeat("\0", 512 - 5));

		$header2 = $this->createTarHeader('file2.txt', 6);
		fwrite($fp, $header2);
		fwrite($fp, "test22");
		fwrite($fp, str_repeat("\0", 512 - 6));

		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);

		$extractor = new TTarFileExtractor('http://example.com/multi.tar');

		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tarFile);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/file1.txt');
		$this->assertFileExists($this->extractDir . '/file2.txt');
		$this->assertEquals('test1', file_get_contents($this->extractDir . '/file1.txt'));
		$this->assertEquals('test22', file_get_contents($this->extractDir . '/file2.txt'));
	}

	public function testHttpsUrlExtractMultipleFiles()
	{
		$tarFile = $this->testDir . '/multi_https.tar';
		$fp = fopen($tarFile, 'wb');

		// Content lengths must match the declared sizes exactly.
		$header1 = $this->createTarHeader('https_file1.txt', 6);
		fwrite($fp, $header1);
		fwrite($fp, "https1");
		fwrite($fp, str_repeat("\0", 512 - 6));

		$header2 = $this->createTarHeader('https_file2.txt', 7);
		fwrite($fp, $header2);
		fwrite($fp, "https22");
		fwrite($fp, str_repeat("\0", 512 - 7));

		fwrite($fp, str_repeat("\0", 1024));
		fclose($fp);

		$extractor = new TTarFileExtractor('https://example.com/multi.tar');

		$reflection = new \ReflectionClass($extractor);
		$property = $reflection->getProperty('_temp_tarpath');
		$property->setAccessible(true);
		$property->setValue($extractor, $tarFile);

		$result = $extractor->extract($this->extractDir);

		$this->assertTrue($result);
		$this->assertFileExists($this->extractDir . '/https_file1.txt');
		$this->assertFileExists($this->extractDir . '/https_file2.txt');
		$this->assertEquals('https1', file_get_contents($this->extractDir . '/https_file1.txt'));
		$this->assertEquals('https22', file_get_contents($this->extractDir . '/https_file2.txt'));
	}
}
