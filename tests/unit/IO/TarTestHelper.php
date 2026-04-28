<?php

/**
 * TarTestHelper — unified TAR archive builder for unit tests.
 *
 * All TTarFileExtractor unit tests should build programmatic test archives through
 * this helper so that checksum construction, field layout, and compression helpers
 * live in exactly one place.
 *
 * @since 4.3.3
 */
class TarTestHelper
{
	/**
	 * A fixed, deterministic mtime (2021-01-01 00:00:00 UTC) used when tests need
	 * reproducible timestamps.
	 */
	public const FIXED_MTIME = 1609459200;

	// ---------------------------------------------------------------------------
	// Core header / entry builders
	// ---------------------------------------------------------------------------

	/**
	 * Build a single 512-byte POSIX ustar header block with a correctly computed
	 * unsigned-sum checksum (RFC 1003.1 §10.1.1).
	 *
	 * @param string $filename  Path stored in the archive (max 100 chars without prefix).
	 * @param int    $size      File size in bytes (0 for directories / links).
	 * @param string $typeflag  Single character: '0'=file, '5'=dir, '2'=symlink, etc.
	 * @param string $linkname  Link target (symlinks / hard-links only).
	 * @param int    $mode      UNIX permission bits (e.g. 0644).
	 * @param int    $uid       Numeric user ID.
	 * @param int    $gid       Numeric group ID.
	 * @param int    $mtime     Modification time (Unix epoch). 0 = current time.
	 * @param string $uname     Symbolic user name (max 32 chars).
	 * @param string $gname     Symbolic group name (max 32 chars).
	 * @return string  Exactly 512 bytes.
	 */
	public static function header(
		string $filename,
		int $size,
		string $typeflag = '0',
		string $linkname = '',
		int $mode = 0,
		int $uid = 0,
		int $gid = 0,
		int $mtime = 0,
		string $uname = 'root',
		string $gname = 'root'
	): string {
		// 0 = sentinel: directories need execute bits for traversal; files default to 0644.
		if ($mode === 0) {
			$mode = ($typeflag === '5') ? 0o755 : 0o644;
		}

		if ($mtime === 0) {
			$mtime = time();
		}

		// First 156 bytes: name + numeric fields + checksum placeholder.
		$raw = pack(
			'a100a8a8a8a12a12a8',
			$filename,
			sprintf('%07o', $mode) . "\x00",   // 7 octal digits + NUL = 8 bytes
			sprintf('%07o', $uid)  . "\x00",
			sprintf('%07o', $gid)  . "\x00",
			sprintf('%011o', $size)  . "\x00", // 11 octal digits + NUL = 12 bytes
			sprintf('%011o', $mtime) . "\x00",
			'        '                         // 8-space checksum placeholder (bytes 148-155)
		);

		// Next 356 bytes: typeflag, linkname, ustar magic, uname/gname, etc.
		$raw .= pack(
			'a1a100a6a2a32a32a8a8a155a12',
			$typeflag,
			$linkname,
			'ustar ',    // magic (6 bytes, GNU-style "ustar " with trailing space)
			'00',        // version
			$uname,
			$gname,
			'',          // devmajor
			'',          // devminor
			'',          // prefix
			''           // padding to 512
		);

		// Pad to 512 bytes (both packs already total 512, but be defensive).
		$raw = str_pad($raw, 512, "\x00");

		// Compute unsigned-sum checksum, treating checksum field (bytes 148-155) as spaces.
		$checksum = 0;
		for ($i = 0; $i < 148; $i++) {
			$checksum += ord($raw[$i]);
		}
		$checksum += 8 * 32; // bytes 148-155 treated as 8 ASCII spaces
		for ($i = 156; $i < 512; $i++) {
			$checksum += ord($raw[$i]);
		}

		// Embed checksum: 6 octal digits + NUL + space = 8 bytes at offset 148.
		return substr($raw, 0, 148)
			. sprintf('%06o', $checksum) . "\x00 "
			. substr($raw, 156);
	}

	/**
	 * Build a full tar entry: a 512-byte header followed by content padded to the
	 * nearest 512-byte boundary.
	 *
	 * @param string $filename
	 * @param string $content   File content bytes. Empty for directories / links.
	 * @param string $typeflag
	 * @param string $linkname
	 * @param int    $mode
	 * @param int    $uid
	 * @param int    $gid
	 * @param int    $mtime     0 = current time.
	 * @param string $uname
	 * @param string $gname
	 * @return string  Header block + zero or more data blocks.
	 */
	public static function entry(
		string $filename,
		string $content = '',
		string $typeflag = '0',
		string $linkname = '',
		int $mode = 0,
		int $uid = 0,
		int $gid = 0,
		int $mtime = 0,
		string $uname = 'root',
		string $gname = 'root'
	): string {
		$size = strlen($content);
		$hdr = self::header($filename, $size, $typeflag, $linkname, $mode, $uid, $gid, $mtime, $uname, $gname);
		if ($size === 0) {
			return $hdr;
		}
		$paddedSize = (int)(ceil($size / 512) * 512);
		return $hdr . str_pad($content, $paddedSize, "\x00");
	}

	// ---------------------------------------------------------------------------
	// GNU long-name / long-link extensions
	// ---------------------------------------------------------------------------

	/**
	 * Build a GNU long-name extension pair (typeflag 'L') followed by a real file entry.
	 * Use when the filename exceeds 100 characters.
	 *
	 * Returns an array of two raw binary strings:
	 *   [0] = GNU 'L' block (header + padded long-name data)
	 *   [1] = real entry block (header + optional data blocks)
	 *
	 * Pass both elements into {@see archive()} to assemble a complete tar.
	 *
	 * @param string $longFilename  Full filename stored in the 'L' extension (> 100 chars).
	 * @param string $content       Content for the real file entry.
	 * @param string $typeflag      Typeflag for the real entry (default '0' = regular file).
	 * @param string $linkname      Linkname for the real entry (symlinks / hard-links).
	 * @param int    $mtime         Modification time for the real entry. 0 = current time.
	 * @return array{0:string,1:string}
	 */
	public static function gnuLongNamePair(
		string $longFilename,
		string $content = '',
		string $typeflag = '0',
		string $linkname = '',
		int $mtime = 0
	): array {
		// Long-name data block: long filename followed by a NUL terminator.
		$longData   = $longFilename . "\x00";
		$gnuHdr     = self::header('././@LongLink', strlen($longData), 'L', '', 0, 0, 0, 0, '', '');
		$paddedData = str_pad($longData, (int)(ceil(strlen($longData) / 512) * 512), "\x00");

		// Real entry — the short name slot holds the first 100 chars of the long name.
		$realEntry = self::entry(substr($longFilename, 0, 100), $content, $typeflag, $linkname, 0644, 0, 0, $mtime);

		return [$gnuHdr . $paddedData, $realEntry];
	}

	/**
	 * Build a GNU long-linkname extension pair (typeflag 'K') followed by a symlink entry.
	 * Use when the symlink target exceeds 100 characters.
	 *
	 * Returns an array of two raw binary strings:
	 *   [0] = GNU 'K' block (header + padded long-linkname data)
	 *   [1] = real symlink entry block
	 *
	 * @param string $linkFilename  The symlink filename stored in the archive.
	 * @param string $longLinkname  The full symlink target path (> 100 chars).
	 * @return array{0:string,1:string}
	 */
	public static function gnuLongLinkPair(string $linkFilename, string $longLinkname): array
	{
		$longData   = $longLinkname . "\x00";
		$gnuHdr     = self::header('././@LongLink', strlen($longData), 'K', '', 0, 0, 0, 0, '', '');
		$paddedData = str_pad($longData, (int)(ceil(strlen($longData) / 512) * 512), "\x00");
		$realEntry  = self::entry($linkFilename, '', '2', substr($longLinkname, 0, 100));
		return [$gnuHdr . $paddedData, $realEntry];
	}

	// ---------------------------------------------------------------------------
	// Archive assembly
	// ---------------------------------------------------------------------------

	/**
	 * Concatenate an array of raw entry blocks and append the required 1024-byte
	 * end-of-archive marker (two consecutive NUL-filled 512-byte blocks).
	 *
	 * @param string[] $blocks  Each element is one or more raw 512-byte blocks (header
	 *                          plus optional data blocks) as returned by {@see entry()},
	 *                          {@see header()}, or the GNU pair helpers.
	 * @return string  Complete tar archive binary.
	 */
	public static function archive(array $blocks): string
	{
		return implode('', $blocks) . str_repeat("\x00", 1024);
	}

	// ---------------------------------------------------------------------------
	// File writers
	// ---------------------------------------------------------------------------

	/**
	 * Write a plain uncompressed .tar file.
	 *
	 * @param string   $path    Destination file path.
	 * @param string[] $entries Raw entry blocks to include.
	 */
	public static function writeTar(string $path, array $entries): void
	{
		file_put_contents($path, self::archive($entries));
	}

	/**
	 * Write a gzip-compressed .tar.gz file.
	 *
	 * @param string   $path
	 * @param string[] $entries
	 */
	public static function writeTarGz(string $path, array $entries): void
	{
		$data = self::archive($entries);
		$gz   = gzopen($path, 'wb9');
		gzwrite($gz, $data);
		gzclose($gz);
	}

	/**
	 * Write a bzip2-compressed .tar.bz2 file.
	 * Marks the calling test as skipped when the bz2 extension is unavailable.
	 *
	 * @param string                        $path
	 * @param string[]                      $entries
	 * @param \PHPUnit\Framework\TestCase   $test   The active test case (used for markTestSkipped).
	 */
	public static function writeTarBz2(
		string $path,
		array $entries,
		\PHPUnit\Framework\TestCase $test
	): void {
		if (!function_exists('bzcompress')) {
			$test->markTestSkipped('bz2 extension not available');
		}
		file_put_contents($path, bzcompress(self::archive($entries)));
	}

	// ---------------------------------------------------------------------------
	// Convenience wrappers kept for backward compat / readability
	// ---------------------------------------------------------------------------

	/**
	 * Alias for {@see header()} — builds a 512-byte header with no data blocks.
	 * Useful when building archives with low-level `fwrite` calls or when the data
	 * blocks must be written separately (e.g. in ZipSlip tests).
	 *
	 * @see header()
	 */
	public static function createHeader(
		string $filename,
		int $size,
		string $typeflag = '0',
		string $linkname = '',
		int $mode = 0,
		int $uid = 0,
		int $gid = 0,
		int $mtime = 0,
		string $uname = 'root',
		string $gname = 'root'
	): string {
		return self::header($filename, $size, $typeflag, $linkname, $mode, $uid, $gid, $mtime, $uname, $gname);
	}
}
