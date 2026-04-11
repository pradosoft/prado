# I18N/core/MessageSource_XLIFF

### Directories
[framework](./INDEX.md) / [I18N](./I18N/INDEX.md) / [core](./I18N/core/INDEX.md) / **`MessageSource_XLIFF`**

**Location:** `framework/I18N/core/MessageSource_XLIFF.php`
**Namespace:** `Prado\I18N\core`

## Overview
XLIFF (XML Localization Interchange File Format) message source. Industry-standard translation format.

## XLIFF File Format

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.0">
  <file source-language="EN" target-language="de_DE" 
        datatype="plaintext" original="messages" 
        date="2024-01-15T10:30:00Z">
    <body>
      <trans-unit id="1">
        <source>Hello</source>
        <target>Hallo</target>
        <note>Basic greeting</note>
      </trans-unit>
    </body>
  </file>
</xliff>
```

## Usage

```php
$source = MessageSource::factory('XLIFF', '/path/to/messages/');
$source->setCulture('de_DE');
$source->setCache(new MessageCache('/tmp'));

$formatter = new MessageFormat($source);
echo $formatter->format('Hello');
```

## Catalogue Structure

```
messages/
  messages.xml           # default
  messages.de_DE.xml     # German
  messages.en_AU.xml     # Australian English
  de/
    DE/
      messages.xml       # or in subdirectory
```

## Key Methods

### `save($catalogue = 'messages'): bool`

Save untranslated messages as new `<trans-unit>` elements.

### `update($text, $target, $comments, $catalogue = 'messages'): bool`

Update the `<target>` element of a trans-unit.

### `delete($message, $catalogue = 'messages'): bool`

Remove a trans-unit from the XLIFF file.

## See Also

- [MessageSource](./MessageSource.md) - Abstract base class
- [MessageSource_PHP](./MessageSource_PHP.md) - PHP array alternative
- [MessageSource_gettext](./MessageSource_gettext.md) - GNU gettext alternative
