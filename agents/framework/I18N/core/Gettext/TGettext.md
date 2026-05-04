# I18N/core/Gettext/TGettext

### Directories
[framework](../../../INDEX.md) / [I18N](../../INDEX.md) / [core](../INDEX.md) / [Gettext](./INDEX.md) / **`TGettext`**

## Class Info
**Location:** `framework/I18N/core/Gettext/TGettext.php`
**Namespace:** `Prado\I18N\core\Gettext`

## Overview
Abstract base class for GNU Gettext `.po`/`.mo` file handling. Provides common interface and utilities for `TGettext_MO` and `TGettext_PO`.

## Data Structure

Both MO and PO normalize to the same array format:

```php
[
    'meta' => [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'PO-Revision-Date' => '2024-01-15 10:30:00',
        'Last-Translator' => 'John Doe <john@example.com>',
    ],
    'strings' => [
        'Hello' => 'Hallo',
        'One item|%d items' => ['Ein Element', '%d Elemente'],
    ]
]
```

## Factory Method

```php
public static function factory(string $format, string $file = ''): TGettext
```

```php
$mo = TGettext::factory('MO', '/path/to/messages.mo');
$po = TGettext::factory('PO', '/path/to/messages.po');
```

## Static Methods

### `poFile2moFile($pofile, $mofile): bool`

Compile `.po` to `.mo`:
```php
TGettext::poFile2moFile('/path/messages.po', '/path/messages.mo');
```

### `prepare($string, $reverse = false): string`

Escape/unescape special characters for gettext format.

### `meta2array($meta): array`

Parse metadata block to array.

## Instance Methods

### `toArray(): array`

Get meta and strings as array.

### `fromArray($array): bool`

Load from array structure.

### `toMO(): TGettext_MO`

Convert to MO format.

### `toPO(): TGettext_PO`

Convert to PO format.

## See Also

- [TGettext_MO](./TGettext_MO.md) - Binary MO file handler
- [TGettext_PO](./TGettext_PO.md) - Text PO file handler