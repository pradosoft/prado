# I18N/core/MessageSource_XLIFF

### Directories
[framework](../../INDEX.md) / [I18N](../INDEX.md) / [core](INDEX.md) / **`MessageSource_XLIFF`**

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
  de_DE/
    messages.xml         # or in a culture directory
```

Each `.xml` name is also tried as `.xlf`; `catalogues()` lists both.

## Key Methods

### `save($catalogue = 'messages'): bool`

Save untranslated messages as new `<trans-unit>` elements, with CDATA source and target, and clear the untranslated list.

### `update($text, $target, $comments, $catalogue = 'messages'): bool`

Set the `<target>` and `<note>` text of the trans-unit whose source text equals `$text` (strict string comparison, text or CDATA). False when no unit matches; the file is unchanged.

### `delete($message, $catalogue = 'messages'): bool`

Remove the trans-unit whose source text equals `$message`.

## See Also

- [MessageSource](./MessageSource.md) - Abstract base class
- [MessageSource_PHP](./MessageSource_PHP.md) - PHP array alternative
- [MessageSource_gettext](./MessageSource_gettext.md) - GNU gettext alternative
