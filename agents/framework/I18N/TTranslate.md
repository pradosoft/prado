# I18N/TTranslate

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TTranslate`**

## Class Info
**Location:** `framework/I18N/TTranslate.php`
**Namespace:** `Prado\I18N`

## Overview
Template control for translating text with parameter substitution. Renders translated text with support for named placeholders `{key}` that get replaced with values.

## Usage

```php
<com:TTranslate Text="Goodbye" />

<com:TTranslate Parameters.time=<%= time() %> >
  The unix-time is "{time}".
</com:TTranslate>
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Text` | string | The text to translate |
| `Catalogue` | string | Message catalogue name (default: `'messages'`) |
| `Key` | string | Key for message lookup |
| `Trim` | bool | Trim whitespace from content (default: `true`) |
| `Parameters` | TAttributeCollection | Named parameters for substitution |

## Parameter Substitution

Parameters are enclosed in `{key}` syntax:

```php
<com:TTranslate>
  Hello {name}, you have {count} messages.
  <com:TTranslateParameter Key="name">Alice</com:TTranslateParameter>
  <com:TTranslateParameter Key="count">5</com:TTranslateParameter>
</com:TTranslate>
```

## See Also

- [TI18NControl](./TI18NControl.md) - Base class providing Culture/Charset
- [TTranslateParameter](./TTranslateParameter.md) - Parameter component
- [Translation](./Translation.md) - Static helper for programmatic translation