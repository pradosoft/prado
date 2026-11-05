# I18N/TI18NControl

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TI18NControl`**

## Class Info
**Location:** `framework/I18N/TI18NControl.php`
**Namespace:** `Prado\I18N`

## Overview
Base class for I18N-aware PRADO controls. Provides `Culture` and `Charset` properties with hierarchical fallback to application globalization settings. The actual property implementation is provided by [TI18NControlTrait](./TI18NControlTrait.md) — `TI18NControl` simply extends `TControl` and `use`s that trait.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Culture` | string | Locale identifier (e.g., `'en_US'`, `'zh_CN'`). Falls back to application culture. Stored in view state. |
| `Charset` | string | Character encoding. Falls back to application charset, then `'UTF-8'`. Stored in view state. |

## Property Resolution Order

**Culture:**
1. Control-level `Culture` view state
2. Application globalization `getCulture()`
3. `dyDefaultCultureValue('')` dynamic event (for behaviors to override)

**Charset:**
1. Control-level `Charset` view state
2. Application globalization `getCharset()`
3. Application globalization `getDefaultCharset()`
4. `dyDefaultCharsetValue('UTF-8')` dynamic event (for behaviors to override)

## See Also

- [TI18NControlTrait](./TI18NControlTrait.md) - Trait providing the actual implementation
- [TTranslate](./TTranslate.md) - Translation control extending this base
- [TDateFormat](./TDateFormat.md) - Date formatting control
- [TNumberFormat](./TNumberFormat.md) - Number formatting control