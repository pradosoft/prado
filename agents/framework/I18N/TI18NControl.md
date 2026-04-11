# I18N/TI18NControl

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TI18NControl`**

## Class Info
**Location:** `framework/I18N/TI18NControl.php`
**Namespace:** `Prado\I18N`

## Overview
Base class for I18N-aware PRADO controls. Provides `Culture` and `Charset` properties with hierarchical fallback to application globalization settings.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Culture` | string | Locale identifier (e.g., `'en_US'`, `'zh_CN'`). Falls back to application culture. |
| `Charset` | string | Character encoding. Falls back to application charset, then `'UTF-8'`. |

## Property Resolution Order

**Culture:**
1. Control-level `Culture` property
2. Application globalization culture

**Charset:**
1. Control-level `Charset` property
2. Application globalization charset
3. Default charset from globalization
4. `'UTF-8'`

## See Also

- [TTranslate](./TTranslate.md) - Translation control extending this base
- [TDateFormat](./TDateFormat.md) - Date formatting control
- [TNumberFormat](./TNumberFormat.md) - Number formatting control