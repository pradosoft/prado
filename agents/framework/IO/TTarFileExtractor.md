# IO / TTarFileExtractor

[./](../INDEX.md) > [IO](./INDEX.md) > [TTarFileExtractor](./TTarFileExtractor.md)

**Location:** `framework/IO/TTarFileExtractor.php`
**Namespace:** `Prado\IO`

## Overview

Extracts files from TAR archives. Supports local files and remote URLs (http://, ftp://).

## Usage

```php
$tar = new TTarFileExtractor('/path/to/archive.tar');
$tar->extract('/destination/directory');
```

## Key Methods

### `extract($path = ''): bool`

Extract all files to the specified directory.

### `extractModify($path, $removePath): bool`

Extract with path prefix removal.

## Features

- Extracts files and directories
- Creates missing directories
- Preserves file modification times
- Handles HTTP/FTP remote tar files (downloads to temp file first)
- Validates file sizes after extraction

## See Also

PHP Manual: TAR archive functions
