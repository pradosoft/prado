<?php

/**
 * TTarFileExtractor class file
 *
 * @author Vincent Blavet <vincent@phpconcept.net>
 */

namespace Prado\IO;

use Prado\Prado;

/* vim: set ts=4 sw=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Vincent Blavet <vincent@phpconcept.net>                      |
// +----------------------------------------------------------------------+
//
// $Id: TTarFileExtractor.php 3188 2012-07-12 12:13:23Z ctrlaltca $

/**
 * Extracts files from TAR archives.
 *
 * Supported formats:
 *  - Uncompressed tar     (.tar)
 *  - Gzip-compressed tar  (.tar.gz, .tgz)   — requires the zlib extension
 *  - Bzip2-compressed tar (.tar.bz2, .tbz2) — requires the bz2 extension
 *  - LZMA-compressed tar  (.tar.xz, .txz)   — requires the `xz` or `xzdec` system command
 *
 * The format is detected from magic bytes. File extension is used as a fallback when
 * bytes are unavailable, for example when passed a remote URL before the file is downloaded.
 *
 * Remote archives (http://, https://, and ftp://) are downloaded to a system temp file,
 * extracted, and the temp file is deleted automatically on completion.
 *
 * Security — in strict mode (the default) extraction is aborted if any entry would escape
 * the destination directory (Zip Slip Attack), or point a symlink or hard link outside it.
 * With strict mode disabled those entries are skipped and recorded via {@see getSkippedFiles()}.
 *
 * Basic usage:
 * ```php
 * $extractor = new TTarFileExtractor('/path/to/archive.tar.gz');
 * $extractor->extract('/destination/directory');
 * ```
 *
 * Non-strict extraction (skip bad entries, continue):
 * ```php
 * $extractor = new TTarFileExtractor('https://example.com/release.tar.xz');
 * $extractor->setStrict(false)->extract('/opt/app');
 * if ($extractor->hasSkippedFiles()) {
 *     foreach ($extractor->getSkippedFiles() as $entry) {
 *         Prado::warning($entry['type'] . ': ' . $entry['filename'], self::class);
 *     }
 * }
 * ```
 *
 * @author Vincent Blavet <vincent@phpconcept.net>
 * @author Brad Anderson <belisoful@icloud.com> Zip Slip Safeguards, decompression
 * @since 3.0
 */
class TTarFileExtractor
{
	/**
	 * @var int Uncompressed tar archive
	 * @since 4.3.3
	 */
	public const COMPRESSION_NONE = 0;

	/**
	 * @var int Gzip-compressed tar (.tar.gz, .tgz)
	 * @since 4.3.3
	 */
	public const COMPRESSION_GZIP = 1;

	/**
	 * @var int Bzip2-compressed tar (.tar.bz2, .tbz2)
	 * @since 4.3.3
	 */
	public const COMPRESSION_BZIP2 = 2;

	/**
	 * @var int LZMA-compressed tar (.tar.xz, .txz)
	 * @since 4.3.3
	 */
	public const COMPRESSION_LZMA = 3;

	/**
	 * @var string Name of the Tar
	 */
	private $_tarname = '';

	/**
	 * @var null|resource file descriptor
	 */
	private $_file = 0;

	/**
	 * @var float the delay to wait for downloading url before the timeout, default 6 seconds
	 * @since 4.3.3
	 */
	private $_urlTimeout = 6.0;

	/**
	 * @var string Local Tar name of a remote Tar (http://, https://, or ftp://)
	 */
	private $_temp_tarname = '';

	/**
	 * @var bool Whether to fail on security issues (zip slip, symlink/hardlink attacks).
	 *            When true (default), extraction fails on any security issue.
	 *            When false, security issues are logged but extraction continues.
	 * @since 4.3.3
	 */
	private $_strict = true;

	/**
	 * @var array List of skipped files due to security issues when Strict is false.
	 *            Each entry contains: type, filename, linkname (if symlink/hardlink),
	 *            header copy, timestamp
	 * @since 4.3.3
	 */
	private array $_skippedFiles = [];

	/**
	 * @var int Compression type detected for this archive for processing only
	 * @since 4.3.3
	 */
	private $_compression = self::COMPRESSION_NONE;

	/**
	 * @var int Compression type detected for this archive (persists after extraction)
	 * @since 4.3.3
	 */
	private $_detectedCompression = self::COMPRESSION_NONE;

	/**
	 * Archive_Tar Class constructor. This flavour of the constructor only
	 * declare a new Archive_Tar object, identifying it by the name of the
	 * tar file.
	 *
	 * @param string $p_tarname The name of the tar archive to create
	 */
	public function __construct($p_tarname)
	{
		$this->_tarname = $p_tarname;
	}

	/**
	 * Destructor.
	 * Cleans up temporary files and closes file handles.
	 */
	public function __destruct()
	{
		$this->_close();
	}

	/**
	 * Extracts the archive to the specified path.
	 *
	 * @param string $p_path The path where to extract the archive. If empty, extracts to current directory.
	 * @return bool True on success, false on error.
	 */
	public function extract($p_path = '')
	{
		return $this->extractModify($p_path, '');
	}

	/**
	 * Returns whether strict mode is enabled.
	 * When strict, extraction fails on any security issue (zip slip, symlink/hardlink attacks).
	 * When not strict, security issues are logged but extraction continues.
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	public function getStrict(): bool
	{
		return $this->_strict;
	}

	/**
	 * Sets whether strict mode is enabled.
	 * When strict (true, default), extraction fails on any security issue.
	 * When not strict (false), security issues are logged but extraction continues.
	 *
	 * @param bool $value
	 * @return static $this
	 * @since 4.3.3
	 */
	public function setStrict(bool $value): static
	{
		$this->_strict = $value;
		return $this;
	}

	/**
	 * Returns whether any files were skipped due to security issues.
	 *
	 * @return bool True if files were skipped, false otherwise
	 * @since 4.3.3
	 */
	public function hasSkippedFiles(): bool
	{
		return count($this->_skippedFiles) > 0;
	}

	/**
	 * Returns the list of files skipped due to security issues.
	 *
	 * @return array List of skipped file info, each containing:
	 *   - type: string The type of security issue ('zip_slip', 'symlink', 'hardlink')
	 *   - filename: string The filename that was skipped
	 *   - linkname: string|null The link target (for symlink/hardlink)
	 *   - header: array Copy of the tar header entry
	 *   - timestamp: float Unix timestamp when the file was skipped
	 * @since 4.3.3
	 */
	public function getSkippedFiles(): array
	{
		return $this->_skippedFiles;
	}

	/**
	 * Clears the skipped files list.
	 *
	 * @return static $this
	 * @since 4.3.3
	 */
	public function clearSkippedFiles(): static
	{
		$this->_skippedFiles = [];
		return $this;
	}

	/**
	 * Returns the 'http' and 'https' timeout time in seconds for fetching URLs.
	 * @return float Timeout time for fetching URLs, default 6.0 seconds
	 * @since 4.3.3
	 */
	public function getUrlTimeout(): float
	{
		return $this->_urlTimeout;
	}

	/**
	 * Sets the 'http' and 'https' timeout time in seconds for fetching URLs.
	 * @param float $value The timeout time for fetching URLs.
	 * @return static $this
	 * @since 4.3.3
	 */
	public function setUrlTimeout(float $value): static
	{
		$this->_urlTimeout = $value;
		return $this;
	}

	/**
	 * Returns the compression type of the archive.
	 *
	 * @return int One of the COMPRESSION_* constants
	 * @since 4.3.3
	 */
	public function getCompression(): int
	{
		return $this->_detectedCompression;
	}

	/**
	 * Detects compression type from file magic bytes and extension.
	 *
	 * For local files, magic bytes are checked first (most reliable).
	 * For URLs, magic-byte detection is skipped entirely — the URL is never
	 * opened here; extension-based detection is used instead, since at this
	 * call site the URL has already been downloaded to a local temp file when
	 * the file path is a URL (only unit tests call this method directly with
	 * a URL string).
	 *
	 * @param string $tarname The tar archive filename or URL
	 * @return int One of the COMPRESSION_* constants
	 * @since 4.3.3
	 */
	private function _detectCompression(string $tarname): int
	{
		// Check magic bytes first (most reliable) — but only for local files.
		// Skip fopen for remote URLs: attempting to open an unreachable URL
		// would stall until the system timeout fires.
		$isUrl = str_starts_with($tarname, 'http://')
			|| str_starts_with($tarname, 'https://')
			|| str_starts_with($tarname, 'ftp://');
		$handle = $isUrl ? false : @fopen($tarname, 'rb');
		if ($handle) {
			$magic = fread($handle, 6);
			fclose($handle);

			if ($magic !== false && strlen($magic) >= 2) {
				$bytes = array_values(unpack('C6', $magic));

				// gzip: 0x1f 0x8b
				if ($bytes[0] === 0x1f && $bytes[1] === 0x8b) {
					if (function_exists('gzopen')) {
						return self::COMPRESSION_GZIP;
					}
				}
				// bzip2: 'BZ' (0x42 0x5a)
				if ($bytes[0] === 0x42 && $bytes[1] === 0x5a) {
					if (function_exists('bzopen')) {
						return self::COMPRESSION_BZIP2;
					}
				}
				// lzma/xz: 0xfd followed by "7zXZ\x00"
				if ($bytes[0] === 0xfd && strlen($magic) >= 6) {
					if ($magic === "\xfd\x37\x7a\x58\x5a\x00") {
						// Check for xz command availability
						$xzDec = trim(shell_exec('which xzdec'));
						$xzCmd = trim(shell_exec('which xz'));
						if ($xzDec || $xzCmd) {
							return self::COMPRESSION_LZMA;
						}
					}
				}
			}
		}

		// Fallback to extension detection (with availability checks)
		$lower = strtolower($tarname);
		if (str_ends_with($lower, '.tar.gz') || str_ends_with($lower, '.tgz')) {
			if (function_exists('gzopen')) {
				return self::COMPRESSION_GZIP;
			}
		}
		if (str_ends_with($lower, '.tar.bz2') || str_ends_with($lower, '.tbz2')) {
			if (function_exists('bzopen')) {
				return self::COMPRESSION_BZIP2;
			}
		}
		if (str_ends_with($lower, '.tar.xz') || str_ends_with($lower, '.txz')) {
			// Check for xz command availability
			$xzDec = trim(shell_exec('which xzdec'));
			$xzCmd = trim(shell_exec('which xz'));
			if ($xzDec || $xzCmd) {
				return self::COMPRESSION_LZMA;
			}
		}
		return self::COMPRESSION_NONE;
	}

	/**
	 * Opens a compressed file for reading, returning a stream handle.
	 *
	 * @param string $filename The file to open
	 * @param int $compression The compression type (COMPRESSION_*)
	 * @param bool $isTemporary
	 * @return false|resource The stream handle, or false on failure
	 * @since 4.3.3
	 */
	private function _openCompressedRead(string &$filename, int $compression, bool $isTemporary)
	{
		switch ($compression) {
			case self::COMPRESSION_GZIP:
				if (!function_exists('gzopen')) {
					$this->_error('zlib extension is required for gzip compression');
					return false;
				}
				$handle = @gzopen($filename, 'rb');
				if ($handle === false) {
					$this->_error('Unable to open gzip archive: ' . $filename);
					return false;
				}
				return $handle;

			case self::COMPRESSION_BZIP2:
				if (!function_exists('bzopen')) {
					$this->_error('bzip2 extension is required for bzip2 compression');
					return false;
				}
				$handle = @bzopen($filename, 'r');
				if ($handle === false) {
					$this->_error('Unable to open bzip2 archive: ' . $filename);
					return false;
				}
				return $handle;

			case self::COMPRESSION_LZMA:
				// Check for xz command availability
				$xzDec = trim(shell_exec('which xzdec') ?: '');
				$xzCmd = trim(shell_exec('which xz') ?: '');
				if (!$xzDec && !$xzCmd) {
					$this->_error('xz command is required for LZMA compression');
					return false;
				}
				// For LZMA/XZ, decompress to a temp file first
				$tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('lzma') . '.tar';

				// xzdec writes to stdout with no flags required (filename only).
				// xz uses -dc (decompress, stdout). Both redirect to the temp file.
				$command = $xzDec
					? escapeshellarg($xzDec) . ' ' . escapeshellarg($filename) . ' > ' . escapeshellarg($tempFile)
					: escapeshellarg($xzCmd) . ' -dc ' . escapeshellarg($filename) . ' > ' . escapeshellarg($tempFile);

				$output = [];
				$returnVar = -1;
				exec($command, $output, $returnVar);

				if (!file_exists($tempFile)) {
					if ($returnVar !== 0) {
						$this->_error('Unable to decompress LZMA archive: decompression command failed');
						return false;
					}
					$this->_error('Unable to decompress LZMA archive: temp file not created');
					return false;
				}

				if ($isTemporary) {
					// Delete the original downloaded/compressed file now that it has been
					// decompressed. Update $filename (by reference) to point to the
					// decompressed tar so _openRead() can update _temp_tarname accordingly.
					@unlink($filename);
					$filename = $tempFile;
				}

				$handle = @fopen($tempFile, 'rb');
				if ($handle === false) {
					$this->_error('Unable to open decompressed LZMA file: ' . $tempFile);
					return false;
				}
				return $handle;

			default:
				return false;
		}
	}

	/**
	 * This method extract all the content of the archive in the directory
	 * indicated by $p_path. When relevant the memorized path of the
	 * files/dir can be modified by removing the $p_remove_path path at the
	 * beginning of the file/dir path.
	 * While extracting a file, if the directory path does not exists it is
	 * created.
	 * While extracting a file, if the file already exists it is replaced
	 * without looking for last modification date.
	 * While extracting a file, if the file already exists and is write
	 * protected, the extraction is aborted.
	 * While extracting a file, if a directory with the same name already
	 * exists, the extraction is aborted.
	 * While extracting a directory, if a file with the same name already
	 * exists, the extraction is aborted.
	 * While extracting a file/directory if the destination directory exist
	 * and is write protected, or does not exist but can not be created,
	 * the extraction is aborted.
	 * If after extraction an extracted file does not show the correct
	 * stored file size, the extraction is aborted.
	 * When the extraction is aborted, a PEAR error text is set and false
	 * is returned. However the result can be a partial extraction that may
	 * need to be manually cleaned.
	 *
	 * @param string $p_path         The path of the directory where the
	 *                               files/dir need to by extracted.
	 * @param string $p_remove_path  Part of the memorized path that can be
	 *                               removed if present at the beginning of
	 *                               the file/dir path.
	 * @return bool               true on success, false on error.
	 * @access public
	 */
	/**
	 * Extracts the archive with optional path removal.
	 *
	 * @param string $p_path         The path where to extract the archive.
	 * @param string $p_remove_path  Path to remove from extracted file paths.
	 * @return bool True on success, false on error.
	 */
	protected function extractModify($p_path, $p_remove_path)
	{
		$v_result = true;
		$v_list_detail = [];

		if ($v_result = $this->_openRead()) {
			$v_result = $this->_extractList(
				$p_path,
				$v_list_detail,
				"complete",
				0,
				$p_remove_path
			);
			$this->_close();
		}

		return $v_result;
	}

	/**
	 * Throws an exception with the specified message.
	 *
	 * @param string $p_message The error message.
	 * @throws \Exception Always throws an exception with the given message.
	 */
	protected function _error($p_message)
	{
		throw new \Exception($p_message);
	}

	/**
	 * Checks if the given file exists and is a regular file.
	 *
	 * @param null|string $p_filename The filename to check. If null, uses the internal tar name.
	 * @return bool True if the file exists and is a regular file, false otherwise.
	 */
	private function _isArchive($p_filename = null)
	{
		if ($p_filename == null) {
			$p_filename = $this->_tarname;
		}
		clearstatcache();
		return @is_file($p_filename);
	}

	/**
	 * Opens the tar file for reading, handling local and remote files.
	 *
	 * For remote URLs (http://, https://, ftp://), downloads the file to a temporary location.
	 * Detects compression type and opens with the appropriate handler.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function _openRead()
	{
		$isTemporary = false;
		// Determine the file to use (handle remote URLs)
		if (str_starts_with($this->_tarname, 'http://') ||
			str_starts_with($this->_tarname, 'https://') ||
			str_starts_with($this->_tarname, 'ftp://')) {
			// ----- Look if a local copy need to be done
			if ($this->_temp_tarname == '') {
				$timeout = $this->getUrlTimeout();
				$this->_temp_tarname = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('tar') . '.tmp';
				// Use a short timeout so an unreachable URL fails fast instead of stalling
				$ctx = stream_context_create([
					'http' => ['timeout' => $timeout],
					'https' => ['timeout' => $timeout],
				]);
				if (!$v_file_from = @fopen($this->_tarname, 'rb', false, $ctx)) {
					$this->_error('Unable to open in read mode \''
							  . $this->_tarname . '\'');
					$this->_temp_tarname = '';
					return false;
				}
				if (!$v_file_to = @fopen($this->_temp_tarname, 'wb')) {
					$this->_error('Unable to open in write mode \''
							  . $this->_temp_tarname . '\'');
					$this->_temp_tarname = '';
					return false;
				}
				while ($v_data = @fread($v_file_from, 1024)) {
					@fwrite($v_file_to, $v_data);
				}
				@fclose($v_file_from);
				@fclose($v_file_to);
			}

			// ----- File to open if the local copy
			$v_filename = $this->_temp_tarname;
			$isTemporary = true;
		} else {
			// ----- File to open if the normal Tar file
			$v_filename = $this->_tarname;
		}

		// Detect compression type
		$this->_compression = $this->_detectCompression($v_filename);
		$this->_detectedCompression = $this->_compression;

		// Open with appropriate handler
		if ($this->_compression !== self::COMPRESSION_NONE) {
			$this->_file = $this->_openCompressedRead($v_filename, $this->_compression, $isTemporary);
			if ($this->_file === false) {
				return false;
			}
			if ($isTemporary) {
				$this->_temp_tarname = $v_filename;
			}
		} else {
			$this->_file = @fopen($v_filename, 'rb');
			if ($this->_file == 0) {
				$this->_error('Unable to open in read mode \'' . $v_filename . '\'');
				return false;
			}
		}

		return true;
	}

	/**
	 * Closes the file handle and cleans up temporary files.
	 *
	 * Handles closing for both regular file handles and compressed stream handles
	 * (gzopen, bzopen). Also removes temporary files created for remote URL handling
	 * and decompression processes.
	 *
	 * @return bool True on success.
	 */
	private function _close()
	{
		// Close the file handle (works for both regular and compressed streams)
		if ($this->_file !== 0 && $this->_file !== false) {
			if ($this->_compression === self::COMPRESSION_GZIP) {
				@gzclose($this->_file);
			} elseif ($this->_compression === self::COMPRESSION_BZIP2) {
				@bzclose($this->_file);
			} else {
				@fclose($this->_file);
			}
			$this->_file = 0;
		}

		// Reset runtime compression state
		$this->_compression = self::COMPRESSION_NONE;

		if ($this->_temp_tarname != '') {
			@unlink($this->_temp_tarname);
			$this->_temp_tarname = '';
		}

		return true;
	}

	/**
	 * Records a skipped file due to a security issue.
	 *
	 * @param string $type The type of security issue ('zip_slip', 'symlink', 'hardlink')
	 * @param string $filename The filename that was skipped
	 * @param null|string $linkname The link target (for symlink/hardlink)
	 * @param array $header Copy of the tar header entry
	 * @since 4.3.3
	 */
	private function _addSkippedFile(string $type, string $filename, ?string $linkname, array $header): void
	{
		$this->_skippedFiles[] = [
			'type' => $type,
			'filename' => $filename,
			'linkname' => $linkname,
			'header' => $header,
			'timestamp' => microtime(true),
		];
		Prado::warning(
			"{$type} detected and skipped: {$filename}" . ($linkname !== null ? " (target: {$linkname})" : ''),
			'Prado\IO\TTarFileExtractor'
		);
	}

	private function _readBlock()
	{
		$v_block = null;
		if ($this->_file !== 0 && $this->_file !== false) {
			if ($this->_compression === self::COMPRESSION_GZIP) {
				$v_block = @gzread($this->_file, 512);
			} elseif ($this->_compression === self::COMPRESSION_BZIP2) {
				$v_block = @bzread($this->_file, 512);
			} else {
				$v_block = @fread($this->_file, 512);
			}
		}
		return $v_block;
	}

	private function _jumpBlock($p_len = null)
	{
		if ($this->_file === 0 || $this->_file === false) {
			return true;
		}

		if ($p_len === null) {
			$p_len = 1;
		}

		$bytesToSkip = $p_len * 512;

		// Compressed streams don't support seeking, so read and discard
		if ($this->_compression === self::COMPRESSION_GZIP) {
			@gzread($this->_file, $bytesToSkip);
		} elseif ($this->_compression === self::COMPRESSION_BZIP2) {
			@bzread($this->_file, $bytesToSkip);
		} else {
			@fseek($this->_file, @ftell($this->_file) + $bytesToSkip);
		}
		return true;
	}

	private function _readHeader($v_binary_data, &$v_header)
	{
		if (strlen($v_binary_data) == 0) {
			$v_header['filename'] = '';
			return true;
		}

		if (strlen($v_binary_data) != 512) {
			$v_header['filename'] = '';
			$this->_error('Invalid block size : ' . strlen($v_binary_data));
			return false;
		}

		// ----- Calculate the checksum
		$v_checksum = 0;
		// ..... First part of the header
		for ($i = 0; $i < 148; $i++) {
			$v_checksum += ord(substr($v_binary_data, $i, 1));
		}
		// ..... Ignore the checksum value and replace it by ' ' (space)
		for ($i = 148; $i < 156; $i++) {
			$v_checksum += ord(' ');
		}
		// ..... Last part of the header
		for ($i = 156; $i < 512; $i++) {
			$v_checksum += ord(substr($v_binary_data, $i, 1));
		}

		$v_data = unpack(
			"a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/"
						 . "a8checksum/a1typeflag/a100link/a6magic/a2version/"
						 . "a32uname/a32gname/a8devmajor/a8devminor",
			$v_binary_data
		);

		// ----- Extract the checksum
		$v_header['checksum'] = OctDec(trim($v_data['checksum']));
		if ($v_header['checksum'] != $v_checksum) {
			$v_header['filename'] = '';

			// ----- Look for last block (empty block)
			if (($v_checksum == 256) && ($v_header['checksum'] == 0)) {
				return true;
			}

			$this->_error('Invalid checksum for file "' . $v_data['filename']
						  . '" : ' . $v_checksum . ' calculated, '
						  . $v_header['checksum'] . ' expected');
			return false;
		}

		// ----- Extract the properties
		$v_header['filename'] = trim($v_data['filename']);
		$v_header['mode'] = OctDec(trim($v_data['mode']));
		$v_header['uid'] = OctDec(trim($v_data['uid']));
		$v_header['gid'] = OctDec(trim($v_data['gid']));
		$v_header['size'] = OctDec(trim($v_data['size']));
		$v_header['mtime'] = OctDec(trim($v_data['mtime']));
		$v_header['linkname'] = trim($v_data['link']);
		if (($v_header['typeflag'] = $v_data['typeflag']) == "5") {
			$v_header['size'] = 0;
		}
		return true;
	}

	private function _readLongHeader(&$v_header)
	{
		$v_filename = '';
		$n = floor($v_header['size'] / 512);
		for ($i = 0; $i < $n; $i++) {
			$v_content = $this->_readBlock();
			$v_filename .= $v_content;
		}
		if (($v_header['size'] % 512) != 0) {
			$v_content = $this->_readBlock();
			$v_filename .= $v_content;
		}

		// ----- Read the next header
		$v_binary_data = $this->_readBlock();

		if (!$this->_readHeader($v_binary_data, $v_header)) {
			return false;
		}

		$v_header['filename'] = $v_filename;

		return true;
	}

	protected function _extractList(
		$p_path,
		&$p_list_detail,
		$p_mode,
		$p_file_list,
		$p_remove_path
	) {
		$v_result = true;
		$v_nb = 0;
		$v_extract_all = true;
		$v_listing = false;

		$p_path = $this->_translateWinPath($p_path, false);
		if ($p_path == '' || (substr($p_path, 0, 1) != '/'
		&& substr($p_path, 0, 3) != "../" && !strpos($p_path, ':'))) {
			$p_path = "./" . $p_path;
		}
		$p_remove_path = $this->_translateWinPath($p_remove_path);

		// ----- Look for path to remove format (should end by /)
		if (($p_remove_path != '') && (substr($p_remove_path, -1) != '/')) {
			$p_remove_path .= '/';
		}
		$p_remove_path_size = strlen($p_remove_path);

		switch ($p_mode) {
			case "complete":
				$v_extract_all = true;
				$v_listing = false;
				break;
			case "partial":
				$v_extract_all = false;
				$v_listing = false;
				break;
			case "list":
				$v_extract_all = false;
				$v_listing = true;
				break;
			default:
				$this->_error('Invalid extract mode (' . $p_mode . ')');
				return false;
		}

		clearstatcache();

		while (strlen($v_binary_data = $this->_readBlock()) != 0) {
			$v_extract_file = false;
			$v_extraction_stopped = 0;

			if (!$this->_readHeader($v_binary_data, $v_header)) {
				return false;
			}

			if ($v_header['filename'] == '') {
				continue;
			}

			// ----- Look for long filename
			if ($v_header['typeflag'] == 'L') {
				if (!$this->_readLongHeader($v_header)) {
					return false;
				}
			}

			if ((!$v_extract_all) && (is_array($p_file_list))) {
				// ----- By default no unzip if the file is not found
				$v_extract_file = false;

				$p_file_list_count = count($p_file_list);
				for ($i = 0; $i < $p_file_list_count; $i++) {
					// ----- Look if it is a directory
					if (substr($p_file_list[$i], -1) == '/') {
						// ----- Look if the directory is in the filename path
						if ((strlen($v_header['filename']) > strlen($p_file_list[$i]))
				&& (substr($v_header['filename'], 0, strlen($p_file_list[$i]))
					== $p_file_list[$i])) {
							$v_extract_file = true;
							break;
						}
					}

					// ----- It is a file, so compare the file names
					elseif ($p_file_list[$i] == $v_header['filename']) {
						$v_extract_file = true;
						break;
					}
				}
			} else {
				$v_extract_file = true;
			}

			// ----- Look if this file need to be extracted
			if (($v_extract_file) && (!$v_listing)) {
				if (($p_remove_path != '')
			&& (substr($v_header['filename'], 0, $p_remove_path_size)
				== $p_remove_path)) {
					$v_header['filename'] = substr(
						$v_header['filename'],
						$p_remove_path_size
					);
				}
				if (($p_path != './') && ($p_path != '/')) {
					while (substr($p_path, -1) == '/') {
						$p_path = substr($p_path, 0, strlen($p_path) - 1);
					}

					if (substr($v_header['filename'], 0, 1) == '/') {
						$v_header['filename'] = $p_path . $v_header['filename'];
					} else {
						$v_header['filename'] = $p_path . '/' . $v_header['filename'];
					}
				}

				// ----- Validate path doesn't escape destination (Zip Slip prevention)
				if (!$this->_validatePathSecurity($v_header['filename'], $p_path)) {
					$message = 'Zip Slip path traversal attempt detected: ' . $v_header['filename'];
					if ($this->_strict) {
						$this->_error($message);
						return false;
					}
					$this->_addSkippedFile('zip_slip', $v_header['filename'], null, $v_header);
					$this->_jumpBlock(ceil(($v_header['size'] / 512)));
					continue;
				}

				if (file_exists($v_header['filename'])) {
					if ((@is_dir($v_header['filename']))
			  && ($v_header['typeflag'] == '')) {
						$this->_error('File ' . $v_header['filename']
						  . ' already exists as a directory');
						return false;
					}
					if (($this->_isArchive($v_header['filename']))
			  && ($v_header['typeflag'] == "5")) {
						$this->_error('Directory ' . $v_header['filename']
						  . ' already exists as a file');
						return false;
					}
					if (!is_writable($v_header['filename'])) {
						$this->_error('File ' . $v_header['filename']
						  . ' already exists and is write protected');
						return false;
					}
					if (filemtime($v_header['filename']) > $v_header['mtime']) {
						// To be completed : An error or silent no replace ?
					}
				}

				// ----- Check the directory availability and create it if necessary
				elseif (($v_result
				 = $this->_dirCheck(($v_header['typeflag'] == "5"
									? $v_header['filename']
									: dirname($v_header['filename'])))) != 1) {
					$this->_error('Unable to create path for ' . $v_header['filename']);
					return false;
				}

				if ($v_extract_file) {
					if ($v_header['typeflag'] == "5") {
						if (!@file_exists($v_header['filename'])) {
							if (!@mkdir($v_header['filename'], Prado::getDefaultDirPermissions())) {
								$this->_error('Unable to create directory {'
								  . $v_header['filename'] . '}');
								return false;
							}
							chmod($v_header['filename'], Prado::getDefaultDirPermissions());
						}
					} elseif ($v_header['typeflag'] == "2") {
						// ----- Symlink (typeflag = "2")
						$v_linkname = trim($v_header['linkname'] ?? '');
						if (!$this->_validateSymlinkTarget($v_linkname, dirname($v_header['filename']), $p_path)) {
							$message = 'Symlink target outside extraction directory: ' . $v_linkname;
							if ($this->_strict) {
								$this->_error($message);
								return false;
							}
							$this->_addSkippedFile('symlink', $v_header['filename'], $v_linkname, $v_header);
							continue;
						}
						if (!@symlink($v_linkname, $v_header['filename'])) {
							$this->_error('Unable to create symlink: ' . $v_header['filename']);
							return false;
						}
					} elseif ($v_header['typeflag'] == "1") {
						// ----- Hard link (typeflag = "1")
						$v_linkname = trim($v_header['linkname'] ?? '');
						if (!$this->_validateHardLinkTarget($v_linkname, dirname($v_header['filename']), $p_path)) {
							$message = 'Hard link target outside extraction directory: ' . $v_linkname;
							if ($this->_strict) {
								$this->_error($message);
								return false;
							}
							$this->_addSkippedFile('hardlink', $v_header['filename'], $v_linkname, $v_header);
							continue;
						}
						$v_target_path = $p_path . '/' . $v_linkname;
						if (!@link($v_target_path, $v_header['filename'])) {
							$this->_error('Unable to create hard link: ' . $v_header['filename']);
							return false;
						}
					} else {
						if (($v_dest_file = @fopen($v_header['filename'], "wb")) == 0) {
							$this->_error('Error while opening {' . $v_header['filename']
								. '} in write binary mode');
							return false;
						} else {
							$n = floor($v_header['size'] / 512);
							for ($i = 0; $i < $n; $i++) {
								$v_content = $this->_readBlock();
								fwrite($v_dest_file, $v_content, 512);
							}
							if (($v_header['size'] % 512) != 0) {
								$v_content = $this->_readBlock();
								fwrite($v_dest_file, $v_content, ($v_header['size'] % 512));
							}

							@fclose($v_dest_file);

							// ----- Change the file mode, mtime
							@touch($v_header['filename'], $v_header['mtime']);
							// To be completed
							//chmod($v_header[filename], DecOct($v_header[mode]));
						}

						// ----- Check the file size
						clearstatcache();
						if (filesize($v_header['filename']) != $v_header['size']) {
							$this->_error('Extracted file ' . $v_header['filename']
							. ' does not have the correct file size \''
							. filesize($v_header['filename'])
							. '\' (' . $v_header['size']
							. ' expected). Archive may be corrupted.');
							return false;
						}
					}
				} else {
					$this->_jumpBlock(ceil(($v_header['size'] / 512)));
				}
			} else {
				$this->_jumpBlock(ceil(($v_header['size'] / 512)));
			}

			/* TBC : Seems to be unused ...
			if ($this->_compress)
		$v_end_of_file = @gzeof($this->_file);
			else
		$v_end_of_file = @feof($this->_file);
		*/

			if ($v_listing || $v_extract_file || $v_extraction_stopped) {
				// ----- Log extracted files
				if (($v_file_dir = dirname($v_header['filename']))
			== $v_header['filename']) {
					$v_file_dir = '';
				}
				if ((substr($v_header['filename'], 0, 1) == '/') && ($v_file_dir == '')) {
					$v_file_dir = '/';
				}

				$p_list_detail[$v_nb++] = $v_header;
			}
		}

		return true;
	}

	/**
	 * Validates that the extracted path doesn't escape the destination directory.
	 * Prevents Zip Slip attacks via path traversal sequences like ../
	 *
	 * @param string $v_header_filename The constructed file path from tar entry
	 * @param string $p_path The extraction destination path
	 * @return bool True if path is contained, false if it escapes
	 * @since 4.3.3
	 */
	private function _validatePathSecurity($v_header_filename, $p_path)
	{
		$normalizedFilename = $this->_normalizePath($v_header_filename);
		$normalizedDest = $this->_normalizePath($p_path);

		if (strpos($normalizedFilename . '/', $normalizedDest . '/') !== 0) {
			return false;
		}

		return true;
	}

	/**
	 * Validates that a symlink target is within the extraction directory.
	 *
	 * @param string $v_linkname The symlink target
	 * @param string $v_dir The directory where the symlink is being created
	 * @param string $p_path The extraction destination path
	 * @return bool True if target is safe, false if it escapes
	 * @since 4.3.3
	 */
	private function _validateSymlinkTarget($v_linkname, $v_dir, $p_path)
	{
		if ($v_linkname === '') {
			return false;
		}

		// Determine the full path the symlink would resolve to
		if (substr($v_linkname, 0, 1) === '/') {
			// Absolute path symlink
			$resolvedPath = $this->_normalizePath($v_linkname);
		} else {
			// Relative path - resolve relative to where symlink is created
			$resolvedPath = $this->_normalizePath($v_dir . '/' . $v_linkname);
		}

		$normalizedDest = $this->_normalizePath($p_path);

		// Check if resolved path is outside extraction directory
		if (strpos($resolvedPath . '/', $normalizedDest . '/') !== 0) {
			return false;
		}

		return true;
	}

	/**
	 * Validates that a hard link target is within the extraction directory.
	 *
	 * @param string $v_linkname The hard link target (file that already exists)
	 * @param string $v_dir The directory where the hard link is being created
	 * @param string $p_path The extraction destination path
	 * @return bool True if target is safe, false if it escapes
	 * @since 4.3.3
	 */
	private function _validateHardLinkTarget($v_linkname, $v_dir, $p_path)
	{
		if ($v_linkname === '') {
			return false;
		}

		if (substr($v_linkname, 0, 1) === '/') {
			$resolvedPath = $this->_normalizePath($v_linkname);
		} else {
			$resolvedPath = $this->_normalizePath($v_dir . '/' . $v_linkname);
		}

		$normalizedDest = $this->_normalizePath($p_path);

		if (strpos($resolvedPath . '/', $normalizedDest . '/') !== 0) {
			return false;
		}

		return true;
	}

	/**
	 * Normalizes a path for comparison by resolving . and .. sequences.
	 *
	 * @param string $path The path to normalize
	 * @return string The normalized path
	 */
	private function _normalizePath($path)
	{
		$parts = explode('/', $path);
		$normalized = [];

		foreach ($parts as $part) {
			if ($part === '' || $part === '.') {
				continue;
			}
			if ($part === '..') {
				array_pop($normalized);
				continue;
			}
			$normalized[] = $part;
		}

		$result = implode('/', $normalized);
		if (substr($path, 0, 1) === '/') {
			$result = '/' . $result;
		}

		return $result === '' ? '.' : $result;
	}

	/**
	 * Check if a directory exists and create it (including parent
	 * dirs) if not.
	 *
	 * @param string $p_dir directory to check
	 *
	 * @return bool true if the directory exists or was created
	 */
	protected function _dirCheck($p_dir)
	{
		if ((@is_dir($p_dir)) || ($p_dir == '')) {
			return true;
		}

		$p_parent_dir = dirname($p_dir);

		if (($p_parent_dir != $p_dir) &&
			($p_parent_dir != '') &&
			(!$this->_dirCheck($p_parent_dir))) {
			return false;
		}

		if (!@mkdir($p_dir, Prado::getDefaultDirPermissions())) {
			$this->_error("Unable to create directory '$p_dir'");
			return false;
		}
		chmod($p_dir, Prado::getDefaultDirPermissions());

		return true;
	}

	protected function _translateWinPath($p_path, $p_remove_disk_letter = true)
	{
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			// ----- Look for potential disk letter
			if (($p_remove_disk_letter)
			  && (($v_position = strpos($p_path, ':')) != false)) {
				$p_path = substr($p_path, $v_position + 1);
			}
			// ----- Change potential windows directory separator
			if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0, 1) == '\\')) {
				$p_path = strtr($p_path, '\\', '/');
			}
		}
		return $p_path;
	}
}
