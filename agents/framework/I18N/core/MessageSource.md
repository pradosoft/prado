# I18N/core/MessageSource

### Directories
[framework](./INDEX.md) / [I18N](./I18N/INDEX.md) / [core](./I18N/core/INDEX.md) / **`MessageSource`**

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

## See Also

- [IMessageSource](./IMessageSource.md) - Interface definition
- [MessageSource_XLIFF](./MessageSource_XLIFF.md) - XLIFF implementation
- [MessageSource_PHP](./MessageSource_PHP.md) - PHP array implementation
- [MessageSource_gettext](./MessageSource_gettext.md) - GNU gettext implementation
- [MessageSource_Database](./MessageSource_Database.md) - Database implementation
