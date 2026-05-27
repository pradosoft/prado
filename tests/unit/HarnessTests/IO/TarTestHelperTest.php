<?php

/**
 * TarTestHelperTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/../../PradoUnitRequires.php';

/**
 * Tests for {@see TarTestHelper}.
 *
 * Verifies the ustar header layout, checksum computation, padding rules,
 * GNU long-name / long-link extensions, end-of-archive marker, and the three
 * on-disk writers (plain, gzip, bzip2). The helper itself underpins every
 * `TTarFileExtractor*Test`, so failures here cascade — these tests pin its
 * contract.
 *
 * @package System.Harness.IO
 */
class TarTestHelperTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// header() — block size, field encoding, checksum
	// -----------------------------------------------------------------------

	public function testHeader_returnsExactly512Bytes(): void
	{
		$hdr = TarTestHelper::header('foo.txt', 0);
		$this->assertSame(512, strlen($hdr));
	}

	public function testHeader_storesFilenameInFirst100Bytes(): void
	{
		$hdr = TarTestHelper::header('hello.txt', 0);
		$this->assertSame('hello.txt', rtrim(substr($hdr, 0, 100), "\x00"));
	}

	public function testHeader_storesUstarMagic(): void
	{
		// ustar magic occupies offset 257..262 inclusive (6 bytes).
		$hdr = TarTestHelper::header('foo.txt', 0);
		$this->assertSame('ustar ', substr($hdr, 257, 6));
	}

	public function testHeader_typeflagOffset156(): void
	{
		// typeflag is at offset 156 (single byte).
		$hdr = TarTestHelper::header('dir/', 0, '5');
		$this->assertSame('5', $hdr[156]);
	}

	public function testHeader_defaultMode_regularFile_is0644(): void
	{
		$hdr = TarTestHelper::header('foo.txt', 0);
		// Mode field at offset 100, 8 bytes: 7 octal digits + NUL.
		$mode = rtrim(substr($hdr, 100, 8), "\x00 ");
		$this->assertSame('0000644', $mode);
	}

	public function testHeader_defaultMode_directory_is0755(): void
	{
		$hdr = TarTestHelper::header('dir/', 0, '5');
		$mode = rtrim(substr($hdr, 100, 8), "\x00 ");
		$this->assertSame('0000755', $mode);
	}

	public function testHeader_encodesSizeAsOctal(): void
	{
		$hdr = TarTestHelper::header('foo.txt', 1024);
		// Size field at offset 124, 12 bytes: 11 octal digits + NUL.
		$size = rtrim(substr($hdr, 124, 12), "\x00 ");
		$this->assertSame('00000002000', $size); // 1024 = 02000 octal
	}

	public function testHeader_encodesMtimeAsOctal(): void
	{
		$hdr = TarTestHelper::header('foo.txt', 0, '0', '', 0, 0, 0, TarTestHelper::FIXED_MTIME);
		// Mtime at offset 136, 12 bytes: 11 octal digits + NUL.
		$mtime = rtrim(substr($hdr, 136, 12), "\x00 ");
		$this->assertSame(sprintf('%011o', TarTestHelper::FIXED_MTIME), $mtime);
	}

	public function testHeader_checksumValid(): void
	{
		// Reconstruct: sum every byte except the checksum field (offsets 148..155),
		// which we treat as 8 spaces. Compare against the embedded checksum.
		$hdr = TarTestHelper::header('foo.txt', 0);
		$computed = 0;
		for ($i = 0; $i < 148; $i++) {
			$computed += ord($hdr[$i]);
		}
		$computed += 8 * 32;
		for ($i = 156; $i < 512; $i++) {
			$computed += ord($hdr[$i]);
		}
		// Embedded: 6 octal digits + NUL + space at offset 148.
		$embedded = octdec(rtrim(substr($hdr, 148, 6), "\x00 "));
		$this->assertSame($computed, $embedded);
	}

	public function testHeader_linknameStoredForSymlink(): void
	{
		// Linkname at offset 157, 100 bytes.
		$hdr = TarTestHelper::header('link', 0, '2', 'target/path');
		$this->assertSame('target/path', rtrim(substr($hdr, 157, 100), "\x00"));
	}

	public function testHeader_unameAndGnameStored(): void
	{
		// uname at offset 265, gname at offset 297; each 32 bytes.
		$hdr = TarTestHelper::header('foo.txt', 0, '0', '', 0, 0, 0, 0, 'alice', 'staff');
		$this->assertSame('alice', rtrim(substr($hdr, 265, 32), "\x00"));
		$this->assertSame('staff', rtrim(substr($hdr, 297, 32), "\x00"));
	}

	// -----------------------------------------------------------------------
	// entry() — header + padded content blocks
	// -----------------------------------------------------------------------

	public function testEntry_emptyContent_isHeaderOnly(): void
	{
		$entry = TarTestHelper::entry('foo.txt', '');
		$this->assertSame(512, strlen($entry));
	}

	public function testEntry_directoryEntry_isHeaderOnly(): void
	{
		$entry = TarTestHelper::entry('dir/', '', '5');
		$this->assertSame(512, strlen($entry));
	}

	public function testEntry_contentPaddedToNext512Boundary(): void
	{
		$entry = TarTestHelper::entry('foo.txt', str_repeat('x', 100));
		// 100 bytes of content rounds up to one 512-byte data block.
		$this->assertSame(1024, strlen($entry));
	}

	public function testEntry_contentExactly512_doesNotOverpad(): void
	{
		$entry = TarTestHelper::entry('foo.txt', str_repeat('x', 512));
		// Header + exactly one full data block.
		$this->assertSame(1024, strlen($entry));
	}

	public function testEntry_largeContent_multipleDataBlocks(): void
	{
		$entry = TarTestHelper::entry('big.bin', str_repeat('y', 1500));
		// 1500 bytes → 3 data blocks (1536 bytes), plus header.
		$this->assertSame(2048, strlen($entry));
	}

	public function testEntry_contentBytesPreserved(): void
	{
		$payload = "hello\nworld\n";
		$entry   = TarTestHelper::entry('greet.txt', $payload);
		$this->assertSame($payload, substr($entry, 512, strlen($payload)));
	}

	// -----------------------------------------------------------------------
	// gnuLongNamePair() — typeflag 'L' extension
	// -----------------------------------------------------------------------

	public function testGnuLongNamePair_returnsTwoElementArray(): void
	{
		$pair = TarTestHelper::gnuLongNamePair(str_repeat('a', 150) . '.txt');
		$this->assertCount(2, $pair);
	}

	public function testGnuLongNamePair_firstBlockHasLTypeflag(): void
	{
		$pair = TarTestHelper::gnuLongNamePair(str_repeat('a', 150) . '.txt');
		$this->assertSame('L', $pair[0][156]);
	}

	public function testGnuLongNamePair_firstBlockMarkedAsLongLink(): void
	{
		$pair = TarTestHelper::gnuLongNamePair(str_repeat('a', 150) . '.txt');
		$this->assertSame('././@LongLink', rtrim(substr($pair[0], 0, 100), "\x00"));
	}

	public function testGnuLongNamePair_realEntryNameIsFirst100Chars(): void
	{
		$long = str_repeat('a', 150) . '.txt';
		$pair = TarTestHelper::gnuLongNamePair($long);
		$this->assertSame(substr($long, 0, 100), rtrim(substr($pair[1], 0, 100), "\x00"));
	}

	public function testGnuLongNamePair_realEntryCarriesContent(): void
	{
		$long = str_repeat('a', 150) . '.txt';
		$pair = TarTestHelper::gnuLongNamePair($long, 'payload');
		// Real entry: 1 header block + 1 data block padded to 512.
		$this->assertSame(1024, strlen($pair[1]));
		$this->assertSame('payload', substr($pair[1], 512, 7));
	}

	// -----------------------------------------------------------------------
	// gnuLongLinkPair() — typeflag 'K' extension
	// -----------------------------------------------------------------------

	public function testGnuLongLinkPair_returnsTwoElementArray(): void
	{
		$pair = TarTestHelper::gnuLongLinkPair('link', str_repeat('b', 150));
		$this->assertCount(2, $pair);
	}

	public function testGnuLongLinkPair_firstBlockHasKTypeflag(): void
	{
		$pair = TarTestHelper::gnuLongLinkPair('link', str_repeat('b', 150));
		$this->assertSame('K', $pair[0][156]);
	}

	public function testGnuLongLinkPair_realEntryIsSymlink(): void
	{
		$pair = TarTestHelper::gnuLongLinkPair('link', str_repeat('b', 150));
		// Typeflag '2' = symlink at offset 156 of the real entry.
		$this->assertSame('2', $pair[1][156]);
	}

	// -----------------------------------------------------------------------
	// archive() — assembly + end-of-archive marker
	// -----------------------------------------------------------------------

	public function testArchive_appendsEndOfArchive1024Nuls(): void
	{
		$entry = TarTestHelper::entry('foo.txt', 'data');
		$arch  = TarTestHelper::archive([$entry]);
		// Last 1024 bytes must all be NUL.
		$this->assertSame(str_repeat("\x00", 1024), substr($arch, -1024));
	}

	public function testArchive_concatenatesBlocksInOrder(): void
	{
		$a = TarTestHelper::entry('a.txt', 'A');
		$b = TarTestHelper::entry('b.txt', 'B');
		$arch = TarTestHelper::archive([$a, $b]);
		$this->assertSame(strlen($a) + strlen($b) + 1024, strlen($arch));
		// Content bytes preserved in declared order.
		$this->assertSame('A', $arch[512]);
		$this->assertSame('B', $arch[strlen($a) + 512]);
	}

	public function testArchive_emptyArray_isJustEndMarker(): void
	{
		$arch = TarTestHelper::archive([]);
		$this->assertSame(1024, strlen($arch));
		$this->assertSame(str_repeat("\x00", 1024), $arch);
	}

	// -----------------------------------------------------------------------
	// writeTar() / writeTarGz() / writeTarBz2()
	// -----------------------------------------------------------------------

	public function testWriteTar_producesReadableArchive(): void
	{
		$path = tempnam(sys_get_temp_dir(), 'tarhelper_');
		try {
			TarTestHelper::writeTar($path, [TarTestHelper::entry('foo.txt', 'bar')]);
			$bytes = file_get_contents($path);
			$this->assertStringContainsString('foo.txt', $bytes);
			$this->assertStringContainsString('bar', $bytes);
			// Ends with the EOA marker.
			$this->assertSame(str_repeat("\x00", 1024), substr($bytes, -1024));
		} finally {
			@unlink($path);
		}
	}

	public function testWriteTarGz_producesGzipMagicBytes(): void
	{
		$path = tempnam(sys_get_temp_dir(), 'tarhelper_') . '.tgz';
		try {
			TarTestHelper::writeTarGz($path, [TarTestHelper::entry('foo.txt', 'bar')]);
			$bytes = file_get_contents($path);
			// gzip magic = 1f 8b.
			$this->assertSame("\x1f\x8b", substr($bytes, 0, 2));
			// Decompressing yields the same plain-tar bytes.
			$plain = gzdecode($bytes);
			$this->assertStringContainsString('foo.txt', $plain);
			$this->assertStringContainsString('bar', $plain);
		} finally {
			@unlink($path);
		}
	}

	public function testWriteTarBz2_producesBzipMagicWhenExtensionAvailable(): void
	{
		if (!function_exists('bzcompress')) {
			$this->markTestSkipped('bz2 extension not available');
		}
		$path = tempnam(sys_get_temp_dir(), 'tarhelper_') . '.tbz2';
		try {
			TarTestHelper::writeTarBz2($path, [TarTestHelper::entry('foo.txt', 'bar')], $this);
			$bytes = file_get_contents($path);
			// bzip2 magic = "BZh".
			$this->assertSame('BZh', substr($bytes, 0, 3));
		} finally {
			@unlink($path);
		}
	}

	// -----------------------------------------------------------------------
	// Constants and convenience wrappers
	// -----------------------------------------------------------------------

	public function testFixedMtime_is2021NewYearUtc(): void
	{
		$this->assertSame(1609459200, TarTestHelper::FIXED_MTIME);
		$this->assertSame('2021-01-01 00:00:00', gmdate('Y-m-d H:i:s', TarTestHelper::FIXED_MTIME));
	}

	public function testCreateHeader_aliasesHeader(): void
	{
		// createHeader() is documented as an alias for header() — identical bytes.
		$a = TarTestHelper::header('foo.txt', 0, '0', '', 0, 0, 0, TarTestHelper::FIXED_MTIME);
		$b = TarTestHelper::createHeader('foo.txt', 0, '0', '', 0, 0, 0, TarTestHelper::FIXED_MTIME);
		$this->assertSame($a, $b);
	}
}
