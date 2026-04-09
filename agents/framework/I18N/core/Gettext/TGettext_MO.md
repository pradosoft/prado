# I18N / core / Gettext / TGettext_MO

### Directories
[./](../../INDEX.md) > [I18N](../INDEX.md) > [core](../INDEX.md) > [Gettext](./INDEX.md) > [TGettext_MO](./TGettext_MO.md)

**Location:** `framework/I18N/core/Gettext/TGettext_MO.php`
**Namespace:** `Prado\I18N\core\Gettext`

## Overview

Reads and writes GNU gettext compiled binary `.mo` files. Used at runtime for fast parsing of translation data.

## Usage

```php
$mo = new TGettext_MO('/path/to/messages.mo');
$mo->load();

// Access translations
echo $mo->strings['Hello'];  // "Hallo"

// Modify
$mo->strings['Hello'] = 'Guten Tag';
$mo->save();
```

## MO File Format

Binary format with header:
- Magic number (detects endianness)
- Version
- String count
- Offset tables
- String data

## Magic Numbers

| Value | Byte Order | Endianness |
|-------|------------|------------|
| `0x950412de` | Little-endian | Intel/ARM |
| `0xde120495` | Big-endian | Motorola/SPARC |

## Key Methods

### `load($file = null): bool`

Load `.mo` file from disk.

### `save($file = null): bool`

Write `.mo` file to disk.

### `_read($bytes): string`

Read raw bytes.

### `_readInt($bigendian = false): int`

Read 32-bit integer.

### `_readStr($params): string`

Read null-terminated string at offset.

## Endianness Control

```php
$mo->writeBigEndian = true;  // Force big-endian output
$mo->save();
```

## See Also

- [TGettext](./TGettext.md) - Abstract base class
- [TGettext_PO](./TGettext_PO.md) - Text PO file handler
- [MessageSource_gettext](../MessageSource_gettext.md) - Message source using this