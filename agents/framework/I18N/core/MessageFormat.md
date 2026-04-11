# I18N/core/MessageFormat

### Directories
[framework](./INDEX.md) / [I18N](./I18N/INDEX.md) / [core](./I18N/core/INDEX.md) / **`MessageFormat`**

**Location:** `framework/I18N/core/MessageFormat.php`
**Namespace:** `Prado\I18N\core`

## Overview
Looks up translated strings from a [MessageSource](./MessageSource.md) and performs token substitution. Handles untranslated message tracking.

## Usage

```php
$source = MessageSource::factory('XLIFF', '/path/to/messages/');
$source->setCulture('fr_FR');
$source->setCache(new MessageCache('/tmp'));

$formatter = new MessageFormat($source, 'UTF-8');

echo $formatter->format('Hello {name}', ['{name}' => 'Alice']);
```

## Constructor

```php
public function __construct(IMessageSource $source, string $charset = 'UTF-8')
```

## Key Methods

### `format($string, $args = [], $catalogue = null, $charset = null): string`

Translate and substitute placeholders. Placeholders use `{key}` syntax.

### `setUntranslatedPS([$prefix, $suffix]): void`

Wrap untranslated messages with prefix/suffix:
```php
$formatter->setUntranslatedPS(['[T]', '[/T]']);
// Untranslated "Hello" becomes "[T]Hello[/T]"
```

### `getSource(): MessageSource`

Get the underlying message source.

## Catalogue Loading

Catalogues are loaded lazily and cached. Call `loadCatalogue()` to preload:
```php
$formatter->loadCatalogue('messages');
```

## See Also

- [IMessageSource](./IMessageSource.md) - Interface for backends
- [MessageSource](./MessageSource.md)::factory() - Create message sources
- [Translation](../Translation.md) - Static helper wrapping MessageFormat
