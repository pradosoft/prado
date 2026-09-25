# Web/UI/TCachePageStatePersisterTimeoutMode

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TCachePageStatePersisterTimeoutMode`**

**Location:** `framework/Web/UI/TCachePageStatePersisterTimeoutMode.php`
**Namespace:** `Prado\Web\UI`
**Since:** 4.4.0

## Overview
The `TEnumerable` for [TCachePageStatePersister](./TCachePageStatePersister.md)`::CacheTimeoutMode`: where the lifetime of a cached page state comes from.

| Value | Lifetime of a page state saved now |
|---|---|
| `Fixed` | `CacheTimeout` (default, the behavior before 4.4.0) |
| `Session` | the session module's `Timeout`, or `CacheTimeout` without one |
| `Auth` | `TAuthManager::AuthExpire` for an authenticated user, or `CacheTimeout` |
| `Auto` | `Auth`, then `Session`, then `CacheTimeout` |

`AuthExpire` applies only when it is above 0 and `AllowAutoLogin` is off.

## Configuration
```xml
<pages StatePersisterClass="Prado\Web\UI\TCachePageStatePersister"
       StatePersister.CacheTimeoutMode="Auto" />
```
