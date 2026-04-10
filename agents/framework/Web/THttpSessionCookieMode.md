# Web/THttpSessionCookieMode

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`THttpSessionCookieMode`**

## Class Info
**Location:** `framework/Web/THttpSessionCookieMode.php`
**Namespace:** `Prado\Web`

## Overview
THttpSessionCookieMode defines the enumerable type for session cookie storage methods. It controls how the session ID is stored and transmitted via cookies.

## Key Properties/Methods

- `None` - Not using cookies for session ID (deprecated in PHP 8.4)
- `Allow` - Using cookies for session ID (deprecated in PHP 8.4)
- `Only` - Using cookies only for session ID (current recommended default)

## See Also

- [THttpSession](./THttpSession.md)
