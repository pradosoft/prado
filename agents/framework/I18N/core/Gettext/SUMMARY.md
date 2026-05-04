# I18N/core/Gettext/SUMMARY.md

GNU Gettext `.po`/`.mo` file reader/writer for the Prado I18N message source system.

## Classes

- **`TGettext`** — Abstract base class for Gettext file handling; defines interface: `load($file)`, `save($file)`, `toArray()`, `fromArray($data)`.

- **`TGettext_MO`** — Reads and writes compiled Gettext binary `.mo` files; handles both little-endian and big-endian byte orders.

- **`TGettext_PO`** — Reads and writes Gettext source `.po` text files; parses `msgid`/`msgstr` pairs, plural forms, metadata headers.
