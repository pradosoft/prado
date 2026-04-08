# I18N/core/Gettext/INDEX.md - I18N_CORE_GETTEXT_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

GNU Gettext `.po`/`.mo` file reader/writer for the Prado I18N message source system.

## Classes

- **`TGettext`** — Abstract base class for Gettext file handling. Defines the common interface: `load($file)`, `save($file)`, `toArray()`, `fromArray($data)`. Handles file validation and shared header parsing.

- **`TGettext_MO`** — Reads and writes compiled Gettext binary `.mo` files. Parses the magic number, header offsets, and string tables for both little-endian and big-endian byte orders. Used at runtime for performance (binary format is faster to parse than `.po`).

- **`TGettext_PO`** — Reads and writes Gettext source `.po` text files. Parses `msgid` / `msgstr` pairs, plural forms, metadata headers, and translator comments. Used for translation authoring and editing.

## Data Format

Both classes normalise to the same array structure:

```php
[
    ''       => ['Content-Type' => 'text/plain; charset=UTF-8', ...], // header
    'Hello'  => 'Hola',          // singular translation
    'One item|%d items' => ['Un elemento', '%d elementos'],  // plural forms
]
```

## Patterns & Gotchas

- **`.po` → `.mo` compilation** — `.po` files must be compiled to `.mo` for production use. `TGettext_PO` can read/write `.po`; `TGettext_MO` handles the compiled binary.
- **Byte order** — `TGettext_MO` detects endianness from the magic number (`0x950412de` = little-endian, `0xde120495` = big-endian). Never assume a fixed byte order.
- **Plural forms** — Plural expressions in the `.po` header (`Plural-Forms: nplurals=2; plural=(n!=1);`) are parsed but evaluation is handled by the calling `MessageFormat`.
- These classes are used internally by `MessageSource_gettext` in the parent `core/` directory; do not instantiate directly.
