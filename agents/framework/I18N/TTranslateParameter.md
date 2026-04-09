# I18N / TTranslateParameter

### Directories
[./](../INDEX.md) > [I18N](./INDEX.md) > [TTranslateParameter](./TTranslateParameter.md)

**Location:** `framework/I18N/TTranslateParameter.php`
**Namespace:** `Prado\I18N`

## Overview

Template control for specifying named parameters inside `TTranslate`. Provides key-value substitution for translated text placeholders.

## Usage

```php
<com:TTranslate>
  {greeting} {name}!
  <com:TTranslateParameter Key="name">World</com:TTranslateParameter>
  <com:TTranslateParameter Key="greeting">Hello</com:TTranslateParameter>
</com:TTranslate>
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Key` | string | **Required.** The placeholder key (enclosed in `{}`) |
| `Trim` | bool | Trim whitespace from content (default: `true`) |
| `Value` | string | Direct value for the parameter |
| `Parameter` | string | Computed value (content or Value property) |

## See Also

- [TTranslate](./TTranslate.md) - Parent translation component