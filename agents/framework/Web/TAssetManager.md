# Web/TAssetManager

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`TAssetManager`**

## Class Info
**Location:** `framework/Web/TAssetManager.php`
**Namespace:** `Prado\Web`

## Overview
TAssetManager provides a scheme to allow web clients visiting private files that are normally web-inaccessible. It copies files to be published into a web-accessible directory.

## Key Features
- Manages publishing of files and directories to web-accessible locations
- Supports both file and directory publishing with recursive copying
- Implements timestamp checking to ensure published files are up-to-date
- Uses performance mode to skip timestamp checks when appropriate
- Generates hashed paths for published assets to avoid conflicts
- Supports tar file extraction and publishing
- Supports symbolic-link publishing (`LinkAssets`) and timestamp-versioned URLs (`AppendTimestamp`) — @since 4.3.3

## Configuration
### XML Format
```xml
<modules>
    <module id="asset" class="Prado\Web\TAssetManager"
        BasePath="Application.assets" BaseUrl="/assets"
        LinkAssets="false" AppendTimestamp="false" TimestampVar="v" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'asset' => [
            'class' => 'Prado\Web\TAssetManager',
            'properties' => [
                'BasePath' => 'Application.assets',
                'BaseUrl' => '/assets',
            ],
        ],
    ],
];
```

### Properties

**Existing:**
- `BasePath`: The root directory storing published asset files (default: `'assets'`)
- `BaseUrl`: The base URL for accessing the publishing directory
- `CheckTimestamp`: Whether to use modification-time checking (default: `false`)

**New in 4.3.3:**
- `LinkAssets` (bool): Use symbolic links instead of copying files when publishing. Default: `false`.
- `ForceCopy` (bool): Copy asset files even if they already exist in the target directory. Default: `false`.
- `AppendTimestamp` (bool): Append `?{TimestampVar}={mtime}` to every published single-file asset URL for cache-busting. Default: `false`.
- `TimestampVar` (string): Query parameter name used when `AppendTimestamp` is true. Default: `'v'`.
- `HashCallback` (?callable): Custom callback for generating the asset directory hash.
- `BeforeCopy` (?callable): Callback invoked before copying each file/directory. Return `false` to skip.
- `AfterCopy` (?callable): Callback invoked after each file/directory is successfully copied.
- `AssetMap` (array): Mapping from source asset files (keys) to target asset files (values).
- `Only` (?array): Glob patterns that file paths must match to be copied.
- `Except` (?array): Glob patterns that exclude files from being copied.
- `CaseSensitive` (bool): Whether `Only`/`Except` patterns are case sensitive. Default: `true`.
- `FileMode` (?int): Unix permissions for newly published asset files.
- `DirMode` (int): Unix permissions for newly created asset directories. Defaults to `Prado::getDefaultDirPermissions()`.

## Core Methods
### Initialization
- `init($config)`: Initializes the module with configuration

### Publishing
- `publish($path, $checkTimestamp)`: Publishes a file, directory, or `IPublishable` asset — @since 4.4.0
- `publishFilePath($path, $checkTimestamp)`: Publishes a file or directory and returns the URL
- `getPublishedPath($path)`: Returns the published path without actually publishing
- `getPublishedUrl($path)`: Returns the published URL without actually publishing

Both accessors throw `TInvalidDataValueException` for a path that does not exist, matching
`publish()`. Without it every missing path resolves to one hashed directory. — @since 4.4.0

### Directory Operations
- `copyDirectory($src, $dst)`: Copies a directory recursively to a destination
- `copyFile($src, $dst)`: Copies a file to a directory with timestamp checking
- `publishTarFile($tarfile, $md5sum, $checkTimestamp)`: Publishes a tar file by extracting it

A tar publish extracts the contents, copies the checksum file, and writes a completion
marker last, so the marker appears only once the publish is complete. — @since 4.4.0

| condition | result |
|---|---|
| marker absent, or older than the checksum file | the tar extracts |
| marker present and at least as new as the checksum file | the extraction is skipped |
| `deployTarFile()` returns false | `TIOException` (`assetmanager_tarfile_extract_failed`); no marker, so a later call retries |

The published checksum file no longer gates the extraction. It is the same file at the same
hashed path that `publish()` produces for that checksum on its own, so a copy of it says
nothing about whether the contents were ever extracted. The marker is keyed by the checksum
file path (`directoryCompleteMarker()`), so two tars whose checksums share a published
directory each keep their own.

## Subclass Extension Points
A subclass overrides these instead of re-implementing the surrounding machinery:

| Method | Overriding it changes |
|---|---|
| `copyDirectoryFile($srcPath, $dstPath, $relativePath, $options)` | how one file of a directory publish is handled, keeping the walk, the visited-path cycle guard, `PATH_COPY_EXCEPTIONS`, the marker exclusion, and the directory creation — @since 4.4.0 |
| `deployTarFile($path, $destination, $options)` | how a tar archive is extracted; return false or throw to report failure |
| `virtualAssetPath($asset)` | the path an `IPublishable` publishes under, leaving `virtualAssetTarget()` to resolve the location — @since 4.4.0 |
| `publishedLocation($path, $isDir, $fileName)` | the hashed directory, destination, and URL of any published path |
| `hash($path)` | the asset sub-directory name |
| `symlink($target, $link)` | how a linked asset is created |

`Prado\Test\Unit\Web\TAssetManagerTest` is written against `getTestClass()` and
`newAssetManager()`, so a subclass test extends it and overrides those two methods to run the
whole suite against the subclass.

## Internals
- Uses `hash()` method to generate CRC32 hashes for paths
- Maintains a `_published` array to cache published asset paths
- Supports performance mode optimization where timestamp checks are skipped
- Implements directory traversal with filtering of .svn and .git directories
- Uses default file/directory permissions from [Prado](../Prado.md)::getDefault{Dir,File}Permissions()

## Exception Handling
- [TConfigurationException](../Exceptions/TConfigurationException.md): Thrown when BasePath is invalid or not writable
- [TInvalidDataValueException](../Exceptions/TInvalidDataValueException.md): Thrown for invalid file paths or tar checksums
- [TIOException](../Exceptions/TIOException.md): Thrown for an invalid tar file, and for a tar extraction that fails