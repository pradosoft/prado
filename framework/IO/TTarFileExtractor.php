<?php

/**
 * TTarFileExtractor class file
 *
 * @author Vincent Blavet <vincent@phpconcept.net>
 */

namespace Prado\IO;

define('PRADO_TAR_DIR_DEFAULT', true);

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
 * **Non-atomic extraction with restore (default)**
 *
 * By default ({@see getAtomic()} is false) files are written directly to the destination.
 * When {@see getRestoreOnFailure()} is true (also the default), any pre-existing file that
 * would be overwritten is first renamed to a private backup directory; on success the
 * backups are discarded, and on failure any newly-written files are removed and the
 * originals are restored from backup.  Use {@see setRestoreOnFailure(false)} to disable
 * this safety net (faster, but partial extractions are permanent on failure).
 *
 * **Atomic extraction (opt-in)**
 *
 * When {@see getAtomic()} is true, the archive is first extracted into a private staging
 * directory under the system temp directory, then files are moved into the real destination
 * one by one.  A failure at any point leaves the destination directory in its original
 * state — either the staging phase failed before anything was written to the destination,
 * or the move phase restores any files it already overwrote.  Use {@see setAtomic(true)}
 * to enable this mode (stronger guarantee, but uses extra disk space for staging).
 * Note: {@see getRestoreOnFailure()} has no bearing on atomic extraction; the atomic mode
 * maintains its own independent backup mechanism during the merge phase.
 *
 * **Permission overrides**
 *
 * {@see setDirModeOverride()} replaces the UNIX mode applied to every extracted
 * directory, overriding whatever the archive stored.  When null (the default)
 * the archive's own mode is used; {@see getDirModeOverride()} provides a
 * {@see \Prado\Prado::getDefaultDirPermissions()} fallback for intermediate parent
 * directories that have no archive entry.  {@see setFileModeOverride()} does the
 * same for regular files.
 *
 * **Conflict modes**
 *
 * When a destination file already exists the behaviour is controlled by
 * {@see getConflictMode()} / {@see setConflictMode()}.  Choose from the five
 * `CONFLICT_*` constants:
 *  - `CONFLICT_OVERWRITE` (default) — always replace the existing file.
 *  - `CONFLICT_ERROR`    — abort with an exception on the first conflict.
 *  - `CONFLICT_SKIP`     — silently skip entries that conflict.
 *  - `CONFLICT_NEWER`    — keep whichever copy (archive or existing) is newer.
 *  - `CONFLICT_OLDER`    — keep whichever copy (archive or existing) is older.
 *
 * Conflict modes apply identically in both atomic and non-atomic modes.
 * Directory entries are never considered a conflict.
 *
 * **Security**
 *
 * In strict mode (the default) extraction is aborted if any entry would escape the
 * destination directory (Zip Slip Attack), or point a symlink or hard link outside it.
 * With strict mode disabled those entries are skipped and recorded via
 * {@see getSkippedFiles()}.  A manifest scan ({@see getManifest()}) always annotates
 * entries that would be skipped with a `reason` key, regardless of strict mode.
 * Security violations additionally carry a `security` key (e.g. `'zip_slip_attack'`,
 * `'is_device'`, `'linkpath_above_root'`), which is absent on conflict-based skips,
 * making it easy to distinguish the two categories.
 *
 * **Basic usage**
 * ```php
 * $extractor = new TTarFileExtractor('/path/to/archive.tar.gz');
 * $extractor->extract('/destination/directory');
 * ```
 *
 * @author Vincent Blavet <vincent@phpconcept.net>
 * @author Brad Anderson <belisoful@icloud.com> v4.3.3 - Zip Slip Defense, Manifest
 *			Inspection, Atomic Extraction-Restore, file conflict resolution, gz/bzip2/xz
 *			decompression, honors tar file and directory modes, callable conflict mode.
 * @since 3.0
 */
class TTarFileExtractor
{
	// =========================================================================
	// TAR typeflag constants (POSIX.1-1988 §3.1.1 + GNU extensions).
	// Digit-based typeflags ('0'–'7') map directly to their integer value.
	// Letter-based typeflags are stored as their ASCII/ord value so that every
	// 		constant is an unambiguous integer.
	// =========================================================================

	/**
	 * @var int Tar Type Constant - Regular file (typeflag '0'; also covers the empty-string old-format entry)
	 * @since 4.3.3
	 */
	public const TYPE_FILE = 0;

	/**
	 * @var int Tar Type Constant - Hard link (typeflag '1')
	 * @since 4.3.3
	 */
	public const TYPE_HARDLINK = 1;

	/**
	 * @var int Tar Type Constant - Symbolic link (typeflag '2')
	 * @since 4.3.3
	 */
	public const TYPE_SYMLINK = 2;

	/**
	 * @var int Tar Type Constant - Character special device (typeflag '3')
	 * @since 4.3.3
	 */
	public const TYPE_CHAR_SPECIAL = 3;

	/**
	 * @var int Tar Type Constant - Block special device (typeflag '4')
	 * @since 4.3.3
	 */
	public const TYPE_BLOCK_SPECIAL = 4;

	/**
	 * @var int Tar Type Constant - Directory (typeflag '5')
	 * @since 4.3.3
	 */
	public const TYPE_DIRECTORY = 5;

	/**
	 * @var int Tar Type Constant - FIFO special file (typeflag '6')
	 * @since 4.3.3
	 */
	public const TYPE_FIFO = 6;

	/**
	 * @var int Tar Type Constant - Contiguous file (typeflag '7')
	 * @since 4.3.3
	 */
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

	// =========================================================================
	// Conflict-mode constants
	// =========================================================================

	/**
	 * Abort extraction (throw) when an entry would overwrite an existing file.
	 * Directory entries are never considered a conflict.
	 * @since 4.3.3
	 */
	public const CONFLICT_ERROR = 0;

	/**
	 * Silently skip any entry whose destination file already exists.
	 * The skipped entry is recorded in the extraction manifest with
	 * `reason = 'conflict_skip'`.
	 * @since 4.3.3
	 */
	public const CONFLICT_SKIP = 1;

	/**
	 * Always overwrite existing destination files with archive entries.
	 * This is the default conflict mode and matches historical behaviour.
	 * @since 4.3.3
	 */
	public const CONFLICT_OVERWRITE = 2;

	/**
	 * When a destination file already exists, keep whichever copy — archive
	 * entry or existing file — has the **newer** modification time.  If the
	 * existing file is newer the archive entry is skipped and recorded with
	 * `reason = 'conflict_existing_newer'`.
	 * @since 4.3.3
	 */
	public const CONFLICT_NEWER = 3;

	/**
	 * When a destination file already exists, keep whichever copy — archive
	 * entry or existing file — has the **older** modification time.  If the
	 * existing file is older the archive entry is skipped and recorded with
	 * `reason = 'conflict_existing_older'`.
	 * @since 4.3.3
	 */
	public const CONFLICT_OLDER = 4;

	// =========================================================================
	// Reason constants
	// =========================================================================

	/**
	 * Skip reason: a tar entry whose stored path contains `../` sequences that would
	 * write files outside the extraction root (Zip Slip attack).
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_ZIP_SLIP = 'zip_slip';

	/**
	 * Skip reason: the tar entry is a character special, block special, or FIFO
	 * device file.  Device files cannot be safely extracted.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_DEVICE = 'device';

	/**
	 * Skip reason: the tar entry is a symbolic link whose target resolves outside
	 * the extraction root.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_SYMLINK = 'symlink';

	/**
	 * Skip reason: the tar entry is a hard link whose target resolves outside the
	 * extraction root.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_HARDLINK = 'hardlink';

	/**
	 * Skip reason: the destination file already exists and the active conflict mode
	 * is {@see CONFLICT_SKIP}.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_CONFLICT_SKIP = 'conflict_skip';

	/**
	 * Skip reason: the destination file already exists, the active conflict mode is
	 * {@see CONFLICT_NEWER}, and the existing file is newer than (or the same age
	 * as) the archive entry — so the existing file is kept.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_CONFLICT_EXISTING_NEWER = 'conflict_existing_newer';

	/**
	 * Skip reason: the destination file already exists, the active conflict mode is
	 * {@see CONFLICT_OLDER}, and the existing file is older than (or the same age
	 * as) the archive entry — so the existing file is kept.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_CONFLICT_EXISTING_OLDER = 'conflict_existing_older';

	/**
	 * Skip reason: the active conflict mode is a user-supplied callable that returned
	 * a falsy value without setting `$reason`.  The extractor uses this constant as
	 * the default skip reason in that case.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_CONFLICT_CALLABLE_SKIP = 'conflict_callable_skip';

	/**
	 * Skip reason: the active conflict mode is a user-supplied callable that threw a
	 * `\TypeError` (e.g. the callable has an incompatible signature).  The entry is
	 * skipped and this constant is recorded as the reason.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const REASON_CONFLICT_CALLABLE_ERROR_SKIP = 'conflict_callable_error_skip';

	// =========================================================================
	// Security constants
	// =========================================================================

	/**
	 * Security-violation type recorded in the manifest `security` field when an
	 * entry is skipped because its stored path would escape the extraction root
	 * (Zip Slip attack).
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const SECURITY_ZIP_SLIP_ATTACK = 'zip_slip_attack';

	/**
	 * Security-violation type recorded in the manifest `security` field when an
	 * entry is skipped because it is a device or FIFO special file.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const SECURITY_IS_DEVICE = 'is_device';

	/**
	 * Security-violation type recorded in the manifest `security` field when an
	 * entry is skipped because its symlink or hard-link target path resolves above
	 * the extraction root.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const SECURITY_LINKPATH_OUTSIDE_DESTINATION = 'linkpath_above_root';


	// =========================================================================
	// Compression constants
	// =========================================================================

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

	// =========================================================================
	// Staging / working-mode constants
	// =========================================================================

	/**
	 * UNIX mode applied to directories during extraction before final permissions
	 * are set by the deferred-chmod pass.  Also used for the atomic staging root
	 * and any intermediate parent directories created by {@see _dirCheck()}.
	 * Owner+group can read, write, and traverse; world has no access.
	 *
	 * @var int
	 * @since 4.3.3
	 */
	public const STAGING_DIR_MODE = 0o755;

	/**
	 * UNIX mode applied to regular files written into the atomic staging
	 * directory while the merge is in progress.  The final permission (from the
	 * archive or {@see setFileModeOverride()}) is applied by {@see _mergeStaging()}
	 * after the file is moved to the real destination.
	 * Owner+group can read and write; world has no access; no execution.
	 *
	 * @var int
	 * @since 4.3.3
	 */
	public const STAGING_FILE_MODE = 0o644;

	/**
	 * UNIX mode applied to extracted directories when none is specified. Within PRADO,
	 * {@see \Prado\Prado::getDefaultDirPermissions()} will be used instead and this
	 * constant is unused.
	 *
	 * @var int
	 * @since 4.3.3
	 */
	public const DEFAULT_DIR_MODE = 0o755;

	/**
	 * The default conflict mode for when there is a conflict between existing files and
	 * files being extracted.
	 *
	 * @var int
	 * @since 4.3.3
	 */
	public const DEFAULT_CONFLICT_MODE = self::CONFLICT_OVERWRITE;

	/**
	 * The default thrown exception class.
	 *
	 * @var string
	 * @since 4.3.3
	 */
	public const DEFAULT_EXCEPTION_CLASS = '\Exception';

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @var string Name / URL of the tar archive.
	 */
	private string $_tarpath = '';

	/**
	 * @var ?resource Active file handle (null when closed).
	 */
	private $_file;

	/**
	 * @var float Timeout in seconds for remote URL downloads. Default 6 s.
	 * @since 4.3.3
	 */
	private float $_urlTimeout = 6.0;

	/**
	 * When true the temp file created for a URL download or LZMA decompression
	 * is retained until the object is destroyed instead of being deleted after
	 * each extraction.  Useful when scanning before extracting the same archive.
	 * @var bool
	 * @since 4.3.3
	 */
	private bool $_retainTempFile = false;

	/**
	 * @var ?string Absolute path to the local copy of a remote or LZMA archive.
	 */
	private ?string $_temp_tarpath = null;

	/**
	 * @var bool Abort (throw) on security issues when true; skip and record when false.
	 * @since 4.3.3
	 */
	private bool $_strict = true;

	/**
	 * @var int Compression type in use for the currently-open file handle.
	 * @since 4.3.3
	 */
	private int $_workingCompression = self::COMPRESSION_NONE;

	/**
	 * @var int Compression type detected for this archive (persists after extraction).
	 * @since 4.3.3
	 */
	private int $_compression = self::COMPRESSION_NONE;

	/**
	 * @var ?array<string,array> Clean manifest (no extraction fields).  Null until populated.
	 * @since 4.3.3
	 */
	private ?array $_tarManifest = null;

	/**
	 * @var ?array<string,array> Full extraction manifest including extracted/extractedPath/reason.
	 * @since 4.3.3
	 */
	private ?array $_tarExtractManifest = null;

	/**
	 * When true, extraction uses a private staging directory so that the destination
	 * is left untouched on failure.  When false (the default), files are written
	 * directly to the destination; {@see $_restoreOnFailure} controls whether a
	 * lightweight backup-and-restore safety net is applied in that case.
	 * @var bool
	 * @since 4.3.3
	 */
	private bool $_atomic = false;

	/**
	 * When true (the default) and {@see $_atomic} is false, pre-existing destination
	 * files that would be overwritten are first renamed to a private backup directory
	 * before writing the archive entry.  On success the backups are discarded; on
	 * failure any newly-written files are removed and the originals are restored.
	 * Has no effect when {@see $_atomic} is true (atomic mode manages its own backup).
	 * @var bool
	 * @since 4.3.3
	 */
	private bool $_restoreOnFailure = true;

	/**
	 * Controls how existing destination files are handled during extraction.
	 * One of the CONFLICT_* constants.  This may be a callable as well.
	 * Default CONFLICT_OVERWRITE.
	 * @var mixed
	 * @since 4.3.3
	 */
	private mixed $_conflictMode = null;

	/**
	 * When non-null, overrides the UNIX permission bits applied to every
	 * directory created or updated during extraction, regardless of the
	 * mode stored in the archive entry.
	 * When null, the archive's stored mode is used; {@see getDirModeOverride()}
	 * falls back to {@see \Prado\Prado::getDefaultDirPermissions()} when the
	 * Prado class is available (used for intermediate parent directories
	 * that have no archive entry of their own).
	 * @var ?int
	 * @since 4.3.3
	 */
	private ?int $_dirModeOverride = null;

	/**
	 * When non-null, overrides the UNIX permission bits applied to every
	 * file written during extraction, regardless of the mode stored in the
	 * archive entry.  When null, the archive's stored mode is used.
	 * @var ?int
	 * @since 4.3.3
	 */
	private ?int $_fileModeOverride = null;

	/**
	 * Fully-qualified class name of the exception thrown by {@see _error()}.
	 * When null, {@see DEFAULT_EXCEPTION_CLASS} (`\Exception`) is used.
	 * The class must be constructable with a single string message argument.
	 *
	 * @var ?string
	 * @since 4.3.3
	 */
	private ?string $_exceptionClass = null;

	// =========================================================================
	// Construction / Destruction
	// =========================================================================

	/**
	 * @param string $p_tarpath Path or URL of the tar archive to operate on.
	 */
	public function __construct(string $p_tarpath)
	{
		$this->setTarPath($p_tarpath);
	}

	/**
	 * Closes any open file handle and cleans up temporary files.
	 */
	public function __destruct()
	{
		$this->_completeTarFile();
	}

	// =========================================================================
	// Public API — Extraction
	// =========================================================================

	/**
	 * Extracts the archive to the specified directory.
	 *
	 * @param string $p_destPath Destination directory.  Defaults to the current
	 *                            working directory when empty.
	 * @return bool True on success, false on error.
	 */
	public function extract(string $p_destPath = ''): bool
	{
		return $this->extractModify($p_destPath);
	}

	// =========================================================================
	// Public API — Extraction Settings
	// =========================================================================

	/**
	 * Returns the path or URL of the tar archive this extractor operates on.
	 *
	 * @return string Local file path or remote URL (http/https/ftp).
	 * @since 4.3.3
	 */
	public function getTarPath(): string
	{
		return $this->_tarpath;
	}

	/**
	 * Internally sets the tarpath of the extractor.
	 *
	 * @param string $value
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	protected function setTarPath(string $value): static
	{
		$this->_tarpath = $value;
		return $this;
	}

	/**
	 * Returns whether strict mode is enabled.
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	public function getStrict(): bool
	{
		return $this->_strict;
	}

	/**
	 * Enables or disables strict mode.
	 * In strict mode (default) any security violation aborts extraction with an
	 * exception.  When disabled, violations are skipped and recorded instead.
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
	 * Returns whether atomic extraction is enabled (default false).
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	public function getAtomic(): bool
	{
		return $this->_atomic;
	}

	/**
	 * Enables or disables atomic extraction.
	 *
	 * When false (the default) files are written directly to the destination.
	 * If {@see getRestoreOnFailure()} is also true (the default), pre-existing
	 * files are backed up before overwriting and restored on failure.
	 *
	 * When true, files are staged in a private temp directory first; the
	 * destination is modified only during the final merge phase, which backs up
	 * any overwritten files so they can be restored on failure.  Atomic mode
	 * provides a stronger all-or-nothing guarantee at the cost of extra I/O and
	 * temporary disk space.
	 *
	 * Note: {@see getRestoreOnFailure()} has no bearing on atomic extraction.
	 *
	 * @param bool $value
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setAtomic(bool $value): static
	{
		$this->_atomic = $value;
		return $this;
	}

	/**
	 * Returns whether the non-atomic extractor restores the destination on failure
	 * (default true).
	 *
	 * When true and {@see getAtomic()} is false, any pre-existing file that would be
	 * overwritten is renamed to a private backup directory before the archive entry is
	 * written.  On success the backups are discarded; on failure any newly-written
	 * files are removed and the originals are restored.  Has no effect when
	 * {@see getAtomic()} is true.
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	public function getRestoreOnFailure(): bool
	{
		return $this->_restoreOnFailure;
	}

	/**
	 * Enables or disables the non-atomic restore-on-failure safety net.
	 *
	 * When true (the default), the non-atomic extractor backs up pre-existing
	 * destination files before overwriting them and restores them if extraction
	 * fails.  Set to false to disable this behaviour (faster, but a partial
	 * extraction cannot be rolled back).  Has no effect when {@see getAtomic()}
	 * is true.
	 *
	 * @param bool $value
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setRestoreOnFailure(bool $value): static
	{
		$this->_restoreOnFailure = $value;
		return $this;
	}

	/**
	 * Returns the active conflict mode (one of the `CONFLICT_*` constants).
	 *
	 * @return mixed
	 * @since 4.3.3
	 */
	public function getConflictMode(): mixed
	{
		return $this->_conflictMode ?? self::DEFAULT_CONFLICT_MODE;
	}

	/**
	 * Sets how extraction handles a destination file that already exists.
	 *
	 * Accepts either one of the five `CONFLICT_*` integer constants or any PHP
	 * callable with the signature:
	 *   `function(array $entry, string $extractedPath, ?string &$reason): bool`
	 * A callable must return `true` to overwrite the existing file or `false` to
	 * skip the entry.  When returning `false` it may set `$reason` to a custom
	 * string; if left empty the extractor records {@see REASON_CONFLICT_CALLABLE_SKIP}.
	 * A `\TypeError` thrown inside the callable causes the entry to be skipped with
	 * {@see REASON_CONFLICT_CALLABLE_ERROR_SKIP}.
	 *
	 * @param mixed $value One of `CONFLICT_ERROR`, `CONFLICT_SKIP`,
	 *                     `CONFLICT_OVERWRITE`, `CONFLICT_NEWER`, `CONFLICT_OLDER`,
	 *                     or a PHP callable.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setConflictMode(mixed $value): static
	{
		$this->_conflictMode = $value;
		return $this;
	}

	/**
	 * Returns the directory-permission override, or a fallback when none is set.
	 *
	 * Resolution order:
	 *  1. The value set via {@see setDirModeOverride()} if non-null.
	 *  2. {@see \Prado\Prado::getDefaultDirPermissions()} when the Prado class is
	 *     available (using the fully-qualified name so this method remains portable
	 *     independent of any `use` import).
	 *  3. `null` — callers that need a concrete mode should use `?? 0o755`.
	 *
	 * This method serves as the single authority for intermediate parent directories
	 * (those with no explicit archive entry), replacing direct calls to
	 * `Prado::getDefaultDirPermissions()` throughout the extractor.
	 *
	 * @return ?int UNIX permission bits, or null when no default is available.
	 * @since 4.3.3
	 */
	public function getDirModeOverride(): ?int
	{
		if ($this->_dirModeOverride !== null) {
			return $this->_dirModeOverride;
		}
		if (defined('PRADO_TAR_DIR_DEFAULT') && constant('PRADO_TAR_DIR_DEFAULT') && class_exists('\Prado\Prado')) {
			return \Prado\Prado::getDefaultDirPermissions();
		}
		return null;
	}

	/**
	 * Sets a directory-permission override applied to every directory created or
	 * updated during extraction.  Pass `null` to restore default behaviour (use the
	 * archive's stored mode, falling back to {@see \Prado\Prado::getDefaultDirPermissions()}).
	 *
	 * @param ?int $value UNIX permission bits (e.g. `0o755`), or null to clear.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setDirModeOverride(?int $value): static
	{
		$this->_dirModeOverride = $value;
		return $this;
	}

	/**
	 * Returns the file-permission override, or null when none is set (meaning the
	 * archive's stored mode is used).
	 *
	 * @return ?int UNIX permission bits, or null.
	 * @since 4.3.3
	 */
	public function getFileModeOverride(): ?int
	{
		return $this->_fileModeOverride;
	}

	/**
	 * Sets a file-permission override applied to every regular file written during
	 * extraction.  Pass `null` to restore default behaviour (use the archive's
	 * stored mode).
	 *
	 * @param ?int $value UNIX permission bits (e.g. `0o644`), or null to clear.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setFileModeOverride(?int $value): static
	{
		$this->_fileModeOverride = $value;
		return $this;
	}

	/**
	 * Returns the fully-qualified exception class name used by {@see _error()}.
	 *
	 * @return string
	 * @since 4.3.3
	 */
	public function getExceptionClass(): string
	{
		return $this->_exceptionClass ?? self::DEFAULT_EXCEPTION_CLASS;
	}

	/**
	 * Sets the exception class thrown by {@see _error()}.  The class must exist
	 * and be constructable with a single string message argument (i.e. extend
	 * `\Exception` or implement `\Throwable` with a compatible constructor).
	 * Invalid values are stored as-is; {@see _error()} validates at throw time
	 * and falls back to `\Exception` when the stored value is unusable.
	 *
	 * @param ?string $value Fully-qualified class name, e.g. `\RuntimeException`.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setExceptionClass(?string $value): static
	{
		if (empty($value)) {
			$this->_exceptionClass = null;
		} else {
			$this->_exceptionClass = $value;
		}
		return $this;
	}

	// =========================================================================
	// Protected — Conflict Resolution
	// =========================================================================

	/**
	 * Returns a callable that implements the active conflict-resolution strategy.
	 *
	 * The returned callable has the signature:
	 *   `function(array $entry, string $extractedPath, ?string &$reason): bool`
	 * where returning `true` means "overwrite / write the entry" and `false` means
	 * "skip the entry" (with `$reason` set to the appropriate `REASON_CONFLICT_*`
	 * constant).
	 *
	 * For the five built-in {@see CONFLICT_*} constants the method returns one of
	 * the `resolveConflict*()` methods on this instance.  When {@see getConflictMode()}
	 * returns a PHP callable instead of a constant, that callable is wrapped so that:
	 *  - A truthy return → overwrite (reason stays null).
	 *  - A falsy return with an empty `$reason` → skip with
	 *    `REASON_CONFLICT_CALLABLE_SKIP`.
	 *  - A `\TypeError` thrown inside the callable → skip with
	 *    `REASON_CONFLICT_CALLABLE_ERROR_SKIP`.
	 * An unrecognised non-callable value falls back to
	 * {@see resolveConflictOverwriteExisting()} (always overwrite).
	 *
	 * @return callable(array,string,?string&):bool
	 * @since 4.3.3
	 */
	protected function getConflictModeFunction(): callable
	{
		$conflictMode = $this->getConflictMode();
		return match (true) {
			$conflictMode === self::CONFLICT_ERROR => [$this, 'resolveConflictError'],
			$conflictMode === self::CONFLICT_SKIP => [$this, 'resolveConflictSkipTar'],
			$conflictMode === self::CONFLICT_OVERWRITE => [$this, 'resolveConflictOverwriteExisting'],
			$conflictMode === self::CONFLICT_NEWER => [$this, 'resolveConflictNewer'],
			$conflictMode === self::CONFLICT_OLDER => [$this, 'resolveConflictOlder'],
			is_callable($conflictMode) => function (array $entry, string $extractedPath, ?string &$reason) use ($conflictMode): bool {
				try {
					$result = (bool) $conflictMode($entry, $extractedPath, $reason);
					if (!$result && empty($reason)) {
						$reason = self::REASON_CONFLICT_CALLABLE_SKIP;
					}
				} catch (\TypeError $e) {
					$result = false;
					$reason = self::REASON_CONFLICT_CALLABLE_ERROR_SKIP;
				}
				return $result;
			},
			default => [$this, 'resolveConflictOverwriteExisting'],
		};
	}

	/**
	 * Conflict resolver for {@see CONFLICT_ERROR}: aborts extraction with an exception.
	 * Never returns normally; always throws via {@see _error()}.
	 *
	 * @param array   $entry         Archive entry metadata.
	 * @param string  $extractedPath Absolute destination path that already exists.
	 * @param ?string &$reason       Not used; the method throws before setting it.
	 * @return bool Never returns.
	 * @since 4.3.3
	 */
	protected function resolveConflictError(array $entry, string $extractedPath, ?string &$reason): bool
	{
		$this->_error("Conflict: '$extractedPath' already exists");
	}

	/**
	 * Conflict resolver for {@see CONFLICT_SKIP}: always skips the archive entry.
	 * Sets `$reason` to {@see REASON_CONFLICT_SKIP} and returns false.
	 *
	 * @param array   $entry         Archive entry metadata.
	 * @param string  $extractedPath Absolute destination path.
	 * @param ?string &$reason       Receives {@see REASON_CONFLICT_SKIP}.
	 * @return bool Always false (skip extraction).
	 * @since 4.3.3
	 */
	protected function resolveConflictSkipTar(array $entry, string $extractedPath, ?string &$reason): bool
	{
		$reason = self::REASON_CONFLICT_SKIP;
		return false;
	}

	/**
	 * Conflict resolver for {@see CONFLICT_OVERWRITE}: always overwrites.
	 *
	 * @param array   $entry         Archive entry metadata.
	 * @param string  $extractedPath Absolute destination path.
	 * @param ?string &$reason       Not modified.
	 * @return bool Always true (overwrite with extraction).
	 * @since 4.3.3
	 */
	protected function resolveConflictOverwriteExisting(array $entry, string $extractedPath, ?string &$reason): bool
	{
		return true;
	}

	/**
	 * Conflict resolver for {@see CONFLICT_NEWER}: keeps the newer copy.
	 *
	 * If the archive entry is strictly newer than the existing file, it overwrites.
	 * Otherwise the existing file is kept and `$reason` is set to
	 * {@see REASON_CONFLICT_EXISTING_NEWER}.
	 *
	 * @param array   $entry         Archive entry metadata (must contain 'mtime').
	 * @param string  $extractedPath Absolute destination path.
	 * @param ?string &$reason       Receives {@see REASON_CONFLICT_EXISTING_NEWER} when skipping.
	 * @return bool True to overwrite with extraction, false to skip the extraction.
	 * @since 4.3.3
	 */
	protected function resolveConflictNewer(array $entry, string $extractedPath, ?string &$reason): bool
	{
		// The newer copy "wins" and is kept.  If the archive entry is strictly newer,
		// overwrite the existing file; otherwise leave the existing file in place.
		$existingMtime = (int) @filemtime($extractedPath);
		$archiveMtime = (int) ($entry['mtime'] ?? 0);
		$overwrite = $archiveMtime > $existingMtime;
		if (!$overwrite) {
			$reason = self::REASON_CONFLICT_EXISTING_NEWER;
		}
		return $overwrite;
	}

	/**
	 * Conflict resolver for {@see CONFLICT_OLDER}: keeps the older copy.
	 *
	 * If the archive entry is strictly older than the existing file, it overwrites.
	 * Otherwise the existing file is kept and `$reason` is set to
	 * {@see REASON_CONFLICT_EXISTING_OLDER}.
	 *
	 * @param array   $entry         Archive entry metadata (must contain 'mtime').
	 * @param string  $extractedPath Absolute destination path.
	 * @param ?string &$reason       Receives {@see REASON_CONFLICT_EXISTING_OLDER} when skipping.
	 * @return bool True to overwrite with extraction, false to skip the extraction.
	 * @since 4.3.3
	 */
	protected function resolveConflictOlder(array $entry, string $extractedPath, ?string &$reason): bool
	{
		// The older copy "wins" and is kept.  If the archive entry is strictly older,
		// overwrite the existing file; otherwise leave the existing file in place.
		$existingMtime = (int) @filemtime($extractedPath);
		$archiveMtime = (int) ($entry['mtime'] ?? 0);
		$overwrite = $archiveMtime < $existingMtime;
		if (!$overwrite) {
			$reason = self::REASON_CONFLICT_EXISTING_OLDER;
		}
		return $overwrite;
	}

	// =========================================================================
	// Public API — Skipped / Security
	// =========================================================================

	/**
	 * Returns whether any entries were skipped during the last extraction.
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	public function hasSkippedFiles(): bool
	{
		return count($this->getSkippedFiles()) > 0;
	}

	/**
	 * Returns the extraction-manifest entries that were skipped.
	 * Each entry contains at minimum `reason`, `filepath`, and `typeflag` keys.
	 *
	 * @return array Filtered subset of {@see getExtractManifest()}.
	 * @since 4.3.3
	 */
	public function getSkippedFiles(): array
	{
		return array_filter($this->getExtractManifest() ?? [], static function (array $entry): bool {
			return isset($entry['reason']);
		});
	}

	// =========================================================================
	// Public API — URL / Temp File Settings
	// =========================================================================

	/**
	 * Returns the HTTP/HTTPS timeout in seconds used when downloading remote archives.
	 *
	 * @return float Default 6.0 seconds.
	 * @since 4.3.3
	 */
	public function getUrlTimeout(): float
	{
		return $this->_urlTimeout;
	}

	/**
	 * Sets the HTTP/HTTPS timeout in seconds for downloading remote archives.
	 *
	 * @param float $value
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setUrlTimeout(float $value): static
	{
		$this->_urlTimeout = $value;
		return $this;
	}

	/**
	 * Returns whether the temp file for a URL or LZMA archive is retained
	 * across multiple operations until the object is destroyed.
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	public function getRetainTempFile(): bool
	{
		return $this->_retainTempFile;
	}

	/**
	 * Sets whether the temp file for a URL or LZMA archive is retained across
	 * multiple operations (useful for scan-then-extract workflows).
	 *
	 * @param bool $value
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	public function setRetainTempFile(bool $value): static
	{
		$this->_retainTempFile = $value;
		return $this;
	}

	/**
	 * Returns the path of the currently-active temporary tar file, or null if none.
	 *
	 * @return ?string
	 * @since 4.3.3
	 */
	public function getTempPath(): ?string
	{
		return $this->_temp_tarpath;
	}

	/**
	 * Sets the path of the currently-active temporary tar file.
	 * Pass `null` to clear it (no temp file in use).
	 *
	 * @param ?string $value Absolute path to the temp file, or null to clear.
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	protected function setTempPath(?string $value): static
	{
		$this->_temp_tarpath = $value;
		return $this;
	}

	/**
	 * Deletes the current temporary tar file and clears the internal path.
	 *
	 * A temp file exists when the archive was downloaded from a remote URL or
	 * decompressed from LZMA (.tar.xz).  It can be retained across a
	 * scan-then-extract workflow via {@see setRetainTempFile()}, but any temp file
	 * is always removed by the destructor regardless.
	 *
	 * Return convention (matches historical `unlink()` semantics):
	 *  - `true`  — file was deleted successfully.
	 *  - `false` — `unlink()` failed; the file may still exist.
	 *  - `null`  — no temp file was present; nothing to delete.
	 *
	 * @return ?bool True on success, false if unlink failed, null if no file existed.
	 * @since 4.3.3
	 */
	public function clearTempFile(): ?bool
	{
		$temp_tarpath = $this->getTempPath();
		if ($temp_tarpath !== null) {
			if (!@unlink($temp_tarpath)) {
				return false;
			}
			$this->setTempPath(null);
			return true;
		}
		return null;
	}

	/**
	 * Returns the detected file compression type for this archive.
	 *
	 * @return int One of the COMPRESSION_* constants.
	 * @since 4.3.3
	 */
	public function getCompression(): int
	{
		return $this->_compression;
	}

	/**
	 * Internally sets the value of the detected file compression type if there is one.
	 *
	 * @param int $value
	 * @return static $this For method chaining.
	 * @since 4.3.3
	 */
	protected function setCompression(int $value): static
	{
		$this->_compression = $value;
		return $this;
	}

	// =========================================================================
	// Public API — Manifest
	// =========================================================================


	/**
	 * Returns the full extraction manifest including `extracted`, `extractedPath`,
	 * `reason`, and `security` fields.  Populated after {@see extract()}; null before.
	 * Returns the full metadata map for every entry in the archive.
	 *
	 * Keys are normalised relative paths; directory keys always end with
	 * {@see DIRECTORY_SEPARATOR}.  Directory entries precede file entries.
	 *
	 * Each value array contains at minimum:
	 *  - `path`          string   Canonical map key (matches the array key).
	 *  - `name`          string   Entry basename.
	 *  - `type`          string   'file', 'directory', 'symlink', 'hardlink',
	 *                            'char_device', 'block_device', or 'fifo'.
	 *  - `typeflag`      int      One of the TYPE_* constants.
	 *  - `filename`      string   Raw relative path stored in the archive.
	 *  - `filepath`      string   Working relative path (after prefix removal).
	 *  - `size`          int      Stored size in bytes.
	 *  - `mtime`         int      Modification time (Unix epoch).
	 *  - `mode`          int      UNIX permission bits.
	 *  - `uid`           int      Numeric user ID.
	 *  - `gid`           int      Numeric group ID.
	 *  - `uname`         string   Symbolic user name.
	 *  - `gname`         string   Symbolic group name.
	 *  - `linkpath`      string   Symlink / hard-link target (empty for other types).
	 *  - `checksum`      int      Stored header checksum.
	 *  - `filesafe`      bool     True when the path contains no traversal sequences.
	 *  - `device`        bool     True for character / block special device entries.
	 *  - `extracted`     bool     Was the entry extracted.
	 *  - `extractedPath` string   Path of the extracted file, directory, or link.
	 *  - `reason`        string   Present only when the entry would be skipped during
	 *                         extraction (e.g. 'zip_slip', 'device', 'symlink',
	 *                         'hardlink', 'conflict_skip', 'conflict_existing_newer',
	 *                         'conflict_existing_older').
	 *  - `security`      string   Present only for entries skipped due to a security
	 *                         policy violation: 'zip_slip_attack', 'is_device', or
	 *						   'linkpath_above_root'.  Absent for conflict-based skips,
	 *                         allowing callers to distinguish the two categories.
	 *
	 *
	 * @return ?array<string,array>
	 * @since 4.3.3
	 */
	public function getExtractManifest(): ?array
	{
		return $this->_tarExtractManifest;
	}

	/**
	 * Internally sets the extracted manifest after an extraction.
	 * When building just the Manifest, there is no extraction manifest.
	 *
	 * @since 4.3.3
	 * @param ?array<string,array> $value
	 * @return static
	 */
	protected function setExtractManifest(?array $value): static
	{
		$this->_tarExtractManifest = $value;
		return $this;
	}

	/**
	 * Returns the extraction manifest entry for the given path, including
	 * `extracted`, `extractedPath`, `reason`, and `security` fields.
	 *
	 * @param string $path Relative archive path.
	 * @return ?array
	 * @since 4.3.3
	 */
	public function getExtractManifestInfo(string $path): ?array
	{
		$key = $this->_findExtractManifestKey($path);
		return $key !== null ? ($this->_tarExtractManifest[$key] ?? null) : null;
	}

	/**
	 * Returns an ordered list of relative entry paths contained in the archive.
	 * Directories appear before files and always end with {@see DIRECTORY_SEPARATOR}.
	 * Triggers a lazy scan when the archive has not yet been extracted.
	 *
	 * @return string[]
	 * @since 4.3.3
	 */
	public function getManifestPaths(): array
	{
		return array_keys($this->getManifest());
	}

	/**
	 * Returns whether the manifest has been populated (by extraction or scan).
	 *
	 * @return bool
	 * @since 4.3.3
	 */
	protected function hasManifest(): bool
	{
		return $this->_tarManifest !== null;
	}

	/**
	 * Returns the full metadata map for every entry in the archive.
	 *
	 * Keys are normalised relative paths; directory keys always end with
	 * {@see DIRECTORY_SEPARATOR}.  Directory entries precede file entries.
	 *
	 * Each value array contains at minimum:
	 *  - `path`      string   Canonical map key (matches the array key).
	 *  - `name`      string   Entry basename.
	 *  - `type`      string   'file', 'directory', 'symlink', 'hardlink',
	 *                         'char_device', 'block_device', or 'fifo'.
	 *  - `typeflag`  int      One of the TYPE_* constants.
	 *  - `filename`  string   Raw relative path stored in the archive.
	 *  - `filepath`  string   Working relative path (after prefix removal).
	 *  - `size`      int      Stored size in bytes.
	 *  - `mtime`     int      Modification time (Unix epoch).
	 *  - `mode`      int      UNIX permission bits.
	 *  - `uid`       int      Numeric user ID.
	 *  - `gid`       int      Numeric group ID.
	 *  - `uname`     string   Symbolic user name.
	 *  - `gname`     string   Symbolic group name.
	 *  - `linkpath`  string   Symlink / hard-link target (empty for other types).
	 *  - `checksum`  int      Stored header checksum.
	 *  - `filesafe`  bool     True when the path contains no traversal sequences.
	 *  - `device`    bool     True for character / block special device entries.
	 *  - `reason`    string   Present only when the entry would be skipped during
	 *                         extraction (e.g. 'zip_slip', 'device', 'symlink',
	 *                         'hardlink', 'conflict_skip', 'conflict_existing_newer',
	 *                         'conflict_existing_older').
	 *  - `security`  string   Present only for entries skipped due to a security
	 *                         policy violation: 'zip_slip_attack', 'is_device',
	 *                         or 'linkpath_above_root'.  Absent for conflict-based
	 *						   skips, allowing callers to distinguish the two
	 *						   categories.
	 *
	 * If the archive has not been extracted yet, a scan-only pass is performed.
	 *
	 * @return array<string,array>
	 * @since 4.3.3
	 */
	public function getManifest(): array
	{
		if (!$this->hasManifest() && $this->_openRead()) {
			// Retain the temp file (URL download / LZMA) so a following
			// extract() can reuse it without re-downloading.
			if ($this->getTempPath() !== null) {
				$this->setRetainTempFile(true);
			}

			$extractionManifest = [];
			$v_exception = null;
			try {
				$this->_extractList(null, $extractionManifest, null, null);
			} catch (\Exception $e) {
				$v_exception = $e;
			}

			$this->_close();

			if ($v_exception !== null) {
				throw $v_exception;
			}

			$this->_sortManifest($extractionManifest);
			$this->setManifest($extractionManifest);
		}

		return $this->_tarManifest ?? [];
	}

	/**
	 * Populates the manifest from an extraction or scan manifest, stripping
	 * extraction-specific fields (`extracted`, `extractedPath`).
	 *
	 * @param mixed $manifest
	 * @since 4.3.3
	 */
	protected function setManifest($manifest): void
	{
		$this->_tarManifest = [];
		if (!is_array($manifest)) {
			return;
		}
		foreach ($manifest as $path => $entry) {
			$clean = $entry;
			// Strip extraction-only fields; keep 'reason' so callers can inspect
			// which entries would be skipped (zip_slip, device, symlink, hardlink).
			unset($clean['extracted'], $clean['extractedPath']);
			$this->_tarManifest[$path] = $clean;
		}
	}

	/**
	 * Returns the clean manifest entry for the given path, or null if not found.
	 * The returned array does not include `extracted`, `extractedPath`, or `reason`.
	 *
	 * @param string $path Relative archive path.
	 * @return ?array
	 * @since 4.3.3
	 */
	public function getManifestInfo(string $path): ?array
	{
		$key = $this->_findManifestKey($path);
		return $key !== null ? $this->_tarManifest[$key] : null;
	}

	/**
	 * Returns a single field from the clean manifest entry for the given path.
	 *
	 * @param string $path Relative archive path.
	 * @param string $key  Field name.
	 * @return mixed Field value, or null if the path or field is not found.
	 * @since 4.3.3
	 */
	public function getManifestValue(string $path, string $key): mixed
	{
		$found = $this->_findManifestKey($path);
		return $found !== null ? ($this->_tarManifest[$found][$key] ?? null) : null;
	}

	/**
	 * Returns the entry type string ('file', 'directory', 'symlink', etc.)
	 * for the given archive path, or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?string 'file', 'directory', 'symlink', 'hardlink', 'char_device',
	 *                 'block_device', 'fifo', or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestType(string $path): ?string
	{
		return $this->getManifestValue($path, 'type');
	}

	/**
	 * Returns the TYPE_* constant value for the given archive path, or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?int One of the `TYPE_*` constants, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestTypeFlag(string $path): ?int
	{
		return $this->getManifestValue($path, 'typeflag');
	}

	/**
	 * Returns the stored size in bytes for the given archive entry, or null if not found.
	 * TAR archives do not store a creation time; use {@see getManifestMtime()}.
	 *
	 * @param string $path Relative archive path.
	 * @return ?int Size in bytes, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestSize(string $path): ?int
	{
		return $this->getManifestValue($path, 'size');
	}

	/**
	 * Returns the modification timestamp (Unix epoch) for the given archive entry,
	 * or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?int Unix timestamp, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestMtime(string $path): ?int
	{
		return $this->getManifestValue($path, 'mtime');
	}

	/**
	 * Returns the UNIX permission bits (mode) for the given archive entry,
	 * or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?int UNIX permission bits (e.g. 0644), or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestMode(string $path): ?int
	{
		return $this->getManifestValue($path, 'mode');
	}

	/**
	 * Returns the numeric user ID for the given archive entry, or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?int Numeric UID, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestUid(string $path): ?int
	{
		return $this->getManifestValue($path, 'uid');
	}

	/**
	 * Returns the numeric group ID for the given archive entry, or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?int Numeric GID, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestGid(string $path): ?int
	{
		return $this->getManifestValue($path, 'gid');
	}

	/**
	 * Returns the symbolic user name for the given archive entry, or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?string Symbolic user name, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestUname(string $path): ?string
	{
		return $this->getManifestValue($path, 'uname');
	}

	/**
	 * Returns the symbolic group name for the given archive entry, or null if not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?string Symbolic group name, or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestGname(string $path): ?string
	{
		return $this->getManifestValue($path, 'gname');
	}

	/**
	 * Returns the symlink / hard-link target for the given archive entry.
	 * Returns an empty string for non-link entries, null if the path is not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?string Link target string (may be empty for non-link entries), or null if not found.
	 * @since 4.3.3
	 */
	public function getManifestLinkPath(string $path): ?string
	{
		return $this->getManifestValue($path, 'linkpath');
	}

	/**
	 * Returns whether the given archive path is free of traversal sequences.
	 * True = safe, false = contains '..', absolute, or Windows drive-letter prefix.
	 * Returns null if the path is not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?bool True if safe, false if unsafe, null if not found.
	 * @since 4.3.3
	 */
	public function getManifestIsSafe(string $path): ?bool
	{
		return $this->getManifestValue($path, 'filesafe');
	}

	/**
	 * Returns the skip-reason string stored for the given archive path, or null if none.
	 * A non-null value means the entry would be (or was) skipped during extraction.
	 * The value is one of the `REASON_*` constants.
	 *
	 * @param string $path Relative archive path.
	 * @return ?string One of the `REASON_*` constants, or null if the entry is safe.
	 * @since 4.3.3
	 */
	public function getManifestUnsafeReason(string $path): ?string
	{
		return $this->getManifestValue($path, 'reason');
	}

	/**
	 * Returns the security-violation type for the given archive path, or null if the
	 * entry is safe (or not found).  A non-null return means the entry was (or would
	 * be) skipped specifically due to a security policy, not merely a conflict.
	 *
	 * Possible return values: 'zip_slip_attack', 'is_device', 'linkpath_above_root'.
	 * Returns null for safe entries and for conflict-based skips.
	 *
	 * @param string $path Relative archive path.
	 * @return ?string
	 * @since 4.3.3
	 */
	public function getManifestSecurity(string $path): ?string
	{
		return $this->getManifestValue($path, 'security');
	}

	/**
	 * Returns whether the given archive entry is a device or FIFO special file.
	 * Returns null if the path is not found.
	 *
	 * @param string $path Relative archive path.
	 * @return ?bool True if the entry is a character/block device or FIFO, null if not found.
	 * @since 4.3.3
	 */
	public function getManifestIsDevice(string $path): ?bool
	{
		return $this->getManifestValue($path, 'device');
	}

	// =========================================================================
	// Protected — Core Extraction
	// =========================================================================

	/**
	 * Extracts the archive to $p_destPath, optionally stripping a path prefix.
	 *
	 * @param string  $p_destPath      Destination directory.
	 * @param ?string $p_remove_path   Path prefix to strip from each entry's path.
	 * @return bool True on success.
	 */
	protected function extractModify(string $p_destPath, ?string $p_remove_path = null): bool
	{
		if (!empty($p_destPath)) {
			
			$p_checkPath = rtrim($p_destPath, '/');
			
			// Pre-check: fail fast if the destination is not writable before any staging I/O.
			if (is_dir($p_checkPath)) {
				if (!is_writable($p_checkPath)) {
					$this->_error("Atomic extraction destination '$p_checkPath' is not writable");
					return false;
				}
			} else {
				$parentDir = dirname($p_checkPath);
				if (!is_dir($parentDir)) {
					$this->_error("Atomic extraction destination parent '$parentDir' does not exist");
					return false;
				}
				if (!is_writable($parentDir)) {
					$this->_error("Atomic extraction destination parent '$parentDir' is not writable");
					return false;
				}
			}
			
			if ($this->getAtomic()) {
				return $this->_extractAtomic($p_destPath, $p_remove_path);
			}
			if ($this->getRestoreOnFailure()) {
				return $this->_extractDirectWithRestore($p_destPath, $p_remove_path);
			}
		}
		return $this->_extractDirect($p_destPath, $p_remove_path);
	}

	/**
	 * Core archive-reading loop.  Reads every entry from the open file handle,
	 * recording metadata and (when $p_destPath is non-null) writing files to disk.
	 *
	 * @param ?string    $p_destPath           Destination root; null triggers scan/list mode.
	 * @param array      &$p_manifest          Receives per-entry metadata.
	 * @param null|array $p_file_list          Allow list of paths; null = all entries.
	 * @param ?string    $p_remove_path_prefix Path prefix to strip from stored paths.
	 * @param mixed      $conflictMode         CONFLICT_* constant; null = uses {@see getConflictMode()}.
	 * @param bool       $applyPermissions     When false, skip chmod/touch on files and dirs.
	 *                                         Pass false for staging extractions; permissions
	 *                                         will be applied during the merge phase instead.
	 * @param ?callable  $preWriteHook         Optional hook called just before writing each
	 *                                         non-directory entry (after conflict checks pass).
	 *                                         Signature: `function(string $extractedPath, bool $preexisted): void`.
	 *                                         Used by {@see _extractDirectWithRestore()} to back up
	 *                                         pre-existing files before they are overwritten.
	 *                                         The hook may throw to abort the extraction.
	 * @return bool True on success.
	 * @since 4.3.3
	 */
	protected function _extractList(
		?string $p_destPath,
		array &$p_manifest,
		?array $p_file_list,
		?string $p_remove_path_prefix,
		mixed $conflictMode = null,
		bool $applyPermissions = true,
		?callable $preWriteHook = null
	): bool {
		$conflictMode ??= $this->getConflictMode();

		// ------------------------------------------------------------------
		// Closure: record one entry into the manifest.
		// $security is set to the violation type string ('zip_slip_attack', 'is_device',
		// 'linkpath_above_root') for entries skipped due to a security policy,
		// letting callers distinguish security violations from conflict skips.
		// ------------------------------------------------------------------
		$recordEntryDetail = function (array $fileInfo, ?string $extractedPath, ?string $reason = null, ?string $security = null) use (&$p_manifest): void {
			if (!is_array($p_manifest)) {
				return;
			}

			$normKey = $fileInfo['filepath_norm'] ?? rtrim($fileInfo['filepath'] ?? '', '/\\');
			if (($fileInfo['typeflag'] ?? 0) === self::TYPE_DIRECTORY) {
				$mapKey = rtrim((string) $normKey, '/\\') . DIRECTORY_SEPARATOR;
			} else {
				$mapKey = (string) $normKey;
			}

			$fileInfo['path'] = $mapKey;

			if ($extractedPath !== null) {
				$fileInfo['extracted'] = true;
				$fileInfo['extractedPath'] = $extractedPath;
			}
			if ($reason !== null) {
				$fileInfo['reason'] = $reason;
			}
			if ($security !== null) {
				$fileInfo['security'] = $security;
			}

			$p_manifest[$mapKey] = $fileInfo;
		};

		// ------------------------------------------------------------------
		// Normalise destination path.
		// ------------------------------------------------------------------
		$directoryModes = [];

		if ($p_destPath !== null && $p_destPath !== '') {
			$p_destPath = $this->_translateWinPath($p_destPath, false);
			if ($p_destPath !== './' && $p_destPath !== '/') {
				$p_destPath = rtrim($p_destPath, '/');
			}
		}

		if ($p_remove_path_prefix) {
			$p_remove_path_prefix = $this->_translateWinPath($p_remove_path_prefix);
			if (!str_ends_with($p_remove_path_prefix, '/')) {
				$p_remove_path_prefix .= '/';
			}
			$p_remove_path_prefix_length = strlen($p_remove_path_prefix);
		} else {
			$p_remove_path_prefix_length = 0;
		}

		clearstatcache();

		// ------------------------------------------------------------------
		// Main loop: read one 512-byte header at a time.
		// ------------------------------------------------------------------
		while (($v_binary_data = $this->_readBlock()) !== null
				&& $v_binary_data !== false
				&& strlen($v_binary_data) !== 0) {

			$v_header = [];
			if (!$this->_readHeader($v_binary_data, $v_header)) {
				return false;
			}
			if (empty($v_header['filepath'])) {
				continue;
			}
			if ($this->processLongHeader($v_header)) {
				return false;
			}

			// Decide whether this entry should be extracted.
			$v_extract_file = !empty($p_destPath);

			if ($v_extract_file && is_array($p_file_list)) {
				$v_extract_file = false;
				foreach ($p_file_list as $allowedPath) {
					if (str_ends_with($allowedPath, '/')) {
						if (str_starts_with($v_header['filepath'], $allowedPath)) {
							$v_extract_file = true;
							break;
						}
					} elseif ($allowedPath === $v_header['filepath']) {
						$v_extract_file = true;
						break;
					}
				}
			}

			// Strip path prefix.
			if ($p_remove_path_prefix && str_starts_with($v_header['filepath'], $p_remove_path_prefix)) {
				$v_header['filepath'] = substr($v_header['filepath'], $p_remove_path_prefix_length);
				$v_header['filepath_norm'] = $this->_normalizePath($v_header['filepath']);
				$v_header['filesafe'] = $this->_isRelativePathSafe((string) ($v_header['filepath_norm'] ?? ''));
			}

			$typeFlag = $v_header['typeflag'];

			// ---------------------------------------------------------------
			// Scan / list mode — annotate entries that would be skipped.
			// ---------------------------------------------------------------
			if (!$v_extract_file) {
				// Path traversal.
				if (!($v_header['filesafe'] ?? true)) {
					$recordEntryDetail($v_header, null, self::REASON_ZIP_SLIP, self::SECURITY_ZIP_SLIP_ATTACK);
					if (($v_header['size'] ?? 0) > 0) {
						$this->_jumpBlock((int) ceil($v_header['size'] / 512));
					}
					continue;
				}
				// Device / special files.
				if (in_array($typeFlag, [self::TYPE_CHAR_SPECIAL, self::TYPE_BLOCK_SPECIAL, self::TYPE_FIFO], true)) {
					$recordEntryDetail($v_header, null, self::REASON_DEVICE, self::SECURITY_IS_DEVICE);
					if (($v_header['size'] ?? 0) > 0) {
						$this->_jumpBlock((int) ceil($v_header['size'] / 512));
					}
					continue;
				}
				// Unsafe symlink / hardlink targets.
				if ($typeFlag === self::TYPE_SYMLINK || $typeFlag === self::TYPE_HARDLINK) {
					$linkpath = trim($v_header['linkpath'] ?? '');
					$linksafe = $v_header['linksafe'] ?? $this->_isRelativePathSafe($this->_normalizePath($linkpath) ?? '');
					if (!$linksafe || str_starts_with($linkpath, '/')) {
						$reason = ($typeFlag === self::TYPE_SYMLINK) ? self::REASON_SYMLINK : self::REASON_HARDLINK;
						$recordEntryDetail($v_header, null, $reason, self::SECURITY_LINKPATH_OUTSIDE_DESTINATION);
						continue;
					}
				}
				// Normal scan: record metadata, skip data blocks.
				$recordEntryDetail($v_header, null);
				if (($v_header['size'] ?? 0) > 0
					&& !in_array($typeFlag, [self::TYPE_DIRECTORY, self::TYPE_SYMLINK, self::TYPE_HARDLINK], true)) {
					$this->_jumpBlock((int) ceil($v_header['size'] / 512));
				}
				continue;
			}	// end of List Mode - loop back to block processing.

			$extractedPath = $p_destPath . '/' . ltrim($v_header['filepath'], '/');

			// ---------------------------------------------------------------
			// Extraction mode — validate path security (Zip Slip).
			// ---------------------------------------------------------------
			if (!$this->_validatePathSecurity($extractedPath, $p_destPath)) {
				$message = "Zip Slip path traversal attempt detected: '$extractedPath'";
				if ($this->getStrict()) {
					$this->_error($message);
					return false;
				}
				$recordEntryDetail($v_header, null, self::REASON_ZIP_SLIP, self::SECURITY_ZIP_SLIP_ATTACK);
				if (($v_header['size'] ?? 0) > 0) {
					$this->_jumpBlock((int) ceil($v_header['size'] / 512));
				}
				continue;
			}

			// Device / special files.
			if (in_array($typeFlag, [self::TYPE_CHAR_SPECIAL, self::TYPE_BLOCK_SPECIAL, self::TYPE_FIFO], true)) {
				$message = "Special file type cannot be extracted: '$extractedPath'";
				if ($this->getStrict()) {
					$this->_error($message);
					return false;
				}
				$recordEntryDetail($v_header, null, self::REASON_DEVICE, self::SECURITY_IS_DEVICE);
				if (($v_header['size'] ?? 0) > 0) {
					$this->_jumpBlock((int) ceil($v_header['size'] / 512));
				}
				continue;
			}

			// Snapshot pre-existence before _dirCheck creates the directory.
			$v_preexisted = @file_exists($extractedPath);

			// ---------------------------------------------------------------
			// Conflict resolution (non-directory entries only).
			// Directories are always created or re-used — never a conflict.
			// ---------------------------------------------------------------
			$v_skip_conflict = false;
			$v_conflict_reason = null;

			if ($typeFlag !== self::TYPE_DIRECTORY && $v_preexisted && $conflictMode !== null) {
				$conflictModeFunction = $this->getConflictModeFunction();
				$v_skip_conflict = !$conflictModeFunction($v_header, $extractedPath, $v_conflict_reason);
			}

			if ($v_skip_conflict) {
				$recordEntryDetail($v_header, null, $v_conflict_reason);
				if (!in_array($typeFlag, [self::TYPE_DIRECTORY, self::TYPE_SYMLINK, self::TYPE_HARDLINK], true)
					&& ($v_header['size'] ?? 0) > 0) {
					$this->_jumpBlock((int) ceil($v_header['size'] / 512));
				}
				continue;
			}

			// ---------------------------------------------------------------
			// Ensure parent directory exists.
			// For TYPE_DIRECTORY entries we only create *parent* directories here;
			// the directory itself is created below with STAGING_DIR_MODE so that
			// files can be written into it before the deferred-chmod loop applies
			// the final (potentially restrictive) permission.
			//
			// Always pass STAGING_DIR_MODE as the working mode so that any intermediate
			// parent directories — whether they are archive entries whose final mode
			// will be applied by the deferred loop, or implicit parents with no archive
			// entry — remain owner+group traversable but are not world-accessible.
			// ---------------------------------------------------------------
			if (!$v_preexisted || $typeFlag === self::TYPE_DIRECTORY) {
				if (!$this->_dirCheck(dirname($extractedPath), self::STAGING_DIR_MODE)) {
					$this->_error("Unable to create path for '$extractedPath'");
					return false;
				}
			}

			// Pre-write hook: invoked once per non-directory entry that will be written,
			// after all conflict checks pass.  Used by _extractDirectWithRestore() to
			// back up pre-existing files before they are overwritten.  The hook may
			// throw to abort the extraction (caught by the caller).
			if ($preWriteHook !== null && $typeFlag !== self::TYPE_DIRECTORY) {
				$preWriteHook((string) $extractedPath, $v_preexisted);
			}

			// ---------------------------------------------------------------
			// Write — directory.
			// ---------------------------------------------------------------
			if ($typeFlag === self::TYPE_DIRECTORY) {
				$v_created = !$v_preexisted;
				if (!@file_exists($extractedPath)) {
					// Create with STAGING_DIR_MODE (owner+group only) so files can
					// be written into the directory before final permissions are applied.
					// The override (or tar mode) is applied by the deferred $directoryModes
					// loop below, which is only executed when $applyPermissions is true.
					if (!@mkdir($extractedPath, self::STAGING_DIR_MODE)) {
						$this->_error("Unable to create directory {$extractedPath}");
						return false;
					}
					$v_created = true;
				}
				// Defer applying directory permissions until after extraction so the
				// directory remains writable while we write files into it.
				// getDirModeOverride() resolves in priority order: explicit override →
				// Prado::getDefaultDirPermissions() fallback → null (use tar-stored mode).
				$v_tarDirMode = (int) ($v_header['mode'] ?? 0);

				$v_effDirMode = $this->getDirModeOverride() ?? $v_tarDirMode;
				if ($v_effDirMode > 0) {
					$directoryModes[$extractedPath] = $v_effDirMode;
				}
				$recordEntryDetail($v_header, $extractedPath);

				// ---------------------------------------------------------------
				// Write — symlink / hard link.
				// ---------------------------------------------------------------
			} elseif ($typeFlag === self::TYPE_SYMLINK || $typeFlag === self::TYPE_HARDLINK) {
				$isSymLink = ($typeFlag === self::TYPE_SYMLINK);
				$linkType = $isSymLink ? 'Symlink' : 'Hard link';
				$linkMethod = $isSymLink ? 'symlink' : 'link';

				if ($p_remove_path_prefix && str_starts_with($v_header['linkpath'], $p_remove_path_prefix)) {
					$v_header['linkpath'] = substr($v_header['linkpath'], $p_remove_path_prefix_length);
					$v_header['linkpath_norm'] = $this->_normalizePath($v_header['linkpath']);
					$v_header['linksafe'] = $this->_isRelativePathSafe($v_header['linkpath_norm']);
				}

				$v_linkpath = trim($v_header['linkpath'] ?? '');
				if (!$this->_validateLinkTarget($v_linkpath, dirname($extractedPath), $p_destPath)) {
					$message = "$linkType target outside extraction directory: $v_linkpath";
					if ($this->getStrict()) {
						$this->_error($message);
						return false;
					}
					$v_linkViolation = $isSymLink ? self::REASON_SYMLINK : self::REASON_HARDLINK;
					$recordEntryDetail($v_header, null, $v_linkViolation, self::SECURITY_LINKPATH_OUTSIDE_DESTINATION);
					continue;
				}

				// Symlinks are created with the path stored in the archive (relative or absolute)
				// so that a relative symlink can be safely moved to the final destination
				// during atomic merge without pointing into the staging directory.
				// Hard links require a resolvable absolute path so that link() can locate
				// the target inode.
				if ($isSymLink) {
					$v_resolvedLinkpath = $v_linkpath;
				} else {
					$v_resolvedLinkpath = $v_linkpath;
					if (!str_starts_with($v_resolvedLinkpath, '/')) {
						$v_resolvedLinkpath = dirname($extractedPath) . '/' . $v_resolvedLinkpath;
					}
				}

				// Remove existing link/file at destination before creating new one.
				if ($v_preexisted && (file_exists($extractedPath) || is_link($extractedPath))) {
					@unlink($extractedPath);
				}

				if (!@$linkMethod($v_resolvedLinkpath, $extractedPath)) {
					$this->_error('Unable to create ' . strtolower($linkType) . ": $extractedPath");
					return false;
				}
				$recordEntryDetail($v_header, $extractedPath);

				// ---------------------------------------------------------------
				// Write — regular file (also TYPE_CONTIGUOUS).
				// ---------------------------------------------------------------
			} else {
				$v_dest_file = @fopen($extractedPath, 'wb');
				if ($v_dest_file === false) {
					$this->_error("Error while opening {$extractedPath} in write binary mode");
					return false;
				}

				$n = (int) floor($v_header['size'] / 512);
				for ($i = 0; $i < $n; $i++) {
					$v_content = $this->_readBlock();
					fwrite($v_dest_file, $v_content, 512);
				}
				if (($v_header['size'] % 512) !== 0) {
					$v_content = $this->_readBlock();
					fwrite($v_dest_file, $v_content, ($v_header['size'] % 512));
				}
				@fclose($v_dest_file);

				// Apply mtime and permissions (skipped for staging extractions;
				// _mergeStaging applies them to the final destination).
				if ($applyPermissions) {
					@touch($extractedPath, $v_header['mtime']);
					$v_tarFileMode = (int) ($v_header['mode'] ?? 0);
					$v_effFileMode = $this->getFileModeOverride() ?? $v_tarFileMode;
					if ($v_effFileMode > 0) {
						@chmod($extractedPath, $v_effFileMode);
					}
				} else {
					// Staging extraction: restrict file to owner+group (STAGING_FILE_MODE) so that
					// world cannot read sensitive content while the merge is in progress.
					@chmod($extractedPath, self::STAGING_FILE_MODE);
				}

				// Verify extracted size.
				clearstatcache();
				if (filesize($extractedPath) !== $v_header['size']) {
					$this->_error(
						"Extracted file $extractedPath has incorrect size "
						. filesize($extractedPath) . ' (' . $v_header['size'] . ' expected). '
						. 'Archive may be corrupted.'
					);
					return false;
				}

				$recordEntryDetail($v_header, $extractedPath);
			}
		} // end while

		// Apply deferred directory permissions (after all files are in place).
		// Skipped for staging extractions; _mergeStaging applies them to the final destination.
		if ($applyPermissions) {
			foreach ($directoryModes as $dirPath => $mode) {
				@chmod($dirPath, $mode);
			}
		}

		return true;
	}

	/**
	 * Extracts the archive directly into the destination directory with inline
	 * conflict resolution.
	 *
	 * When {@see getRestoreOnFailure()} is true and `$p_destPath` is non-empty,
	 * delegates to {@see _extractDirectWithRestore()} which backs up pre-existing
	 * files and restores them on failure.  Otherwise extracts with no rollback.
	 *
	 * @param string  $p_destPath
	 * @param ?string $p_remove_path
	 * @return bool
	 */
	private function _extractDirect(string $p_destPath, ?string $p_remove_path): bool
	{
		$extractionManifest = [];
		$v_result = true;

		if (!($v_result = $this->_openRead())) {
			return false;
		}

		$v_exception = null;
		try {
			$v_result = $this->_extractList(
				$p_destPath !== '' ? $p_destPath : null,
				$extractionManifest,
				null,
				$p_remove_path
			);
		} catch (\Exception $e) {
			$v_result = false;
			$v_exception = $e;
		}

		$this->_sortManifest($extractionManifest);
		$this->setExtractManifest($extractionManifest);

		$this->_close();

		if ($v_exception !== null) {
			throw $v_exception;
		}

		if ($this->_tarManifest === null) {
			$this->setManifest($extractionManifest);
		}

		return $v_result;
	}

	/**
	 * Non-atomic extraction with restore-on-failure.
	 *
	 * Files are written directly to `$p_destPath`, but before each non-directory
	 * entry is overwritten its pre-existing destination file (if any) is renamed
	 * into a private backup directory created under {@see _staging_temp_directory()}.
	 *
	 * On success:  all backup files are deleted and the backup directory removed.
	 * On failure:  every file written during this extraction is removed, every
	 *              backed-up original is renamed back to its destination path, and
	 *              the backup directory is removed.
	 *
	 * This provides a lightweight rollback that avoids the extra staging I/O of
	 * {@see _extractAtomic()}.  The backup directory name is produced by
	 * {@see _staging_backup_dir_name()}.
	 *
	 * @param string  $p_destPath    Non-empty destination directory.
	 * @param ?string $p_remove_path Path prefix to strip from archive entries.
	 * @return bool True on success.
	 */
	private function _extractDirectWithRestore(string $p_destPath, ?string $p_remove_path): bool
	{
		// Create a private backup directory under the system temp dir.
		$restoreDir = rtrim($p_destPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->_staging_backup_dir_name();
		if (!@mkdir($restoreDir, self::STAGING_DIR_MODE, true)) {
			$this->_error("Unable to create restore backup directory '$restoreDir'");
			return false;
		}

		$extractionManifest = [];
		// $backups:  destPath => backupPath  — pre-existing files renamed before overwrite.
		// $written:  destPath => true         — paths that the extraction attempted to write.
		$backups = [];
		$written = [];

		// Pre-write hook: called once for each non-directory entry just before it is
		// written, after all conflict checks pass.  Backs up any pre-existing file to
		// $restoreDir and records the write attempt in $written so the failure path
		// knows what to clean up.
		$preWriteHook = function (string $extractedPath, bool $preexisted) use ($restoreDir, &$backups, &$written): void {
			if (file_exists($extractedPath) || is_link($extractedPath)) {
				// Rename the pre-existing file/link to the backup directory.
				$backupPath = $restoreDir . DIRECTORY_SEPARATOR . uniqid('', true);
				if (!@rename($extractedPath, $backupPath)) {
					$this->_error("Unable to backup '$extractedPath' before overwrite (restore-on-failure)");
				}
				$backups[$extractedPath] = $backupPath;
			}
			// Track every path we are about to write, regardless of pre-existence.
			$written[$extractedPath] = true;
		};

		if (!$this->_openRead()) {
			@rmdir($restoreDir);
			return false;
		}

		$v_exception = null;
		$v_result = false;
		try {
			$v_result = $this->_extractList(
				$p_destPath,
				$extractionManifest,
				null,
				$p_remove_path,
				null,      // conflictMode: use getConflictMode()
				true,      // applyPermissions
				$preWriteHook
			);
		} catch (\Exception $e) {
			$v_result = false;
			$v_exception = $e;
		}

		$this->_close();

		if ($v_result && $v_exception === null) {
			// Success: discard all backups and clean up the backup directory.
			foreach ($backups as $backupPath) {
				if (file_exists($backupPath) || is_link($backupPath)) {
					@unlink($backupPath);
				}
			}
		} else {
			// Failure: remove newly-written files and restore original backups.
			foreach ($written as $writtenPath => $unused) {
				// Only unlink files that were not also backed up; for backed-up paths,
				// the rename below will atomically replace whatever was written.
				if (!isset($backups[$writtenPath]) && (file_exists($writtenPath) || is_link($writtenPath))) {
					@unlink($writtenPath);
				}
			}
			foreach ($backups as $origPath => $backupPath) {
				if (file_exists($backupPath) || is_link($backupPath)) {
					@rename($backupPath, $origPath);
				}
			}
		}

		// Remove the (now-empty) backup directory.
		if (is_dir($restoreDir)) {
			@rmdir($restoreDir);
		}

		$this->_sortManifest($extractionManifest);
		$this->setExtractManifest($extractionManifest);

		if ($this->_tarManifest === null) {
			$this->setManifest($extractionManifest);
		}

		if ($v_exception !== null) {
			throw $v_exception;
		}

		return $v_result;
	}

	// =========================================================================
	// Private — Atomic Extraction
	// =========================================================================

	/**
	 * Extracts the archive atomically: files land in a private staging directory
	 * first, then are moved into the real destination with conflict resolution.
	 * If extraction into staging fails the destination is untouched.  If the
	 * merge phase fails, any files already overwritten are restored from backups.
	 *
	 * @param string  $p_destPath
	 * @param ?string $p_remove_path
	 * @return bool
	 */
	private function _extractAtomic(string $p_destPath, ?string $p_remove_path): bool
	{
		// Create the staging directory under the system temp dir for portability.
		$stagingDir = $this->_staging_dir();
		if (!@mkdir($stagingDir, self::STAGING_DIR_MODE, true)) {
			$this->_error("Unable to create atomic staging directory '$stagingDir'");
			return false;
		}

		$stagingManifest = [];

		if (!$this->_openRead()) {
			$this->_removeDirectory($stagingDir);
			return false;
		}

		$v_exception = null;
		try {
			// Phase 1: extract everything into staging (fresh dir — no conflicts).
			// Permissions are not applied to staging; _mergeStaging applies them to the
			// final destination, and symlinks keep their original relative paths so they
			// remain valid after being moved out of the staging directory.
			$v_result = $this->_extractList(
				$stagingDir,
				$stagingManifest,
				null,
				$p_remove_path,
				self::CONFLICT_OVERWRITE,  // staging is always fresh
				false                      // $applyPermissions: handled by _mergeStaging
			);
		} catch (\Exception $e) {
			$v_result = false;
			$v_exception = $e;
		}

		$this->_close();

		if (!$v_result || $v_exception !== null) {
			$this->_removeDirectory($stagingDir);
			if ($v_exception !== null) {
				throw $v_exception;
			}
			return false;
		}

		// Allow subclasses (e.g. test doubles) to inspect the staging directory
		// while it still exists — before the merge moves files to the destination
		// and before cleanup deletes the staging tree.
		$this->_onStagingReady($stagingDir, $stagingManifest);

		// Phase 2: merge staging → destination, applying conflict mode.
		$backups = [];
		try {
			$this->_mergeStaging($stagingDir, $p_destPath, $stagingManifest, $backups);
		} catch (\Exception $e) {
			// Restore overwritten files from backups.
			foreach ($backups as $origPath => $backupPath) {
				if (file_exists($backupPath)) {
					@rename($backupPath, $origPath);
				}
			}
			$this->_removeDirectory($stagingDir);
			$this->_sortManifest($stagingManifest);
			$this->setExtractManifest($stagingManifest);
			if ($this->_tarManifest === null) {
				$this->setManifest($stagingManifest);
			}
			throw $e;
		}

		// Success: clean up staging and backups.
		$this->_removeDirectory($stagingDir);
		foreach ($backups as $backupPath) {
			if (file_exists($backupPath)) {
				@unlink($backupPath);
			}
		}

		$this->_sortManifest($stagingManifest);
		$this->setExtractManifest($stagingManifest);
		if ($this->_tarManifest === null) {
			$this->setManifest($stagingManifest);
		}

		return true;
	}

	/**
	 * Merges files from $stagingDir into $destDir, applying the active conflict mode.
	 * Files that are overwritten have their originals backed up in $backups for rollback.
	 * On completion $manifest entries have their extractedPath updated to point into $destDir.
	 *
	 * @param string  $stagingDir
	 * @param string  $destDir
	 * @param array  &$manifest
	 * @param array  &$backups   Map of original-path => backup-path for rollback.
	 */
	private function _mergeStaging(
		string $stagingDir,
		string $destDir,
		array &$manifest,
		array &$backups
	): void {
		$this->_dirCheck($destDir);

		$stagingDir = rtrim($stagingDir, '/');
		$stagingDirLen = strlen($stagingDir);

		// Directory permissions are applied after all files have been moved so that
		// directories remain writable/traversable during the merge phase.
		$deferredDirModes = [];

		$conflictMode = $this->getConflictMode();

		// For CONFLICT_ERROR pre-scan every file before touching anything.
		if ($conflictMode === self::CONFLICT_ERROR) {
			foreach ($manifest as $entry) {
				if (($entry['typeflag'] ?? 0) === self::TYPE_DIRECTORY) {
					continue;
				}
				if (!isset($entry['extractedPath'])) {
					continue;
				}
				$relPath = substr($entry['extractedPath'], $stagingDirLen);
				$destPath = $destDir . $relPath;
				if (file_exists($destPath) || is_link($destPath)) {
					$this->_error("Conflict: '$destPath' already exists (CONFLICT_ERROR mode)");
				}
			}
		}

		// --- Merge directories first (already sorted before files in manifest). ---
		foreach ($manifest as $mapKey => $entry) {
			if (($entry['typeflag'] ?? 0) !== self::TYPE_DIRECTORY) {
				continue;
			}
			if (!isset($entry['extractedPath'])) {
				continue;
			}
			$relPath = substr($entry['extractedPath'], $stagingDirLen);
			$destPath = $destDir . $relPath;
			// Use STAGING_DIR_MODE (owner+group only); deferred chmod below applies the final mode.
			$this->_dirCheck($destPath, self::STAGING_DIR_MODE);

			// Defer directory chmod — applying restrictive modes before files are moved
			// in would prevent traversal of the directory during the merge phase.
			// getDirModeOverride() resolves in priority order: explicit override →
			// Prado::getDefaultDirPermissions() fallback → null (use tar-stored mode).
			$v_tarDirMode = (int) ($entry['mode'] ?? 0);
			$v_effDirMode = $this->getDirModeOverride() ?? $v_tarDirMode;
			if ($v_effDirMode > 0) {
				$deferredDirModes[$destPath] = $v_effDirMode;
			}

			$manifest[$mapKey]['extractedPath'] = $destPath;
			$manifest[$mapKey]['extracted'] = true;
		}

		$conflictModeFunction = $this->getConflictModeFunction();

		// --- Merge files, symlinks, hardlinks. ---
		foreach ($manifest as $mapKey => $entry) {
			$typeFlag = $entry['typeflag'] ?? self::TYPE_FILE;
			if ($typeFlag === self::TYPE_DIRECTORY) {
				continue;
			}
			if (!isset($entry['extractedPath'])) {
				continue;
			}

			$stagingPath = $entry['extractedPath'];
			$relPath = substr($stagingPath, $stagingDirLen);
			$destPath = $destDir . $relPath;

			$exists = file_exists($destPath) || is_link($destPath);
			$shouldWrite = true;

			if ($exists) {
				$v_conflict_reason = null;
				$shouldWrite = $conflictModeFunction($entry, $destPath, $v_conflict_reason);
				if (!$shouldWrite) {
					$manifest[$mapKey]['reason'] = $v_conflict_reason;
					unset($manifest[$mapKey]['extracted'], $manifest[$mapKey]['extractedPath']);
				}
			}

			if (!$shouldWrite) {
				if (file_exists($stagingPath) || is_link($stagingPath)) {
					@unlink($stagingPath);
				}
				continue;
			}

			// Backup existing file so we can restore it on merge failure.
			if ($exists) {
				$backupPath = $destPath . $this->_staging_backup_dir_name();
				if (!@rename($destPath, $backupPath)) {
					$this->_error("Unable to backup '$destPath' for atomic replace");
				}
				$backups[$destPath] = $backupPath;
			}

			// Ensure the parent directory exists in the destination.
			// Use STAGING_DIR_MODE (owner+group only); archive-entry dirs are already
			// in $deferredDirModes and will receive their final mode below.
			$this->_dirCheck(dirname($destPath), self::STAGING_DIR_MODE);

			// Hard link entries: re-create the link relationship in the destination
			// rather than moving the staging copy.  On the same filesystem rename()
			// would preserve the shared inode anyway, but on a cross-filesystem move
			// a plain copy loses it.  Using link() is the only semantically correct
			// path in both cases.
			$moved = false;
			if ($typeFlag === self::TYPE_HARDLINK) {
				$rawLinkpath = trim($entry['linkpath'] ?? '');
				if ($rawLinkpath !== '') {
					$linkTargetDest = $destDir . '/' . ltrim($rawLinkpath, '/');
					if (file_exists($linkTargetDest) && @link($linkTargetDest, $destPath)) {
						@unlink($stagingPath);  // best-effort cleanup of the staging copy
						$moved = true;
					}
				}
			}
			if (!$moved && !$this->_moveFile($stagingPath, $destPath)) {
				$this->_error("Unable to move '$stagingPath' to '$destPath'");
			}

			// Apply mtime and mode to the moved file.
			@touch($destPath, (int) ($entry['mtime'] ?? 0));
			$v_tarFileMode = (int) ($entry['mode'] ?? 0);
			$v_effFileMode = $this->getFileModeOverride() ?? $v_tarFileMode;
			if ($v_effFileMode > 0) {
				@chmod($destPath, $v_effFileMode);
			}

			$manifest[$mapKey]['extractedPath'] = $destPath;
			$manifest[$mapKey]['extracted'] = true;
		}

		// Apply deferred directory permissions now that all files have been moved in.
		foreach ($deferredDirModes as $dirPath => $mode) {
			@chmod($dirPath, $mode);
		}
	}
	
	/**
	 * Returns the full absolute path for a new atomic staging directory.
	 *
	 * Combines {@see _staging_temp_directory()} and {@see _staging_dir_name()} to
	 * produce a unique, non-existent path under the system temp directory.  Called
	 * once per atomic extraction to allocate the staging root.
	 *
	 * @return string Absolute path for the staging directory (not yet created).
	 * @since 4.3.3
	 */
	protected function _staging_dir(): string
	{
		return $this->_staging_temp_directory() . DIRECTORY_SEPARATOR . $this->_staging_dir_name();
	}

	/**
	 * Returns the base directory under which staging directories are created.
	 *
	 * Defaults to {@see sys_get_temp_dir()}.  Override in a subclass or test
	 * double to redirect staging I/O to a controlled location.
	 *
	 * @return string Absolute path to the staging parent directory.
	 * @since 4.3.3
	 */
	protected function _staging_temp_directory(): string
	{
		return sys_get_temp_dir();
	}

	/**
	 * Returns a unique name for a new atomic staging directory.
	 *
	 * Each call returns a different name so that concurrent or successive
	 * extractions do not collide.  The name is prefixed with `.prado_tar_stage_`
	 * and suffixed with `.tmp` to aid cleanup scripts.
	 *
	 * @return string Unique directory name (relative, no separators).
	 * @since 4.3.3
	 */
	protected function _staging_dir_name(): string
	{
		return uniqid('.prado_tar_stage_', true) . '.tmp';
	}

	/**
	 * Returns a unique backup-directory name used during the atomic merge phase
	 * and during non-atomic restore-on-failure backups.
	 *
	 * In atomic mode ({@see _mergeStaging()}) the name is appended to each
	 * destination file path to create a sibling backup path.  In non-atomic
	 * restore mode ({@see _extractDirectWithRestore()}) a single directory with
	 * this name is created under {@see _staging_temp_directory()} to hold all
	 * backed-up originals.  Each call returns a different value to prevent
	 * collisions between concurrent operations.
	 *
	 * @return string Unique backup identifier / directory name.
	 * @since 4.3.3
	 */
	protected function _staging_backup_dir_name(): string
	{
		return '.~staging_bkp_' . uniqid('', true) . '~';
	}

	/**
	 * Called after phase-1 (staging extraction) completes successfully and before
	 * phase-2 (_mergeStaging) begins.  The staging directory still contains all
	 * extracted files with their staging permissions (STAGING_DIR_MODE /
	 * STAGING_FILE_MODE) and has not yet been cleaned up.
	 *
	 * The default implementation is a no-op.  Override in a subclass or test
	 * double to inspect staging contents, assert permissions, or inject faults.
	 *
	 * @param string $stagingDir      Absolute path to the staging directory root.
	 * @param array  $stagingManifest Manifest populated by phase-1 extraction.
	 * @since 4.3.3
	 */
	protected function _onStagingReady(string $stagingDir, array $stagingManifest): void
	{
	}

	/**
	 * Moves a file from $from to $to.
	 * Tries rename() first (same-filesystem, atomic); falls back to copy+unlink
	 * or symlink recreation for cross-filesystem moves.
	 *
	 * @param string $from
	 * @param string $to
	 * @return bool
	 */
	private function _moveFile(string $from, string $to): bool
	{
		if (@rename($from, $to)) {
			return true;
		}
		// Cross-filesystem fallback: re-create symlinks, copy regular files.
		if (is_link($from)) {
			$target = @readlink($from);
			if ($target !== false && @symlink($target, $to)) {
				@unlink($from);
				return true;
			}
			return false;
		}
		if (@copy($from, $to)) {
			@unlink($from);
			return true;
		}
		return false;
	}

	/**
	 * Recursively removes a directory and all its contents.
	 *
	 * @param string $dir
	 */
	private function _removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		$items = @scandir($dir);
		if ($items === false) {
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if (is_link($path) || is_file($path)) {
				@unlink($path);
			} elseif (is_dir($path)) {
				$this->_removeDirectory($path);
			}
		}
		@rmdir($dir);
	}

	// =========================================================================
	// Private — Archive I/O
	// =========================================================================

	/**
	 * Throws an exception with the given message, using the configured exception
	 * class ({@see getExceptionClass()}).  Falls back to `\Exception` when the
	 * stored class is empty, does not exist, or cannot be instantiated with a
	 * single string argument.
	 *
	 * @param string $p_message
	 * @throws \Exception
	 */
	protected function _error(string $p_message): never
	{
		$cls = $this->getExceptionClass();
		if (!empty($cls) && $cls !== '\Exception' && $cls !== 'Exception') {
			if (class_exists($cls)) {
				try {
					throw new $cls($p_message);
				} catch (\TypeError) {
					// Class exists but constructor is incompatible; fall through.
				}
			}
		}
		throw new \Exception($p_message);
	}

	/**
	 * Opens the archive for reading, downloading remote URLs to a temp file first.
	 * Detects compression and sets the appropriate stream handle.
	 *
	 * @return bool True on success.
	 */
	private function _openRead(): bool
	{
		$isTemporary = false;

		$v_filepath = $this->getTarPath();
		if (str_starts_with($v_filepath, 'http://')
			|| str_starts_with($v_filepath, 'https://')
			|| str_starts_with($v_filepath, 'ftp://')) {

			$v_temppath = $this->getTempPath();
			if ($v_temppath === null) {
				$v_temppath = $this->_new_local_temppath('tar');

				$ctx = stream_context_create([
					'http' => ['timeout' => $timeout = $this->getUrlTimeout()],
					'https' => ['timeout' => $timeout],
				]);
				$v_file_from = @fopen($v_filepath, 'rb', false, $ctx);
				if (!$v_file_from) {
					$this->_error("Unable to open in read mode '{$v_filepath}'");
					return false;
				}
				$v_file_to = @fopen($v_temppath, 'wb');
				if (!$v_file_to) {
					@fclose($v_file_from);
					$this->_error("Unable to open in write mode '{$v_temppath}'");
					return false;
				}
				while ($v_data = @fread($v_file_from, 1024)) {
					@fwrite($v_file_to, $v_data);
				}
				@fclose($v_file_from);
				@fclose($v_file_to);
				$this->setTempPath($v_temppath);
			}
			$v_filepath = $v_temppath;
			$isTemporary = true;
		}

		$this->setCompression($this->_detectCompression($v_filepath));

		$fileHandle = $this->_openFile($v_filepath, $this->getCompression(), $isTemporary);
		if ($fileHandle === false) {
			return false;
		}
		if (is_string($fileHandle)) {
			$this->_error($fileHandle);
			return false;
		}

		$this->_file = $fileHandle;
		if ($isTemporary) {
			$this->setTempPath($v_filepath);
		}

		return true;
	}
	
	/**
	 * Returns a full absolute path for a new unique temporary file.
	 *
	 * Combines {@see _local_temp_directory()} and {@see _local_temp_file()} to produce
	 * a path that does not yet exist on disk.  Used when a local copy of a remote or
	 * LZMA-compressed archive must be written to disk before extraction.
	 *
	 * @param string $prefix Optional filename prefix (e.g. `'tar'`, `'lzma'`).
	 * @return string Absolute path for a new temp file (not yet created).
	 * @since 4.3.3
	 */
	protected function _new_local_temppath(string $prefix = ''): string
	{
		return $this->_local_temp_directory() . DIRECTORY_SEPARATOR . $this->_local_temp_file($prefix);
	}

	/**
	 * Returns the directory under which local temporary files are created.
	 *
	 * Defaults to {@see sys_get_temp_dir()}.  Override in a subclass or test
	 * double to redirect temporary file I/O to a controlled location.
	 *
	 * @return string Absolute path to the local temp file parent directory.
	 * @since 4.3.3
	 */
	protected function _local_temp_directory(): string
	{
		return sys_get_temp_dir();
	}

	/**
	 * Returns a unique filename (relative, no separators) for a new temporary file.
	 *
	 * Each call returns a different name.  The name is suffixed with `.tmp` to
	 * aid cleanup scripts and to make the file type obvious.
	 *
	 * @param string $prefix Optional filename prefix (e.g. `'tar'`, `'lzma'`).
	 * @return string Unique filename (relative, no directory separators).
	 * @since 4.3.3
	 */
	protected function _local_temp_file(string $prefix = ''): string
	{
		return uniqid($prefix, true) . '.tmp';
	}

	/**
	 * Opens a (possibly compressed) file for reading and returns the stream handle.
	 * Returns a string error message on failure, false if the handle could not be created.
	 *
	 * @param string $filepath     Path to the file; updated in place for LZMA decompression.
	 * @param int    $compression  One of the COMPRESSION_* constants.
	 * @param bool   $isTemporary  True when $filepath is a downloaded/decompressed temp file.
	 * @return false|resource|string
	 */
	private function _openFile(string &$filepath, int $compression, bool $isTemporary)
	{
		switch ($compression) {
			case self::COMPRESSION_NONE:
				$handle = @fopen($filepath, 'rb');
				if ($handle === false) {
					return "Unable to open in read binary mode '$filepath'";
				}
				break;

			case self::COMPRESSION_GZIP:
				if (!function_exists('gzopen')) {
					return 'zlib extension is required for gzip compression';
				}
				$handle = @gzopen($filepath, 'rb');
				if ($handle === false) {
					return "Unable to open gzip in read binary mode '$filepath'";
				}
				break;

			case self::COMPRESSION_BZIP2:
				if (!function_exists('bzopen')) {
					return 'bzip2 extension is required for bzip2 compression';
				}
				$handle = @bzopen($filepath, 'r');
				if ($handle === false) {
					return "Unable to open bzip2 in read binary mode '$filepath'";
				}
				break;

			case self::COMPRESSION_LZMA:
				$xzDec = trim(shell_exec('which xzdec') ?: '');
				$xzCmd = trim(shell_exec('which xz') ?: '');
				if (!$xzDec && !$xzCmd) {
					return 'xz command is required for LZMA compression';
				}
				$tempFile = $this->_new_local_temppath('lzma');
				$command = $xzDec
					? escapeshellarg($xzDec) . ' ' . escapeshellarg($filepath) . ' > ' . escapeshellarg($tempFile)
					: escapeshellarg($xzCmd) . ' -dc ' . escapeshellarg($filepath) . ' > ' . escapeshellarg($tempFile);

				$output = [];
				$returnVar = -1;
				exec($command, $output, $returnVar);

				if (!file_exists($tempFile)) {
					return $returnVar !== 0
						? "Unable to decompress LZMA archive: command failed (exit $returnVar)"
						: "Unable to decompress LZMA archive: temp file not created '$tempFile'";
				}

				if ($isTemporary) {
					@unlink($filepath);
					$filepath = $tempFile;
				}

				$handle = @fopen($tempFile, 'rb');
				if ($handle === false) {
					return "Unable to open decompressed LZMA in read binary mode '$filepath'";
				}
				break;

			default:
				return false;
		}

		$this->_workingCompression = $compression;
		return $handle;
	}

	/**
	 * Closes the active file handle and optionally deletes the temp file.
	 *
	 * @param bool $forceClearTemp Delete temp file regardless of retainTempFile setting.
	 * @return bool
	 */
	private function _close(bool $forceClearTemp = false): bool
	{
		$result = false;

		if ($this->_file !== null) {
			if ($this->_workingCompression === self::COMPRESSION_GZIP) {
				$result = @gzclose($this->_file);
			} elseif ($this->_workingCompression === self::COMPRESSION_BZIP2) {
				$result = @bzclose($this->_file);
			} else {
				$result = @fclose($this->_file);
			}
			$this->_file = null;
		}

		if ($forceClearTemp || !$this->getRetainTempFile()) {
			$this->clearTempFile();
		}

		$this->_workingCompression = self::COMPRESSION_NONE;

		return $result;
	}

	/**
	 * Closes the file handle, clears temp files, and blanks the tarname.
	 * Called from the destructor.
	 *
	 * @return bool
	 */
	private function _completeTarFile(): bool
	{
		$result = $this->_close(true);
		$this->setTarPath('');
		return $result;
	}

	/**
	 * Reads one 512-byte block from the archive.
	 *
	 * @return null|false|string The block data, null at end-of-file, false on no handle.
	 */
	private function _readBlock()
	{
		if ($this->_file === null) {
			return false;
		}

		if ($this->_workingCompression === self::COMPRESSION_GZIP) {
			$v_block = @gzread($this->_file, 512);
		} elseif ($this->_workingCompression === self::COMPRESSION_BZIP2) {
			$v_block = @bzread($this->_file, 512);
		} else {
			$v_block = @fread($this->_file, 512);
		}

		if ($v_block === '' || $v_block === null) {
			return null;
		}
		return $v_block;
	}

	/**
	 * Skips $p_len data blocks (512 bytes each) in the archive stream.
	 *
	 * @param ?int $p_len Number of blocks to skip (default 1).
	 * @return bool
	 */
	private function _jumpBlock(?int $p_len = null): bool
	{
		if ($this->_file === null) {
			return true;
		}

		$p_len ??= 1;
		$bytesToSkip = $p_len * 512;

		if ($bytesToSkip <= 0) {
			return true;
		}

		if ($this->_workingCompression === self::COMPRESSION_GZIP) {
			@gzread($this->_file, $bytesToSkip);
		} elseif ($this->_workingCompression === self::COMPRESSION_BZIP2) {
			@bzread($this->_file, $bytesToSkip);
		} else {
			@fseek($this->_file, @ftell($this->_file) + $bytesToSkip);
		}

		return true;
	}

	// =========================================================================
	// Private — Compression Detection
	// =========================================================================

	/**
	 * Detects the compression format of $tarname using magic bytes (local files)
	 * or file-extension heuristics (URLs / unavailable files).
	 *
	 * @param string $tarname Archive path or URL.
	 * @return int One of the COMPRESSION_* constants.
	 * @since 4.3.3
	 */
	private function _detectCompression(string $tarname): int
	{
		$isUrl = str_starts_with($tarname, 'http://')
			|| str_starts_with($tarname, 'https://')
			|| str_starts_with($tarname, 'ftp://');
		$handle = $isUrl ? false : @fopen($tarname, 'rb');

		if ($handle) {
			$magic = fread($handle, 6);
			fclose($handle);

			if ($magic !== false && strlen($magic) >= 2) {
				$bytes = array_values(unpack('C6', str_pad($magic, 6, "\x00")));

				if ($bytes[0] === 0x1f && $bytes[1] === 0x8b && function_exists('gzopen')) {
					return self::COMPRESSION_GZIP;
				}
				if ($bytes[0] === 0x42 && $bytes[1] === 0x5a && function_exists('bzopen')) {
					return self::COMPRESSION_BZIP2;
				}
				if ($bytes[0] === 0xfd && strlen($magic) >= 6 && $magic === "\xfd\x37\x7a\x58\x5a\x00") {
					$xzDec = trim(shell_exec('which xzdec') ?: '');
					$xzCmd = trim(shell_exec('which xz') ?: '');
					if ($xzDec || $xzCmd) {
						return self::COMPRESSION_LZMA;
					}
				}
			}
		}

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
			$xzDec = trim(shell_exec('which xzdec') ?: '');
			$xzCmd = trim(shell_exec('which xz') ?: '');
			if ($xzDec || $xzCmd) {
				return self::COMPRESSION_LZMA;
			}
		}

		return self::COMPRESSION_NONE;
	}

	// =========================================================================
	// Private — TAR Parsing
	// =========================================================================

	/**
	 * Reads and parses a 512-byte tar header block into $v_header.
	 *
	 * @param string $v_binary_data 512-byte header block.
	 * @param array  &$v_header     Receives parsed fields.
	 * @return bool True on success; false on checksum mismatch or format error.
	 */
	private function _readHeader(string $v_binary_data, array &$v_header): bool
	{
		if (strlen($v_binary_data) === 0) {
			$v_header['filepath'] = '';
			return true;
		}
		if (strlen($v_binary_data) !== 512) {
			$v_header['filepath'] = '';
			$this->_error('Invalid block size : ' . strlen($v_binary_data));
			return false;
		}

		// Compute unsigned-sum checksum (bytes 148-155 treated as spaces).
		$v_checksum = 0;
		for ($i = 0; $i < 148; $i++) {
			$v_checksum += ord($v_binary_data[$i]);
		}
		$v_checksum += 8 * 32; // 8 space bytes
		for ($i = 156; $i < 512; $i++) {
			$v_checksum += ord($v_binary_data[$i]);
		}

		$v_data = unpack(
			'a100filepath/a8mode/a8uid/a8gid/a12size/a12mtime/'
			. 'a8checksum/a1typeflag/a100linkpath/a6magic/a2version/'
			. 'a32uname/a32gname/a8devmajor/a8devminor',
			$v_binary_data
		);

		$v_header['checksum'] = OctDec(trim($v_data['checksum']));
		if ($v_header['checksum'] !== $v_checksum) {
			$v_header['filepath'] = '';
			// Last two 512-byte blocks are all-NUL (end-of-archive marker).
			if ($v_checksum === 256 && $v_header['checksum'] === 0) {
				return true;
			}
			$this->_error(
				'Invalid checksum for file "' . $v_data['filepath']
				. '" : ' . $v_checksum . ' calculated, '
				. $v_header['checksum'] . ' expected'
			);
			return false;
		}

		// Decode typeflag.
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

		$v_header['tarpath'] = $filepath;
		$v_header['filepath'] = $filepath;
		$v_header['filepath_norm'] = $filenorm;
		$v_header['filesafe'] = $this->_isRelativePathSafe((string) ($filenorm ?? ''));
		$v_header['name'] = basename($filepath);
		$v_header['filename'] = $filepath;
		$v_header['mode'] = OctDec(trim($v_data['mode']));
		$v_header['uid'] = OctDec(trim($v_data['uid']));
		$v_header['gid'] = OctDec(trim($v_data['gid']));
		$v_header['size'] = OctDec(trim($v_data['size']));
		$v_header['mtime'] = OctDec(trim($v_data['mtime']));
		$v_header['tarlink'] = $linkpath;
		$v_header['linkpath'] = $linkpath;
		$v_header['uname'] = trim($v_data['uname']);
		$v_header['gname'] = trim($v_data['gname']);
		$v_header['typeflag'] = $typeFlag;
		$v_header['device'] = in_array($typeFlag, [self::TYPE_CHAR_SPECIAL, self::TYPE_BLOCK_SPECIAL], true);

		switch ($typeFlag) {
			case self::TYPE_DIRECTORY:
				$v_header['size'] = 0;
				break;
			case self::TYPE_SYMLINK:
			case self::TYPE_HARDLINK:
				$linknorm = $this->_normalizePath($linkpath);
				$v_header['linkpath_norm'] = $linknorm;
				$v_header['linksafe'] = $this->_isRelativePathSafe((string) ($linknorm ?? ''));
				break;
		}

		$v_header['type'] = match ($typeFlag) {
			self::TYPE_DIRECTORY => 'directory',
			self::TYPE_SYMLINK => 'symlink',
			self::TYPE_HARDLINK => 'hardlink',
			self::TYPE_CHAR_SPECIAL => 'char_device',
			self::TYPE_BLOCK_SPECIAL => 'block_device',
			self::TYPE_FIFO => 'fifo',
			default => 'file',
		};

		return true;
	}

	/**
	 * Reads a GNU long-name ('L') or long-link ('K') data block, then reads the
	 * following real entry header and stores the long name in $v_header[$field].
	 *
	 * @param array  &$v_header
	 * @param string $field     'filepath' or 'linkpath'.
	 * @return bool True on success.
	 */
	private function _readLongHeader(array &$v_header, string $field = 'filepath'): bool
	{
		$v_data = '';
		$n = (int) floor($v_header['size'] / 512);
		for ($i = 0; $i < $n; $i++) {
			$v_data .= $this->_readBlock();
		}
		if (($v_header['size'] % 512) !== 0) {
			$v_data .= $this->_readBlock();
		}

		$v_binary_data = $this->_readBlock();
		if (!$this->_readHeader($v_binary_data, $v_header)) {
			return false;
		}

		$v_header[$field] = rtrim($v_data, "\x00");

		if ($field === 'filepath') {
			$filenorm = $this->_normalizePath($v_header['filepath']);
			$v_header['filepath_norm'] = $filenorm;
			$v_header['filesafe'] = $this->_isRelativePathSafe((string) ($filenorm ?? ''));
			$v_header['name'] = basename($v_header['filepath']);
			$v_header['filename'] = $v_header['filepath'];
		}

		return true;
	}

	/**
	 * Handles GNU long-name ('L') and long-link ('K') extension headers.
	 * Mutates $v_header in place.
	 *
	 * @param array &$v_header
	 * @return bool True if a fatal read error occurred (caller should abort).
	 */
	protected function processLongHeader(array &$v_header): bool
	{
		$typeFlag = $v_header['typeflag'];

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

	// =========================================================================
	// Private — Path Security
	// =========================================================================

	/**
	 * Returns true when $path contains no traversal sequences that could escape
	 * a containing directory (no '..', no leading '/', no Windows drive letter).
	 *
	 * @param string $path
	 * @return bool
	 * @since 4.3.3
	 */
	private function _isRelativePathSafe(string $path): bool
	{
		if ($path === '') {
			return false;
		}
		if ($path[0] === '/' || $path[0] === '\\') {
			return false;
		}
		if (strlen($path) >= 3 && $path[1] === ':') {
			return false;
		}
		foreach (preg_split('/[\/\\\\]/', $path) as $part) {
			if ($part === '..') {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns true when $v_filepath is contained within $p_destPath after
	 * normalisation.  Prevents Zip Slip attacks.
	 * Returns true unconditionally when $p_destPath is null (scan mode).
	 *
	 * @param ?string $v_filepath
	 * @param ?string $p_destPath
	 * @return bool
	 * @since 4.3.3
	 */
	private function _validatePathSecurity(?string $v_filepath, ?string $p_destPath): bool
	{
		if ($p_destPath === null || $p_destPath === '') {
			return true;
		}

		$normalizedFilePath = $this->_normalizePath($v_filepath);
		$normalizedDestPath = $this->_normalizePath($p_destPath);

		if ($normalizedFilePath === null || $normalizedDestPath === null) {
			return false;
		}

		return str_starts_with($normalizedFilePath . '/', $normalizedDestPath . '/');
	}

	/**
	 * Returns true when $v_linkpath, resolved relative to $v_dir, is contained
	 * within $p_destPath.
	 *
	 * @param string  $v_linkpath  Link target (absolute or relative).
	 * @param string  $v_dir       Directory where the link file lives.
	 * @param ?string $p_destPath  Extraction root.
	 * @return bool
	 * @since 4.3.3
	 */
	private function _validateLinkTarget(string $v_linkpath, string $v_dir, ?string $p_destPath): bool
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
	 * Resolves '.' and '..' sequences in $path without touching the filesystem.
	 * Returns null when the path tries to escape its root (e.g. '/../').
	 *
	 * @param ?string $path
	 * @return ?string
	 */
	protected function _normalizePath(?string $path): ?string
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
					return null;
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
	 * Normalises path separators and removes Windows drive letters.
	 *
	 * @param string $p_destPath
	 * @param bool   $p_remove_disk_letter
	 * @return string
	 */
	protected function _translateWinPath(string $p_destPath, bool $p_remove_disk_letter = true): string
	{
		if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
			return $p_destPath;
		}
		if ($p_remove_disk_letter && ($v_position = strpos($p_destPath, ':')) !== false) {
			$p_destPath = substr($p_destPath, $v_position + 1);
		}
		return str_replace('\\', '/', $p_destPath);
	}

	// =========================================================================
	// Private — Manifest Helpers
	// =========================================================================

	/**
	 * Finds the canonical key for $path in the clean manifest, triggering a lazy
	 * scan if the manifest has not yet been populated.
	 *
	 * @param string $path
	 * @return ?string The matching key, or null if not found.
	 * @since 4.3.3
	 */
	private function _findManifestKey(string $path): ?string
	{
		if ($this->_tarManifest === null) {
			$this->getManifest();
		}
		if ($this->_tarManifest === null) {
			return null;
		}
		if (isset($this->_tarManifest[$path])) {
			return $path;
		}
		$withSep = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
		if (isset($this->_tarManifest[$withSep])) {
			return $withSep;
		}
		$withoutSep = rtrim($path, '/\\');
		if ($withoutSep !== $path && isset($this->_tarManifest[$withoutSep])) {
			return $withoutSep;
		}
		return null;
	}

	/**
	 * Finds the canonical key for $path in the extraction manifest.
	 *
	 * @param string $path
	 * @return ?string
	 * @since 4.3.3
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
	 * Sorts $manifest so that directory entries precede file entries.
	 * Within each group keys are sorted alphabetically.
	 *
	 * @param array &$manifest
	 * @since 4.3.3
	 */
	private function _sortManifest(array &$manifest): void
	{
		if (empty($manifest)) {
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

	// =========================================================================
	// Private — Filesystem Helpers
	// =========================================================================

	/**
	 * Ensures that $p_dir exists, creating it (and any missing parents) if needed.
	 *
	 * @param string   $p_dir
	 * @param null|int $workingMode  Mode to use when creating a new directory.  Pass
	 *                               `0o755` (or another traversable mode) when a
	 *                               deferred-chmod mechanism will apply the final mode
	 *                               later; omit to use {@see getDirModeOverride()}.
	 *                               This is used for intermediate parent directories
	 *                               that have no explicit archive entry.
	 * @return bool True when the directory exists or was successfully created.
	 */
	protected function _dirCheck(string $p_dir, ?int $workingMode = null): bool
	{
		if (@is_dir($p_dir) || $p_dir === '') {
			return true;
		}

		$p_parent_dir = dirname($p_dir);
		if ($p_parent_dir !== $p_dir && $p_parent_dir !== '' && !$this->_dirCheck($p_parent_dir, $workingMode)) {
			return false;
		}

		$v_dirMode = $workingMode ?? $this->getDirModeOverride() ?? static::DEFAULT_DIR_MODE;
		if (!@mkdir($p_dir, $v_dirMode)) {
			$this->_error("Unable to create directory '$p_dir'");
			return false;
		}
		@chmod($p_dir, $v_dirMode);

		return true;
	}

}
