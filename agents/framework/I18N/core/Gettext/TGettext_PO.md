# I18N / core / Gettext / TGettext_PO

### Directories
[./](../../INDEX.md) > [I18N](../INDEX.md) > [core](../INDEX.md) > [Gettext](./INDEX.md) > [TGettext_PO](./TGettext_PO.md)

**Location:** `framework/I18N/core/Gettext/TGettext_PO.php`
**Namespace:** `Prado\I18N\core\Gettext`

## Overview

Reads and writes GNU gettext source text `.po` files. Used for translation authoring and editing.

## PO File Format

```po
#  translator comments
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"PO-Revision-Date: 2024-01-15 10:30:00\n"

msgid "Hello"
msgstr "Hallo"

msgid "One item|%d items"
msgstr[0] "Ein Element"
msgstr[1] "%d Elemente"
```

## Usage

```php
$po = new TGettext_PO('/path/to/messages.po');
$po->load();

// Access translations
echo $po->strings['Hello'];  // "Hallo"

// Modify
$po->strings['Hello'] = 'Guten Tag';
$po->save();
```

## Key Methods

### `load($file = null): bool`

Parse `.po` file.

### `save($file = null): bool`

Write `.po` file.

## Metadata Header

The empty `msgid` (`""`) stores metadata:
```php
$po->meta['Content-Type'] = 'text/plain; charset=UTF-8';
$po->meta['PO-Revision-Date'] = date('Y-m-d H:i:s');
```

## See Also

- [TGettext](./TGettext.md) - Abstract base class
- [TGettext_MO](./TGettext_MO.md) - Binary MO file handler
- [TGettext](./TGettext.md)::poFile2moFile() - Compile PO to MO