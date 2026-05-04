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

## Configuration
### XML Format
```xml
<module id="asset" BasePath="Application.assets" BaseUrl="/assets" />
```

### Properties
- `BasePath`: The root directory storing published asset files (default: 'assets')
- `BaseUrl`: The base URL for accessing the publishing directory (default: '/assets')
- `CheckTimestamp`: Whether to use timestamp checking (default: false)

## Core Methods
### Initialization
- `init($config)`: Initializes the module with configuration

### Publishing
- `publishFilePath($path, $checkTimestamp)`: Publishes a file or directory and returns the URL
- `getPublishedPath($path)`: Returns the published path without actually publishing
- `getPublishedUrl($path)`: Returns the published URL without actually publishing

### Directory Operations
- `copyDirectory($src, $dst)`: Copies a directory recursively to a destination
- `copyFile($src, $dst)`: Copies a file to a directory with timestamp checking
- `publishTarFile($tarfile, $md5sum, $checkTimestamp)`: Publishes a tar file by extracting it

## Internals
- Uses `hash()` method to generate CRC32 hashes for paths
- Maintains a `_published` array to cache published asset paths
- Supports performance mode optimization where timestamp checks are skipped
- Implements directory traversal with filtering of .svn and .git directories
- Uses default file/directory permissions from [Prado](../Prado.md)::getDefault{Dir,File}Permissions()

## Exception Handling
- [TConfigurationException](../../Exceptions/TConfigurationException.md): Thrown when BasePath is invalid or not writable
- [TInvalidDataValueException](../../Exceptions/TInvalidDataValueException.md): Thrown for invalid file paths or tar checksums
- [TIOException](../../Exceptions/TIOException.md): Thrown for invalid tar files