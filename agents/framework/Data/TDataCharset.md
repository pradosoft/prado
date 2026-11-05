# Data/TDataCharset

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDataCharset`**

## Class Info
**Location:** `framework/Data/TDataCharset.php`
**Namespace:** `Prado\Data`
**Extends:** [`TEnumerable`](../TEnumerable.md)
**Since:** 4.3.3

## Overview

`TDataCharset` defines IANA-registered charset identifiers for use with [`TDbConnection::setCharset()`](./TDbConnection.md). Each constant holds the canonical IANA name; the connection layer translates these to the driver-specific names when the charset is applied.

Using these constants instead of raw strings avoids typos and provides IDE completion for the most common database charsets.

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `UTF8` | `'UTF-8'` | UTF-8 — supported by all drivers |
| `UTF16` | `'UTF-16'` | UTF-16 — driver resolves byte order |
| `UTF16LE` | `'UTF-16LE'` | UTF-16 little-endian |
| `UTF16BE` | `'UTF-16BE'` | UTF-16 big-endian |
| `Latin1` | `'ISO-8859-1'` | ISO-8859-1 (Latin-1) — Western European |
| `Latin2` | `'ISO-8859-2'` | ISO-8859-2 (Latin-2) — Central European |
| `ASCII` | `'US-ASCII'` | 7-bit ASCII |
| `Win1250` | `'windows-1250'` | Windows-1250 — Central European |
| `Win1251` | `'windows-1251'` | Windows-1251 — Cyrillic |
| `Win1252` | `'windows-1252'` | Windows-1252 — Western European |
| `KOI8R` | `'KOI8-R'` | KOI8-R — Russian Cyrillic |
| `KOI8U` | `'KOI8-U'` | KOI8-U — Ukrainian Cyrillic |

## Usage

```php
use Prado\Data\TDataCharset;

$conn = new TDbConnection($dsn, $user, $pass);
$conn->Charset = TDataCharset::UTF8;
$conn->Active = true;
```

## See Also

- [`TDbConnection`](./TDbConnection.md) — `Charset` property accepts these values
- [`TDbDriver`](./TDbDriver.md) — companion enumeration for driver name strings
