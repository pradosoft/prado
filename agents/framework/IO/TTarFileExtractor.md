# IO/TTarFileExtractor

### Directories
[framework](../INDEX.md) / [IO](./INDEX.md) / **`TTarFileExtractor`**

## Class Info
**Location:** `framework/IO/TTarFileExtractor.php`
**Namespace:** `Prado\IO`

## Overview
Extracts files from TAR archives. Supports uncompressed, gzip, bzip2, and LZMA-compressed archives as local files or remote URLs. Major enhancements in 4.3.3 add atomic extraction with rollback-on-failure, decompression, URL download, conflict modes, per-entry type constants, and Zip Slip defense.

## Supported Formats

| Format | Extension | Requirement |
|--------|-----------|-------------|
| Uncompressed | `.tar` | — |
| Gzip | `.tar.gz`, `.tgz` | `zlib` PHP extension or `gunzip`/`gzip` CLI |
| Bzip2 | `.tar.bz2`, `.tbz2` | `bz2` PHP extension or `bunzip2`/`bzip2` CLI |
| LZMA | `.tar.xz`, `.txz` | `xzdec` or `xz` CLI command |

Format is detected from magic bytes; file extension is used as fallback. Remote URLs (`http://`, `https://`, `ftp://`) are downloaded to a temp file before extraction.

## Basic Usage

```php
$extractor = new TTarFileExtractor('/path/to/archive.tar.gz');
$extractor->extract('/destination/directory');

// With options:
$extractor->setAtomic(true);
$extractor->setConflictMode(TTarFileExtractor::CONFLICT_SKIP);
$extractor->setStrict(false);  // skip (don't throw) on security violations
$extractor->extract('/destination/directory');

// Inspect without extracting:
$manifest = $extractor->getManifest();
```

## Configuration Properties (@since 4.3.3)

| Property | Default | Description |
|----------|---------|-------------|
| `Atomic` | `false` | Two-phase extract: stage to temp dir first, then merge into destination |
| `ConflictMode` | `CONFLICT_OVERWRITE` | What to do when a destination file already exists; int constant or callable |
| `Strict` | `true` | When `true`, security violations throw; when `false`, they are skipped and recorded |
| `DirModeOverride` | `null` | UNIX mode applied to all extracted directories (overrides archive); falls back to `Prado::getDefaultDirPermissions()` for intermediate parents |
| `FileModeOverride` | `null` | UNIX mode applied to all extracted files (overrides archive) |
| `UrlTimeout` | `6.0` | Seconds to wait when downloading a remote archive |
| `RetainTempFile` | `false` | Keep temp file between `getManifest()` and `extract()` calls on the same archive |

## Conflict Mode Constants (@since 4.3.3)

| Constant | Value | Behavior |
|----------|-------|----------|
| `CONFLICT_ERROR` | `0` | Throw on first conflicting file |
| `CONFLICT_SKIP` | `1` | Silently skip, record `reason = 'conflict_skip'` |
| `CONFLICT_OVERWRITE` | `2` | Always overwrite (default, historical behavior) |
| `CONFLICT_NEWER` | `3` | Keep whichever file (archive or existing) is newer |
| `CONFLICT_OLDER` | `4` | Keep whichever file is older |

A callable can also be provided: `setConflictMode(callable)`. The callable receives `($archivePath, $destPath, $archiveMtime, $destMtime, &$reason)` and returns `true` to overwrite, `false` to skip.

## Entry Type Constants (@since 4.3.3)

| Constant | Value | Description |
|----------|-------|-------------|
| `TYPE_FILE` | `0` | Regular file (typeflag `'0'` or empty) |
| `TYPE_HARDLINK` | `1` | Hard link |
| `TYPE_SYMLINK` | `2` | Symbolic link |
| `TYPE_CHAR_SPECIAL` | `3` | Character device |
| `TYPE_BLOCK_SPECIAL` | `4` | Block device |
| `TYPE_DIRECTORY` | `5` | Directory |
| `TYPE_FIFO` | `6` | FIFO special file |
| `TYPE_CONTIGUOUS` | `7` | Contiguous file |
| `TYPE_GNU_LONG_NAME` | `76` | GNU long-filename extension (internal) |
| `TYPE_GNU_LONG_LINK` | `75` | GNU long-linkpath extension (internal) |

## Compression Constants (@since 4.3.3)

`COMPRESSION_NONE`, `COMPRESSION_GZIP`, `COMPRESSION_BZIP2`, `COMPRESSION_LZMA`

## Skip Reason Constants (@since 4.3.3)

| Constant | Value | When set |
|----------|-------|----------|
| `REASON_ZIP_SLIP` | `'zip_slip'` | Path traversal attempt |
| `REASON_DEVICE` | `'device'` | Device / FIFO special file |
| `REASON_SYMLINK` | `'symlink'` | Symlink target outside destination |
| `REASON_HARDLINK` | `'hardlink'` | Hard link target outside destination |
| `REASON_CONFLICT_SKIP` | `'conflict_skip'` | `CONFLICT_SKIP` mode |
| `REASON_CONFLICT_EXISTING_NEWER` | `'conflict_existing_newer'` | `CONFLICT_NEWER` — existing file is newer |
| `REASON_CONFLICT_EXISTING_OLDER` | `'conflict_existing_older'` | `CONFLICT_OLDER` — existing file is older |
| `REASON_CONFLICT_CALLABLE_SKIP` | `'conflict_callable_skip'` | Callable returned false |
| `REASON_CONFLICT_CALLABLE_ERROR_SKIP` | `'conflict_callable_error_skip'` | Callable threw `TypeError` |

## Security Constants (@since 4.3.3)

| Constant | Value | Description |
|----------|-------|-------------|
| `SECURITY_ZIP_SLIP_ATTACK` | `'zip_slip_attack'` | Manifest `security` field for path traversal |
| `SECURITY_IS_DEVICE` | `'is_device'` | Manifest `security` field for device files |
| `SECURITY_LINKPATH_OUTSIDE_DESTINATION` | `'linkpath_above_root'` | Manifest `security` field for links outside root |

## Atomic Extraction (@since 4.3.3)

When `Atomic=true`:
1. **Phase 1 — Staging**: All entries extracted to a temp staging directory adjacent to the destination. Destination untouched. If phase 1 fails, staging dir is removed; destination unchanged.
2. **Phase 2 — Merge**: Files moved from staging to destination one by one. Overwritten originals are backed up. If merge fails, moved files are removed, originals restored from backup, staging and backup dirs removed.

## Restore on Failure (non-atomic)

In default (non-atomic) mode, files overwritten during extraction are backed up to a private directory. On success the backup is discarded; on failure the newly-written files are removed and originals restored.

## Manifest Inspection (@since 4.3.3)

```php
$extractor = new TTarFileExtractor('/path/to/archive.tar.gz');
$manifest = $extractor->getManifest();  // scans without extracting

foreach ($manifest as $path => $entry) {
    // $entry keys: name, size, mtime, typeflag, linkname,
    //              reason (if skipped), security (if security violation)
}

$skipped = $extractor->getSkippedFiles();  // entries not extracted
```

## Key Methods

| Method | Description |
|--------|-------------|
| `__construct(string $tarpath)` | Accepts local path or URL |
| `extract(string $path = ''): bool` | Extract all entries to `$path` |
| `getManifest(): array` | Return manifest without extracting |
| `getSkippedFiles(): array` | Return entries that were skipped during last extraction |
| `getAtomic(): bool` / `setAtomic(bool)` | Atomic mode toggle |
| `getConflictMode()` / `setConflictMode(int\|callable)` | Conflict handling |
| `getStrict(): bool` / `setStrict(bool)` | Security-violation behavior |
| `getDirModeOverride(): ?int` / `setDirModeOverride(?int)` | Directory UNIX mode override |
| `getFileModeOverride(): ?int` / `setFileModeOverride(?int)` | File UNIX mode override |
| `getUrlTimeout(): float` / `setUrlTimeout(float)` | Remote URL download timeout |
| `getCompression(): int` | Detected compression type constant |

## Gotchas

- **LZMA requires CLI** — no PHP extension exists; `xzdec` or `xz` must be in `$PATH`.
- **CLI fallback for gz/bz2** — if the PHP extension is absent, the corresponding CLI tool is tried. If neither is available, extraction fails.
- **Strict mode default is `true`** — path traversal (Zip Slip) throws by default. Set `Strict=false` only if you control the archive source.
- **Manifest `reason`/`security` keys** — present only on skipped entries. `security` is set only for security violations (not conflict skips), making it easy to distinguish the two categories.
- **Temp files** — remote/LZMA archives are written to `sys_get_temp_dir()`. Set `RetainTempFile=true` when you need to call `getManifest()` and then `extract()` on the same remote archive without downloading twice.

## See Also

- PHP manual: `IntlDateFormatter`, `gzopen`, `bzopen`
