# I18N/core/MessageSource

### Directories
[framework](../../INDEX.md) / [I18N](../INDEX.md) / [core](INDEX.md) / **`MessageSource`**

**Location:** `framework/I18N/core/MessageSource.php`
**Namespace:** `Prado\I18N\core`

## Overview
Abstract base class for all message sources. Implements `IMessageSource` interface with caching and catalogue fallback support.

## Factory Method

```php
public static function factory(string $type, string $source = '.', string $filename = ''): MessageSource
```

| Type | Source Parameter |
|------|------------------|
| `'XLIFF'` | Directory containing `.xml`/`.xlf` files |
| `'PHP'` | Directory containing `.php` array files |
| `'gettext'` | Directory containing `.mo`/`.po` files |
| `'Database'` | Connection ID from application modules |

```php
$source = MessageSource::factory('XLIFF', '/path/to/messages/');
$source = MessageSource::factory('Database', 'db1');
```

## Catalogue Fallback

When loading `zh_CN`, the source tries:
1. `messages.zh_CN.xml`
2. `messages.zh.xml`
3. `messages.xml`

## Protected Methods (for Subclasses)

| Method | Description |
|--------|-------------|
| `loadData($variant): array` | Load messages from resource |
| `getSource($variant): string` | Get resource identifier |
| `isValidSource($source): bool` | Validate resource exists |
| `getCatalogueList($catalogue): array` | Get variant list for fallback |
| `getLastModified($source): int` | Get resource mtime for cache |

## Cache Support

```php
$source->setCache(new MessageCache('/tmp'));
```

## Exceptions

| Condition | Exception, error code |
|---|---|
| `factory()` with an unknown type | `TConfigurationException` `messagesource_type_invalid` |
| `factory()` with a missing class file | `TConfigurationException` `messagesource_file_missing` |
| a catalogue cannot be written | [TMessageSourceIOException](./TMessageSourceIOException.md) |
| `new MessageCache($dir)` of a missing or unwritable directory | `TConfigurationException` `messagecache_directory_invalid`, `messagecache_directory_unwritable` |
| `TCache_Lite` cannot open, read, write, or remove | `TIOException` `cachelite_directory_failed`, `cachelite_read_failed`, `cachelite_write_failed`, `cachelite_remove_failed` |
| `TCache_Lite` with an unknown `readControlType` | `TInvalidDataValueException` `cachelite_readcontroltype_invalid` |
| `TGettext::factory()` of an unknown format; `poFile2moFile()` of a missing PO file | `TInvalidDataValueException` `gettext_format_invalid`; `TIOException` `gettext_pofile_missing` |
| `ChoiceFormat::isValid()` of a malformed set | `TInvalidDataValueException` `choiceformat_set_invalid`; a malformed `{n: ...}` notation is false |

## Clock

`MessageSource` uses `TApplicationClockAwareTrait`; `setClock()` overrides the application clock. `formatNow($format, $utc)` stamps the dates the sources write:

| Source | Date | Format |
|---|---|---|
| XLIFF | `<file date>` on save, update, delete | UTC, `Y-m-d\TH:i:s\Z` |
| XLIFF | template `date` | clock time zone, `c` |
| PHP | `info.date` | UTC, `Y-m-d\TH:i:s\Z` |
| gettext | `PO-Revision-Date` | clock time zone, `Y-m-d H:iO` |
| Database | `date_added`, `date_modified` | Unix time |

A cache hit compares a source's last-modified time with the cache file's modification time. The file sources report file times, and the database reports clock times.

## See Also

- [IMessageSource](./IMessageSource.md) - Interface definition
- [MessageSource_XLIFF](./MessageSource_XLIFF.md) - XLIFF implementation
- [MessageSource_PHP](./MessageSource_PHP.md) - PHP array implementation
- [MessageSource_gettext](./MessageSource_gettext.md) - GNU gettext implementation
- [MessageSource_Database](./MessageSource_Database.md) - Database implementation
