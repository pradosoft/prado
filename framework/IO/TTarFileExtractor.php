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
 * TTarFileExtractor class
 *
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
 *         Prado::warning($entry['type'] . ': ' . $entry['filepath'], self::class);
 *     }
 * }
 * ```
 *
 * By default, RetainTempFile is false. When it RetainTempFile is false, the temporary file
 * for url download or .xz extraction is unlinked after being extracted. The file is
 * downloaded in {@see extract()}. When RetainTempFile is true, the temporary file remains
 * after initial extraction for multiple extractions; and the temporary file unlinked
 * at the TTarFileExtractor object's destructor.
 *
 * @author Vincent Blavet <vincent@phpconcept.net>
 * @author Brad Anderson <belisoful@icloud.com> Zip Slip Safeguards, decompression, rollbackOnFail, Manifest
 * @since 3.0
 * @todo v4.4 set RetainTempFile default to true. currently mimicking existing function as false.
 *			  set RollbackOnFailure default to true. currently mimicking existing function as false.
 */
/*
Todo:
	- set file permissions, set directory permissions after
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

	// ---------------------------------------------------------------------------
	// TAR typeflag constants (POSIX.1-1988 §3.1.1 + GNU extensions).
	// Digit-based typeflags ('0'–'7') map directly to their integer value.
	// Letter-based typeflags are stored as their ASCII/ord value so that every
	// constant is an unambiguous integer.
	// ---------------------------------------------------------------------------

	/** @var int Regular file (typeflag '0'; also covers the empty-string old-format entry) */
	public const TYPE_FILE = 0;

	/** @var int Hard link (typeflag '1') */
	public const TYPE_HARDLINK = 1;

	/** @var int Symbolic link (typeflag '2') */
	public const TYPE_SYMLINK = 2;

	/** @var int Character special device (typeflag '3') */
	public const TYPE_CHAR_SPECIAL = 3;

	/** @var int Block special device (typeflag '4') */
	public const TYPE_BLOCK_SPECIAL = 4;

	/** @var int Directory (typeflag '5') */
	public const TYPE_DIRECTORY = 5;

	/** @var int FIFO special file (typeflag '6') */
	public const TYPE_FIFO = 6;

	/** @var int Contiguous file (typeflag '7') */
	public const TYPE_CONTIGUOUS = 7;

	/**
	 * @var int GNU long-filepath extension (typeflag 'L', ASCII 76).
	 * Used internally by {@see _readLongHeader()} — not a real file entry.
	 * @since 4.3.3
	 */
	public const TYPE_GNU_LONG_NAME = 76;   // ord('L')

	/**
	 * @var int GNU long-linkpath extension (typeflag 'K', ASCII 75).
	 * @since 4.3.3
	 */
	public const TYPE_GNU_LONG_LINK = 75;   // ord('K')

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
	 * This flag determines if the temporary cache file (url downloads or .xz extractions)
	 * should be retained after an extraction.
	 *   - When true, the temporary file is unlinked at instance destruction.
	 *   - When false, the temporary file is unlinked after extraction.
	 * @var bool Caching Temp File until destruct; or unlink after extraction when false.
	 * @since 4.3.3
	 */
	private $_retainTempFile = false;

	/**
	 * @var ?string Local Tar path of a remote Tar (http://, https://, or ftp://)
	 */
	private $_temp_tarpath;

	/**
	 * @var bool Whether to fail on security issues (zip slip, symlink/hardlink attacks).
	 *            When true (default), extraction fails on any security issue.
	 *            When false, security issues are logged but extraction continues.
	 * @since 4.3.3
	 */
	private $_strict = true;

	/**
	 * @var array List of skipped files due to security issues when Strict is false.
	 *            Each entry contains: type, filepath, linkpath (if symlink/hardlink),
	 *            header copy, timestamp
	 * @since 4.3.3
	 */
	//private array $_skippedFiles = [];

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
	 * @var null|array<string,array> Map of relative tar paths to their entry metadata.
	 * Keys are relative paths; directory keys always end with {@see DIRECTORY_SEPARATOR}.
	 * Null means the map has not yet been populated (either by extraction or by scan).
	 * @since 4.3.3
	 */
	private ?array $_tarManifest = null;

	/**
	 *   /\
	 *   ||
	 * @since 4.3.3
	 */
	private ?array $_tarExtractManifest = null;

	/**
	 * @var bool Whether a failed extraction should unwind (remove) all entries
	 * that were successfully written before the failure occurred.
	 * Default false — matches pre-existing behaviour of leaving a partial extraction
	 * in place. Set to true to get a clean destination directory on failure.
	 * @since 4.3.3
	 */
	private bool $_rollbackOnFailure = false;

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
		$this->_completeTarFile();
	}

	/**
	 * Extracts the archive to the specified path.
	 *
	 * @param string $p_destPath The path where to extract the archive. If empty, extracts to current directory.
	 * @return bool True on success, false on error.
	 */
	public function extract($p_destPath = '')
	{
		return $this->extractModify($p_destPath);
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
	 * @return static $this For method chaining.
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
		return count($this->getSkippedFiles()) > 0;
	}

	/**
	 * Returns the list of files skipped due to security issues. It filters
	 * {@see getExtractManifest()} for a `reason` to not extract the files.
	 * @return array This is a filtered array from {@see getExtractManifest}
	 * @see getExtractManifest
	 * @since 4.3.3
	 */
	public function getSkippedFiles(): array
	{
		return array_filter($this->getExtractManifest() ?? [], function ($entry) {
			return isset($entry['reason']);
		});
	}

	/**
	 * Clears all skipped files records from the extraction manifest.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function clearSkippedFiles(): static
	{
		if ($this->_tarExtractManifest !== null) {
			$this->_tarExtractManifest = array_filter($this->_tarExtractManifest, function ($entry) {
				return !isset($entry['reason']);
			});
		}
		return $this;
	}

	/**
	 * Returns the 'http' and 'https' timeout time in seconds for fetching URLs.
	 * @return float Timeout time for fetching URLs, default 6.0 seconds.
	 * @since 4.3.3
	 */
	public function getUrlTimeout(): float
	{
		return $this->_urlTimeout;
	}

	/**
	 * Sets the 'http' and 'https' timeout time in seconds for fetching URLs.
	 * @param float $value The timeout time for fetching URLs.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setUrlTimeout(float $value): static
	{
		$this->_urlTimeout = $value;
		return $this;
	}

	/**
	 * Whether or not to retain the temporary tar file after extraction until the
	 * object is destructed.  When this is false, then the temporary file will be
	 * unlinked after extraction, otherwise the temporary tar file is retained until
	 * the object is destructed.
	 * @return bool Retain the temp tar file after extraction.
	 * @since 4.3.3
	 */
	public function getRetainTempFile(): bool
	{
		return $this->_retainTempFile;
	}

	/**
	 * Whether or not to retain the temporary tar file after extraction until the
	 * object is destructed.  When this is false, then the temporary file will be
	 * unlinked after extraction, otherwise the temporary tar file is retained until
	 * the object is destructed.
	 * @param bool $value The new value to retain the temp tar file after extraction.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setRetainTempFile(bool $value): static
	{
		$this->_retainTempFile = $value;
		return $this;
	}

	/**
	 * The temporary tar file path used by url download and .xz decompression
	 * @return ?string The file path of the temporary tar file path.
	 * @since 4.3.3
	 */
	public function getTempPath(): ?string
	{
		return $this->_temp_tarpath;
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

	// ---------------------------------------------------------------------------
	// Tar path map API
	// ---------------------------------------------------------------------------

	/**
	 * Returns whether extraction failures should unwind (remove) already-extracted entries.
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	public function getRollbackOnFailure(): bool
	{
		return $this->_rollbackOnFailure;
	}

	/**
	 * Sets whether a failed extraction should unwind (remove) the entries it already wrote.
	 * Default is false — pre-existing behaviour leaves any partial extraction in place.
	 *
	 * @param bool $value
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setRollbackOnFailure(bool $value): static
	{
		$this->_rollbackOnFailure = $value;
		return $this;
	}

	/**
	 * Returns an ordered array of the relative entry paths contained in the archive.
	 * Directory paths appear before file paths and always end with {@see DIRECTORY_SEPARATOR}.
	 * If the archive has not been extracted yet, it is scanned without extracting.
	 *
	 * @return string[]
	 * @since 4.3.3
	 */
	public function getManifestPaths(): array
	{
		return array_keys($this->getManifest());
	}

	/**
	 * Returns an ordered array of the relative entry paths contained in the archive.
	 * Directory paths appear before file paths and always end with {@see DIRECTORY_SEPARATOR}.
	 * If the archive has not been extracted yet, it is scanned without extracting.
	 *
	 * @return bool if the
	 * @since 4.3.3
	 */
	protected function hasManifest(): bool
	{
		return $this->_tarManifest !== null;
	}

	/**
	 * Returns the full metadata map for every entry in the archive.
	 * Keys are relative paths (directories end with {@see DIRECTORY_SEPARATOR}).
	 * Directory entries always precede file entries within the map.
	 *
	 * Each value array contains:
	 *  - path         string  Map key (normalised relative path)
	 *  - name         string  File basename
	 *  - device       bool    Is a TYPE_CHAR_SPECIAL or TYPE_BLOCK_SPECIAL
	 *  - filepath     string  Raw relative path in the archive
	 *  - timestamp    float   metrics
	 *  - size         int     Stored file size in bytes
	 *  - mtime        int     Modification timestamp (Unix epoch)
	 *  - mode         int     UNIX permission bits
	 *  - uid          int     Numeric user ID
	 *  - gid          int     Numeric group ID
	 *  - uname        string  Symbolic user name
	 *  - gname        string  Symbolic group name
	 *  - linkpath     string  Symlink / hard-link target (empty for regular entries)
	 *  - typeflag     int     TYPE_* constant value (TYPE_FILE, TYPE_DIRECTORY, etc.)
	 *  - checksum     int     Stored header checksum
	 *  - safe         bool    True if the path contains no break-out path sequences
	 *  - device       bool    True if the entry is a character or block special device file
	 *  - extracted    bool    True if the entry was written to disk
	 *  - reason       bool    Only present when there is a reason to not extract. like Zip Slip attacks.
	 *
	 * On extraction:
	 *  - extractedPath string Absolute path where the entry was written (empty if not extracted)
	 *
	 * If the map has not been populated by a prior extraction, the archive is scanned
	 * without extracting any files.
	 *
	 * @return array<string,array>
	 * @since 4.3.3
	 */
	public function getManifest(): array
	{
		if ($this->_tarManifest !== null) {
			return $this->_tarManifest;
		}

		$v_result = true;
		$extractionManifest = [];

if ($v_result = $this->_openRead()) {
			$v_exception = null;
			try {
				$v_result = $this->_extractList(
					null,
					$extractionManifest, 
					"list",
					null,
					null
				);
			} catch (\Exception $e) {
				$v_result = false;
				$v_exception = $e;
			}
			$this->_close();
		
			// Sort map: directories before files, both groups alphabetical.
			$this->_sortManifest($extractionManifest);
			
			// DEBUG
			file_put_contents('/tmp/prado_tar_debug.log', "After _extractList: " . count($extractionManifest) . " entries\nKeys: " . json_encode(array_keys($extractionManifest)) . "\n", FILE_APPEND);
		
			// Re-throw after cleanup so callers still see the exception.
			if ($v_exception !== null) {
				throw $v_exception;
			}

			if ($this->_tarManifest === null) {
				$this->setManifest($extractionManifest);
			}
		}
		return $this->_tarManifest ?? [];
	}

	/**
	 * These are the files that were processed by tar extraction.  If there was
	 * an error and {@see getStrict} is true, only the entries prior to the error
	 * will be in this array.
	 * For a full Manifest of the tar, use {@see getManifest()}.
	 * The format of each entry is the same as for {@see getManifest()}
	 * @return ?array The files processed by the {@see extract} to completion or error.
	 * @since 4.3.3
	 */
	public function getExtractManifest(): ?array
	{
		return $this->_tarExtractManifest;
	}

	/**
	 * Sets the tar manifest. it removes any extraction fields.
	 * @since 4.3.3
	 * @param mixed $manifest
	 */
	protected function setManifest($manifest)
	{
		$this->_tarManifest = [];
		if (!is_array($manifest)) {
			return;
		}
		foreach ($manifest as $path => $entry) {
			// Remove extraction-specific fields to create clean base manifest
			$cleanEntry = $entry;
			unset($cleanEntry['extracted']);
			unset($cleanEntry['extractedPath']);
			unset($cleanEntry['reason']);
			$this->_tarManifest[$path] = $cleanEntry;
		}
		// DEBUG: log what happened
		file_put_contents('/tmp/prado_tar_debug.log', "setManifest called with " . count($manifest) . " entries, keys: " . json_encode(array_keys($manifest)) . "\n", FILE_APPEND);
	}

	/**
	 * Returns the full metadata array for a single archive entry, or null if not found.
	 * See {@see getManifest()} for the structure of the returned array.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|array
	 * @since 4.3.3
	 */
	public function getManifestInfo(string $path): ?array
	{
		$key = $this->_findManifestKey($path);
		return $key !== null ? $this->_tarManifest[$key] : null;
	}

	/**
	 * Returns the extraction metadata for a single archive entry.
	 * Unlike getManifestInfo(), this includes 'extracted', 'extractedPath', and 'reason' fields.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|array
	 * @since 4.3.3
	 */
	public function getExtractManifestInfo(string $path): ?array
	{
		$key = $this->_findExtractManifestKey($path);
		if ($key === null) {
			return null;
		}
		return $this->_tarExtractManifest[$key] ?? null;
	}

	/**
	 * Returns the full metadata array for a single archive entry, or null if not found.
	 * See {@see getManifest()} for the structure of the returned array.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @param string $key
	 * @return mixed
	 * @since 4.3.3
	 */
	public function getManifestValue(string $path, string $key): mixed
	{
		$path = $this->_findManifestKey($path);
		return $path !== null ? $this->_tarManifest[$path][$key] ?? null : null;
	}

	/**
	 * Returns the entry type for the given archive path.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|string One of 'file', 'directory', 'symlink', 'hardlink',
	 *                     'char_device', 'block_device', 'fifo', or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestType(string $path): ?string
	{
		return $this->getManifestValue($path, 'type');
	}

	/**
	 * Returns the entry type flag for the given archive path.
	 * This will be a TYPE_* constant value (TYPE_FILE, TYPE_DIRECTORY, etc).
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|int One of TYPE_FILE, TYPE_DIRECTORY, TYPE_HARDLINK, TYPE_SYMLINK, etc
	 * @since 4.3.3
	 */
	public function getManifestTypeFlag(string $path): ?int
	{
		return $this->getManifestValue($path, 'typeflag');
	}

	/**
	 * Returns the stored size (in bytes) of the given archive entry.
	 * Note: tar archives do not store a separate creation time; use {@see getManifestMtime()}.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|int Size in bytes, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestSize(string $path): ?int
	{
		return $this->getManifestValue($path, 'size');
	}

	/**
	 * Returns the modification timestamp of the given archive entry (Unix epoch seconds).
	 * TAR archives store only mtime; there is no separate creation-time field.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|int Unix timestamp, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestMtime(string $path): ?int
	{
		return $this->getManifestValue($path, 'mtime');
	}

	/**
	 * Returns the UNIX permission bits (mode) of the given archive entry.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|int Mode, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestMode(string $path): ?int
	{
		return $this->getManifestValue($path, 'mode');
	}

	/**
	 * Returns the numeric user ID of the given archive entry.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|int UID, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestUid(string $path): ?int
	{
		return $this->getManifestValue($path, 'uid');
	}

	/**
	 * Returns the numeric group ID of the given archive entry.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|int GID, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestGid(string $path): ?int
	{
		return $this->getManifestValue($path, 'gid');
	}

	/**
	 * Returns the symbolic user name of the given archive entry.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|string User name, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestUname(string $path): ?string
	{
		return $this->getManifestValue($path, 'uname');
	}

	/**
	 * Returns the symbolic group name of the given archive entry.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|string Group name, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestGname(string $path): ?string
	{
		return $this->getManifestValue($path, 'gname');
	}

	/**
	 * Returns the link target of the given archive entry (symlinks and hard links only).
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|string Link target, empty string for non-link entries, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestLinkPath(string $path): ?string
	{
		return $this->getManifestValue($path, 'linkpath');
	}

	/**
	 * Returns whether the given archive entry path is safe from path-traversal attacks.
	 * A path is considered safe when it contains no '..' components, does not start
	 * with '/' or a Windows drive letter, and cannot escape the extraction root.
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|bool True if safe, false if a traversal sequence was found, null if not found.
	 * @since 4.3.3
	 */
	public function getManifestIsSafe(string $path): ?bool
	{
		return $this->getManifestValue($path, 'filesafe');
	}

	/**
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|bool True if safe, false if a traversal sequence was found, null if not found.
	 * @since 4.3.3
	 */
	public function getManifestUnsafeReason(string $path): ?bool
	{
		return $this->getManifestValue($path, 'reason');
	}

	/**
	 * Returns whether the given archive entry is a character or block special device file
	 * ({@see TYPE_CHAR_SPECIAL} or {@see TYPE_BLOCK_SPECIAL}).
	 * A device file may still be at a path-safe location ({@see getManifestIsSafe()} reports
	 * the path safety independently).
	 *
	 * @param string $path Relative path as it appears in the archive.
	 * @return null|bool True if the entry is a device file, false otherwise, null if not found.
	 * @since 4.3.3
	 */
	public function getManifestIsDevice(string $path): ?bool
	{
		return $this->getManifestValue($path, 'device');
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
	 * @param string $tarname The tar archive filepath or URL
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
	 * @param string $filepath The file to open
	 * @param int $compression The compression type (COMPRESSION_*)
	 * @param bool $isTemporary
	 * @return false|resource|string The stream handle; string error or false on failure
	 * @since 4.3.3
	 */
	private function _openFile(string &$filepath, int $compression, bool $isTemporary)
	{
		$handle = false;
		switch ($compression) {
			case self::COMPRESSION_NONE:
				$handle = @fopen($filepath, 'rb');
				if ($handle === false) {
					return 'Unable to open in read binary mode \'' . $filepath . '\'';
				}
				break;
			case self::COMPRESSION_GZIP:
				if (!function_exists('gzopen')) {
					return 'zlib extension is required for gzip compression';
				}
				$handle = @gzopen($filepath, 'rb');
				if ($handle === false) {
					return 'Unable to open gzip in read binary mode \'' . $filepath . '\'';
				}
				break;
			case self::COMPRESSION_BZIP2:
				if (!function_exists('bzopen')) {
					return 'bzip2 extension is required for bzip2 compression';
				}
				$handle = @bzopen($filepath, 'r');
				if ($handle === false) {
					return 'Unable to open bzip2 in read binary mode \'' . $filepath . '\'';
				}
				break;
			case self::COMPRESSION_LZMA:
				// Check for xz command availability
				$xzDec = trim(shell_exec('which xzdec') ?: '');
				$xzCmd = trim(shell_exec('which xz') ?: '');
				if (!$xzDec && !$xzCmd) {
					return 'xz command is required for LZMA compression';
				}
				// For LZMA/XZ, decompress to a temp file first
				$tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('lzma') . '.tar';

				// xzdec writes to stdout with no flags required (filepath only).
				// xz uses -dc (decompress, stdout). Both redirect to the temp file.
				$command = $xzDec
					? escapeshellarg($xzDec) . ' ' . escapeshellarg($filepath) . ' > ' . escapeshellarg($tempFile)
					: escapeshellarg($xzCmd) . ' -dc ' . escapeshellarg($filepath) . ' > ' . escapeshellarg($tempFile);

				$output = [];
				$returnVar = -1;
				exec($command, $output, $returnVar);

				if (!file_exists($tempFile)) {
					if ($returnVar !== 0) {
						return 'Unable to decompress LZMA archive: decompression command failed, return code \'' . $returnVar . '\'';
					}
					return 'Unable to decompress LZMA archive: temp file not created \'' . $tempFile . '\'';
				}

				if ($isTemporary) {
					// Delete the original downloaded/compressed file now that it has been
					// decompressed. Update $filepath (by reference) to point to the
					// decompressed tar so _openRead() can update _temp_tarpath accordingly.
					@unlink($filepath);
					$filepath = $tempFile;
				}

				$handle = @fopen($tempFile, 'rb');
				if ($handle === false) {
					return 'Unable to open decompressed LZMA in read binary mode \'' . $filepath . '\'';
				}
				break;
		}
		$this->_compression = $compression;
		return $handle;
	}

	/**
	 * This method extract all the content of the archive in the directory
	 * indicated by $p_destPath. When relevant the memorized path of the
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
	 * is returned.
	 *
	 * @param string $p_destPath        The path of the directory where the
	 *                                files/dir need to by extracted.
	 * @param ?string $p_remove_path   Part of the memorized path that can be
	 *                                removed if present at the beginning of
	 *                                the file/dir path.
	 * @return bool                   true on success, false on error.
	 * @access public
	 */
	/**
	 * Extracts the archive with optional path removal.
	 *
	 * @param string $p_destPath	 The path where to extract the archive.
	 * @param string $p_remove_path  Path to remove from extracted file paths.
	 * @return bool True on success, false on error.
	 */
	protected function extractModify($p_destPath, $p_remove_path = null)
	{
		$v_result = true;
		$extractionManifest = [];

		if ($v_result = $this->_openRead()) {
			$v_exception = null;
			try {
				$v_result = $this->_extractList(
					$p_destPath,
					$extractionManifest,
					"complete",
					null,
					$p_remove_path
				);
			} catch (\Exception $e) {
				$v_result = false;
				$v_exception = $e;
			}

			// Sort map: directories before files, both groups alphabetical.
			$this->_sortManifest($extractionManifest);
			$this->_tarExtractManifest = $extractionManifest;

			if (!$v_result && $this->getRollbackOnFailure()) {
				$this->_rollbackExtraction($extractionManifest);
			}

			$this->_close();

			// Re-throw after cleanup so callers still see the exception.
			if ($v_exception !== null) {
				throw $v_exception;
			}

			if ($this->_tarManifest === null) {
				$this->setManifest($extractionManifest);
			}
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
	 * @param null|string $p_filepath The filepath to check. If null, uses the internal tar name.
	 * @return bool True if the file exists and is a regular file, false otherwise.
	 */
	private function _isArchive($p_filepath = null)
	{
		if ($p_filepath == null) {
			$p_filepath = $this->_tarname;
		}
		clearstatcache();
		return @is_file($p_filepath);
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
			if ($this->_temp_tarpath === null) {
				$timeout = $this->getUrlTimeout();
				$this->_temp_tarpath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('tar') . '.tmp';
				// Use a short timeout so an unreachable URL fails fast instead of stalling
				$ctx = stream_context_create([
					'http' => ['timeout' => $timeout],
					'https' => ['timeout' => $timeout],
				]);
				if (!$v_file_from = @fopen($this->_tarname, 'rb', false, $ctx)) {
					$this->_error('Unable to open in read mode \''
							  . $this->_tarname . '\'');
					$this->_temp_tarpath = null;
					return false;
				}
				if (!$v_file_to = @fopen($this->_temp_tarpath, 'wb')) {
					$this->_error('Unable to open in write mode \''
							  . $this->_temp_tarpath . '\'');
					$this->_temp_tarpath = null;
					return false;
				}
				while ($v_data = @fread($v_file_from, 1024)) {
					@fwrite($v_file_to, $v_data);
				}
				@fclose($v_file_from);
				@fclose($v_file_to);
			}

			// ----- File to open if the local copy
			$v_filepath = $this->_temp_tarpath;
			$isTemporary = true;
		} else {
			// ----- File to open if the normal Tar file
			$v_filepath = $this->_tarname;
		}

		// Detect compression type
		$this->_detectedCompression = $this->_detectCompression($v_filepath);

		$fileHandle = $this->_openFile($v_filepath, $this->_detectedCompression, $isTemporary);
		if ($fileHandle === false) {
			return false;
		} elseif (is_string($fileHandle)) {
			$this->_error($fileHandle);
			return false;
		}
		$this->_file = $fileHandle;
		if ($isTemporary) {
			$this->_temp_tarpath = $v_filepath;
		}

		return true;
	}

	/**
	 * Closes the file handle.
	 *
	 * Handles closing for both regular file handles and compressed stream handles
	 * (gzopen, bzopen). This keeps the temporary url download (or decompressed .xz)
	 * file.  The downloaded file or .xz decompression is kept in the cache until
	 * this object is destroyed.
	 *
	 * @param bool $forceClearTemp
	 * @return bool True on success, false on failure.
	 */
	private function _close(bool $forceClearTemp = false)
	{
		$result = false;

		// Close the file handle
		if ($this->_file !== 0 && $this->_file !== false) {
			if ($this->_compression === self::COMPRESSION_GZIP) {
				$result = @gzclose($this->_file);
			} elseif ($this->_compression === self::COMPRESSION_BZIP2) {
				$result = @bzclose($this->_file);
			} else {
				$result = @fclose($this->_file);
			}
			$this->_file = 0;
		}

		if ($forceClearTemp || !$this->getRetainTempFile()) {
			$this->clearTempFile();
		}

		// Reset runtime compression state
		$this->_compression = self::COMPRESSION_NONE;

		return $result;
	}

	/**
	 * The temporary tar file path used by url download and .xz decompression
	 * @return ?bool If there was an error.
	 */
	public function clearTempFile(): ?bool
	{
		if ($this->_temp_tarpath !== null) {
			if (!@unlink($this->_temp_tarpath)) {
				return true;	// an error unlinking
			}
			$this->_temp_tarpath = null;
			return false;
		}
		return null;
	}

	/**
	 * Closes the file handle, cleans up temporary files, and blanks the tarname.
	 * Typically called in destruct.
	 * @return bool True on success, false on failure.
	 */
	private function _completeTarFile()
	{
		$result = $this->_close(true);
		$this->_tarname = '';
		return $result;
	}

	/**
	 * Reads a 512-byte block from the archive file.
	 *
	 * @return null|string The 512-byte block data, or null if no data read.
	 */
	private function _readBlock()
	{
		$v_block = false;
		if ($this->_file !== 0 && $this->_file !== false) {
			if ($this->_compression === self::COMPRESSION_GZIP) {
				$v_block = @gzread($this->_file, 512);
			} elseif ($this->_compression === self::COMPRESSION_BZIP2) {
				$v_block = @bzread($this->_file, 512);
			} else {
				$v_block = @fread($this->_file, 512);
			}
			// Return null when at end of file, not empty string
			if ($v_block === '' || $v_block === null) {
				return null;
			}
		}
		return $v_block;
	}

	/**
	 * Skips the specified number of 512-byte blocks by seeking or reading past them.
	 *
	 * @param null|int $p_len Number of blocks to skip. Defaults to 1 if null.
	 * @return bool True on success.
	 */
	private function _jumpBlock($p_len = null)
	{
		if ($this->_file === 0 || $this->_file === false) {
			return true;
		}

		if ($p_len === null) {
			$p_len = 1;
		}

		$bytesToSkip = $p_len * 512;

		// Skip reading when there are no bytes to skip
		if ($bytesToSkip <= 0) {
			return true;
		}

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

	/**
	 * Reads and parses a tar header block into an associative array.
	 *
	 * @param string $v_binary_data The raw 512-byte header block.
	 * @param array &$v_header Parsed header data (passed by reference).
	 * @return bool True on success, false on error or end-of-archive.
	 */
	private function _readHeader($v_binary_data, &$v_header)
	{
		if (strlen($v_binary_data) == 0) {
			$v_header['filepath'] = '';
			return true;
		}

		if (strlen($v_binary_data) != 512) {
			$v_header['filepath'] = '';
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
			"a100filepath/a8mode/a8uid/a8gid/a12size/a12mtime/"
						 . "a8checksum/a1typeflag/a100linkpath/a6magic/a2version/"
						 . "a32uname/a32gname/a8devmajor/a8devminor",
			$v_binary_data
		);

		// ----- Extract the checksum
		$v_header['checksum'] = OctDec(trim($v_data['checksum']));
		if ($v_header['checksum'] != $v_checksum) {
			$v_header['filepath'] = '';

			// ----- Look for last block (empty block)
			if (($v_checksum == 256) && ($v_header['checksum'] == 0)) {
				return true;
			}

			$this->_error('Invalid checksum for file "' . $v_data['filepath']
						  . '" : ' . $v_checksum . ' calculated, '
						  . $v_header['checksum'] . ' expected');
			return false;
		}

		// ----- Extract the properties
		$rawTypeflag = str_replace("\x00", '', $v_data['typeflag']);
		if ($rawTypeflag === '') {
			$typeFlag = self::TYPE_FILE;
		} elseif ($rawTypeflag >= '0' && $rawTypeflag <= '9') {
			$typeFlag = (int) $rawTypeflag;
		} else {
			$typeFlag = ord($rawTypeflag[0]);
		}
		$filepath = trim($v_data['filepath']);
		$linkpath = trim($v_data['linkpath']);
		$filenorm = $this->_normalizePath($filepath);
		$v_header['tarpath'] = $filepath;	// retain the original
		$v_header['filepath'] = $filepath;	// working tar path
		$v_header['filepath_norm'] = $filenorm;
		$v_header['filesafe'] = $this->_isRelativePathSafe($filenorm);
		$v_header['filename'] = basename($filepath);
		$v_header['mode'] = OctDec(trim($v_data['mode']));
		$v_header['uid'] = OctDec(trim($v_data['uid']));
		$v_header['gid'] = OctDec(trim($v_data['gid']));
		$v_header['size'] = OctDec(trim($v_data['size']));
		$v_header['mtime'] = OctDec(trim($v_data['mtime']));
		$v_header['tarlink'] = $linkpath;	// retain the original
		$v_header['linkpath'] = $linkpath;	// working tar link path
		$v_header['uname'] = trim($v_data['uname']);
		$v_header['gname'] = trim($v_data['gname']);
		$v_header['device'] = in_array($typeFlag, [self::TYPE_CHAR_SPECIAL, self::TYPE_BLOCK_SPECIAL]);

		// Convert the raw 1-char typeflag string to an integer constant.
		// The `a1` unpack format may return "\x00" for a null/empty typeflag field.
		$rawTypeflag = str_replace("\x00", '', $v_data['typeflag']);
		if ($rawTypeflag === '') {
			$v_header['typeflag'] = self::TYPE_FILE;   // old-format regular file
		} elseif ($rawTypeflag >= '0' && $rawTypeflag <= '9') {
			$v_header['typeflag'] = (int) $rawTypeflag; // POSIX digit typeflag
		} else {
			$v_header['typeflag'] = ord($rawTypeflag[0]); // GNU letter typeflag (L, K, …)
		}
		switch ($v_header['typeflag']) {
			case self::TYPE_DIRECTORY:
				$v_header['size'] = 0;
				break;
			case self::TYPE_SYMLINK:
			case self::TYPE_HARDLINK:
				$linknorm = $this->_normalizePath($linkpath);
				$v_header['linkpath_norm'] = $linknorm;
				$v_header['linksafe'] = $this->_isRelativePathSafe($linknorm);
				break;
		}

		// Add derived fields for clean archive entry
		$v_header['type'] = match ($typeFlag) {
			self::TYPE_DIRECTORY => 'directory',
			self::TYPE_SYMLINK => 'symlink',
			self::TYPE_HARDLINK => 'hardlink',
			self::TYPE_CHAR_SPECIAL => 'char_device',
			self::TYPE_BLOCK_SPECIAL => 'block_device',
			self::TYPE_FIFO => 'fifo',
			self::TYPE_CONTIGUOUS => 'file',
			default => 'file',
		};

		return true;
	}

	/**
	 * Reads a GNU long-name or long-link data block and sets the specified header field.
	 *
	 * @param array  $v_header Parsed header; updated in place.
	 * @param string $field    Header key to set: 'filepath' (TYPE_GNU_LONG_NAME)
	 *                         or 'linkpath' (TYPE_GNU_LONG_LINK).
	 * @return bool True on success, false on read error.
	 */
	private function _readLongHeader(&$v_header, string $field = 'filepath')
	{
		$v_data = '';
		$n = floor($v_header['size'] / 512);
		for ($i = 0; $i < $n; $i++) {
			$v_content = $this->_readBlock();
			$v_data .= $v_content;
		}
		if (($v_header['size'] % 512) != 0) {
			$v_content = $this->_readBlock();
			$v_data .= $v_content;
		}

		// ----- Read the next header
		$v_binary_data = $this->_readBlock();

		if (!$this->_readHeader($v_binary_data, $v_header)) {
			return false;
		}

		// GNU long blocks use a null terminator; strip it and any null padding.
		$v_header[$field] = rtrim($v_data, "\x00");

		return true;
	}

	// ---------------------------------------------------------------------------
	// Tar manifest helpers
	// ---------------------------------------------------------------------------


	/**
	 * Locates the canonical map key for the given path query, handling optional
	 * trailing separators and triggering a lazy scan when needed.
	 *
	 * @param string $path Relative path to look up.
	 * @return null|string The matching map key, or null if not found.
	 * @since 4.3.3
	 */
	private function _findManifestKey(string $path): ?string
	{
		// Ensure the map is populated (lazy scan if necessary).
		if ($this->_tarManifest === null) {
			$this->getManifest();
		}
		if ($this->_tarManifest === null) {
			return null;
		}
		// Direct lookup (works for file keys and already-normalised directory keys).
		if (isset($this->_tarManifest[$path])) {
			return $path;
		}
		// Try as a directory (with trailing DIRECTORY_SEPARATOR).
		$withSep = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
		if (isset($this->_tarManifest[$withSep])) {
			return $withSep;
		}
		// Try without any trailing separator.
		$withoutSep = rtrim($path, '/\\');
		if ($withoutSep !== $path && isset($this->_tarManifest[$withoutSep])) {
			return $withoutSep;
		}
		return null;
	}

	/**
	 * Locates the key in extraction manifest.
	 * @param string $path
	 */
	private function _findExtractManifestKey(string $path): ?string
	{
		if ($this->_tarExtractManifest === null) {
			return null;
		}
		if (isset($this->_tarExtractManifest[$path])) {
			return $path;
		}
		$withSep = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
		if (isset($this->_tarExtractManifest[$withSep])) {
			return $withSep;
		}
		$withoutSep = rtrim($path, '/\\');
		if ($withoutSep !== $path && isset($this->_tarExtractManifest[$withoutSep])) {
			return $withoutSep;
		}
		return null;
	}

	/**
	 * Records or updates a single entry in the tar path info map.
	 *
	 * @param array  $header       	The parsed tar header.
	 * @param array $v_header
	 * @since 4.3.3
	 */
	/*private function _recordTarManifestEntry(
		array $header
	): void {
		$this->_recordInfoEntry($header, null, false);
	}
	*/
	/**
	 * Records or updates a single entry in the tar path info map.
	 *
	 * @param array  $header       	  The parsed tar header.
	 * @param ?string $extractedPath  Absolute path where the entry was written (empty if not written).
	 * @param ?string $reason   	  Whether the entry was successfully written to disk.
	 * @since 4.3.3
	 */
	/*private function _recordExtractEntry(
		array $header,
		?string $extractedPath,
		?string $reason = null
	): void {
		$this->_recordInfoEntry($header, $extractedPath, $reason);
	}*/

	/**
	 * Records or updates a single entry in the tar path info map.
	 *
	 * @param array  $header       	  The parsed tar header.
	 * @param ?string $extractedPath  Absolute path where the entry was written (empty if not written).
	 * @param ?string $reason   	  Whether the entry was successfully written to disk.
	 * @since 4.3.3
	 */
	/* private function _recordInfoEntry(
		array $header,
		?string $extractedPath,
		false|?string $reason = null,
	): void {
		$isExtracting = $reason !== false;
		if ($isExtracting && $this->_tarExtractManifest === null) {
			$this->_tarExtractManifest = [];
		} elseif (!$isExtracting && $this->_tarManifest === null) {
			$this->_tarManifest = [];
		}
		$type = match ($header['typeflag'] ?? self::TYPE_FILE) {
			self::TYPE_DIRECTORY     => 'directory',
			self::TYPE_SYMLINK       => 'symlink',
			self::TYPE_HARDLINK      => 'hardlink',
			self::TYPE_CHAR_SPECIAL  => 'char_device',
			self::TYPE_BLOCK_SPECIAL => 'block_device',
			self::TYPE_FIFO          => 'fifo',
			default                  => 'file',
		};

		$mapKey = ($type === 'directory')
			? rtrim($relativePath, '/\\') . DIRECTORY_SEPARATOR
			: rtrim($relativePath, '/\\');
		$normalizedPath = $this->_normalizePath($relativePath, false);
		$normalizedPath = $this->_normalizePath($relativePath, false);
		$entry = [
			'path'          => $mapKey,
			'name'          => basename(rtrim($relativePath, '/\\')),
			'type'          => $type,
			'filepath'      => $header['filepath'],
			'size'          => (int)($header['size'] ?? 0),
			'mtime'         => (int)($header['mtime'] ?? 0),
			'mode'          => (int)($header['mode'] ?? 0),
			'uid'           => (int)($header['uid'] ?? 0),
			'gid'           => (int)($header['gid'] ?? 0),
			'uname'         => (string)($header['uname'] ?? ''),
			'gname'         => (string)($header['gname'] ?? ''),
			'linkpath'      => (string)($header['linkpath'] ?? ''),
			'typeflag'      => (int)($header['typeflag'] ?? self::TYPE_FILE),
			'checksum'      => (int)($header['checksum'] ?? 0),
			'filesafe'          => $safe,
			'device'        => in_array(
				$header['typeflag'] ?? self::TYPE_FILE,
				[self::TYPE_CHAR_SPECIAL, self::TYPE_BLOCK_SPECIAL],
				true
			),
			'timestamp'		=> microtime(true),
		];
		if (is_string($reason)) {
			$entry['reason'] = $reason;
		}
		if ($isExtracting) {
			if ($extractedPath !== null) {
				$entry['extracted'] = $extractedPath !== null,
				$entry['extractedPath'] = $extractedPath;
			}
			$this->_tarExtractManifest[$mapKey] = $entry;
			if (!$safe) {
				//Prado::warning(
				//	"{$type} detected and skipped: {$filepath}" . ($linkpath !== null ? " (target: {$linkpath})" : ''),
				//	'Prado\IO\TTarFileExtractor'
				//);
			}
		} else {
			$this->_tarManifest[$mapKey] = $entry;
		}
	}
	*/

	/**
	 * Sorts $_tarManifest so that directory entries precede file entries.
	 * Within each group the keys are sorted alphabetically.
	 * @return bool was there an error. false if ok.
	 * @since 4.3.3
	 */
	private function processLongHeader(array &$v_header): bool
	{
		$typeFlag = $v_header['typeflag'];

		// Handle GNU long-filepath and long-linkpath extensions.
		if ($typeFlag === self::TYPE_GNU_LONG_NAME) {
			if (!$this->_readLongHeader($v_header, 'filepath')) {
				return true;
			}
		} elseif ($typeFlag === self::TYPE_GNU_LONG_LINK) {
			if (!$this->_readLongHeader($v_header, 'linkpath')) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sorts $_tarManifest so that directory entries precede file entries.
	 * Within each group the keys are sorted alphabetically.
	 *
	 * @since 4.3.3
	 * @param array $manifest
	 */
	private function _sortManifest(array &$manifest): void
	{
		if (empty($manifest)) {
			$manifest = [];
			return;
		}
		uksort($manifest, static function (string $a, string $b): int {
			$aDir = str_ends_with($a, DIRECTORY_SEPARATOR);
			$bDir = str_ends_with($b, DIRECTORY_SEPARATOR);
			if ($aDir !== $bDir) {
				return $aDir ? -1 : 1;
			}
			return strcmp($a, $b);
		});
	}

	/**
	 * Scans the archive and populates {@see getManifest()} without extracting any files.
	 * If a temporary file is created during this scan (URL download or LZMA decompression)
	 * {@see getRetainTempFile()} is automatically set to true so that a subsequent
	 * {@see extract()} call can reuse it without downloading or decompressing again.
	 *
	 * @since 4.3.3
	 * @param mixed $p_destPath
	 * @param mixed $p_manifest
	 * @param mixed $p_mode
	 * @param mixed $p_file_list
	 * @param mixed $p_remove_path_prefix
	 * @return bool True on success, false on failure.
	 */
	/*protected function _scanTarManifest(): bool
	{
		if (!$this->_openRead()) {
			return false;
		}

		// Keep any temp file (URL download or LZMA decompression) so that a
		// following extract() can reuse it without repeating the work.
		if ($this->_temp_tarpath !== null) {
			$this->_retainTempFile = true;
		}

		$this->_tarManifest = [];

		while (strlen($v_binary_data = $this->_readBlock()) != 0) {
			$v_header = [];
			if (!$this->_readHeader($v_binary_data, $v_header)) {
				$this->_close();
				return false;
			}

			if ($v_header['filepath'] == '') {
				continue;
			}

			if ($this->processLongHeader($v_header)) {
				$this->_close();
				return false;
			}
			$typeFlag = $v_header['typeflag'];

			$this->_recordTarManifestEntry($v_header);

			// Skip the data blocks — no content is extracted during a scan.
			if ($typeFlag !== self::TYPE_DIRECTORY && ($v_header['size'] ?? 0) > 0) {
				$this->_jumpBlock((int)ceil($v_header['size'] / 512));
			}
		}

		$this->_close();
		$this->_sortManifest(false);

		return true;
	} */

	/**
	 * Extracts or lists files from the archive based on the extraction mode.
	 *
	 * @param ?string $p_destPath The destination path.
	 * @param array &$p_manifest List of extracted file details (passed by reference).
	 * @param string $p_mode Extraction mode: "complete", "partial", or "list".
	 * @param array|int $p_file_list List of files to extract, or 0/null for all.
	 * @param string $p_remove_path_prefix Path prefix to remove from extracted file paths.
	 * @return bool True on success, false on error.
	 * @since 4.3.3
	 */
	protected function _extractList(
		$p_destPath,
		&$p_manifest,
		$p_mode,
		$p_file_list,
		$p_remove_path_prefix
	) {
		$recordEntryDetail = function ($fileInfo, $extractedPath, $reason) use (&$p_manifest) {

			if (!is_array($p_manifest)) {
				return;
			}

			// Use normalized path as key - clean and uniform
			$mapKey = $fileInfo['filepath_norm'] ?? $fileInfo['filepath'] ?? '';

			// Add fail-safe field to indicate entry is safe for extraction
			$fileInfo['fail-safe'] = $fileInfo['filesafe'] ?? true;

			// Add extraction-specific fields
			if ($extractedPath !== null) {
				$fileInfo['extracted'] = true;
				$fileInfo['extractedPath'] = $extractedPath;
			}

			if (is_string($reason)) {
				$fileInfo['created'] = false;
				$fileInfo['reason'] = $reason;
			} elseif (is_bool($reason)) {
				$fileInfo['created'] = $reason;
			}

			$p_manifest[$mapKey] = $fileInfo;
		}; // end function $recordEntryDetail

		$directoryModes = [];
		if ($p_destPath) {
			$p_destPath = $this->_translateWinPath($p_destPath, false);
			if ($p_destPath == '' || (str_starts_with($p_destPath, '/') && str_starts_with($p_destPath, "../") && strpos($p_destPath, ':') !== false)) {
				$p_destPath = "./" . $p_destPath;
			}
			if ($p_destPath !== './' && $p_destPath !== '/') {
				$p_destPath = rtrim($p_destPath, '/');
			}
		}
		$v_result = true;

		if ($p_remove_path_prefix) {
			$p_remove_path_prefix = $this->_translateWinPath($p_remove_path_prefix);

			// ----- Look for path to remove format (should end by /)
			if (!empty($p_remove_path_prefix) && !str_ends_with($p_remove_path_prefix, '/')) {
				$p_remove_path_prefix .= '/';
			}
			$p_remove_path_prefix_length = strlen($p_remove_path_prefix);
		} else {
			$p_remove_path_prefix_length = 0;
		}

		switch ($p_mode) {
			case "complete":
			case "partial":
			case "list":
				break;
			default:
				$this->_error('Invalid extract mode (' . $p_mode . ')');
				return false;
		}

		clearstatcache();

		while (($v_binary_data = $this->_readBlock()) !== null && $v_binary_data !== false && strlen($v_binary_data) != 0) {
			if (!$this->_readHeader($v_binary_data, $v_header)) {
				return false;
			}

			if (empty($v_header['filepath'])) {
				continue;
			}

			if ($this->processLongHeader($v_header)) {
				return false;
			}

			// Determine which files to extract
			$v_extract_file = $p_destPath !== null;
			
			if ($v_extract_file && is_array($p_file_list)) {
				// ----- By default no untar if the file is not found in file list
				$v_extract_file = false;
				foreach ($p_file_list as $allowedPath) {
					// ----- Look if it is a directory
					if (str_ends_with($allowedPath, '/')) {
						// ----- Look if the directory is in the filepath
						if (str_starts_with($v_header['filepath'], $allowedPath)) {
							$v_extract_file = true;
							break;
						}
					}

					// ----- It is a file, so compare the file names
					elseif ($allowedPath == $v_header['filepath']) {
						$v_extract_file = true;
						break;
					}
				}
			}

			// ----- Look if this file need to be extracted
			//if ($v_extract_file) {

			// remove path prefix
			if ($p_remove_path_prefix && str_starts_with($v_header['filepath'], $p_remove_path_prefix)) {
				$v_header['filepath'] = substr($v_header['filepath'], $p_remove_path_prefix_length);
				$v_header['filepath_norm'] = $this->_normalizePath($v_header['filepath']);
				$v_header['filesafe'] = $this->_isRelativePathSafe($v_header['filepath_norm']);
			}

			// calculate extracted path for the file
			$extractedPath = null;
			if ($p_destPath) {
				$extractedPath = $p_destPath . '/' . ltrim($v_header['filepath'], '/');
			}

			// ----- Validate path doesn't escape destination (Zip Slip prevention)
			if (!$this->_validatePathSecurity($extractedPath, $p_destPath)) {
				$message = 'Zip Slip path traversal attempt detected: \'' . $extractedPath . '\'';
				if ($this->_strict) {
					$this->_error($message);
					return false;
				}
				$recordEntryDetail($v_header, null, 'zip_slip');
				$this->_jumpBlock(ceil(($v_header['size'] / 512)));
				continue;
			}

			$typeFlag = $v_header['typeflag'];

			// ----- Device and special file types cannot be extracted in PHP
			if ($typeFlag === self::TYPE_CHAR_SPECIAL
				|| $typeFlag === self::TYPE_BLOCK_SPECIAL
				|| $typeFlag === self::TYPE_FIFO) {
				$message = 'Special file type cannot be extracted: \'' . $extractedPath . '\'';
				if ($this->_strict) {
					$this->_error($message);
					return false;
				}
				$recordEntryDetail($v_header, null, 'device');
				if (($v_header['size'] ?? 0) > 0) {
					$this->_jumpBlock(ceil(($v_header['size'] / 512)));
				}
				continue;
			}

			if ($v_extract_file) {
				if (file_exists($extractedPath)) {
					if (@is_dir($extractedPath) && ($typeFlag === self::TYPE_FILE)) {
						$this->_error('File \'' . $extractedPath . '\' already exists as a directory');
						return false;
					}
					if ($this->_isArchive($extractedPath) && ($typeFlag === self::TYPE_DIRECTORY)) {
						$this->_error('Directory \'' . $extractedPath . '\' already exists as a file');
						return false;
					}
					if (!is_writable($extractedPath)) {
						$this->_error('File \'' . $extractedPath . '\' already exists and is write protected');
						return false;
					}
					if (filemtime($extractedPath) > $v_header['mtime']) {
						// To be completed : An error or silent no replace ?
						// @todo: error, skip, newest, oldest, replace all, or callback
					}

				} elseif (!$this->_dirCheck(($typeFlag === self::TYPE_DIRECTORY
												? $extractedPath : dirname($extractedPath)))) {
					// ----- Check the directory availability and create it if necessary

					$this->_error('Unable to create path for \'' . $extractedPath . '\'');
					return false;
				}
			}

			//if ($v_extract_file) {
				if ($typeFlag === self::TYPE_DIRECTORY) {
					$v_created = false;
					if ($v_extract_file && !@file_exists($extractedPath)) {
						if (!@mkdir($extractedPath, Prado::getDefaultDirPermissions())) {
							$this->_error('Unable to create directory {'
						  	. $extractedPath . '}');
							return false;
						}
						chmod($extractedPath, Prado::getDefaultDirPermissions());
						$v_created = true;
						$directoryModes[$extractedPath] = $v_header['mode'];
					}
					$recordEntryDetail($v_header, $extractedPath, $v_created);
				} elseif ($typeFlag === self::TYPE_SYMLINK || $typeFlag === self::TYPE_HARDLINK) {
					// ----- Symlink (typeflag = "2"),  Hard link (typeflag = "1")
					if ($isSymLink = ($typeFlag === self::TYPE_SYMLINK)) {
						$linkType = 'Symlink';
						$linkMethod = 'symlink';
					} else {
						$linkType = 'Hard link';
						$linkMethod = 'link';
					}
	
					if ($p_remove_path_prefix && str_starts_with($v_header['linkpath'], $p_remove_path_prefix)) {
						$v_header['linkpath'] = substr($v_header['linkpath'], $p_remove_path_prefix_length);
						$v_header['linkpath_norm'] = $this->_normalizePath($v_header['linkpath']);
						$v_header['linksafe'] = $this->_isRelativePathSafe($v_header['linkpath_norm']);
					}
	
					$v_linkpath = trim($v_header['linkpath'] ?? '');
					if (!$this->_validateLinkTarget($v_linkpath, dirname($extractedPath), $p_destPath)) {
						$message = $linkType . ' target outside extraction directory: ' . $v_linkpath;
						if ($this->_strict) {
							$this->_error($message);
							return false;
						}
						$recordEntryDetail($v_header, null, $isSymLink ? 'symlink' : 'hardlink');
						continue;
					}
					$v_resolvedLinkpath = $v_linkpath;
					if (!str_starts_with($v_resolvedLinkpath, '/')) {
						$v_resolvedLinkpath = dirname($extractedPath) . '/' . $v_resolvedLinkpath;
					}
					if ($v_extract_file && !@$linkMethod($v_resolvedLinkpath, $extractedPath)) {
						$this->_error('Unable to create ' . strtolower($linkType) . ': ' . $extractedPath);
						return false;
					}
					$recordEntryDetail($v_header, $extractedPath, $v_extract_file);
				} else {
					if ($v_extract_file) {
						if (($v_dest_file = @fopen($extractedPath, "wb")) == 0) {
							$this->_error('Error while opening {' . $extractedPath
								. '} in write binary mode');
							return false;
						}
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
						@touch($extractedPath, $v_header['mtime']);
						
						// ----- Check the file size
						clearstatcache();
						if (filesize($extractedPath) != $v_header['size']) {
							$this->_error('Extracted file ' . $extractedPath
							. ' does not have the correct file size \''
							. filesize($extractedPath)
							. '\' (' . $v_header['size']
							. ' expected). Archive may be corrupted.');
							return false;
						}
					}
	
					// @todo File mod, To be completed
					//chmod($v_header[filepath], DecOct($v_header[mode]));
					$recordEntryDetail($v_header, $extractedPath, $v_extract_file);
	
				}
			//} else {
			//	$this->_jumpBlock(ceil(($v_header['size'] / 512)));
			//}
		} // end while (strlen($v_binary_data = $this->_readBlock()) != 0);

		foreach ($directoryModes as $dirPath => $mode) {
			// @todo
			//chmod($dirPath, $mode);
		}

		return true;
	}

	/**
	 * Removes entries that were successfully written during the current extraction.
	 * Called when extraction fails and {@see getRollbackOnFailure()} is true.
	 * Files and links are removed first; directories are removed last (deepest first)
	 * and only when they are empty.
	 *
	 * @since 4.3.3
	 * @param ?array $rollback
	 */
	private function _rollbackExtraction(?array $rollback): void
	{
		if (empty($rollback)) {
			return;
		}

		$files = [];
		$dirs = [];
		foreach ($rollback as $info) {
			if (!isset($info['extractedPath'])) {
				continue;
			}
			if ($info['typeflag'] === self::TYPE_DIRECTORY) {
				$dirs[] = $info['extractedPath'];
			} else {
				$files[] = $info['extractedPath'];
			}
		}

		// Remove regular files, symlinks, and hard links.
		foreach ($files as $filePath) {
			if (file_exists($filePath) || is_link($filePath)) {
				@unlink($filePath);
			}
		}

		// Remove directories deepest-first (reverse-sorted path strings put
		// deeper paths earlier when child paths are always longer than parents).
		rsort($dirs);
		foreach ($dirs as $dirPath) {
			if (!is_dir($dirPath)) {
				continue;
			}
			$items = @scandir($dirPath);
			if ($items !== false && count($items) <= 2) { // only '.' and '..'
				@rmdir($dirPath);
			}
		}
	}

	/**
	 * Returns true when a relative path contains no traversal sequences.
	 * Checks: no '..' components, no leading '/', no Windows drive letter.
	 *
	 * @param string $path Relative path to test.
	 * @return bool
	 * @since 4.3.3
	 */
	private function _isRelativePathSafe(string $path): bool
	{
		if (empty($path)) {
			return false;
		}
		// Absolute path (Unix or Windows UNC)
		if ($path[0] === '/' || $path[0] === '\\') {
			return false;
		}
		// Windows drive letter (e.g. "C:\"...)
		if (strlen($path) >= 3 && $path[1] === ':') {
			return false;
		}
		// Any '..' path component
		foreach (preg_split('/[\/\\\\]/', $path) as $part) {
			if ($part === '..') {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validates that the extracted path doesn't escape the destination directory.
	 * Prevents Zip Slip attacks via path traversal sequences like ../
	 *
	 * @param string $v_filepath The constructed file path from tar entry
	 * @param string $p_destPath The extraction destination path
	 * @return bool True if path is contained, false if it escapes
	 * @since 4.3.3
	 */
	private function _validatePathSecurity($v_filepath, $p_destPath)
	{
		$normalizedFilePath = $this->_normalizePath($v_filepath);
		$normalizedDestPath = $this->_normalizePath($p_destPath);

		if (strpos($normalizedFilePath . '/', $normalizedDestPath . '/') !== 0) {
			return false;
		}

		return true;
	}

	/**
	 * Validates that a hard link target is within the extraction directory.
	 *
	 * @param string $v_linkpath The hard link target (file that already exists)
	 * @param string $v_dir The directory where the hard link is being created
	 * @param string $p_destPath The extraction destination path
	 * @return bool True if target is safe, false if it escapes
	 * @since 4.3.3
	 */
	private function _validateLinkTarget($v_linkpath, $v_dir, $p_destPath)
	{
		if ($v_linkpath === '') {
			return false;
		}

		if (!str_starts_with($v_linkpath, '/')) {
			$v_linkpath = $v_dir . '/' . $v_linkpath;
		}
		return $this->_validatePathSecurity($v_linkpath, $p_destPath);
	}

	/**
	 * Normalizes a path for comparison by resolving . and .. sequences.
	 *
	 * @param string $path The path to normalize
	 * @return ?string The normalized path, null if path escapes root
	 */
	protected function _normalizePath($path)
	{
		if ($path === null) {
			return null;
		}
		$isAbsolute = str_starts_with($path, '/');
		$parts = explode('/', $path);
		$stack = [];

		foreach ($parts as $part) {
			if ($part === '' || $part === '.') {
				continue;
			}

			if ($part !== '..') {
				$stack[] = $part;
				continue;
			}
			if (!empty($stack) && end($stack) !== '..') {
				array_pop($stack);
			} else {
				if ($isAbsolute) {
					return null; // above root
				}
				$stack[] = '..';
			}
		}

		$result = implode('/', $stack);

		if ($isAbsolute) {
			$result = '/' . $result;
		}

		return $result === '' ? ($isAbsolute ? '/' : '.') : $result;
	}

	/**
	 * Check if a directory exists and create it (including parent
	 * dirs) if not.
	 *
	 * @param string $p_dir directory to check
	 *
	 * @return bool true if the directory exists or was created
	 */
	protected function _dirCheck($p_dir): bool
	{
		if ((@is_dir($p_dir)) || ($p_dir == '')) {
			return true;
		}

		$p_parent_dir = dirname($p_dir);

		if (($p_parent_dir != $p_dir) && ($p_parent_dir != '') && (!$this->_dirCheck($p_parent_dir))) {
			return false;
		}

		if (!@mkdir($p_dir, Prado::getDefaultDirPermissions())) {
			$this->_error("Unable to create directory '$p_dir'");
			return false;
		}
		chmod($p_dir, Prado::getDefaultDirPermissions());

		return true;
	}

	/**
	 * Translates Windows path separators and removes disk letters from paths.
	 *
	 * On Windows, removes the disk letter (e.g., "C:") and converts backslashes
	 * to forward slashes for consistent path handling across platforms.
	 *
	 * @param string $p_destPath The path to translate.
	 * @param bool $p_remove_disk_letter Whether to remove the disk letter (default true).
	 * @return string The translated path.
	 * @since 4.3.3
	 */
	protected function _translateWinPath($p_destPath, $p_remove_disk_letter = true)
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
			return $p_destPath;
		}

		// ----- Look for disk letter
		if ($p_remove_disk_letter && ($v_position = strpos($p_destPath, ':') !== false)) {
			$p_destPath = substr($p_destPath, $v_position + 1);
		}

		// ----- Change potential windows directory separator
		return str_replace('\\', '/', $p_destPath);
	}
}
